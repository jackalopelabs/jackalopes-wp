# Jackalopes-WP Plugin Patches

This directory contains patches to fix the issue with duplicate Three.js instances being loaded in WordPress.

## Problem

The error message `WARNING: Multiple instances of Three.js being imported` appears because:

1. The Jackalopes plugin loads Three.js as part of its bundled JavaScript
2. The WordPress theme is also loading Three.js or initializing the game in a way that loads a second instance

## How to Apply the Patches

### Option 1: Apply Patches Manually

1. Replace the default `includes/shortcodes.php` with `patches/shortcodes.php.patch.txt`
2. Replace the default `includes/assets.php` with `patches/assets.php.patch.txt`

```bash
# From the plugin directory
cp includes/patches/shortcodes.php.patch.txt includes/shortcodes.php
cp includes/patches/assets.php.patch.txt includes/assets.php
```

### Option 2: Use the "disable_threejs" attribute in the shortcode

Add the `disable_threejs="true"` attribute to the shortcode:

```php
[jackalopes width="800px" height="500px" disable_threejs="true"]
```

This will prevent the plugin from initializing a second instance of Three.js if another is already present.

## Changes Made by These Patches

1. **Shortcode Patch**: 
   - Adds a new `disable_threejs` attribute to the shortcode
   - Adds detection for existing Three.js instances
   - Passes the loading preference to the game initialization

2. **Assets Patch**:
   - Adds conditional Three.js loading functionality
   - Detects if Three.js is already loaded before importing another instance
   - Provides debugging output to help diagnose Three.js loading issues

## For Theme Developers

If you're providing your own Three.js implementation in your theme, update your theme's JavaScript to:

1. Check if Three.js is already loaded before initializing your own instance
2. Use the `disable_threejs="true"` attribute in all Jackalopes shortcodes
3. Use the global `window.jackalopeThreeJSStatus` object to check Three.js status

```javascript
// Example check in your theme JavaScript
if (window.jackalopeThreeJSStatus && window.jackalopeThreeJSStatus.instanceCount > 0) {
  console.log('Using existing Three.js instance');
} else {
  console.log('Loading our own Three.js instance');
  // Load Three.js here
}
``` 