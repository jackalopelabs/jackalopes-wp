/**
 * UI Container utility
 * 
 * This module ensures that all UI elements stay within the game container
 * when integrated into WordPress.
 */

/**
 * Initialize UI containment for the specified container
 */
export function initUiContainment(containerId: string): void {
  const container = document.getElementById(containerId);
  if (!container) return;
  
  // Add container class
  container.classList.add('jackalopes-game-container');
  
  // Create observer to watch for new UI elements
  const observer = new MutationObserver((mutations) => {
    ensureElementsInContainer(container);
  });
  
  // Start observing
  observer.observe(document.body, {
    childList: true,
    subtree: true
  });
  
  // Initial check
  ensureElementsInContainer(container);
  
  // Handle fullscreen changes
  document.addEventListener('fullscreenchange', () => {
    if (document.fullscreenElement === container) {
      container.classList.add('fullscreen-active');
    } else {
      container.classList.remove('fullscreen-active');
    }
    
    // Check elements again after fullscreen change
    setTimeout(() => ensureElementsInContainer(container), 100);
  });
  
  // Setup fullscreen button
  setupFullscreenButton(container);
  
  // Check periodically (safety net)
  setInterval(() => ensureElementsInContainer(container), 1000);
  
  return;
}

/**
 * Setup fullscreen button
 */
function setupFullscreenButton(container: HTMLElement): void {
  // Create fullscreen button if needed
  let fullscreenBtn = container.querySelector('.fullscreen-button') as HTMLElement;
  
  if (!fullscreenBtn) {
    fullscreenBtn = document.createElement('button');
    fullscreenBtn.className = 'fullscreen-button fixed-ui fixed-top-right';
    fullscreenBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>';
    fullscreenBtn.style.position = 'absolute';
    fullscreenBtn.style.top = '10px';
    fullscreenBtn.style.right = '10px';
    fullscreenBtn.style.zIndex = '100';
    fullscreenBtn.style.background = 'rgba(0, 0, 0, 0.5)';
    fullscreenBtn.style.color = 'white';
    fullscreenBtn.style.border = 'none';
    fullscreenBtn.style.borderRadius = '4px';
    fullscreenBtn.style.padding = '8px';
    fullscreenBtn.style.cursor = 'pointer';
    
    container.appendChild(fullscreenBtn);
  }
  
  // Add fullscreen toggle
  fullscreenBtn.addEventListener('click', () => {
    if (!document.fullscreenElement) {
      container.requestFullscreen().catch(err => {
        console.error(`Error attempting to enable fullscreen: ${err.message}`);
      });
    } else {
      document.exitFullscreen().catch(err => {
        console.error(`Error attempting to exit fullscreen: ${err.message}`);
      });
    }
  });
}

/**
 * Ensure all UI elements are inside the container
 */
function ensureElementsInContainer(container: HTMLElement): void {
  // List of selectors for elements that should be contained
  const selectors = [
    '.fps-stats',
    '.virtual-gamepad',
    '.game-controls',
    '.jackalopes-ui',
    '.jackalopes-status',
    '.jackalopes-help',
    '.jackalopes-loading',
    '.jackalopes-wordpress-notice',
    '.loading-screen',
    '.control-panel',
    '.game-ui-element',
    '.jackalopes-audio-button-container',
    '.jackalopes-audio-wrapper',
    '.jackalopes-audio-mobile-wrapper'
  ];
  
  // Find elements
  const elements = document.querySelectorAll(selectors.join(', '));
  
  // Process each element
  elements.forEach(element => {
    if (element.parentElement !== container) {
      // Get current position information
      const style = window.getComputedStyle(element);
      const position = style.position;
      const top = parseInt(style.top);
      const left = parseInt(style.left);
      const right = parseInt(style.right);
      const bottom = parseInt(style.bottom);
      
      // Move to container
      container.appendChild(element);
      
      // Add positioning classes
      if (position === 'fixed' || position === 'absolute') {
        element.classList.add('fixed-ui');
        
        // Determine corner position
        if ((top <= 50 || style.top === '0px') && (left <= 50 || style.left === '0px')) {
          element.classList.add('fixed-top-left');
        } else if ((top <= 50 || style.top === '0px') && (right <= 50 || style.right === '0px')) {
          element.classList.add('fixed-top-right');
        } else if ((bottom <= 50 || style.bottom === '0px') && (left <= 50 || style.left === '0px')) {
          element.classList.add('fixed-bottom-left');
        } else if ((bottom <= 50 || style.bottom === '0px') && (right <= 50 || style.right === '0px')) {
          element.classList.add('fixed-bottom-right');
        }
      }
    }
  });
}

/**
 * Handle element positioning during fullscreen mode
 */
export function handleFullscreenChange(containerId: string): void {
  const container = document.getElementById(containerId);
  if (!container) return;
  
  if (document.fullscreenElement === container) {
    container.classList.add('fullscreen-active');
  } else {
    container.classList.remove('fullscreen-active');
  }
  
  // Ensure elements are positioned correctly
  ensureElementsInContainer(container);
}

/**
 * Helper to clean up UI containment
 */
export function cleanupUiContainment(): void {
  // Nothing specific to clean up yet
}

export default {
  initUiContainment,
  handleFullscreenChange,
  cleanupUiContainment
}; 