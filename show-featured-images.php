<?php
/**
 * Plugin Name: Show Featured Images
 * Description: Automatically displays featured images at the top of pages
 * Version: 1.0
 */

// Add featured image to page content
function riman_show_featured_image($content) {
    // Only on single pages, not posts or other content types
    if (is_page() && is_single() && has_post_thumbnail()) {
        $featured_image = get_the_post_thumbnail(
            get_the_ID(),
            'large',
            array(
                'style' => 'width: 100%; height: auto; margin-bottom: 20px; border-radius: 8px;'
            )
        );
        
        // Prepend featured image to content
        $content = $featured_image . $content;
    }
    
    return $content;
}

// Hook into the content filter
add_filter('the_content', 'riman_show_featured_image');

// Also add CSS for better styling
function riman_featured_image_styles() {
    if (is_page()) {
        echo '<style>
        .wp-post-image {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .wp-post-image:hover {
            transform: scale(1.02);
        }
        </style>';
    }
}
add_action('wp_head', 'riman_featured_image_styles');