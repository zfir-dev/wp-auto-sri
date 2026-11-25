<?php

if (!defined('ABSPATH')) exit;

class AutoSRI {

    public static function init() {
        // Standard WP enqueued assets
        add_filter('script_loader_tag', [__CLASS__, 'inject_sri'], 10, 3);
        add_filter('style_loader_tag',  [__CLASS__, 'inject_sri'], 10, 4);

        // Output buffer to catch ALL scripts (raw + injected)
        add_action('template_redirect', [__CLASS__, 'start_buffer']);

        // Admin Settings
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);

        // Settings Link in Plugins List
        $plugin_basename = plugin_basename(dirname(__DIR__) . '/auto-sri.php');
        add_filter("plugin_action_links_" . $plugin_basename, [__CLASS__, 'add_plugin_action_links']);

        // One-time notice for sites that saved custom exclusions before 2.4
        add_action('admin_notices', [__CLASS__, 'legacy_exclusions_notice']);
        add_action('admin_post_auto_sri_dismiss_legacy_notice', [__CLASS__, 'dismiss_legacy_notice']);

        // Hide the Auto SRI Pro card on the settings page
        add_action('admin_post_auto_sri_dismiss_pro_link', [__CLASS__, 'dismiss_pro_link']);
    }

    /**
     * Start output buffering
     */
    public static function start_buffer() {
        // Skip if in admin panel
        if (is_admin()) {
            return;
        }
        
        ob_start([__CLASS__, 'rewrite_output']);
    }

    /**
     * Handles rewriting of final HTML to add SRI to ALL external scripts & styles
     */
    // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
    public static function rewrite_output($html) {

        // ============================
        // UNIVERSAL SCRIPT MATCHER
        // ============================
        $html = preg_replace_callback(
            '#<script\b([^>]*)\bsrc=(["\'])(https?://[^"\']+)\2([^>]*)>(?:</script>)?#is',
            function ($matches) {

                $before = $matches[1];
                $url    = $matches[3];
                $after  = $matches[4];
                $full   = $matches[0];

                // Skip if SRI already exists
                if (stripos($full, 'integrity=') !== false) {
                    return $full;
                }

                // Check exclusions
                if (self::is_excluded($url)) {
                    return $full;
                }

                // ============================

                $sri = AutoSRI::get_sri_hash($url);
                if (!$sri) return $full;

                return "<script{$before} src=\"{$url}\" integrity=\"{$sri}\" crossorigin=\"anonymous\"{$after}></script>";
            },
            $html
        );

        // ============================
        // UNIVERSAL LINK MATCHER
        // ============================

        $html = preg_replace_callback(
            '#<link\b([^>]*)\bhref=(["\'])(https?://[^"\']+)\2([^>]*)>#is',
            function ($matches) {

                $before = $matches[1];
                $url    = $matches[3];
                $after  = $matches[4];
                $full   = $matches[0];

                // Apply only to rel=stylesheet
                if (stripos($full, 'rel=') !== false && stripos($full, 'stylesheet') === false) {
                    return $full;
                }

                // Skip if already has SRI
                if (stripos($full, 'integrity=') !== false) {
                    return $full;
                }

                // Check exclusions
                if (self::is_excluded($url)) {
                    return $full;
                }

                $sri = AutoSRI::get_sri_hash($url);
                if (!$sri) return $full;

                return "<link{$before} href=\"{$url}\" integrity=\"{$sri}\" crossorigin=\"anonymous\"{$after}>";
            },
            $html
        );

        return $html;
    }
    /**
     * Standard WP enqueue filter-based injection
    */
    // phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript
    public static function inject_sri($tag, $handle, $src, $media = null) {

        // Skip if in admin panel
        if (is_admin()) {
            return $tag;
        }

        // Skip internal files
        if (!$src || strpos($src, home_url()) === 0) {
            return $tag;
        }

        // Skip if already has SRI
        if (strpos($tag, 'integrity=') !== false) {
            return $tag;
        }

        // Check exclusions
        if (self::is_excluded($src)) {
            return $tag;
        }

        $sri = self::get_sri_hash($src);
        if (!$sri) return $tag;

        // Scripts
        if (str_starts_with(trim($tag), '<script')) {
            return str_replace(
                '<script',
                '<script integrity="' . esc_attr($sri) . '" crossorigin="anonymous"',
                $tag
            );
        }

        // Stylesheets
        if (str_starts_with(trim($tag), '<link')) {
            return str_replace(
                '<link',
                '<link integrity="' . esc_attr($sri) . '" crossorigin="anonymous"',
                $tag
            );
        }

        return $tag;
    }

    /**
     * Check if the URL is excluded
     */
    public static function is_excluded($url) {
        $excluded = self::is_excluded_by_default($url);

        /**
         * Filters whether a URL is excluded from SRI.
         *
         * @param bool   $excluded Whether the URL is excluded.
         * @param string $url      The asset URL.
         */
        return (bool) apply_filters('auto_sri_is_excluded', $excluded, $url);
    }

    /**
     * Built-in and user-defined exclusions.
     */
    private static function is_excluded_by_default($url) {
        // ============================
        // GOOGLE EXCLUSIONS (Hardcoded)
        // ============================

        // 1. Google reCAPTCHA
        if (preg_match('#google\.com/recaptcha#i', $url)) {
            return true;
        }

        // 2. Google Fonts CSS
        if (strpos($url, 'fonts.googleapis.com') !== false) {
            return true;
        }

        // 3. Google reCAPTCHA subresources / specific font files
        if (strpos($url, 'gstatic.com/recaptcha') !== false || strpos($url, 'fonts.gstatic.com') !== false) {
            return true;
        }

        // 4. WordPress.com widgets
        if (strpos($url, 'widgets.wp.com') !== false) {
            return true;
        }

        // 5. Dynamic concatenated resources
        if (strpos($url, '/_static/??') !== false) {
            return true;
        }

        // ============================
        // EXTRA EXCLUSIONS (via filter)
        // ============================
        foreach (self::get_excluded_urls() as $pattern) {
            // Simple substring match
            if (stripos($url, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extra exclusion patterns (domains, filenames or URLs, matched as substrings).
     *
     * Auto SRI has no settings for these since 2.4. Auto SRI Pro provides a
     * "Custom URLs" setting through this filter, and developers can add
     * their own patterns in code.
     *
     * @return string[]
     */
    public static function get_excluded_urls() {
        /**
         * Filters extra URL patterns to exclude from SRI.
         *
         * @param string[] $patterns Default empty.
         */
        $patterns = apply_filters('auto_sri_excluded_urls', []);
        if (!is_array($patterns)) {
            return [];
        }

        $clean = [];
        foreach ($patterns as $pattern) {
            if (is_scalar($pattern) && '' !== trim((string) $pattern)) {
                $clean[] = trim((string) $pattern);
            }
        }
        return $clean;
    }

    /**
     * Add Admin Menu
     */
    public static function add_admin_menu() {
        add_options_page(
            'Auto SRI Settings',
            'Auto SRI',
            'manage_options',
            'auto-sri',
            [__CLASS__, 'settings_page_html']
        );
    }

    /**
     * Add Settings Link to Plugins Page
     */
    public static function add_plugin_action_links($links) {
        $settings_link = '<a href="options-general.php?page=auto-sri">Settings</a>';
        array_unshift($links, $settings_link);

        if (self::show_pro_ads()) {
            $links[] = '<a href="' . esc_url(self::get_pro_url()) . '" target="_blank" rel="noopener noreferrer" style="font-weight:600;">' . esc_html__('Upgrade to Pro', 'auto-sri') . '</a>';
        }

        return $links;
    }

    /**
     * Settings Page HTML
     */
    public static function settings_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p><?php esc_html_e('Auto SRI adds Subresource Integrity hashes to external scripts and stylesheets automatically. There is nothing to configure.', 'auto-sri'); ?></p>
            <p><?php esc_html_e('Built-in exclusions: Google reCAPTCHA, Google Fonts, WordPress.com widgets and concatenated /_static/ resources.', 'auto-sri'); ?></p>
            <?php
            /**
             * Fires on the Auto SRI settings page, so add-ons can add their own content.
             * (The name is kept from when this page had a settings form.)
             */
            do_action('auto_sri_settings_page_after_form');

            self::render_pro_card();
            ?>
            <div style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); max-width: 600px;">
                <h3 style="margin-top: 0;"><?php esc_html_e('Like Auto SRI? Help keep it that way!', 'auto-sri'); ?></h3>
                <p><?php esc_html_e('If Auto SRI has been useful to you, consider supporting its development with a $5 contribution. It helps keep the plugin maintained and improved.', 'auto-sri'); ?></p>
                <p style="margin-bottom: 0;">
                    <a href="https://www.paypal.com/ncp/payment/K3VU2WF5GXRSS" target="_blank" rel="noopener noreferrer" style="display: inline-block; background: #0070ba; color: #fff; font-weight: 600; font-size: 15px; padding: 10px 22px; border-radius: 4px; text-decoration: none;">
                        <?php esc_html_e('Help keep Auto SRI free — $5', 'auto-sri'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Custom exclusions saved in the old settings textarea (before 2.4).
     * Read only to tell the site owner about them; Auto SRI no longer applies them.
     *
     * @return string[]
     */
    public static function get_legacy_exclusions() {
        $saved = get_option('auto_sri_exclusions', '');
        if (!is_string($saved)) {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode("\n", $saved)), 'strlen'));
    }

    /**
     * Dismissible notice, on the Plugins screen and this plugin's settings page only,
     * for sites whose saved exclusions are no longer applied. Not shown while
     * Auto SRI Pro is licensed (it applies them).
     */
    public static function legacy_exclusions_notice() {
        if (!current_user_can('manage_options') || !self::show_pro_ads()) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || !in_array($screen->id, ['plugins', 'settings_page_auto-sri'], true)) {
            return;
        }
        $legacy = self::get_legacy_exclusions();
        if (!$legacy || get_user_meta(get_current_user_id(), 'auto_sri_legacy_notice_dismissed', true)) {
            return;
        }

        $dismiss_url = wp_nonce_url(admin_url('admin-post.php?action=auto_sri_dismiss_legacy_notice'), 'auto_sri_dismiss_legacy_notice');
        ?>
        <div class="notice notice-warning">
            <p><strong><?php esc_html_e('Auto SRI: your custom excluded URLs are no longer applied.', 'auto-sri'); ?></strong></p>
            <p><?php esc_html_e('Since version 2.4, custom URL exclusions are part of Auto SRI Pro. Your saved list is kept, and Auto SRI Pro picks it up automatically:', 'auto-sri'); ?></p>
            <ul style="list-style: disc; padding-left: 20px;">
                <?php foreach (array_slice($legacy, 0, 20) as $line) : ?>
                    <li><code><?php echo esc_html($line); ?></code></li>
                <?php endforeach; ?>
                <?php if (count($legacy) > 20) : ?>
                    <li><?php echo esc_html(sprintf(__('and %d more', 'auto-sri'), count($legacy) - 20)); ?></li>
                <?php endif; ?>
            </ul>
            <p><?php esc_html_e('Without Pro, these files get integrity hashes again. If one of them changes on every request, browsers may block it. Developers can keep excluding them with the auto_sri_excluded_urls filter.', 'auto-sri'); ?></p>
            <p>
                <a class="button button-primary" href="<?php echo esc_url(self::get_pro_url()); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Get Auto SRI Pro', 'auto-sri'); ?></a>
                <a class="button" href="<?php echo esc_url($dismiss_url); ?>"><?php esc_html_e('Dismiss', 'auto-sri'); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Remember that the current user dismissed the legacy exclusions notice.
     */
    public static function dismiss_legacy_notice() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'auto-sri'), 403);
        }
        check_admin_referer('auto_sri_dismiss_legacy_notice');

        update_user_meta(get_current_user_id(), 'auto_sri_legacy_notice_dismissed', 1);

        wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url('plugins.php'));
        exit;
    }

    /**
     * Whether to show Auto SRI Pro ads. Auto SRI Pro returns false while it is licensed.
     */
    public static function show_pro_ads() {
        return (bool) apply_filters('auto_sri_show_pro_link', true);
    }

    /**
     * Where "Get Pro" links point.
     */
    public static function get_pro_url() {
        return (string) apply_filters('auto_sri_pro_url', 'https://zfir.lemonsqueezy.com/');
    }

    /**
     * Card describing the optional Auto SRI Pro add-on. Shown only on this
     * settings page, hidden once Pro is licensed, and dismissible per user.
     * Nothing in Auto SRI is locked behind it.
     */
    public static function render_pro_card() {
        if (!self::show_pro_ads()) {
            return;
        }
        if (get_user_meta(get_current_user_id(), 'auto_sri_pro_link_dismissed', true)) {
            return;
        }

        $dismiss_url = wp_nonce_url(admin_url('admin-post.php?action=auto_sri_dismiss_pro_link'), 'auto_sri_dismiss_pro_link');
        $features    = [
            [__('Custom URLs', 'auto-sri'), __('exclude your own domains, files or URLs from SRI.', 'auto-sri')],
            [__('Scheduled re-checks and email alerts', 'auto-sri'), __('know when a CDN file changes, and keep the old hash until you approve the new file.', 'auto-sri')],
            [__('Asset manager', 'auto-sri'), __('every external file with its hash and status, plus re-check, accept and exclude actions.', 'auto-sri')],
            [__('Hash algorithm choice', 'auto-sri'), __('SHA-256, SHA-384 or SHA-512.', 'auto-sri')],
            [__('WP-CLI commands', 'auto-sri'), __('manage the license and assets from the command line.', 'auto-sri')],
        ];
        ?>
        <div class="auto-sri-pro-card" style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04); max-width: 600px;">
            <h2 style="margin-top: 0;"><?php esc_html_e('Auto SRI Pro', 'auto-sri'); ?></h2>
            <p><?php esc_html_e('An optional paid add-on for sites that want more control. Auto SRI keeps working without it.', 'auto-sri'); ?></p>
            <ul style="list-style: disc; padding-left: 20px;">
                <?php foreach ($features as $feature) : ?>
                    <li><strong><?php echo esc_html($feature[0]); ?></strong> &ndash; <?php echo esc_html($feature[1]); ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="description" style="margin-bottom: 4px;"><?php esc_html_e('Preview of the Custom URLs setting in Auto SRI Pro (not available in the free plugin):', 'auto-sri'); ?></p>
            <div aria-hidden="true" style="font-family: Consolas, Monaco, monospace; font-size: 12px; line-height: 1.6; background: #f6f7f7; border: 1px dashed #c3c4c7; border-radius: 4px; padding: 8px 12px; color: #50575e; user-select: none;">
                ads.example.com<br>my-dynamic-script.js<br>https://cdn.example.com/widget.js
            </div>
            <p style="margin-bottom: 0; margin-top: 16px;">
                <a class="button button-primary" href="<?php echo esc_url(self::get_pro_url()); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Get Auto SRI Pro', 'auto-sri'); ?></a>
                <a href="<?php echo esc_url($dismiss_url); ?>" style="margin-left: 12px;"><?php esc_html_e('Hide this', 'auto-sri'); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Remember that the current user hid the Pro card.
     */
    public static function dismiss_pro_link() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'auto-sri'), 403);
        }
        check_admin_referer('auto_sri_dismiss_pro_link');

        update_user_meta(get_current_user_id(), 'auto_sri_pro_link_dismissed', 1);

        wp_safe_redirect(admin_url('options-general.php?page=auto-sri'));
        exit;
    }

    /**
     * Supported SRI hash algorithms.
     */
    public static function get_supported_algorithms() {
        return ['sha256', 'sha384', 'sha512'];
    }

    /**
     * Hash algorithm used for new SRI hashes (SHA-384 by default).
     */
    public static function get_hash_algorithm() {
        /**
         * Filters the SRI hash algorithm. Must be sha256, sha384 or sha512.
         *
         * @param string $algorithm Default 'sha384'.
         */
        $algorithm = apply_filters('auto_sri_hash_algorithm', 'sha384');

        return in_array($algorithm, self::get_supported_algorithms(), true) ? $algorithm : 'sha384';
    }

    /**
     * Compute or get cached SRI hash
     */
    public static function get_sri_hash($url) {

        $algorithm = self::get_hash_algorithm();

        /**
         * Short-circuits hash lookup. Return a full SRI string (e.g. "sha384-...")
         * to use it, false to skip SRI for this URL, or null to continue.
         *
         * @param string|false|null $pre       Default null.
         * @param string            $url       The asset URL.
         * @param string            $algorithm The hash algorithm in use.
         */
        $pre = apply_filters('auto_sri_pre_get_hash', null, $url, $algorithm);
        if (null !== $pre) {
            return $pre ? (string) $pre : false;
        }

        $cache_key = 'autosri_' . md5($url);
        $cached = get_option($cache_key);

        // Reuse the cache only if it was computed with the current algorithm.
        if ($cached && strpos($cached, $algorithm . '-') === 0) return $cached;

        $response = wp_remote_get($url, ['timeout' => 10]);

        if (is_wp_error($response)) return false;

        $body = wp_remote_retrieve_body($response);
        if (!$body) return false;

        // SHA-384 recommended by browsers
        $hash = base64_encode(hash($algorithm, $body, true));
        $sri  = "$algorithm-$hash";

        update_option($cache_key, $sri);

        /**
         * Fires after a new SRI hash has been computed and cached.
         *
         * @param string $url       The asset URL.
         * @param string $sri       The SRI value, e.g. "sha384-...".
         * @param string $algorithm The hash algorithm used.
         */
        do_action('auto_sri_hash_computed', $url, $sri, $algorithm);

        return $sri;
    }
}
