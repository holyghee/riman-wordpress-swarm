<?php
/**
 * Finale Hero-Metadaten-Analyse - Alle RIMAN-Seiten
 * Identifiziert exakt welche Detailseiten noch keine Hero-Metadaten haben
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
 * Holt alle RIMAN Posts mit kompletter Hierarchie-Info
 */
function get_all_riman_posts_with_hierarchy() {
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

        // Hero-Metadaten prüfen
        $hero_title = get_post_meta($post->ID, '_riman_hero_title', true);
        $hero_subtitle = get_post_meta($post->ID, '_riman_hero_subtitle', true);
        $hero_area_label = get_post_meta($post->ID, '_riman_hero_area_label', true);
        $hero_icon = get_post_meta($post->ID, '_riman_hero_icon', true);

        $has_hero = !empty($hero_title) || !empty($hero_subtitle) ||
                   !empty($hero_area_label) || !empty($hero_icon);

        // Featured Image prüfen
        $has_featured_image = has_post_thumbnail($post->ID);

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
            'has_featured_image' => $has_featured_image,
            'hero_data' => [
                'title' => $hero_title,
                'subtitle' => $hero_subtitle,
                'area_label' => $hero_area_label,
                'icon' => $hero_icon
            ],
            'url' => get_permalink($post->ID)
        ];
    }

    return $structured_posts;
}

/**
 * Analysiere verfügbare Hero-Metadaten in JSON-Dateien
 */
function analyze_available_hero_data() {
    $hero_files = [
        'extended' => ABSPATH . 'wp-content/uploads/riman-hero-metadata-extended.json',
        'missing' => ABSPATH . 'wp-content/uploads/riman-missing-hero-metadata.json'
    ];

    $json_coverage = [];
    $total_json_entries = 0;

    foreach ($hero_files as $type => $file) {
        if (file_exists($file)) {
            $json = file_get_contents($file);
            $data = json_decode($json, true);

            if ($type === 'extended' && isset($data['site_pages'])) {
                $json_coverage[$type] = count($data['site_pages']);
                $total_json_entries += count($data['site_pages']);
            } elseif ($type === 'missing' && isset($data['pages'])) {
                $count = 0;
                foreach (['unterseiten', 'detailseiten', 'info_seiten'] as $section) {
                    if (isset($data['pages'][$section])) {
                        $count += count($data['pages'][$section]);
                    }
                }
                $json_coverage[$type] = $count;
                $total_json_entries += $count;
            }
        } else {
            $json_coverage[$type] = 0;
        }
    }

    return [
        'files' => $json_coverage,
        'total' => $total_json_entries
    ];
}

$all_posts = get_all_riman_posts_with_hierarchy();
$json_analysis = analyze_available_hero_data();

// Statistiken berechnen
$total_posts = count($all_posts);
$posts_with_hero = array_filter($all_posts, function($post) { return $post['has_hero']; });
$posts_without_hero = array_filter($all_posts, function($post) { return !$post['has_hero']; });
$posts_with_images = array_filter($all_posts, function($post) { return $post['has_featured_image']; });

$count_with_hero = count($posts_with_hero);
$count_without_hero = count($posts_without_hero);
$count_with_images = count($posts_with_images);

// Gruppierung nach Typ
$by_type = [];
foreach ($all_posts as $post) {
    if (!isset($by_type[$post['type']])) {
        $by_type[$post['type']] = ['with_hero' => 0, 'without_hero' => 0, 'posts' => []];
    }

    if ($post['has_hero']) {
        $by_type[$post['type']]['with_hero']++;
    } else {
        $by_type[$post['type']]['without_hero']++;
    }

    $by_type[$post['type']]['posts'][] = $post;
}

// Kritische fehlende Seiten identifizieren
$critical_missing = array_filter($posts_without_hero, function($post) {
    return in_array($post['type'], ['hauptseite', 'unterseite', 'detailseite']);
});

?>
<!DOCTYPE html>
<html>
<head>
    <title>🔍 Finale Hero-Metadaten-Analyse</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 20px; }
        .container { max-width: 1600px; margin: 0 auto; }
        .stats { display: flex; gap: 15px; margin: 30px 0; flex-wrap: wrap; }
        .stat { text-align: center; padding: 20px; background: #f5f5f5; border-radius: 8px; flex: 1; min-width: 120px; }
        .stat-number { font-size: 28px; font-weight: bold; margin-bottom: 5px; }
        .stat-label { font-size: 12px; color: #666; }
        .with-hero { color: #4caf50; }
        .without-hero { color: #f44336; }
        .total { color: #2196f3; }
        .images { color: #ff9800; }

        .section { margin: 30px 0; }
        .post-item { border: 1px solid #ddd; padding: 12px; margin: 6px 0; border-radius: 4px; }
        .has-hero { border-color: #4caf50; background: #f1f8e9; }
        .no-hero { border-color: #f44336; background: #fce4ec; }
        .critical { border: 2px solid #f44336; background: #ffebee; }

        .hierarchy-path { font-size: 11px; color: #666; margin-bottom: 5px; }
        .post-title { font-weight: bold; margin-bottom: 5px; }
        .post-meta { font-size: 11px; color: #888; }
        .hero-preview { background: #fff; padding: 8px; border-radius: 4px; margin-top: 6px; font-size: 11px; }

        .type-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .type-header { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .type-stats { font-size: 14px; color: #666; margin-bottom: 15px; }

        .critical-section { background: #ffebee; padding: 20px; border-radius: 8px; border: 2px solid #f44336; }
        .success-section { background: #e8f5e9; padding: 20px; border-radius: 8px; border: 2px solid #4caf50; }

        .json-analysis { background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0; }

        .fix-suggestions { background: #fff3e0; padding: 20px; border-radius: 8px; border: 1px solid #ff9800; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Finale Hero-Metadaten-Analyse aller RIMAN-Seiten</h1>

        <div class="stats">
            <div class="stat">
                <div class="stat-number total"><?php echo $total_posts; ?></div>
                <div class="stat-label">Gesamt RIMAN Seiten</div>
            </div>
            <div class="stat">
                <div class="stat-number with-hero"><?php echo $count_with_hero; ?></div>
                <div class="stat-label">Mit Hero-Metadaten</div>
            </div>
            <div class="stat">
                <div class="stat-number without-hero"><?php echo $count_without_hero; ?></div>
                <div class="stat-label">Ohne Hero-Metadaten</div>
            </div>
            <div class="stat">
                <div class="stat-number images"><?php echo $count_with_images; ?></div>
                <div class="stat-label">Mit Featured Image</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo round(($count_with_hero / $total_posts) * 100); ?>%</div>
                <div class="stat-label">Hero-Abdeckung</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo round(($count_with_images / $total_posts) * 100); ?>%</div>
                <div class="stat-label">Bild-Abdeckung</div>
            </div>
        </div>

        <div class="json-analysis">
            <h3>📊 JSON-Dateien Verfügbarkeit</h3>
            <strong>Extended JSON:</strong> <?php echo $json_analysis['files']['extended']; ?> Einträge<br>
            <strong>Missing JSON:</strong> <?php echo $json_analysis['files']['missing']; ?> Einträge<br>
            <strong>Gesamt JSON-Einträge:</strong> <?php echo $json_analysis['total']; ?><br>
            <strong>WordPress Posts:</strong> <?php echo $total_posts; ?><br>
            <strong>Differenz:</strong> <?php echo $total_posts - $json_analysis['total']; ?>
            <?php if ($total_posts > $json_analysis['total']): ?>
                <span style="color: #f44336;">(JSON unvollständig)</span>
            <?php elseif ($total_posts < $json_analysis['total']): ?>
                <span style="color: #ff9800;">(JSON hat mehr Einträge)</span>
            <?php else: ?>
                <span style="color: #4caf50;">(JSON vollständig)</span>
            <?php endif; ?>
        </div>

        <?php if ($count_without_hero > 0): ?>
        <div class="critical-section">
            <h2>❌ KRITISCHE FEHLENDE HERO-METADATEN (<?php echo $count_without_hero; ?> Seiten)</h2>
            <p><strong>Diese Seiten brauchen dringend Hero-Metadaten:</strong></p>

            <?php foreach ($posts_without_hero as $post): ?>
                <div class="post-item critical">
                    <div class="hierarchy-path"><?php echo esc_html($post['hierarchy_path']); ?></div>
                    <div class="post-title">❌ <?php echo esc_html($post['title']); ?></div>
                    <div class="post-meta">
                        <strong>ID:</strong> <?php echo $post['id']; ?> |
                        <strong>Slug:</strong> <code><?php echo esc_html($post['slug']); ?></code> |
                        <strong>Typ:</strong> <?php echo esc_html($post['type']); ?> |
                        <strong>Featured Image:</strong> <?php echo $post['has_featured_image'] ? '✅' : '❌'; ?> |
                        <a href="<?php echo esc_url($post['url']); ?>" target="_blank">Seite ansehen</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="success-section">
            <h2>✅ ALLE SEITEN HABEN HERO-METADATEN!</h2>
            <p>Perfekt! Alle <?php echo $total_posts; ?> RIMAN-Seiten haben Hero-Metadaten.</p>
        </div>
        <?php endif; ?>

        <div class="section">
            <h2>📈 Übersicht nach Seitentyp</h2>

            <?php foreach ($by_type as $type => $data): ?>
                <div class="type-section">
                    <div class="type-header">
                        <?php
                        $type_names = [
                            'hauptseite' => '🏠 Hauptseiten',
                            'unterseite' => '📂 Unterseiten',
                            'detailseite' => '📄 Detailseiten',
                            'info' => 'ℹ️ Info-Seiten'
                        ];
                        echo $type_names[$type] ?? '❓ ' . ucfirst($type);
                        ?>
                    </div>
                    <div class="type-stats">
                        <span class="with-hero"><?php echo $data['with_hero']; ?> mit Hero</span> |
                        <span class="without-hero"><?php echo $data['without_hero']; ?> ohne Hero</span> |
                        Gesamt: <?php echo count($data['posts']); ?> |
                        Abdeckung: <?php echo count($data['posts']) > 0 ? round(($data['with_hero'] / count($data['posts'])) * 100) : 0; ?>%
                    </div>

                    <?php if ($data['without_hero'] > 0): ?>
                        <div style="margin-top: 10px;">
                            <strong style="color: #f44336;">Fehlende Hero-Metadaten:</strong>
                            <?php foreach ($data['posts'] as $post): ?>
                                <?php if (!$post['has_hero']): ?>
                                    <div class="post-item no-hero">
                                        <div class="hierarchy-path"><?php echo esc_html($post['hierarchy_path']); ?></div>
                                        <div class="post-title">❌ <?php echo esc_html($post['title']); ?></div>
                                        <div class="post-meta">
                                            <strong>Slug:</strong> <code><?php echo esc_html($post['slug']); ?></code> |
                                            <a href="<?php echo esc_url($post['url']); ?>" target="_blank">Seite ansehen</a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($count_without_hero > 0): ?>
        <div class="fix-suggestions">
            <h2>🔧 Lösungsvorschläge</h2>

            <h3>1. Sofortige Fixes:</h3>
            <ul>
                <li><strong>Erweitere die Hero-Metadaten-JSON</strong> um alle <?php echo $count_without_hero; ?> fehlenden Seiten</li>
                <li><strong>Führe den Missing-Hero-Import aus:</strong>
                    <a href="import-missing-hero-metadata.php">import-missing-hero-metadata.php</a>
                </li>
                <li><strong>Setze Hero-Metadaten manuell</strong> für kritische Seiten</li>
            </ul>

            <h3>2. Systematische Lösung:</h3>
            <ul>
                <li><strong>Verbessere das Path-Matching</strong> im MD-Import-Script</li>
                <li><strong>Erstelle Fallback-Hero-Metadaten</strong> für alle Seitentypen</li>
                <li><strong>Implementiere Auto-Generation</strong> basierend auf Seitentitel</li>
            </ul>

            <h3>3. Vollständige JSON-Abdeckung erstellen:</h3>
            <textarea style="width: 100%; height: 200px; font-family: monospace; font-size: 11px;">
{
  "missing_pages_detailed": [
<?php
$json_entries = [];
foreach ($posts_without_hero as $post) {
    $clean_title = str_replace(' - Riman GmbH', '', $post['title']);
    $hierarchy_slugs = array_map(function($h) { return $h['slug']; }, $post['hierarchy']);
    $path_suggestion = implode('/', array_slice($hierarchy_slugs, 0, -1)) . '/' . $post['slug'] . '/index.md';

    $json_entries[] = '    {
      "title": "' . addslashes($clean_title) . '",
      "slug": "' . $post['slug'] . '",
      "path_suggestion": "' . $path_suggestion . '",
      "type": "' . $post['type'] . '",
      "hierarchy": "' . addslashes($post['hierarchy_path']) . '",
      "hero": {
        "title": "' . addslashes($clean_title) . '",
        "subtitle": "TODO: Untertitel für ' . addslashes($clean_title) . '",
        "area_label": "TODO: Bereichslabel",
        "icon": "fas fa-info-circle"
      }
    }';
}
echo implode(",\n", $json_entries);
?>
  ]
}
            </textarea>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>