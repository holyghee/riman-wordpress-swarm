<?php
/**
 * Import aller MD-Dateien mit KORREKTER Unterkategorie-Zuordnung
 * basierend auf dem Ordnerpfad
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

// Helper function für Markdown-Konvertierung
function convert_markdown_to_blocks($markdown, $page_title = '') {
    $lines = explode("\n", $markdown);
    $filtered_lines = [];
    $skip_next_separator = false;
    
    foreach ($lines as $index => $line) {
        if (preg_match('/^\*\*Meta Description:\*\*/', $line)) {
            continue;
        }
        if (preg_match('/^\*\*SEO Keywords:\*\*/', $line)) {
            $skip_next_separator = true;
            continue;
        }
        if ($skip_next_separator && trim($line) === '---') {
            $skip_next_separator = false;
            continue;
        }
        if ($index > 0 && trim($lines[$index-1]) === '---' && trim($line) === '' && $index > 1 && preg_match('/^\*\*SEO Keywords:\*\*/', $lines[$index-2])) {
            continue;
        }
        $filtered_lines[] = $line;
    }
    
    $content = '';
    $lines = $filtered_lines;
    $in_list = false;
    $list_items = [];
    $first_h1_skipped = false;
    
    foreach ($lines as $line) {
        if (preg_match('/^#{1,6}\s+(.+)/', $line, $matches)) {
            $level = strlen(explode(' ', $line)[0]);
            $heading_text = trim($matches[1]);
            
            if ($level === 1 && !$first_h1_skipped) {
                $first_h1_skipped = true;
                if (strcasecmp(trim($heading_text), trim($page_title)) === 0) {
                    continue;
                }
            }
            
            if ($in_list && !empty($list_items)) {
                $content .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n";
                foreach ($list_items as $item) {
                    $content .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->\n";
                }
                $content .= "</ul>\n<!-- /wp:list -->\n\n";
                $in_list = false;
                $list_items = [];
            }
            
            $content .= "<!-- wp:heading {\"level\":$level} -->\n<h$level class=\"wp-block-heading\">$heading_text</h$level>\n<!-- /wp:heading -->\n\n";
        }
        elseif (preg_match('/^[\s]*[-*+•]\s+(.+)/', $line, $matches)) {
            $in_list = true;
            $item_text = trim($matches[1]);
            $item_text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $item_text);
            $list_items[] = $item_text;
        }
        elseif (trim($line) !== '') {
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
    
    if ($in_list && !empty($list_items)) {
        $content .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n";
        foreach ($list_items as $item) {
            $content .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->\n";
        }
        $content .= "</ul>\n<!-- /wp:list -->\n\n";
    }
    
    return $content;
}

// Basis-Pfad
$base_path = file_exists('/var/www/html/riman-content/') ? 
             '/var/www/html/riman-content/' : 
             '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content/';

echo "=== Import aller Blog-Posts mit KORREKTER Kategorie-Zuordnung ===\n\n";
echo "Basis-Pfad: $base_path\n\n";

// Lösche zuerst alle existierenden Posts
$existing_posts = get_posts(['numberposts' => -1, 'post_type' => 'post', 'post_status' => 'any']);
foreach($existing_posts as $post) {
    wp_delete_post($post->ID, true);
}
echo "✓ " . count($existing_posts) . " existierende Posts gelöscht\n\n";

// Mapping von Ordnernamen zu Kategorie-Slugs (KORREKT aus der Datenbank)
$folder_to_category = [
    // Altlastensanierung - WICHTIG: Spezifische zuerst!
    'services/altlastensanierung/erkundung' => 'altlasten-erkundung',
    'services/altlastensanierung/sanierungsplanung' => 'altlasten-sanierungsplanung',
    'services/altlastensanierung/bodensanierung' => 'altlasten-bodensanierung',
    'services/altlastensanierung/grundwassersanierung' => 'altlasten-grundwassersanierung',
    'services/altlastensanierung/monitoring' => 'altlasten-monitoring',
    'services/altlastensanierung' => 'altlasten', // Hauptkategorie ZULETZT
    
    // Rückbaumanagement - WICHTIG: Spezifische zuerst!
    'services/rueckbaumanagement/planung' => 'rueckbau-planung',
    'services/rueckbaumanagement/ausschreibung' => 'rueckbau-ausschreibung',
    'services/rueckbaumanagement/durchfuehrung' => 'rueckbau-durchfuehrung',
    'services/rueckbaumanagement/entsorgung' => 'rueckbau-entsorgung',
    'services/rueckbaumanagement/recycling' => 'rueckbau-recycling',
    'services/rueckbaumanagement/dokumentation' => 'rueckbau-dokumentation',
    'services/rueckbaumanagement' => 'rueckbau', // Hauptkategorie ZULETZT
    
    // Schadstoff-Management - WICHTIG: Spezifische zuerst!
    'services/schadstoff-management/asbest' => 'schadstoffe-asbest',
    'services/schadstoff-management/pcb' => 'schadstoffe-pcb',
    'services/schadstoff-management/pak' => 'schadstoffe-pak',
    'services/schadstoff-management/kmf' => 'schadstoffe-kmf',
    'services/schadstoff-management/schwermetalle' => 'schadstoffe-schwermetalle',
    'services/schadstoff-management' => 'schadstoffe', // Hauptkategorie ZULETZT
    
    // Sicherheitskoordination - WICHTIG: Spezifische zuerst!
    'services/sicherheitskoordination/sigeko-planung' => 'sicherheitskoordination-sigeko-planung',
    'services/sicherheitskoordination/sigeko-ausfuehrung' => 'sicherheitskoordination-sigeko-ausfuehrung',
    'services/sicherheitskoordination/gefaehrdungsbeurteilung' => 'sicherheitskoordination-gefaehrdungsbeurteilung',
    'services/sicherheitskoordination/arbeitsschutz' => 'sicherheitskoordination-arbeitsschutz',
    'services/sicherheitskoordination/notfallmanagement' => 'sicherheitskoordination-notfallmanagement',
    'services/sicherheitskoordination' => 'sicherheitskoordination', // Hauptkategorie ZULETZT
    
    // Beratung & Mediation - WICHTIG: Spezifische zuerst!
    'services/beratung-mediation/projektberatung' => 'beratung-projektberatung',
    'services/beratung-mediation/baumediation' => 'beratung-baumediation',
    'services/beratung-mediation/gutachten' => 'beratung-gutachten',
    'services/beratung-mediation/compliance' => 'beratung-compliance',
    'services/beratung-mediation/schulungen' => 'beratung-schulungen',
    'services/beratung-mediation' => 'beratung', // Hauptkategorie ZULETZT
];

// Finde alle MD-Dateien
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
        $relative_path = str_replace($base_path, '', $filepath);
        
        // Finde die passende Kategorie basierend auf dem Pfad
        $category_slug = null;
        $folder_path = dirname($relative_path);
        $best_match_length = 0;
        
        // Finde die SPEZIFISCHSTE Übereinstimmung (längster Pfad)
        foreach ($folder_to_category as $folder => $slug) {
            if (strpos($folder_path, $folder) === 0) {
                $match_length = strlen($folder);
                if ($match_length > $best_match_length) {
                    $category_slug = $slug;
                    $best_match_length = $match_length;
                }
            }
        }
        
        if (!$category_slug) {
            echo "⚠ Keine Kategorie-Zuordnung für: $relative_path\n";
            $skipped++;
            continue;
        }
        
        // Hole die Kategorie
        $category = get_category_by_slug($category_slug);
        if (!$category) {
            echo "⚠ Kategorie nicht gefunden: $category_slug (Datei: $relative_path)\n";
            $skipped++;
            continue;
        }
        
        // Lade Markdown-Inhalt
        $markdown_content = file_get_contents($filepath);
        
        // Extrahiere Titel
        $title = '';
        if (preg_match('/^#\s+(.+)$/m', $markdown_content, $matches)) {
            $title = trim($matches[1]);
        } else {
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
        
        // Prüfe ob es ein Platzhalter-Post ist
        $is_placeholder = false;
        $content_length = strlen($content);
        
        // Liste von Platzhalter-Phrasen
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
            'Platzhalter'
        ];
        
        // Prüfe auf Platzhalter-Phrasen
        foreach ($placeholder_phrases as $phrase) {
            if (stripos($content, $phrase) !== false) {
                $is_placeholder = true;
                break;
            }
        }
        
        // Prüfe auf zu kurzen Inhalt (< 500 Zeichen)
        if (!$is_placeholder && $content_length < 500) {
            $is_placeholder = true;
        }
        
        // Bestimme Post-Status basierend auf Inhalt
        $post_status = $is_placeholder ? 'draft' : 'publish';
        
        // Erstelle Slug aus Dateiname
        $slug = str_replace('.md', '', $filename);
        
        // Erstelle Blog-Post
        $post_id = wp_insert_post([
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => $content,
            'post_status' => $post_status,
            'post_type' => 'post',
            'post_author' => 1,
            'post_category' => [$category->term_id]
        ]);
        
        if ($post_id) {
            $status_text = $post_status === 'draft' ? ' (als Entwurf)' : ' (veröffentlicht)';
            echo "✓ Blog-Post erstellt: $title$status_text\n";
            echo "  → Kategorie: {$category->name} (Slug: $category_slug)\n";
            echo "  → Pfad: $folder_path\n";
            if ($is_placeholder) {
                echo "  → Inhaltslänge: $content_length Zeichen (zu kurz/Platzhalter)\n";
            }
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
        echo "- {$cat->name}: " . count($posts) . " Posts";
        if ($cat->parent > 0) {
            $parent = get_category($cat->parent);
            echo " (Unterkategorie von {$parent->name})";
        }
        echo "\n";
    }
}