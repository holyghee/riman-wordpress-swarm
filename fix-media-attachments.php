<?php
/**
 * Fix Media Attachments - Registriert alle physischen Bilder als WordPress Attachments
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

require_once(ABSPATH . 'wp-admin/includes/image.php');

echo "=== Fix Media Attachments ===\n\n";

// Upload-Verzeichnis
$upload_dir = wp_upload_dir();
$base_dir = $upload_dir['basedir'];

// Finde alle Bilddateien
$image_files = array();
$years = glob($base_dir . '/*', GLOB_ONLYDIR);

foreach ($years as $year_dir) {
    $months = glob($year_dir . '/*', GLOB_ONLYDIR);
    foreach ($months as $month_dir) {
        $files = glob($month_dir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        foreach ($files as $file) {
            // Überspringe Thumbnails
            if (preg_match('/-\d+x\d+\.(jpg|jpeg|png|gif|webp)$/', $file)) {
                continue;
            }
            $image_files[] = $file;
        }
    }
}

echo "Gefundene Bilddateien: " . count($image_files) . "\n\n";

$created = 0;
$skipped = 0;

foreach ($image_files as $file_path) {
    $filename = basename($file_path);
    
    // Prüfe ob bereits als Attachment existiert
    $relative_path = str_replace($base_dir . '/', '', $file_path);
    
    $args = array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'meta_query' => array(
            array(
                'key' => '_wp_attached_file',
                'value' => $relative_path,
                'compare' => '='
            )
        )
    );
    
    $existing = get_posts($args);
    
    if (!empty($existing)) {
        echo "✓ Bereits registriert: $filename\n";
        $skipped++;
        continue;
    }
    
    // Erstelle Attachment
    $wp_filetype = wp_check_filetype($filename, null);
    
    // Titel aus Dateinamen generieren
    $title = preg_replace('/\.[^.]+$/', '', $filename);
    $title = str_replace('-', ' ', $title);
    $title = str_replace('_', ' ', $title);
    $title = ucwords($title);
    
    // Attachment-Daten
    $attachment = array(
        'guid'           => $upload_dir['url'] . '/' . basename(dirname($file_path)) . '/' . basename(dirname(dirname($file_path))) . '/' . $filename,
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => $title,
        'post_content'   => '',
        'post_status'    => 'inherit'
    );
    
    // Füge Attachment hinzu
    $attach_id = wp_insert_attachment($attachment, $file_path);
    
    if ($attach_id) {
        // Update metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        echo "✅ Neu registriert: $filename (ID: $attach_id)\n";
        $created++;
    } else {
        echo "❌ Fehler bei: $filename\n";
    }
}

echo "\n=== Zusammenfassung ===\n";
echo "Neue Attachments erstellt: $created\n";
echo "Bereits vorhanden: $skipped\n";
echo "Total Attachments jetzt: " . count(get_posts(array('post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1))) . "\n";