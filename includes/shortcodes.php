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
        <script>
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
                
                if (callback) {
                    script.onload = callback;
                }
                
                document.head.appendChild(script);
            }
            
            // Check if React is already loaded
            if (typeof React === 'undefined') {
                console.log('React not loaded, loading from CDN...');
                loadScript('https://unpkg.com/react@18/umd/react.production.min.js', 'react-fallback', function() {
                    // After React loads, load ReactDOM
                    loadScript('https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', 'react-dom-fallback');
                });
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
    </div>
    <?php
    return ob_get_clean();
} 