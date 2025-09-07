<?php
/**
 * RIMAN Website - Bilder Import Script
 * Importiert alle Bilder aus dem riman-content Repository in WordPress
 * 
 * Verwendung: php import-riman-images.php
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

echo "=== RIMAN Bilder Import Script ===\n\n";

// Basis-Pfade für Bilder
$image_sources = [
    '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-rebuild/public/assets/images/',
    '/var/www/html/riman-content/riman-website-rebuild/public/assets/images/'
];

// Finde den richtigen Pfad
$image_base_path = null;
foreach ($image_sources as $path) {
    if (is_dir($path)) {
        $image_base_path = $path;
        break;
    }
}

if (!$image_base_path) {
    die("FEHLER: Bilder-Verzeichnis nicht gefunden!\n");
}

echo "Bilder-Pfad: $image_base_path\n\n";

// Image Mapping laden
$mapping_file = dirname($image_base_path) . '/../../../riman-website-codex/image-mapping.json';
if (!file_exists($mapping_file)) {
    $mapping_file = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/image-mapping.json';
}

$image_mapping = [];
if (file_exists($mapping_file)) {
    $image_mapping = json_decode(file_get_contents($mapping_file), true);
    echo "Image-Mapping geladen.\n\n";
}

// ============================================================================
// Bilder importieren
// ============================================================================
echo "Importiere Bilder...\n";

$imported_count = 0;
$error_count = 0;
$imported_images = []; // Speichere Zuordnung Dateiname -> Attachment ID

// Funktion zum Importieren eines Bildes
function import_image($file_path, $title = null) {
    global $imported_images;
    
    $filename = basename($file_path);
    
    // Prüfe ob Bild bereits importiert wurde
    if (isset($imported_images[$filename])) {
        return $imported_images[$filename];
    }
    
    // Prüfe ob Datei existiert
    if (!file_exists($file_path)) {
        echo "  FEHLER: Datei nicht gefunden: $file_path\n";
        return false;
    }
    
    // Lade Bild in WordPress Media Library
    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents($file_path);
    
    if ($image_data === false) {
        echo "  FEHLER: Konnte Datei nicht lesen: $file_path\n";
        return false;
    }
    
    $unique_filename = wp_unique_filename($upload_dir['path'], $filename);
    $upload_file = $upload_dir['path'] . '/' . $unique_filename;
    
    // Speichere Datei
    if (file_put_contents($upload_file, $image_data) === false) {
        echo "  FEHLER: Konnte Datei nicht speichern: $upload_file\n";
        return false;
    }
    
    // Mime Type ermitteln
    $wp_filetype = wp_check_filetype($unique_filename, null);
    
    // Attachment Daten vorbereiten
    $attachment = array(
        'guid'           => $upload_dir['url'] . '/' . $unique_filename, 
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => $title ?: preg_replace('/\.[^.]+$/', '', $filename),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );
    
    // Füge Attachment zur Datenbank hinzu
    $attach_id = wp_insert_attachment($attachment, $upload_file);
    
    // Erstelle Metadaten für das Attachment
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload_file);
    wp_update_attachment_metadata($attach_id, $attach_data);
    
    // Speichere Zuordnung
    $imported_images[$filename] = $attach_id;
    
    echo "  ✓ $filename importiert (ID: $attach_id)\n";
    
    return $attach_id;
}

// Durchsuche alle Unterverzeichnisse
$directories = ['logos', 'team', 'services', 'company', 'process', 'categories', 'general'];

foreach ($directories as $dir) {
    $dir_path = $image_base_path . $dir;
    
    if (!is_dir($dir_path)) {
        echo "Verzeichnis $dir nicht gefunden, überspringe...\n";
        continue;
    }
    
    echo "\nImportiere aus $dir/:\n";
    
    $files = glob($dir_path . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    
    foreach ($files as $file) {
        // Überspringe -640w Versionen (sind Thumbnails)
        if (strpos($file, '-640w') !== false) {
            continue;
        }
        
        $result = import_image($file);
        if ($result) {
            $imported_count++;
        } else {
            $error_count++;
        }
    }
}

echo "\n=== Import abgeschlossen ===\n";
echo "Erfolgreich importiert: $imported_count Bilder\n";
echo "Fehler: $error_count\n\n";

// ============================================================================
// Bilder zu Seiten und Posts zuordnen
// ============================================================================
echo "Ordne Bilder zu Seiten zu...\n\n";

// Mapping von Seitentiteln zu Bildnamen (basierend auf image-mapping.json)
$page_image_mapping = [
    'Rückbaumanagement' => 'nachhaltiger-rueckbau-baustelle-recycling.jpg',
    'Schadstoffsanierung' => 'schadstoff-analytik-professional.png', 
    'Altlastensanierung' => 'altlastensanierung-grundwasser-bodenschutz.jpg',
    'Sicherheitskoordination' => 'sicherheit-koordination-professional.png',
    'Baubegleitung' => 'baumediation-konfliktloesung-projektmanagement.jpg'
];

foreach ($page_image_mapping as $page_title => $image_name) {
    // Finde Seite
    $page = get_page_by_title($page_title);
    if (!$page) {
        // Versuche als Kategorie
        $term = get_term_by('name', $page_title, 'category');
        if ($term) {
            echo "Kategorie '$page_title' gefunden, setze Bild...\n";
            
            // Finde Attachment ID
            if (isset($imported_images[$image_name])) {
                $attachment_id = $imported_images[$image_name];
                
                // Setze als Kategorie-Bild (Custom Field)
                update_term_meta($term->term_id, '_thumbnail_id', $attachment_id);
                echo "  ✓ Bild zugeordnet zu Kategorie '$page_title'\n";
            }
        }
        continue;
    }
    
    // Finde Attachment ID
    if (isset($imported_images[$image_name])) {
        $attachment_id = $imported_images[$image_name];
        
        // Setze als Featured Image
        set_post_thumbnail($page->ID, $attachment_id);
        echo "  ✓ Featured Image gesetzt für Seite '$page_title'\n";
    } else {
        echo "  ⚠ Bild '$image_name' nicht gefunden für Seite '$page_title'\n";
    }
}

// Team-Bilder zuordnen
$team_mapping = [
    'Dr. Michael Riman' => 'dr-michael-riman-geschaeftsfuehrer.jpg'
];

foreach ($team_mapping as $name => $image_name) {
    $page = get_page_by_title($name, OBJECT, 'post');
    if ($page && isset($imported_images[$image_name])) {
        set_post_thumbnail($page->ID, $imported_images[$image_name]);
        echo "  ✓ Team-Bild gesetzt für '$name'\n";
    }
}

echo "\n=== Bilder-Import komplett ===\n";
echo "Alle Bilder wurden importiert und zugeordnet.\n";
echo "\nHinweis: Die Bilder sind jetzt in der WordPress Media Library verfügbar.\n";
echo "Du kannst sie im WordPress Admin unter 'Medien' sehen und verwalten.\n";