<?php
/**
 * Check Media Count Script
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

echo "=== WordPress Media Count Check ===\n\n";

// Zähle alle Attachments
$args = array(
    'post_type' => 'attachment',
    'post_status' => 'inherit',
    'posts_per_page' => -1
);

$attachments = get_posts($args);
$total_count = count($attachments);

echo "Total Attachments in Database: $total_count\n\n";

// Zeige Details
echo "Attachments by Type:\n";
$types = array();

foreach ($attachments as $attachment) {
    $mime_type = get_post_mime_type($attachment->ID);
    $type = explode('/', $mime_type)[0];
    
    if (!isset($types[$type])) {
        $types[$type] = 0;
    }
    $types[$type]++;
}

foreach ($types as $type => $count) {
    echo "- $type: $count\n";
}

// Liste die letzten 10 importierten Bilder
echo "\nLetzte 10 Bilder (neueste zuerst):\n";
$recent = array_slice($attachments, 0, 10);

foreach ($recent as $attachment) {
    echo "- ID: {$attachment->ID} | {$attachment->post_title} | {$attachment->post_date}\n";
}

// Prüfe Upload-Verzeichnis
$upload_dir = wp_upload_dir();
echo "\nUpload Directory: " . $upload_dir['basedir'] . "\n";

// Zähle physische Dateien
$files = glob($upload_dir['basedir'] . '/*/*/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
echo "Physical Files in Upload Dir: " . count($files) . "\n";

// Prüfe ob es doppelte Einträge gibt
$titles = array();
$duplicates = 0;
foreach ($attachments as $attachment) {
    if (in_array($attachment->post_title, $titles)) {
        $duplicates++;
    }
    $titles[] = $attachment->post_title;
}
echo "Duplicate Titles: $duplicates\n";