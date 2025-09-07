<?php
/**
 * Create WordPress Pages with Enhanced Mapping Slugs
 */

require_once('/var/www/html/wp-load.php');

echo "=== Create Pages with Enhanced Mapping Slugs ===\n\n";

// Lade Enhanced Mappings
$mappings_file = '/tmp/wordpress-enhanced-image-mappings.json';
if (!file_exists($mappings_file)) {
    echo "❌ Enhanced Mappings nicht gefunden\n";
    exit;
}

$enhanced = json_decode(file_get_contents($mappings_file), true);
if (!$enhanced || !isset($enhanced['page_mappings'])) {
    echo "❌ Fehler beim Laden der Enhanced Mappings\n";
    exit;
}

echo "✅ Enhanced Mappings geladen mit " . count($enhanced['page_mappings']) . " Einträgen\n\n";

$created = 0;
$existing = 0;

foreach ($enhanced['page_mappings'] as $slug => $image_filename) {
    // Skip leere Einträge und spezielle Seiten
    if (empty($slug) || $slug == 'home' || $slug == 'ueber-uns') {
        continue;
    }
    
    // Erstelle Seitentitel aus Slug
    $title = ucwords(str_replace('-', ' ', $slug));
    $title = str_replace('Rueckbau', 'Rückbau', $title);
    $title = str_replace('Altlastensanierung', 'Altlastensanierung', $title);
    $title = str_replace('Schadstoffmanagement', 'Schadstoffmanagement', $title);
    $title = str_replace('Umweltberatung', 'Umweltberatung', $title);
    $title = str_replace('Arbeitssicherheit', 'Arbeitssicherheit', $title);
    $title = str_replace('Baustellensicherheit', 'Baustellensicherheit', $title);
    
    // Prüfe ob Seite bereits existiert
    $existing_page = get_page_by_path($slug);
    if ($existing_page) {
        echo "⏭️  Existiert bereits: $slug ($title)\n";
        $existing++;
        continue;
    }
    
    // Erstelle Seite mit exaktem Slug
    $page_data = array(
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_content' => "Inhalt für $title wird hier eingefügt.",
        'post_status'  => 'publish',
        'post_type'    => 'page'
    );
    
    $page_id = wp_insert_post($page_data);
    
    if ($page_id && !is_wp_error($page_id)) {
        echo "✅ Erstellt: $slug → $title (ID: $page_id)\n";
        $created++;
    } else {
        echo "❌ Fehler bei: $slug\n";
    }
}

echo "\n=== Ergebnis ===\n";
echo "✅ Neue Seiten erstellt: $created\n";
echo "⏭️  Bereits vorhanden: $existing\n";
echo "📊 Gesamt Enhanced Mappings: " . count($enhanced['page_mappings']) . "\n";

// Zeige alle Seiten mit ihren Slugs
$all_pages = get_posts(array(
    'post_type' => 'page',
    'numberposts' => -1,
    'post_status' => 'any'
));

echo "\n=== Alle WordPress Seiten ===\n";
foreach ($all_pages as $page) {
    echo "📄 {$page->post_name} → {$page->post_title}\n";
}
echo "\n";