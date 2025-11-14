<?php
/**
 * Plugin Name: WP Auto SRI
 * Description: Automatically adds Subresource Integrity (SRI) to ALL external scripts and styles — including dynamically injected tags and raw HTML.
 * Version: 1.2
 * Author: Zafir Sk Heerah
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-wp-auto-sri.php';

add_action('plugins_loaded', ['WP_Auto_SRI', 'init']);
