import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';
import './index.css';
import { setupWPGameIntegration } from './utils/wpIntegration';
import uiContainer from './utils/ui-container';

// Import types from shared file
import { JackalopesGameSettings, JackalopesGameOptions } from './types/wordpress';

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
function setupContainedUI(containerId: string) {
  // Get the container element
  const container = document.getElementById(containerId);
  if (!container) return;
  
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
    fullscreenBtn.style.marginTop = '10px';
    fullscreenBtn.style.marginRight = '10px';
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
    jackalopesGameSettings?: any;
  }
}

// Store the observer for cleanup
let uiObserver: MutationObserver | null = null;

// Modify the existing initialization function to include UI containment
window.initJackalopesGame = (containerId, options = {}) => {
  // Get the container element
  const container = document.getElementById(containerId);
  if (!container) {
    console.error(`Container element with ID "${containerId}" not found`);
    return;
  }
  
  // Store game settings globally
  window.jackalopesGameSettings = {
    serverUrl: options.serverUrl || 'ws://localhost:8082',
    isFullscreen: options.fullscreen || false,
    assetsUrl: options.assetsUrl || '',
    containerId,
    isWordPress: true
  };
  
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
};

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