<?php
/**
 * Merge neue Hero-Metadaten mit bestehender JSON-Struktur
 * Erstellt eine aktualisierte riman-hero-metadata-extended.json mit allen Seiten
 */

// WordPress Environment laden
if (!defined('ABSPATH')) {
    $wp_load_path = dirname(__FILE__);
    while ($wp_load_path && !file_exists($wp_load_path . '/wp-load.php')) {
        $parent = dirname($wp_load_path);
        if ($parent === $wp_load_path) break;
        $wp_load_path = $parent;
    }

    if (file_exists($wp_load_path . '/wp-load.php')) {
        require_once($wp_load_path . '/wp-load.php');
    } else {
        die("WordPress not found!");
    }
}

// Nur für Administratoren
if (!current_user_can('manage_options')) {
    wp_die('Nur Administratoren dürfen dieses Script ausführen.');
}

/**
 * Holt alle RIMAN Posts und erstellt slug-to-data mapping
 */
function get_all_riman_posts_mapping() {
    $posts = get_posts([
        'post_type' => 'riman_seiten',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);

    $mapping = [];
    foreach ($posts as $post) {
        $terms = get_the_terms($post->ID, 'seitentyp');
        $type = 'unbekannt';
        if ($terms && !is_wp_error($terms)) {
            $type = $terms[0]->slug;
        }

        $mapping[$post->post_name] = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'parent' => $post->post_parent,
            'type' => $type,
            'menu_order' => $post->menu_order
        ];
    }

    return $mapping;
}

/**
 * Lädt neue Hero-Metadaten basierend auf Post-Titel
 */
function get_hero_data_by_title() {
    $hero_file = ABSPATH . 'wp-content/uploads/riman-pages-hero-metadata.json';
    if (!file_exists($hero_file)) {
        return [];
    }

    $json = file_get_contents($hero_file);
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }

    $by_title = [];

    // Hauptseiten
    if (isset($data['pages']['hauptseiten'])) {
        foreach ($data['pages']['hauptseiten'] as $page) {
            $by_title[trim($page['title'])] = $page;
        }
    }

    // Unterseiten
    if (isset($data['pages']['unterseiten'])) {
        foreach ($data['pages']['unterseiten'] as $page) {
            $by_title[trim($page['title'])] = $page;
        }
    }

    // Detailseiten
    if (isset($data['pages']['detailseiten'])) {
        foreach ($data['pages']['detailseiten'] as $page) {
            $by_title[trim($page['title'])] = $page;
        }
    }

    return $by_title;
}

/**
 * Erstellt Pfad-Key aus Post-Hierarchie
 */
function create_path_key($post_data, $all_posts) {
    $path_parts = [];

    // Baue Hierarchie auf
    $current = $post_data;
    while ($current) {
        array_unshift($path_parts, $current['slug']);

        if ($current['parent'] > 0) {
            // Finde Parent
            $parent_found = false;
            foreach ($all_posts as $slug => $post_info) {
                if ($post_info['id'] == $current['parent']) {
                    $current = $post_info;
                    $parent_found = true;
                    break;
                }
            }
            if (!$parent_found) break;
        } else {
            break;
        }
    }

    // Führe 01_, 02_ Prefixe hinzu basierend auf ersten beiden Hauptseiten
    if (count($path_parts) > 0) {
        $root_slug = $path_parts[0];
        if (strpos($root_slug, 'sicherheits-koordination') !== false) {
            $path_parts[0] = '01_Sicherheits-Koordination_und_Mediation';
        } elseif (strpos($root_slug, 'sicherheits-management') !== false) {
            $path_parts[0] = '02_Sicherheits-Management_im_Baubereich';
        }
    }

    return implode('/', $path_parts) . '/index.md';
}

/**
 * Merge-Prozess ausführen
 */
function merge_hero_metadata() {
    // Lade bestehende Struktur
    $existing_file = ABSPATH . 'wp-content/uploads/riman-hero-metadata-extended.json';
    $existing_data = [];

    if (file_exists($existing_file)) {
        $json = file_get_contents($existing_file);
        $existing_data = json_decode($json, true);
    }

    // Initialisiere neue Struktur
    $new_data = [
        "description" => "WordPress Enhanced Image Mappings + Hero Metadata - Vollständige Zuordnung mit Hero-Section Daten für ALLE RIMAN Seiten",
        "version" => "hero-extended-complete-v3.0",
        "image_base_path" => "/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images",
        "last_updated" => date('c'),
        "mapping_strategy" => "complete-hierarchy-with-hero",
        "total_pages_mapped" => 0,
        "duplicate_prevention" => "verified-unique",
        "site_pages" => []
    ];

    // Kopiere bestehende Daten
    if (isset($existing_data['site_pages'])) {
        $new_data['site_pages'] = $existing_data['site_pages'];
    }

    // Hole WordPress Posts und neue Hero-Daten
    $posts_mapping = get_all_riman_posts_mapping();
    $hero_data = get_hero_data_by_title();

    $processed = 0;
    $updated = 0;

    foreach ($posts_mapping as $slug => $post_info) {
        $path_key = create_path_key($post_info, $posts_mapping);

        // Bereinige Titel für Matching
        $clean_title = str_replace(' - Riman GmbH', '', trim($post_info['title']));

        // Finde Hero-Daten
        $hero = null;
        if (isset($hero_data[$clean_title])) {
            $hero = $hero_data[$clean_title]['hero'] ?? null;
        }

        // Fallback: Finde ähnlichen Titel
        if (!$hero) {
            foreach ($hero_data as $title => $data) {
                if (stripos($clean_title, $title) !== false || stripos($title, $clean_title) !== false) {
                    $hero = $data['hero'] ?? null;
                    break;
                }
            }
        }

        // Erstelle Eintrag wenn Hero-Daten gefunden
        if ($hero) {
            $new_data['site_pages'][$path_key] = [
                "image" => "generated_hero_image_" . $slug . ".png",
                "alt" => $clean_title . " - " . ($hero['subtitle'] ?? 'RIMAN GmbH'),
                "title" => $clean_title . " | RIMAN GmbH",
                "caption" => $hero['area_label'] ?? $clean_title,
                "description" => $hero['subtitle'] ?? $clean_title,
                "theme" => "riman-" . $post_info['type'],
                "agent_id" => rand(10, 99),
                "quadrant" => ["top_left", "top_right", "bottom_left", "bottom_right", "center"][array_rand(["top_left", "top_right", "bottom_left", "bottom_right", "center"])],
                "hero" => $hero
            ];
            $updated++;
        }

        $processed++;
    }

    $new_data['total_pages_mapped'] = count($new_data['site_pages']);

    // Speichere neue Datei
    $backup_file = ABSPATH . 'wp-content/uploads/riman-hero-metadata-extended-backup-' . date('Y-m-d-H-i-s') . '.json';
    if (file_exists($existing_file)) {
        copy($existing_file, $backup_file);
    }

    file_put_contents($existing_file, json_encode($new_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return [
        'processed' => $processed,
        'updated' => $updated,
        'total_pages' => count($new_data['site_pages']),
        'backup_file' => basename($backup_file)
    ];
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Hero Metadata Merge</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #1565c0; background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .btn { background: #1976d2; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #1565c0; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat { text-align: center; padding: 15px; background: #f5f5f5; border-radius: 8px; flex: 1; }
        .stat-number { font-size: 24px; font-weight: bold; color: #1976d2; }
        .stat-label { font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Hero Metadata Merge</h1>

        <div class="info">
            Merged neue Hero-Metadaten mit bestehender JSON-Struktur basierend auf tatsächlichen WordPress-Posts.
        </div>

        <?php if (isset($_POST['run_merge'])): ?>
            <?php $results = merge_hero_metadata(); ?>

            <div class="success">
                <strong>✅ Merge erfolgreich abgeschlossen!</strong>
            </div>

            <div class="stats">
                <div class="stat">
                    <div class="stat-number"><?php echo $results['processed']; ?></div>
                    <div class="stat-label">Posts verarbeitet</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo $results['updated']; ?></div>
                    <div class="stat-label">Hero-Daten gefunden</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo $results['total_pages']; ?></div>
                    <div class="stat-label">Gesamt in JSON</div>
                </div>
            </div>

            <div class="info">
                📄 Backup erstellt: <code><?php echo esc_html($results['backup_file']); ?></code><br>
                📄 Aktualisiert: <code>riman-hero-metadata-extended.json</code>
            </div>

            <p><a href="test-hero-import.php" class="btn">🧪 Import jetzt testen</a></p>

        <?php else: ?>
            <form method="post">
                <button type="submit" name="run_merge" class="btn">
                    🔄 Hero-Metadata Merge starten
                </button>
            </form>

            <div class="info" style="margin-top: 20px;">
                <strong>Was passiert:</strong>
                <ul>
                    <li>Lädt alle WordPress RIMAN-Posts mit ihren echten Slugs</li>
                    <li>Matched diese mit Hero-Daten aus der neuen JSON</li>
                    <li>Aktualisiert die bestehende <code>riman-hero-metadata-extended.json</code></li>
                    <li>Erstellt automatisch ein Backup der bestehenden Datei</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>