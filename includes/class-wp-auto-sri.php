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

        // Process <script> tags
        $html = preg_replace_callback(
            '#<script([^>]*)src=["\'](https?://[^"\']+)["\']([^>]*)></script>#i',
            function($matches) {
                $fullTag = $matches[0];
                $attrsBefore = $matches[1];
                $url = $matches[2];
                $attrsAfter = $matches[3];

                // Skip if integrity already present
                if (strpos($fullTag, 'integrity=') !== false) {
                    return $fullTag;
                }

                $sri = WP_Auto_SRI::get_sri_hash($url);
                if (!$sri) return $fullTag;

                // Rebuild the script tag
                return "<script{$attrsBefore} src=\"{$url}\" integrity=\"{$sri}\" crossorigin=\"anonymous\"{$attrsAfter}></script>";
            },
            $html
        );

        // Process <link rel="stylesheet"> tags
        $html = preg_replace_callback(
            '#<link([^>]*)href=["\'](https?://[^"\']+)["\']([^>]*)>#i',
            function($matches) {
                $fullTag = $matches[0];
                $attrsBefore = $matches[1];
                $url = $matches[2];
                $attrsAfter = $matches[3];

                // Skip non-stylesheet links
                if (stripos($fullTag, 'rel=') !== false && stripos($fullTag, 'stylesheet') === false) {
                    return $fullTag;
                }

                // Skip if integrity already present
                if (strpos($fullTag, 'integrity=') !== false) {
                    return $fullTag;
                }

                $sri = WP_Auto_SRI::get_sri_hash($url);
                if (!$sri) return $fullTag;

                return "<link{$attrsBefore} href=\"{$url}\" integrity=\"{$sri}\" crossorigin=\"anonymous\"{$attrsAfter}>";
            },
            $html
        );

        return $html;
    }

    /**
     * Standard WP enqueue filter-based injection (still needed)
     */
    public static function inject_sri($tag, $handle, $src, $media = null) {

        // Skip internal files
        if (!$src || strpos($src, home_url()) === 0) {
            return $tag;
        }

        // Skip if SRI already present
        if (strpos($tag, 'integrity=') !== false) {
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

        // Styles
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

        $hash = base64_encode(hash('sha384', $body, true));
        $sri  = "sha384-$hash";

        update_option($cache_key, $sri);

        return $sri;
    }
}
