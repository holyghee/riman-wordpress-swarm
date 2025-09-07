<?php
/**
 * RIMAN Website - Komplettes Setup Script V2
 * Erstellt alle Kategorien, Seiten, Verknüpfungen und Blog-Posts
 * basierend auf der riman-website-codex Struktur
 * 
 * WARNUNG: Dieses Script löscht ALLE existierenden Inhalte und erstellt sie neu!
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

echo "=== RIMAN Website Komplettes Setup Script V2 ===\n\n";

// Prüfe ob Content-Dateien im Container vorhanden sind, ansonsten nutze lokalen Pfad
if (file_exists('/var/www/html/riman-content/')) {
    $base_path = '/var/www/html/riman-content/';
} else {
    $base_path = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content/';
}

echo "Content-Pfad: $base_path\n\n";

// ============================================================================
// SCHRITT 1: Lösche alle existierenden Inhalte
// ============================================================================
echo "SCHRITT 1: Lösche existierende Inhalte...\n";

// Lösche alle Attachments/Medien ZUERST (wichtig für doppelte Bilder)
$attachments = get_posts(array('numberposts' => -1, 'post_type' => 'attachment', 'post_status' => 'any'));
foreach($attachments as $attachment) {
    wp_delete_attachment($attachment->ID, true);
}
echo "- " . count($attachments) . " Attachments gelöscht\n";

// Lösche alle Posts
$posts = get_posts(array('numberposts' => -1, 'post_type' => 'post', 'post_status' => 'any'));
foreach($posts as $post) {
    wp_delete_post($post->ID, true);
}
echo "- " . count($posts) . " Posts gelöscht\n";

// Lösche alle Seiten
$pages = get_posts(array('numberposts' => -1, 'post_type' => 'page', 'post_status' => 'any'));
foreach($pages as $page) {
    wp_delete_post($page->ID, true);
}
echo "- " . count($pages) . " Seiten gelöscht\n";

// Lösche alle Kategorien (außer Uncategorized)
$categories = get_terms(array('taxonomy' => 'category', 'hide_empty' => false));
foreach ($categories as $category) {
    if ($category->slug !== 'uncategorized') {
        wp_delete_term($category->term_id, 'category');
    }
}
echo "- " . (count($categories) - 1) . " Kategorien gelöscht\n\n";

// ============================================================================
// Helper Funktionen
// ============================================================================

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
            $skip_next_separator = true; // Markiere dass wir den nächsten --- überspringen sollen
            continue;
        }
        
        // Überspringe --- nach SEO Keywords
        if ($skip_next_separator && trim($line) === '---') {
            $skip_next_separator = false;
            continue;
        }
        
        // Überspringe leere Zeilen direkt nach --- Separator
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
    $first_h1_skipped = false; // Flag für die erste H1
    $skip_lines = 0; // Anzahl der zu überspringenden Zeilen
    
    for ($index = 0; $index < count($lines); $index++) {
        // Überspringe Zeilen wenn nötig
        if ($skip_lines > 0) {
            $skip_lines--;
            continue;
        }
        
        $line = $lines[$index];
        // Custom Media Blocks (beginnt mit ::: und media-)
        if (preg_match('/^:::\s+(media-left|media-right|media-center)\s+(.*)/', $line, $matches)) {
            $alignment = str_replace('media-', '', $matches[1]); // left, right, or center
            $media_content = trim($matches[2]);
            
            // Parse media path und caption
            $media_parts = explode('|', $media_content);
            $image_path = trim($media_parts[0]);
            $caption = isset($media_parts[1]) ? trim($media_parts[1]) : '';
            
            // Erstelle Media & Text Block für left/right, oder Image Block für center
            if ($alignment === 'center') {
                // Zentriertes Bild
                $content .= "<!-- wp:image {\"align\":\"center\"} -->\n";
                $content .= "<figure class=\"wp-block-image aligncenter\">";
                $content .= "<img src=\"$image_path\" alt=\"$caption\"/>";
                if ($caption) {
                    $content .= "<figcaption class=\"wp-element-caption\">$caption</figcaption>";
                }
                $content .= "</figure>\n";
                $content .= "<!-- /wp:image -->\n\n";
            } else {
                // Media & Text Block für left/right
                $mediaPosition = ($alignment === 'right') ? 'right' : 'left';
                $content .= "<!-- wp:media-text {\"mediaPosition\":\"$mediaPosition\",\"mediaType\":\"image\"} -->\n";
                $content .= "<div class=\"wp-block-media-text has-media-on-the-$mediaPosition is-stacked-on-mobile\">";
                $content .= "<figure class=\"wp-block-media-text__media\">";
                $content .= "<img src=\"$image_path\" alt=\"$caption\"/>";
                $content .= "</figure>";
                $content .= "<div class=\"wp-block-media-text__content\">\n";
                
                // Sammle den Content zwischen ::: Markern
                $media_text_content = '';
                $next_index = $index + 1;
                
                // Sammle alle Zeilen bis zum schließenden :::
                while (isset($lines[$next_index]) && trim($lines[$next_index]) !== ':::') {
                    $text_line = trim($lines[$next_index]);
                    
                    // Überspringe leere Zeilen
                    if ($text_line === '') {
                        $next_index++;
                        $skip_lines++;
                        continue;
                    }
                    
                    // Blockquote
                    if (preg_match('/^>\s+(.+)/', $text_line, $quote_match)) {
                        $quote_text = trim($quote_match[1]);
                        $media_text_content .= "<!-- wp:quote -->\n";
                        $media_text_content .= "<blockquote class=\"wp-block-quote\"><p>$quote_text</p></blockquote>\n";
                        $media_text_content .= "<!-- /wp:quote -->\n";
                    }
                    // Normale Zeile
                    else {
                        // Konvertiere Markdown-Links zu HTML
                        $text_line = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2">$1</a>', $text_line);
                        
                        // Konvertiere Fettschrift
                        $text_line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text_line);
                        
                        $media_text_content .= "<!-- wp:paragraph -->\n";
                        $media_text_content .= "<p>$text_line</p>\n";
                        $media_text_content .= "<!-- /wp:paragraph -->\n";
                    }
                    
                    $next_index++;
                    $skip_lines++;
                }
                
                // Überspringe auch das schließende :::
                if (isset($lines[$next_index]) && trim($lines[$next_index]) === ':::') {
                    $skip_lines++;
                }
                
                $content .= $media_text_content;
                $content .= "</div></div>\n";
                $content .= "<!-- /wp:media-text -->\n\n";
            }
            continue;
        }
        
        // Code-Blöcke
        if (preg_match('/^```/', $line)) {
            if ($in_code) {
                if (!empty($code_lines)) {
                    $code_content = implode("\n", $code_lines);
                    $content .= "<!-- wp:code -->\n<pre class=\"wp-block-code\"><code>" . 
                               htmlspecialchars($code_content) . "</code></pre>\n<!-- /wp:code -->\n\n";
                }
                $in_code = false;
                $code_lines = [];
            } else {
                $in_code = true;
            }
            continue;
        }
        
        if ($in_code) {
            $code_lines[] = $line;
            continue;
        }
        
        // Listen beenden wenn nötig
        if ($in_list && !preg_match('/^[\s]*[-*+•]/', $line) && !preg_match('/^[\s]*\d+\./', $line)) {
            if (!empty($list_items)) {
                $content .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n";
                foreach ($list_items as $item) {
                    $content .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->\n";
                }
                $content .= "</ul>\n<!-- /wp:list -->\n\n";
            }
            $in_list = false;
            $list_items = [];
        }
        
        // Überschriften
        if (preg_match('/^#{1,6}\s+(.+)/', $line, $matches)) {
            $level = strlen(explode(' ', $line)[0]);
            $heading_text = trim($matches[1]);
            
            // Überspringe nur die ERSTE H1-Überschrift wenn sie dem Seitentitel entspricht
            if ($level === 1 && !$first_h1_skipped) {
                $first_h1_skipped = true;
                // Prüfe ob H1 identisch mit Seitentitel ist
                if (strcasecmp(trim($heading_text), trim($page_title)) === 0) {
                    continue; // Überspringe nur wenn identisch
                }
            }
            
            $content .= "<!-- wp:heading {\"level\":$level} -->\n<h$level class=\"wp-block-heading\">$heading_text</h$level>\n<!-- /wp:heading -->\n\n";
        }
        // Listen
        elseif (preg_match('/^[\s]*[-*+•]\s+(.+)/', $line, $matches) || preg_match('/^[\s]*\d+\.\s+(.+)/', $line, $matches)) {
            $in_list = true;
            $item_text = trim($matches[1]);
            // Markdown-Formatierung auch in Listen verarbeiten
            $item_text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $item_text);
            $item_text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $item_text);
            $item_text = preg_replace('/_(.+?)_/', '<em>$1</em>', $item_text);
            $list_items[] = $item_text;
        }
        // Blockquotes
        elseif (preg_match('/^>\s+(.+)/', $line, $matches)) {
            $content .= "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>" . 
                       trim($matches[1]) . "</p></blockquote>\n<!-- /wp:quote -->\n\n";
        }
        // Horizontale Linie
        elseif (preg_match('/^---+$/', $line)) {
            $content .= "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->\n\n";
        }
        // Normaler Paragraph
        elseif (trim($line) !== '') {
            // Fett und Kursiv verarbeiten
            $line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line);
            $line = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $line);
            $line = preg_replace('/_(.+?)_/', '<em>$1</em>', $line);
            
            $content .= "<!-- wp:paragraph -->\n<p>" . trim($line) . "</p>\n<!-- /wp:paragraph -->\n\n";
        }
    }
    
    // Restliche Listen abschließen
    if ($in_list && !empty($list_items)) {
        $content .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n";
        foreach ($list_items as $item) {
            $content .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->\n";
        }
        $content .= "</ul>\n<!-- /wp:list -->\n\n";
    }
    
    return $content;
}

// ============================================================================
// SCHRITT 2: Erstelle die Kategorie-Struktur mit allen verfügbaren Inhalten
// ============================================================================
echo "SCHRITT 2: Erstelle Kategorien und Seiten...\n";

$structure = [
    // ALTLASTENSANIERUNG
    [
        'name' => 'Altlastensanierung',
        'slug' => 'altlasten',
        'content_file' => 'services/altlastensanierung/main.md',
        'children' => [
            ['name' => 'Altlasten-Erkundung', 'slug' => 'erkundung', 'content_file' => 'services/altlastensanierung/erkundung/main.md'],
            ['name' => 'Sanierungsplanung', 'slug' => 'sanierungsplanung', 'content_file' => 'services/altlastensanierung/sanierungsplanung/main.md'],
            ['name' => 'Bodensanierung', 'slug' => 'bodensanierung', 'content_file' => 'services/altlastensanierung/bodensanierung/main.md'],
            ['name' => 'Grundwassersanierung', 'slug' => 'grundwassersanierung', 'content_file' => 'services/altlastensanierung/grundwassersanierung/main.md'],
            ['name' => 'Altlasten-Monitoring', 'slug' => 'monitoring', 'content_file' => 'services/altlastensanierung/monitoring/main.md']
        ]
    ],
    // RÜCKBAUMANAGEMENT
    [
        'name' => 'Rückbaumanagement',
        'slug' => 'rueckbau',
        'content_file' => 'services/rueckbaumanagement/main.md',
        'children' => [
            ['name' => 'Rückbauplanung', 'slug' => 'planung', 'content_file' => 'services/rueckbaumanagement/planung/main.md'],
            ['name' => 'Ausschreibung', 'slug' => 'ausschreibung', 'content_file' => 'services/rueckbaumanagement/ausschreibung/main.md'],
            ['name' => 'Durchführung', 'slug' => 'durchfuehrung', 'content_file' => 'services/rueckbaumanagement/durchfuehrung/main.md'],
            ['name' => 'Entsorgung', 'slug' => 'entsorgung', 'content_file' => 'services/rueckbaumanagement/entsorgung/main.md'],
            ['name' => 'Recycling', 'slug' => 'recycling', 'content_file' => 'services/rueckbaumanagement/recycling/main.md'],
            ['name' => 'Dokumentation', 'slug' => 'dokumentation', 'content_file' => 'services/rueckbaumanagement/dokumentation/main.md']
        ]
    ],
    // SCHADSTOFF-MANAGEMENT
    [
        'name' => 'Schadstoff-Management',
        'slug' => 'schadstoffe',
        'content_file' => 'services/schadstoff-management/main.md',
        'children' => [
            ['name' => 'Asbestsanierung', 'slug' => 'asbest', 'content_file' => 'services/schadstoff-management/asbest/main.md'],
            ['name' => 'PCB-Sanierung', 'slug' => 'pcb', 'content_file' => 'services/schadstoff-management/pcb/main.md'],
            ['name' => 'PAK-Sanierung', 'slug' => 'pak', 'content_file' => 'services/schadstoff-management/pak/main.md'],
            ['name' => 'KMF-Sanierung', 'slug' => 'kmf', 'content_file' => 'services/schadstoff-management/kmf/main.md'],
            ['name' => 'Schwermetalle', 'slug' => 'schwermetalle', 'content_file' => 'services/schadstoff-management/schwermetalle/main.md']
        ]
    ],
    // SICHERHEITSKOORDINATION
    [
        'name' => 'Sicherheitskoordination',
        'slug' => 'sicherheitskoordination',
        'content_file' => 'services/sicherheitskoordination/main.md',
        'children' => [
            ['name' => 'SiGeKo Planung', 'slug' => 'sigeko-planung', 'content_file' => 'services/sicherheitskoordination/sigeko-planung/main.md'],
            ['name' => 'SiGeKo Ausführung', 'slug' => 'sigeko-ausfuehrung', 'content_file' => 'services/sicherheitskoordination/sigeko-ausfuehrung/main.md'],
            ['name' => 'Gefährdungsbeurteilung', 'slug' => 'gefaehrdungsbeurteilung', 'content_file' => 'services/sicherheitskoordination/gefaehrdungsbeurteilung/main.md'],
            ['name' => 'Arbeitsschutz', 'slug' => 'arbeitsschutz', 'content_file' => 'services/sicherheitskoordination/arbeitsschutz/main.md'],
            ['name' => 'Notfallmanagement', 'slug' => 'notfallmanagement', 'content_file' => 'services/sicherheitskoordination/notfallmanagement/main.md']
        ]
    ],
    // BERATUNG & MEDIATION
    [
        'name' => 'Beratung & Mediation',
        'slug' => 'beratung',
        'content_file' => 'services/beratung-mediation/main.md',
        'children' => [
            ['name' => 'Projektberatung', 'slug' => 'projektberatung', 'content_file' => 'services/beratung-mediation/projektberatung/main.md'],
            ['name' => 'Baumediation', 'slug' => 'baumediation', 'content_file' => 'services/beratung-mediation/baumediation/main.md'],
            ['name' => 'Gutachten', 'slug' => 'gutachten', 'content_file' => 'services/beratung-mediation/gutachten/main.md'],
            ['name' => 'Compliance', 'slug' => 'compliance', 'content_file' => 'services/beratung-mediation/compliance/main.md'],
            ['name' => 'Schulungen', 'slug' => 'schulungen', 'content_file' => 'services/beratung-mediation/schulungen/main.md']
        ]
    ]
];

$created_categories = [];
$created_pages = [];

foreach ($structure as $main_cat) {
    // Erstelle Hauptkategorie
    $main_category = wp_insert_term($main_cat['name'], 'category', [
        'slug' => $main_cat['slug'],
        'description' => 'Hauptkategorie: ' . $main_cat['name']
    ]);
    
    if (!is_wp_error($main_category)) {
        $main_cat_id = $main_category['term_id'];
        $created_categories[$main_cat['slug']] = $main_cat_id;
        echo "\n✓ Hauptkategorie erstellt: {$main_cat['name']} (ID: $main_cat_id)\n";
        
        // Lade Content für Hauptkategorie-Seite
        $content = '';
        $content_file = $base_path . $main_cat['content_file'];
        if (file_exists($content_file)) {
            $markdown = file_get_contents($content_file);
            $content = convert_markdown_to_blocks($markdown, $main_cat['name']);
            echo "  ✓ Content geladen: {$main_cat['content_file']}\n";
        } else {
            echo "  ⚠ Content nicht gefunden: {$main_cat['content_file']}\n";
            $content = "<!-- wp:paragraph -->\n<p>Willkommen bei {$main_cat['name']}.</p>\n<!-- /wp:paragraph -->\n";
        }
        
        // Erstelle Seite für Hauptkategorie
        $page_id = wp_insert_post([
            'post_title' => $main_cat['name'],
            'post_name' => $main_cat['slug'],
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1
        ]);
        
        if ($page_id) {
            $created_pages[$main_cat['slug']] = $page_id;
            
            // WICHTIG: Verknüpfung herstellen
            update_post_meta($page_id, '_linked_category_id', $main_cat_id);
            update_term_meta($main_cat_id, '_linked_page_id', $page_id);
            
            // Featured Image Assignment: Check if category has thumbnail and assign to page
            $category_thumbnail_id = get_term_meta($main_cat_id, '_thumbnail_id', true);
            if ($category_thumbnail_id && !is_wp_error($category_thumbnail_id)) {
                // Verify attachment exists
                $attachment = get_post($category_thumbnail_id);
                if ($attachment && $attachment->post_type === 'attachment') {
                    // Try set_post_thumbnail first
                    $thumbnail_set = set_post_thumbnail($page_id, $category_thumbnail_id);
                    if (!$thumbnail_set) {
                        // Fallback: use meta directly
                        update_post_meta($page_id, '_thumbnail_id', $category_thumbnail_id);
                        echo "  ✓ Featured image assigned via fallback method (ID: $category_thumbnail_id)\n";
                    } else {
                        echo "  ✓ Featured image assigned to page from category thumbnail (ID: $category_thumbnail_id)\n";
                    }
                } else {
                    echo "  ⚠ Category thumbnail attachment not found (ID: $category_thumbnail_id)\n";
                }
            }
            
            echo "  ✓ Seite erstellt und verknüpft: {$main_cat['name']} (ID: $page_id)\n";
        }
        
        // Erstelle Unterkategorien
        if (isset($main_cat['children'])) {
            foreach ($main_cat['children'] as $sub_cat) {
                $sub_category = wp_insert_term($sub_cat['name'], 'category', [
                    'slug' => $main_cat['slug'] . '-' . $sub_cat['slug'],
                    'parent' => $main_cat_id,
                    'description' => 'Unterkategorie von ' . $main_cat['name']
                ]);
                
                if (!is_wp_error($sub_category)) {
                    $sub_cat_id = $sub_category['term_id'];
                    echo "  ✓ Unterkategorie erstellt: {$sub_cat['name']} (ID: $sub_cat_id)\n";
                    
                    // Lade Content für Unterkategorie-Seite
                    $sub_content = '';
                    $sub_content_file = $base_path . $sub_cat['content_file'];
                    if (file_exists($sub_content_file)) {
                        $markdown = file_get_contents($sub_content_file);
                        $sub_content = convert_markdown_to_blocks($markdown, $sub_cat['name']);
                        echo "    ✓ Content geladen: {$sub_cat['content_file']}\n";
                    } else {
                        echo "    ⚠ Content nicht gefunden: {$sub_cat['content_file']}\n";
                        $sub_content = "<!-- wp:paragraph -->\n<p>Willkommen bei {$sub_cat['name']}.</p>\n<!-- /wp:paragraph -->\n";
                    }
                    
                    // Erstelle Seite für Unterkategorie
                    $sub_page_id = wp_insert_post([
                        'post_title' => $sub_cat['name'],
                        'post_name' => $main_cat['slug'] . '-' . $sub_cat['slug'],
                        'post_content' => $sub_content,
                        'post_status' => 'publish',
                        'post_type' => 'page',
                        'post_parent' => $page_id,
                        'post_author' => 1
                    ]);
                    
                    if ($sub_page_id) {
                        // WICHTIG: Verknüpfung herstellen
                        update_post_meta($sub_page_id, '_linked_category_id', $sub_cat_id);
                        update_term_meta($sub_cat_id, '_linked_page_id', $sub_page_id);
                        
                        // Featured Image Assignment: Check if subcategory has thumbnail and assign to page
                        $subcategory_thumbnail_id = get_term_meta($sub_cat_id, '_thumbnail_id', true);
                        if ($subcategory_thumbnail_id && !is_wp_error($subcategory_thumbnail_id)) {
                            // Verify attachment exists
                            $attachment = get_post($subcategory_thumbnail_id);
                            if ($attachment && $attachment->post_type === 'attachment') {
                                // Try set_post_thumbnail first
                                $thumbnail_set = set_post_thumbnail($sub_page_id, $subcategory_thumbnail_id);
                                if (!$thumbnail_set) {
                                    // Fallback: use meta directly
                                    update_post_meta($sub_page_id, '_thumbnail_id', $subcategory_thumbnail_id);
                                    echo "    ✓ Featured image assigned via fallback method (ID: $subcategory_thumbnail_id)\n";
                                } else {
                                    echo "    ✓ Featured image assigned to subpage from category thumbnail (ID: $subcategory_thumbnail_id)\n";
                                }
                            } else {
                                echo "    ⚠ Subcategory thumbnail attachment not found (ID: $subcategory_thumbnail_id)\n";
                            }
                        }
                        
                        echo "    ✓ Seite erstellt und verknüpft: {$sub_cat['name']} (ID: $sub_page_id)\n";
                    }
                }
            }
        }
    }
}

// ============================================================================
// SCHRITT 3: Erstelle Blog-Posts (Beispiel-Posts für jede Hauptkategorie)
// ============================================================================
echo "\nSCHRITT 3: Erstelle Blog-Posts...\n";

$blog_posts = [
    [
        'title' => 'Neue Richtlinien für Altlastensanierung 2025',
        'category' => 'altlasten',
        'content' => 'Die neuen gesetzlichen Anforderungen für die Altlastensanierung bringen wichtige Änderungen mit sich. Wir erklären, was Sie beachten müssen.'
    ],
    [
        'title' => 'Erfolgreiches Rückbauprojekt in München',
        'category' => 'rueckbau',
        'content' => 'Unser Team hat erfolgreich ein 5-stöckiges Bürogebäude in München zurückgebaut. Hier unser Projektbericht.'
    ],
    [
        'title' => 'Asbestfunde in Schulgebäuden - Was tun?',
        'category' => 'schadstoffe',
        'content' => 'Immer wieder werden in älteren Schulgebäuden Asbestbelastungen gefunden. Wir zeigen, wie eine professionelle Sanierung abläuft.'
    ],
    [
        'title' => 'Neue SiGeKo-Verordnung ab 2025',
        'category' => 'sicherheit',
        'content' => 'Die Baustellenverordnung wurde überarbeitet. Diese Änderungen betreffen Bauherren und Koordinatoren.'
    ],
    [
        'title' => 'Mediation spart Kosten bei Bauprojekten',
        'category' => 'beratung',
        'content' => 'Konflikte auf Baustellen können teuer werden. Professionelle Mediation hilft, Streitigkeiten schnell und kostengünstig zu lösen.'
    ]
];

foreach ($blog_posts as $post_data) {
    $category_id = isset($created_categories[$post_data['category']]) ? 
                   $created_categories[$post_data['category']] : 1;
    
    $post_id = wp_insert_post([
        'post_title' => $post_data['title'],
        'post_content' => "<!-- wp:paragraph -->\n<p>" . $post_data['content'] . "</p>\n<!-- /wp:paragraph -->\n",
        'post_status' => 'publish',
        'post_type' => 'post',
        'post_author' => 1,
        'post_category' => [$category_id]
    ]);
    
    if ($post_id) {
        echo "✓ Blog-Post erstellt: {$post_data['title']} (Kategorie: {$post_data['category']})\n";
    }
}

// ============================================================================
// SCHRITT 4: Erstelle Hauptseiten
// ============================================================================
echo "\nSCHRITT 4: Erstelle Hauptseiten...\n";

$main_pages = [
    ['title' => 'Startseite', 'slug' => 'startseite', 'file' => 'pages/home.md'],
    ['title' => 'Über uns', 'slug' => 'ueber-uns', 'file' => 'company/about.md'],
    ['title' => 'Kontakt', 'slug' => 'kontakt', 'file' => 'company/contact.md'],
    ['title' => 'Impressum', 'slug' => 'impressum', 'file' => 'company/impressum.md']
];

foreach ($main_pages as $page_data) {
    $content = '';
    $file_path = $base_path . $page_data['file'];
    
    if (file_exists($file_path)) {
        $markdown = file_get_contents($file_path);
        $content = convert_markdown_to_blocks($markdown, $page_data['title']);
        echo "✓ Content geladen: {$page_data['file']}\n";
    } else {
        $content = "<!-- wp:paragraph -->\n<p>Inhalt für {$page_data['title']} folgt.</p>\n<!-- /wp:paragraph -->\n";
        echo "⚠ Content nicht gefunden: {$page_data['file']}\n";
    }
    
    $page_id = wp_insert_post([
        'post_title' => $page_data['title'],
        'post_name' => $page_data['slug'],
        'post_content' => $content,
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_author' => 1
    ]);
    
    if ($page_id && $page_data['slug'] === 'startseite') {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $page_id);
        echo "  → Als Homepage festgelegt\n";
    }
}

// ============================================================================
// SCHRITT 5: Prüfe und aktiviere Category Page Connector Plugin
// ============================================================================
echo "\nSCHRITT 5: Prüfe Plugin-Status...\n";

if (!is_plugin_active('category-page-connector/category-page-connector.php')) {
    activate_plugin('category-page-connector/category-page-connector.php');
    echo "✓ Category Page Connector Plugin aktiviert\n";
} else {
    echo "✓ Category Page Connector Plugin bereits aktiv\n";
}

// ============================================================================
// Zusammenfassung
// ============================================================================
echo "\n=== SETUP ABGESCHLOSSEN ===\n\n";

$total_cats = wp_count_terms('category', array('hide_empty' => false)) - 1; // minus uncategorized
$total_pages = wp_count_posts('page')->publish;
$total_posts = wp_count_posts('post')->publish;

echo "Erstellt wurden:\n";
echo "- $total_cats Kategorien\n";
echo "- $total_pages Seiten\n";
echo "- $total_posts Blog-Posts\n\n";

// Teste eine Verknüpfung
$test_cat = get_term_by('slug', 'altlasten', 'category');
if ($test_cat) {
    $linked_page = get_term_meta($test_cat->term_id, '_linked_page_id', true);
    if ($linked_page) {
        echo "✓ Verknüpfungstest erfolgreich: Kategorie 'Altlastensanierung' ist mit Seite ID $linked_page verknüpft\n";
    } else {
        echo "⚠ Warnung: Verknüpfung für 'Altlastensanierung' nicht gefunden\n";
    }
}

echo "\n✅ Setup erfolgreich abgeschlossen!\n";
echo "Besuche http://localhost:8801 um die Website zu sehen.\n";