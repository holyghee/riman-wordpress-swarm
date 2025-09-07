<?php
/**
 * RIMAN Website - Komplettes Setup Script
 * Erstellt alle Kategorien, Seiten und Verknüpfungen basierend auf der riman-website-codex Struktur
 * 
 * WARNUNG: Dieses Script löscht ALLE existierenden Kategorien und erstellt sie neu!
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

echo "=== RIMAN Website Setup Script ===\n\n";

// ============================================================================
// SCHRITT 1: Lösche alle existierenden Kategorien (außer Uncategorized)
// ============================================================================
echo "Schritt 1: Lösche existierende Kategorien...\n";

$existing_categories = get_terms(array(
    'taxonomy' => 'category',
    'hide_empty' => false,
));

foreach ($existing_categories as $category) {
    if ($category->slug !== 'uncategorized') {
        wp_delete_term($category->term_id, 'category');
        echo "- Gelöscht: {$category->name}\n";
    }
}

// ============================================================================
// SCHRITT 2: Definiere die komplette Struktur
// ============================================================================
echo "\nSchritt 2: Definiere Website-Struktur...\n";

$website_structure = [
    // ALTLASTENSANIERUNG
    [
        'type' => 'category',
        'name' => 'Altlastensanierung',
        'slug' => 'altlasten',
        'description' => 'Fachgerechte Sanierung kontaminierter Böden und Grundwässer',
        'parent' => null,
        'page' => [
            'title' => 'Altlastensanierung',
            'slug' => 'altlastensanierung',
            'content_file' => 'services/altlastensanierung/main.md',
            'parent_page' => null
        ],
        'children' => [
            [
                'type' => 'category',
                'name' => 'Altlasten-Erkundung',
                'slug' => 'erkundung',
                'description' => 'Systematische Untersuchung kontaminierter Standorte',
                'page' => [
                    'title' => 'Altlasten-Erkundung',
                    'slug' => 'altlasten-erkundung',
                    'content_file' => 'services/altlastensanierung/erkundung.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'Sanierungsplanung',
                'slug' => 'sanierungsplanung',
                'description' => 'Entwicklung optimaler Sanierungsstrategien',
                'page' => [
                    'title' => 'Sanierungsplanung',
                    'slug' => 'sanierungsplanung',
                    'content_file' => 'services/altlastensanierung/sanierungsplanung.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'Bodensanierung',
                'slug' => 'bodensanierung',
                'description' => 'Fachgerechte Sanierung kontaminierter Böden',
                'page' => [
                    'title' => 'Bodensanierung',
                    'slug' => 'bodensanierung',
                    'content_file' => 'services/altlastensanierung/bodensanierung.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'Grundwassersanierung',
                'slug' => 'grundwassersanierung',
                'description' => 'Reinigung kontaminierter Grundwässer',
                'page' => [
                    'title' => 'Grundwassersanierung',
                    'slug' => 'grundwassersanierung',
                    'content_file' => 'services/altlastensanierung/grundwassersanierung.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'Altlasten-Monitoring',
                'slug' => 'monitoring',
                'description' => 'Überwachung und Kontrolle',
                'page' => [
                    'title' => 'Altlasten-Monitoring',
                    'slug' => 'altlasten-monitoring',
                    'content_file' => 'services/altlastensanierung/monitoring.md'
                ]
            ]
        ]
    ],
    
    // RÜCKBAU & ABBRUCH
    [
        'type' => 'category',
        'name' => 'Rückbau & Abbruch',
        'slug' => 'rueckbau',
        'description' => 'Professioneller Rückbau und Abbruch von Gebäuden und Anlagen',
        'parent' => null,
        'page' => [
            'title' => 'Rückbau & Abbruch',
            'slug' => 'rueckbau-abbruch',
            'content_file' => 'services/rueckbaumanagement/main.md',
            'parent_page' => null
        ],
        'children' => [
            [
                'type' => 'category',
                'name' => 'Rückbauplanung',
                'slug' => 'rueckbauplanung',
                'description' => 'Professionelle Planung von Rückbaumaßnahmen',
                'page' => [
                    'title' => 'Rückbauplanung',
                    'slug' => 'rueckbauplanung',
                    'content_file' => 'services/rueckbaumanagement/planung.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'Wohnungsentkernungen',
                'slug' => 'wohnungsentkernungen',
                'description' => 'Fachgerechte Entkernung von Wohnräumen',
                'page' => [
                    'title' => 'Wohnungsentkernungen',
                    'slug' => 'wohnungsentkernungen',
                    'content_file' => 'services/rueckbaumanagement/wohnungsentkernung.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'Teilabbruch',
                'slug' => 'teilabbruch',
                'description' => 'Präziser Rückbau einzelner Gebäudeteile',
                'page' => [
                    'title' => 'Teilabbruch',
                    'slug' => 'teilabbruch',
                    'content_file' => 'services/rueckbaumanagement/teilabbruch.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'Komplettabbruch',
                'slug' => 'komplettabbruch',
                'description' => 'Vollständiger Gebäuderückbau',
                'page' => [
                    'title' => 'Komplettabbruch',
                    'slug' => 'komplettabbruch',
                    'content_file' => 'services/rueckbaumanagement/komplettabbruch.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'Industrierückbau',
                'slug' => 'industrierueckbau',
                'description' => 'Demontage industrieller Anlagen',
                'page' => [
                    'title' => 'Industrierückbau',
                    'slug' => 'industrierueckbau',
                    'content_file' => 'services/rueckbaumanagement/industrierueckbau.md'
                ]
            ]
        ]
    ],
    
    // SCHADSTOFFSANIERUNG
    [
        'type' => 'category',
        'name' => 'Schadstoffsanierung',
        'slug' => 'schadstoffe',
        'description' => 'Fachgerechte Sanierung von Schadstoffen in Gebäuden',
        'parent' => null,
        'page' => [
            'title' => 'Schadstoffsanierung',
            'slug' => 'schadstoffsanierung',
            'content_file' => 'services/schadstoff-management/main.md',
            'parent_page' => null
        ],
        'children' => [
            [
                'type' => 'category',
                'name' => 'Asbestsanierung',
                'slug' => 'asbestsanierung',
                'description' => 'Sichere Entfernung von Asbestmaterialien',
                'page' => [
                    'title' => 'Asbestsanierung',
                    'slug' => 'asbestsanierung',
                    'content_file' => 'services/schadstoff-management/asbest.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'PCB-Sanierung',
                'slug' => 'pcb-sanierung',
                'description' => 'Beseitigung von PCB-Belastungen',
                'page' => [
                    'title' => 'PCB-Sanierung',
                    'slug' => 'pcb-sanierung',
                    'content_file' => 'services/schadstoff-management/pcb.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'Schimmelsanierung',
                'slug' => 'schimmelsanierung',
                'description' => 'Nachhaltige Schimmelbeseitigung',
                'page' => [
                    'title' => 'Schimmelsanierung',
                    'slug' => 'schimmelsanierung',
                    'content_file' => 'services/schadstoff-management/schimmel.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'KMF-Sanierung',
                'slug' => 'kmf-sanierung',
                'description' => 'Entfernung künstlicher Mineralfasern',
                'page' => [
                    'title' => 'KMF-Sanierung',
                    'slug' => 'kmf-sanierung',
                    'content_file' => 'services/schadstoff-management/kmf.md'
                ]
            ]
        ]
    ],
    
    // ARBEITSSICHERHEIT
    [
        'type' => 'category',
        'name' => 'Arbeitssicherheit',
        'slug' => 'sicherheit',
        'description' => 'Umfassende Arbeitssicherheit und Gesundheitsschutz',
        'parent' => null,
        'page' => [
            'title' => 'Arbeitssicherheit',
            'slug' => 'arbeitssicherheit',
            'content_file' => 'services/sicherheitskoordination/main.md',
            'parent_page' => null
        ],
        'children' => [
            [
                'type' => 'category',
                'name' => 'Sicherheitskoordination',
                'slug' => 'sige-koordination',
                'description' => 'SiGe-Koordination nach Baustellenverordnung',
                'page' => [
                    'title' => 'Sicherheitskoordination',
                    'slug' => 'sicherheitskoordination',
                    'content_file' => 'services/sicherheitskoordination/sigeko-planung.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'Gefährdungsbeurteilung',
                'slug' => 'gefaehrdungsbeurteilung',
                'description' => 'Systematische Risikoanalyse',
                'page' => [
                    'title' => 'Gefährdungsbeurteilung',
                    'slug' => 'gefaehrdungsbeurteilung',
                    'content_file' => 'services/sicherheitskoordination/gefaehrdungsbeurteilung.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'Arbeitsschutzbetreuung',
                'slug' => 'arbeitsschutzbetreuung',
                'description' => 'Externe Fachkraft für Arbeitssicherheit',
                'page' => [
                    'title' => 'Arbeitsschutzbetreuung',
                    'slug' => 'arbeitsschutzbetreuung',
                    'content_file' => 'services/sicherheitskoordination/arbeitsschutzbetreuung.md'
                ]
            ]
        ]
    ],
    
    // BERATUNG & MEDIATION
    [
        'type' => 'category',
        'name' => 'Beratung & Mediation',
        'slug' => 'beratung',
        'description' => 'Professionelle Beratung und Konfliktlösung',
        'parent' => null,
        'page' => [
            'title' => 'Beratung & Mediation',
            'slug' => 'beratung-mediation',
            'content_file' => 'services/beratung-mediation/main.md',
            'parent_page' => null
        ],
        'children' => [
            [
                'type' => 'category',
                'name' => 'Bauberatung',
                'slug' => 'bauberatung',
                'description' => 'Expertenberatung für Bauprojekte',
                'page' => [
                    'title' => 'Bauberatung',
                    'slug' => 'bauberatung',
                    'content_file' => 'services/beratung-mediation/projektberatung.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'Umweltberatung',
                'slug' => 'umweltberatung',
                'description' => 'Nachhaltige Umweltkonzepte',
                'page' => [
                    'title' => 'Umweltberatung',
                    'slug' => 'umweltberatung',
                    'content_file' => 'services/beratung-mediation/umweltberatung.md'
                ]
            ],
            [
                'type' => 'category',
                'name' => 'Konfliktmediation',
                'slug' => 'konfliktmediation',
                'description' => 'Professionelle Streitschlichtung',
                'page' => [
                    'title' => 'Konfliktmediation',
                    'slug' => 'konfliktmediation',
                    'content_file' => 'services/beratung-mediation/baumediation.md'
                ]
            ]
        ]
    ]
];

// ============================================================================
// SCHRITT 3: Hilfsfunktionen
// ============================================================================

/**
 * Konvertiert Markdown zu WordPress Blocks
 */
function convert_markdown_to_blocks($markdown) {
    // Entferne Meta-Informationen
    $markdown = preg_replace('/^\*\*Meta Description:\*\*.*?\n/m', '', $markdown);
    $markdown = preg_replace('/^\*\*SEO Keywords:\*\*.*?\n/m', '', $markdown);
    $markdown = preg_replace('/^---\s*\n/m', '', $markdown);
    
    $blocks = '';
    $lines = explode("\n", $markdown);
    $in_list = false;
    $list_items = [];
    $current_paragraph = '';
    
    foreach ($lines as $line) {
        $original_line = $line;
        $line = trim($line);
        
        // Leere Zeile
        if (empty($line)) {
            if ($in_list) {
                $blocks .= create_wp_list_block($list_items);
                $in_list = false;
                $list_items = [];
            }
            if (!empty($current_paragraph)) {
                $blocks .= "<!-- wp:paragraph -->\n<p>" . trim($current_paragraph) . "</p>\n<!-- /wp:paragraph -->\n\n";
                $current_paragraph = '';
            }
            continue;
        }
        
        // Hauptüberschrift (ignorieren)
        if (preg_match('/^#\s+(.+)$/', $line, $matches)) {
            continue;
        }
        // Überschrift Level 2
        elseif (preg_match('/^##\s+(.+)$/', $line, $matches)) {
            // Speichere vorherigen Content
            if ($in_list) {
                $blocks .= create_wp_list_block($list_items);
                $in_list = false;
                $list_items = [];
            }
            if (!empty($current_paragraph)) {
                $blocks .= "<!-- wp:paragraph -->\n<p>" . trim($current_paragraph) . "</p>\n<!-- /wp:paragraph -->\n\n";
                $current_paragraph = '';
            }
            
            $blocks .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . $matches[1] . "</h2>\n<!-- /wp:heading -->\n\n";
        }
        // Überschrift Level 3
        elseif (preg_match('/^###\s+(.+)$/', $line, $matches)) {
            // Speichere vorherigen Content
            if ($in_list) {
                $blocks .= create_wp_list_block($list_items);
                $in_list = false;
                $list_items = [];
            }
            if (!empty($current_paragraph)) {
                $blocks .= "<!-- wp:paragraph -->\n<p>" . trim($current_paragraph) . "</p>\n<!-- /wp:paragraph -->\n\n";
                $current_paragraph = '';
            }
            
            $blocks .= "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">" . $matches[1] . "</h3>\n<!-- /wp:heading -->\n\n";
        }
        // Liste
        elseif (preg_match('/^-\s+(.+)$/', $line, $matches)) {
            if (!empty($current_paragraph)) {
                $blocks .= "<!-- wp:paragraph -->\n<p>" . trim($current_paragraph) . "</p>\n<!-- /wp:paragraph -->\n\n";
                $current_paragraph = '';
            }
            $in_list = true;
            $list_items[] = $matches[1];
        }
        // Normaler Text
        else {
            if ($in_list) {
                $blocks .= create_wp_list_block($list_items);
                $in_list = false;
                $list_items = [];
            }
            
            if (!empty($current_paragraph)) {
                $current_paragraph .= ' ';
            }
            $current_paragraph .= $line;
        }
    }
    
    // Verarbeite übrig gebliebene Inhalte
    if ($in_list) {
        $blocks .= create_wp_list_block($list_items);
    }
    if (!empty($current_paragraph)) {
        $blocks .= "<!-- wp:paragraph -->\n<p>" . trim($current_paragraph) . "</p>\n<!-- /wp:paragraph -->";
    }
    
    return trim($blocks);
}

function create_wp_list_block($items) {
    $block = "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
    foreach ($items as $item) {
        $block .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->\n";
    }
    $block .= "</ul>\n<!-- /wp:list -->\n\n";
    return $block;
}

// ============================================================================
// SCHRITT 4: Erstelle Kategorien und Seiten rekursiv
// ============================================================================
echo "\nSchritt 3: Erstelle Kategorien und Seiten...\n";

// Prüfe ob Content-Dateien im Container vorhanden sind, ansonsten nutze lokalen Pfad
if (file_exists('/var/www/html/riman-content/')) {
    $base_path = '/var/www/html/riman-content/';
} else {
    $base_path = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content/';
}
$created_categories = [];
$created_pages = [];

function process_structure($item, $parent_category_id = null, $parent_page_id = null) {
    global $base_path, $created_categories, $created_pages;
    
    echo "Verarbeite: {$item['name']}...\n";
    
    // Erstelle Kategorie
    $term = wp_insert_term(
        $item['name'],
        'category',
        array(
            'slug' => $item['slug'],
            'description' => $item['description'],
            'parent' => $parent_category_id ? $parent_category_id : 0
        )
    );
    
    if (is_wp_error($term)) {
        echo "FEHLER beim Erstellen der Kategorie {$item['name']}: " . $term->get_error_message() . "\n";
        return;
    }
    
    $category_id = $term['term_id'];
    
    $created_categories[$item['slug']] = $category_id;
    echo "✓ Kategorie erstellt: {$item['name']} (ID: $category_id)\n";
    
    // Erstelle zugehörige Seite wenn definiert
    if (isset($item['page'])) {
        $page_data = $item['page'];
        $content = '';
        
        // Lade Content aus Datei wenn vorhanden
        if (isset($page_data['content_file'])) {
            $file_path = $base_path . $page_data['content_file'];
            if (file_exists($file_path)) {
                $markdown_content = file_get_contents($file_path);
                $content = convert_markdown_to_blocks($markdown_content);
            } else {
                // Fallback Content wenn Datei nicht existiert
                $content = "<!-- wp:paragraph -->\n<p>Inhalt folgt in Kürze...</p>\n<!-- /wp:paragraph -->";
                echo "  ⚠ Content-Datei nicht gefunden: {$page_data['content_file']}\n";
            }
        }
        
        // Erstelle die Seite
        $page_args = [
            'post_title' => $page_data['title'],
            'post_name' => $page_data['slug'],
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_parent' => $parent_page_id ? $parent_page_id : 0
        ];
        
        $page_id = wp_insert_post($page_args);
        
        if (!is_wp_error($page_id)) {
            // Verknüpfe Seite mit Kategorie
            update_post_meta($page_id, '_linked_category_id', $category_id);
            update_term_meta($category_id, '_linked_page_id', $page_id);
            
            $created_pages[$page_data['slug']] = $page_id;
            echo "  ✓ Seite erstellt und verknüpft: {$page_data['title']} (ID: $page_id)\n";
        } else {
            echo "  ✗ FEHLER beim Erstellen der Seite {$page_data['title']}: " . $page_id->get_error_message() . "\n";
        }
        
        // Verarbeite Unterkategorien
        if (isset($item['children'])) {
            foreach ($item['children'] as $child) {
                process_structure($child, $category_id, $page_id);
            }
        }
    }
}

// Verarbeite die gesamte Struktur
foreach ($website_structure as $main_item) {
    process_structure($main_item);
}

// ============================================================================
// SCHRITT 5: Erstelle zusätzliche Standalone-Seiten
// ============================================================================
echo "\nSchritt 4: Erstelle Standalone-Seiten...\n";

$standalone_pages = [
    [
        'title' => 'Startseite',
        'slug' => 'startseite',
        'content_file' => 'pages/home.md'
    ],
    [
        'title' => 'Über uns',
        'slug' => 'ueber-uns',
        'content_file' => 'company/about.md'
    ],
    [
        'title' => 'Kontakt',
        'slug' => 'kontakt',
        'content_file' => 'company/contact.md'
    ],
    [
        'title' => 'Impressum',
        'slug' => 'impressum',
        'content_file' => 'company/impressum.md'
    ]
];

foreach ($standalone_pages as $page_data) {
    $content = '';
    $file_path = $base_path . $page_data['content_file'];
    
    if (file_exists($file_path)) {
        $markdown_content = file_get_contents($file_path);
        $content = convert_markdown_to_blocks($markdown_content);
    } else {
        $content = "<!-- wp:paragraph -->\n<p>Inhalt folgt in Kürze...</p>\n<!-- /wp:paragraph -->";
        echo "  ⚠ Content-Datei nicht gefunden: {$page_data['content_file']}\n";
    }
    
    $page_args = [
        'post_title' => $page_data['title'],
        'post_name' => $page_data['slug'],
        'post_content' => $content,
        'post_status' => 'publish',
        'post_type' => 'page'
    ];
    
    $page_id = wp_insert_post($page_args);
    
    if (!is_wp_error($page_id)) {
        echo "✓ Standalone-Seite erstellt: {$page_data['title']} (ID: $page_id)\n";
        
        // Setze Startseite als Homepage
        if ($page_data['slug'] === 'startseite') {
            update_option('page_on_front', $page_id);
            update_option('show_on_front', 'page');
            echo "  → Als Homepage festgelegt\n";
        }
    } else {
        echo "✗ FEHLER beim Erstellen der Seite {$page_data['title']}: " . $page_id->get_error_message() . "\n";
    }
}

// ============================================================================
// SCHRITT 6: Zusammenfassung
// ============================================================================
echo "\n=== SETUP ABGESCHLOSSEN ===\n";
echo "Erstellt wurden:\n";
echo "- " . count($created_categories) . " Kategorien\n";
echo "- " . count($created_pages) . " verknüpfte Seiten\n";
echo "- " . count($standalone_pages) . " Standalone-Seiten\n";
echo "\nDas Category Page Content Connector Plugin sollte bereits aktiviert sein.\n";
echo "Die Seiten-Inhalte werden automatisch in den Kategorie-Seiten angezeigt.\n";
echo "\n✅ Setup erfolgreich abgeschlossen!\n";

?>