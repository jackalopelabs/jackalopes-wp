<?php
/**
 * Direct CSS handler for Jackalopes main CSS
 * 
 * This script serves CSS files directly with the correct MIME type
 * from the plugin root to avoid WordPress MIME type issues.
 */

// Set proper headers first thing
header('Content-Type: text/css');
header('Cache-Control: max-age=3600');

// Define path to this plugin directory
$plugin_dir = dirname(__FILE__);

// Define the path to the main.css file
$css_file = $plugin_dir . '/game/dist/assets/main.css';

// Check if the file exists
if (file_exists($css_file)) {
    // Output the file contents
    readfile($css_file);
} else {
    // Output basic styles as fallback
    echo <<<CSS
    /* Fallback styles - main.css not found */
    .jackalopes-game-container {
        position: relative;
        overflow: hidden;
        background: #000;
        border-radius: 5px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    #jackalopes-game {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .game-loading {
        text-align: center;
        color: #fff;
        padding: 20px;
    }
    
    .loading-spinner {
        border: 5px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top: 5px solid #3498db;
        width: 40px;
        height: 40px;
        animation: jackalopes-spin 1s linear infinite;
        margin: 20px auto;
    }
    
    @keyframes jackalopes-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .game-error {
        text-align: center;
        background: rgba(220, 53, 69, 0.9);
        color: white;
        padding: 20px;
        border-radius: 5px;
        max-width: 80%;
        margin: 0 auto;
    }
    
    .error-retry {
        background: #fff;
        color: #dc3545;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: bold;
        cursor: pointer;
        margin-top: 10px;
    }
    
    .error-retry:hover {
        background: #f8f9fa;
    }
    
    /* Fix for canvas display */
    .jackalopes-game-container canvas {
        width: 100% !important;
        height: 100% !important;
        display: block !important;
    }
CSS;
} 