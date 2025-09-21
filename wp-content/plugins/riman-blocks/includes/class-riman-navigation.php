<?php
/**
 * RIMAN Navigation - Automatische Hierarchie-Navigation
 * Erstellt automatisch Dropdown-Menüs für RIMAN-Seiten basierend auf der Seitenhierarchie
 */

if (!defined('ABSPATH')) exit;

class RIMAN_Navigation {

    public function __construct() {
        // KOMPLETT DEAKTIVIERT - Menü soll original bleiben
        // Keine Navigation-Filter mehr aktiv
    }

    /**
     * Fügt automatisch Unterseiten zu RIMAN Hauptseiten-Menüpunkten hinzu
     */
    public function add_riman_subpages_to_menu($items, $args) {
        $new_items = [];

        foreach ($items as $item) {
            $new_items[] = $item;

            // Prüfe ob es ein riman_seiten Menüpunkt ist
            if ($item->object == 'riman_seiten') {
                $page_id = $item->object_id;
                $page = get_post($page_id);

                if ($page && $page->post_parent == 0) {
                    // Hole alle direkten Unterseiten
                    $unterseiten = get_posts([
                        'post_type' => 'riman_seiten',
                        'post_parent' => $page_id,
                        'post_status' => 'publish',
                        'numberposts' => -1,
                        'orderby' => 'menu_order title',
                        'order' => 'ASC'
                    ]);

                    if (!empty($unterseiten)) {
                        // Füge has-children Klasse zum Hauptelement hinzu
                        if (!is_array($item->classes)) {
                            $item->classes = [];
                        }
                        $item->classes[] = 'menu-item-has-children';

                        // Erstelle Sub-Menü Items
                        foreach ($unterseiten as $unterseite) {
                            $sub_item = $this->create_menu_item($unterseite, $item->ID);
                            $new_items[] = $sub_item;
                        }
                    }
                }
            }
        }

        return $new_items;
    }

    /**
     * Modifiziert Block Navigation für RIMAN Seiten Dropdowns
     */
    public function modify_block_navigation($block_content, $block) {
        // Prüfe ob RIMAN Seiten im Navigation Block sind
        if (strpos($block_content, 'riman_seiten') === false) {
            return $block_content;
        }

        // Parse HTML
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $block_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Finde alle Navigation Items
        $xpath = new DOMXPath($dom);
        $nav_items = $xpath->query('//li[contains(@class, "wp-block-navigation-item")]');

        foreach ($nav_items as $nav_item) {
            $link = $xpath->query('.//a', $nav_item)->item(0);

            if ($link && $this->is_riman_page_url($link->getAttribute('href'))) {
                $page_id = $this->get_page_id_from_url($link->getAttribute('href'));

                if ($page_id) {
                    $page = get_post($page_id);

                    if ($page && $page->post_type === 'riman_seiten' && $page->post_parent == 0) {
                        // Hole Unterseiten
                        $unterseiten = get_posts([
                            'post_type' => 'riman_seiten',
                            'post_parent' => $page_id,
                            'post_status' => 'publish',
                            'numberposts' => -1,
                            'orderby' => 'menu_order title',
                            'order' => 'ASC'
                        ]);

                        if (!empty($unterseiten)) {
                            // Füge CSS-Klasse hinzu
                            $classes = $nav_item->getAttribute('class');
                            $nav_item->setAttribute('class', $classes . ' has-child');

                            // Erstelle Submenu
                            $submenu = $dom->createElement('ul');
                            $submenu->setAttribute('class', 'wp-block-navigation-submenu');

                            foreach ($unterseiten as $unterseite) {
                                $sub_li = $dom->createElement('li');
                                $sub_li->setAttribute('class', 'wp-block-navigation-item');

                                $sub_a = $dom->createElement('a');
                                $sub_a->setAttribute('href', get_permalink($unterseite->ID));
                                $sub_a->setAttribute('class', 'wp-block-navigation-item__content');
                                $sub_a->textContent = str_replace(' - Riman GmbH', '', $unterseite->post_title);

                                $sub_li->appendChild($sub_a);
                                $submenu->appendChild($sub_li);
                            }

                            $nav_item->appendChild($submenu);
                        }
                    }
                }
            }
        }

        return $dom->saveHTML();
    }

    /**
     * Prüft ob eine URL eine RIMAN Seite ist
     */
    private function is_riman_page_url($url) {
        // Einfache Prüfung - könnte verfeinert werden
        $page_id = $this->get_page_id_from_url($url);
        if ($page_id) {
            $page = get_post($page_id);
            return $page && $page->post_type === 'riman_seiten';
        }
        return false;
    }

    /**
     * Extrahiert Post ID aus URL
     */
    private function get_page_id_from_url($url) {
        // WordPress URL to Post ID
        $post_id = url_to_postid($url);
        return $post_id ? $post_id : null;
    }

    /**
     * Holt die komplette Seitenhierarchie für eine Hauptseite
     */
    private function get_riman_page_hierarchy($parent_id, $parent_menu_item_id) {
        $hierarchy_items = [];
        $menu_item_id = 1000000 + $parent_menu_item_id; // Eindeutige IDs

        // 1. Hole alle direkten Unterseiten (Level 1)
        $unterseiten = get_posts([
            'post_type' => 'riman_seiten',
            'post_parent' => $parent_id,
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'menu_order title',
            'order' => 'ASC'
        ]);

        foreach ($unterseiten as $unterseite) {
            // Erstelle Menüpunkt für Unterseite
            $menu_item = $this->create_menu_item($unterseite, $menu_item_id++, $parent_menu_item_id);
            $hierarchy_items[] = $menu_item;

            // 2. Hole Detailseiten dieser Unterseite (Level 2)
            $detailseiten = get_posts([
                'post_type' => 'riman_seiten',
                'post_parent' => $unterseite->ID,
                'post_status' => 'publish',
                'numberposts' => -1,
                'orderby' => 'menu_order title',
                'order' => 'ASC'
            ]);

            foreach ($detailseiten as $detailseite) {
                $detail_item = $this->create_menu_item($detailseite, $menu_item_id++, $menu_item->ID);
                $hierarchy_items[] = $detail_item;
            }
        }

        return $hierarchy_items;
    }

    /**
     * Erstellt ein Menüpunkt-Objekt
     */
    private function create_menu_item($post, $parent_menu_item_id) {
        static $menu_item_counter = 100000;

        $menu_item = new stdClass();

        $menu_item->ID = $menu_item_counter++;
        $menu_item->db_id = $menu_item->ID;
        $menu_item->menu_item_parent = $parent_menu_item_id;
        $menu_item->object_id = $post->ID;
        $menu_item->object = 'riman_seiten';
        $menu_item->type = 'post_type';
        $menu_item->type_label = 'RIMAN Seite';
        $menu_item->title = str_replace(' - Riman GmbH', '', $post->post_title);
        $menu_item->url = get_permalink($post->ID);
        $menu_item->target = '';
        $menu_item->attr_title = '';
        $menu_item->description = '';
        $menu_item->classes = ['menu-item', 'menu-item-riman-subpage'];
        $menu_item->xfn = '';

        return $menu_item;
    }

    /**
     * Hilfsfunktion: Ermittle den Seitentyp einer RIMAN-Seite
     */
    private function get_seitentyp($post_id) {
        $terms = get_the_terms($post_id, 'seitentyp');

        if ($terms && !is_wp_error($terms)) {
            return $terms[0]->slug;
        }

        return '';
    }

    /**
     * Fügt CSS-Klassen für die Navigation hinzu
     */
    public function add_navigation_styles() {
        ?>
        <style>
        /* RIMAN Navigation Styles */
        .menu-item-riman-info {
            border-left: 3px solid #007cba;
        }

        .menu-item-riman-info > a {
            font-style: italic;
            color: #007cba;
        }

        .seitentyp-hauptseite > a {
            font-weight: 600;
        }

        .seitentyp-unterseite > a {
            padding-left: 1rem;
        }

        .seitentyp-detailseite > a {
            padding-left: 2rem;
            font-size: 0.9rem;
        }

        /* WordPress Block Navigation Dropdown Styles */
        .wp-block-navigation .wp-block-navigation-item.has-child {
            position: relative;
        }

        .wp-block-navigation .wp-block-navigation-submenu {
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 250px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            padding: 0.5rem 0;
        }

        .wp-block-navigation .wp-block-navigation-item:hover .wp-block-navigation-submenu,
        .wp-block-navigation .wp-block-navigation-item:focus-within .wp-block-navigation-submenu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .wp-block-navigation .wp-block-navigation-submenu .wp-block-navigation-item {
            width: 100%;
            border-bottom: 1px solid #f0f0f0;
        }

        .wp-block-navigation .wp-block-navigation-submenu .wp-block-navigation-item:last-child {
            border-bottom: none;
        }

        .wp-block-navigation .wp-block-navigation-submenu .wp-block-navigation-item a {
            padding: 0.75rem 1rem;
            display: block;
            color: #333;
            text-decoration: none;
            transition: background-color 0.2s ease;
            white-space: nowrap;
        }

        .wp-block-navigation .wp-block-navigation-submenu .wp-block-navigation-item a:hover {
            background-color: #f8f9fa;
            color: #007cba;
        }

        /* Arrow indicators for dropdown parents */
        .wp-block-navigation .has-child > a::after {
            content: '▼';
            font-size: 0.8em;
            margin-left: 0.5rem;
            transition: transform 0.3s ease;
        }

        .wp-block-navigation .has-child:hover > a::after {
            transform: rotate(180deg);
        }

        /* Legacy menu support */
        .menu-item-has-children {
            position: relative;
        }

        .menu-item-has-children .sub-menu {
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 250px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            padding: 0.5rem 0;
        }

        .menu-item-has-children:hover .sub-menu,
        .menu-item-has-children:focus-within .sub-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .menu-item-has-children .sub-menu .menu-item {
            border-bottom: 1px solid #f0f0f0;
            width: 100%;
        }

        .menu-item-has-children .sub-menu .menu-item:last-child {
            border-bottom: none;
        }

        .menu-item-has-children .sub-menu a {
            padding: 0.75rem 1rem;
            display: block;
            transition: background-color 0.2s ease;
            color: #333;
            text-decoration: none;
            white-space: nowrap;
        }

        .menu-item-has-children .sub-menu a:hover {
            background-color: #f8f9fa;
            color: #007cba;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .wp-block-navigation .wp-block-navigation-submenu,
            .menu-item-has-children .sub-menu {
                position: static;
                opacity: 1;
                visibility: visible;
                transform: none;
                box-shadow: none;
                border: none;
                background: transparent;
                padding-left: 1rem;
            }
        }
        </style>
        <?php
    }
}

// Initialisieren
add_action('init', function() {
    new RIMAN_Navigation();
});

// ALLES DEAKTIVIERT - Keine CSS oder JavaScript Eingriffe
// add_action('wp_head', function() {
//     $nav = new RIMAN_Navigation();
//     $nav->add_navigation_styles();
// });

// Kein JavaScript - alles deaktiviert
?>