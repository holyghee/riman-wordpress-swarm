<?php
/**
 * Debug Script für alle Sicherheits-Management im Baubereich Seiten
 * Findet alle Posts in dieser Sektion und prüft Hero-Metadaten
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
 * Finde Hauptseite "Sicherheits-Management im Baubereich"
 */
function find_sicherheits_management_hauptseite() {
    $posts = get_posts([
        'post_type' => 'riman_seiten',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);

    foreach ($posts as $post) {
        if (stripos($post->post_title, 'Sicherheits-Management im Baubereich') !== false) {
            return $post;
        }
    }
    return null;
}

/**
 * Finde alle Child-Posts einer Hauptseite
 */
function find_all_children($parent_id, $depth = 0) {
    $children = get_posts([
        'post_type' => 'riman_seiten',
        'post_parent' => $parent_id,
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ]);

    $result = [];
    foreach ($children as $child) {
        // Hero-Metadaten prüfen
        $hero_title = get_post_meta($child->ID, '_riman_hero_title', true);
        $hero_subtitle = get_post_meta($child->ID, '_riman_hero_subtitle', true);
        $hero_area_label = get_post_meta($child->ID, '_riman_hero_area_label', true);
        $hero_icon = get_post_meta($child->ID, '_riman_hero_icon', true);

        $has_hero = !empty($hero_title) || !empty($hero_subtitle) ||
                   !empty($hero_area_label) || !empty($hero_icon);

        $child_data = [
            'post' => $child,
            'depth' => $depth,
            'has_hero' => $has_hero,
            'hero_data' => [
                'title' => $hero_title,
                'subtitle' => $hero_subtitle,
                'area_label' => $hero_area_label,
                'icon' => $hero_icon
            ],
            'url' => get_permalink($child->ID),
            'children' => find_all_children($child->ID, $depth + 1)
        ];

        $result[] = $child_data;
    }

    return $result;
}

/**
 * Lade alle Hero-Metadaten aus JSON-Dateien
 */
function load_all_hero_metadata() {
    $hero_files = [
        'extended' => ABSPATH . 'wp-content/uploads/riman-hero-metadata-extended.json',
        'missing' => ABSPATH . 'wp-content/uploads/riman-missing-hero-metadata.json'
    ];

    $all_hero_data = [];

    foreach ($hero_files as $type => $file) {
        if (file_exists($file)) {
            $json = file_get_contents($file);
            $data = json_decode($json, true);

            if ($type === 'extended' && isset($data['site_pages'])) {
                foreach ($data['site_pages'] as $path => $page_data) {
                    if (strpos($path, '02_Sicherheits-Management_im_Baubereich') !== false) {
                        $all_hero_data[$type][$path] = $page_data;
                    }
                }
            } elseif ($type === 'missing' && isset($data['pages'])) {
                foreach (['unterseiten', 'detailseiten', 'info_seiten'] as $section) {
                    if (isset($data['pages'][$section])) {
                        foreach ($data['pages'][$section] as $page_data) {
                            $title = $page_data['title'] ?? '';
                            if (stripos($title, 'Abbruch') !== false ||
                                stripos($title, 'Schadstoff') !== false ||
                                stripos($title, 'Entsorgungs') !== false ||
                                stripos($title, 'Altlasten') !== false ||
                                stripos($title, 'Fachbauleitung') !== false ||
                                stripos($title, 'Planung') !== false ||
                                stripos($title, 'Erkundung') !== false) {
                                $all_hero_data[$type][] = $page_data;
                            }
                        }
                    }
                }
            }
        }
    }

    return $all_hero_data;
}

/**
 * Rendere Post-Hierarchie
 */
function render_post_tree($posts, $parent_title = '') {
    foreach ($posts as $post_data) {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $post_data['depth']);
        $post = $post_data['post'];

        echo '<div class="post-item ' . ($post_data['has_hero'] ? 'has-hero' : 'no-hero') . '" style="margin-left: ' . ($post_data['depth'] * 20) . 'px;">';
        echo '<h' . min(6, 3 + $post_data['depth']) . '>';
        echo $post_data['has_hero'] ? '✅ ' : '❌ ';
        echo esc_html($post->post_title);
        echo '</h' . min(6, 3 + $post_data['depth']) . '>';

        echo '<div class="post-meta">';
        echo '<strong>ID:</strong> ' . $post->ID . ' | ';
        echo '<strong>Slug:</strong> <code>' . esc_html($post->post_name) . '</code> | ';
        echo '<strong>URL:</strong> <a href="' . esc_url($post_data['url']) . '" target="_blank">Seite ansehen</a>';
        echo '</div>';

        if ($post_data['has_hero']) {
            echo '<div class="hero-preview">';
            echo '<strong>Hero-Metadaten:</strong><br>';
            if ($post_data['hero_data']['title']) echo 'Titel: ' . esc_html($post_data['hero_data']['title']) . '<br>';
            if ($post_data['hero_data']['subtitle']) echo 'Untertitel: ' . esc_html($post_data['hero_data']['subtitle']) . '<br>';
            if ($post_data['hero_data']['area_label']) echo 'Bereich: ' . esc_html($post_data['hero_data']['area_label']) . '<br>';
            if ($post_data['hero_data']['icon']) echo 'Icon: <i class="' . esc_attr($post_data['hero_data']['icon']) . '"></i> ' . esc_html($post_data['hero_data']['icon']);
            echo '</div>';
        }

        echo '</div>';

        if (!empty($post_data['children'])) {
            render_post_tree($post_data['children'], $post->post_title);
        }
    }
}

$hauptseite = find_sicherheits_management_hauptseite();
$all_children = $hauptseite ? find_all_children($hauptseite->ID) : [];
$hero_data = load_all_hero_metadata();

// Statistiken berechnen
$total_posts = 1; // Hauptseite
$posts_with_hero = 0;
$posts_without_hero = 0;

function count_posts($posts, &$total, &$with_hero, &$without_hero) {
    foreach ($posts as $post_data) {
        $total++;
        if ($post_data['has_hero']) {
            $with_hero++;
        } else {
            $without_hero++;
        }

        if (!empty($post_data['children'])) {
            count_posts($post_data['children'], $total, $with_hero, $without_hero);
        }
    }
}

count_posts($all_children, $total_posts, $posts_with_hero, $posts_without_hero);

// Hauptseite Hero-Check
if ($hauptseite) {
    $hauptseite_hero_title = get_post_meta($hauptseite->ID, '_riman_hero_title', true);
    $hauptseite_has_hero = !empty($hauptseite_hero_title);
    if ($hauptseite_has_hero) {
        $posts_with_hero++;
    } else {
        $posts_without_hero++;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug: Sicherheits-Management im Baubereich</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 20px; }
        .container { max-width: 1600px; margin: 0 auto; }
        .stats { display: flex; gap: 20px; margin: 30px 0; }
        .stat { text-align: center; padding: 20px; background: #f5f5f5; border-radius: 8px; flex: 1; }
        .stat-number { font-size: 32px; font-weight: bold; margin-bottom: 5px; }
        .stat-label { font-size: 14px; color: #666; }
        .with-hero { color: #4caf50; }
        .without-hero { color: #f44336; }
        .total { color: #2196f3; }

        .post-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .has-hero { border-color: #4caf50; background: #f1f8e9; }
        .no-hero { border-color: #f44336; background: #fce4ec; }
        .post-meta { font-size: 12px; color: #666; margin: 5px 0; }
        .hero-preview { background: #fff; padding: 10px; border-radius: 4px; margin-top: 8px; font-size: 12px; }

        .json-section { background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 30px 0; }
        .json-item { background: #fff; padding: 15px; margin: 10px 0; border-radius: 4px; border: 1px solid #ddd; }
        .error { color: #c62828; background: #ffebee; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .success { color: #2e7d32; background: #e8f5e9; padding: 15px; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug: Sicherheits-Management im Baubereich</h1>

        <?php if (!$hauptseite): ?>
            <div class="error">
                <strong>❌ Hauptseite nicht gefunden!</strong><br>
                Konnte keine Seite mit "Sicherheits-Management im Baubereich" im Titel finden.
            </div>
        <?php else: ?>
            <div class="success">
                <strong>✅ Hauptseite gefunden:</strong> <?php echo esc_html($hauptseite->post_title); ?><br>
                <strong>ID:</strong> <?php echo $hauptseite->ID; ?> |
                <strong>Slug:</strong> <?php echo esc_html($hauptseite->post_name); ?>
            </div>

            <div class="stats">
                <div class="stat">
                    <div class="stat-number total"><?php echo $total_posts; ?></div>
                    <div class="stat-label">Gesamt Seiten</div>
                </div>
                <div class="stat">
                    <div class="stat-number with-hero"><?php echo $posts_with_hero; ?></div>
                    <div class="stat-label">Mit Hero-Metadaten</div>
                </div>
                <div class="stat">
                    <div class="stat-number without-hero"><?php echo $posts_without_hero; ?></div>
                    <div class="stat-label">Ohne Hero-Metadaten</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo $total_posts > 0 ? round(($posts_with_hero / $total_posts) * 100) : 0; ?>%</div>
                    <div class="stat-label">Abdeckung</div>
                </div>
            </div>

            <h2>📋 Komplette Seitenhierarchie</h2>

            <!-- Hauptseite -->
            <div class="post-item <?php echo $hauptseite_has_hero ? 'has-hero' : 'no-hero'; ?>">
                <h2>
                    <?php echo $hauptseite_has_hero ? '✅' : '❌'; ?>
                    <?php echo esc_html($hauptseite->post_title); ?> (Hauptseite)
                </h2>
                <div class="post-meta">
                    <strong>ID:</strong> <?php echo $hauptseite->ID; ?> |
                    <strong>Slug:</strong> <code><?php echo esc_html($hauptseite->post_name); ?></code> |
                    <strong>URL:</strong> <a href="<?php echo esc_url(get_permalink($hauptseite->ID)); ?>" target="_blank">Seite ansehen</a>
                </div>
                <?php if ($hauptseite_has_hero): ?>
                    <div class="hero-preview">
                        <strong>Hero-Metadaten vorhanden!</strong>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Alle Child-Seiten -->
            <?php render_post_tree($all_children); ?>

        <?php endif; ?>

        <h2>📊 Verfügbare Hero-Daten in JSON-Dateien</h2>

        <?php foreach ($hero_data as $source => $data): ?>
            <div class="json-section">
                <h3><?php echo ucfirst($source); ?> JSON - Sicherheits-Management Einträge</h3>

                <?php if (empty($data)): ?>
                    <div class="error">
                        <strong>❌ Keine Einträge gefunden!</strong><br>
                        Die <?php echo $source; ?> JSON-Datei enthält keine Einträge für "Sicherheits-Management im Baubereich".
                    </div>
                <?php else: ?>
                    <div class="success">
                        <strong>✅ <?php echo count($data); ?> Einträge gefunden</strong>
                    </div>

                    <?php foreach ($data as $key => $item): ?>
                        <div class="json-item">
                            <?php if ($source === 'extended'): ?>
                                <strong>Pfad:</strong> <code><?php echo esc_html($key); ?></code><br>
                                <?php if (isset($item['hero'])): ?>
                                    <strong>Hero-Titel:</strong> <?php echo esc_html($item['hero']['title'] ?? 'N/A'); ?><br>
                                    <strong>Untertitel:</strong> <?php echo esc_html($item['hero']['subtitle'] ?? 'N/A'); ?><br>
                                    <strong>Bereich:</strong> <?php echo esc_html($item['hero']['area_label'] ?? 'N/A'); ?><br>
                                    <strong>Icon:</strong> <?php echo esc_html($item['hero']['icon'] ?? 'N/A'); ?>
                                <?php else: ?>
                                    <span style="color: #f44336;">Keine Hero-Daten!</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <strong>Titel:</strong> <?php echo esc_html($item['title'] ?? 'N/A'); ?><br>
                                <strong>Slug:</strong> <code><?php echo esc_html($item['slug'] ?? 'N/A'); ?></code><br>
                                <?php if (isset($item['hero'])): ?>
                                    <strong>Hero-Titel:</strong> <?php echo esc_html($item['hero']['title'] ?? 'N/A'); ?><br>
                                    <strong>Untertitel:</strong> <?php echo esc_html($item['hero']['subtitle'] ?? 'N/A'); ?><br>
                                    <strong>Bereich:</strong> <?php echo esc_html($item['hero']['area_label'] ?? 'N/A'); ?><br>
                                    <strong>Icon:</strong> <?php echo esc_html($item['hero']['icon'] ?? 'N/A'); ?>
                                <?php else: ?>
                                    <span style="color: #f44336;">Keine Hero-Daten!</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <h2>🔧 Diagnose & Lösungsvorschläge</h2>

        <?php if ($posts_without_hero > 0): ?>
            <div class="error">
                <strong>Problem identifiziert:</strong><br>
                <?php echo $posts_without_hero; ?> von <?php echo $total_posts; ?> Seiten haben keine Hero-Metadaten!<br><br>

                <strong>Mögliche Ursachen:</strong>
                <ol>
                    <li>Hero-Metadaten-JSONs enthalten nicht alle Seiten dieser Sektion</li>
                    <li>Path-Matching im MD-Import funktioniert nicht korrekt</li>
                    <li>Slug-Zuordnung zwischen JSON und WordPress stimmt nicht überein</li>
                    <li>Hero-Metadaten wurden nach dem Import überschrieben/gelöscht</li>
                </ol>

                <strong>Lösungsschritte:</strong>
                <ol>
                    <li>Erweitere die Hero-Metadaten-JSON um alle fehlenden Seiten</li>
                    <li>Verbessere das Path-Matching für "02_Sicherheits-Management_im_Baubereich"</li>
                    <li>Führe einen manuellen Hero-Import für diese Sektion aus</li>
                </ol>
            </div>
        <?php else: ?>
            <div class="success">
                <strong>✅ Alle Seiten haben Hero-Metadaten!</strong><br>
                Die Sicherheits-Management Sektion ist vollständig abgedeckt.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>