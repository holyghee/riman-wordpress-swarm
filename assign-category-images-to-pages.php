<?php
/**
 * Assign Category Images to Linked Pages
 * Setzt die gleichen Bilder für verknüpfte Seiten wie ihre Kategorien haben
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

echo "=== Kategorie-Bilder zu Seiten zuordnen ===\n\n";

// Hole alle Kategorien
$categories = get_terms(array(
    'taxonomy' => 'category',
    'hide_empty' => false
));

$updated = 0;
$skipped = 0;

foreach ($categories as $category) {
    // Prüfe ob Kategorie ein Bild hat
    $category_image_id = get_term_meta($category->term_id, '_thumbnail_id', true);
    
    if (!$category_image_id) {
        echo "⚠ Kategorie '{$category->name}' hat kein Bild\n";
        continue;
    }
    
    // Hole verknüpfte Seite
    $linked_page_id = get_term_meta($category->term_id, '_linked_page_id', true);
    
    if (!$linked_page_id) {
        echo "ℹ Kategorie '{$category->name}' hat keine verknüpfte Seite\n";
        continue;
    }
    
    $page = get_post($linked_page_id);
    if (!$page) {
        echo "❌ Verknüpfte Seite nicht gefunden für Kategorie '{$category->name}'\n";
        continue;
    }
    
    // Prüfe ob Seite bereits ein Bild hat
    $page_image_id = get_post_thumbnail_id($linked_page_id);
    
    if ($page_image_id) {
        echo "✓ Seite '{$page->post_title}' hat bereits ein Bild\n";
        $skipped++;
        continue;
    }
    
    // Setze das gleiche Bild für die Seite
    set_post_thumbnail($linked_page_id, $category_image_id);
    echo "✅ Bild zugeordnet: '{$page->post_title}' <- Kategorie '{$category->name}'\n";
    $updated++;
}

// Zusätzlich: Basierend auf Namen zuordnen (falls Verknüpfung fehlt)
echo "\n=== Zusätzliche Zuordnung nach Namen ===\n";

// Mapping von Kategorienamen zu Seitentiteln
$name_mapping = [
    'Rückbauplanung' => 'Rückbauplanung',
    'Ausschreibung' => 'Ausschreibung', 
    'Durchführung' => 'Durchführung',
    'Entsorgung' => 'Entsorgung',
    'Erkundung' => 'Erkundung: Fundament der Sanierung',
    'Sanierungsplanung' => 'Sanierungsplanung',
    'Bodensanierung' => 'Bodensanierung',
    'Grundwassersanierung' => 'Grundwassersanierung',
    'Monitoring' => 'Monitoring',
    'Asbest' => 'Asbestsanierung',
    'KMF' => 'KMF-Sanierung',
    'PAK' => 'PAK/Teer',
    'PCB' => 'PCB-Sanierung',
    'Schwermetalle' => 'Schwermetalle',
    'SiGeKo-Planung' => 'SiGeKo in der Planungsphase',
    'SiGeKo-Ausführung' => 'SiGeKo in der Ausführungsphase',
    'Arbeitsschutz' => 'Arbeitsschutzbetreuung',
    'Gefährdungsbeurteilung' => 'Gefährdungsbeurteilung',
    'Notfallmanagement' => 'Notfallmanagement',
    'Baumediation' => 'Baumediation',
    'Projektberatung' => 'Projektberatung',
    'Gutachten' => 'Gutachten und Expertisen',
    'Schulungen' => 'Schulungen',
    'Compliance' => 'Compliance'
];

foreach ($name_mapping as $category_name => $page_title) {
    // Hole Kategorie
    $term = get_term_by('name', $category_name, 'category');
    if (!$term) {
        continue;
    }
    
    // Hole Kategorie-Bild
    $category_image_id = get_term_meta($term->term_id, '_thumbnail_id', true);
    if (!$category_image_id) {
        // Versuche Bild basierend auf Slug zu finden
        $attachment = get_page_by_title($category_name, OBJECT, 'attachment');
        if ($attachment) {
            $category_image_id = $attachment->ID;
            // Setze auch für Kategorie
            update_term_meta($term->term_id, '_thumbnail_id', $category_image_id);
            echo "✅ Bild für Kategorie '{$category_name}' gesetzt\n";
        } else {
            continue;
        }
    }
    
    // Hole Seite
    $page = get_page_by_title($page_title);
    if (!$page) {
        // Versuche exakte Übereinstimmung mit Kategoriename
        $page = get_page_by_title($category_name);
        if (!$page) {
            echo "⚠ Seite '{$page_title}' nicht gefunden\n";
            continue;
        }
    }
    
    // Prüfe ob Seite bereits ein Bild hat
    $page_image_id = get_post_thumbnail_id($page->ID);
    if ($page_image_id) {
        echo "✓ Seite '{$page->post_title}' hat bereits ein Bild\n";
        $skipped++;
        continue;
    }
    
    // Setze Bild
    set_post_thumbnail($page->ID, $category_image_id);
    echo "✅ Bild zugeordnet: Seite '{$page->post_title}' <- Kategorie '{$category_name}'\n";
    $updated++;
}

echo "\n=== Zusammenfassung ===\n";
echo "Seiten mit neuem Bild: $updated\n";
echo "Übersprungen: $skipped\n";

// Zeige alle Seiten ohne Bild
echo "\n=== Seiten ohne Featured Image ===\n";
$pages_without_image = get_posts(array(
    'post_type' => 'page',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_thumbnail_id',
            'compare' => 'NOT EXISTS'
        )
    )
));

foreach ($pages_without_image as $page) {
    echo "- {$page->post_title}\n";
}

echo "\nTotal Seiten ohne Bild: " . count($pages_without_image) . "\n";