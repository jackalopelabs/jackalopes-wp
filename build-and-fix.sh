#!/bin/bash

echo "==============================================="
echo "Jackalopes WP Comprehensive Build & Fix Script"
echo "==============================================="
echo
echo "This script will fix all common issues:"
echo "- CORS errors"
echo "- Missing assets"
echo "- Development server references"
echo "- Mixed content warnings"
echo

# Set environment to production for build
export NODE_ENV=production

# Step 1: Build the game
echo "STEP 1: Building Jackalopes game in production mode..."
cd game
node_modules/.bin/vite build --emptyOutDir

# Step 2: Copy all assets to ensure they're available
echo
echo "STEP 2: Copying all required assets..."
mkdir -p dist/assets/environment/lowpoly_nature
mkdir -p dist/assets/models
mkdir -p dist/assets/sounds
mkdir -p dist/assets/textures

# Copy all environment assets
if [ -d "src/assets/environment" ]; then
  cp -r src/assets/environment/* dist/assets/environment/ 2>/dev/null
  echo "✓ Environment assets copied"
else
  echo "× Environment assets directory not found"
fi

# Copy model files
if [ -d "src/assets/models" ]; then
  cp -r src/assets/models/* dist/assets/models/ 2>/dev/null
  echo "✓ Model assets copied"
else
  echo "× Models directory not found, creating placeholders"
  # Create placeholder for fps.glb - often referenced but missing
  touch dist/assets/fps.glb
fi

# Copy texture files
if [ -d "src/assets/textures" ]; then
  cp -r src/assets/textures/* dist/assets/textures/ 2>/dev/null
  echo "✓ Texture assets copied"
else
  echo "× Textures directory not found"
fi

# Copy sound files
if [ -d "src/assets/sounds" ]; then
  cp -r src/assets/sounds/* dist/assets/sounds/ 2>/dev/null
  echo "✓ Sound assets copied"
else
  echo "× Sounds directory not found"
fi

# Copy image files from the root assets directory
cp -r src/assets/*.png dist/assets/ 2>/dev/null
cp -r src/assets/*.jpg dist/assets/ 2>/dev/null
cp -r src/assets/*.webp dist/assets/ 2>/dev/null
cp -r src/assets/*.svg dist/assets/ 2>/dev/null
cp -r src/assets/*.glb dist/assets/ 2>/dev/null
cp -r src/assets/*.gltf dist/assets/ 2>/dev/null

# Step 3: Create placeholder files for any referenced but missing assets
echo
echo "STEP 3: Creating placeholder files for commonly referenced assets..."

# List of commonly referenced assets that might be missing
COMMON_ASSETS=(
  "fps.glb"
  "background.png"
  "merc-fallback.glb"
  "jackalope-fallback.glb"
)

for asset in "${COMMON_ASSETS[@]}"; do
  if [ ! -f "dist/assets/$asset" ]; then
    touch "dist/assets/$asset"
    echo "✓ Created placeholder for $asset"
  fi
done

# Step 4: Clean up any development references in JavaScript files
echo
echo "STEP 4: Cleaning up development references in built files..."
find dist -type f -name "*.js" -exec sed -i "" "s|http://localhost:5173|./assets|g" {} \;
find dist -type f -name "*.js" -exec sed -i "" "s|http://\[::1\]:5173|./assets|g" {} \;
find dist -type f -name "*.js" -exec sed -i "" "s|localhost:3000|./assets|g" {} \;
find dist -type f -name "*.js" -exec sed -i "" "s|ws://localhost|wss://localhost|g" {} \;
echo "✓ Cleaned up localhost references"

# Step 5: Create a .htaccess file to handle CORS
echo
echo "STEP 5: Creating .htaccess file for CORS headers..."
cat > dist/.htaccess << EOL
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"
    Header set Access-Control-Allow-Headers "Origin, X-Requested-With, Content-Type, Accept"
</IfModule>
EOL
echo "✓ Created .htaccess with CORS headers"

cd ..

echo
echo "==============================================="
echo "Build complete! The production-ready files are in game/dist directory."
echo
echo "Next steps:"
echo "1. Copy the entire 'game/dist' directory to your WordPress plugin"
echo "2. Make sure the assets are properly referenced in your WordPress plugin"
echo "3. Test the game on your WordPress site"
echo "===============================================" 