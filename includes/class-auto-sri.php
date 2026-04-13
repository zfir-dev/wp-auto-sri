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
            <div style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); max-width: 600px;">
                <h3 style="margin-top: 0;"><?php esc_html_e('Like Auto SRI? Help keep it that way!', 'auto-sri'); ?></h3>
                <p><?php esc_html_e('If Auto SRI has been useful to you, consider supporting its development with a one-time $5 contribution. It helps keep the plugin maintained and improved.', 'auto-sri'); ?></p>
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
     * Compute or get cached SRI hash
     */
    public static function get_sri_hash($url) {

        $cache_key = 'autosri_' . md5($url);
        $cached = get_option($cache_key);

        if ($cached) return $cached;

        $response = wp_remote_get($url, ['timeout' => 10]);

        if (is_wp_error($response)) return false;

        $body = wp_remote_retrieve_body($response);
        if (!$body) return false;

        // SHA-384 recommended by browsers
        $hash = base64_encode(hash('sha384', $body, true));
        $sri  = "sha384-$hash";

        update_option($cache_key, $sri);

        return $sri;
    }
}
