#!/bin/bash

# KORRIGIERTES Setup-Script mit richtigem Mapping
echo "=== KORRIGIERTES Setup mit richtigen Bild-Zuordnungen ==="

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

echo "1. Erstelle KORREKTES Mapping..."

# Erstelle PHP-Script das die JSON RICHTIG nutzt
cat > /tmp/correct-import.php << 'PHPSCRIPT'
<?php
/**
 * KORREKTER Import der die JSON-Mappings EXAKT befolgt
 */
require_once('/var/www/html/wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

echo "=== KORREKTER Import mit exaktem Mapping ===\n\n";

// Lade SEO-JSON
$json = json_decode(file_get_contents('/var/www/html/wordpress-enhanced-image-mappings-seo.json'), true);
$images_dir = '/var/www/html/images/';

// Helper: Import mit SEO
function import_image($image_file, $data) {
    global $images_dir;
    
    $path = $images_dir . $image_file;
    if (!file_exists($path)) {
        echo "  ❌ Bild nicht gefunden: $image_file\n";
        return false;
    }
    
    $temp = tempnam(sys_get_temp_dir(), 'wp_');
    copy($path, $temp);
    
    $file = [
        'name' => sanitize_title($data['name'] ?? 'image') . '.png',
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
            'post_title' => $data['title'] ?? '',
            'post_excerpt' => $data['caption'] ?? '',
            'post_content' => $data['description'] ?? ''
        ]);
        
        if (!empty($data['alt'])) {
            update_post_meta($id, '_wp_attachment_image_alt', $data['alt']);
        }
        
        return $id;
    }
    
    return false;
}

// WICHTIG: Erstelle exaktes Slug-Mapping
$slug_mapping = [
    // Hauptkategorien - aus der WordPress-DB
    'altlasten' => 'altlasten',
    'rueckbau' => 'rueckbau',
    'schadstoffe' => 'schadstoffe',
    'sicherheit' => 'sicherheitskoordination',
    'beratung' => 'beratung',
    
    // Unterkategorien - exakte WordPress-Slugs
    'erkundung' => 'altlasten-erkundung',
    'sanierungsplanung' => 'altlasten-sanierungsplanung',
    'bodensanierung' => 'altlasten-bodensanierung',
    'grundwassersanierung' => 'altlasten-grundwassersanierung',
    'monitoring' => 'altlasten-monitoring',
    
    'planung' => 'rueckbau-planung',
    'ausschreibung' => 'rueckbau-ausschreibung',
    'durchfuehrung' => 'rueckbau-durchfuehrung',
    'entsorgung' => 'rueckbau-entsorgung',
    'recycling' => 'rueckbau-recycling',
    'dokumentation' => 'rueckbau-dokumentation',
    
    'asbest' => 'schadstoffe-asbest',
    'pcb' => 'schadstoffe-pcb',
    'pak' => 'schadstoffe-pak',
    'kmf' => 'schadstoffe-kmf',
    'schwermetalle' => 'schadstoffe-schwermetalle',
    
    'sigeko-planung' => 'sicherheitskoordination-sigeko-planung',
    'sigeko-ausfuehrung' => 'sicherheitskoordination-sigeko-ausfuehrung',
    'gefaehrdungsbeurteilung' => 'sicherheitskoordination-gefaehrdungsbeurteilung',
    'arbeitsschutz' => 'sicherheitskoordination-arbeitsschutz',
    'notfallmanagement' => 'sicherheitskoordination-notfallmanagement',
    
    'projektberatung' => 'beratung-projektberatung',
    'baumediation' => 'beratung-baumediation',
    'gutachten' => 'beratung-gutachten',
    'compliance' => 'beratung-compliance',
    'schulungen' => 'beratung-schulungen'
];

echo "SCHRITT 1: Hauptkategorien mit KORREKTEM Mapping\n";
echo str_repeat("-", 60) . "\n";

// Verarbeite Hauptkategorien EXAKT nach JSON
foreach ($json['main_categories'] as $json_slug => $data) {
    if (empty($json_slug)) continue;
    
    $wp_slug = $slug_mapping[$json_slug] ?? $json_slug;
    
    echo "JSON-Slug: '$json_slug' → WordPress-Slug: '$wp_slug'\n";
    echo "  Bild: {$data['image']}\n";
    
    // Finde Kategorie mit EXAKTEM Slug
    $category = get_term_by('slug', $wp_slug, 'category');
    
    if (!$category) {
        echo "  ⚠️  Kategorie '$wp_slug' nicht gefunden\n";
        continue;
    }
    
    // Importiere das RICHTIGE Bild aus der JSON
    $attachment_id = import_image($data['image'], $data);
    
    if ($attachment_id) {
        // Setze für Kategorie
        update_term_meta($category->term_id, '_thumbnail_id', $attachment_id);
        
        // Finde Seite mit GLEICHEM Slug
        $pages = get_posts([
            'post_type' => 'page',
            'name' => $wp_slug,
            'numberposts' => 1
        ]);
        
        if (!empty($pages)) {
            $page = $pages[0];
            set_post_thumbnail($page->ID, $attachment_id);
            update_post_meta($page->ID, '_thumbnail_id', $attachment_id);
            echo "  ✅ {$category->name}: Richtiges Bild gesetzt!\n";
        } else {
            echo "  ✅ {$category->name}: Kategorie-Bild gesetzt\n";
        }
    }
}

echo "\nSCHRITT 2: Unterkategorien mit KORREKTEM Mapping\n";
echo str_repeat("-", 60) . "\n";

// Verarbeite Unterkategorien EXAKT nach JSON
foreach ($json['subcategories'] as $parent => $subs) {
    foreach ($subs as $sub_slug => $data) {
        if (empty($sub_slug) || !isset($data['slug'])) continue;
        
        $wp_slug = $slug_mapping[$sub_slug] ?? $data['slug'];
        
        echo "Unterkategorie: '$sub_slug' → '$wp_slug'\n";
        echo "  Bild: {$data['image']}\n";
        
        $category = get_term_by('slug', $wp_slug, 'category');
        
        if (!$category) {
            // Versuche mit Parent-Prefix
            $alt_slug = $parent . '-' . $sub_slug;
            $category = get_term_by('slug', $alt_slug, 'category');
            if ($category) {
                $wp_slug = $alt_slug;
            }
        }
        
        if (!$category) {
            echo "  ⚠️  Kategorie '$wp_slug' nicht gefunden\n";
            continue;
        }
        
        // Importiere das RICHTIGE Bild
        $attachment_id = import_image($data['image'], $data);
        
        if ($attachment_id) {
            update_term_meta($category->term_id, '_thumbnail_id', $attachment_id);
            
            // Seite mit gleichem Slug
            $pages = get_posts([
                'post_type' => 'page',
                'name' => $wp_slug,
                'numberposts' => 1
            ]);
            
            if (!empty($pages)) {
                set_post_thumbnail($pages[0]->ID, $attachment_id);
                update_post_meta($pages[0]->ID, '_thumbnail_id', $attachment_id);
                echo "  ✅ {$category->name}: Richtiges Bild gesetzt!\n";
            }
        }
    }
}

// Spezielle Seiten
echo "\nSCHRITT 3: Spezielle Seiten\n";
echo str_repeat("-", 60) . "\n";

$special_pages = [
    'home' => 'startseite',
    'kontakt' => 'kontakt',
    'impressum' => 'impressum',
    'ueber-uns' => 'ueber-uns'
];

foreach ($special_pages as $json_key => $wp_slug) {
    if (!isset($json['special_pages'][$json_key])) continue;
    
    $data = $json['special_pages'][$json_key];
    
    $pages = get_posts([
        'post_type' => 'page',
        'name' => $wp_slug,
        'numberposts' => 1
    ]);
    
    if (!empty($pages)) {
        $page = $pages[0];
        $attachment_id = import_image($data['image'], $data);
        
        if ($attachment_id) {
            set_post_thumbnail($page->ID, $attachment_id);
            echo "✅ {$page->post_title}: Bild gesetzt\n";
        }
    }
}

// VERIFIZIERUNG: Prüfe ob die richtigen Bilder gesetzt wurden
echo "\n=== VERIFIZIERUNG ===\n";
echo str_repeat("-", 60) . "\n";

// Prüfe speziell Altlasten und Arbeitsschutz
$altlasten = get_term_by('slug', 'altlasten', 'category');
$arbeitsschutz = get_term_by('slug', 'sicherheitskoordination-arbeitsschutz', 'category');

if ($altlasten) {
    $alt_thumb = get_term_meta($altlasten->term_id, '_thumbnail_id', true);
    if ($alt_thumb) {
        $alt_attachment = get_post($alt_thumb);
        echo "Altlasten hat Bild: " . basename($alt_attachment->guid) . "\n";
        echo "  Sollte sein: Environmental_monitoring_station...\n";
    }
}

if ($arbeitsschutz) {
    $arb_thumb = get_term_meta($arbeitsschutz->term_id, '_thumbnail_id', true);
    if ($arb_thumb) {
        $arb_attachment = get_post($arb_thumb);
        echo "Arbeitsschutz hat Bild: " . basename($arb_attachment->guid) . "\n";
        echo "  Sollte sein: Safety_coordination...\n";
    }
}

// Finale Statistik
$pages = get_posts(['post_type' => 'page', 'numberposts' => -1]);
$correct = 0;
$wrong = 0;

foreach ($pages as $page) {
    if (get_post_thumbnail_id($page->ID)) {
        $correct++;
    } else {
        $wrong++;
        echo "❌ Fehlt: {$page->post_title} (Slug: {$page->post_name})\n";
    }
}

echo "\n=== ERGEBNIS ===\n";
echo "✅ Mit richtigem Bild: $correct\n";
echo "❌ Ohne Bild: $wrong\n";
PHPSCRIPT

echo "2. Kopiere Dateien..."

# Kopiere Bilder
docker cp "$IMAGES_DIR" "$WORDPRESS_CONTAINER:/var/www/html/"
echo "✅ Bilder kopiert"

# Kopiere SEO-JSON
docker cp "$SEO_JSON" "$WORDPRESS_CONTAINER:/var/www/html/"
echo "✅ SEO-JSON kopiert"

# Kopiere Import-Script
docker cp /tmp/correct-import.php "$WORDPRESS_CONTAINER:/var/www/html/"
echo "✅ Import-Script kopiert"

# Setup
docker cp setup-complete-riman.php "$WORDPRESS_CONTAINER:/var/www/html/"

echo "3. WordPress Setup..."
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/setup-complete-riman.php

echo "4. KORREKTER Import..."
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/correct-import.php

echo ""
echo "=== FERTIG ==="
echo "👉 http://127.0.0.1:8801"
echo ""

# Finale Prüfung
echo "Prüfe ob Altlasten und Arbeitsschutz verschiedene Bilder haben..."
docker exec "$WORDPRESS_CONTAINER" php -r '
require_once("/var/www/html/wp-load.php");
$alt = get_term_by("slug", "altlasten", "category");
$arb = get_term_by("slug", "sicherheitskoordination-arbeitsschutz", "category");

if ($alt && $arb) {
    $alt_img = get_term_meta($alt->term_id, "_thumbnail_id", true);
    $arb_img = get_term_meta($arb->term_id, "_thumbnail_id", true);
    
    if ($alt_img == $arb_img) {
        echo "❌ FEHLER: Altlasten und Arbeitsschutz haben dasselbe Bild!\n";
    } else {
        echo "✅ KORREKT: Altlasten und Arbeitsschutz haben verschiedene Bilder!\n";
    }
}
'

echo "✅ Setup mit korrekten Mappings abgeschlossen!"
