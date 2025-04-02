// Asset verification script
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

// Get __dirname equivalent in ES modules
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const DIST_DIR = path.join(__dirname, 'dist');
const ASSETS_DIR = path.join(DIST_DIR, 'assets');

// Required directories that should exist
const REQUIRED_DIRS = [
  'assets',
  'assets/models',
  'assets/environment',
  'assets/environment/lowpoly_nature', 
  'assets/characters',
  'assets/characters/animations',
  'assets/textures'
];

// Critical assets that should exist
const CRITICAL_ASSETS = [
  'assets/models/merc-fallback.glb',
  'assets/models/jackalope-fallback.glb'
];

console.log('Verifying assets directory structure...');

// Verify required directories exist
let allDirectoriesExist = true;
for (const dir of REQUIRED_DIRS) {
  const fullPath = path.join(DIST_DIR, dir);
  
  if (!fs.existsSync(fullPath)) {
    console.error(`❌ Missing required directory: ${dir}`);
    allDirectoriesExist = false;
    
    // Create the missing directory
    try {
      fs.mkdirSync(fullPath, { recursive: true });
      console.log(`✅ Created missing directory: ${dir}`);
    } catch (err) {
      console.error(`   Error creating directory: ${err.message}`);
    }
  } else {
    console.log(`✅ Directory exists: ${dir}`);
  }
}

// Verify critical assets exist
let allCriticalAssetsExist = true;
for (const asset of CRITICAL_ASSETS) {
  const fullPath = path.join(DIST_DIR, asset);
  
  if (!fs.existsSync(fullPath)) {
    console.error(`❌ Missing critical asset: ${asset}`);
    allCriticalAssetsExist = false;
    
    // Create a placeholder file with a warning
    try {
      // Ensure parent directory exists
      const parentDir = path.dirname(fullPath);
      if (!fs.existsSync(parentDir)) {
        fs.mkdirSync(parentDir, { recursive: true });
      }
      
      // If it's a model file, create a placeholder model
      if (asset.endsWith('.glb') || asset.endsWith('.gltf')) {
        // For models, try to find the file in src directory and copy it
        const srcModelPath = path.join(__dirname, 'src', asset);
        if (fs.existsSync(srcModelPath)) {
          fs.copyFileSync(srcModelPath, fullPath);
          console.log(`✅ Copied model from source: ${asset}`);
        } else {
          // Just create an empty file as placeholder
          fs.writeFileSync(fullPath, '');
          console.log(`⚠️ Created empty placeholder for: ${asset}`);
        }
      } else {
        // For other assets, create empty files
        fs.writeFileSync(fullPath, '');
        console.log(`⚠️ Created empty placeholder for: ${asset}`);
      }
    } catch (err) {
      console.error(`   Error creating placeholder: ${err.message}`);
    }
  } else {
    console.log(`✅ Critical asset exists: ${asset}`);
  }
}

// Final report
if (allDirectoriesExist && allCriticalAssetsExist) {
  console.log('✅ All required directories and critical assets are present!');
} else {
  console.warn('⚠️ Some required directories or critical assets were missing and were created as placeholders.');
  console.warn('   Please ensure actual assets are copied to the dist directory before deployment.');
}

// Helper function to scan directory and report contents
function scanAndReportDir(dir, maxDepth = 2, currentDepth = 0) {
  if (currentDepth > maxDepth) return;
  
  try {
    const items = fs.readdirSync(dir);
    
    for (const item of items) {
      const fullPath = path.join(dir, item);
      const stat = fs.statSync(fullPath);
      
      const relativePath = path.relative(DIST_DIR, fullPath);
      const indent = '  '.repeat(currentDepth);
      
      if (stat.isDirectory()) {
        console.log(`${indent}📁 ${relativePath}/`);
        scanAndReportDir(fullPath, maxDepth, currentDepth + 1);
      } else {
        console.log(`${indent}📄 ${relativePath} (${(stat.size / 1024).toFixed(2)} KB)`);
      }
    }
  } catch (err) {
    console.error(`Error scanning directory ${dir}: ${err.message}`);
  }
}

// Scan and report the dist directory structure
console.log('\nDist directory contents:');
scanAndReportDir(DIST_DIR, 1); 