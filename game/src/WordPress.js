/**
 * WordPress.js - Helper script for initializing the Jackalopes game in WordPress
 */

// Define React version for consistency with WordPress
const REACT_VERSION = '18.3.1';

// Initialize once the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
  console.log('WordPress helper script loaded');
  
  // Check if there are already multiple versions of React
  if (window._REACT_LOADED && typeof React !== 'undefined' && window.React !== React) {
    console.warn('Multiple React instances detected - fixing...');
    // Ensure we only use one copy of React
    window.React = React;
  }
  
  // Flag that we've loaded React
  window._REACT_LOADED = true;
  
  // Explicitly ensure React is defined on window
  if (typeof React !== 'undefined' && !window.React) {
    window.React = React;
  }
  
  if (typeof ReactDOM !== 'undefined' && !window.ReactDOM) {
    window.ReactDOM = ReactDOM;
  }
  
  // Add a small delay to ensure React is fully initialized
  setTimeout(function() {
    // Load React if not available
    if (!checkReactAvailability()) {
      console.log('React not detected, loading React libraries...');
      loadReactLibraries().then(() => {
        console.log('React libraries loaded successfully');
        // Add another small delay after loading
        setTimeout(initializeGame, 100);
      }).catch(error => {
        console.error('Failed to load React:', error);
        showError('Failed to load React libraries. Please refresh the page.');
      });
    } else {
      console.log('React is already available');
      initializeGame();
    }
  }, 100);
  
  // Add retry button functionality
  document.querySelectorAll('.error-retry').forEach(button => {
    button.addEventListener('click', function() {
      console.log('Retrying game initialization');
      hideError();
      showLoading();
      initializeGame();
    });
  });
});

// Load React libraries from CDN
function loadReactLibraries() {
  return new Promise((resolve, reject) => {
    loadScript(`https://unpkg.com/react@${REACT_VERSION}/umd/react.production.min.js`, 'react-script')
      .then(() => {
        return loadScript(`https://unpkg.com/react-dom@${REACT_VERSION}/umd/react-dom.production.min.js`, 'react-dom-script');
      })
      .then(() => {
        // Make sure React is available on window
        if (typeof React !== 'undefined') {
          window.React = React;
        }
        if (typeof ReactDOM !== 'undefined') {
          window.ReactDOM = ReactDOM;
        }
        resolve();
      })
      .catch(error => {
        reject(error);
      });
  });
}

// Helper function to load a script
function loadScript(src, id) {
  return new Promise((resolve, reject) => {
    if (document.getElementById(id)) {
      resolve();
      return;
    }
    
    const script = document.createElement('script');
    script.id = id;
    script.src = src;
    script.crossOrigin = 'anonymous';
    script.async = false;
    
    script.onload = () => resolve();
    script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
    
    document.head.appendChild(script);
  });
}

// Check for React availability and initialize when available
function checkReactAndInitialize(retryCount = 0) {
  const maxRetries = 10;
  
  if (checkReactAvailability()) {
    console.log('React is available, initializing game');
    initializeGame();
  } else if (retryCount < maxRetries) {
    console.log(`Waiting for React (attempt ${retryCount + 1}/${maxRetries})`);
    setTimeout(() => checkReactAndInitialize(retryCount + 1), 500);
  } else {
    console.error('React was not loaded after several attempts. Cannot initialize game.');
    showError('Failed to load React. Please check your browser console for more information.');
  }
}

// Check if React and ReactDOM are available
function checkReactAvailability() {
  // Double check both global and window properties
  const reactAvailable = typeof React !== 'undefined' || typeof window.React !== 'undefined';
  const reactDOMAvailable = typeof ReactDOM !== 'undefined' || typeof window.ReactDOM !== 'undefined';
  
  if (reactAvailable && !window.React) {
    window.React = React;
  }
  
  if (reactDOMAvailable && !window.ReactDOM) {
    window.ReactDOM = ReactDOM;
  }
  
  // Check if React hooks are working properly
  if (reactAvailable && reactDOMAvailable) {
    if (typeof window.React.useState !== 'function' || 
        typeof window.React.useEffect !== 'function') {
      console.warn('React hooks not available, possible version mismatch');
      return false;
    }
  }
  
  return reactAvailable && reactDOMAvailable;
}

// Main initialization function
function initializeGame() {
  const gameContainer = document.getElementById('jackalopes-game');
  
  if (!gameContainer) {
    console.error('Game container not found');
    return;
  }
  
  // Don't initialize if already initialized
  if (gameContainer.getAttribute('data-initialized') === 'true') {
    console.log('Game already initialized');
    return;
  }
  
  // Check if server should be enabled
  const enableServer = gameContainer.getAttribute('data-enable-server') !== 'false';
  
  // Check if main.js has loaded and the initJackalopesGame function exists
  if (typeof window.initJackalopesGame === 'function') {
    try {
      console.log('Initializing Jackalopes game');
      window.initJackalopesGame('jackalopes-game', {
        enableServer: enableServer
      });
      gameContainer.setAttribute('data-initialized', 'true');
      hideLoading();
    } catch (error) {
      console.error('Error initializing game:', error);
      showError('Error initializing game: ' + error.message);
    }
  } else {
    console.log('initJackalopesGame function not available yet, retrying in 500ms');
    // Retry after a delay
    setTimeout(checkAndInitialize, 500);
  }
}

// Helper function to check if the game can be initialized and retry as needed
function checkAndInitialize(retryCount = 0) {
  const maxRetries = 10;
  
  if (typeof window.initJackalopesGame === 'function') {
    console.log('initJackalopesGame found, initializing now');
    initializeGame();
  } else if (retryCount < maxRetries) {
    console.log(`Retry ${retryCount + 1}/${maxRetries} - waiting for initJackalopesGame`);
    setTimeout(() => checkAndInitialize(retryCount + 1), 500);
  } else {
    console.error('Failed to initialize game after maximum retries');
    showError('Failed to load the game. Please refresh the page and try again.');
  }
}

// Helper functions for UI handling
function showLoading() {
  const loadingElement = document.querySelector('.game-loading');
  if (loadingElement) {
    loadingElement.style.display = 'block';
  }
}

function hideLoading() {
  const loadingElement = document.querySelector('.game-loading');
  if (loadingElement) {
    loadingElement.style.display = 'none';
  }
}

function showError(message) {
  hideLoading();
  const errorElement = document.querySelector('.game-error');
  const errorMessage = document.querySelector('.error-message');
  
  if (errorElement) {
    errorElement.style.display = 'block';
  }
  
  if (errorMessage && message) {
    errorMessage.textContent = message;
  }
}

function hideError() {
  const errorElement = document.querySelector('.game-error');
  if (errorElement) {
    errorElement.style.display = 'none';
  }
} 