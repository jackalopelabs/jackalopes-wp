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
        <div class="jackalopes-loading">
            <div class="jackalopes-loading-spinner"></div>
            <div class="jackalopes-loading-message">Loading Jackalopes...</div>
        </div>
        
        <!-- Start Game button - initially visible -->
        <div class="start-game-overlay">
            <button class="start-game-button">Start Game</button>
        </div>
        
        <!-- Fullscreen button -->
        <button class="fullscreen-button fixed-ui fixed-top-right">
            Fullscreen
        </button>
        
        <style>
            /* Inline critical CSS to ensure proper positioning */
            #<?php echo esc_attr($game_id); ?> {
                position: relative !important;
                overflow: hidden !important;
                background-color: #242424;
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
            #<?php echo esc_attr($game_id); ?> .fullscreen-button {
                background-color: rgba(0, 0, 0, 0.5);
                color: white;
                border: 1px solid rgba(255, 255, 255, 0.5);
                border-radius: 4px;
                padding: 8px 12px;
                cursor: pointer;
                font-size: 14px;
                margin: 10px;
                z-index: 100;
                transition: background-color 0.2s;
            }
            #<?php echo esc_attr($game_id); ?> .fullscreen-button:hover {
                background-color: rgba(0, 0, 0, 0.8);
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
            #<?php echo esc_attr($game_id); ?> .jackalopes-loading {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                background-color: #242424;
                z-index: 50;
                color: white;
            }
            #<?php echo esc_attr($game_id); ?> .jackalopes-loading-spinner {
                border: 5px solid rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                border-top: 5px solid #ffffff;
                width: 50px;
                height: 50px;
                animation: jackalopes-spin 1s linear infinite;
                margin-bottom: 20px;
            }
            @keyframes jackalopes-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
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
                
                // Add fullscreen button functionality
                var container = document.getElementById('<?php echo esc_js($game_id); ?>');
                var fullscreenBtn = container.querySelector('.fullscreen-button');
                
                if (fullscreenBtn) {
                    fullscreenBtn.addEventListener('click', function() {
                        if (!document.fullscreenElement) {
                            if (container.requestFullscreen) {
                                container.requestFullscreen().catch(err => {
                                    console.error(`Error attempting to enable fullscreen: ${err.message}`);
                                });
                            } else if (container.mozRequestFullScreen) { /* Firefox */
                                container.mozRequestFullScreen();
                            } else if (container.webkitRequestFullscreen) { /* Chrome, Safari & Opera */
                                container.webkitRequestFullscreen();
                            } else if (container.msRequestFullscreen) { /* IE/Edge */
                                container.msRequestFullscreen();
                            }
                        } else {
                            if (document.exitFullscreen) {
                                document.exitFullscreen();
                            } else if (document.mozCancelFullScreen) {
                                document.mozCancelFullScreen();
                            } else if (document.webkitExitFullscreen) {
                                document.webkitExitFullscreen();
                            } else if (document.msExitFullscreen) {
                                document.msExitFullscreen();
                            }
                        }
                    });
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
                console.error('Jackalopes game initialization function not found. Making fallback scene.');
                
                // Make a fallback scene with Three.js if the game doesn't load
                createFallbackScene('<?php echo esc_js($game_id); ?>');
            }
            
            // Add a failsafe to hide loading screen after 5 seconds
            setTimeout(function() {
                var loadingScreen = document.querySelector('#<?php echo esc_js($game_id); ?> .jackalopes-loading');
                if (loadingScreen) {
                    loadingScreen.style.display = 'none';
                }
                
                // After loading screen is hidden, check if canvas exists
                var canvas = document.querySelector('#<?php echo esc_js($game_id); ?> canvas');
                if (!canvas) {
                    console.error('No canvas found after 5 seconds. Creating fallback display.');
                    createFallbackScene('<?php echo esc_js($game_id); ?>');
                } else {
                    // Force canvas to be visible
                    canvas.style.display = 'block';
                    canvas.style.width = '100%';
                    canvas.style.height = '100%';
                }
            }, 5000);
        }, 100);
        
        // Helper function to create a fallback scene if the React app fails
        function createFallbackScene(containerId) {
            var container = document.getElementById(containerId);
            if (!container) return;
            
            // Check if we already created a fallback
            if (container.querySelector('.fallback-scene')) return;
            
            // Create a notice
            var notice = document.createElement('div');
            notice.className = 'jackalopes-wordpress-notice';
            notice.style.position = 'absolute';
            notice.style.top = '10px';
            notice.style.left = '10px';
            notice.style.color = 'white';
            notice.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
            notice.style.padding = '10px';
            notice.style.borderRadius = '5px';
            notice.style.zIndex = '100';
            notice.textContent = 'Jackalopes Fallback Mode - Game Engine Not Loaded';
            container.appendChild(notice);
            
            // Check if Three.js is already loaded
            if (typeof THREE === 'undefined') {
                // Load Three.js from CDN
                var script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';
                script.onload = function() {
                    renderFallbackScene(container);
                };
                document.head.appendChild(script);
            } else {
                renderFallbackScene(container);
            }
        }
        
        // Render a basic Three.js scene
        function renderFallbackScene(container) {
            // Create scene, camera, renderer
            var scene = new THREE.Scene();
            var camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
            var renderer = new THREE.WebGLRenderer({ antialias: true });
            
            // Set renderer size and append to container
            renderer.setSize(container.clientWidth, container.clientHeight);
            renderer.setClearColor(0x242424);
            renderer.shadowMap.enabled = true;
            
            // Add the fallback-scene class to the canvas
            renderer.domElement.classList.add('fallback-scene');
            container.appendChild(renderer.domElement);
            
            // Lights
            var ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            scene.add(ambientLight);
            
            var pointLight = new THREE.PointLight(0xffffff, 1);
            pointLight.position.set(5, 5, 5);
            pointLight.castShadow = true;
            scene.add(pointLight);
            
            // Create a cube
            var geometry = new THREE.BoxGeometry(1, 1, 1);
            var material = new THREE.MeshStandardMaterial({ color: 0x00ff00 });
            var cube = new THREE.Mesh(geometry, material);
            cube.castShadow = true;
            scene.add(cube);
            
            // Create a ground plane
            var planeGeometry = new THREE.PlaneGeometry(10, 10);
            var planeMaterial = new THREE.MeshStandardMaterial({ color: 0x555555 });
            var plane = new THREE.Mesh(planeGeometry, planeMaterial);
            plane.rotation.x = -Math.PI / 2;
            plane.position.y = -1;
            plane.receiveShadow = true;
            scene.add(plane);
            
            // Add grid helper
            var gridHelper = new THREE.GridHelper(10, 10);
            scene.add(gridHelper);
            
            // Position camera
            camera.position.z = 5;
            camera.position.y = 1;
            
            // Handle window resize
            window.addEventListener('resize', function() {
                camera.aspect = container.clientWidth / container.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(container.clientWidth, container.clientHeight);
            });
            
            // Animation loop
            function animate() {
                requestAnimationFrame(animate);
                
                // Rotate cube
                cube.rotation.x += 0.01;
                cube.rotation.y += 0.01;
                
                renderer.render(scene, camera);
            }
            
            animate();
            console.log('Fallback ThreeJS scene created successfully');
        }
        
        // Helper function to ensure UI elements stay contained
        function ensureContainedUI(containerId) {
            var container = document.getElementById(containerId);
            if (!container) return;
            
            // Check for UI elements every second and move them into the container if needed
            setInterval(function() {
                var fixedElements = document.querySelectorAll('.fps-stats, .virtual-gamepad, .game-controls, .jackalopes-ui, .jackalopes-status, .jackalopes-help, .loading-screen, .jackalopes-wordpress-notice, .jackalopes-audio-button-container, .jackalopes-audio-wrapper, .jackalopes-audio-mobile-wrapper');
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