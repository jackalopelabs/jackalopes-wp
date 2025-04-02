/**
 * Asset loader utility for Jackalopes game
 * 
 * This utility handles asset paths in both WordPress and standalone environments.
 */

/**
 * Ensure a URL has the same protocol as the current page to prevent mixed content issues
 * 
 * @param url - The URL to fix
 * @returns The URL with correct protocol
 */
export const fixProtocol = (url: string): string => {
  // Skip if already using the same protocol or is a relative URL
  if (url.startsWith('//') || url.startsWith('./') || url.startsWith('/') || !url.includes('://')) {
    return url;
  }

  // Get current protocol
  const currentProto = window.location.protocol;
  
  // If we're on HTTPS but URL is HTTP, convert to protocol-relative or HTTPS
  if (currentProto === 'https:' && url.startsWith('http:')) {
    // Convert to protocol-relative URL
    return url.replace('http:', '');
  }
  
  return url;
};

/**
 * Normalize a path to ensure proper formatting
 * 
 * @param path - Path to normalize
 * @returns Normalized path
 */
export const normalizePath = (path: string): string => {
  // Remove leading slash if present
  let cleanPath = path.startsWith('/') ? path.substring(1) : path;
  
  // Ensure consistent path format for special asset directories
  if (cleanPath.includes('environment/') || 
      cleanPath.includes('characters/') || 
      cleanPath.includes('models/')) {
    
    // If the path doesn't already have the assets/ prefix but needs it
    if (!cleanPath.startsWith('assets/')) {
      
      // If it's a direct path to a model directory, add assets/
      if (cleanPath.startsWith('models/') || 
          cleanPath.startsWith('environment/') || 
          cleanPath.startsWith('characters/')) {
        cleanPath = `assets/${cleanPath}`;
      }
      
      // Otherwise, if it has a models/ or environment/ or characters/ in the middle
      else if (cleanPath.includes('/models/') || 
               cleanPath.includes('/environment/') || 
               cleanPath.includes('/characters/')) {
        
        // Extract just the important part - get everything from models/ onwards
        const parts = cleanPath.split(/(models\/|environment\/|characters\/)/);
        if (parts.length >= 3) {
          const prefix = parts[1]; // This is "models/" or "environment/" or "characters/"
          const suffix = parts[2]; // This is everything after
          cleanPath = `assets/${prefix}${suffix}`;
        }
      }
    }
  }
  
  return cleanPath;
};

/**
 * Get the correct asset path based on environment
 * 
 * @param path - The asset path relative to assets directory
 * @returns The full path to the asset
 */
export const getAssetPath = (path: string): string => {
  // Handle null or undefined paths
  if (!path) {
    console.error('[ASSET] Null or undefined path provided');
    return './assets/fallback.png'; // Return a fallback path
  }
  
  // Normalize the path
  const cleanPath = normalizePath(path);
  
  // Debug logs to help troubleshoot path resolution
  console.log(`[ASSET] Original path: ${path}`);
  console.log(`[ASSET] Normalized path: ${cleanPath}`);
  
  // If running in WordPress, use the assets URL from WordPress settings
  if (window.jackalopesGameSettings?.assetsUrl) {
    // Handle WordPress mode
    const wpUrl = window.jackalopesGameSettings.assetsUrl;
    
    // Fix double "assets" in the path
    let finalPath = cleanPath;
    if (finalPath.startsWith('assets/') && wpUrl.includes('/assets/')) {
      finalPath = finalPath.substring(7); // Remove the leading "assets/"
    }

    // Ensure URL uses correct protocol to prevent mixed content issues
    const fullPath = fixProtocol(`${wpUrl}${finalPath}`);
    console.log(`[ASSET] WordPress path resolved: ${fullPath}`);
    return fullPath;
  }
  
  // In development mode, use the relative path
  // Handle different path formats from the original game
  if (cleanPath.startsWith('assets/')) {
    return `./${cleanPath}`;
  }
  
  const devPath = `./assets/${cleanPath}`;
  console.log(`[ASSET] Development path resolved: ${devPath}`);
  return devPath;
};

/**
 * Resolve a model path for use in both WordPress and standalone environments
 * 
 * @param modelPath - Original model path from standalone game
 * @returns Properly resolved path for current environment
 */
export const resolveModelPath = (modelPath: string): string => {
  // Handle special case for environment models
  if (modelPath.includes('lowpoly_nature') || modelPath.includes('environment')) {
    return getAssetPath(`environment/lowpoly_nature/${modelPath.split('/').pop()}`);
  }
  
  // Handle special case for character models
  if (modelPath.includes('characters')) {
    if (modelPath.includes('animations')) {
      return getAssetPath(`characters/animations/${modelPath.split('/').pop()}`);
    }
    return getAssetPath(`characters/${modelPath.split('/').pop()}`);
  }
  
  // Extract just the filename if it's a full path
  const filename = modelPath.split('/').pop() || modelPath;
  
  // Resolve based on environment - default to models folder
  return getAssetPath(`models/${filename}`);
};

/**
 * Check if an asset exists at the specified path
 * 
 * @param path - The asset path to check
 * @returns A promise that resolves to true if the asset exists
 */
export const checkAssetExists = (path: string): Promise<boolean> => {
  return new Promise((resolve) => {
    const xhr = new XMLHttpRequest();
    xhr.open('HEAD', getAssetPath(path), true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        resolve(xhr.status === 200);
      }
    };
    xhr.send();
  });
};

/**
 * Preload an image asset
 * 
 * @param path - The asset path relative to assets directory
 * @returns A promise that resolves when the image is loaded
 */
export const preloadImage = (path: string): Promise<HTMLImageElement> => {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.src = getAssetPath(path);
    img.onload = () => resolve(img);
    img.onerror = () => reject(new Error(`Failed to load image: ${path}`));
  });
};

/**
 * Preload an audio asset
 * 
 * @param path - The asset path relative to assets directory
 * @returns A promise that resolves when the audio is loaded
 */
export const preloadAudio = (path: string): Promise<HTMLAudioElement> => {
  return new Promise((resolve, reject) => {
    const audio = new Audio();
    audio.src = getAssetPath(path);
    audio.oncanplaythrough = () => resolve(audio);
    audio.onerror = () => reject(new Error(`Failed to load audio: ${path}`));
  });
};

/**
 * Asset types for the Jackalopes game
 */
export enum AssetType {
  Model = 'models',
  Texture = 'textures',
  Sound = 'sounds',
  Image = 'images'
}

/**
 * Get the full path for a specific asset type
 * 
 * @param type - The type of asset
 * @param filename - The filename of the asset
 * @returns The full path to the asset
 */
export const getTypedAssetPath = (type: AssetType, filename: string): string => {
  return getAssetPath(`${type}/${filename}`);
};

// Create a test function to verify asset loading
export const testAssetLoading = (path: string): void => {
  console.log(`Testing asset path: ${path}`);
  console.log(`Resolved path: ${getAssetPath(path)}`);
  
  checkAssetExists(path)
    .then(exists => console.log(`Asset exists: ${exists}`))
    .catch(err => console.error(`Error checking asset: ${err}`));
};

export default {
  getAssetPath,
  resolveModelPath,
  preloadImage,
  preloadAudio,
  getTypedAssetPath,
  checkAssetExists,
  testAssetLoading,
  AssetType,
  fixProtocol,
  normalizePath
}; 