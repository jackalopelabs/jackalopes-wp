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
    add_shortcode('jackalopes', 'jackalopes_wp_game_shortcode');
}

/**
 * Shortcode callback for [jackalopes] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string Shortcode output.
 */
function jackalopes_wp_game_shortcode($atts = []) {
    // Parse attributes
    $atts = shortcode_atts(
        [
            'width' => '100%',
            'height' => '600px',
            'fullscreen' => 'false',
            'server' => '', // Optional server URL override
            'disable_ui' => 'false', // Option to disable default UI
            'disable_threejs' => 'false', // Option to disable Three.js loading
        ],
        $atts,
        'jackalopes'
    );

    // Enqueue required scripts and styles
    jackalopes_wp_enqueue_game_assets();
    
    // Generate a unique ID for this game instance
    $game_id = 'jackalopes-game-' . uniqid();
    
    // Start output buffering
    ob_start();
    
    // Game container HTML with better positioning and CSS
    ?>
    <div id="<?php echo esc_attr($game_id); ?>" 
         class="jackalopes-game-container" 
         data-fullscreen="<?php echo esc_attr($atts['fullscreen']); ?>"
         data-server="<?php echo esc_attr($atts['server']); ?>"
         data-disable-ui="<?php echo esc_attr($atts['disable_ui']); ?>"
         data-disable-threejs="<?php echo esc_attr($atts['disable_threejs']); ?>"
         style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>; position: relative; overflow: hidden;">
        <div class="jackalopes-loading">
            <div class="jackalopes-loading-spinner"></div>
            <div class="jackalopes-loading-message">Loading Jackalopes...</div>
        </div>
        
        <style>
            /* Inline critical CSS to ensure proper positioning */
            #<?php echo esc_attr($game_id); ?> {
                position: relative !important;
                overflow: hidden !important;
            }
            #<?php echo esc_attr($game_id); ?> .fixed-ui {
                position: absolute !important;
                z-index: 10;
            }
            #<?php echo esc_attr($game_id); ?> .fixed-top-left {
                top: 10px !important;
                left: 10px !important;
            }
            #<?php echo esc_attr($game_id); ?> .fixed-top-right {
                top: 10px !important;
                right: 10px !important;
            }
            #<?php echo esc_attr($game_id); ?> .fixed-bottom-left {
                bottom: 10px !important;
                left: 10px !important;
            }
            #<?php echo esc_attr($game_id); ?> .fixed-bottom-right {
                bottom: 10px !important;
                right: 10px !important;
            }
            #<?php echo esc_attr($game_id); ?>.fullscreen-active {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                z-index: 9999 !important;
            }
        </style>
    </div>
    <script>
        // Wait for the module to be fully loaded
        setTimeout(function() {
            // Initialize the game when the DOM is fully loaded
            if (typeof window.initJackalopesGame === 'function') {
                window.initJackalopesGame('<?php echo esc_js($game_id); ?>', {
                    fullscreen: <?php echo $atts['fullscreen'] === 'true' ? 'true' : 'false'; ?>,
                    serverUrl: '<?php echo esc_js($atts['server']); ?>',
                    disableUi: <?php echo $atts['disable_ui'] === 'true' ? 'true' : 'false'; ?>,
                    disableThreejs: <?php echo $atts['disable_threejs'] === 'true' ? 'true' : 'false'; ?>
                });
                
                // Add event listener for fullscreen changes
                document.addEventListener('fullscreenchange', function() {
                    var container = document.getElementById('<?php echo esc_js($game_id); ?>');
                    if (document.fullscreenElement === container) {
                        container.classList.add('fullscreen-active');
                    } else {
                        container.classList.remove('fullscreen-active');
                    }
                });
                
                // Ensure all UI elements stay within the container
                ensureContainedUI('<?php echo esc_js($game_id); ?>');
            } else {
                console.error('Jackalopes game initialization function not found. Make sure all assets are properly loaded.');
            }
        }, 100);
        
        // Helper function to ensure UI elements stay contained
        function ensureContainedUI(containerId) {
            var container = document.getElementById(containerId);
            if (!container) return;
            
            // Check for UI elements every second and move them into the container if needed
            setInterval(function() {
                var fixedElements = document.querySelectorAll('.fps-stats, .virtual-gamepad, .game-controls, .jackalopes-ui');
                fixedElements.forEach(function(el) {
                    if (el.parentElement !== container) {
                        var style = window.getComputedStyle(el);
                        container.appendChild(el);
                        
                        // Add appropriate positioning class
                        if (style.top === '0px' || parseInt(style.top) < 50) {
                            if (style.left === '0px' || parseInt(style.left) < 50) {
                                el.classList.add('fixed-ui', 'fixed-top-left');
                            } else if (style.right === '0px' || parseInt(style.right) < 50) {
                                el.classList.add('fixed-ui', 'fixed-top-right');
                            }
                        } else if (style.bottom === '0px' || parseInt(style.bottom) < 50) {
                            if (style.left === '0px' || parseInt(style.left) < 50) {
                                el.classList.add('fixed-ui', 'fixed-bottom-left');
                            } else if (style.right === '0px' || parseInt(style.right) < 50) {
                                el.classList.add('fixed-ui', 'fixed-bottom-right');
                            }
                        }
                    }
                });
            }, 1000);
        }
    </script>
    <?php
    
    // Return the buffered content
    return ob_get_clean();
} 