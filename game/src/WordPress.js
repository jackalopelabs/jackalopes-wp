/**
 * WordPress.js - Helper script for initializing the Jackalopes game in WordPress
 */

// Initialize once the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
  console.log('WordPress helper script loaded');
  initializeGame();
  
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
    console.error('Failed to initialize game after multiple retries');
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