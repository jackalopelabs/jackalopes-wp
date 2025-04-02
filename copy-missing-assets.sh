#!/bin/bash

# Create directory structure
mkdir -p game/dist/assets/environment/lowpoly_nature
mkdir -p game/dist/assets

# Copy environment assets
cp -r game/src/assets/environment/* game/dist/assets/environment/
cp -r game/src/assets/fps.glb game/dist/assets/

# Other assets
cp -r game/src/assets/models/* game/dist/assets/ 2>/dev/null || echo "No models to copy"
cp -r game/src/assets/textures/* game/dist/assets/ 2>/dev/null || echo "No textures to copy"
cp -r game/src/assets/*.png game/dist/assets/ 2>/dev/null || echo "No PNG files to copy"
cp -r game/src/assets/*.jpg game/dist/assets/ 2>/dev/null || echo "No JPG files to copy"

echo "Assets copied successfully!" 