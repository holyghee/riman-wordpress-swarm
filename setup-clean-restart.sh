#!/bin/bash

# Setup mit KOMPLETTER BEREINIGUNG alter Daten
echo "=== SAUBERER NEUSTART mit korrekten Bild-Zuordnungen ==="

WORDPRESS_CONTAINER="riman-wordpress-swarm-wordpress-1"
SEO_JSON="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-enhanced-image-mappings-seo.json"
IMAGES_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images"

# Prüfe Docker
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker nicht gestartet"
    exit 1
fi

# Container check
if ! docker ps | grep -q "$WORDPRESS_CONTAINER"; then
    docker swarm init 2>/dev/null || true
    docker stack deploy -c docker-compose.yml riman-wordpress-swarm
    sleep 30
fi

echo "1. LÖSCHE alle alten Daten für sauberen Neustart..."

# Bereinigungsscript
cat > /tmp/clean-wordpress.php << 'CLEANSCRIPT'
<?php
/**
 * KOMPLETTE BEREINIGUNG für sauberen Neustart
 */
require_once('/var/www/html/wp-load.php');

echo "=== BEREINIGUNG ALLER ALTEN DATEN ===\n\n";

// 1. LÖSCHE ALLE ATTACHMENTS/BILDER
echo "SCHRITT 1: Lösche alle Bilder aus Media Library...\n";
$attachments = get_posts([
    'post_type' => 'attachment',
    'numberposts' => -1,
    'post_status' => 'any'
]);

foreach($attachments as $attachment) {
    wp_delete_attachment($attachment->ID, true); // true = auch Dateien löschen
}
echo "✅ " . count($attachments) . " Bilder gelöscht\n\n";

// 2. LÖSCHE ALLE FEATURED IMAGE ZUORDNUNGEN
echo "SCHRITT 2: Lösche alle Featured Image Zuordnungen...\n";
global $wpdb;

// Von Seiten
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id'");
$deleted_page_thumbs = $wpdb->rows_affected;

// Von Kategorien
$wpdb->query("DELETE FROM {$wpdb->termmeta} WHERE meta_key = '_thumbnail_id'");
$deleted_cat_thumbs = $wpdb->rows_affected;

echo "✅ $deleted_page_thumbs Seiten-Zuordnungen gelöscht\n";
echo "✅ $deleted_cat_thumbs Kategorie-Zuordnungen gelöscht\n\n";

// 3. LÖSCHE ALLE POSTS
echo "SCHRITT 3: Lösche alle Posts...\n";
$posts = get_posts([
    'post_type' => 'post',
    'numberposts' => -1,
    'post_status' => 'any'
]);

foreach($posts as $post) {
    wp_delete_post($post->ID, true);
}
echo "✅ " . count($posts) . " Posts gelöscht\n\n";

// 4. LÖSCHE ALLE SEITEN
echo "SCHRITT 4: Lösche alle Seiten...\n";
$pages = get_posts([
    'post_type' => 'page',
    'numberposts' => -1,
    'post_status' => 'any'
]);

foreach($pages as $page) {
    wp_delete_post($page->ID, true);
}
echo "✅ " . count($pages) . " Seiten gelöscht\n\n";

// 5. LÖSCHE ALLE KATEGORIEN (außer Uncategorized)
echo "SCHRITT 5: Lösche alle Kategorien...\n";
$categories = get_terms([
    'taxonomy' => 'category',
    'hide_empty' => false
]);

$deleted_cats = 0;
foreach($categories as $category) {
    if($category->slug !== 'uncategorized') {
        wp_delete_term($category->term_id, 'category');
        $deleted_cats++;
    }
}
echo "✅ $deleted_cats Kategorien gelöscht\n\n";

// 6. BEREINIGE UPLOAD-VERZEICHNIS
echo "SCHRITT 6: Bereinige Upload-Verzeichnis...\n";
$upload_dir = wp_upload_dir();
$base_dir = $upload_dir['basedir'];

// Lösche alle Dateien im Upload-Verzeichnis
if(file_exists($base_dir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    $file_count = 0;
    foreach($iterator as $file) {
        if($file->isFile()) {
            @unlink($file->getPathname());
            $file_count++;
        }
    }
    echo "✅ $file_count Dateien aus Upload-Verzeichnis gelöscht\n\n";
}

// 7. LEERE TRANSIENTS UND CACHE
echo "SCHRITT 7: Leere Cache...\n";
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'");
wp_cache_flush();
echo "✅ Cache geleert\n\n";

echo "=== BEREINIGUNG ABGESCHLOSSEN ===\n";
echo "WordPress ist jetzt komplett sauber für Neustart!\n";
CLEANSCRIPT

echo "2. Führe Bereinigung aus..."
docker exec "$WORDPRESS_CONTAINER" php -r "file_put_contents('/var/www/html/clean-wordpress.php', file_get_contents('php://stdin'));" < /tmp/clean-wordpress.php
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/clean-wordpress.php

echo "3. WordPress Neuaufbau..."
docker cp setup-complete-riman.php "$WORDPRESS_CONTAINER:/var/www/html/"
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/setup-complete-riman.php

echo "4. Importiere Bilder mit KORREKTEM Mapping..."

# Import-Script mit korrektem Mapping
cat > /tmp/import-correct.php << 'IMPORTSCRIPT'
<?php
/**
 * Import mit EXAKTEM Mapping aus SEO-JSON
 */
require_once('/var/www/html/wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

echo "=== IMPORT MIT KORREKTEM MAPPING ===\n\n";

$json = json_decode(file_get_contents('/var/www/html/wordpress-enhanced-image-mappings-seo.json'), true);
$images_dir = '/var/www/html/images/';

function import_with_seo($image_file, $data) {
    global $images_dir;
    
    $path = $images_dir . $image_file;
    if (!file_exists($path)) return false;
    
    $temp = tempnam(sys_get_temp_dir(), 'wp_');
    copy($path, $temp);
    
    // SEO-freundlicher Name
    $filename = sanitize_title($data['name'] ?? 'image') . '-' . uniqid() . '.png';
    
    $file = [
        'name' => $filename,
        'tmp_name' => $temp,
        'size' => filesize($temp),
        'type' => 'image/png'
    ];
    
    $id = media_handle_sideload($file, 0);
    @unlink($temp);
    
    if (!is_wp_error($id)) {
        // SEO-Metadaten
        wp_update_post([
            'ID' => $id,
            'post_title' => $data['title'] ?? $data['name'],
            'post_excerpt' => $data['caption'] ?? '',
            'post_content' => $data['description'] ?? ''
        ]);
        
        update_post_meta($id, '_wp_attachment_image_alt', $data['alt'] ?? '');
        
        return $id;
    }
    
    return false;
}

// Verarbeite HAUPTKATEGORIEN
echo "Hauptkategorien:\n";
foreach ($json['main_categories'] as $slug => $data) {
    if (empty($slug)) continue;
    
    // Map JSON-Slug auf WordPress-Slug
    $wp_slugs = [
        'altlasten' => 'altlasten',
        'rueckbau' => 'rueckbau',
        'schadstoffe' => 'schadstoffe',
        'sicherheit' => 'sicherheitskoordination',
        'beratung' => 'beratung'
    ];
    
    $wp_slug = $wp_slugs[$slug] ?? $slug;
    $category = get_term_by('slug', $wp_slug, 'category');
    
    if ($category) {
        echo "  {$category->name}: ";
        $id = import_with_seo($data['image'], $data);
        
        if ($id) {
            update_term_meta($category->term_id, '_thumbnail_id', $id);
            
            // Finde Seite
            $page = get_page_by_path($wp_slug);
            if ($page) {
                set_post_thumbnail($page->ID, $id);
                update_post_meta($page->ID, '_thumbnail_id', $id);
            }
            echo "✅ " . basename($data['image']) . "\n";
        } else {
            echo "❌ Fehler\n";
        }
    }
}

// Verarbeite UNTERKATEGORIEN
echo "\nUnterkategorien:\n";
foreach ($json['subcategories'] as $parent => $subs) {
    foreach ($subs as $sub_slug => $data) {
        if (!isset($data['slug'])) continue;
        
        // WordPress verwendet kombinierte Slugs
        $possible_slugs = [
            $data['slug'],
            $parent . '-' . $sub_slug,
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
        
        if ($category) {
            echo "  {$category->name}: ";
            $id = import_with_seo($data['image'], $data);
            
            if ($id) {
                update_term_meta($category->term_id, '_thumbnail_id', $id);
                
                $page = get_page_by_path($category->slug);
                if ($page) {
                    set_post_thumbnail($page->ID, $id);
                    update_post_meta($page->ID, '_thumbnail_id', $id);
                }
                echo "✅ " . basename($data['image']) . "\n";
            }
        }
    }
}

// VERIFIKATION
echo "\n=== VERIFIKATION ===\n";
$alt = get_term_by('slug', 'altlasten', 'category');
$arb = get_term_by('slug', 'sicherheitskoordination-arbeitsschutz', 'category');

if ($alt && $arb) {
    $alt_img = get_term_meta($alt->term_id, '_thumbnail_id', true);
    $arb_img = get_term_meta($arb->term_id, '_thumbnail_id', true);
    
    if ($alt_img && $arb_img) {
        if ($alt_img == $arb_img) {
            echo "❌ PROBLEM: Gleiche Bilder!\n";
        } else {
            $alt_file = get_post($alt_img);
            $arb_file = get_post($arb_img);
            echo "✅ Altlasten: " . basename($alt_file->guid) . "\n";
            echo "✅ Arbeitsschutz: " . basename($arb_file->guid) . "\n";
        }
    }
}

// Statistik
$pages = get_posts(['post_type' => 'page', 'numberposts' => -1]);
$with = 0;
foreach($pages as $p) {
    if(get_post_thumbnail_id($p->ID)) $with++;
}
echo "\n✅ $with von " . count($pages) . " Seiten haben Featured Images\n";
IMPORTSCRIPT

# Kopiere Dateien
docker cp "$IMAGES_DIR" "$WORDPRESS_CONTAINER:/var/www/html/"
docker cp "$SEO_JSON" "$WORDPRESS_CONTAINER:/var/www/html/"
docker exec "$WORDPRESS_CONTAINER" php -r "file_put_contents('/var/www/html/import-correct.php', file_get_contents('php://stdin'));" < /tmp/import-correct.php

# Führe Import aus
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/import-correct.php

echo ""
echo "=== SETUP ABGESCHLOSSEN ==="
echo "👉 http://127.0.0.1:8801"
echo "Admin: admin / admin_password_123"
echo ""
echo "✅ Alle alten Daten wurden gelöscht"
echo "✅ Frische Installation mit korrekten Bildern"
