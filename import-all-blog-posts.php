<?php
/**
 * Import aller MD-Dateien (außer main.md) als Blog-Posts
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

// Helper function aus dem Haupt-Setup-Script
function convert_markdown_to_blocks($markdown, $page_title = '') {
    // Entferne Meta-Daten egal wo sie in der Datei stehen
    $lines = explode("\n", $markdown);
    $filtered_lines = [];
    $skip_next_separator = false;
    
    foreach ($lines as $index => $line) {
        // Überspringe Meta Description Zeilen
        if (preg_match('/^\*\*Meta Description:\*\*/', $line)) {
            continue;
        }
        
        // Überspringe SEO Keywords Zeilen
        if (preg_match('/^\*\*SEO Keywords:\*\*/', $line)) {
            $skip_next_separator = true;
            continue;
        }
        
        // Überspringe --- nach SEO Keywords
        if ($skip_next_separator && trim($line) === '---') {
            $skip_next_separator = false;
            continue;
        }
        
        // Überspringe leere Zeilen direkt nach ---
        if ($index > 0 && trim($lines[$index-1]) === '---' && trim($line) === '' && $index > 1 && preg_match('/^\*\*SEO Keywords:\*\*/', $lines[$index-2])) {
            continue;
        }
        
        $filtered_lines[] = $line;
    }
    
    // Verarbeite die gefilterten Zeilen
    $content = '';
    $lines = $filtered_lines;
    $in_list = false;
    $in_code = false;
    $list_items = [];
    $code_lines = [];
    $first_h1_skipped = false;
    
    foreach ($lines as $line) {
        // Überschriften
        if (preg_match('/^#{1,6}\s+(.+)/', $line, $matches)) {
            $level = strlen(explode(' ', $line)[0]);
            $heading_text = trim($matches[1]);
            
            // Überspringe erste H1 wenn identisch mit Titel
            if ($level === 1 && !$first_h1_skipped) {
                $first_h1_skipped = true;
                if (strcasecmp(trim($heading_text), trim($page_title)) === 0) {
                    continue;
                }
            }
            
            $content .= "<!-- wp:heading {\"level\":$level} -->\n<h$level class=\"wp-block-heading\">$heading_text</h$level>\n<!-- /wp:heading -->\n\n";
        }
        // Listen
        elseif (preg_match('/^[\s]*[-*+•]\s+(.+)/', $line, $matches)) {
            $in_list = true;
            $item_text = trim($matches[1]);
            $item_text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $item_text);
            $list_items[] = $item_text;
        }
        // Normaler Paragraph
        elseif (trim($line) !== '') {
            // Listen beenden wenn nötig
            if ($in_list && !empty($list_items)) {
                $content .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n";
                foreach ($list_items as $item) {
                    $content .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->\n";
                }
                $content .= "</ul>\n<!-- /wp:list -->\n\n";
                $in_list = false;
                $list_items = [];
            }
            
            $line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line);
            $line = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $line);
            $content .= "<!-- wp:paragraph -->\n<p>" . trim($line) . "</p>\n<!-- /wp:paragraph -->\n\n";
        }
    }
    
    return $content;
}

// Basis-Pfad
$base_path = file_exists('/var/www/html/riman-content/') ? 
             '/var/www/html/riman-content/' : 
             '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content/';

echo "=== Import aller Blog-Posts aus MD-Dateien ===\n\n";
echo "Basis-Pfad: $base_path\n\n";

// Kategorie-Zuordnung basierend auf Pfad
$category_mapping = [
    'rueckbaumanagement' => 'rueckbau',
    'altlastensanierung' => 'altlasten',
    'schadstoff-management' => 'schadstoffe',
    'sicherheitskoordination' => 'sicherheitskoordination',
    'beratung-mediation' => 'beratung'
];

// Finde alle MD-Dateien (außer main.md und Hauptseiten)
$services_path = $base_path . 'services/';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($services_path),
    RecursiveIteratorIterator::SELF_FIRST
);

$imported = 0;
$skipped = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'md') {
        $filename = $file->getFilename();
        
        // Überspringe main.md Dateien
        if ($filename === 'main.md') {
            continue;
        }
        
        $filepath = $file->getPathname();
        $relative_path = str_replace($services_path, '', $filepath);
        
        // Extrahiere Kategorie aus Pfad
        $path_parts = explode('/', $relative_path);
        $service_folder = $path_parts[0];
        $category_slug = isset($category_mapping[$service_folder]) ? 
                        $category_mapping[$service_folder] : 
                        $service_folder;
        
        // Hole Kategorie-ID
        $category = get_category_by_slug($category_slug);
        if (!$category) {
            echo "⚠ Kategorie nicht gefunden für: $category_slug (Datei: $relative_path)\n";
            $skipped++;
            continue;
        }
        
        // Lade Markdown-Inhalt
        $markdown_content = file_get_contents($filepath);
        
        // Extrahiere Titel (erste H1)
        $title = '';
        if (preg_match('/^#\s+(.+)$/m', $markdown_content, $matches)) {
            $title = trim($matches[1]);
        } else {
            // Nutze Dateiname als Titel
            $title = str_replace(['-', '_', '.md'], [' ', ' ', ''], $filename);
            $title = ucwords($title);
        }
        
        // Prüfe ob Post bereits existiert
        $existing = get_page_by_title($title, OBJECT, 'post');
        if ($existing) {
            echo "⚠ Post existiert bereits: $title\n";
            $skipped++;
            continue;
        }
        
        // Konvertiere Markdown zu Gutenberg Blocks
        $content = convert_markdown_to_blocks($markdown_content, $title);
        
        // Erstelle Slug aus Dateiname
        $slug = str_replace('.md', '', $filename);
        
        // Erstelle Blog-Post
        $post_id = wp_insert_post([
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => 1,
            'post_category' => [$category->term_id]
        ]);
        
        if ($post_id) {
            echo "✓ Blog-Post erstellt: $title (Kategorie: {$category->name})\n";
            $imported++;
        } else {
            echo "✗ Fehler beim Erstellen: $title\n";
            $skipped++;
        }
    }
}

echo "\n=== Import abgeschlossen ===\n";
echo "Importiert: $imported Posts\n";
echo "Übersprungen: $skipped Dateien\n";

// Zeige Zusammenfassung nach Kategorien
echo "\n=== Posts pro Kategorie ===\n";
$categories = get_categories(['hide_empty' => false]);
foreach ($categories as $cat) {
    $posts = get_posts([
        'category' => $cat->term_id,
        'numberposts' => -1,
        'post_type' => 'post'
    ]);
    if (count($posts) > 0) {
        echo "- {$cat->name}: " . count($posts) . " Posts\n";
    }
}