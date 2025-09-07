<?php
/**
 * Plugin Name: Mega Menu Fixed
 * Description: Zeigt alle Unterkategorien mit ihren Posts in einem funktionierenden Mega-Menü
 * Version: 3.0
 * Author: RIMAN GmbH
 */

if (!defined('ABSPATH')) exit;

class Mega_Menu_Fixed {
    
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
                        
                        // Konvertiere Link zu Submenu mit Mega-Menü
                        $block_content = sprintf(
                            '<li class="wp-block-navigation-item wp-block-navigation-submenu has-child has-mega-menu">
                                <button class="wp-block-navigation-item__content wp-block-navigation-submenu__toggle" aria-expanded="false" data-category-url="%s">
                                    <span class="wp-block-navigation-item__label">%s</span>
                                    <span class="wp-block-navigation__submenu-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none" role="img" aria-hidden="true" focusable="false">
                                            <path d="M1.50002 4L6.00002 8L10.5 4" stroke-width="1.5"></path>
                                        </svg>
                                    </span>
                                </button>
                                <div class="mega-menu-container">
                                    <div class="mega-menu-inner">
                                        <div class="mega-menu-header">
                                            <a href="%s" class="mega-menu-main-link">Alle %s anzeigen →</a>
                                        </div>
                                        <div class="mega-menu-grid">
                                            %s
                                        </div>
                                    </div>
                                </div>
                            </li>',
                            esc_url($block['attrs']['url']),
                            esc_html($block['attrs']['label'] ?? $category->name),
                            esc_url($block['attrs']['url']),
                            esc_html($category->name),
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
                        // Füge Mega-Menü-Klasse hinzu
                        $block_content = str_replace(
                            'wp-block-navigation-submenu',
                            'wp-block-navigation-submenu has-mega-menu',
                            $block_content
                        );
                        
                        // Füge data-category-url zum Button hinzu
                        $block_content = str_replace(
                            'wp-block-navigation-submenu__toggle"',
                            'wp-block-navigation-submenu__toggle" data-category-url="' . esc_url($block['attrs']['url']) . '"',
                            $block_content
                        );
                        
                        // Generiere Mega-Menü HTML
                        $mega_menu_html = $this->generate_mega_menu($subcategories);
                        
                        // Erstelle neues Mega-Menü Container HTML
                        $mega_menu_container = sprintf(
                            '<div class="mega-menu-container">
                                <div class="mega-menu-inner">
                                    <div class="mega-menu-header">
                                        <a href="%s" class="mega-menu-main-link">Alle %s anzeigen →</a>
                                    </div>
                                    <div class="mega-menu-grid">
                                        %s
                                    </div>
                                </div>
                            </div>',
                            esc_url($block['attrs']['url']),
                            esc_html($category->name),
                            $mega_menu_html
                        );
                        
                        // Ersetze Standard ul mit Mega-Menü
                        $block_content = preg_replace(
                            '/<ul class="wp-block-navigation__submenu-container">.*?<\/ul>/s',
                            $mega_menu_container,
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
        
        foreach ($subcategories as $subcat) {
            $html .= '<div class="mega-menu-column">';
            $html .= '<h3 class="mega-menu-title">';
            $html .= '<a href="' . esc_url(get_category_link($subcat->term_id)) . '">' . esc_html($subcat->name) . '</a>';
            $html .= '</h3>';
            
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
                min-width: 800px;
                max-width: 1200px;
                z-index: 999999;
                margin-top: 0;
            }
            
            .mega-menu-container.is-open {
                display: block;
            }
            
            .mega-menu-inner {
                padding: 20px 30px 30px;
            }
            
            /* Header mit Hauptkategorie-Link */
            .mega-menu-header {
                padding-bottom: 15px;
                margin-bottom: 20px;
                border-bottom: 2px solid #f0f0f0;
            }
            
            .mega-menu-main-link {
                font-size: 14px;
                font-weight: 600;
                color: #007cba;
                text-decoration: none;
            }
            
            .mega-menu-main-link:hover {
                text-decoration: underline;
            }
            
            /* Grid Layout */
            .mega-menu-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 30px;
            }
            
            /* Column Styling */
            .mega-menu-column {
                min-width: 180px;
            }
            
            .mega-menu-title {
                font-size: 15px;
                font-weight: 600;
                margin: 0 0 12px 0;
                padding-bottom: 8px;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .mega-menu-title a {
                color: #1e1e1e;
                text-decoration: none;
            }
            
            .mega-menu-title a:hover {
                color: #007cba;
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
                padding: 6px 0;
                color: #666;
                text-decoration: none;
                font-size: 13px;
                transition: all 0.2s ease;
                position: relative;
                padding-left: 12px;
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
                padding-left: 18px;
            }
            
            .mega-menu-item a:hover:before {
                opacity: 1;
            }
            
            .mega-menu-empty {
                color: #999;
                font-size: 13px;
                font-style: italic;
                margin: 5px 0;
            }
            
            /* Button Styling */
            .wp-block-navigation-submenu__toggle {
                cursor: pointer;
                background: none;
                border: none;
                display: flex;
                align-items: center;
                padding: 0.5rem 1rem;
                color: inherit;
                font-size: inherit;
                font-family: inherit;
            }
            
            .wp-block-navigation-submenu__toggle:hover {
                color: #007cba;
            }
            
            /* Mobile Responsive */
            @media (max-width: 900px) {
                .mega-menu-container {
                    position: static;
                    width: 100%;
                    min-width: auto;
                    box-shadow: none;
                    border: none;
                    border-radius: 0;
                    background: #f9f9f9;
                }
                
                .mega-menu-grid {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }
            }
            
            /* Dark Mode Support */
            @media (prefers-color-scheme: dark) {
                .mega-menu-container {
                    background: #1e1e1e;
                    border-color: #333;
                }
                
                .mega-menu-title a {
                    color: #fff;
                }
                
                .mega-menu-item a {
                    color: #ccc;
                }
                
                .mega-menu-item a:hover {
                    color: #007cba;
                }
                
                .mega-menu-header {
                    border-color: #333;
                }
            }
        </style>
        <?php
    }
    
    /**
     * JavaScript für Desktop und Mobile Interaktion
     */
    public function add_mega_menu_script() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const megaMenuItems = document.querySelectorAll('.has-mega-menu');
            let currentOpenMenu = null;
            let closeTimeout = null;
            
            // Funktion zum Schließen aller Menüs
            function closeAllMenus() {
                megaMenuItems.forEach(item => {
                    const container = item.querySelector('.mega-menu-container');
                    if (container) {
                        container.classList.remove('is-open');
                        const button = item.querySelector('.wp-block-navigation-submenu__toggle');
                        if (button) {
                            button.setAttribute('aria-expanded', 'false');
                        }
                    }
                });
                currentOpenMenu = null;
            }
            
            // Funktion zum Öffnen eines Menüs
            function openMenu(item) {
                const container = item.querySelector('.mega-menu-container');
                const button = item.querySelector('.wp-block-navigation-submenu__toggle');
                
                if (container && container !== currentOpenMenu) {
                    closeAllMenus();
                    container.classList.add('is-open');
                    if (button) {
                        button.setAttribute('aria-expanded', 'true');
                    }
                    currentOpenMenu = container;
                }
            }
            
            megaMenuItems.forEach(item => {
                const container = item.querySelector('.mega-menu-container');
                const button = item.querySelector('.wp-block-navigation-submenu__toggle');
                
                if (!container) return;
                
                // Desktop: Hover-Verhalten
                if (window.innerWidth > 900) {
                    // Hover auf dem Menü-Item
                    item.addEventListener('mouseenter', function() {
                        clearTimeout(closeTimeout);
                        openMenu(item);
                    });
                    
                    item.addEventListener('mouseleave', function() {
                        clearTimeout(closeTimeout);
                        closeTimeout = setTimeout(() => {
                            if (!item.matches(':hover')) {
                                const container = item.querySelector('.mega-menu-container');
                                if (container) {
                                    container.classList.remove('is-open');
                                }
                                if (currentOpenMenu === container) {
                                    currentOpenMenu = null;
                                }
                            }
                        }, 100);
                    });
                    
                    // Klick auf Button navigiert zur Kategorie
                    if (button) {
                        button.addEventListener('click', function(e) {
                            e.preventDefault();
                            const categoryUrl = this.getAttribute('data-category-url');
                            if (categoryUrl) {
                                window.location.href = categoryUrl;
                            }
                        });
                    }
                }
                
                // Mobile: Toggle beim Klick
                if (window.innerWidth <= 900 && button) {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        if (container.classList.contains('is-open')) {
                            container.classList.remove('is-open');
                            this.setAttribute('aria-expanded', 'false');
                        } else {
                            closeAllMenus();
                            container.classList.add('is-open');
                            this.setAttribute('aria-expanded', 'true');
                            currentOpenMenu = container;
                        }
                    });
                }
            });
            
            // Schließe Menüs beim Klick außerhalb
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.has-mega-menu')) {
                    closeAllMenus();
                }
            });
            
            // ESC zum Schließen
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
new Mega_Menu_Fixed();