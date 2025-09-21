<?php
/**
 * Import für fehlende Hero-Metadaten
 * Importiert Hero-Metadaten für die 34 bisher nicht abgedeckten RIMAN-Seiten
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
 * Lädt die Missing Hero-Metadata JSON-Struktur
 */
function load_missing_hero_metadata() {
    $hero_file = ABSPATH . 'wp-content/uploads/riman-missing-hero-metadata.json';

    if (!file_exists($hero_file)) {
        error_log("Missing hero metadata file not found at: " . $hero_file);
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
 * Findet WordPress Post anhand des Slugs UND Titels (fallback)
 */
function find_riman_post_by_slug_or_title($slug, $title) {
    // Erst Slug versuchen
    $posts = get_posts([
        'post_type' => 'riman_seiten',
        'name' => $slug,
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ]);

    if (!empty($posts)) {
        return $posts[0];
    }

    // Fallback: Title-basierte Suche
    $clean_title = str_replace(' - Riman GmbH', '', $title);
    $posts = get_posts([
        'post_type' => 'riman_seiten',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);

    foreach ($posts as $post) {
        $post_title_clean = str_replace(' - Riman GmbH', '', $post->post_title);

        // Exakte Übereinstimmung
        if ($post_title_clean === $clean_title) {
            return $post;
        }

        // Teilübereinstimmung (beide Richtungen)
        if (stripos($post_title_clean, $clean_title) !== false ||
            stripos($clean_title, $post_title_clean) !== false) {
            return $post;
        }
    }

    return null;
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
 * Importiert alle fehlenden Hero-Metadaten
 */
function import_missing_hero_metadata() {
    $data = load_missing_hero_metadata();
    if (!$data) {
        return ['error' => 'Konnte Missing Hero-Metadata JSON nicht laden'];
    }

    $results = [
        'processed' => 0,
        'updated' => 0,
        'not_found' => 0,
        'already_exists' => 0,
        'details' => []
    ];

    // Alle Seitentypen verarbeiten
    $all_pages = [];

    if (isset($data['pages']['unterseiten'])) {
        $all_pages = array_merge($all_pages, $data['pages']['unterseiten']);
    }

    if (isset($data['pages']['detailseiten'])) {
        $all_pages = array_merge($all_pages, $data['pages']['detailseiten']);
    }

    if (isset($data['pages']['info_seiten'])) {
        $all_pages = array_merge($all_pages, $data['pages']['info_seiten']);
    }

    foreach ($all_pages as $page) {
        $result = process_missing_page($page);
        $results['processed']++;

        if ($result['success']) {
            if ($result['action'] === 'updated') {
                $results['updated']++;
            } else {
                $results['already_exists']++;
            }
        } else {
            $results['not_found']++;
        }

        $results['details'][] = $result;
    }

    return $results;
}

/**
 * Verarbeitet eine einzelne fehlende Seite
 */
function process_missing_page($page_data) {
    $slug = $page_data['slug'];
    $title = $page_data['title'];
    $post = find_riman_post_by_slug_or_title($slug, $title);

    if (!$post) {
        return [
            'success' => false,
            'slug' => $slug,
            'title' => $title,
            'message' => 'WordPress Post nicht gefunden'
        ];
    }

    // Prüfen ob bereits Hero-Metadaten vorhanden sind
    $existing_hero_title = get_post_meta($post->ID, '_riman_hero_title', true);
    $existing_hero_subtitle = get_post_meta($post->ID, '_riman_hero_subtitle', true);
    $existing_hero_area_label = get_post_meta($post->ID, '_riman_hero_area_label', true);
    $existing_hero_icon = get_post_meta($post->ID, '_riman_hero_icon', true);

    $has_existing_hero = !empty($existing_hero_title) || !empty($existing_hero_subtitle) ||
                        !empty($existing_hero_area_label) || !empty($existing_hero_icon);

    if ($has_existing_hero) {
        return [
            'success' => true,
            'action' => 'skipped',
            'slug' => $slug,
            'title' => $title,
            'post_id' => $post->ID,
            'message' => "Hero-Metadaten bereits vorhanden (übersprungen)"
        ];
    }

    $updated_count = set_hero_metadata_for_post($post->ID, $page_data);

    return [
        'success' => true,
        'action' => 'updated',
        'slug' => $slug,
        'title' => $title,
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
    <title>RIMAN Missing Hero-Metadata Import</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .success { color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #c62828; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #1565c0; background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #ef6c00; background: #fff3e0; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .btn { background: #1976d2; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #1565c0; }
        .results { margin-top: 30px; }
        .result-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .result-success { border-color: #4caf50; background: #f1f8e9; }
        .result-error { border-color: #f44336; background: #fce4ec; }
        .result-skipped { border-color: #ff9800; background: #fff3e0; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat { text-align: center; padding: 15px; background: #f5f5f5; border-radius: 8px; }
        .stat-number { font-size: 24px; font-weight: bold; color: #1976d2; }
        .stat-label { font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 RIMAN Missing Hero-Metadata Import</h1>

        <div class="info">
            <strong>JSON-Datei:</strong> /wp-content/uploads/riman-missing-hero-metadata.json<br>
            Importiert Hero-Metadaten für die 34 bisher nicht abgedeckten RIMAN-Seiten.<br>
            <strong>Modus:</strong> Überspringt Seiten die bereits Hero-Metadaten haben.
        </div>

        <?php
        // Debug: Zeige JSON-Dateipfad
        $debug_data = load_missing_hero_metadata();
        if ($debug_data !== false) {
            echo '<div class="success">✅ JSON-Datei erfolgreich gefunden und geladen!</div>';
            echo '<div class="info">📊 Enthält ' . count($debug_data['pages']['unterseiten'] ?? []) . ' Unterseiten, ' .
                 count($debug_data['pages']['detailseiten'] ?? []) . ' Detailseiten, ' .
                 count($debug_data['pages']['info_seiten'] ?? []) . ' Info-Seiten</div>';
        } else {
            echo '<div class="error">❌ JSON-Datei konnte nicht geladen werden.</div>';
        }
        ?>

        <?php if (isset($_POST['run_import'])): ?>
            <?php
            $results = import_missing_hero_metadata();

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
                        <div class="stat-label">Neu importiert</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><?php echo $results['already_exists']; ?></div>
                        <div class="stat-label">Bereits vorhanden</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><?php echo $results['not_found']; ?></div>
                        <div class="stat-label">Nicht gefunden</div>
                    </div>
                </div>

                <div class="results">
                    <h3>Detaillierte Ergebnisse:</h3>
                    <?php foreach ($results['details'] as $detail): ?>
                        <div class="result-item <?php
                            if ($detail['success']) {
                                echo $detail['action'] === 'updated' ? 'result-success' : 'result-skipped';
                            } else {
                                echo 'result-error';
                            }
                        ?>">
                            <strong><?php echo esc_html($detail['title']); ?></strong>
                            (<?php echo esc_html($detail['slug']); ?>)
                            <?php if ($detail['success']): ?>
                                <br><?php echo $detail['action'] === 'updated' ? '✅' : '⚠️'; ?>
                                <?php if (isset($detail['post_id'])): ?>
                                    Post ID: <?php echo $detail['post_id']; ?> -
                                <?php endif; ?>
                                <?php echo esc_html($detail['message']); ?>
                            <?php else: ?>
                                <br>❌ <?php echo esc_html($detail['message']); ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($results['updated'] > 0): ?>
                    <div class="success">
                        <strong>🎉 Erfolgreich!</strong> <?php echo $results['updated']; ?> Seiten haben jetzt Hero-Metadaten!<br>
                        <a href="analyze-missing-hero-metadata.php" style="color: #1976d2;">🔍 Finale Analyse durchführen</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        <?php else: ?>
            <form method="post">
                <button type="submit" name="run_import" class="btn">
                    🚀 Missing Hero-Metadata Import starten
                </button>
            </form>

            <div class="warning" style="margin-top: 20px;">
                <strong>Hinweis:</strong>
                <ul>
                    <li>Seiten mit vorhandenen Hero-Metadaten werden übersprungen</li>
                    <li>Nur neue/fehlende Hero-Metadaten werden importiert</li>
                    <li>Verwendet sowohl Slug- als auch Title-basiertes Matching</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>