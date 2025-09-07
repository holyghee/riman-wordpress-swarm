#!/bin/bash

# Korrigiertes Setup-Script das die SEO-JSON verwendet
echo "=== Riman WordPress Setup mit SEO-optimierten Images ==="

# Configuration - NUTZE DIE SEO VERSION!
WORDPRESS_CONTAINER="riman-wordpress-swarm-wordpress-1"
SEO_MAPPINGS="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-enhanced-image-mappings-seo.json"
IMAGES_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images"
CATEGORIZED_DIR="/tmp/categorized-images"

# Prüfe ob Docker läuft
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker ist nicht gestartet"
    exit 1
fi

# Stelle sicher dass Container läuft
if ! docker ps | grep -q "$WORDPRESS_CONTAINER"; then
    docker swarm init 2>/dev/null || true
    docker stack deploy -c docker-compose.yml riman-wordpress-swarm
    sleep 30
fi

echo "1. Organisiere Bilder..."
mkdir -p "$CATEGORIZED_DIR"

# Kopiere ALLE Bilder direkt (wir mappen später korrekt)
cp -r "$IMAGES_DIR"/* "$CATEGORIZED_DIR"/ 2>/dev/null
echo "✅ Bilder kopiert"

echo "2. Erstelle korrektes Import-Script mit SEO-Daten..."

cat > /tmp/import-with-seo.php << 'EOF'
<?php
/**
 * Import mit SEO-optimierten Mappings
 */
require_once('/var/www/html/wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

echo "=== Import mit SEO-Mappings ===\n\n";

// Lade SEO-Mappings
$json_file = '/var/www/html/wordpress-enhanced-image-mappings-seo.json';
if (!file_exists($json_file)) {
    die("❌ SEO-Mappings nicht gefunden!\n");
}

$mappings = json_decode(file_get_contents($json_file), true);
$image_dir = '/tmp/categorized-images/';

// Helper-Funktion
function import_image_with_seo($image_file, $seo_data, $page_id = 0) {
    global $image_dir;
    
    $path = $image_dir . $image_file;
    if (!file_exists($path)) {
        echo "  ⚠️  Bild nicht gefunden: $image_file\n";
        return false;
    }
    
    // Kopiere zu temp
    $temp = tempnam(sys_get_temp_dir(), 'wp_');
    copy($path, $temp);
    
    // Erstelle SEO-freundlichen Dateinamen
    $seo_filename = sanitize_title($seo_data['name'] ?? 'image') . '.png';
    
    $file = [
        'name' => $seo_filename,
        'tmp_name' => $temp,
        'size' => filesize($temp),
        'type' => 'image/png'
    ];
    
    $attachment_id = media_handle_sideload($file, $page_id);
    @unlink($temp);
    
    if (!is_wp_error($attachment_id)) {
        // Setze SEO-Metadaten
        wp_update_post([
            'ID' => $attachment_id,
            'post_title' => $seo_data['title'] ?? '',
            'post_excerpt' => $seo_data['caption'] ?? '',
            'post_content' => $seo_data['description'] ?? ''
        ]);
        
        // Alt-Text
        if (!empty($seo_data['alt'])) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $seo_data['alt']);
        }
        
        return $attachment_id;
    }
    
    return false;
}

// 1. Hauptkategorien
echo "SCHRITT 1: Hauptkategorien\n";
foreach ($mappings['main_categories'] as $slug => $data) {
    if (empty($slug)) continue;
    
    echo "Verarbeite: {$data['name']} (Slug: {$data['slug']})\n";
    
    // Finde Kategorie
    $category = get_term_by('slug', $data['slug'], 'category');
    if (!$category) {
        // Versuche alternative Slugs
        $alt_slugs = [$slug, 'altlasten', 'rueckbau', 'schadstoffe', 'sicherheitskoordination', 'beratung'];
        foreach ($alt_slugs as $try) {
            $category = get_term_by('slug', $try, 'category');
            if ($category) break;
        }
    }
    
    if (!$category) {
        echo "  ⚠️  Kategorie nicht gefunden\n";
        continue;
    }
    
    // Importiere Bild mit SEO-Daten
    $attachment_id = import_image_with_seo($data['image'], $data);
    
    if ($attachment_id) {
        // Setze für Kategorie
        update_term_meta($category->term_id, '_thumbnail_id', $attachment_id);
        
        // Finde verknüpfte Seite
        $pages = get_posts([
            'post_type' => 'page',
            'meta_key' => '_linked_category_id',
            'meta_value' => $category->term_id,
            'numberposts' => 1
        ]);
        
        if (empty($pages)) {
            // Alternative: Suche nach Slug
            $pages = get_posts([
                'post_type' => 'page',
                'name' => $category->slug,
                'numberposts' => 1
            ]);
        }
        
        if (!empty($pages)) {
            set_post_thumbnail($pages[0]->ID, $attachment_id);
            update_post_meta($pages[0]->ID, '_thumbnail_id', $attachment_id);
            echo "  ✅ Featured Image gesetzt mit SEO-Daten\n";
        }
    }
}

// 2. Unterkategorien
echo "\nSCHRITT 2: Unterkategorien\n";
foreach ($mappings['subcategories'] as $parent_slug => $subcats) {
    foreach ($subcats as $sub_slug => $data) {
        if (empty($data['slug'])) continue;
        
        echo "Verarbeite: {$data['name']} (Slug: {$data['slug']})\n";
        
        // Finde Kategorie mit verschiedenen Slug-Varianten
        $possible_slugs = [
            $data['slug'],
            $parent_slug . '-' . $sub_slug,
            'altlasten-' . $sub_slug,
            'rueckbau-' . $sub_slug,
            'schadstoffe-' . $sub_slug,
            'sicherheitskoordination-' . $sub_slug,
            'beratung-' . $sub_slug
        ];
        
        $category = null;
        foreach ($possible_slugs as $try) {
            $category = get_term_by('slug', $try, 'category');
            if ($category) break;
        }
        
        if (!$category) {
            echo "  ⚠️  Kategorie nicht gefunden\n";
            continue;
        }
        
        $attachment_id = import_image_with_seo($data['image'], $data);
        
        if ($attachment_id) {
            update_term_meta($category->term_id, '_thumbnail_id', $attachment_id);
            
            // Verknüpfte Seite
            $pages = get_posts([
                'post_type' => 'page',
                'name' => $category->slug,
                'numberposts' => 1
            ]);
            
            if (!empty($pages)) {
                set_post_thumbnail($pages[0]->ID, $attachment_id);
                update_post_meta($pages[0]->ID, '_thumbnail_id', $attachment_id);
                echo "  ✅ Featured Image mit SEO-Daten gesetzt\n";
            }
        }
    }
}

// 3. Spezielle Seiten
echo "\nSCHRITT 3: Spezielle Seiten\n";
if (isset($mappings['special_pages'])) {
    foreach ($mappings['special_pages'] as $slug => $data) {
        $pages = get_posts([
            'post_type' => 'page',
            'name' => $slug,
            'numberposts' => 1
        ]);
        
        if (!empty($pages)) {
            $page = $pages[0];
            if (!get_post_thumbnail_id($page->ID)) {
                $attachment_id = import_image_with_seo($data['image'], $data, $page->ID);
                if ($attachment_id) {
                    set_post_thumbnail($page->ID, $attachment_id);
                    echo "✅ {$page->post_title}: SEO-optimiertes Bild gesetzt\n";
                }
            }
        }
    }
}

// Statistik
$pages = get_posts(['post_type' => 'page', 'numberposts' => -1]);
$with = 0;
$without = 0;

foreach ($pages as $page) {
    if (get_post_thumbnail_id($page->ID)) {
        $with++;
    } else {
        $without++;
        echo "❌ Ohne Bild: {$page->post_title}\n";
    }
}

echo "\n=== FINALE STATISTIK ===\n";
echo "✅ Mit Featured Image: $with\n";
echo "❌ Ohne Featured Image: $without\n";
EOF

echo "3. Kopiere Dateien in Container..."

# Kopiere SEO-Mappings
docker cp "$SEO_MAPPINGS" "$WORDPRESS_CONTAINER:/var/www/html/"
echo "✅ SEO-Mappings kopiert"

# Kopiere Import-Script
docker cp /tmp/import-with-seo.php "$WORDPRESS_CONTAINER:/var/www/html/"
echo "✅ Import-Script kopiert"

# Kopiere Bilder
docker cp "$CATEGORIZED_DIR" "$WORDPRESS_CONTAINER:/tmp/"
echo "✅ Bilder kopiert"

# Kopiere Setup-Script
docker cp setup-complete-riman.php "$WORDPRESS_CONTAINER:/var/www/html/"
echo "✅ Setup-Script kopiert"

echo "4. WordPress Setup..."
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/setup-complete-riman.php

echo "5. Import mit SEO-Daten..."
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/import-with-seo.php

echo ""
echo "=== Setup abgeschlossen ==="
echo "👉 http://127.0.0.1:8801"
echo "Admin: admin / admin_password_123"

# Finale Prüfung
docker exec "$WORDPRESS_CONTAINER" php -r '
require_once("/var/www/html/wp-load.php");
$pages = get_posts(["post_type" => "page", "numberposts" => -1]);
$with = 0;
foreach($pages as $p) {
    if(get_post_thumbnail_id($p->ID)) $with++;
}
echo "\n📊 " . $with . " von " . count($pages) . " Seiten haben Featured Images mit SEO-Daten!\n";
'

echo "✅ Setup komplett!"
