<?php
/**
 * RIMAN Website - Unterkategorien Bilder Import Script
 * Importiert alle Bilder für Unterkategorien aus dem riman-website Repository
 * 
 * Verwendung: php import-subcategory-images.php
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

echo "=== RIMAN Unterkategorien Bilder Import Script ===\n\n";

// Basis-Pfade für Bilder - WICHTIG: riman-website, nicht rebuild!
$image_sources = [
    '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website/assets/images/',
    '/var/www/html/riman-content/riman-website/assets/images/'
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
$mapping_file = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/image-mapping.json';
if (!file_exists($mapping_file)) {
    $mapping_file = '/var/www/html/riman-content/riman-website-codex/image-mapping.json';
}

if (!file_exists($mapping_file)) {
    die("FEHLER: image-mapping.json nicht gefunden!\n");
}

$image_mapping = json_decode(file_get_contents($mapping_file), true);
echo "Image-Mapping geladen.\n\n";

// ============================================================================
// Helper Funktionen
// ============================================================================

function import_image_to_wordpress($file_path, $title = null) {
    $filename = basename($file_path);
    
    // Prüfe ob Datei existiert
    if (!file_exists($file_path)) {
        echo "  ⚠ Datei nicht gefunden: $filename\n";
        return false;
    }
    
    // Prüfe ob Bild bereits in Media Library existiert
    $args = array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'meta_query' => array(
            array(
                'key' => '_wp_attached_file',
                'value' => $filename,
                'compare' => 'LIKE'
            )
        )
    );
    
    $existing = get_posts($args);
    if (!empty($existing)) {
        echo "  ℹ Bild bereits vorhanden: $filename (ID: {$existing[0]->ID})\n";
        return $existing[0]->ID;
    }
    
    // Lade Bild in WordPress Media Library
    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents($file_path);
    
    if ($image_data === false) {
        echo "  ❌ Konnte Datei nicht lesen: $file_path\n";
        return false;
    }
    
    $unique_filename = wp_unique_filename($upload_dir['path'], $filename);
    $upload_file = $upload_dir['path'] . '/' . $unique_filename;
    
    // Speichere Datei
    if (file_put_contents($upload_file, $image_data) === false) {
        echo "  ❌ Konnte Datei nicht speichern: $upload_file\n";
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
    
    echo "  ✅ $filename importiert (ID: $attach_id)\n";
    
    return $attach_id;
}

// ============================================================================
// Importiere Unterkategorien-Bilder
// ============================================================================
echo "Importiere Unterkategorien-Bilder...\n\n";

$imported_count = 0;
$skipped_count = 0;
$error_count = 0;

// Durchlaufe alle Services im Mapping
foreach ($image_mapping['services'] as $service_key => $service_data) {
    echo "Service: $service_key\n";
    
    // Hauptbild für Service
    if (isset($service_data['main'])) {
        $image_path = $image_base_path . $service_data['main'];
        $result = import_image_to_wordpress($image_path, ucfirst($service_key));
        if ($result) $imported_count++; else $error_count++;
    }
    
    // Unterkategorien
    if (isset($service_data['subservices'])) {
        foreach ($service_data['subservices'] as $subservice_key => $subservice_data) {
            echo "\n  Unterkategorie: $subservice_key\n";
            
            // Hauptbild der Unterkategorie
            if (isset($subservice_data['image'])) {
                $image_path = $image_base_path . $subservice_data['image'];
                $result = import_image_to_wordpress($image_path, ucfirst($subservice_key));
                
                if ($result) {
                    $imported_count++;
                    
                    // Versuche das Bild der Kategorie zuzuordnen
                    $term = get_term_by('slug', $subservice_key, 'category');
                    if (!$term) {
                        // Versuche mit deutschem Namen
                        $german_names = [
                            'planung' => 'Rückbauplanung',
                            'ausschreibung' => 'Ausschreibung',
                            'durchfuehrung' => 'Durchführung',
                            'entsorgung' => 'Entsorgung'
                        ];
                        
                        if (isset($german_names[$subservice_key])) {
                            $term = get_term_by('name', $german_names[$subservice_key], 'category');
                        }
                    }
                    
                    if ($term) {
                        update_term_meta($term->term_id, '_thumbnail_id', $result);
                        echo "    → Bild zu Kategorie '{$term->name}' zugeordnet\n";
                    }
                } else {
                    $error_count++;
                }
            }
            
            // Detail-Bilder (optional)
            if (isset($subservice_data['details'])) {
                foreach ($subservice_data['details'] as $detail_key => $detail_image) {
                    $image_path = $image_base_path . $detail_image;
                    $result = import_image_to_wordpress($image_path, ucfirst($detail_key));
                    if ($result) $imported_count++; else $error_count++;
                }
            }
        }
    }
    echo "\n";
}

echo "\n=== Import abgeschlossen ===\n";
echo "Erfolgreich importiert: $imported_count Bilder\n";
echo "Übersprungen: $skipped_count\n";
echo "Fehler: $error_count\n\n";

echo "Die Bilder sind jetzt in der WordPress Media Library verfügbar.\n";
echo "Unterkategorien mit zugeordneten Bildern können diese als Featured Images verwenden.\n";