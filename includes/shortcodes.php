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
         class="jackalopes-game-container jackalope-game-container" 
         data-fullscreen="<?php echo esc_attr($atts['fullscreen']); ?>"
         data-server="<?php echo esc_attr($atts['server']); ?>"
         data-disable-ui="<?php echo esc_attr($atts['disable_ui']); ?>"
         data-disable-threejs="<?php echo esc_attr($atts['disable_threejs']); ?>"
         style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>; position: relative; overflow: hidden;">
    </div>
    <script>
        // Wait for the module to be fully loaded
        setTimeout(function() {
            // Set flag to prevent auto-pointer lock
            window.jackalopesPreventAutoPointerLock = true;
            
            // Initialize the game when the DOM is fully loaded
            if (typeof window.initJackalopesGame === 'function') {
                window.initJackalopesGame('<?php echo esc_js($game_id); ?>', {
                    fullscreen: <?php echo $atts['fullscreen'] === 'true' ? 'true' : 'false'; ?>,
                    serverUrl: '<?php echo esc_js($atts['server']); ?>',
                    disableUi: <?php echo $atts['disable_ui'] === 'true' ? 'true' : 'false'; ?>,
                    disableThreejs: <?php echo $atts['disable_threejs'] === 'true' ? 'true' : 'false'; ?>,
                    preventPointerLock: true
                });
                
                // Add start game button functionality
                var container = document.getElementById('<?php echo esc_js($game_id); ?>');
                var startGameOverlay = container.querySelector('.start-game-overlay');
                var startGameBtn = container.querySelector('.start-game-button');
                
                if (startGameBtn && startGameOverlay) {
                    startGameBtn.addEventListener('click', function() {
                        // Hide the overlay
                        startGameOverlay.style.display = 'none';
                        
                        // Enable pointer lock by removing the prevention flag
                        window.jackalopesPreventAutoPointerLock = false;
                        
                        // Request pointer lock to start the game
                        try {
                            container.requestPointerLock = container.requestPointerLock || 
                                                          container.mozRequestPointerLock || 
                                                          container.webkitRequestPointerLock;
                            container.requestPointerLock();
                            
                            // Dispatch a custom event for the game to handle
                            var event = new CustomEvent('jackalopesGameStarted');
                            window.dispatchEvent(event);
                        } catch(err) {
                            console.error('Error requesting pointer lock:', err);
                        }
                    });
                }
            }
            
            // Make sure canvas is properly visible when it appears
            setTimeout(function() {
                var canvas = document.querySelector('#<?php echo esc_js($game_id); ?> canvas');
                if (canvas) {
                    // Force canvas to be visible
                    canvas.style.display = 'block';
                    canvas.style.width = '100%';
                    canvas.style.height = '100%';
                }
            }, 1000);
        }, 100);
    </script>
    <?php
    
    // Return the buffered content
    return ob_get_clean();
} 