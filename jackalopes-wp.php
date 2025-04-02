<?php
/**
 * Plugin Name: Jackalopes
 * Plugin URI: https://jackalope.io
 * Description: 3D first-person shooter game built with React Three Fiber and ThreeJS
 * Version: 1.0.0
 * Author: Mason Lawlor
 * Author URI: https://jackalope.io
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jackalopes-wp
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('JACKALOPES_WP_VERSION', '1.0.0');
define('JACKALOPES_WP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('JACKALOPES_WP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JACKALOPES_WP_PLUGIN_FILE', __FILE__);
define('JACKALOPES_WP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoload dependencies
if (file_exists(JACKALOPES_WP_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once JACKALOPES_WP_PLUGIN_DIR . 'vendor/autoload.php';
}

// Include required files
require_once JACKALOPES_WP_PLUGIN_DIR . 'includes/shortcodes.php';
require_once JACKALOPES_WP_PLUGIN_DIR . 'includes/assets.php';

/**
 * The code that runs during plugin activation.
 */
function activate_jackalopes_wp() {
    // Clear rewrite rules
    flush_rewrite_rules();
    
    // Create necessary database tables if needed
    // jackalopes_wp_create_tables();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_jackalopes_wp() {
    // Clear rewrite rules
    flush_rewrite_rules();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'activate_jackalopes_wp');
register_deactivation_hook(__FILE__, 'deactivate_jackalopes_wp');

/**
 * Initialize the plugin.
 */
function jackalopes_wp_init() {
    // Register shortcodes
    jackalopes_wp_register_shortcodes();
    
    // Register assets
    add_action('wp_enqueue_scripts', 'jackalopes_wp_register_assets');
    
    // Register admin pages
    if (is_admin()) {
        require_once JACKALOPES_WP_PLUGIN_DIR . 'admin/admin.php';
    }
    
    // Fix CSS MIME type issues
    add_action('init', 'jackalopes_wp_fix_css_mime_type');
}

/**
 * Fix CSS MIME type issues to prevent WordPress from serving CSS as text/html
 */
function jackalopes_wp_fix_css_mime_type() {
    // Add CSS MIME type
    add_filter('wp_check_filetype_and_ext', function($types, $file, $filename, $mimes) {
        if (strpos($filename, '.css') !== false) {
            $types['type'] = 'text/css';
            $types['ext'] = 'css';
        }
        return $types;
    }, 10, 4);
    
    // Also add the MIME type directly to WordPress
    add_filter('mime_types', function($mimes) {
        $mimes['css'] = 'text/css';
        return $mimes;
    });
}

// Initialize the plugin
add_action('plugins_loaded', 'jackalopes_wp_init'); 