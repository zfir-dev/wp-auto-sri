<?php

echo "BOOTSTRAP FIRED\n";

// -------------------------------------------------------------
// 1. Prevent plugin from exiting
// -------------------------------------------------------------
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

define('PHPUNIT_RUNNING', true);

// -------------------------------------------------------------
// 2. Fake WordPress function stubs
// -------------------------------------------------------------

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) {
        // No-op for PHPUnit
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $args = 1) {
        // No-op for PHPUnit
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value) {
        return $value;
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'https://example.com/wp-content/plugins/auto-sri-for-wordpress/';
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($str) {
        return htmlspecialchars($str, ENT_QUOTES);
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '') {
        return 'https://example.com';
    }
}

$GLOBALS['__mock_options'] = [];

if (!function_exists('get_option')) {
    function get_option($key) {
        return $GLOBALS['__mock_options'][$key] ?? false;
    }
}

if (!function_exists('update_option')) {
    function update_option($key, $value) {
        $GLOBALS['__mock_options'][$key] = $value;
    }
}

if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url, $args = []) {
        return [
            'body' => "console.log('auto-sri-for-wordpress');"
        ];
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return $response['body'] ?? '';
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($value) {
        return false;
    }
}

// -------------------------------------------------------------
// 3. Load Composer autoloader
// -------------------------------------------------------------
require dirname(__DIR__) . '/vendor/autoload.php';

// -------------------------------------------------------------
// 4. Load the plugin file (now that WP stubs exist)
// -------------------------------------------------------------
require_once dirname(__DIR__) . '/auto-sri-for-wordpress.php';
