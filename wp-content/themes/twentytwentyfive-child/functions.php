<?php
/**
 * Functions and definitions for Twenty Twenty-Five Child Theme
 */

// Enqueue parent theme styles
function twentytwentyfive_child_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'twentytwentyfive_child_enqueue_styles' );

// Optional: Add custom functions here
