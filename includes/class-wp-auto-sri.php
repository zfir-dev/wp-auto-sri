<?php

if (!defined('ABSPATH')) exit;

class WP_Auto_SRI {

    public static function init() {
        add_filter('script_loader_tag', [__CLASS__, 'inject_sri'], 10, 3);
        add_filter('style_loader_tag',  [__CLASS__, 'inject_sri'], 10, 4);
    }

    public static function inject_sri($tag, $handle, $src, $media = null) {

        // Ignore same-origin (internal) files
        if (!$src || strpos($src, home_url()) === 0) {
            return $tag;
        }

        // Skip if SRI already exists
        if (strpos($tag, 'integrity=') !== false) {
            return $tag;
        }

        $sri = self::get_sri_hash($src);
        if (!$sri) return $tag;

        // Script
        if (str_starts_with(trim($tag), '<script')) {
            return str_replace(
                '<script',
                '<script integrity="' . esc_attr($sri) . '" crossorigin="anonymous"',
                $tag
            );
        }

        // Stylesheet
        if (str_starts_with(trim($tag), '<link')) {
            return str_replace(
                '<link',
                '<link integrity="' . esc_attr($sri) . '" crossorigin="anonymous"',
                $tag
            );
        }

        return $tag;
    }

    private static function get_sri_hash($url) {

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
