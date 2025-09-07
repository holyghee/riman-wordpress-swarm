<?php
// RIMAN Blocks: zentrale Shortcodes
if (!defined('ABSPATH')) exit;

// [category_page_content] – lädt den Inhalt der mit der Kategorie verknüpften Seite
add_action('init', function() {
    // Wenn bereits ein anderer Provider registriert hat, nichts tun
    if (shortcode_exists('category_page_content')) return;

    add_shortcode('category_page_content', function() {
        if (!is_category()) return '';

        $current_category = get_queried_object();
        if (!$current_category) return '';

        // Verknüpfte Seite via Term Meta
        $linked_page_id = get_term_meta($current_category->term_id, '_linked_page_id', true);

        if ($linked_page_id) {
            $page = get_post($linked_page_id);
            if ($page && $page->post_status === 'publish') {
                $content = apply_filters('the_content', $page->post_content);
                return '<div class="category-page-content">' . $content . '</div>';
            }
        }

        // Fallback: Kategorie-Beschreibung
        $description = category_description();
        if ($description) return '<div class="category-description">' . $description . '</div>';
        return '';
    });
});

