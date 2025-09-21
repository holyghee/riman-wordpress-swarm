<?php
/**
 * Gezielte Hero-Metadaten für die letzten 5 fehlenden Seiten
 * Post IDs: 10530, 10534, 10532, 10527, 10537
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
 * Definiere Hero-Metadaten für die 5 fehlenden Seiten
 */
function get_final_hero_metadata() {
    return [
        10530 => [ // Erkundung für Entsorgungsmanagement
            'title' => 'Erkundung für Entsorgungsmanagement',
            'subtitle' => 'Professionelle Bewertung für optimale Entsorgung',
            'area_label' => 'Entsorgungsanalyse',
            'icon' => 'fas fa-search'
        ],
        10534 => [ // Schadstoff-Erkundung
            'title' => 'Schadstoff-Erkundung',
            'subtitle' => 'Professionelle Bewertung von Gefahrstoffen',
            'area_label' => 'Schadstoff-Analyse',
            'icon' => 'fas fa-microscope'
        ],
        10532 => [ // Planung und Ausschreibung Entsorgungsmanagement
            'title' => 'Planung und Ausschreibung Entsorgung',
            'subtitle' => 'Effiziente Planung für Entsorgungsprojekte',
            'area_label' => 'Entsorgungsplanung',
            'icon' => 'fas fa-clipboard-list'
        ],
        10527 => [ // Planung und Ausschreibung Altlastensanierung
            'title' => 'Planung und Ausschreibung Altlasten',
            'subtitle' => 'Sichere Planung für Altlastensanierung',
            'area_label' => 'Altlasten-Planung',
            'icon' => 'fas fa-clipboard-list'
        ],
        10537 => [ // Planung und Ausschreibung Schadstoffsanierung
            'title' => 'Planung und Ausschreibung Schadstoffsanierung',
            'subtitle' => 'Sichere Planung für Gefahrstoffsanierung',
            'area_label' => 'Sanierungsplanung',
            'icon' => 'fas fa-clipboard-list'
        ]
    ];
}

/**
 * Setze Hero-Metadaten für einen Post
 */
function set_hero_metadata_for_post($post_id, $hero_data) {
    $updated = 0;

    if (!empty($hero_data['title'])) {
        update_post_meta($post_id, '_riman_hero_title', sanitize_text_field($hero_data['title']));
        $updated++;
    }

    if (!empty($hero_data['subtitle'])) {
        update_post_meta($post_id, '_riman_hero_subtitle', sanitize_textarea_field($hero_data['subtitle']));
        $updated++;
    }

    if (!empty($hero_data['area_label'])) {
        update_post_meta($post_id, '_riman_hero_area_label', sanitize_text_field($hero_data['area_label']));
        $updated++;
    }

    if (!empty($hero_data['icon'])) {
        update_post_meta($post_id, '_riman_hero_icon', sanitize_text_field($hero_data['icon']));
        $updated++;
    }

    return $updated;
}

/**
 * Führe den Hero-Metadaten-Fix aus
 */
function execute_final_hero_fix() {
    $hero_definitions = get_final_hero_metadata();
    $results = [];

    foreach ($hero_definitions as $post_id => $hero_data) {
        $post = get_post($post_id);

        if (!$post) {
            $results[] = [
                'post_id' => $post_id,
                'success' => false,
                'message' => 'Post nicht gefunden'
            ];
            continue;
        }

        // Prüfe ob bereits Hero-Metadaten vorhanden sind
        $existing_title = get_post_meta($post_id, '_riman_hero_title', true);
        if (!empty($existing_title)) {
            $results[] = [
                'post_id' => $post_id,
                'post_title' => $post->post_title,
                'success' => true,
                'action' => 'skipped',
                'message' => 'Hero-Metadaten bereits vorhanden'
            ];
            continue;
        }

        // Setze Hero-Metadaten
        $updated_fields = set_hero_metadata_for_post($post_id, $hero_data);

        $results[] = [
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'success' => true,
            'action' => 'updated',
            'updated_fields' => $updated_fields,
            'hero_data' => $hero_data,
            'message' => "Hero-Metadaten erfolgreich gesetzt ($updated_fields Felder)"
        ];
    }

    return $results;
}

/**
 * Validiere dass alle 5 Seiten jetzt Hero-Metadaten haben
 */
function validate_hero_completion() {
    $post_ids = [10530, 10534, 10532, 10527, 10537];
    $validation = [];

    foreach ($post_ids as $post_id) {
        $post = get_post($post_id);

        if (!$post) {
            $validation[] = [
                'post_id' => $post_id,
                'exists' => false,
                'has_hero' => false
            ];
            continue;
        }

        $hero_title = get_post_meta($post_id, '_riman_hero_title', true);
        $hero_subtitle = get_post_meta($post_id, '_riman_hero_subtitle', true);
        $hero_area_label = get_post_meta($post_id, '_riman_hero_area_label', true);
        $hero_icon = get_post_meta($post_id, '_riman_hero_icon', true);

        $has_hero = !empty($hero_title) || !empty($hero_subtitle) ||
                   !empty($hero_area_label) || !empty($hero_icon);

        $validation[] = [
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'exists' => true,
            'has_hero' => $has_hero,
            'hero_data' => [
                'title' => $hero_title,
                'subtitle' => $hero_subtitle,
                'area_label' => $hero_area_label,
                'icon' => $hero_icon
            ]
        ];
    }

    return $validation;
}

// Ausführung
$execution_results = null;
$validation_results = null;

if (isset($_POST['execute_fix'])) {
    $execution_results = execute_final_hero_fix();
    $validation_results = validate_hero_completion();
} else {
    $validation_results = validate_hero_completion();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>🎯 Fix: Finale 5 Hero-Metadaten</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .success { color: #2e7d32; background: #e8f5e9; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .error { color: #c62828; background: #ffebee; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .warning { color: #ef6c00; background: #fff3e0; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .info { color: #1565c0; background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .btn { background: #1976d2; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        .btn:hover { background: #1565c0; }
        .btn-success { background: #4caf50; }
        .btn-success:hover { background: #45a049; }

        .post-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .has-hero { border-color: #4caf50; background: #f1f8e9; }
        .no-hero { border-color: #f44336; background: #fce4ec; }
        .post-title { font-weight: bold; margin-bottom: 8px; }
        .post-meta { font-size: 12px; color: #666; margin-bottom: 8px; }
        .hero-preview { background: #fff; padding: 10px; border-radius: 4px; margin-top: 8px; font-size: 12px; }

        .definition-preview { background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .definition-item { background: #fff; padding: 12px; margin: 8px 0; border-radius: 4px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Gezielte Hero-Metadaten für finale 5 Seiten</h1>

        <div class="info">
            <strong>Ziel:</strong> Setze Hero-Metadaten für die letzten 5 Seiten ohne Hero-Daten<br>
            <strong>Post IDs:</strong> 10530, 10534, 10532, 10527, 10537<br>
            <strong>Problem:</strong> Gleiche Slugs in verschiedenen Bereichen - das Path-Matching funktioniert nicht
        </div>

        <h2>📋 Hero-Metadaten-Definitionen</h2>

        <div class="definition-preview">
            <?php foreach (get_final_hero_metadata() as $post_id => $hero_data): ?>
                <?php $post = get_post($post_id); ?>
                <div class="definition-item">
                    <strong>Post ID <?php echo $post_id; ?>:</strong>
                    <?php echo $post ? esc_html($post->post_title) : 'Post nicht gefunden'; ?><br>
                    <strong>Hero-Titel:</strong> <?php echo esc_html($hero_data['title']); ?><br>
                    <strong>Untertitel:</strong> <?php echo esc_html($hero_data['subtitle']); ?><br>
                    <strong>Bereichslabel:</strong> <?php echo esc_html($hero_data['area_label']); ?><br>
                    <strong>Icon:</strong> <i class="<?php echo esc_attr($hero_data['icon']); ?>"></i> <?php echo esc_html($hero_data['icon']); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <h2>📊 Aktueller Status</h2>

        <?php foreach ($validation_results as $validation): ?>
            <div class="post-item <?php echo $validation['has_hero'] ? 'has-hero' : 'no-hero'; ?>">
                <div class="post-title">
                    <?php echo $validation['has_hero'] ? '✅' : '❌'; ?>
                    Post ID <?php echo $validation['post_id']; ?>
                    <?php if ($validation['exists']): ?>
                        - <?php echo esc_html($validation['post_title']); ?>
                    <?php else: ?>
                        - Post nicht gefunden
                    <?php endif; ?>
                </div>

                <?php if ($validation['exists'] && $validation['has_hero']): ?>
                    <div class="hero-preview">
                        <strong>✅ Hero-Metadaten vorhanden:</strong><br>
                        <?php if ($validation['hero_data']['title']): ?>
                            <strong>Titel:</strong> <?php echo esc_html($validation['hero_data']['title']); ?><br>
                        <?php endif; ?>
                        <?php if ($validation['hero_data']['subtitle']): ?>
                            <strong>Untertitel:</strong> <?php echo esc_html($validation['hero_data']['subtitle']); ?><br>
                        <?php endif; ?>
                        <?php if ($validation['hero_data']['area_label']): ?>
                            <strong>Bereich:</strong> <?php echo esc_html($validation['hero_data']['area_label']); ?><br>
                        <?php endif; ?>
                        <?php if ($validation['hero_data']['icon']): ?>
                            <strong>Icon:</strong> <i class="<?php echo esc_attr($validation['hero_data']['icon']); ?>"></i> <?php echo esc_html($validation['hero_data']['icon']); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if ($execution_results): ?>
            <h2>🔧 Ausführungsergebnisse</h2>

            <?php
            $total_processed = count($execution_results);
            $successful_updates = count(array_filter($execution_results, function($r) { return $r['success'] && ($r['action'] ?? '') === 'updated'; }));
            $already_existing = count(array_filter($execution_results, function($r) { return $r['success'] && ($r['action'] ?? '') === 'skipped'; }));
            $errors = count(array_filter($execution_results, function($r) { return !$r['success']; }));
            ?>

            <div class="success">
                <strong>✅ Ausführung abgeschlossen!</strong><br>
                <strong>Verarbeitet:</strong> <?php echo $total_processed; ?> Posts<br>
                <strong>Erfolgreich aktualisiert:</strong> <?php echo $successful_updates; ?><br>
                <strong>Bereits vorhanden:</strong> <?php echo $already_existing; ?><br>
                <strong>Fehler:</strong> <?php echo $errors; ?>
            </div>

            <?php foreach ($execution_results as $result): ?>
                <div class="post-item <?php echo $result['success'] ? 'has-hero' : 'no-hero'; ?>">
                    <div class="post-title">
                        <?php if ($result['success']): ?>
                            <?php echo ($result['action'] ?? '') === 'updated' ? '✅ Aktualisiert' : '⚠️ Übersprungen'; ?>
                        <?php else: ?>
                            ❌ Fehler
                        <?php endif; ?>
                        - Post ID <?php echo $result['post_id']; ?>
                        <?php if (isset($result['post_title'])): ?>
                            - <?php echo esc_html($result['post_title']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="post-meta">
                        <?php echo esc_html($result['message']); ?>
                        <?php if (isset($result['updated_fields'])): ?>
                            (<?php echo $result['updated_fields']; ?> Felder)
                        <?php endif; ?>
                    </div>

                    <?php if (isset($result['hero_data']) && $result['action'] === 'updated'): ?>
                        <div class="hero-preview">
                            <strong>Gesetzte Hero-Metadaten:</strong><br>
                            <strong>Titel:</strong> <?php echo esc_html($result['hero_data']['title']); ?><br>
                            <strong>Untertitel:</strong> <?php echo esc_html($result['hero_data']['subtitle']); ?><br>
                            <strong>Bereich:</strong> <?php echo esc_html($result['hero_data']['area_label']); ?><br>
                            <strong>Icon:</strong> <i class="<?php echo esc_attr($result['hero_data']['icon']); ?>"></i> <?php echo esc_html($result['hero_data']['icon']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if ($successful_updates > 0): ?>
                <div class="success">
                    <h3>🎉 Mission erfolgreich!</h3>
                    <p><strong><?php echo $successful_updates; ?> Seiten</strong> haben jetzt Hero-Metadaten!</p>
                    <p><a href="final-hero-metadata-analysis.php" class="btn btn-success">🔍 Finale Validierung durchführen</a></p>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <form method="post">
                <button type="submit" name="execute_fix" class="btn">
                    🚀 Hero-Metadaten für 5 Seiten setzen
                </button>
            </form>

            <div class="warning">
                <strong>Was wird passieren:</strong>
                <ul>
                    <li>Für jede der 5 Post IDs werden spezifische Hero-Metadaten gesetzt</li>
                    <li>Seiten mit bereits vorhandenen Hero-Metadaten werden übersprungen</li>
                    <li>Nach der Ausführung werden alle Posts validiert</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>