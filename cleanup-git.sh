#!/bin/bash

echo "==============================================="
echo "Git Repository Cleanup Script for Jackalopes WP"
echo "==============================================="

# Check if git is available
if ! command -v git &> /dev/null; then
    echo "Error: git is not installed or not in PATH"
    exit 1
fi

# Step 1: Make sure we're in the right directory
CURRENT_DIR=$(basename "$PWD")
if [ "$CURRENT_DIR" != "game" ]; then
    echo "Error: This script should be run from the 'game' directory"
    exit 1
fi

# Step 2: Untrack node_modules directory
echo "Removing node_modules from git tracking (files will remain on disk)..."
git rm -r --cached node_modules

# Step 3: Untrack map files
echo "Removing source maps from git tracking..."
git rm -r --cached "*.map"

# Step 4: Apply new .gitignore
echo "Updating git index based on new .gitignore..."
git add .gitignore

# Step 5: Show what's still modified
echo "==============================================="
echo "Files still modified after cleanup:"
git status

echo "==============================================="
echo "Cleanup complete!"
echo "You can now commit the changes with:"
echo "git add -A"
echo "git commit -m 'Clean up repository and update .gitignore'"
echo "===============================================" 