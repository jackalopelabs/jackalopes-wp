/**
 * Canvas size fix for Jackalopes Game
 * 
 * This script ensures that the ThreeJS canvas properly fills the container
 */

(function() {
  // Wait for DOM content to be loaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCanvasFix);
  } else {
    initCanvasFix();
  }
  
  function initCanvasFix() {
    console.log('Initializing canvas size fix');
    
    // Apply fix immediately and then at intervals
    applyCanvasFix();
    
    // Apply multiple times to catch any canvas created after initial load
    setTimeout(applyCanvasFix, 500);
    setTimeout(applyCanvasFix, 1000);
    setTimeout(applyCanvasFix, 2000);
    
    // Also run on resize
    window.addEventListener('resize', applyCanvasFix);
    
    // Listen for potential canvas creation
    observeCanvasCreation();
  }
  
  function applyCanvasFix() {
    // Get all game containers
    const containers = document.querySelectorAll('.jackalopes-game-container');
    
    containers.forEach(container => {
      // Get container dimensions
      const width = container.clientWidth;
      const height = container.clientHeight;
      
      console.log(`Fixing canvas size for container: ${width}x${height}`);
      
      // Force container dimensions
      container.style.position = 'relative';
      container.style.overflow = 'hidden';
      container.style.display = 'block';
      
      // Find all canvas elements within this container
      const canvases = container.querySelectorAll('canvas');
      
      canvases.forEach(canvas => {
        // Force canvas to match container size
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.display = 'block';
        canvas.style.position = 'absolute';
        canvas.style.top = '0';
        canvas.style.left = '0';
        
        // Force canvas to match container dimensions exactly
        canvas.width = width;
        canvas.height = height;
      });
      
      // Also fix any ThreeJS-related divs
      const threeDivs = container.querySelectorAll('div[class*="canvas3d"], div[class*="r3f-"]');
      threeDivs.forEach(div => {
        div.style.width = '100%';
        div.style.height = '100%';
      });
    });
  }
  
  function observeCanvasCreation() {
    // Create a mutation observer to watch for new canvas elements
    const observer = new MutationObserver((mutations) => {
      let shouldApplyFix = false;
      
      mutations.forEach(mutation => {
        if (mutation.type === 'childList') {
          mutation.addedNodes.forEach(node => {
            if (node.nodeName === 'CANVAS' || 
                (node.nodeType === 1 && node.querySelector && node.querySelector('canvas'))) {
              shouldApplyFix = true;
            }
          });
        }
      });
      
      if (shouldApplyFix) {
        console.log('Canvas added to DOM, applying fixes...');
        applyCanvasFix();
      }
    });
    
    // Start observing the document with configured parameters
    observer.observe(document.body, { childList: true, subtree: true });
  }
})(); 