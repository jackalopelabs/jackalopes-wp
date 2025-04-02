import React from 'react';
import * as ReactDOMClient from 'react-dom/client';
import * as ReactDOM from 'react-dom';
import App from './App';
import './index.css';
import { setupWPGameIntegration } from './utils/wpIntegration';
import uiContainer from './utils/ui-container';
import { fixProtocol } from './utils/assetLoader';

// Import types from shared file
import { JackalopesGameSettings, JackalopesGameOptions } from './types/wordpress';

/**
 * Jackalopes Game - WordPress Plugin Integration
 * 
 * This is the entry point for the WordPress plugin integration.
 * The game will be initialized when the WordPress shortcode is loaded.
 */

// Use this line to avoid direct calls to React.useEffect which can cause hook errors
// The useEffect, useState, etc. functions should only be called from inside React components
const { useState, useEffect, useContext, useRef } = React;

// Extend the ReactDOM interface to include createRoot from react-dom/client
declare global {
  interface Window {
    React: typeof React;
    ReactDOM: typeof ReactDOM & {
      createRoot?: typeof ReactDOMClient.createRoot;
    };
    jackalopesGameSettings?: JackalopesGameSettings;
    initJackalopesGame: (containerId: string, options?: JackalopesGameOptions) => void;
  }
}

// Explicitly ensure React is available in the global scope
if (typeof window !== 'undefined') {
  // Assign React to window IMMEDIATELY to ensure it's available
  window.React = React;
  window.ReactDOM = ReactDOM;
  
  // Also for ReactDOMClient
  if (!window.ReactDOM.createRoot) {
    window.ReactDOM.createRoot = ReactDOMClient.createRoot;
  }
}

// If a global React hook function is called outside a component (like in this file)
// We need to make sure it doesn't cause an error
// This creates a safe wrapper function
const safeHookCall = (hookFn: Function) => {
  return (...args: any[]) => {
    try {
      return hookFn(...args);
    } catch (e) {
      console.warn('Hook call failed, but safely caught:', e);
      // Return a dummy cleanup function
      return () => {};
    }
  };
};

// Create safe hook functions
const safeUseEffect = safeHookCall(useEffect);

// Initialize WordPress integration
setupWPGameIntegration();

/**
 * This function ensures that UI elements are properly positioned within the game container
 * @param containerId The ID of the container element
 */
function setupContainedUI(containerId: string) {
  // Get the container element
  const container = document.getElementById(containerId);
  if (!container) return null;
  
  // Add a class to identify this as our container
  container.classList.add('jackalopes-game-container');
  
  // Create a mutation observer to monitor changes to the DOM
  const observer = new MutationObserver((mutations) => {
    // Look for UI elements that might be using fixed positioning
    const fixedElements = document.querySelectorAll(
      '.fps-stats, .virtual-gamepad, .game-controls, .jackalopes-ui, .jackalopes-status, ' +
      '.jackalopes-help, .loading-screen, .jackalopes-wordpress-notice, ' +
      '.jackalopes-audio-button-container, .jackalopes-audio-wrapper, .jackalopes-audio-mobile-wrapper'
    );
    
    // Move any elements outside the container into the container
    fixedElements.forEach(element => {
      // Check if the element is not already a child of our container
      if (element.parentElement !== container) {
        // Get the current computed style to preserve positioning
        const style = window.getComputedStyle(element as HTMLElement);
        const position = style.position;
        const top = style.top;
        const left = style.left;
        const right = style.right;
        const bottom = style.bottom;
        
        // Move the element into our container
        container.appendChild(element);
        
        // Apply the right positioning class based on its original position
        if (position === 'fixed' || position === 'absolute') {
          element.classList.add('fixed-ui');
          
          // Determine which corner it belongs in
          const topValue = parseInt(top);
          const leftValue = parseInt(left);
          const rightValue = parseInt(right);
          const bottomValue = parseInt(bottom);
          
          if (topValue <= 50 && leftValue <= 50) {
            element.classList.add('fixed-top-left');
          } else if (topValue <= 50 && rightValue <= 50) {
            element.classList.add('fixed-top-right');
          } else if (bottomValue <= 50 && leftValue <= 50) {
            element.classList.add('fixed-bottom-left');
          } else if (bottomValue <= 50 && rightValue <= 50) {
            element.classList.add('fixed-bottom-right');
          }
        }
      }
    });
  });
  
  // Start observing the document body for DOM changes
  observer.observe(document.body, { 
    childList: true, 
    subtree: true 
  });
  
  // Setup fullscreen handling
  setupFullscreenHandling(container);
  
  return observer;
}

/**
 * Sets up fullscreen handling for the game container
 */
function setupFullscreenHandling(container: HTMLElement) {
  // Create fullscreen button if it doesn't exist
  let fullscreenBtn = container.querySelector('.fullscreen-button');
  if (!fullscreenBtn) {
    fullscreenBtn = document.createElement('button');
    fullscreenBtn.className = 'fullscreen-button fixed-ui fixed-top-right';
    fullscreenBtn.innerHTML = 'Fullscreen';
    (fullscreenBtn as HTMLElement).style.marginTop = '10px';
    (fullscreenBtn as HTMLElement).style.marginRight = '10px';
    container.appendChild(fullscreenBtn);
  }
  
  // Add fullscreen toggle functionality
  fullscreenBtn.addEventListener('click', () => {
    if (!document.fullscreenElement) {
      if (container.requestFullscreen) {
        container.requestFullscreen().then(() => {
          container.classList.add('fullscreen-active');
        }).catch(err => {
          console.error('Error attempting to enable fullscreen:', err);
        });
      }
    } else {
      if (document.exitFullscreen) {
        document.exitFullscreen().then(() => {
          container.classList.remove('fullscreen-active');
        }).catch(err => {
          console.error('Error attempting to exit fullscreen:', err);
        });
      }
    }
  });
  
  // Handle fullscreen changes
  document.addEventListener('fullscreenchange', () => {
    if (document.fullscreenElement === container) {
      container.classList.add('fullscreen-active');
    } else {
      container.classList.remove('fullscreen-active');
    }
  });
}

// Store the observer for cleanup
let uiObserver: MutationObserver | null = null;

/**
 * Helper function to ensure all URLs use the correct protocol
 * @param settings The game settings object
 * @returns Updated settings object with fixed URLs
 */
const fixGameSettingsProtocol = (settings: JackalopesGameSettings): JackalopesGameSettings => {
  // If we're on HTTPS, make sure all URLs are either HTTPS or protocol-relative
  const isHttps = window.location.protocol === 'https:';
  
  if (isHttps) {
    if (settings.serverUrl && settings.serverUrl.startsWith('ws://')) {
      settings.serverUrl = settings.serverUrl.replace('ws://', 'wss://');
    }
    
    if (settings.assetsUrl) {
      settings.assetsUrl = fixProtocol(settings.assetsUrl);
    }
    
    if (settings.pluginUrl) {
      settings.pluginUrl = fixProtocol(settings.pluginUrl);
    }
  }
  
  return settings;
};

// Define the function separately so it can be both exported and attached to window
function initJackalopesGame(containerId: string, options: JackalopesGameOptions = {}) {
  // Get the container element
  const container = document.getElementById(containerId);
  if (!container) {
    console.error(`Container element with ID "${containerId}" not found`);
    return;
  }
  
  // Extract server URL - default to secure WebSocket for HTTPS sites
  const isSecureSite = window.location.protocol === 'https:';
  let defaultServerUrl = isSecureSite 
    ? 'wss://' + window.location.host + '/websocket/' 
    : 'ws://' + window.location.host + '/websocket/';
  
  // Configure game to operate in standalone mode if no server is available
  const serverModeEnabled = options.enableServer !== false;
  
  // Store game settings globally
  window.jackalopesGameSettings = fixGameSettingsProtocol({
    serverUrl: options.serverUrl || options.server || defaultServerUrl,
    isFullscreen: options.fullscreen || false,
    assetsUrl: options.assetsUrl || '',
    containerId,
    isWordPress: true,
    isSecure: isSecureSite,
    serverModeEnabled: serverModeEnabled
  });
  
  // Initialize UI containment to ensure elements stay in container
  uiContainer.initUiContainment(containerId);
  
  // Set up the contained UI
  const observer = setupContainedUI(containerId);
  if (observer) {
    uiObserver = observer;
  }
  
  // Log initialization information
  console.log('Jackalopes game initializing with settings:', window.jackalopesGameSettings);
  
  // Make sure the container has the right classes
  container.classList.add('jackalopes-game-container');
  
  // Additional cleanup on unload
  window.addEventListener('beforeunload', () => {
    if (uiObserver) {
      uiObserver.disconnect();
      uiObserver = null;
    }
  });
  
  // Initialize the React app
  try {
    // We need a function component wrapper to safely use hooks
    const GameWrapper = (props: any) => {
      // Hooks can safely be used inside the functional component
      // No direct calls to useEffect outside components
      return <App {...props} />;
    };
    
    const root = ReactDOMClient.createRoot(container);
    const gameSettings = window.jackalopesGameSettings || {};
    
    root.render(
      <React.StrictMode>
        <GameWrapper 
          serverUrl={gameSettings.serverUrl}
          isFullscreen={gameSettings.isFullscreen}
          isWordPress={true}
          assetsUrl={gameSettings.assetsUrl}
          serverModeEnabled={gameSettings.serverModeEnabled}
        />
      </React.StrictMode>
    );
    console.log('Game React component successfully rendered');
  } catch (error) {
    console.error('Failed to initialize game:', error);
    // Show error message in container
    if (container) {
      container.innerHTML = `
        <div style="color: red; padding: 20px;">
          <h2>Failed to initialize game</h2>
          <p>${error instanceof Error ? error.message : String(error)}</p>
        </div>
      `;
    }
  }
}

// IMPORTANT: Explicitly set the initialization function on the window object
// This ensures it's available globally for WordPress to call
if (typeof window !== 'undefined') {
  console.log('Setting initJackalopesGame on window object');
  window.initJackalopesGame = initJackalopesGame;
}

// Export the function for module usage
export { initJackalopesGame };

// If not in a WordPress environment (standalone development), initialize immediately
if (!window.jackalopesGameSettings && process.env.NODE_ENV === 'development') {
  const devContainer = document.getElementById('root');
  
  if (devContainer) {
    // Generate a session key if not exists
    const sessionKey = localStorage.getItem('jackalopes_session_key') || 'JACKALOPES-DEV';
    localStorage.setItem('jackalopes_session_key', sessionKey);
    
    // Simulated standalone initialization for development - use the full game App
    const root = ReactDOMClient.createRoot(devContainer);
    root.render(
      <React.StrictMode>
        <App />
      </React.StrictMode>
    );
    
    console.log('Jackalopes game initialized in development mode');
    console.log(`Session Key: ${sessionKey}`);
  }
} 