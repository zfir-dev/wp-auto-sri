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
        add_action('admin_init', [__CLASS__, 'settings_init']);

        // Settings Link in Plugins List
        $plugin_basename = plugin_basename(dirname(__DIR__) . '/auto-sri.php');
        add_filter("plugin_action_links_" . $plugin_basename, [__CLASS__, 'add_plugin_action_links']);

        // Dismiss the optional Pro link on the settings page
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
        // USER DEFINED EXCLUSIONS
        // ============================
        $user_exclusions = get_option('auto_sri_exclusions', '');
        if (!empty($user_exclusions)) {
            $lines = explode("\n", $user_exclusions);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Simple substring match
                if (stripos($url, $line) !== false) {
                    return true;
                }
            }
        }

        return false;
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
     * Register Settings
     */
    public static function settings_init() {
        register_setting('auto_sri', 'auto_sri_exclusions');

        add_settings_section(
            'auto_sri_section',
            __('Exclusions', 'auto-sri'),
            null,
            'auto_sri'
        );

        add_settings_field(
            'auto_sri_exclusions',
            __('Excluded URLs (one per line)', 'auto-sri'),
            [__CLASS__, 'exclusions_callback'],
            'auto_sri',
            'auto_sri_section'
        );
    }

    /**
     * Callback for the exclusions field
     */
    public static function exclusions_callback() {
        $value = get_option('auto_sri_exclusions', '');
        ?>
        <textarea name="auto_sri_exclusions" rows="10" cols="50" class="large-text code"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            Enter domains, filenames, or full URLs to exclude.<br>
            <strong>Important:</strong> Enter each exclusion on a new line.<br>
            For example:<br>
            <code>ads.google.com</code><br>
            <code>my-dynamic-script.js</code>
        </p>
        <?php
    }

    /**
     * Add Settings Link to Plugins Page
     */
    public static function add_plugin_action_links($links) {
        $settings_link = '<a href="options-general.php?page=auto-sri">Settings</a>';
        array_unshift($links, $settings_link);
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
            <form action="options.php" method="post">
                <?php
                settings_fields('auto_sri');
                do_settings_sections('auto_sri');
                submit_button('Save Settings');
                ?>
            </form>
            <?php
            /**
             * Fires after the Auto SRI settings form, so add-ons can add their own content.
             */
            do_action('auto_sri_settings_page_after_form');

            self::render_pro_link();
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
     * Small, dismissible link to the optional Pro add-on. Shown only on this
     * settings page; every free feature works without it.
     */
    public static function render_pro_link() {
        if (!apply_filters('auto_sri_show_pro_link', true)) {
            return;
        }
        if (get_user_meta(get_current_user_id(), 'auto_sri_pro_link_dismissed', true)) {
            return;
        }

        $pro_url     = apply_filters('auto_sri_pro_url', 'https://zfir.lemonsqueezy.com/');
        $dismiss_url = wp_nonce_url(admin_url('admin-post.php?action=auto_sri_dismiss_pro_link'), 'auto_sri_dismiss_pro_link');
        ?>
        <p class="description" style="margin-top: 20px;">
            <?php esc_html_e('Want email alerts when a CDN file changes, scheduled re-checks and SHA-512 hashes?', 'auto-sri'); ?>
            <a href="<?php echo esc_url($pro_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Auto SRI Pro', 'auto-sri'); ?></a>
            &middot;
            <a href="<?php echo esc_url($dismiss_url); ?>"><?php esc_html_e('Dismiss', 'auto-sri'); ?></a>
        </p>
        <?php
    }

    /**
     * Remember that the current user dismissed the Pro link.
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
