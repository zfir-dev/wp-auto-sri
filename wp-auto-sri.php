<?php
/**
 * Plugin Name: WP Auto SRI
 * Description: Automatically generates Subresource Integrity (SRI) hashes for external scripts and styles.
 * Version: 1.0
 * Author: Zafir Sk Heerah
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-wp-auto-sri.php';

add_action('plugins_loaded', ['WP_Auto_SRI', 'init']);
