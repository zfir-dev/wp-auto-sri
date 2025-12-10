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
        return 'https://example.com/wp-content/plugins/auto-sri/';
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
            'body' => "console.log('auto-sri');"
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

// NEW STUBS FOR SETTINGS PAGE
if (!function_exists('is_admin')) {
    function is_admin() {
        return false; // Default to frontend for tests unless mocked otherwise
    }
}

if (!function_exists('add_options_page')) {
    function add_options_page($page_title, $menu_title, $capability, $menu_slug, $function = '') {}
}

if (!function_exists('register_setting')) {
    function register_setting($option_group, $option_name, $args = array()) {}
}

if (!function_exists('add_settings_section')) {
    function add_settings_section($id, $title, $callback, $page) {}
}

if (!function_exists('add_settings_field')) {
    function add_settings_field($id, $title, $callback, $page, $section = 'default', $args = array()) {}
}

if (!function_exists('settings_fields')) {
    function settings_fields($option_group) {}
}

if (!function_exists('do_settings_sections')) {
    function do_settings_sections($page) {}
}

if (!function_exists('submit_button')) {
    function submit_button($text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null) {}
}

if (!function_exists('get_admin_page_title')) {
    function get_admin_page_title() { return 'Auto SRI Settings'; }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) { return true; }
}

if (!function_exists('esc_html')) {
    function esc_html($text) { return htmlspecialchars($text); }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea($text) { return htmlspecialchars($text); }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') { echo $text; }
}

// -------------------------------------------------------------
// 3. Load Composer autoloader
// -------------------------------------------------------------
require dirname(__DIR__) . '/vendor/autoload.php';

// -------------------------------------------------------------
// 4. Load the plugin file (now that WP stubs exist)
// -------------------------------------------------------------
require_once dirname(__DIR__) . '/auto-sri.php';
