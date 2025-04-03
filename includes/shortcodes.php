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
        
        <!-- Start Game button - initially visible -->
        <div class="start-game-overlay">
            <button class="start-game-button">Start Game</button>
        </div>
        
        <style>
            /* Inline critical CSS to ensure proper positioning */
            #<?php echo esc_attr($game_id); ?> {
                position: relative !important;
                overflow: hidden !important;
                background-color: #242424;
            }
            #<?php echo esc_attr($game_id); ?> .start-game-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
                background-color: rgba(0, 0, 0, 0.7);
                z-index: 150;
            }
            #<?php echo esc_attr($game_id); ?> .start-game-button {
                background-color: #4CAF50;
                color: white;
                padding: 16px 32px;
                border: none;
                border-radius: 8px;
                font-size: 24px;
                cursor: pointer;
                transition: all 0.3s ease;
                font-weight: bold;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            }
            #<?php echo esc_attr($game_id); ?> .start-game-button:hover {
                background-color: #45a049;
                transform: scale(1.05);
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
            }
        </style>
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