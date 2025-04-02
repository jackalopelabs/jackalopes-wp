<?php
/**
 * Shortcode-related functionality.
 *
 * @package Jackalopes_WP
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Register all shortcodes.
 */
function jackalopes_wp_register_shortcodes() {
    add_shortcode('jackalopes', 'jackalopes_wp_shortcode_callback');
}

/**
 * Shortcode callback for embedding the game.
 */
function jackalopes_wp_shortcode_callback($atts) {
    // Ensure game assets are enqueued
    jackalopes_wp_enqueue_game_assets();
    
    // Parse shortcode attributes
    $attributes = shortcode_atts(
        [
            'width' => '100%',
            'height' => '600px',
            'class' => '',
            'enable_server' => 'true',
        ],
        $atts
    );
    
    // Build custom CSS for the container
    $custom_css = sprintf(
        'width: %s; height: %s;',
        esc_attr($attributes['width']),
        esc_attr($attributes['height'])
    );
    
    // Convert string 'true'/'false' to boolean for JavaScript
    $enable_server = ($attributes['enable_server'] === 'true') ? 'true' : 'false';
    
    // Start output buffering to return HTML
    ob_start();
    ?>
    <div class="jackalopes-game-container <?php echo esc_attr($attributes['class']); ?>" style="<?php echo $custom_css; ?>">
        <style>
            /* Basic container styles to prevent layout issues */
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
        </style>
        <script>
        // Setup React safety checks to prevent multiple versions
        // Use the version WordPress is actually loading
        window._REACT_VERSION = '18.3.1';
        
        // Remove any existing React if it's the wrong version
        if (typeof React !== 'undefined') {
            if (React.version !== window._REACT_VERSION) {
                console.warn('Detected React version:', React.version, 'vs expected:', window._REACT_VERSION);
                // Don't remove, just note the issue
            }
        }
        
        // Check if React is already loaded, if not, load it
        (function() {
            function loadScript(src, id, callback) {
                if (document.getElementById(id)) {
                    if (callback) callback();
                    return;
                }
                
                var script = document.createElement('script');
                script.id = id;
                script.src = src;
                script.async = false;
                script.crossOrigin = "anonymous";
                
                if (callback) {
                    script.onload = callback;
                }
                
                document.head.appendChild(script);
            }
            
            // Check if React is already loaded
            if (typeof React === 'undefined') {
                console.log('React not loaded, loading from CDN...');
                loadScript('https://unpkg.com/react@18.3.1/umd/react.production.min.js', 'react-fallback', function() {
                    // After React loads, load ReactDOM
                    loadScript('https://unpkg.com/react-dom@18.3.1/umd/react-dom.production.min.js', 'react-dom-fallback', function() {
                        // Define global window.React and window.ReactDOM explicitly
                        window.React = React;
                        window.ReactDOM = ReactDOM;
                        console.log('React and ReactDOM loaded and assigned to window object');
                        
                        // Verify React hooks are available
                        if (typeof React.useState !== 'function' || typeof React.useEffect !== 'function') {
                            console.error('React hooks not available after loading React');
                        } else {
                            console.log('React hooks verified as available');
                        }
                    });
                });
            } else {
                console.log('React already loaded, version:', React.version);
                // Ensure it's on the window object
                window.React = React;
                window.ReactDOM = ReactDOM;
            }
        })();
        
        // Fix asset path issue before game loads
        (function() {
            // Helper function to intercept and fix asset fetch requests
            function fixAssetPaths() {
                // Store original fetch
                const originalFetch = window.fetch;
                
                // Override fetch
                window.fetch = function(url, options) {
                    // Check if this is an asset request with duplicated paths
                    if (typeof url === 'string' && url.includes('/assets/assets/')) {
                        // Fix the duplicated path
                        url = url.replace('/assets/assets/', '/assets/');
                        console.log('Fixed asset path:', url);
                    }
                    // Call original fetch with fixed URL
                    return originalFetch.call(this, url, options);
                };
            }
            
            // Apply the fix
            fixAssetPaths();
        })();
        </script>
        
        <div id="jackalopes-game" data-initialized="false" data-enable-server="<?php echo esc_attr($enable_server); ?>">
            <div class="game-loading">
                <p class="loading-message">Loading Jackalopes Game...</p>
                <div class="loading-spinner"></div>
            </div>
            <div class="game-error" style="display: none;">
                <p class="error-message">There was an error loading the game. Please try refreshing the page.</p>
                <button class="error-retry">Retry</button>
            </div>
        </div>
        <script src="<?php echo esc_url(JACKALOPES_WP_PLUGIN_URL . 'canvas-fix.js?t=' . time()); ?>"></script>
    </div>
    <?php
    return ob_get_clean();
} 