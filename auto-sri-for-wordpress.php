<?php
/**
 * Plugin Name: Auto SRI for WordPress
 * Description: Automatically adds Subresource Integrity (SRI) to external scripts and styles, while safely excluding dynamic content such as Google reCAPTCHA and Google Fonts.
 * Version: 1.5
 * Author: Zafir Sk Heerah
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-auto-sri-for-wordpress.php';

add_action('plugins_loaded', ['WP_Auto_SRI', 'init']);
