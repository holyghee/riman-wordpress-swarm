<?php
/**
 * Import Midjourney Generated Images to WordPress
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

require_once(ABSPATH . 'wp-admin/includes/image.php');

echo "=== Import Midjourney Images ===\n\n";

// Pfad zu den generierten Bildern
$image_dir = '/tmp/midjourney-images/';

// Mapping von Bildern zu Kategorien
$image_category_mapping = [
    'midjourney_upscaled_1756978713361.png' => 'Rückbaumanagement',
    'midjourney_upscaled_1756978784266.png' => 'Altlastensanierung',
    'midjourney_upscaled_1756978856726.png' => 'Schadstoff-Management',
    'midjourney_upscaled_1756978926205.png' => 'Sicherheitskoordination',
    'midjourney_upscaled_1756978992729.png' => 'Beratung & Mediation'
];

foreach ($image_category_mapping as $filename => $category_name) {
    $file_path = $image_dir . $filename;
    
    if (!file_exists($file_path)) {
        echo "❌ Datei nicht gefunden: $filename\n";
        continue;
    }
    
    // Kopiere Datei ins Upload-Verzeichnis
    $upload_dir = wp_upload_dir();
    
    // Besserer Dateiname
    $new_filename = sanitize_file_name(strtolower(str_replace(' ', '-', $category_name))) . '-midjourney.png';
    $upload_file = $upload_dir['path'] . '/' . $new_filename;
    
    if (!copy($file_path, $upload_file)) {
        echo "❌ Konnte Datei nicht kopieren: $filename\n";
        continue;
    }
    
    // Erstelle Attachment
    $wp_filetype = wp_check_filetype($new_filename, null);
    
    $attachment = array(
        'guid'           => $upload_dir['url'] . '/' . $new_filename, 
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => $category_name,
        'post_content'   => 'Generiert mit Midjourney für ' . $category_name,
        'post_status'    => 'inherit'
    );
    
    $attach_id = wp_insert_attachment($attachment, $upload_file);
    
    if ($attach_id) {
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload_file);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        echo "✅ Bild importiert: $category_name (ID: $attach_id)\n";
        
        // Finde Kategorie und setze Bild
        $term = get_term_by('name', $category_name, 'category');
        if ($term) {
            update_term_meta($term->term_id, '_thumbnail_id', $attach_id);
            echo "   → Bild zu Kategorie '$category_name' zugeordnet\n";
            
            // Finde verknüpfte Seite und setze auch dort das Bild
            $linked_page_id = get_term_meta($term->term_id, '_linked_page_id', true);
            if ($linked_page_id) {
                set_post_thumbnail($linked_page_id, $attach_id);
                echo "   → Bild auch zur verknüpften Seite zugeordnet\n";
            }
        }
        
        // Versuche auch Seite direkt zu finden
        $page = get_page_by_title($category_name);
        if ($page && !has_post_thumbnail($page->ID)) {
            set_post_thumbnail($page->ID, $attach_id);
            echo "   → Bild zur Seite '$category_name' zugeordnet\n";
        }
    } else {
        echo "❌ Fehler beim Erstellen des Attachments: $category_name\n";
    }
    echo "\n";
}

echo "=== Import abgeschlossen ===\n";