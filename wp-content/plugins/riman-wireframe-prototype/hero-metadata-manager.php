<?php
/**
 * Hero-Metadaten-Manager - Alle RIMAN-Seiten aus einer Liste verwalten
 * Vollständige Übersicht und Bearbeitung aller Hero-Metadaten
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
    wp_die('Nur Administratoren dürfen dieses Tool verwenden.');
}

/**
 * Holt alle RIMAN Posts mit Hero-Metadaten
 */
function get_all_riman_posts_with_hero_data() {
    $posts = get_posts([
        'post_type' => 'riman_seiten',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ]);

    $structured_posts = [];
    $posts_by_id = [];

    // Erst alle Posts sammeln
    foreach ($posts as $post) {
        $posts_by_id[$post->ID] = $post;
    }

    // Dann Hierarchie und Hero-Metadaten analysieren
    foreach ($posts as $post) {
        $terms = get_the_terms($post->ID, 'seitentyp');
        $type = 'unbekannt';
        if ($terms && !is_wp_error($terms)) {
            $type = $terms[0]->slug;
        }

        // Hierarchie aufbauen
        $hierarchy = [];
        $current = $post;
        while ($current) {
            array_unshift($hierarchy, [
                'id' => $current->ID,
                'title' => $current->post_title,
                'slug' => $current->post_name
            ]);

            if ($current->post_parent > 0 && isset($posts_by_id[$current->post_parent])) {
                $current = $posts_by_id[$current->post_parent];
            } else {
                break;
            }
        }

        // Hero-Metadaten laden
        $hero_title = get_post_meta($post->ID, '_riman_hero_title', true);
        $hero_subtitle = get_post_meta($post->ID, '_riman_hero_subtitle', true);
        $hero_area_label = get_post_meta($post->ID, '_riman_hero_area_label', true);
        $hero_icon = get_post_meta($post->ID, '_riman_hero_icon', true);

        $has_hero = !empty($hero_title) || !empty($hero_subtitle) ||
                   !empty($hero_area_label) || !empty($hero_icon);

        $structured_posts[] = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'parent' => $post->post_parent,
            'type' => $type,
            'menu_order' => $post->menu_order,
            'hierarchy' => $hierarchy,
            'hierarchy_path' => implode(' → ', array_map(function($h) { return $h['title']; }, $hierarchy)),
            'has_hero' => $has_hero,
            'hero_data' => [
                'title' => $hero_title,
                'subtitle' => $hero_subtitle,
                'area_label' => $hero_area_label,
                'icon' => $hero_icon
            ],
            'url' => get_permalink($post->ID),
            'edit_url' => admin_url('post.php?post=' . $post->ID . '&action=edit')
        ];
    }

    return $structured_posts;
}

/**
 * Aktualisiere Hero-Metadaten für einen Post
 */
function update_post_hero_metadata($post_id, $hero_data) {
    $updated = 0;

    if (isset($hero_data['title'])) {
        update_post_meta($post_id, '_riman_hero_title', sanitize_text_field($hero_data['title']));
        $updated++;
    }

    if (isset($hero_data['subtitle'])) {
        update_post_meta($post_id, '_riman_hero_subtitle', sanitize_textarea_field($hero_data['subtitle']));
        $updated++;
    }

    if (isset($hero_data['area_label'])) {
        update_post_meta($post_id, '_riman_hero_area_label', sanitize_text_field($hero_data['area_label']));
        $updated++;
    }

    if (isset($hero_data['icon'])) {
        update_post_meta($post_id, '_riman_hero_icon', sanitize_text_field($hero_data['icon']));
        $updated++;
    }

    return $updated;
}

/**
 * Exportiere alle Hero-Metadaten als JSON
 */
function export_hero_metadata_json() {
    $all_posts = get_all_riman_posts_with_hero_data();

    $export_data = [
        'export_info' => [
            'timestamp' => current_time('mysql'),
            'total_pages' => count($all_posts),
            'exported_by' => get_current_user_id(),
            'version' => '1.0'
        ],
        'hero_metadata' => []
    ];

    foreach ($all_posts as $post) {
        $export_data['hero_metadata'][] = [
            'post_id' => $post['id'],
            'title' => $post['title'],
            'slug' => $post['slug'],
            'type' => $post['type'],
            'hierarchy_path' => $post['hierarchy_path'],
            'parent_id' => $post['parent'],
            'menu_order' => $post['menu_order'],
            'hero_data' => $post['hero_data'],
            'url' => $post['url']
        ];
    }

    return $export_data;
}

/**
 * Importiere Hero-Metadaten aus JSON mit Backup
 */
function import_hero_metadata_json($json_data) {
    if (!is_array($json_data) || !isset($json_data['hero_metadata'])) {
        return ['error' => 'Ungültiges JSON-Format'];
    }

    // Backup erstellen vor Import
    $backup = export_hero_metadata_json();
    $backup_filename = 'hero-metadata-backup-' . date('Y-m-d-H-i-s') . '.json';
    $backup_path = ABSPATH . 'wp-content/uploads/' . $backup_filename;
    file_put_contents($backup_path, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $results = [];
    $total_imported = 0;
    $errors = 0;

    foreach ($json_data['hero_metadata'] as $item) {
        if (!isset($item['post_id']) || !isset($item['hero_data'])) {
            $errors++;
            continue;
        }

        $post_id = intval($item['post_id']);
        $post = get_post($post_id);

        if (!$post) {
            $results[] = [
                'post_id' => $post_id,
                'success' => false,
                'message' => 'Post nicht gefunden'
            ];
            $errors++;
            continue;
        }

        $updated_fields = update_post_hero_metadata($post_id, $item['hero_data']);

        $results[] = [
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'success' => true,
            'updated_fields' => $updated_fields,
            'message' => "Hero-Metadaten importiert ($updated_fields Felder)"
        ];

        $total_imported++;
    }

    return [
        'success' => true,
        'total_imported' => $total_imported,
        'errors' => $errors,
        'backup_file' => $backup_filename,
        'backup_path' => $backup_path,
        'results' => $results
    ];
}

/**
 * Validiere JSON-Struktur für Import
 */
function validate_hero_metadata_json($json_data) {
    $errors = [];

    if (!is_array($json_data)) {
        $errors[] = 'JSON muss ein Array/Objekt sein';
        return $errors;
    }

    if (!isset($json_data['hero_metadata'])) {
        $errors[] = 'Fehlendes "hero_metadata" Feld im JSON';
        return $errors;
    }

    if (!is_array($json_data['hero_metadata'])) {
        $errors[] = '"hero_metadata" muss ein Array sein';
        return $errors;
    }

    foreach ($json_data['hero_metadata'] as $index => $item) {
        if (!isset($item['post_id'])) {
            $errors[] = "Eintrag $index: Fehlende post_id";
        }

        if (!isset($item['hero_data'])) {
            $errors[] = "Eintrag $index: Fehlende hero_data";
        } else {
            $required_fields = ['title', 'subtitle', 'area_label', 'icon'];
            foreach ($required_fields as $field) {
                if (!array_key_exists($field, $item['hero_data'])) {
                    $errors[] = "Eintrag $index: Fehlendes hero_data.$field Feld";
                }
            }
        }
    }

    return $errors;
}

/**
 * Verarbeite Bulk-Updates
 */
function process_bulk_hero_updates() {
    if (!isset($_POST['bulk_updates']) || !is_array($_POST['bulk_updates'])) {
        return ['error' => 'Keine Daten empfangen'];
    }

    $results = [];
    $total_updated = 0;

    foreach ($_POST['bulk_updates'] as $post_id => $hero_data) {
        $post_id = intval($post_id);
        if ($post_id <= 0) continue;

        $post = get_post($post_id);
        if (!$post) {
            $results[] = [
                'post_id' => $post_id,
                'success' => false,
                'message' => 'Post nicht gefunden'
            ];
            continue;
        }

        $updated_fields = update_post_hero_metadata($post_id, $hero_data);

        $results[] = [
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'success' => true,
            'updated_fields' => $updated_fields,
            'message' => "Hero-Metadaten aktualisiert ($updated_fields Felder)"
        ];

        $total_updated++;
    }

    return [
        'success' => true,
        'total_updated' => $total_updated,
        'results' => $results
    ];
}

// Verarbeite Updates und JSON-Operationen
$update_results = null;
$export_results = null;
$import_results = null;

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'bulk_update':
            $update_results = process_bulk_hero_updates();
            break;

        case 'export_json':
            $export_data = export_hero_metadata_json();
            $filename = 'hero-metadata-export-' . date('Y-m-d-H-i-s') . '.json';
            $filepath = ABSPATH . 'wp-content/uploads/' . $filename;

            file_put_contents($filepath, json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $export_results = [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'total_pages' => count($export_data['hero_metadata']),
                'download_url' => content_url('uploads/' . $filename)
            ];
            break;

        case 'import_json':
            if (isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK) {
                $json_content = file_get_contents($_FILES['json_file']['tmp_name']);
                $json_data = json_decode($json_content, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // Validiere JSON-Struktur
                    $validation_errors = validate_hero_metadata_json($json_data);

                    if (empty($validation_errors)) {
                        $import_results = import_hero_metadata_json($json_data);
                    } else {
                        $import_results = [
                            'error' => 'JSON-Validierung fehlgeschlagen',
                            'validation_errors' => $validation_errors
                        ];
                    }
                } else {
                    $import_results = ['error' => 'Ungültiges JSON-Format: ' . json_last_error_msg()];
                }
            } else {
                $import_results = ['error' => 'Keine Datei hochgeladen oder Upload-Fehler'];
            }
            break;
    }
}

$all_posts = get_all_riman_posts_with_hero_data();

// Statistiken
$total_posts = count($all_posts);
$posts_with_hero = array_filter($all_posts, function($post) { return $post['has_hero']; });
$count_with_hero = count($posts_with_hero);

// Font Awesome Icons für Dropdown
$icon_options = [
    'fas fa-handshake' => 'Handshake (Mediation)',
    'fas fa-hard-hat' => 'Hard Hat (Bau)',
    'fas fa-shield-alt' => 'Shield (Sicherheit)',
    'fas fa-building' => 'Building (Unternehmen)',
    'fas fa-home' => 'Home (Familie/Privat)',
    'fas fa-tools' => 'Tools (Baubereich)',
    'fas fa-cogs' => 'Cogs (Management)',
    'fas fa-search' => 'Search (Erkundung)',
    'fas fa-microscope' => 'Microscope (Analyse)',
    'fas fa-clipboard-list' => 'Clipboard (Planung)',
    'fas fa-recycle' => 'Recycle (Entsorgung)',
    'fas fa-leaf' => 'Leaf (Umwelt)',
    'fas fa-exclamation-triangle' => 'Warning (Gefahrstoffe)',
    'fas fa-hammer' => 'Hammer (Abbruch)',
    'fas fa-users' => 'Users (Gruppe)',
    'fas fa-user-friends' => 'Friends (Freundschaft)',
    'fas fa-heart' => 'Heart (Familie)',
    'fas fa-sitemap' => 'Sitemap (Struktur)',
    'fas fa-euro-sign' => 'Euro (Finanzen)',
    'fas fa-question-circle' => 'Question (Beratung)',
    'fas fa-map-marker-alt' => 'Map Marker (Standort)',
    'fas fa-drafting-compass' => 'Compass (Planung)',
    'fas fa-file-signature' => 'File Signature (Genehmigung)',
    'fas fa-info-circle' => 'Info Circle (Information)'
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>🎯 Hero-Metadaten-Manager</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { max-width: 1800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }

        .header { border-bottom: 1px solid #ddd; padding-bottom: 20px; margin-bottom: 30px; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; flex: 1; }
        .stat-number { font-size: 24px; font-weight: bold; color: #2196f3; }
        .stat-label { font-size: 12px; color: #666; }

        .controls { background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .btn { padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; margin: 5px; }
        .btn-primary { background: #1976d2; color: white; }
        .btn-success { background: #4caf50; color: white; }
        .btn-warning { background: #ff9800; color: white; }
        .btn:hover { opacity: 0.9; }

        .posts-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .posts-table th, .posts-table td { padding: 12px; border: 1px solid #ddd; text-align: left; vertical-align: top; }
        .posts-table th { background: #f5f5f5; font-weight: bold; position: sticky; top: 0; }
        .posts-table tr:nth-child(even) { background: #f9f9f9; }
        .posts-table tr:hover { background: #e3f2fd; }

        .hierarchy-path { font-size: 11px; color: #666; margin-bottom: 5px; }
        .post-title { font-weight: bold; margin-bottom: 5px; }

        .hero-input { width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px; }
        .hero-textarea { width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px; min-height: 60px; resize: vertical; }
        .hero-select { width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px; }

        .type-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .type-hauptseite { background: #e3f2fd; color: #1976d2; }
        .type-unterseite { background: #e8f5e9; color: #388e3c; }
        .type-detailseite { background: #fff3e0; color: #f57c00; }
        .type-info { background: #f3e5f5; color: #7b1fa2; }

        .icon-preview { font-size: 16px; margin-right: 8px; }

        .update-results { background: #e8f5e9; border: 1px solid #4caf50; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .error-results { background: #ffebee; border: 1px solid #f44336; padding: 15px; border-radius: 8px; margin: 20px 0; }

        .fixed-header { position: sticky; top: 0; background: white; z-index: 100; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header fixed-header">
            <h1>🎯 Hero-Metadaten-Manager</h1>
            <p>Verwalte alle Hero-Metadaten für <?php echo $total_posts; ?> RIMAN-Seiten aus einer zentralen Übersicht</p>

            <div class="stats">
                <div class="stat">
                    <div class="stat-number"><?php echo $total_posts; ?></div>
                    <div class="stat-label">Gesamt Seiten</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo $count_with_hero; ?></div>
                    <div class="stat-label">Mit Hero-Metadaten</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo round(($count_with_hero / $total_posts) * 100); ?>%</div>
                    <div class="stat-label">Abdeckung</div>
                </div>
            </div>
        </div>

        <?php if ($update_results): ?>
            <?php if (isset($update_results['error'])): ?>
                <div class="error-results">
                    <h3>❌ Fehler beim Update</h3>
                    <p><?php echo esc_html($update_results['error']); ?></p>
                </div>
            <?php else: ?>
                <div class="update-results">
                    <h3>✅ Bulk-Update erfolgreich!</h3>
                    <p><strong><?php echo $update_results['total_updated']; ?> Posts</strong> wurden aktualisiert.</p>
                    <details>
                        <summary>Details anzeigen</summary>
                        <?php foreach ($update_results['results'] as $result): ?>
                            <div style="margin: 5px 0;">
                                <?php echo $result['success'] ? '✅' : '❌'; ?>
                                Post <?php echo $result['post_id']; ?>
                                <?php if (isset($result['post_title'])): ?>
                                    - <?php echo esc_html($result['post_title']); ?>
                                <?php endif; ?>
                                - <?php echo esc_html($result['message']); ?>
                            </div>
                        <?php endforeach; ?>
                    </details>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($export_results): ?>
            <div class="update-results">
                <h3>✅ JSON-Export erfolgreich!</h3>
                <p><strong><?php echo $export_results['total_pages']; ?> Seiten</strong> wurden in JSON exportiert.</p>
                <p><strong>Datei:</strong> <?php echo esc_html($export_results['filename']); ?></p>
                <p>
                    <a href="<?php echo esc_url($export_results['download_url']); ?>" class="btn btn-success" download>
                        📥 JSON-Datei herunterladen
                    </a>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($import_results): ?>
            <?php if (isset($import_results['error'])): ?>
                <div class="error-results">
                    <h3>❌ JSON-Import fehlgeschlagen</h3>
                    <p><?php echo esc_html($import_results['error']); ?></p>
                    <?php if (isset($import_results['validation_errors'])): ?>
                        <details>
                            <summary>Validierungsfehler anzeigen</summary>
                            <ul>
                                <?php foreach ($import_results['validation_errors'] as $error): ?>
                                    <li><?php echo esc_html($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="update-results">
                    <h3>✅ JSON-Import erfolgreich!</h3>
                    <p><strong><?php echo $import_results['total_imported']; ?> Posts</strong> wurden aus JSON importiert.</p>
                    <?php if ($import_results['errors'] > 0): ?>
                        <p><strong>Fehler:</strong> <?php echo $import_results['errors']; ?> Posts konnten nicht importiert werden.</p>
                    <?php endif; ?>
                    <p><strong>Backup erstellt:</strong> <?php echo esc_html($import_results['backup_file']); ?></p>
                    <details>
                        <summary>Import-Details anzeigen</summary>
                        <?php foreach ($import_results['results'] as $result): ?>
                            <div style="margin: 5px 0;">
                                <?php echo $result['success'] ? '✅' : '❌'; ?>
                                Post <?php echo $result['post_id']; ?>
                                <?php if (isset($result['post_title'])): ?>
                                    - <?php echo esc_html($result['post_title']); ?>
                                <?php endif; ?>
                                - <?php echo esc_html($result['message']); ?>
                            </div>
                        <?php endforeach; ?>
                    </details>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="controls">
            <h3>📂 JSON Export/Import</h3>
            <div style="margin: 15px 0; padding: 15px; background: #f0f8ff; border: 1px solid #cde; border-radius: 8px;">
                <h4>📤 JSON Export</h4>
                <p>Exportiere alle aktuellen Hero-Metadaten in eine JSON-Datei zum Download oder Backup.</p>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="export_json">
                    <button type="submit" class="btn btn-primary">
                        📤 Alle Hero-Metadaten als JSON exportieren
                    </button>
                </form>
            </div>

            <div style="margin: 15px 0; padding: 15px; background: #fff8f0; border: 1px solid #ddc; border-radius: 8px;">
                <h4>📥 JSON Import</h4>
                <p>Importiere Hero-Metadaten aus einer JSON-Datei. Automatisches Backup wird vor Import erstellt.</p>
                <form method="post" enctype="multipart/form-data" style="margin-top: 10px;">
                    <input type="hidden" name="action" value="import_json">
                    <input type="file" name="json_file" accept=".json" required style="margin-bottom: 10px;">
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Wirklich importieren? Ein Backup wird automatisch erstellt.')">
                        📥 JSON-Datei importieren
                    </button>
                </form>
            </div>

            <h3>🔧 Bulk-Aktionen</h3>
            <button type="button" onclick="selectAllCheckboxes()" class="btn btn-primary">Alle auswählen</button>
            <button type="button" onclick="clearAllCheckboxes()" class="btn btn-warning">Auswahl löschen</button>
            <button type="button" onclick="bulkSetIcon('fas fa-handshake')" class="btn btn-primary">🤝 Mediation-Icon für ausgewählte</button>
            <button type="button" onclick="bulkSetIcon('fas fa-hard-hat')" class="btn btn-primary">🏗️ Bau-Icon für ausgewählte</button>
            <button type="button" onclick="validateForm()" class="btn btn-success">💾 Alle Änderungen speichern</button>
        </div>

        <form method="post" id="heroForm">
            <input type="hidden" name="action" value="bulk_update">

            <table class="posts-table">
                <thead>
                    <tr>
                        <th width="30">
                            <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()">
                        </th>
                        <th width="80">Typ</th>
                        <th width="300">Seite & Hierarchie</th>
                        <th width="200">Hero-Titel</th>
                        <th width="300">Hero-Untertitel</th>
                        <th width="150">Bereichslabel</th>
                        <th width="150">Icon</th>
                        <th width="100">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_posts as $post): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_posts[]" value="<?php echo $post['id']; ?>" class="post-checkbox">
                            </td>
                            <td>
                                <span class="type-badge type-<?php echo esc_attr($post['type']); ?>">
                                    <?php
                                    $type_labels = [
                                        'hauptseite' => 'Haupt',
                                        'unterseite' => 'Unter',
                                        'detailseite' => 'Detail',
                                        'info' => 'Info'
                                    ];
                                    echo $type_labels[$post['type']] ?? $post['type'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div class="hierarchy-path"><?php echo esc_html($post['hierarchy_path']); ?></div>
                                <div class="post-title">
                                    <a href="<?php echo esc_url($post['url']); ?>" target="_blank">
                                        <?php echo esc_html($post['title']); ?>
                                    </a>
                                </div>
                                <div style="font-size: 10px; color: #888;">
                                    ID: <?php echo $post['id']; ?> |
                                    <a href="<?php echo esc_url($post['edit_url']); ?>" target="_blank">Bearbeiten</a>
                                </div>
                            </td>
                            <td>
                                <input type="text"
                                       name="bulk_updates[<?php echo $post['id']; ?>][title]"
                                       value="<?php echo esc_attr($post['hero_data']['title']); ?>"
                                       placeholder="Hero-Titel eingeben..."
                                       class="hero-input">
                            </td>
                            <td>
                                <textarea name="bulk_updates[<?php echo $post['id']; ?>][subtitle]"
                                          placeholder="Hero-Untertitel eingeben..."
                                          class="hero-textarea"><?php echo esc_textarea($post['hero_data']['subtitle']); ?></textarea>
                            </td>
                            <td>
                                <input type="text"
                                       name="bulk_updates[<?php echo $post['id']; ?>][area_label]"
                                       value="<?php echo esc_attr($post['hero_data']['area_label']); ?>"
                                       placeholder="Bereichslabel..."
                                       class="hero-input">
                            </td>
                            <td>
                                <select name="bulk_updates[<?php echo $post['id']; ?>][icon]" class="hero-select icon-select" data-post-id="<?php echo $post['id']; ?>">
                                    <option value="">Icon wählen...</option>
                                    <?php foreach ($icon_options as $icon_class => $icon_label): ?>
                                        <option value="<?php echo esc_attr($icon_class); ?>"
                                                <?php selected($post['hero_data']['icon'], $icon_class); ?>>
                                            <?php echo esc_html($icon_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="icon-preview-container" id="icon-preview-<?php echo $post['id']; ?>">
                                    <?php if ($post['hero_data']['icon']): ?>
                                        <i class="<?php echo esc_attr($post['hero_data']['icon']); ?> icon-preview"></i>
                                        <span style="font-size: 10px;"><?php echo esc_html($post['hero_data']['icon']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <button type="button" onclick="previewHero(<?php echo $post['id']; ?>)" class="btn btn-primary" style="font-size: 10px; padding: 6px 12px;">
                                    👁️ Vorschau
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="position: sticky; bottom: 0; background: white; padding: 20px; border-top: 1px solid #ddd; text-align: center;">
                <button type="submit" class="btn btn-success" style="font-size: 16px; padding: 15px 30px;">
                    💾 Alle Änderungen speichern
                </button>
            </div>
        </form>
    </div>

    <script>
        // Icon-Vorschau aktualisieren
        document.querySelectorAll('.icon-select').forEach(select => {
            select.addEventListener('change', function() {
                const postId = this.dataset.postId;
                const iconClass = this.value;
                const previewContainer = document.getElementById('icon-preview-' + postId);

                if (iconClass) {
                    previewContainer.innerHTML = `<i class="${iconClass} icon-preview"></i><span style="font-size: 10px;">${iconClass}</span>`;
                } else {
                    previewContainer.innerHTML = '';
                }
            });
        });

        // Checkbox-Funktionen
        function toggleAllCheckboxes() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.post-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        function selectAllCheckboxes() {
            document.getElementById('selectAll').checked = true;
            toggleAllCheckboxes();
        }

        function clearAllCheckboxes() {
            document.getElementById('selectAll').checked = false;
            toggleAllCheckboxes();
        }

        // Bulk-Icon setzen
        function bulkSetIcon(iconClass) {
            const selectedCheckboxes = document.querySelectorAll('.post-checkbox:checked');
            selectedCheckboxes.forEach(checkbox => {
                const postId = checkbox.value;
                const iconSelect = document.querySelector(`select[name="bulk_updates[${postId}][icon]"]`);
                if (iconSelect) {
                    iconSelect.value = iconClass;
                    iconSelect.dispatchEvent(new Event('change'));
                }
            });
        }

        // Vorschau-Funktion
        function previewHero(postId) {
            const title = document.querySelector(`input[name="bulk_updates[${postId}][title]"]`).value;
            const subtitle = document.querySelector(`textarea[name="bulk_updates[${postId}][subtitle]"]`).value;
            const areaLabel = document.querySelector(`input[name="bulk_updates[${postId}][area_label]"]`).value;
            const icon = document.querySelector(`select[name="bulk_updates[${postId}][icon]"]`).value;

            let preview = `Hero-Vorschau für Post ${postId}:\n\n`;
            if (title) preview += `Titel: ${title}\n`;
            if (subtitle) preview += `Untertitel: ${subtitle}\n`;
            if (areaLabel) preview += `Bereichslabel: ${areaLabel}\n`;
            if (icon) preview += `Icon: ${icon}\n`;

            alert(preview);
        }

        // Form-Validierung
        function validateForm() {
            const form = document.getElementById('heroForm');
            const inputs = form.querySelectorAll('input[type="text"], textarea, select');
            let hasChanges = false;

            inputs.forEach(input => {
                if (input.value.trim() !== '') {
                    hasChanges = true;
                }
            });

            if (!hasChanges) {
                alert('Keine Änderungen erkannt. Bitte fülle mindestens ein Feld aus.');
                return false;
            }

            if (confirm(`Möchten Sie wirklich alle Änderungen speichern?`)) {
                form.submit();
            }
        }
    </script>
</body>
</html>