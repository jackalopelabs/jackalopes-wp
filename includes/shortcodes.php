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
        
        <style>
            @keyframes jackalopes-spin {
                to { transform: rotate(360deg); }
            }
        </style>
        
        <script>
            // Remove loading screen once game is initialized
            window.addEventListener('DOMContentLoaded', function() {
                var removeLoadingTimeout = setTimeout(function() {
                    var loadingContainer = document.querySelector('#<?php echo esc_js($game_id); ?> .jackalopes-loading-container');
                    var canvasElement = document.querySelector('#<?php echo esc_js($game_id); ?> canvas');
                    
                    // Only remove if canvas exists
                    if (loadingContainer && canvasElement) {
                        loadingContainer.style.opacity = '0';
                        loadingContainer.style.transition = 'opacity 0.5s ease-out';
                        
                        // Remove after fade out
                        setTimeout(function() {
                            loadingContainer.remove();
                        }, 500);
                    }
                }, 2000); // Wait for 2 seconds after DOM content loaded
                
                // Force remove after 15 seconds regardless
                setTimeout(function() {
                    var loadingContainer = document.querySelector('#<?php echo esc_js($game_id); ?> .jackalopes-loading-container');
                    if (loadingContainer) {
                        loadingContainer.remove();
                    }
                }, 15000);
            });
        </script>
    </div>
    <script>
        // Add a variable to track initialization attempts
        var jackalopesInitAttempts = 0;
        var jackalopesInitSuccess = false;
        var jackalopesMaxInitAttempts = 5;
        
        // Create a function to check if the scripts are loaded
        function checkJackalopesScriptsLoaded() {
            console.log('Checking if Jackalopes scripts are loaded...');
            
            // Check if the initJackalopesGame function exists
            if (typeof window.initJackalopesGame === 'function') {
                console.log('Jackalopes scripts loaded successfully!');
                initializeJackalopesGame();
                return true;
            } else {
                console.warn('Jackalopes scripts not loaded yet, attempt ' + (jackalopesInitAttempts + 1) + ' of ' + jackalopesMaxInitAttempts);
                jackalopesInitAttempts++;
                
                if (jackalopesInitAttempts < jackalopesMaxInitAttempts) {
                    // Try again in 500ms
                    setTimeout(checkJackalopesScriptsLoaded, 500);
                } else {
                    // Maximum attempts reached, show error
                    console.error('Failed to load Jackalopes scripts after multiple attempts');
                    showJackalopesError('Failed to load game scripts. Please try refreshing the page.');
                }
                return false;
            }
        }
        
        // Create a function to handle initialization
        function initializeJackalopesGame() {
            // Set flag to prevent auto-pointer lock
            window.jackalopesPreventAutoPointerLock = true;
            
            var container = document.getElementById('<?php echo esc_js($game_id); ?>');
            
            // Make sure container exists
            if (!container) {
                console.error('Jackalopes game container not found');
                return;
            }
            
            console.log('Initializing Jackalopes game in container: <?php echo esc_js($game_id); ?>');
            
            // Check if this is a mobile device
            var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || navigator.maxTouchPoints > 1;
            
            if (isMobile) {
                console.log('Mobile device detected - applying optimized settings');
                
                // Create global settings for mobile
                window.jackalopesIsMobile = true;
                window.jackalopesUseTouch = true;
                window.jackalopesPreventPointerLock = true;
                window.jackalopesDisableEffects = true; // Tell the game to disable heavy effects
                
                // Create backup polyfills for pointer lock
                if (typeof Element.prototype.requestPointerLock !== 'function') {
                    Element.prototype.requestPointerLock = function() { return false; };
                }
                if (document.body && typeof document.body.requestPointerLock !== 'function') {
                    document.body.requestPointerLock = function() { return false; };
                }
                if (document.documentElement && typeof document.documentElement.requestPointerLock !== 'function') {
                    document.documentElement.requestPointerLock = function() { return false; };
                }
                if (typeof document.exitPointerLock !== 'function') {
                    document.exitPointerLock = function() { return false; };
                }
                
                // Add mobile touch event listener
                document.addEventListener('touchstart', function() {
                    console.log('Touch event detected - activating mobile mode');
                    window.dispatchEvent(new CustomEvent('jackalopesTouchStart'));
                }, { passive: true });
                
                // Create a placeholder canvas to ensure visibility
                var mobileCanvas = document.createElement('canvas');
                mobileCanvas.id = 'jackalopes-mobile-canvas';
                mobileCanvas.style.position = 'absolute';
                mobileCanvas.style.top = '0';
                mobileCanvas.style.left = '0';
                mobileCanvas.style.width = '100%';
                mobileCanvas.style.height = '100%';
                mobileCanvas.style.zIndex = '5';
                mobileCanvas.style.backgroundColor = '#111';
                container.appendChild(mobileCanvas);
                
                // Add mobile-specific styles
                var mobileStyles = document.createElement('style');
                mobileStyles.textContent = `
                    #${container.id} {
                        position: relative !important;
                        overflow: hidden !important;
                        width: 100% !important;
                        height: ${container.style.height || '600px'} !important;
                        min-height: 300px !important;
                        touch-action: none !important;
                        background-color: #111 !important;
                    }
                    #${container.id} canvas {
                        position: absolute !important;
                        top: 0 !important;
                        left: 0 !important;
                        width: 100% !important;
                        height: 100% !important;
                        display: block !important;
                        z-index: 10 !important;
                        visibility: visible !important;
                        opacity: 1 !important;
                        background-color: #111 !important;
                    }
                    #${container.id} .game-ui {
                        position: absolute !important;
                        z-index: 20 !important;
                    }
                    /* Hide duplicate fullscreen buttons */
                    #${container.id} .fullscreen-button:not(:first-child) {
                        display: none !important;
                    }
                    /* Style the fullscreen button properly for mobile */
                    #${container.id} .fullscreen-button {
                        background: rgba(0, 0, 0, 0.7) !important;
                        color: white !important;
                        border: none !important;
                        border-radius: 4px !important;
                        padding: 8px !important;
                        position: absolute !important;
                        top: 10px !important;
                        right: 10px !important;
                        z-index: 50 !important;
                        width: auto !important;
                        height: auto !important;
                    }
                `;
                document.head.appendChild(mobileStyles);
                
                // Force canvas to be a specific size to help rendering
                container.style.width = '100%';
                container.style.height = container.style.height || '600px';
                container.style.minHeight = '300px';
                container.style.backgroundColor = '#111';
                
                // Also add some dynamic content for mobile to ensure something is visible
                var mobileOverlay = document.createElement('div');
                mobileOverlay.className = 'jackalopes-mobile-overlay';
                mobileOverlay.style.position = 'absolute';
                mobileOverlay.style.top = '50%';
                mobileOverlay.style.left = '50%';
                mobileOverlay.style.transform = 'translate(-50%, -50%)';
                mobileOverlay.style.textAlign = 'center';
                mobileOverlay.style.color = 'white';
                mobileOverlay.style.zIndex = '25';
                mobileOverlay.style.backgroundColor = 'rgba(0,0,0,0.7)';
                mobileOverlay.style.padding = '20px';
                mobileOverlay.style.borderRadius = '10px';
                mobileOverlay.style.maxWidth = '80%';
                mobileOverlay.innerHTML = `
                    <h2 style="margin: 0 0 15px 0; font-size: 24px;">Jackalopes Game</h2>
                    <p style="margin: 0 0 20px 0;">Tap to play the 3D game</p>
                    <button class="jackalopes-mobile-play" style="background: #4c8bf5; color: white; border: none; border-radius: 4px; padding: 12px 20px; font-weight: bold; font-size: 16px;">Play Now</button>
                `;
                container.appendChild(mobileOverlay);
                
                // Add tap handler to mobile overlay
                var playButton = mobileOverlay.querySelector('.jackalopes-mobile-play');
                if (playButton) {
                    playButton.addEventListener('click', function() {
                        // Hide the overlay
                        mobileOverlay.style.display = 'none';
                        
                        // Activate the game
                        var event = new CustomEvent('jackalopesGameStarted');
                        window.dispatchEvent(event);
                    });
                }
                
                // Longer delay for initialization on mobile
                setTimeout(function() {
                    try {
                        // Initialize with mobile optimizations
                        window.initJackalopesGame('<?php echo esc_js($game_id); ?>', {
                            fullscreen: false, // Disable fullscreen on mobile
                            serverUrl: '<?php echo esc_js($atts['server']); ?>',
                            disableUi: <?php echo $atts['disable_ui'] === 'true' ? 'true' : 'false'; ?>,
                            disableThreejs: <?php echo $atts['disable_threejs'] === 'true' ? 'true' : 'false'; ?>,
                            preventPointerLock: true,
                            isMobile: true,
                            mobileFriendly: true,
                            lowQuality: true
                        });
                        
                        jackalopesInitSuccess = true;
                        
                        // Setup repeated checks to force canvas visibility and remove duplicates
                        var visibilityCheckCount = 0;
                        var visibilityCheckInterval = setInterval(function() {
                            var canvases = container.querySelectorAll('canvas');
                            console.log('Mobile visibility check: Found ' + canvases.length + ' canvas elements');
                            
                            // Check for duplicate fullscreen buttons and remove extras
                            var fullscreenButtons = container.querySelectorAll('.fullscreen-button');
                            if (fullscreenButtons.length > 1) {
                                console.log('Found ' + fullscreenButtons.length + ' fullscreen buttons - keeping only the first one');
                                for (var i = 1; i < fullscreenButtons.length; i++) {
                                    fullscreenButtons[i].style.display = 'none';
                                }
                            }
                            
                            canvases.forEach(function(canvas, index) {
                                if (canvas.id !== 'jackalopes-mobile-canvas') {
                                    // Force the proper game canvas to be visible
                                    canvas.style.display = 'block';
                                    canvas.style.position = 'absolute';
                                    canvas.style.top = '0';
                                    canvas.style.left = '0';
                                    canvas.style.width = '100%';
                                    canvas.style.height = '100%';
                                    canvas.style.zIndex = '10';
                                    canvas.style.opacity = '1';
                                    canvas.style.visibility = 'visible';
                                    canvas.style.backgroundColor = '#111';
                                    
                                    // Remove our placeholder when the real canvas exists and has content
                                    var placeholder = document.getElementById('jackalopes-mobile-canvas');
                                    if (placeholder) {
                                        setTimeout(function() {
                                            // Only hide the placeholder after the game canvas has had time to render
                                            placeholder.style.display = 'none';
                                        }, 1000);
                                    }
                                    
                                    console.log('Mobile: Applied visibility style to canvas #' + index);
                                }
                            });
                            
                            visibilityCheckCount++;
                            if (visibilityCheckCount >= 10) {
                                clearInterval(visibilityCheckInterval);
                            }
                        }, 500);
                        
                    } catch (err) {
                        console.error('Error initializing Jackalopes game on mobile:', err);
                        showJackalopesError('Error initializing game on mobile: ' + err.message);
                    }
                }, 500);
                
            } else {
                // Desktop initialization
                setTimeout(function() {
                    try {
                        // Regular initialization for desktop
                        window.initJackalopesGame('<?php echo esc_js($game_id); ?>', {
                            fullscreen: <?php echo $atts['fullscreen'] === 'true' ? 'true' : 'false'; ?>,
                            serverUrl: '<?php echo esc_js($atts['server']); ?>',
                            disableUi: <?php echo $atts['disable_ui'] === 'true' ? 'true' : 'false'; ?>,
                            disableThreejs: <?php echo $atts['disable_threejs'] === 'true' ? 'true' : 'false'; ?>,
                            preventPointerLock: true
                        });
                        jackalopesInitSuccess = true;
                        console.log('Jackalopes game initialized successfully');
                    } catch (err) {
                        console.error('Error initializing Jackalopes game:', err);
                        showJackalopesError('Error initializing game: ' + err.message);
                    }
                }, 300);
            }
            
            // Add start game button functionality
            var startGameOverlay = container.querySelector('.start-game-overlay');
            var startGameBtn = container.querySelector('.start-game-button');
            
            if (startGameBtn && startGameOverlay) {
                startGameBtn.addEventListener('click', function() {
                    // Hide the overlay
                    startGameOverlay.style.display = 'none';
                    
                    // Enable pointer lock by removing the prevention flag
                    window.jackalopesPreventAutoPointerLock = false;
                    
                    // Check if this is a mobile device
                    var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || navigator.maxTouchPoints > 1;
                    
                    // Request pointer lock to start the game, but only on desktop
                    if (!isMobile) {
                        try {
                            var targetElement = container;
                            
                            // Try container first, checking if the function exists before calling it
                            if (targetElement.requestPointerLock) {
                                targetElement.requestPointerLock();
                            }
                            // Fallback to document if container fails
                            else if (document.documentElement.requestPointerLock) {
                                document.documentElement.requestPointerLock();
                            }
                            // Last resort: try body if the function exists
                            else if (document.body && typeof document.body.requestPointerLock === 'function') {
                                document.body.requestPointerLock();
                            }
                            else {
                                console.warn('Pointer lock not supported on this device/browser');
                            }
                        } catch(err) {
                            console.error('Error requesting pointer lock:', err);
                        }
                    } else {
                        console.log('Mobile device detected - skipping pointer lock');
                    }
                    
                    // Dispatch a custom event for the game to handle
                    var event = new CustomEvent('jackalopesGameStarted');
                    window.dispatchEvent(event);
                });
            }
            
            // Watch for canvas creation and ensure proper setup
            var canvasCheckInterval = setInterval(function() {
                var canvas = container.querySelector('canvas');
                if (canvas) {
                    clearInterval(canvasCheckInterval);
                    console.log('Canvas found, ensuring proper setup...');
                    
                    // Force canvas to be visible with explicit positioning
                    canvas.style.display = 'block';
                    canvas.style.width = '100%';
                    canvas.style.height = '100%';
                    canvas.style.position = 'absolute';
                    canvas.style.top = '0';
                    canvas.style.left = '0';
                    canvas.style.zIndex = '1'; // Lower z-index to ensure UI is on top
                    
                    // Remove any other canvases that might be duplicates
                    var allCanvases = container.querySelectorAll('canvas');
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
                }
            }, 200); // Check every 200ms
            
            // Set a timeout to clear the interval if no canvas is found after 10 seconds
            setTimeout(function() {
                if (canvasCheckInterval) {
                    clearInterval(canvasCheckInterval);
                    
                    // If no canvas was found and init wasn't successful, show error
                    var canvas = container.querySelector('canvas');
                    if (!canvas && !jackalopesInitSuccess) {
                        console.error('No canvas found after 10 seconds - game initialization likely failed');
                        showJackalopesError('Game failed to initialize properly. Please try a different browser or device.');
                    }
                }
            }, 10000);
            
            // Additional check to ensure UI elements remain visible
            setTimeout(function() {
                // Ensure all UI elements have proper z-index
                var uiElements = container.querySelectorAll('.fixed-ui, .virtual-gamepad, .game-controls, .jackalopes-audio-button-container, .fullscreen-button');
                
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
            }, 4000);
        }
        
        // Function to show error message in the game container
        function showJackalopesError(message) {
            var container = document.getElementById('<?php echo esc_js($game_id); ?>');
            if (!container) return;
            
            // Create error message element
            var errorDiv = document.createElement('div');
            errorDiv.className = 'jackalopes-error-message';
            errorDiv.style.position = 'absolute';
            errorDiv.style.top = '50%';
            errorDiv.style.left = '50%';
            errorDiv.style.transform = 'translate(-50%, -50%)';
            errorDiv.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            errorDiv.style.color = 'white';
            errorDiv.style.padding = '20px';
            errorDiv.style.borderRadius = '5px';
            errorDiv.style.textAlign = 'center';
            errorDiv.style.maxWidth = '80%';
            errorDiv.style.zIndex = '1000';
            errorDiv.innerHTML = '<h3 style="margin-top:0;">Jackalopes Game Error</h3><p>' + message + '</p>' +
                                '<button id="jackalopes-retry-button" style="background:#4CAF50;border:none;color:white;padding:10px 15px;border-radius:4px;cursor:pointer;margin-top:10px;">Retry</button>';
            
            // Add to container
            container.appendChild(errorDiv);
            
            // Add retry button handler
            var retryButton = document.getElementById('jackalopes-retry-button');
            if (retryButton) {
                retryButton.addEventListener('click', function() {
                    // Remove error message
                    errorDiv.remove();
                    // Reset variables
                    jackalopesInitAttempts = 0;
                    jackalopesInitSuccess = false;
                    // Try again
                    checkJackalopesScriptsLoaded();
                });
            }
        }
        
        // Add a specific error handler for webGL context issues
        window.addEventListener('webglcontextlost', function(e) {
            console.error('WebGL context lost:', e);
            showJackalopesError('WebGL context lost. This may be due to limited graphics memory or browser restrictions.');
        }, false);
        
        // Check if this is a mobile device and apply appropriate settings
        var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || navigator.maxTouchPoints > 1;
        if (isMobile) {
            console.log('Mobile device detected - applying mobile-specific settings');
            // Set global flag for mobile devices that the game code can check
            window.jackalopesIsMobile = true;
            // Set flag to prevent auto-pointer lock attempts on mobile
            window.jackalopesPreventPointerLock = true;
            // Set flag for touch events
            window.jackalopesUseTouch = true;
            
            // Add a touch handler to the document to ensure touch events are captured
            document.addEventListener('touchstart', function() {
                console.log('Touch event detected - game should be interactive now');
                
                // Dispatch a custom event that our game can listen for
                var event = new CustomEvent('jackalopesTouchStart');
                window.dispatchEvent(event);
            }, { passive: true });
            
            // Create a no-op polyfill for requestPointerLock to prevent errors in the main game code
            if (typeof Element.prototype.requestPointerLock !== 'function') {
                Element.prototype.requestPointerLock = function() {
                    console.log('Pointer lock requested but not available on this device - using no-op polyfill');
                    return false;
                };
            }
            
            // Apply to document and body to prevent errors
            if (document.body && typeof document.body.requestPointerLock !== 'function') {
                document.body.requestPointerLock = function() {
                    console.log('Body pointer lock requested but not available - using no-op polyfill');
                    return false;
                };
            }
            
            if (document.documentElement && typeof document.documentElement.requestPointerLock !== 'function') {
                document.documentElement.requestPointerLock = function() {
                    console.log('Document pointer lock requested but not available - using no-op polyfill');
                    return false;
                };
            }
            
            // Also polyfill the exit function
            if (typeof document.exitPointerLock !== 'function') {
                document.exitPointerLock = function() {
                    console.log('Exit pointer lock requested but not available - using no-op polyfill');
                    return false;
                };
            }
            
            // Quick fix for keyboard focus issues on mobile
            document.addEventListener('click', function(e) {
                // Prevent default only for clicks on canvas elements to avoid issues
                if (e.target && e.target.tagName && e.target.tagName.toLowerCase() === 'canvas') {
                    console.log('Canvas clicked - ensuring mobile interaction works');
                    e.preventDefault();
                    
                    // Dispatch mobile interaction event
                    var event = new CustomEvent('jackalopesCanvasInteraction');
                    window.dispatchEvent(event);
                }
            }, { passive: false });
            
            // Add a special flag that we can check for in the game code
            window.jackalopesFixMobileInput = function() {
                console.log('Mobile input fix applied');
                // Any additional mobile-specific fixes can go here
                return true;
            };
        }
        
        // Start the initialization sequence
        setTimeout(function() {
            checkJackalopesScriptsLoaded();
        }, 200);
    </script>
    <?php
    
    // Return the buffered content
    return ob_get_clean();
} 