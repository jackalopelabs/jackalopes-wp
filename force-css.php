<?php
/**
 * Direct CSS handler for Jackalopes
 *
 * This script forces the correct MIME type for CSS files to avoid browser restrictions
 */

// Set the content type header to text/css
header('Content-Type: text/css');
header('Cache-Control: max-age=3600');

// Get the requested CSS file path
$css_file = isset($_GET['file']) ? $_GET['file'] : 'game/dist/assets/main.css';
$full_path = __DIR__ . '/' . $css_file;

// Check if the file exists and is a CSS file
if (file_exists($full_path) && (pathinfo($full_path, PATHINFO_EXTENSION) === 'css')) {
    // Read the CSS file
    $css_content = file_get_contents($full_path);
    
    // Output the CSS content
    echo $css_content;
} else {
    // If file doesn't exist, provide some basic CSS as fallback
    echo <<<CSS
    /* Fallback CSS for Jackalopes game - file not found: $full_path */
    .jackalopes-game-container {
        position: relative;
        overflow: hidden;
        background: #000;
        border-radius: 5px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    #jackalopes-game {
        width: 100% !important;
        height: 100% !important;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    canvas {
        width: 100% !important;
        height: 100% !important;
        display: block;
    }
CSS;
} 