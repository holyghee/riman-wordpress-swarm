<?php
/**
 * Plugin Name: Navigation Subcategories Dropdown
 * Description: Fügt automatisch Unterkategorien als Dropdown-Menüs zur Navigation hinzu
 * Version: 1.0
 * Author: RIMAN GmbH
 */

if (!defined('ABSPATH')) exit;

/**
 * Fügt Unterkategorien automatisch zu Kategorie-Menüpunkten hinzu
 */
function add_subcategories_to_nav_menu($items, $args) {
    $new_items = array();
    
    foreach ($items as $item) {
        $new_items[] = $item;
        
        // Prüfe ob es ein Kategorie-Menüpunkt ist
        if ($item->object == 'category') {
            $category_id = $item->object_id;
            
            // Hole Unterkategorien
            $subcategories = get_categories(array(
                'parent' => $category_id,
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            
            if (!empty($subcategories)) {
                foreach ($subcategories as $subcategory) {
                    // Erstelle neuen Menüpunkt für Unterkategorie
                    $sub_item = clone $item;
                    $sub_item->ID = 'subcategory-' . $subcategory->term_id;
                    $sub_item->db_id = 0;
                    $sub_item->object_id = $subcategory->term_id;
                    $sub_item->post_parent = 0;
                    $sub_item->menu_item_parent = $item->ID;
                    $sub_item->title = $subcategory->name;
                    $sub_item->url = get_category_link($subcategory->term_id);
                    $sub_item->classes[] = 'subcategory-menu-item';
                    
                    $new_items[] = $sub_item;
                }
            }
        }
    }
    
    return $new_items;
}
add_filter('wp_nav_menu_objects', 'add_subcategories_to_nav_menu', 10, 2);

/**
 * Füge CSS für Dropdown-Styling hinzu
 */
function add_subcategories_dropdown_styles() {
    ?>
    <style>
        /* Dropdown-Styling für Unterkategorien */
        .wp-block-navigation .has-child > .wp-block-navigation__submenu-container {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: var(--wp--preset--color--base, #ffffff);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid var(--wp--preset--color--accent-6, #e0e0e0);
            z-index: 1000;
            min-width: 200px;
        }
        
        .wp-block-navigation .has-child:hover > .wp-block-navigation__submenu-container,
        .wp-block-navigation .has-child:focus-within > .wp-block-navigation__submenu-container {
            display: block;
        }
        
        /* Indikator für Dropdown */
        .wp-block-navigation .has-child > a::after {
            content: ' ▾';
            font-size: 0.8em;
            margin-left: 0.3em;
        }
        
        /* Submenu Items */
        .wp-block-navigation__submenu-container .wp-block-navigation-item {
            display: block;
            width: 100%;
        }
        
        .wp-block-navigation__submenu-container .wp-block-navigation-item__content,
        .wp-block-navigation__submenu-container a {
            padding: 10px 20px;
            display: block;
            width: 100%;
            border-bottom: 1px solid var(--wp--preset--color--accent-6, #f0f0f0);
            color: var(--wp--preset--color--contrast, #000000) !important;
            text-decoration: none;
            background: transparent !important;
        }
        
        .wp-block-navigation__submenu-container .wp-block-navigation-item__content:hover,
        .wp-block-navigation__submenu-container a:hover {
            background: var(--wp--preset--color--accent-6, #f8f8f8) !important;
            color: var(--wp--preset--color--primary, #007cba) !important;
        }
        
        /* Fix für leere Kategorien und Subcategories Block Konflikt */
        .wp-block-navigation__submenu-container .wp-block-navigation-item__label,
        .wp-block-navigation__submenu-container span {
            color: inherit !important;
        }
        
        /* Override Subcategories Block styles in navigation */
        body.category .wp-block-navigation .subcategory-link,
        .wp-block-navigation .subcategory-link {
            color: var(--wp--preset--color--contrast, #000000) !important;
            background: transparent !important;
            padding: 0 !important;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .wp-block-navigation .has-child > .wp-block-navigation__submenu-container {
                position: static;
                width: 100%;
                box-shadow: none;
                border: none;
                background: rgba(0,0,0,0.05);
                margin-left: 20px;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'add_subcategories_dropdown_styles', 999);

/**
 * Füge JavaScript für Dropdown-Funktionalität hinzu
 */
function add_subcategories_dropdown_script() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fix für Subcategories Block CSS Konflikt
        const dropdownLinks = document.querySelectorAll('.wp-block-navigation__submenu-container a');
        dropdownLinks.forEach(function(link) {
            link.style.color = '#000000';
            link.style.backgroundColor = 'transparent';
            // Hover Effect
            link.addEventListener('mouseenter', function() {
                this.style.color = '#007cba';
                this.style.backgroundColor = '#f8f8f8';
            });
            link.addEventListener('mouseleave', function() {
                this.style.color = '#000000';
                this.style.backgroundColor = 'transparent';
            });
        });
        
        // Finde alle Kategorie-Links mit Unterkategorien
        const categoryLinks = document.querySelectorAll('.wp-block-navigation-item');
        
        categoryLinks.forEach(function(item) {
            // Prüfe ob dieser Punkt Unterpunkte hat
            const hasSubmenu = item.querySelector('.wp-block-navigation__submenu-container');
            if (hasSubmenu) {
                item.classList.add('has-child');
                
                // Touch-Support für Mobile
                const link = item.querySelector('a');
                if (link) {
                    let touchTimer;
                    link.addEventListener('touchstart', function(e) {
                        touchTimer = setTimeout(function() {
                            // Bei langem Touch öffne Link
                        }, 500);
                    });
                    
                    link.addEventListener('touchend', function(e) {
                        clearTimeout(touchTimer);
                        // Bei kurzem Touch toggle Submenu
                        if (window.innerWidth <= 768) {
                            const submenu = item.querySelector('.wp-block-navigation__submenu-container');
                            if (submenu) {
                                const isVisible = submenu.style.display === 'block';
                                submenu.style.display = isVisible ? 'none' : 'block';
                                e.preventDefault();
                            }
                        }
                    });
                }
            }
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'add_subcategories_dropdown_script');

/**
 * Filter für Block-basierte Navigation
 */
function modify_navigation_block_output($block_content, $block) {
    if ($block['blockName'] !== 'core/navigation') {
        return $block_content;
    }
    
    // Hole alle Hauptkategorien aus der Navigation
    preg_match_all('/category\/([^"\/]+)/', $block_content, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $category_slug) {
            $category = get_category_by_slug($category_slug);
            if ($category) {
                // Hole Unterkategorien
                $subcategories = get_categories(array(
                    'parent' => $category->term_id,
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC'
                ));
                
                if (!empty($subcategories)) {
                    // Erstelle Submenu HTML mit Inline-Styles
                    $submenu_html = '<ul class="wp-block-navigation__submenu-container" style="background: white !important;">';
                    foreach ($subcategories as $subcat) {
                        $submenu_html .= sprintf(
                            '<li class="wp-block-navigation-item wp-block-navigation-link">
                                <a class="wp-block-navigation-item__content" href="%s" style="color: #000000 !important; text-decoration: none !important; display: block !important; padding: 10px 20px !important;">
                                    <span class="wp-block-navigation-item__label" style="color: #000000 !important;">%s</span>
                                </a>
                            </li>',
                            esc_url(get_category_link($subcat->term_id)),
                            esc_html($subcat->name)
                        );
                    }
                    $submenu_html .= '</ul>';
                    
                    // Füge Submenu nach dem Kategorie-Link ein
                    $search = 'href="' . get_category_link($category->term_id) . '">' . 
                             '<span class="wp-block-navigation-item__label">' . $category->name . '</span></a>';
                    $replace = 'href="' . get_category_link($category->term_id) . '">' . 
                              '<span class="wp-block-navigation-item__label">' . $category->name . '</span></a>' . 
                              $submenu_html;
                    
                    $block_content = str_replace($search, $replace, $block_content);
                }
            }
        }
    }
    
    return $block_content;
}
add_filter('render_block', 'modify_navigation_block_output', 10, 2);