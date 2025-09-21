<?php
/**
 * Debug Script für planung_ausschreibung Seiten
 * Findet alle Posts mit diesem Slug und prüft Hero-Metadaten
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
 * Finde alle Posts mit planung_ausschreibung Slug
 */
function find_planung_ausschreibung_posts() {
    $posts = get_posts([
        'post_type' => 'riman_seiten',
        'name' => 'planung_ausschreibung',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);

    $results = [];
    foreach ($posts as $post) {
        // Hole Parent-Hierarchie
        $hierarchy = [];
        $current = $post;
        while ($current) {
            array_unshift($hierarchy, [
                'id' => $current->ID,
                'title' => $current->post_title,
                'slug' => $current->post_name
            ]);

            if ($current->post_parent > 0) {
                $current = get_post($current->post_parent);
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

        $results[] = [
            'post' => $post,
            'hierarchy' => $hierarchy,
            'has_hero' => $has_hero,
            'hero_data' => [
                'title' => $hero_title,
                'subtitle' => $hero_subtitle,
                'area_label' => $hero_area_label,
                'icon' => $hero_icon
            ],
            'url' => get_permalink($post->ID)
        ];
    }

    return $results;
}

/**
 * Lade Hero-Metadaten und suche nach passenden Einträgen
 */
function find_matching_hero_data() {
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
                    if (strpos($path, 'planung') !== false || strpos($path, 'ausschreibung') !== false) {
                        $all_hero_data[$type][$path] = $page_data;
                    }
                }
            } elseif ($type === 'missing' && isset($data['pages'])) {
                foreach (['unterseiten', 'detailseiten', 'info_seiten'] as $section) {
                    if (isset($data['pages'][$section])) {
                        foreach ($data['pages'][$section] as $page_data) {
                            if (strpos($page_data['slug'] ?? '', 'planung') !== false ||
                                strpos($page_data['title'] ?? '', 'Planung') !== false ||
                                strpos($page_data['title'] ?? '', 'Ausschreibung') !== false) {
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

$planung_posts = find_planung_ausschreibung_posts();
$hero_data = find_matching_hero_data();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug: Planung & Ausschreibung Seiten</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        .post-item { border: 1px solid #ddd; padding: 20px; margin: 15px 0; border-radius: 8px; }
        .has-hero { border-color: #4caf50; background: #f1f8e9; }
        .no-hero { border-color: #f44336; background: #fce4ec; }
        .hierarchy { font-size: 12px; color: #666; margin-bottom: 10px; }
        .hero-preview { background: #fff; padding: 15px; border-radius: 4px; margin-top: 10px; }
        .json-section { background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .json-item { background: #fff; padding: 10px; margin: 10px 0; border-radius: 4px; border: 1px solid #ddd; }
        .error { color: #c62828; background: #ffebee; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .success { color: #2e7d32; background: #e8f5e9; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .fix-btn { background: #1976d2; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug: Planung & Ausschreibung Seiten</h1>

        <div class="success">
            <strong>Gefundene Posts:</strong> <?php echo count($planung_posts); ?><br>
            <strong>Hero-Daten-Quellen:</strong> Extended JSON + Missing JSON
        </div>

        <h2>📋 WordPress Posts mit Slug "planung_ausschreibung"</h2>

        <?php foreach ($planung_posts as $result): ?>
            <div class="post-item <?php echo $result['has_hero'] ? 'has-hero' : 'no-hero'; ?>">
                <h3>
                    <?php echo $result['has_hero'] ? '✅' : '❌'; ?>
                    <?php echo esc_html($result['post']->post_title); ?>
                </h3>

                <div class="hierarchy">
                    <strong>Hierarchie:</strong>
                    <?php
                    $hierarchy_titles = array_map(function($item) {
                        return $item['title'];
                    }, $result['hierarchy']);
                    echo esc_html(implode(' → ', $hierarchy_titles));
                    ?>
                </div>

                <div>
                    <strong>Post ID:</strong> <?php echo $result['post']->ID; ?><br>
                    <strong>Slug:</strong> <?php echo esc_html($result['post']->post_name); ?><br>
                    <strong>URL:</strong> <a href="<?php echo esc_url($result['url']); ?>" target="_blank"><?php echo esc_url($result['url']); ?></a>
                </div>

                <?php if ($result['has_hero']): ?>
                    <div class="hero-preview">
                        <strong>✅ Hero-Metadaten vorhanden:</strong><br>
                        <?php if ($result['hero_data']['title']): ?>
                            <strong>Titel:</strong> <?php echo esc_html($result['hero_data']['title']); ?><br>
                        <?php endif; ?>
                        <?php if ($result['hero_data']['subtitle']): ?>
                            <strong>Untertitel:</strong> <?php echo esc_html($result['hero_data']['subtitle']); ?><br>
                        <?php endif; ?>
                        <?php if ($result['hero_data']['area_label']): ?>
                            <strong>Bereich:</strong> <?php echo esc_html($result['hero_data']['area_label']); ?><br>
                        <?php endif; ?>
                        <?php if ($result['hero_data']['icon']): ?>
                            <strong>Icon:</strong> <i class="<?php echo esc_attr($result['hero_data']['icon']); ?>"></i> <?php echo esc_html($result['hero_data']['icon']); ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="error">
                        <strong>❌ Keine Hero-Metadaten!</strong><br>
                        Dieser Post braucht Hero-Metadaten.
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <h2>📊 Verfügbare Hero-Daten in JSON</h2>

        <?php foreach ($hero_data as $source => $data): ?>
            <div class="json-section">
                <h3><?php echo ucfirst($source); ?> JSON</h3>

                <?php if (empty($data)): ?>
                    <div class="error">Keine passenden Einträge für "planung" oder "ausschreibung" gefunden.</div>
                <?php else: ?>
                    <?php foreach ($data as $key => $item): ?>
                        <div class="json-item">
                            <?php if ($source === 'extended'): ?>
                                <strong>Pfad:</strong> <?php echo esc_html($key); ?><br>
                                <?php if (isset($item['hero'])): ?>
                                    <strong>Hero-Titel:</strong> <?php echo esc_html($item['hero']['title'] ?? 'N/A'); ?><br>
                                    <strong>Untertitel:</strong> <?php echo esc_html($item['hero']['subtitle'] ?? 'N/A'); ?><br>
                                    <strong>Bereich:</strong> <?php echo esc_html($item['hero']['area_label'] ?? 'N/A'); ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <strong>Titel:</strong> <?php echo esc_html($item['title'] ?? 'N/A'); ?><br>
                                <strong>Slug:</strong> <?php echo esc_html($item['slug'] ?? 'N/A'); ?><br>
                                <?php if (isset($item['hero'])): ?>
                                    <strong>Hero-Titel:</strong> <?php echo esc_html($item['hero']['title'] ?? 'N/A'); ?><br>
                                    <strong>Untertitel:</strong> <?php echo esc_html($item['hero']['subtitle'] ?? 'N/A'); ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <h2>🔧 Mögliche Lösungen</h2>

        <div class="error">
            <strong>Problem identifiziert:</strong><br>
            Mehrere Posts haben den gleichen Slug "planung_ausschreibung" aber unterschiedliche Parent-Hierarchien.<br>
            Die Hero-Metadaten-JSONs enthalten möglicherweise nicht alle Varianten oder die Pfad-Zuordnung funktioniert nicht korrekt.
        </div>

        <div>
            <strong>Nächste Schritte:</strong>
            <ol>
                <li>Prüfe ob alle "Planung & Ausschreibung" Varianten in der JSON definiert sind</li>
                <li>Erweitere das Path-Matching im MD-Import-Script</li>
                <li>Erstelle spezifische Hero-Metadaten für jede Parent-Kombination</li>
            </ol>
        </div>
    </div>
</body>
</html>