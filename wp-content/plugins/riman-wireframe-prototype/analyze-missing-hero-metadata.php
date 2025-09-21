<?php
/**
 * Analysiert welche RIMAN-Seiten keine Hero-Metadaten haben
 * Vergleicht alle WordPress Posts mit vorhandenen Hero-Metadaten
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
 * Holt alle RIMAN Posts
 */
function get_all_riman_posts() {
    $posts = get_posts([
        'post_type' => 'riman_seiten',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ]);

    $structured_posts = [];
    foreach ($posts as $post) {
        $terms = get_the_terms($post->ID, 'seitentyp');
        $type = 'unbekannt';
        if ($terms && !is_wp_error($terms)) {
            $type = $terms[0]->slug;
        }

        // Hero-Metadaten prüfen
        $hero_title = get_post_meta($post->ID, '_riman_hero_title', true);
        $hero_subtitle = get_post_meta($post->ID, '_riman_hero_subtitle', true);
        $hero_area_label = get_post_meta($post->ID, '_riman_hero_area_label', true);
        $hero_icon = get_post_meta($post->ID, '_riman_hero_icon', true);

        $has_hero = !empty($hero_title) || !empty($hero_subtitle) || !empty($hero_area_label) || !empty($hero_icon);

        $structured_posts[] = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'parent' => $post->post_parent,
            'type' => $type,
            'menu_order' => $post->menu_order,
            'has_hero' => $has_hero,
            'hero_data' => [
                'title' => $hero_title,
                'subtitle' => $hero_subtitle,
                'area_label' => $hero_area_label,
                'icon' => $hero_icon
            ]
        ];
    }

    return $structured_posts;
}

/**
 * Erstellt Hierarchie-Info für bessere Übersicht
 */
function build_hierarchy($posts) {
    $posts_by_id = [];
    foreach ($posts as $post) {
        $posts_by_id[$post['id']] = $post;
    }

    foreach ($posts as &$post) {
        $path = [];
        $current = $post;

        while ($current) {
            array_unshift($path, $current['title']);

            if ($current['parent'] > 0 && isset($posts_by_id[$current['parent']])) {
                $current = $posts_by_id[$current['parent']];
            } else {
                break;
            }
        }

        $post['hierarchy_path'] = implode(' → ', $path);
    }

    return $posts;
}

$all_posts = get_all_riman_posts();
$all_posts = build_hierarchy($all_posts);

// Statistiken berechnen
$total_posts = count($all_posts);
$posts_with_hero = array_filter($all_posts, function($post) { return $post['has_hero']; });
$posts_without_hero = array_filter($all_posts, function($post) { return !$post['has_hero']; });

$count_with_hero = count($posts_with_hero);
$count_without_hero = count($posts_without_hero);

// Nach Typ gruppieren
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

?>
<!DOCTYPE html>
<html>
<head>
    <title>RIMAN Hero-Metadaten Analyse</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        .stats { display: flex; gap: 20px; margin: 30px 0; }
        .stat { text-align: center; padding: 20px; background: #f5f5f5; border-radius: 8px; flex: 1; }
        .stat-number { font-size: 32px; font-weight: bold; margin-bottom: 5px; }
        .stat-label { font-size: 14px; color: #666; }
        .with-hero { color: #4caf50; }
        .without-hero { color: #f44336; }
        .total { color: #2196f3; }

        .section { margin: 30px 0; }
        .post-item { border: 1px solid #ddd; padding: 15px; margin: 8px 0; border-radius: 4px; }
        .has-hero { border-color: #4caf50; background: #f1f8e9; }
        .no-hero { border-color: #f44336; background: #fce4ec; }

        .hierarchy-path { font-size: 12px; color: #666; margin-bottom: 5px; }
        .post-title { font-weight: bold; margin-bottom: 5px; }
        .post-meta { font-size: 12px; color: #888; }
        .hero-preview { background: #fff; padding: 10px; border-radius: 4px; margin-top: 8px; font-size: 12px; }

        .type-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .type-header { font-size: 18px; font-weight: bold; margin-bottom: 15px; }
        .type-stats { font-size: 14px; color: #666; margin-bottom: 15px; }

        .missing-list { background: #ffebee; padding: 20px; border-radius: 8px; border: 1px solid #f44336; }
        .missing-list h3 { color: #c62828; margin-top: 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 RIMAN Hero-Metadaten Vollanalyse</h1>

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
                <div class="stat-number"><?php echo round(($count_with_hero / $total_posts) * 100); ?>%</div>
                <div class="stat-label">Abdeckung</div>
            </div>
        </div>

        <?php if ($count_without_hero > 0): ?>
        <div class="missing-list">
            <h3>❌ Seiten ohne Hero-Metadaten (<?php echo $count_without_hero; ?>)</h3>
            <p><strong>Diese Seiten brauchen noch Hero-Metadaten:</strong></p>

            <?php foreach ($posts_without_hero as $post): ?>
                <div class="post-item no-hero">
                    <div class="hierarchy-path"><?php echo esc_html($post['hierarchy_path']); ?></div>
                    <div class="post-title"><?php echo esc_html($post['title']); ?></div>
                    <div class="post-meta">
                        ID: <?php echo $post['id']; ?> |
                        Slug: <code><?php echo esc_html($post['slug']); ?></code> |
                        Typ: <?php echo esc_html($post['type']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
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
                        Gesamt: <?php echo count($data['posts']); ?>
                    </div>

                    <?php foreach ($data['posts'] as $post): ?>
                        <div class="post-item <?php echo $post['has_hero'] ? 'has-hero' : 'no-hero'; ?>">
                            <div class="hierarchy-path"><?php echo esc_html($post['hierarchy_path']); ?></div>
                            <div class="post-title">
                                <?php echo $post['has_hero'] ? '✅' : '❌'; ?>
                                <?php echo esc_html($post['title']); ?>
                            </div>
                            <div class="post-meta">
                                ID: <?php echo $post['id']; ?> |
                                Slug: <code><?php echo esc_html($post['slug']); ?></code>
                            </div>

                            <?php if ($post['has_hero']): ?>
                                <div class="hero-preview">
                                    <strong>Hero-Daten:</strong><br>
                                    <?php if ($post['hero_data']['title']): ?>
                                        Titel: <?php echo esc_html($post['hero_data']['title']); ?><br>
                                    <?php endif; ?>
                                    <?php if ($post['hero_data']['subtitle']): ?>
                                        Untertitel: <?php echo esc_html($post['hero_data']['subtitle']); ?><br>
                                    <?php endif; ?>
                                    <?php if ($post['hero_data']['area_label']): ?>
                                        Bereich: <?php echo esc_html($post['hero_data']['area_label']); ?><br>
                                    <?php endif; ?>
                                    <?php if ($post['hero_data']['icon']): ?>
                                        Icon: <i class="<?php echo esc_attr($post['hero_data']['icon']); ?>"></i> <?php echo esc_html($post['hero_data']['icon']); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="section">
            <h2>📋 JSON-Exportvorlage für fehlende Seiten</h2>
            <textarea style="width: 100%; height: 300px; font-family: monospace; font-size: 12px;">
{
  "missing_pages": [
<?php
$json_entries = [];
foreach ($posts_without_hero as $post) {
    $clean_title = str_replace(' - Riman GmbH', '', $post['title']);
    $json_entries[] = '    {
      "title": "' . addslashes($clean_title) . '",
      "slug": "' . $post['slug'] . '",
      "hero": {
        "title": "' . addslashes($clean_title) . '",
        "subtitle": "TODO: Untertitel hinzufügen",
        "area_label": "TODO: Bereichslabel",
        "icon": "fas fa-circle"
      }
    }';
}
echo implode(",\n", $json_entries);
?>
  ]
}
            </textarea>
        </div>
    </div>
</body>
</html>