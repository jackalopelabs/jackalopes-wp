<?php
/**
 * Asset loading functionality.
 *
 * @package Jackalopes_WP
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Register all assets.
 */
function jackalopes_wp_register_assets() {
    // Register main game styles with direct PHP handler
    wp_register_style(
        'jackalopes-game-styles',
        JACKALOPES_WP_PLUGIN_URL . 'game/dist/css-handler.php',
        [],
        JACKALOPES_WP_VERSION . '.' . time() // Add timestamp to prevent caching
    );
    
    // Add correct content type header for CSS
    add_filter('style_loader_tag', function($tag, $handle) {
        if ('jackalopes-game-styles' === $handle) {
            return str_replace('<link ', '<link type="text/css" ', $tag);
        }
        return $tag;
    }, 10, 2);
    
    // Register React and ReactDOM - use exact version that matches WordPress (18.3.1)
    wp_register_script(
        'react',
        'https://unpkg.com/react@18.3.1/umd/react.production.min.js',
        [],
        '18.3.1',
        false // Load in header
    );
    
    wp_register_script(
        'react-dom',
        'https://unpkg.com/react-dom@18.3.1/umd/react-dom.production.min.js',
        ['react'],
        '18.3.1',
        false // Load in header
    );
    
    // Add crossorigin attribute to React scripts
    add_filter('script_loader_tag', function($tag, $handle) {
        if (in_array($handle, ['react', 'react-dom'])) {
            return str_replace('<script ', '<script crossorigin="anonymous" ', $tag);
        }
        return $tag;
    }, 10, 2);
    
    // Register WordPress helper script
    wp_register_script(
        'jackalopes-wp-helper',
        JACKALOPES_WP_PLUGIN_URL . 'game/src/WordPress.js',
        ['react', 'react-dom'],
        JACKALOPES_WP_VERSION,
        false // Load in header
    );
    
    // Register main game script
    wp_register_script(
        'jackalopes-game',
        JACKALOPES_WP_PLUGIN_URL . 'game/dist/assets/main.js',
        ['jackalopes-wp-helper', 'react', 'react-dom'], // Depend on helper script and React
        JACKALOPES_WP_VERSION,
        true
    );
    
    // Add script attributes for module type
    add_filter('script_loader_tag', function($tag, $handle) {
        if ('jackalopes-game' === $handle) {
            return str_replace('<script ', '<script type="text/javascript" crossorigin="anonymous" ', $tag);
        }
        return $tag;
    }, 10, 2);
    
    // Add inline CSS for game container
    wp_add_inline_style('jackalopes-game-styles', '
        .jackalopes-game-container {
            position: relative;
            overflow: hidden;
            background: #000;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        #jackalopes-game {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .game-loading {
            text-align: center;
            color: #fff;
            padding: 20px;
        }
        
        .loading-spinner {
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 5px solid #3498db;
            width: 40px;
            height: 40px;
            animation: jackalopes-spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes jackalopes-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .game-error {
            text-align: center;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 80%;
            margin: 0 auto;
        }
        
        .error-retry {
            background: #fff;
            color: #dc3545;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .error-retry:hover {
            background: #f8f9fa;
        }
    ');
    
    // Make asset URLs protocol-relative to avoid mixed content warnings
    $assets_url = JACKALOPES_WP_PLUGIN_URL . 'game/dist/assets/';
    // Remove http: or https: but keep the // for protocol-relative URLs
    $assets_url = preg_replace('/^https?:/', '', $assets_url);
    
    // Get the WebSocket server URL, ensuring it's secure when site is secure
    $server_url = jackalopes_wp_get_server_url();
    // If site is HTTPS but server is ws://, convert to wss://
    if (is_ssl() && strpos($server_url, 'ws://') === 0) {
        $server_url = 'wss://' . substr($server_url, 5);
    }
    
    // Add dynamic game settings
    wp_localize_script(
        'jackalopes-game',
        'jackalopesGameSettings',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'pluginUrl' => preg_replace('/^https?:/', '', JACKALOPES_WP_PLUGIN_URL),
            'assetsUrl' => $assets_url,
            'serverUrl' => $server_url,
            'debug' => WP_DEBUG,
            'nonce' => wp_create_nonce('jackalopes_game_nonce'),
            'isSecure' => is_ssl()
        ]
    );
}

/**
 * Enqueue game-specific assets.
 */
function jackalopes_wp_enqueue_game_assets() {
    // Use direct URL for CSS with PHP handler to force correct MIME type
    wp_deregister_style('jackalopes-game-styles');
    wp_register_style(
        'jackalopes-game-styles',
        add_query_arg('jackalopes_css', '1', JACKALOPES_WP_PLUGIN_URL . 'game/dist/assets/main.css'),
        [],
        JACKALOPES_WP_VERSION . '.' . time()
    );
    
    // Enqueue main game styles
    wp_enqueue_style('jackalopes-game-styles');
    
    // Enqueue React and ReactDOM
    wp_enqueue_script('react');
    wp_enqueue_script('react-dom');
    
    // Enqueue WordPress helper script
    wp_enqueue_script('jackalopes-wp-helper');
    
    // Enqueue main game script
    wp_enqueue_script('jackalopes-game');
}

/**
 * Get the WebSocket server URL.
 * 
 * This function checks if the Jackalopes Server plugin is active
 * and retrieves its configured server URL. If not available,
 * it falls back to a default URL.
 * 
 * @return string The WebSocket server URL.
 */
function jackalopes_wp_get_server_url() {
    // Check if Jackalopes Server plugin is active
    if (function_exists('jackalopes_server_get_websocket_url')) {
        return jackalopes_server_get_websocket_url();
    }
    
    // Fall back to default or configured URL
    $server_url = get_option('jackalopes_wp_server_url', '');
    
    if (empty($server_url)) {
        // Use default URL based on current site
        $protocol = is_ssl() ? 'wss://' : 'ws://';
        $server_url = $protocol . parse_url(home_url(), PHP_URL_HOST) . '/websocket/';
    }
    
    return $server_url;
}

/**
 * Handle direct asset requests and redirect them to the correct location
 * This is added to handle cases where the game tries to load assets from /src/assets
 */
function jackalopes_wp_handle_asset_paths() {
    // Check if the request is for a missing asset in src/assets/characters
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/src/assets/characters/') !== false) {
        $requested_file = basename($_SERVER['REQUEST_URI']);
        $correct_path = JACKALOPES_WP_PLUGIN_URL . 'game/dist/assets/characters/' . $requested_file;
        
        // Redirect to the correct asset path
        wp_redirect($correct_path);
        exit;
    }
    
    // Check for background.png in the root
    if (isset($_SERVER['REQUEST_URI']) && basename($_SERVER['REQUEST_URI']) === 'background.png') {
        $correct_path = JACKALOPES_WP_PLUGIN_URL . 'game/dist/assets/background.png';
        
        // Redirect to the correct asset path
        wp_redirect($correct_path);
        exit;
    }
}
add_action('template_redirect', 'jackalopes_wp_handle_asset_paths');

/**
 * Register custom rewrite rules to handle direct asset requests
 */
function jackalopes_wp_register_rewrites() {
    // Add rewrite rules for common asset paths
    add_rewrite_rule(
        '^src/assets/characters/([^/]+)$',
        'index.php?jackalopes_asset=characters/$1',
        'top'
    );
    
    add_rewrite_rule(
        '^background\.png$',
        'index.php?jackalopes_asset=background.png',
        'top'
    );
}
add_action('init', 'jackalopes_wp_register_rewrites');

/**
 * Add query vars for asset handling
 */
function jackalopes_wp_query_vars($vars) {
    $vars[] = 'jackalopes_asset';
    return $vars;
}
add_filter('query_vars', 'jackalopes_wp_query_vars');

/**
 * Handle asset requests through query vars
 */
function jackalopes_wp_handle_asset_requests() {
    global $wp_query;
    
    if (isset($wp_query->query_vars['jackalopes_asset'])) {
        $asset_path = $wp_query->query_vars['jackalopes_asset'];
        $file_path = JACKALOPES_WP_PLUGIN_DIR . 'game/dist/assets/' . $asset_path;
        
        if (file_exists($file_path)) {
            // Set appropriate content type
            $ext = pathinfo($file_path, PATHINFO_EXTENSION);
            switch ($ext) {
                case 'glb':
                case 'gltf':
                    header('Content-Type: model/gltf+json');
                    break;
                case 'png':
                    header('Content-Type: image/png');
                    break;
                case 'jpg':
                case 'jpeg':
                    header('Content-Type: image/jpeg');
                    break;
                case 'css':
                    header('Content-Type: text/css');
                    break;
                default:
                    header('Content-Type: application/octet-stream');
            }
            
            // Output file contents
            readfile($file_path);
            exit;
        }
    }
}
add_action('parse_request', 'jackalopes_wp_handle_asset_requests');

/**
 * Direct handler for CSS files - ensure they're served with the correct MIME type
 */
function jackalopes_wp_handle_css_files() {
    // Check if the request is for a CSS file in our plugin
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    
    if (strpos($request_uri, '/plugins/jackalopes-wp/') !== false && 
        strpos($request_uri, '.css') !== false) {
        
        // Extract the path to the CSS file
        $plugin_dir_uri = plugin_dir_url(__FILE__);
        $plugin_dir_path = plugin_dir_path(__FILE__);
        $base_path = str_replace('includes/', '', $plugin_dir_path);
        
        // Try to determine the file path
        $file_path = $base_path . str_replace('/plugins/jackalopes-wp/', '', $request_uri);
        $file_path = realpath($file_path);
        
        // Only serve CSS files
        if ($file_path && file_exists($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) === 'css') {
            header('Content-Type: text/css');
            readfile($file_path);
            exit;
        }
    }
}
add_action('init', 'jackalopes_wp_handle_css_files', 999);

// Add a direct handler for CSS files via query parameter
add_action('init', function() {
    if (isset($_GET['jackalopes_css'])) {
        $css_path = JACKALOPES_WP_PLUGIN_DIR . 'game/dist/assets/main.css';
        if (file_exists($css_path)) {
            header('Content-Type: text/css');
            header('Cache-Control: max-age=3600');
            readfile($css_path);
            exit;
        }
    }
}, 1); 