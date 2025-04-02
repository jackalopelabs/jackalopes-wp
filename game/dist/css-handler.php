<?php
/**
 * Direct CSS handler for Jackalopes
 * 
 * This script serves CSS files directly with the correct MIME type
 * to avoid WordPress MIME type issues.
 */

// Set proper headers first thing
header('Content-Type: text/css');
header('Cache-Control: max-age=3600');

// Define the path to the main.css file
$css_file = __DIR__ . '/assets/main.css';

// Check if the file exists
if (file_exists($css_file)) {
    // Output the file contents
    readfile($css_file);
} else {
    // Return empty CSS with error comment if file doesn't exist
    echo "/* CSS file not found: $css_file */";
} 