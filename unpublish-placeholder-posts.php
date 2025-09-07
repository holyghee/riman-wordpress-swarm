<?php
/**
 * Script zum Unpublish von Posts mit Platzhalter-Texten
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

echo "=== Suche nach Posts mit Platzhalter-Texten ===\n\n";

// Hole alle veröffentlichten Posts
$posts = get_posts([
    'numberposts' => -1,
    'post_type' => 'post',
    'post_status' => 'publish'
]);

echo "Gefundene Posts: " . count($posts) . "\n\n";

$placeholder_phrases = [
    'Dieser Artikel wird in Kürze',
    'Inhalt folgt in Kürze',
    'Detaillierte Informationen folgen',
    'Ausführliche Informationen zu',
    'wird demnächst hier verfügbar',
    'Umfassende Informationen werden',
    'werden hier in Kürze',
    'bald verfügbar',
    'In Vorbereitung',
    'Artikel in Bearbeitung',
    'Content in Bearbeitung',
    'Platzhalter',
    'Lorem ipsum',
    'Coming soon',
    'Demnächst verfügbar',
    'wird bald hier erscheinen',
    'folgt demnächst',
    'wird zeitnah ergänzt'
];

$unpublished_count = 0;
$small_posts_count = 0;

foreach ($posts as $post) {
    $content = $post->post_content;
    $content_length = strlen($content);
    $is_placeholder = false;
    $reason = '';
    
    // Prüfe auf Platzhalter-Phrasen
    foreach ($placeholder_phrases as $phrase) {
        if (stripos($content, $phrase) !== false) {
            $is_placeholder = true;
            $reason = "Enthält Platzhalter-Text: '$phrase'";
            break;
        }
    }
    
    // Prüfe auf zu kurzen Inhalt (< 500 Zeichen)
    if (!$is_placeholder && $content_length < 500) {
        $is_placeholder = true;
        $reason = "Zu kurzer Inhalt ($content_length Zeichen)";
        $small_posts_count++;
    }
    
    // Prüfe auf wiederholten Text
    if (!$is_placeholder) {
        // Zähle wie oft "folgt" oder "demnächst" vorkommt
        $folgt_count = substr_count(strtolower($content), 'folgt');
        $demnaechst_count = substr_count(strtolower($content), 'demnächst');
        
        if ($folgt_count > 2 || $demnaechst_count > 2) {
            $is_placeholder = true;
            $reason = "Wiederholte Platzhalter-Wörter";
        }
    }
    
    if ($is_placeholder) {
        // Post auf Draft setzen
        $update_result = wp_update_post([
            'ID' => $post->ID,
            'post_status' => 'draft'
        ]);
        
        if ($update_result) {
            echo "✓ Unpublished: {$post->post_title}\n";
            echo "  Grund: $reason\n";
            echo "  Länge: $content_length Zeichen\n\n";
            $unpublished_count++;
        } else {
            echo "✗ Fehler beim Unpublish: {$post->post_title}\n\n";
        }
    }
}

echo "\n=== Zusammenfassung ===\n";
echo "Posts geprüft: " . count($posts) . "\n";
echo "Posts unpublished: $unpublished_count\n";
echo "  davon wegen zu kurzem Inhalt: $small_posts_count\n";

// Zeige verbleibende veröffentlichte Posts
$remaining_posts = get_posts([
    'numberposts' => -1,
    'post_type' => 'post',
    'post_status' => 'publish'
]);

echo "\nVerbleibende veröffentlichte Posts: " . count($remaining_posts) . "\n";

if (count($remaining_posts) > 0) {
    echo "\nListe der noch veröffentlichten Posts:\n";
    foreach ($remaining_posts as $post) {
        $content_length = strlen($post->post_content);
        echo "- {$post->post_title} ({$content_length} Zeichen)\n";
    }
}