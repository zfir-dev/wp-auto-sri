<?php

if (!defined('ABSPATH')) exit;

class WP_Auto_SRI {

    public static function init() {
        // Standard WP enqueued assets
        add_filter('script_loader_tag', [__CLASS__, 'inject_sri'], 10, 3);
        add_filter('style_loader_tag',  [__CLASS__, 'inject_sri'], 10, 4);

        // Output buffer to catch ALL scripts (raw + injected)
        add_action('template_redirect', [__CLASS__, 'start_buffer']);
    }

    /**
     * Start output buffering
     */
    public static function start_buffer() {
        ob_start([__CLASS__, 'rewrite_output']);
    }

    /**
     * Handles rewriting of final HTML to add SRI to ALL external scripts & styles
     */
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

                // ============================
                // GOOGLE EXCLUSIONS
                // ============================

                // 1. Google reCAPTCHA (dynamic)
                if (preg_match('#google\.com/recaptcha#i', $url)) {
                    return $full;
                }

                // 2. Google Fonts CSS (dynamic)
                if (strpos($url, 'fonts.googleapis.com') !== false) {
                    return $full;
                }

                // 3. Google reCAPTCHA subresources
                if (strpos($url, 'gstatic.com/recaptcha') !== false) {
                    return $full;
                }

                // ============================

                $sri = WP_Auto_SRI::get_sri_hash($url);
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

                // ============================
                // GOOGLE EXCLUSIONS
                // ============================

                // 1. Google Fonts CSS — dynamic content, not SRI compatible
                if (strpos($url, 'fonts.googleapis.com') !== false) {
                    return $full;
                }

                // 2. Google Fonts font files (safe to SRI, but they are loaded by CSS)
                if (strpos($url, 'fonts.gstatic.com') !== false) {
                    return $full;
                }

                // ============================

                $sri = WP_Auto_SRI::get_sri_hash($url);
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
    public static function inject_sri($tag, $handle, $src, $media = null) {

        // Skip internal files
        if (!$src || strpos($src, home_url()) === 0) {
            return $tag;
        }

        // Skip if already has SRI
        if (strpos($tag, 'integrity=') !== false) {
            return $tag;
        }

        // ============================
        // GOOGLE EXCLUSIONS
        // ============================

        // reCAPTCHA
        if (preg_match('#google\.com/recaptcha#i', $src)) {
            return $tag;
        }

        // Google Fonts stylesheet
        if (strpos($src, 'fonts.googleapis.com') !== false) {
            return $tag;
        }

        // Google Fonts font files
        if (strpos($src, 'fonts.gstatic.com') !== false) {
            return $tag;
        }

        // ============================

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
     * Compute or get cached SRI hash
     */
    public static function get_sri_hash($url) {

        $cache_key = 'wp_auto_sri_' . md5($url);
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
