<?php
/**
 * Shortcode-related functionality.
 *
 * @package Jackalopes_WP
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Register all shortcodes.
 */
function jackalopes_wp_register_shortcodes() {
    add_shortcode('jackalopes', 'jackalopes_wp_shortcode_callback');
}

/**
 * Shortcode callback for embedding the game.
 */
function jackalopes_wp_shortcode_callback($atts) {
    // Ensure game assets are enqueued
    jackalopes_wp_enqueue_game_assets();
    
    // Parse shortcode attributes
    $attributes = shortcode_atts(
        [
            'width' => '100%',
            'height' => '600px',
            'class' => '',
        ],
        $atts
    );
    
    // Build custom CSS for the container
    $custom_css = sprintf(
        'width: %s; height: %s;',
        esc_attr($attributes['width']),
        esc_attr($attributes['height'])
    );
    
    // Start output buffering to return HTML
    ob_start();
    ?>
    <div class="jackalopes-game-container <?php echo esc_attr($attributes['class']); ?>" style="<?php echo $custom_css; ?>">
        <div id="jackalopes-game" data-initialized="false">
            <div class="game-loading">
                <p class="loading-message">Loading Jackalopes Game...</p>
                <div class="loading-spinner"></div>
            </div>
            <div class="game-error" style="display: none;">
                <p class="error-message">There was an error loading the game. Please try refreshing the page.</p>
                <button class="error-retry">Retry</button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
} 