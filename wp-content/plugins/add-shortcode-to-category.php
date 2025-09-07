<?php
/**
 * Plugin Name: Add Subcategories to Category Template
 * Description: Fügt den Subcategories Shortcode automatisch zu Kategorie-Seiten hinzu
 * Version: 1.0
 * Author: RIMAN GmbH
 */

if (!defined('ABSPATH')) exit;

class Add_Subcategories_To_Category {
    
    public function __construct() {
        // Hook nach dem Category Page Content - mit niedrigerer Priorität damit Page Connector zuerst läuft
        add_filter('the_content', array($this, 'add_subcategories_after_content'), 25);
        
        // NICHT loop_start verwenden - das ist zu früh!
        // add_action('loop_start', array($this, 'add_subcategories_before_posts'));
        
        // Verstecke Standard Posts-Query bei Hauptkategorien
        add_filter('render_block_core/query', array($this, 'hide_posts_query'), 10, 3);
        add_filter('render_block_core/query-title', array($this, 'hide_query_title'), 10, 3);
    }
    
    /**
     * Fügt Subcategories nach dem Content hinzu
     */
    public function add_subcategories_after_content($content) {
        // Nur auf Kategorie-Seiten im Main Loop
        if (!is_category() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        global $post;
        
        // Prüfe ob wir auf der richtigen Stelle sind
        static $already_added = false;
        if ($already_added) {
            return $content;
        }
        
        $category = get_queried_object();
        
        // Hole zuerst den Category Page Content
        $category_content = do_shortcode('[category_page_content]');
        
        // Prüfe ob Unterkategorien existieren
        $subcategories = get_categories([
            'parent' => $category->term_id,
            'hide_empty' => false
        ]);
        
        // Wenn wir Category Content haben, zeige diesen zuerst
        if (!empty($category_content)) {
            $content = $category_content;
            
            // Füge Subcategories hinzu wenn vorhanden
            if (!empty($subcategories)) {
                $content .= '<div class="subcategories-section" style="margin-top: 3rem;">';
                // Verwende den neuen Header Shortcode
                $content .= do_shortcode('[category_section_header]');
                $content .= do_shortcode('[subcategories_grid show_posts="yes" posts_per_category="3" show_description="yes"]');
                $content .= '</div>';
            }
            $already_added = true;
        }
        // Wenn kein Category Content aber Subcategories vorhanden
        else if (!empty($subcategories)) {
            if ($post && $post->ID == -99) {
                // Das ist unser Fake-Post - ersetze komplett
                $content = '<div class="subcategories-section">';
                // Verwende den neuen Header Shortcode
                $content .= do_shortcode('[category_section_header]');
                $content .= do_shortcode('[subcategories_grid show_posts="yes" posts_per_category="3" show_description="yes"]');
                $content .= '</div>';
                $already_added = true;
            }
        }
        
        return $content;
    }
    
    /**
     * Versteckt den Query-Title bei Hauptkategorien
     */
    public function hide_query_title($block_content, $block, $instance) {
        if (is_category()) {
            $category = get_queried_object();
            $subcategories = get_categories([
                'parent' => $category->term_id,
                'hide_empty' => false
            ]);
            
            // Wenn Unterkategorien vorhanden, verstecke "Unsere Beiträge"
            if (!empty($subcategories)) {
                if (strpos($block_content, 'Unsere Beiträge') !== false) {
                    return '<!-- Query Title versteckt -->';
                }
            }
        }
        
        return $block_content;
    }
    
    /**
     * Versteckt den Query-Block bei Hauptkategorien
     */
    public function hide_posts_query($block_content, $block, $instance) {
        if (is_category()) {
            $category = get_queried_object();
            $subcategories = get_categories([
                'parent' => $category->term_id,
                'hide_empty' => false
            ]);
            
            // Wenn Unterkategorien vorhanden, verstecke Query
            if (!empty($subcategories)) {
                // Prüfe ob es der Posts-Query ist
                if (strpos($block_content, 'Unsere Beiträge') !== false ||
                    strpos($block_content, 'wp-block-post-template') !== false) {
                    return '<!-- Query versteckt für Hauptkategorien -->';
                }
            }
        }
        
        return $block_content;
    }
}

// Plugin initialisieren
new Add_Subcategories_To_Category();