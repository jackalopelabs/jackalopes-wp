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
export function fixProtocol(url: string): string {
  // If already protocol-relative or absolute with protocol, return as is
  if (url.startsWith('//') || url.startsWith('http://') || url.startsWith('https://')) {
    return url;
  }
  
  // Convert absolute URLs to protocol-relative
  if (url.startsWith('http:') || url.startsWith('https:')) {
    return url.replace(/^https?:/, '');
  }
  
  return url;
}

/**
 * Detect if we're in a development environment
 * This handles both Vite development mode and WordPress development mode
 */
export const isDevEnvironment = (): boolean => {
  // Check for Vite dev server
  const isViteDev = window.location.port === '3000' || 
                    window.location.port === '5173' || 
                    window.location.hostname === 'localhost' ||
                    window.location.hostname === '[::1]';
                    
  // Check for WordPress development mode
  const isWpDev = window.jackalopesGameSettings?.debug === true;
  
  return isViteDev || isWpDev;
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
 * Check if the current URL is localhost or IP-based 
 * This helps detect dev environments that would trigger CORS issues
 */
export const isLocalOrIpBasedHost = (): boolean => {
  const hostname = window.location.hostname;
  return hostname === 'localhost' || 
         hostname === '[::1]' || 
         /^127\.\d+\.\d+\.\d+$/.test(hostname) ||
         /^\d+\.\d+\.\d+\.\d+$/.test(hostname);
};

/**
 * Get the correct asset path based on environment
 * 
 * @param path - The asset path relative to assets directory
 * @returns The full path to the asset
 */
export function getAssetPath(relativePath: string): string {
  // Remove leading slash if present
  const cleanPath = relativePath.startsWith('/') ? relativePath.substring(1) : relativePath;
  
  // Check if we're in WordPress environment
  if (typeof window !== 'undefined' && 'jackalopesGameSettings' in window) {
    const settings = (window as any).jackalopesGameSettings;
    
    // Fix duplicated "assets/" in the path
    if (cleanPath.startsWith('assets/') && settings.assetsUrl.includes('assets')) {
      return `${settings.assetsUrl}${cleanPath.substring(7)}`;
    }
    
    return `${settings.assetsUrl}${cleanPath}`;
  }
  
  // Local development
  return `/${cleanPath}`;
}

/**
 * Resolve a model path for use in both WordPress and standalone environments
 * 
 * @param modelPath - Original model path from standalone game
 * @returns Properly resolved path for current environment
 */
export const resolveModelPath = (modelPath: string): string => {
  // Special case for fps.glb
  if (modelPath === 'fps.glb' || modelPath.endsWith('/fps.glb')) {
    return getAssetPath('fps.glb');
  }
  
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
 * Try multiple possible locations for an asset until one works
 * 
 * @param paths - Array of paths to try
 * @returns Promise with the first working path or the first path if none work
 */
export const findWorkingAssetPath = async (paths: string[]): Promise<string> => {
  if (!paths || paths.length === 0) return '';
  
  for (const path of paths) {
    try {
      const exists = await checkAssetExists(path);
      if (exists) return getAssetPath(path);
    } catch (err) {
      console.warn(`Error checking path ${path}:`, err);
    }
  }
  
  // If no path works, return the first one as fallback
  return getAssetPath(paths[0]);
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
  normalizePath,
  findWorkingAssetPath,
  isDevEnvironment,
  isLocalOrIpBasedHost
}; 