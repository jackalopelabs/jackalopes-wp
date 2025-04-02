#!/bin/bash

# WordPress Deployment Helper Script for Jackalopes
# This script helps deploy the game to a WordPress site

# Check if a destination directory was provided
if [ -z "$1" ]; then
  echo "Please provide the path to your WordPress plugin directory."
  echo "Usage: ./deploy-to-wordpress.sh /path/to/wordpress/wp-content/plugins/jackalopes-wp"
  exit 1
fi

DEST_DIR="$1"

# Check if the destination directory exists
if [ ! -d "$DEST_DIR" ]; then
  echo "Error: Destination directory doesn't exist."
  echo "Please make sure the path is correct and the directory exists."
  exit 1
fi

# First, run the build-and-fix script to ensure we have a clean build
echo "Building and fixing the game..."
./build-and-fix.sh

# Check if build was successful
if [ ! -d "game/dist" ]; then
  echo "Error: Build failed, dist directory not found."
  exit 1
fi

# Create required directories in the WordPress plugin
echo "Creating required directories in WordPress plugin..."
mkdir -p "$DEST_DIR/assets"

# Copy the dist directory to the WordPress plugin
echo "Copying built files to WordPress plugin..."
cp -r game/dist/* "$DEST_DIR/"

# Create a special WordPress loader script to prevent CORS errors
echo "Creating WordPress loader script..."
cat > "$DEST_DIR/jackalopes-loader.js" << EOL
/**
 * Jackalopes WordPress Loader
 * This script handles loading the game in WordPress while preventing CORS errors
 */
(function() {
  // Block any references to development servers
  function blockDevReferences() {
    // Create a list of blocked patterns
    const blockedPatterns = [
      'localhost:5173',
      'localhost:3000',
      '[::1]:5173',
      '[::1]:3000',
      '@vite/client'
    ];
    
    // Intercept element creation to block dev references
    const originalCreateElement = document.createElement.bind(document);
    document.createElement = function(tagName) {
      const element = originalCreateElement(tagName);
      
      if (tagName.toLowerCase() === 'script' || tagName.toLowerCase() === 'link' || tagName.toLowerCase() === 'img') {
        const originalSetAttribute = element.setAttribute.bind(element);
        element.setAttribute = function(name, value) {
          if ((name === 'src' || name === 'href') && typeof value === 'string') {
            // Block requests to development servers
            const isBlocked = blockedPatterns.some(pattern => value.includes(pattern));
            if (isBlocked) {
              console.warn('Blocked development resource:', value);
              return;
            }
            
            // Fix mixed content by upgrading HTTP to HTTPS if needed
            if (window.location.protocol === 'https:' && value.startsWith('http:')) {
              // Only upgrade if not pointing to localhost or IP
              if (!value.includes('localhost:') && !value.includes('[::1]:')) {
                value = value.replace('http:', 'https:');
              }
            }
          }
          originalSetAttribute(name, value);
        };
      }
      
      return element;
    };
    
    // Handle errors for development resources to prevent blocking
    window.addEventListener('error', function(event) {
      if (event.target && (event.target.tagName === 'SCRIPT' || event.target.tagName === 'LINK' || event.target.tagName === 'IMG')) {
        const src = event.target.src || event.target.href || '';
        const isDevResource = blockedPatterns.some(pattern => src.includes(pattern));
        if (isDevResource) {
          console.warn('Prevented error for dev resource:', src);
          event.preventDefault();
          event.stopPropagation();
        }
      }
    }, true);
  }
  
  // Initialize the protection
  blockDevReferences();
  
  // Add a class to the WordPress body for detection
  document.body.classList.add('wordpress');
  
  // Make the plugin URL available globally
  window.jackalopesPluginUrl = document.currentScript.src.substring(0, document.currentScript.src.lastIndexOf('/') + 1);
  
  console.log('Jackalopes WordPress loader initialized');
})();
EOL

echo "Creating WordPress loader script reference in the plugin..."
cat > "$DEST_DIR/jackalopes-helper.php" << EOL
<?php
/**
 * Jackalopes Helper Functions
 */

/**
 * Enqueue the Jackalopes loader script
 */
function jackalopes_enqueue_loader() {
    wp_enqueue_script(
        'jackalopes-loader',
        plugins_url('jackalopes-loader.js', __FILE__),
        array(),
        '1.0.0',
        false
    );
}
add_action('wp_enqueue_scripts', 'jackalopes_enqueue_loader', 5);

/**
 * Add CORS headers for Jackalopes assets
 */
function jackalopes_add_cors_headers() {
    if (strpos($_SERVER['REQUEST_URI'], 'wp-content/plugins/jackalopes-wp/') !== false) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
    }
}
add_action('init', 'jackalopes_add_cors_headers');
EOL

echo "Done! The game has been deployed to $DEST_DIR"
echo
echo "Next steps:"
echo "1. Include 'jackalopes-helper.php' in your main plugin file"
echo "2. Make sure the shortcode is properly registered"
echo "3. Test the game on your WordPress site" 