# Jackalopes WordPress Plugin

A WordPress plugin that integrates the Jackalopes 3D first-person shooter game into any WordPress site using a simple shortcode.

## Features

- Easy integration into any WordPress site using a shortcode
- Seamless integration with the jackalopes-server for multiplayer functionality
- Responsive design that works on mobile and desktop
- Configuration options through shortcode attributes
- Performance optimizations for WordPress environments

## Installation

### Method 1: Build from Source

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/jackalopes.git
   cd jackalopes/jackalopes-wp
   ```

2. Install dependencies:
   ```bash
   cd game
   npm install
   cd ..
   ```

3. Build and deploy:
   ```bash
   # Option 1: Build only
   ./build.sh
   
   # Option 2: Build and copy to WordPress plugins directory
   ./build.sh /path/to/wordpress/wp-content/plugins/jackalopes-wp
   ```

4. Activate the plugin in your WordPress admin panel.

5. Add the shortcode to any page or post:
   ```
   [jackalopes]
   ```

### Method 2: Manual Installation

1. Download the latest release zip file.
2. In your WordPress admin, go to Plugins > Add New > Upload Plugin.
3. Upload the zip file and activate the plugin.
4. Add the shortcode `[jackalopes]` to any page or post.

## Usage

### Basic Usage

Simply add the shortcode to any page or post:

```
[jackalopes]
```

### Advanced Usage

The shortcode accepts optional attributes to customize the game:

```
[jackalopes width="800px" height="600px" fullscreen="true" server="ws://your-server.com/websocket/"]
```

#### Available Attributes

| Attribute | Description | Default |
|-----------|-------------|---------|
| width | Width of the game container | 100% |
| height | Height of the game container | 600px |
| fullscreen | Enable fullscreen mode | false |
| server | WebSocket server URL | Auto-detected |

## Troubleshooting

### Missing Asset Files

If you encounter 404 errors for assets (like models, textures, etc.), you need to ensure all assets are correctly copied to the plugin directory:

1. Run the asset fixer script:
   ```bash
   ./fix-assets.sh
   ```

2. Make sure the original game's public directory structure is accessible to copy assets from:
   ```
   ../public/
   ```

### WebSocket Connection Issues

If you're having issues with the multiplayer functionality:

1. Ensure the jackalopes-server plugin is installed and activated.
2. Verify the WebSocket server is running.
3. Check for any firewall or proxy configuration that might block WebSocket connections.

### CORS & Loading Issues

If you encounter issues with the game loading, black screens, CORS errors, or mixed content warnings:

1. Build the game using the production build script which handles these issues automatically:
   ```bash
   ./build-prod.sh
   ```

2. Check your browser console for specific errors:
   - **Mixed Content Warnings:** This happens when loading HTTP content on an HTTPS site
   - **CORS Errors:** Appears when trying to load resources from different origins
   - **Loading Screen Hanging:** The loading screen might get stuck

3. Common solutions:
   - Use relative paths for all resources rather than absolute URLs
   - Ensure all scripts and assets are loaded from the same domain
   - Use the production build script which fixes most common issues
   - Make sure your server is configured to send proper CORS headers

## Development

### Local Testing

You can test the plugin locally using the included development server:

```bash
cd jackalopes-wp
php -S localhost:8000 serve.php
```

Then open your browser to http://localhost:8000

### Building for Development

During development, you can use the Vite development server for faster iteration:

```bash
cd game
npm run dev
```

3. For WordPress testing, build the game:
   ```bash
   npm run build
   ```

### Build Scripts

Several build scripts are available to help with development and deployment:

#### Production Build Script (`build-prod.sh`)

This script builds the game in production mode with optimizations to prevent common issues like CORS errors and mixed content warnings:

```bash
./build-prod.sh
```

Key features:
- Sets NODE_ENV to production
- Skips TypeScript type checking to allow builds even with TypeScript errors
- Cleans up any development-only resources that might cause issues on production sites
- Fixes references to localhost resources that would cause CORS errors
- Creates a highly optimized production build in the `game/dist` directory

**Problem solving:**
- **Mixed Content Warnings:** Fixes HTTP vs HTTPS content issues by ensuring all resource paths use relative references
- **CORS Errors:** Removes any hardcoded references to localhost or development servers that would be blocked by CORS
- **Loading Issues:** Ensures the game loads immediately without showing loading screens that might hang
- **Build Errors:** Bypasses TypeScript errors to produce working builds even when type definitions aren't perfect

#### Standard Build Script (`build.sh`)

This script provides standard build functionality:

```bash
# Simple build
./build.sh

# Build and deploy to WordPress plugins directory
./build.sh /path/to/wordpress/wp-content/plugins/jackalopes-wp
```

#### Asset Management Script (`copy-assets.sh`)

This script handles copying and organizing assets for the game:

```bash
./copy-assets.sh
```

It automatically runs after builds to:
- Copy 3D models, textures, and other assets from source to distribution
- Ensure assets are properly referenced and available in the built game

#### When to Use Each Script

- Use `npm run dev` during active development for hot reloading
- Use `build-prod.sh` when preparing for production deployment
- Use `build.sh` for routine builds or when you want to immediately deploy to a WordPress directory
- Use `copy-assets.sh` manually when troubleshooting missing assets

### Game Controls

- Press `F3` to toggle FPS and debug stats
- Press `C` to switch between first-person and third-person views
- Use WASD to move (when fully implemented)
- Mouse to look (when fully implemented)
- Click to shoot (when fully implemented)

### Console Debugging

The following debug functions are available in the browser console:

```javascript
// Set debug level (0-3)
window.__setDebugLevel(2);

// Set graphics quality
window.__setGraphicsQuality('high'); // 'auto', 'high', 'medium', 'low'

// Toggle network logs
window.__toggleNetworkLogs(true);
```

## Plugin Architecture

```
jackalopes-wp/
├── admin/                  # Admin UI components
├── includes/               # WordPress integration
│   ├── shortcodes.php      # Shortcode registration
│   └── assets.php          # Asset loading
├── src/                    # PHP Classes
│   ├── Plugin.php          # Main plugin class
│   └── Game.php            # Game integration class
├── game/                   # The React/ThreeJS app
│   ├── src/                # Game source files
│   │   ├── App.tsx         # Main game component
│   │   ├── components/     # Game components
│   │   ├── hooks/          # React hooks
│   │   ├── types/          # Type definitions
│   │   └── utils/          # Utility functions
│   ├── public/             # Static assets
│   └── dist/               # Built game files
└── vendor/                 # Composer dependencies
```

## Multiplayer Functionality

For multiplayer functionality, you'll need to install the [Jackalopes Server](https://github.com/yourusername/jackalopes-server) plugin. Once installed:

1. Navigate to Jackalopes Server in the WordPress admin menu
2. Start the WebSocket server
3. The Jackalopes game will automatically connect to the server

### Setting Up Multiplayer for Testing

1. Start the server:
   ```bash
   cd jackalopes-server
   node server.js
   ```

2. Open multiple browser tabs to test multiplayer
3. Each tab will be assigned a different player type (jackalope/merc)

## Implementation Notes

This plugin uses a modern architecture with:

- React and ThreeJS for the game engine
- WebSockets for real-time multiplayer
- Composer for PHP dependency management
- Vite for frontend builds

The game is implemented as a standalone React application that is integrated into WordPress via a shortcode system and asset loading utilities.

## Credits

- Built with [React Three Fiber](https://github.com/pmndrs/react-three-fiber)
- Physics by [Rapier](https://github.com/pmndrs/react-three-rapier)
- 3D rendering with [Three.js](https://threejs.org/)
- Development by [Mason Lawlor](https://jackalope.io)

## Asset Handling

The plugin includes an automated asset handling system that addresses potential issues with 3D models and textures during the build process:

### Automatic Asset Copying

After each build, the `copy-assets.sh` script runs automatically to:

1. Copy 3D models, textures, and other assets from source to distribution
2. Look for assets in multiple locations (plugin src, original game src, original game public)
3. Create placeholder files for any missing assets to prevent 404 errors
4. Set up fallback paths for specific problematic assets

### Manual Asset Fixing

If you encounter missing assets in your WordPress deployment, you can run the asset fixer script manually:

```bash
cd /path/to/jackalopes
./jackalopes-wp/copy-assets.sh
```

### Asset Troubleshooting

If you see 404 errors for specific assets in the browser console:

1. First check that the asset exists in `jackalopes-wp/game/src/assets/`
2. If not, copy it from the original game's `public/` or `src/assets/` directory
3. Run the copy-assets.sh script manually
4. Rebuild the plugin with `npm run build --prefix jackalopes-wp/game`

The script creates placeholder files for missing assets to prevent JavaScript errors, but for optimal game experience, you should provide the actual model files.

## UI Containment System

When integrating the Jackalopes game into a WordPress site, UI elements were previously displayed outside the game container, appearing in the corners of the entire page. This has been fixed with our UI containment system that:

1. **Contains UI Elements:** All game UI elements (buttons, controls, stats, etc.) are now properly contained within the game's container div.

2. **Handles Fullscreen Mode:** When entering fullscreen mode, the UI elements remain visible and properly positioned within the fullscreen container.

3. **Preserves Positioning:** UI elements maintain their relative positioning (top-left, top-right, etc.) but stay confined to the game container.

### Usage

The UI containment system is automatically enabled when using the `[jackalopes]` shortcode. No additional configuration is needed.

You can customize some aspects with shortcode attributes:

```
[jackalopes width="800px" height="600px" fullscreen="true" disable_ui="false"]
```

### How It Works

The system uses a combination of:

1. **CSS Positioning:** Elements use relative/absolute positioning instead of fixed positioning.
2. **DOM Observation:** A MutationObserver watches for new UI elements and ensures they stay in the container.
3. **Fullscreen Management:** Special handling for fullscreen mode to preserve UI visibility.

If you're extending the game with custom UI elements, add the class `game-ui-element` to ensure they're properly contained.

### Troubleshooting

If UI elements still appear outside the container:

1. Make sure your theme doesn't override the positioning with higher-specificity CSS.
2. Check for any custom JavaScript that might be moving elements outside the container.
3. Verify that the game container has `position: relative` and `overflow: hidden`. 