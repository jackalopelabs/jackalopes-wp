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
    
    // Create .htaccess file for assets directory to fix MIME types
    jackalopes_wp_create_assets_htaccess();
    
    // Add action to inject canvas fix styles
    add_action('wp_head', 'jackalopes_wp_inject_canvas_fix_styles', 999);
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

/**
 * Creates an .htaccess file in the game/dist/assets directory to force correct MIME types
 */
function jackalopes_wp_create_assets_htaccess() {
    $htaccess_path = JACKALOPES_WP_PLUGIN_DIR . 'game/dist/assets/.htaccess';
    
    // Only create if it doesn't exist
    if (!file_exists($htaccess_path)) {
        $htaccess_content = <<<EOT
# Force correct MIME types
<IfModule mod_mime.c>
    AddType text/css .css
    AddType application/javascript .js
    AddType model/gltf+json .gltf
    AddType model/gltf-binary .glb
</IfModule>

# Force header content type
<IfModule mod_headers.c>
    <FilesMatch "\.css$">
        Header set Content-Type "text/css"
    </FilesMatch>
    <FilesMatch "\.js$">
        Header set Content-Type "application/javascript"
    </FilesMatch>
</IfModule>

# Force type for CSS files
<FilesMatch "\.css$">
    ForceType text/css
</FilesMatch>
EOT;
        
        file_put_contents($htaccess_path, $htaccess_content);
    }
}

/**
 * Inject styles to fix canvas display issues (black right half)
 */
function jackalopes_wp_inject_canvas_fix_styles() {
    ?>
    <style>
        /* Fix for ThreeJS canvas display issues */
        .jackalopes-game-container {
            width: 100% !important;
            height: 600px !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .jackalopes-game-container canvas {
            width: 100% !important;
            height: 100% !important;
            display: block !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
        }
        
        /* Ensure the game container has full width */
        #jackalopes-game {
            width: 100% !important;
            height: 100% !important;
            display: block !important; 
            position: relative !important;
        }
        
        /* Fix for React Three Fiber canvas */
        div[class*="canvas3d"] {
            width: 100% !important;
            height: 100% !important;
        }
    </style>
    <?php
}

// Initialize the plugin
add_action('plugins_loaded', 'jackalopes_wp_init'); 