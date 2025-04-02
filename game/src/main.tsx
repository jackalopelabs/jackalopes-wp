import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';
import './index.css';
import { setupWPGameIntegration } from './utils/wpIntegration';
import uiContainer from './utils/ui-container';

// Import types from shared file
import { JackalopesGameSettings, JackalopesGameOptions } from './types/wordpress';

// Block any attempts to load resources from development server in production
(function blockLocalDevServer() {
  // Only apply this in production environments
  const isProduction = window.location.protocol === 'https:' || 
                       window.location.hostname !== 'localhost';
  
  if (isProduction) {
    // Create a list of blocked domains/patterns
    const blockedPatterns = [
      'localhost:5173',
      'localhost:3000',
      '[::1]:5173',
      '[::1]:3000',
      '@vite/client'
    ];
    
    // Prevent loading resources from localhost in production
    const originalCreateElement = document.createElement.bind(document);
    document.createElement = function(tagName: string, options?: ElementCreationOptions): HTMLElement {
      const element = originalCreateElement(tagName, options);
      
      if (tagName.toLowerCase() === 'script' || tagName.toLowerCase() === 'link' || tagName.toLowerCase() === 'img') {
        const originalSetAttribute = element.setAttribute.bind(element);
        element.setAttribute = function(name: string, value: string): void {
          // If it's a src or href attribute, check against blocked patterns
          if ((name === 'src' || name === 'href') && typeof value === 'string') {
            const isBlocked = blockedPatterns.some(pattern => value.includes(pattern));
            if (isBlocked) {
              console.warn(`Blocked resource from development server: ${value}`);
              return; // Don't set the attribute
            }
          }
          originalSetAttribute(name, value);
        };
      }
      
      return element;
    };
    
    // Also block existing elements with dev server URLs
    setTimeout(() => {
      const allElements = document.querySelectorAll('script[src], link[href], img[src]');
      allElements.forEach(element => {
        const url = element.getAttribute('src') || element.getAttribute('href') || '';
        const isBlocked = blockedPatterns.some(pattern => url.includes(pattern));
        if (isBlocked) {
          console.warn(`Removing existing dev server resource: ${url}`);
          element.remove();
        }
      });
    }, 0);
  }
})();

// Add error handlers for resource loading to prevent CORS errors from breaking the app
// This helps with issues on production sites trying to load local development resources
(function setupErrorHandlers() {
  // Store original methods to restore them if needed
  const originalCreateElement = document.createElement.bind(document);
  
  // Intercept script tag creation to add error handling
  document.createElement = function(tagName: string, options?: ElementCreationOptions): HTMLElement {
    const element = originalCreateElement(tagName, options);
    
    if (tagName.toLowerCase() === 'script') {
      const scriptElement = element as HTMLScriptElement;
      
      // Add error handling to script tags
      const originalSetAttribute = scriptElement.setAttribute.bind(scriptElement);
      scriptElement.setAttribute = function(name: string, value: string): void {
        // Skip loading Vite HMR scripts which will fail in production
        if (name === 'src' && (
            value.includes('@vite/client') || 
            value.includes('localhost:') || 
            value.includes('[::]')
        )) {
          console.warn(`Skipping loading of local development resource: ${value}`);
          return;
        }
        
        originalSetAttribute(name, value);
      };
    }
    
    return element;
  };
  
  // Add global error handler for script loading errors
  window.addEventListener('error', function(event) {
    // Check if it's a script loading error
    if (event.target && (event.target as HTMLElement).tagName === 'SCRIPT') {
      const src = (event.target as HTMLScriptElement).src;
      // Prevent errors for localhost resources when in production
      if (src && (
          src.includes('localhost:') || 
          src.includes('[::1]:') || 
          src.includes('@vite/client')
      )) {
        console.warn(`Ignored error loading development resource: ${src}`);
        event.preventDefault();
        event.stopPropagation();
      }
    }
  }, true);
})();

/**
 * Jackalopes Game - WordPress Plugin Integration
 * 
 * This is the entry point for the WordPress plugin integration.
 * The game will be initialized when the WordPress shortcode is loaded.
 */

// Initialize WordPress integration
setupWPGameIntegration();

/**
 * This function ensures that UI elements are properly positioned within the game container
 * @param containerId The ID of the container element
 */
function setupContainedUI(containerId: string): MutationObserver | null {
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
        const style = window.getComputedStyle(element);
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

// Extend the window interface for global initialization
declare global {
  interface Window {
    initJackalopesGame: (containerId: string, options?: any) => void;
    jackalopesGameSettings?: JackalopesGameSettings;
  }
}

// Store the observer for cleanup
let uiObserver: MutationObserver | null = null;

// Modify the existing initialization function to include UI containment
// Export the function so it can be imported in test.html
export function initJackalopesGame(containerId: string, options: any = {}) {
  // Get the container element
  const container = document.getElementById(containerId);
  if (!container) {
    console.error(`Container element with ID "${containerId}" not found`);
    return;
  }
  
  // Store game settings globally
  window.jackalopesGameSettings = {
    serverUrl: options.serverUrl || 'ws://localhost:8082',
    assetsUrl: options.assetsUrl || '',
    // Add required properties with default values
    ajaxUrl: options.ajaxUrl || '',
    pluginUrl: options.pluginUrl || '',
    debug: options.debugMode || false,
    nonce: options.nonce || '',
    sessionKey: options.sessionKey || localStorage.getItem('jackalopes_session_key') || 'WP-DEFAULT'
  };
  
  // Store container ID in a data attribute for reference
  container.dataset.containerId = containerId;
  
  // Initialize UI containment to ensure elements stay in container
  uiContainer.initUiContainment(containerId);
  
  // Set up the contained UI
  uiObserver = setupContainedUI(containerId);
  
  // The rest of your initialization code here...
  console.log('Game initialized with contained UI');
  
  // Make sure the container has the right classes
  container.classList.add('jackalopes-game-container');
  
  // Additional cleanup on unload
  window.addEventListener('beforeunload', () => {
    if (uiObserver) {
      uiObserver.disconnect();
      uiObserver = null;
    }
  });
}

// Also assign to window for backwards compatibility
window.initJackalopesGame = initJackalopesGame;

// If not in a WordPress environment (standalone development), initialize immediately
if (!window.jackalopesGameSettings && process.env.NODE_ENV === 'development') {
  const devContainer = document.getElementById('root');
  
  if (devContainer) {
    // Generate a session key if not exists
    const sessionKey = localStorage.getItem('jackalopes_session_key') || 'JACKALOPES-DEV';
    localStorage.setItem('jackalopes_session_key', sessionKey);
    
    // Simulated standalone initialization for development - use the full game App
    const root = ReactDOM.createRoot(devContainer);
    root.render(
      <React.StrictMode>
        <App />
      </React.StrictMode>
    );
    
    console.log('Jackalopes game initialized in development mode');
    console.log(`Session Key: ${sessionKey}`);
  }
} 