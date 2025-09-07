<?php
/**
 * Import alle Midjourney Bilder und weise sie den Kategorien zu
 * Dieses Script wird automatisch vom Setup aufgerufen
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

echo "=== Automatischer Bilder-Import für alle Kategorien ===\n\n";

// Pfad zu den Midjourney Bildern
$local_image_dir = '/Users/holgerbrandt/dev/claude-code/tools/midjourney-mcp-server/midjourney-images/';
$container_image_dir = '/tmp/midjourney-images/';

// Prüfe welcher Pfad existiert
if (file_exists($container_image_dir)) {
    $image_dir = $container_image_dir;
} elseif (file_exists($local_image_dir)) {
    // Wenn lokal, kopiere ins Container tmp Verzeichnis
    exec("docker cp $local_image_dir riman-wordpress-swarm-wordpress-1:/tmp/");
    $image_dir = $container_image_dir;
} else {
    echo "❌ Fehler: Bilder-Verzeichnis nicht gefunden!\n";
    exit(1);
}

// Kategorie-Bild Mapping basierend auf Keywords
$category_image_mappings = [
    // Altlastensanierung Unterkategorien
    'altlasten-erkundung' => ['investigation', 'exploration', 'sampling', 'assessment'],
    'sanierungsplanung' => ['planning', 'engineering', 'blueprint', 'technical'],
    'bodensanierung' => ['soil', 'remediation', 'excavator', 'earth'],
    'grundwassersanierung' => ['groundwater', 'water', 'treatment', 'filtration'],
    'altlasten-monitoring' => ['monitoring', 'station', 'surveillance', 'data'],
    
    // Rückbaumanagement Unterkategorien
    'dokumentation' => ['documentation', 'document', 'digital', 'archive'],
    'recycling' => ['recycling', 'material', 'circular', 'sorted'],
    
    // Schadstoff-Management Unterkategorien
    'asbestsanierung' => ['asbestos', 'removal', 'protective', 'hazardous'],
    'pcb-sanierung' => ['pcb', 'electrical', 'biphenyl', 'contamination'],
    'pak-sanierung' => ['pah', 'aromatic', 'hydrocarbon', 'tar'],
    'kmf-sanierung' => ['mineral', 'fiber', 'insulation', 'artificial'],
    'schwermetalle' => ['heavy', 'metal', 'laboratory', 'analysis'],
    
    // Sicherheitskoordination Unterkategorien
    'sigeko-planung' => ['safety-planning', 'coordination-blueprint', 'risk-assessment'],
    'sigeko-ausfuehrung' => ['safety-coordination', 'execution', 'inspection', 'on-site'],
    'gefaehrdungsbeurteilung' => ['risk', 'assessment', 'hazard', 'evaluation'],
    'arbeitsschutz' => ['workplace', 'safety', 'occupational', 'protective'],
    'notfallmanagement' => ['emergency', 'crisis', 'response', 'incident'],
    
    // Beratung & Mediation Unterkategorien
    'projektberatung' => ['consulting', 'project', 'advisory', 'expert-advisors'],
    'baumediation' => ['mediation', 'conflict', 'resolution', 'dispute'],
    'gutachten' => ['expert', 'assessment', 'evaluation', 'witness'],
    'compliance' => ['compliance', 'regulatory', 'certification', 'audit'],
    'schulungen' => ['training', 'workshop', 'classroom', 'education']
];

// Funktion zum Finden des besten Bildes für eine Kategorie
function find_best_image_for_category($category_slug, $image_dir, $mappings) {
    if (!isset($mappings[$category_slug])) {
        return null;
    }
    
    $keywords = $mappings[$category_slug];
    $images = glob($image_dir . '*.png');
    
    // Suche nach dem besten Match
    foreach ($images as $image) {
        $filename = strtolower(basename($image));
        foreach ($keywords as $keyword) {
            if (strpos($filename, $keyword) !== false) {
                return $image;
            }
        }
    }
    
    // Fallback: Nimm das neueste Bild wenn kein Match gefunden
    if (!empty($images)) {
        // Sortiere nach Änderungszeit (neueste zuerst)
        usort($images, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        return $images[0];
    }
    
    return null;
}

// Hole alle Kategorien ohne Bilder
$categories = get_terms(array(
    'taxonomy' => 'category',
    'hide_empty' => false
));

$imported_count = 0;
$skipped_count = 0;

foreach ($categories as $category) {
    if ($category->slug === 'uncategorized') continue;
    
    // Prüfe ob Kategorie schon ein Bild hat
    $existing_thumbnail = get_term_meta($category->term_id, '_thumbnail_id', true);
    if (!empty($existing_thumbnail)) {
        echo "⏭️  {$category->name} hat bereits Bild (ID: $existing_thumbnail)\n";
        $skipped_count++;
        continue;
    }
    
    // Finde passendes Bild für diese Kategorie
    $image_path = find_best_image_for_category($category->slug, $image_dir, $category_image_mappings);
    
    if (!$image_path) {
        // Versuche mit vereinfachtem Slug
        $simplified_slug = str_replace('-', '', $category->slug);
        $image_path = find_best_image_for_category($simplified_slug, $image_dir, $category_image_mappings);
    }
    
    if (!$image_path) {
        echo "⚠️  Kein Bild gefunden für: {$category->name} ({$category->slug})\n";
        continue;
    }
    
    // Importiere das Bild
    $file_array = array(
        'name' => basename($image_path),
        'tmp_name' => $image_path
    );
    
    $attachment_id = media_handle_sideload($file_array, 0);
    
    if (is_wp_error($attachment_id)) {
        echo "❌ Fehler beim Import für {$category->name}: " . $attachment_id->get_error_message() . "\n";
        continue;
    }
    
    // Weise das Bild der Kategorie zu
    update_term_meta($category->term_id, '_thumbnail_id', $attachment_id);
    echo "✅ Bild importiert für {$category->name} (Bild ID: $attachment_id)\n";
    $imported_count++;
    
    // Finde verknüpfte Seite und weise ihr auch das Bild zu
    $pages = get_posts(array(
        'post_type' => 'page',
        'meta_key' => '_category_id',
        'meta_value' => $category->term_id,
        'numberposts' => 1
    ));
    
    if (!empty($pages)) {
        $page = $pages[0];
        set_post_thumbnail($page->ID, $attachment_id);
        echo "   └─ Bild auch der Seite '{$page->post_title}' zugewiesen\n";
    }
}

echo "\n=== Import abgeschlossen ===\n";
echo "✅ Importiert: $imported_count Bilder\n";
echo "⏭️  Übersprungen: $skipped_count Kategorien (haben bereits Bilder)\n";

// Zeige finale Statistik
$categories_with_images = 0;
$categories_without_images = 0;

foreach ($categories as $category) {
    if ($category->slug === 'uncategorized') continue;
    $thumbnail_id = get_term_meta($category->term_id, '_thumbnail_id', true);
    if (!empty($thumbnail_id)) {
        $categories_with_images++;
    } else {
        $categories_without_images++;
    }
}

echo "\n=== Finale Statistik ===\n";
echo "Kategorien mit Bildern: $categories_with_images\n";
echo "Kategorien ohne Bilder: $categories_without_images\n";
echo "Gesamt: " . ($categories_with_images + $categories_without_images) . "\n";