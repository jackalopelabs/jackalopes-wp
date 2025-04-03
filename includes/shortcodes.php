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
    
    // Add critical CSS for proper UI and canvas positioning
    ?>
    <style>
        /* Ensure the game container has proper positioning */
        .jackalopes-game-container {
            position: relative;
            overflow: hidden;
        }

        /* Canvas should be behind UI elements */
        .jackalopes-game-container canvas {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            display: block !important;
            z-index: 1 !important;
        }

        /* All UI elements should be above canvas */
        .jackalopes-game-container .fixed-ui,
        .jackalopes-game-container .virtual-gamepad,
        .jackalopes-game-container .game-controls,
        .jackalopes-game-container .start-game-overlay,
        .jackalopes-game-container .start-game-button,
        .jackalopes-game-container .jackalopes-audio-button-container,
        .jackalopes-game-container .fullscreen-button,
        .jackalopes-game-container .fps-stats {
            z-index: 10 !important;
            position: absolute !important;
        }

        /* Fixed positioning for UI corners */
        .jackalopes-game-container .fixed-top-left {
            top: 10px !important;
            left: 10px !important;
        }
        .jackalopes-game-container .fixed-top-right {
            top: 10px !important;
            right: 10px !important;
        }
        .jackalopes-game-container .fixed-bottom-left {
            bottom: 10px !important;
            left: 10px !important;
        }
        .jackalopes-game-container .fixed-bottom-right {
            bottom: 10px !important;
            right: 10px !important;
        }

        /* Fix for some Three.js elements that might get wrong position */
        .jackalopes-game-container div[class^="leva-"], 
        .jackalopes-game-container div[id^="leva-"] {
            position: absolute !important;
            z-index: 100 !important;
        }
    </style>
    <?php
    
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
                // Longer delay for initialization
                setTimeout(function() {
                    window.initJackalopesGame('<?php echo esc_js($game_id); ?>', {
                        fullscreen: <?php echo $atts['fullscreen'] === 'true' ? 'true' : 'false'; ?>,
                        serverUrl: '<?php echo esc_js($atts['server']); ?>',
                        disableUi: <?php echo $atts['disable_ui'] === 'true' ? 'true' : 'false'; ?>,
                        disableThreejs: <?php echo $atts['disable_threejs'] === 'true' ? 'true' : 'false'; ?>,
                        preventPointerLock: true
                    });
                }, 300); // Increased to 300ms for better loading sequence
                
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
                    // Force canvas to be visible with explicit positioning
                    canvas.style.display = 'block';
                    canvas.style.width = '100%';
                    canvas.style.height = '100%';
                    canvas.style.position = 'absolute';
                    canvas.style.top = '0';
                    canvas.style.left = '0';
                    canvas.style.zIndex = '1'; // Lower z-index to ensure UI is on top
                    
                    // Remove any other canvases that might be duplicates
                    var allCanvases = document.querySelectorAll('#<?php echo esc_js($game_id); ?> canvas');
                    if (allCanvases.length > 1) {
                        console.log('Found multiple canvases - removing duplicates');
                        for (var i = 1; i < allCanvases.length; i++) {
                            allCanvases[i].remove();
                        }
                    }
                    
                    // Force a reflow for mobile devices
                    setTimeout(function() {
                        // Check if this is likely a mobile device
                        var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || navigator.maxTouchPoints > 1;
                        if (isMobile) {
                            console.log('Mobile device detected - forcing canvas reflow');
                            // Force a reflow by toggling display
                            canvas.style.display = 'none';
                            // Force layout calculation
                            void canvas.offsetHeight;
                            // Restore display
                            setTimeout(function() {
                                canvas.style.display = 'block';
                            }, 50);
                        }
                    }, 500);
                } else {
                    console.warn('Canvas not found after 2000ms - game may not be rendering properly.');
                }
            }, 2000); // Increased to 2000ms for slower devices
            
            // Additional check to ensure UI elements remain visible
            setTimeout(function() {
                // Ensure all UI elements have proper z-index
                var uiElements = document.querySelectorAll('#<?php echo esc_js($game_id); ?> .fixed-ui, ' + 
                                                         '#<?php echo esc_js($game_id); ?> .virtual-gamepad, ' +
                                                         '#<?php echo esc_js($game_id); ?> .game-controls, ' +
                                                         '#<?php echo esc_js($game_id); ?> .jackalopes-audio-button-container, ' +
                                                         '#<?php echo esc_js($game_id); ?> .fullscreen-button');
                
                if (uiElements.length > 0) {
                    console.log('Found ' + uiElements.length + ' UI elements, ensuring proper visibility');
                    uiElements.forEach(function(element) {
                        element.style.zIndex = '10';
                        element.style.position = 'absolute';
                        element.style.visibility = 'visible';
                        element.style.opacity = '1';
                    });
                } else {
                    console.warn('No UI elements found after 4 seconds');
                }
            }, 4000); // Check after all other initialization
        }, 200); // Increased initial delay to 200ms
    </script>
    <?php
    
    // Return the buffered content
    return ob_get_clean();
} 