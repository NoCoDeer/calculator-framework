<?php
/*
Plugin Name: Calculator Framework
Plugin URI: 
Description: A modular framework for creating calculators in WordPress. Includes Chart.js (MIT License) for chart rendering.
Version: 1.0.1
Author: szhigimont
Author URI: https://t.me/szhigimont
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: calculator-framework
Domain Path: /languages
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CF_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load plugin text domain for translations
function cf_load_textdomain() {
    $loaded = load_plugin_textdomain('calculator-framework', false, dirname(plugin_basename(__FILE__)) . '/languages');
    if (!$loaded) {
        error_log('Failed to load text domain: calculator-framework');
    } else {
        error_log('Text domain loaded: calculator-framework');
    }
}
add_action('plugins_loaded', 'cf_load_textdomain');

// Include core classes
require_once CF_PLUGIN_DIR . 'includes/class-calculator-framework.php';
require_once CF_PLUGIN_DIR . 'includes/class-calculator-module.php';
require_once CF_PLUGIN_DIR . 'includes/class-admin.php';

// Initialize the framework
function cf_init() {
    $framework = new Calculator_Framework();
    $admin = new CF_Admin();
}
add_action('plugins_loaded', 'cf_init');

// Add defer attribute to specific scripts
function cf_add_defer_attribute($tag, $handle) {
    $defer_scripts = [
        'chart-js',
        'html2canvas',
        'jspdf',
        'cf-framework-js',
    ];
    if (in_array($handle, $defer_scripts, true)) {
        return str_replace(' src', ' defer="defer" src', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'cf_add_defer_attribute', 10, 2);