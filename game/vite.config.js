import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve, join, dirname } from 'path';
import { copyFileSync, existsSync, mkdirSync } from 'fs';
import { glob } from 'glob';

// Helper function to safely create directory
function ensureDirectoryExists(dirPath) {
  if (!dirPath || dirPath.trim() === '') {
    console.warn('Empty directory path provided to ensureDirectoryExists');
    return false;
  }
  
  try {
    if (!existsSync(dirPath)) {
      mkdirSync(dirPath, { recursive: true });
    }
    return true;
  } catch (err) {
    console.error(`Error creating directory ${dirPath}: ${err.message}`);
    return false;
  }
}

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    react(),
    {
      name: 'copy-assets',
      closeBundle: async () => {
        console.log('Copying assets to dist directory...');
        
        // Define source and destination directories
        const sourceDir = resolve(__dirname, 'src/assets');
        const destDir = resolve(__dirname, 'dist/assets');
        
        // Create destination directory if it doesn't exist
        ensureDirectoryExists(destDir);
        
        try {
          // Copy model files and directories
          const assetFiles = glob.sync(`${sourceDir}/**/*.*`);
          
          for (const file of assetFiles) {
            // Get the relative path from the sourceDir
            const relativePath = file.replace(sourceDir, '');
            
            // Create the full destination path
            const destPath = join(destDir, relativePath);
            
            // Ensure the destination directory exists
            const destDirName = dirname(destPath);
            if (destDirName && destDirName !== '') {
              ensureDirectoryExists(destDirName);
            }
            
            try {
              copyFileSync(file, destPath);
              console.log(`Copied: ${relativePath}`);
            } catch (err) {
              console.error(`Error copying ${relativePath}: ${err.message}`);
            }
          }
          
          console.log('Asset copying complete!');
        } catch (err) {
          console.error(`Error during asset copy: ${err.message}`);
        }
      }
    },
    // Custom plugin to ensure global variable is exported properly
    {
      name: 'expose-game-function',
      generateBundle(options, bundle) {
        // Adding a banner to wrap the entire bundle in an IIFE
        // and explicitly expose the initialization function
        const mainBundle = bundle['assets/main.js'];
        if (mainBundle) {
          // Add a banner to ensure the initJackalopesGame function is properly exposed
          mainBundle.code = `
// Jackalopes WordPress Integration
// Ensure the initialization function is properly exposed to the window object
(function() {
  // Make sure React is defined in the global scope
  if (typeof React !== 'undefined' && !window.React) {
    window.React = React;
  }
  if (typeof ReactDOM !== 'undefined' && !window.ReactDOM) {
    window.ReactDOM = ReactDOM;
  }

  ${mainBundle.code}
  // Explicitly make initJackalopesGame available on window
  if (typeof initJackalopesGame === 'function' && !window.initJackalopesGame) {
    console.log('Exposing initJackalopesGame to window object');
    window.initJackalopesGame = initJackalopesGame;
  }
})();
`;
        }
        
        // Ensure CSS has the right MIME type
        const cssBundle = bundle['assets/main.css'];
        if (cssBundle) {
          // Add a CSS MIME type comment
          cssBundle.source = `/* MIME Type: text/css */\n${cssBundle.source}`;
        }
      }
    }
  ],
  optimizeDeps: {
    include: ['react', 'react-dom', 'react-dom/client'],
    exclude: ['lucide-react'],
  },
  base: './',
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    minify: true,
    sourcemap: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'src/main.tsx'),
      },
      output: {
        entryFileNames: 'assets/[name].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name].[ext]',
        format: 'iife',
        // Add proper globals for React
        globals: {
          'react': 'React',
          'react-dom': 'ReactDOM',
          'react-dom/client': 'ReactDOM'
        }
      },
      // Include React in the bundle to avoid version conflicts
      external: []
    },
    manifest: true,
    assetsInlineLimit: 0,
  },
  // Ensure CSS MIME type is set correctly
  css: {
    postcss: {
      plugins: [
        {
          postcssPlugin: 'internal:charset-removal',
          AtRule: {
            charset: (atRule) => {
              if (atRule.name === 'charset') {
                atRule.remove();
              }
            }
          }
        }
      ]
    }
  },
  publicDir: 'public',
  define: {
    'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV || 'production'),
    'process.env.VITE_APP_TITLE': JSON.stringify('Jackalopes'),
    'process.env.VITE_DEBUG': JSON.stringify(process.env.VITE_DEBUG || 'false'),
    // Explicitly define React versions to avoid conflicts
    '__REACT_VERSION__': JSON.stringify('18.3.1'),
    '__REACT_DOM_VERSION__': JSON.stringify('18.3.1')
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
      'react': resolve(__dirname, 'node_modules/react'),
      'react-dom': resolve(__dirname, 'node_modules/react-dom')
    },
  },
  server: {
    hmr: {
      protocol: 'ws',
      host: 'localhost',
    },
    port: 3000,
    open: true,
    cors: true,
  },
}); 