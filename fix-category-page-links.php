<?php
/**
 * Fix-Skript für Category-Page Verknüpfungen
 * Stellt die Verknüpfung zwischen gleichnamigen Kategorien und Seiten her
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

echo "=== Fix Category-Page Links ===\n\n";

// Hole alle Kategorien
$categories = get_terms(array(
    'taxonomy' => 'category',
    'hide_empty' => false,
    'exclude' => get_cat_ID('uncategorized')
));

$fixed_count = 0;
$already_linked = 0;

foreach ($categories as $category) {
    echo "Prüfe Kategorie: {$category->name} (ID: {$category->term_id})\n";
    
    // Prüfe ob bereits eine Verknüpfung existiert
    $existing_page_id = get_term_meta($category->term_id, '_linked_page_id', true);
    
    if ($existing_page_id) {
        $page = get_post($existing_page_id);
        if ($page) {
            echo "  ✓ Bereits verknüpft mit Seite: {$page->post_title} (ID: {$existing_page_id})\n";
            $already_linked++;
            continue;
        }
    }
    
    // Suche nach gleichnamiger Seite
    $pages = get_posts(array(
        'post_type' => 'page',
        'name' => $category->slug,
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ));
    
    if (empty($pages)) {
        // Versuche es mit dem Namen statt Slug
        $pages = get_posts(array(
            'post_type' => 'page',
            'title' => $category->name,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
    }
    
    if (empty($pages)) {
        // Noch ein Versuch mit LIKE-Suche
        global $wpdb;
        $page_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_type = 'page' 
             AND post_status = 'publish' 
             AND post_title LIKE %s 
             LIMIT 1",
            '%' . $wpdb->esc_like($category->name) . '%'
        ));
        
        if ($page_id) {
            $pages = array(get_post($page_id));
        }
    }
    
    if (!empty($pages)) {
        $page = $pages[0];
        
        // Stelle die Verknüpfung her
        update_term_meta($category->term_id, '_linked_page_id', $page->ID);
        update_post_meta($page->ID, '_linked_category_id', $category->term_id);
        
        echo "  ✅ Verknüpfung hergestellt mit Seite: {$page->post_title} (ID: {$page->ID})\n";
        $fixed_count++;
    } else {
        echo "  ⚠ Keine passende Seite gefunden für Kategorie: {$category->name}\n";
    }
}

echo "\n=== Zusammenfassung ===\n";
echo "Bereits verknüpft: {$already_linked}\n";
echo "Neu verknüpft: {$fixed_count}\n";
echo "Gesamt Kategorien: " . count($categories) . "\n";

// Teste eine Verknüpfung
if (count($categories) > 0) {
    $test_category = $categories[0];
    $linked_page_id = get_term_meta($test_category->term_id, '_linked_page_id', true);
    
    if ($linked_page_id) {
        echo "\n✅ Test erfolgreich: Kategorie '{$test_category->name}' ist mit Seite ID {$linked_page_id} verknüpft.\n";
    }
}

echo "\n✅ Fix abgeschlossen!\n";