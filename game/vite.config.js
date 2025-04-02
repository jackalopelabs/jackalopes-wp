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
    }
  ],
  optimizeDeps: {
    exclude: ['lucide-react'],
  },
  base: './',
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'src/main.tsx'),
        test: 'test.html',
      },
      output: {
        entryFileNames: 'assets/[name].js',
        chunkFileNames: 'assets/[name].[hash].js',
        assetFileNames: ({ name }) => {
          if (/\.(gltf|glb|fbx|obj|mtl|hdr|bin)$/.test(name ?? '')) {
            return 'assets/[name].[ext]';
          }
          return 'assets/[name].[ext]';
        },
      },
    },
    manifest: true,
    sourcemap: true,
    assetsInlineLimit: 0,
  },
  define: {
    'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV || 'development'),
    'process.env.WORDPRESS_PLUGIN': JSON.stringify(true),
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    hmr: {
      protocol: 'ws',
      host: 'localhost',
    },
    port: 3000,
    https: true,
  },
}); 