<?php
/**
 * Analyze Discord timeline to identify only upscaled images (U1, U2, U3, U4) - NOT 2x2 grids
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Analyzing Timeline for Upscaled Images Only ===\n";

// Fetch timeline data from Discord clone
$timeline_url = 'http://localhost:5173/api/timeline';
$timeline_data = file_get_contents($timeline_url);

if (!$timeline_data) {
    die("Failed to fetch timeline data from $timeline_url\n");
}

$data = json_decode($timeline_data, true);
if (!$data || !isset($data['messages'])) {
    die("Failed to parse timeline data\n");
}

echo "Found " . count($data['messages']) . " messages in timeline\n";

$upscaled_images = [];
$grid_images = [];

foreach ($data['messages'] as $message) {
    if (isset($message['attachments'])) {
        foreach ($message['attachments'] as $attachment) {
            if (isset($attachment['url']) && strpos($attachment['url'], '.png') !== false) {
                $filename = basename($attachment['url']);
                $timestamp = $message['timestamp'];
                $image_id = $message['id'];
                
                // Check if this is an upscaled image (contains U1, U2, U3, U4)
                if (preg_match('/_U[1-4]\.png$/', $filename)) {
                    $upscaled_images[] = [
                        'id' => $image_id,
                        'filename' => $filename,
                        'url' => $attachment['url'],
                        'timestamp' => $timestamp,
                        'content' => $message['content'] ?? '',
                        'type' => 'upscaled'
                    ];
                    echo "✅ UPSCALED: $filename (ID: $image_id)\n";
                } else {
                    // This is likely a 2x2 grid image
                    $grid_images[] = [
                        'id' => $image_id,
                        'filename' => $filename,
                        'url' => $attachment['url'],
                        'timestamp' => $timestamp,
                        'content' => $message['content'] ?? '',
                        'type' => 'grid'
                    ];
                    echo "🔲 GRID (SKIP): $filename (ID: $image_id)\n";
                }
            }
        }
    }
}

echo "\n=== Summary ===\n";
echo "✅ Upscaled images (U1-U4): " . count($upscaled_images) . "\n";
echo "🔲 Grid images (2x2): " . count($grid_images) . "\n";

// Sort by timestamp (older first)
usort($upscaled_images, function($a, $b) {
    return strtotime($a['timestamp']) - strtotime($b['timestamp']);
});

echo "\n=== Upscaled Images (chronological order) ===\n";
foreach ($upscaled_images as $index => $image) {
    echo sprintf("%d. %s (ID: %s) - %s\n", 
        $index + 1, 
        $image['filename'], 
        $image['id'],
        substr($image['content'], 0, 100) . '...'
    );
}

// Save only upscaled mappings
$output_data = [
    'created' => date('Y-m-d H:i:s'),
    'total_upscaled' => count($upscaled_images),
    'total_grids_skipped' => count($grid_images),
    'upscaled_images' => $upscaled_images,
    'grid_images' => $grid_images // for reference
];

file_put_contents('/var/www/html/upscaled_only_timeline.json', json_encode($output_data, JSON_PRETTY_PRINT));
echo "\n✅ Saved upscaled timeline data to: /var/www/html/upscaled_only_timeline.json\n";