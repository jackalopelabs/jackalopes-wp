/**
 * WordPress-related type definitions
 */

/**
 * WordPress integration types
 */

/**
 * WordPress game settings passed from the plugin
 */
export interface JackalopesGameSettings {
  ajaxUrl?: string;
  pluginUrl?: string;
  assetsUrl?: string;
  serverUrl?: string;
  debug?: boolean;
  nonce?: string;
  sessionKey?: string;
  isFullscreen?: boolean;
  containerId?: string;
  isWordPress?: boolean;
  isSecure?: boolean;
}

/**
 * Game initialization options
 */
export interface JackalopesGameOptions {
  server?: string;
  serverUrl?: string;
  fullscreen?: boolean;
  assetsUrl?: string;
  sessionKey?: string;
  debugMode?: boolean;
} 