<?php
/**
 * Plugin Name: Mega Menu Navigation
 * Description: Zeigt alle Unterkategorien mit ihren Posts in einem übersichtlichen Mega-Menü
 * Version: 1.0
 * Author: RIMAN GmbH
 */

if (!defined('ABSPATH')) exit;

class Mega_Menu_Navigation {
    
    public function __construct() {
        add_filter('render_block_core/navigation-submenu', array($this, 'create_mega_menu'), 10, 3);
        add_filter('render_block_core/navigation-link', array($this, 'create_mega_menu_for_link'), 10, 3);
        add_action('wp_head', array($this, 'add_mega_menu_styles'));
        add_action('wp_footer', array($this, 'add_mega_menu_script'));
    }
    
    /**
     * Erstellt Mega-Menü für Kategorie-Links
     */
    public function create_mega_menu_for_link($block_content, $block, $instance) {
        // Prüfe ob es ein Kategorie-Link ist
        if (!empty($block['attrs']['url']) && strpos($block['attrs']['url'], '/category/') !== false) {
            preg_match('/\/category\/([^\/]+)/', $block['attrs']['url'], $matches);
            
            if (!empty($matches[1])) {
                $category = get_category_by_slug($matches[1]);
                
                if ($category) {
                    // Prüfe ob es Unterkategorien gibt
                    $subcategories = get_categories(array(
                        'parent' => $category->term_id,
                        'hide_empty' => false,
                        'orderby' => 'name',
                        'order' => 'ASC'
                    ));
                    
                    if (!empty($subcategories)) {
                        // Erstelle Mega-Menü
                        $mega_menu_html = $this->generate_mega_menu($subcategories);
                        
                        // Konvertiere Link zu Submenu mit Mega-Menü - MIT klickbarem Link zur Kategorie
                        $block_content = sprintf(
                            '<li class="wp-block-navigation-item wp-block-navigation-submenu has-child has-mega-menu">
                                <a href="%s" class="wp-block-navigation-item__content mega-menu-toggle">
                                    <span class="wp-block-navigation-item__label">%s</span>
                                    <span class="wp-block-navigation__submenu-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none" role="img" aria-hidden="true" focusable="false">
                                            <path d="M1.50002 4L6.00002 8L10.5 4" stroke-width="1.5"></path>
                                        </svg>
                                    </span>
                                </a>
                                <div class="mega-menu-container">
                                    <div class="mega-menu-grid">
                                        %s
                                    </div>
                                </div>
                            </li>',
                            esc_url($block['attrs']['url']),
                            esc_html($block['attrs']['label'] ?? $category->name),
                            $mega_menu_html
                        );
                    }
                }
            }
        }
        
        return $block_content;
    }
    
    /**
     * Modifiziert bestehende Submenus
     */
    public function create_mega_menu($block_content, $block, $instance) {
        if (!empty($block['attrs']['url']) && strpos($block['attrs']['url'], '/category/') !== false) {
            preg_match('/\/category\/([^\/]+)/', $block['attrs']['url'], $matches);
            
            if (!empty($matches[1])) {
                $category = get_category_by_slug($matches[1]);
                
                if ($category) {
                    $subcategories = get_categories(array(
                        'parent' => $category->term_id,
                        'hide_empty' => false,
                        'orderby' => 'name',
                        'order' => 'ASC'
                    ));
                    
                    if (!empty($subcategories)) {
                        // Ersetze Standard-Submenu mit Mega-Menü
                        $mega_menu_html = $this->generate_mega_menu($subcategories);
                        
                        // Füge Mega-Menü-Klasse hinzu
                        $block_content = str_replace(
                            'wp-block-navigation-submenu',
                            'wp-block-navigation-submenu has-mega-menu',
                            $block_content
                        );
                        
                        // Ersetze Standard ul mit Mega-Menü
                        $block_content = preg_replace(
                            '/<ul class="wp-block-navigation__submenu-container">.*?<\/ul>/s',
                            '<div class="mega-menu-container"><div class="mega-menu-grid">' . $mega_menu_html . '</div></div>',
                            $block_content
                        );
                    }
                }
            }
        }
        
        return $block_content;
    }
    
    /**
     * Generiert das Mega-Menü HTML
     */
    private function generate_mega_menu($subcategories) {
        $html = '';
        $columns = array_chunk($subcategories, 2); // 2 Unterkategorien pro Spalte
        
        foreach ($subcategories as $subcat) {
            $html .= '<div class="mega-menu-column">';
            $html .= '<h3 class="mega-menu-title">' . esc_html($subcat->name) . '</h3>';
            
            // Hole Posts für diese Unterkategorie
            $posts = get_posts(array(
                'category' => $subcat->term_id,
                'posts_per_page' => 6,
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            if (!empty($posts)) {
                $html .= '<ul class="mega-menu-list">';
                foreach ($posts as $post) {
                    $html .= sprintf(
                        '<li class="mega-menu-item">
                            <a href="%s">%s</a>
                        </li>',
                        get_permalink($post->ID),
                        esc_html($post->post_title)
                    );
                }
                $html .= '</ul>';
            } else {
                $html .= '<p class="mega-menu-empty">Keine Beiträge vorhanden</p>';
            }
            
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * CSS für das Mega-Menü
     */
    public function add_mega_menu_styles() {
        ?>
        <style>
            /* Mega Menu Container */
            .has-mega-menu {
                position: relative;
            }
            
            .mega-menu-container {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                background: #ffffff;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 30px;
                min-width: 800px;
                z-index: 999999; /* Sehr hoher z-index für Überlagerung */
                margin-top: 0;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.2s ease, visibility 0.2s ease;
            }
            
            .mega-menu-container.active {
                display: block;
                opacity: 1;
                visibility: visible;
            }
            
            /* Klickbarer Link statt Button */
            .mega-menu-toggle {
                display: flex;
                align-items: center;
                text-decoration: none;
                color: inherit;
                padding: 0.5rem 1rem;
                cursor: pointer;
            }
            
            .mega-menu-toggle:hover {
                text-decoration: none;
            }
            
            /* Icon styling */
            .wp-block-navigation__submenu-icon {
                margin-left: 0.5rem;
                display: inline-flex;
                transition: transform 0.2s ease;
            }
            
            .has-mega-menu.is-open .wp-block-navigation__submenu-icon {
                transform: rotate(180deg);
            }
            
            /* Grid Layout */
            .mega-menu-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 40px;
                max-width: 1200px;
            }
            
            /* Column Styling */
            .mega-menu-column {
                min-width: 200px;
            }
            
            .mega-menu-title {
                font-size: 16px;
                font-weight: 700;
                color: #1e1e1e;
                margin: 0 0 15px 0;
                padding-bottom: 10px;
                border-bottom: 2px solid #007cba;
            }
            
            /* List Styling */
            .mega-menu-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .mega-menu-item {
                margin: 0;
                padding: 0;
            }
            
            .mega-menu-item a {
                display: block;
                padding: 8px 0;
                color: #666;
                text-decoration: none;
                font-size: 14px;
                transition: all 0.2s ease;
                position: relative;
                padding-left: 15px;
            }
            
            .mega-menu-item a:before {
                content: "→";
                position: absolute;
                left: 0;
                opacity: 0;
                transition: opacity 0.2s ease;
            }
            
            .mega-menu-item a:hover {
                color: #007cba;
                padding-left: 20px;
            }
            
            .mega-menu-item a:hover:before {
                opacity: 1;
            }
            
            .mega-menu-empty {
                color: #999;
                font-size: 13px;
                font-style: italic;
            }
            
            /* Mobile Responsive */
            @media (max-width: 900px) {
                .mega-menu-container {
                    position: static;
                    width: 100%;
                    min-width: auto;
                    box-shadow: none;
                    border: none;
                    padding: 20px;
                    margin: 0;
                }
                
                .mega-menu-grid {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }
                
                .has-mega-menu:hover .mega-menu-container {
                    display: none;
                }
                
                .has-mega-menu.is-open .mega-menu-container {
                    display: block;
                }
            }
            
            /* Animation */
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .mega-menu-container {
                animation: fadeIn 0.3s ease;
            }
            
            /* Hover-Effekt für bessere UX */
            .wp-block-navigation-submenu__toggle[aria-expanded="true"] + .mega-menu-container {
                display: block;
            }
            
            /* Dark Mode Support */
            @media (prefers-color-scheme: dark) {
                .mega-menu-container {
                    background: #1e1e1e;
                    border-color: #333;
                }
                
                .mega-menu-title {
                    color: #fff;
                    border-color: #007cba;
                }
                
                .mega-menu-item a {
                    color: #ccc;
                }
                
                .mega-menu-item a:hover {
                    color: #007cba;
                }
            }
        </style>
        <?php
    }
    
    /**
     * JavaScript für mobile und Desktop Interaktion
     */
    public function add_mega_menu_script() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const megaMenuItems = document.querySelectorAll('.has-mega-menu');
            let currentOpenMenu = null;
            let closeTimeout = null;
            let openTimeout = null;
            
            // Funktion zum Schließen aller Menüs
            function closeAllMenus() {
                megaMenuItems.forEach(item => {
                    const container = item.querySelector('.mega-menu-container');
                    if (container) {
                        container.classList.remove('active');
                        item.classList.remove('is-open');
                    }
                });
                currentOpenMenu = null;
            }
            
            // Funktion zum Öffnen eines Menüs
            function openMenu(item) {
                const container = item.querySelector('.mega-menu-container');
                if (container && container !== currentOpenMenu) {
                    // Schließe andere Menüs
                    closeAllMenus();
                    // Öffne dieses Menü
                    container.classList.add('active');
                    item.classList.add('is-open');
                    currentOpenMenu = container;
                }
            }
            
            megaMenuItems.forEach(item => {
                const container = item.querySelector('.mega-menu-container');
                const link = item.querySelector('.mega-menu-toggle');
                
                if (!container || !link) return;
                
                // Desktop: Hover-Verhalten
                if (window.innerWidth > 900) {
                    // Hover über Menü-Item
                    item.addEventListener('mouseenter', function() {
                        clearTimeout(closeTimeout);
                        clearTimeout(openTimeout);
                        // Kleine Verzögerung beim Öffnen für bessere UX
                        openTimeout = setTimeout(() => {
                            openMenu(item);
                        }, 50);
                    });
                    
                    // Verlasse Menü-Item
                    item.addEventListener('mouseleave', function(e) {
                        clearTimeout(openTimeout);
                        clearTimeout(closeTimeout);
                        
                        // Prüfe ob Maus zum Dropdown geht
                        closeTimeout = setTimeout(() => {
                            // Prüfe ob Maus über Item oder Container ist
                            const isOverItem = item.matches(':hover');
                            const isOverContainer = container.matches(':hover');
                            
                            if (!isOverItem && !isOverContainer) {
                                container.classList.remove('active');
                                item.classList.remove('is-open');
                                if (currentOpenMenu === container) {
                                    currentOpenMenu = null;
                                }
                            }
                        }, 200); // Größere Verzögerung für stabileres Verhalten
                    });
                    
                    // Link Click - navigiere zur Kategorie-Seite
                    link.addEventListener('click', function(e) {
                        // Lasse den Link normal funktionieren
                        // Browser navigiert zur href URL
                    });
                    
                    // Hover über Container selbst
                    container.addEventListener('mouseenter', function() {
                        clearTimeout(closeTimeout);
                        container.classList.add('active');
                        item.classList.add('is-open');
                    });
                    
                    container.addEventListener('mouseleave', function(e) {
                        clearTimeout(closeTimeout);
                        
                        closeTimeout = setTimeout(() => {
                            const isOverItem = item.matches(':hover');
                            const isOverContainer = container.matches(':hover');
                            
                            if (!isOverItem && !isOverContainer) {
                                container.classList.remove('active');
                                item.classList.remove('is-open');
                                if (currentOpenMenu === container) {
                                    currentOpenMenu = null;
                                }
                            }
                        }, 200);
                    });
                }
                
                // Mobile: Click-Toggle
                if (window.innerWidth <= 900) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault(); // Verhindere Navigation auf Mobile
                        
                        if (container.classList.contains('active')) {
                            container.classList.remove('active');
                            item.classList.remove('is-open');
                            currentOpenMenu = null;
                        } else {
                            closeAllMenus();
                            openMenu(item);
                        }
                    });
                }
            });
            
            // Schließe Menüs bei Klick außerhalb
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.has-mega-menu')) {
                    closeAllMenus();
                }
            });
            
            // Schließe Menüs bei ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeAllMenus();
                }
            });
        });
        </script>
        <?php
    }
}

// Plugin initialisieren
new Mega_Menu_Navigation();