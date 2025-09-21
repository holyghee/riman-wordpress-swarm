<?php
/**
 * Import für neue Hero-Metadata JSON-Struktur
 * Lädt die neue JSON-Datei und importiert Hero-Metadaten für alle RIMAN-Seiten
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
    wp_die('Nur Administratoren dürfen diesen Import ausführen.');
}

/**
 * Lädt die neue Hero-Metadata JSON-Struktur
 */
function load_new_hero_metadata() {
    // Pfad zur JSON-Datei im uploads Ordner (wie die bestehende)
    $hero_file = ABSPATH . 'wp-content/uploads/riman-pages-hero-metadata.json';

    if (!file_exists($hero_file)) {
        error_log("Hero metadata file not found at: " . $hero_file);
        return false;
    }

    $json = file_get_contents($hero_file);
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    return $data;
}

/**
 * Findet WordPress Post anhand des Slugs
 */
function find_riman_post_by_slug($slug) {
    $posts = get_posts([
        'post_type' => 'riman_seiten',
        'name' => $slug,
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ]);

    return !empty($posts) ? $posts[0] : null;
}

/**
 * Setzt Hero-Metadaten für einen WordPress Post
 */
function set_hero_metadata_for_post($post_id, $hero_data) {
    if (empty($hero_data['hero'])) {
        return false;
    }

    $hero = $hero_data['hero'];
    $updated = 0;

    if (!empty($hero['title'])) {
        update_post_meta($post_id, '_riman_hero_title', sanitize_text_field($hero['title']));
        $updated++;
    }

    if (!empty($hero['subtitle'])) {
        update_post_meta($post_id, '_riman_hero_subtitle', sanitize_textarea_field($hero['subtitle']));
        $updated++;
    }

    if (!empty($hero['area_label'])) {
        update_post_meta($post_id, '_riman_hero_area_label', sanitize_text_field($hero['area_label']));
        $updated++;
    }

    if (!empty($hero['icon'])) {
        update_post_meta($post_id, '_riman_hero_icon', sanitize_text_field($hero['icon']));
        $updated++;
    }

    return $updated;
}

/**
 * Importiert alle Hero-Metadaten
 */
function import_all_hero_metadata() {
    $data = load_new_hero_metadata();
    if (!$data) {
        return ['error' => 'Konnte Hero-Metadata JSON nicht laden'];
    }

    $results = [
        'processed' => 0,
        'updated' => 0,
        'not_found' => 0,
        'details' => []
    ];

    // Hauptseiten verarbeiten
    if (isset($data['pages']['hauptseiten'])) {
        foreach ($data['pages']['hauptseiten'] as $page) {
            $result = process_page($page);
            $results['processed']++;
            if ($result['success']) {
                $results['updated']++;
            } else {
                $results['not_found']++;
            }
            $results['details'][] = $result;
        }
    }

    // Unterseiten verarbeiten
    if (isset($data['pages']['unterseiten'])) {
        foreach ($data['pages']['unterseiten'] as $page) {
            $result = process_page($page);
            $results['processed']++;
            if ($result['success']) {
                $results['updated']++;
            } else {
                $results['not_found']++;
            }
            $results['details'][] = $result;
        }
    }

    // Detailseiten verarbeiten
    if (isset($data['pages']['detailseiten'])) {
        foreach ($data['pages']['detailseiten'] as $page) {
            $result = process_page($page);
            $results['processed']++;
            if ($result['success']) {
                $results['updated']++;
            } else {
                $results['not_found']++;
            }
            $results['details'][] = $result;
        }
    }

    return $results;
}

/**
 * Verarbeitet eine einzelne Seite
 */
function process_page($page_data) {
    $slug = $page_data['slug'];
    $post = find_riman_post_by_slug($slug);

    if (!$post) {
        return [
            'success' => false,
            'slug' => $slug,
            'title' => $page_data['title'],
            'message' => 'WordPress Post nicht gefunden'
        ];
    }

    $updated_count = set_hero_metadata_for_post($post->ID, $page_data);

    return [
        'success' => true,
        'slug' => $slug,
        'title' => $page_data['title'],
        'post_id' => $post->ID,
        'updated_fields' => $updated_count,
        'message' => "Hero-Metadaten erfolgreich gesetzt ($updated_count Felder)"
    ];
}

// HTML Output
?>
<!DOCTYPE html>
<html>
<head>
    <title>RIMAN Hero-Metadata Import</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .success { color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #c62828; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #1565c0; background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .btn { background: #1976d2; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #1565c0; }
        .results { margin-top: 30px; }
        .result-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .result-success { border-color: #4caf50; background: #f1f8e9; }
        .result-error { border-color: #f44336; background: #fce4ec; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat { text-align: center; padding: 15px; background: #f5f5f5; border-radius: 8px; }
        .stat-number { font-size: 24px; font-weight: bold; color: #1976d2; }
        .stat-label { font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 RIMAN Hero-Metadata Import</h1>

        <div class="info">
            <strong>JSON-Datei:</strong> /wp-content/uploads/riman-pages-hero-metadata.json<br>
            Importiert Hero-Metadaten (Titel, Untertitel, Bereichslabel, Icons) für alle RIMAN-Seiten.
        </div>

        <?php
        // Debug: Zeige JSON-Dateipfad
        $debug_data = load_new_hero_metadata();
        if ($debug_data !== false) {
            echo '<div class="success">✅ JSON-Datei erfolgreich gefunden und geladen!</div>';
        } else {
            echo '<div class="error">❌ JSON-Datei konnte nicht geladen werden.</div>';
        }
        ?>

        <?php if (isset($_POST['run_import'])): ?>
            <?php
            $results = import_all_hero_metadata();

            if (isset($results['error'])): ?>
                <div class="error">
                    <strong>Fehler:</strong> <?php echo esc_html($results['error']); ?>
                </div>
            <?php else: ?>
                <div class="success">
                    <strong>Import abgeschlossen!</strong>
                </div>

                <div class="stats">
                    <div class="stat">
                        <div class="stat-number"><?php echo $results['processed']; ?></div>
                        <div class="stat-label">Verarbeitet</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><?php echo $results['updated']; ?></div>
                        <div class="stat-label">Erfolgreich</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><?php echo $results['not_found']; ?></div>
                        <div class="stat-label">Nicht gefunden</div>
                    </div>
                </div>

                <div class="results">
                    <h3>Detaillierte Ergebnisse:</h3>
                    <?php foreach ($results['details'] as $detail): ?>
                        <div class="result-item <?php echo $detail['success'] ? 'result-success' : 'result-error'; ?>">
                            <strong><?php echo esc_html($detail['title']); ?></strong>
                            (<?php echo esc_html($detail['slug']); ?>)
                            <?php if ($detail['success']): ?>
                                <br>✅ Post ID: <?php echo $detail['post_id']; ?> - <?php echo esc_html($detail['message']); ?>
                            <?php else: ?>
                                <br>❌ <?php echo esc_html($detail['message']); ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <form method="post">
                <button type="submit" name="run_import" class="btn">
                    🚀 Hero-Metadata Import starten
                </button>
            </form>

            <div class="info" style="margin-top: 20px;">
                <strong>Hinweis:</strong> Stellen Sie sicher, dass:
                <ul>
                    <li>Die JSON-Datei in <code>/wp-content/uploads/riman-pages-hero-metadata.json</code> existiert</li>
                    <li>Alle RIMAN-Seiten bereits in WordPress erstellt wurden</li>
                    <li>Sie ein Backup gemacht haben (falls nötig)</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>