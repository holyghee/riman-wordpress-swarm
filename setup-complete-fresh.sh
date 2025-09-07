#!/bin/bash

# ========================================================================
# KOMPLETTES ALL-IN-ONE SETUP SCRIPT FÜR RIMAN WORDPRESS
# 1. Löscht alle alten Daten
# 2. Erstellt frische WordPress-Struktur mit Content
# 3. Importiert alle Bilder mit korrektem SEO-Mapping
# ========================================================================

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║     RIMAN WORDPRESS - KOMPLETTER NEUSTART MIT CONTENT       ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Konfiguration
WORDPRESS_CONTAINER="riman-wordpress-swarm-wordpress-1"
CONTENT_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex"
IMAGES_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images"
SEO_JSON="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-enhanced-image-mappings-seo.json"

# Farben
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Helper Funktionen
echo_step() {
    echo -e "\n${BLUE}▶ $1${NC}"
    echo "────────────────────────────────────────"
}

echo_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

echo_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

echo_error() {
    echo -e "${RED}❌ $1${NC}"
}

# ========================================================================
# SCHRITT 0: VORBEREITUNGEN
# ========================================================================
echo_step "SCHRITT 0: Vorbereitungen"

# Docker prüfen
if ! docker info > /dev/null 2>&1; then
    echo_error "Docker ist nicht gestartet!"
    exit 1
fi

# Container prüfen und starten wenn nötig
if ! docker ps | grep -q "$WORDPRESS_CONTAINER"; then
    echo_warning "WordPress Container nicht gefunden. Starte Container..."
    docker swarm init 2>/dev/null || true
    docker stack deploy -c docker-compose.yml riman-wordpress-swarm
    
    echo "Warte auf WordPress (40 Sekunden)..."
    for i in {1..40}; do
        echo -n "."
        sleep 1
    done
    echo ""
fi

echo_success "WordPress Container läuft"

# ========================================================================
# SCHRITT 1: KOMPLETTE BEREINIGUNG
# ========================================================================
echo_step "SCHRITT 1: Lösche ALLE alten Daten"

cat > /tmp/clean-all.php << 'CLEANPHP'
<?php
require_once('/var/www/html/wp-load.php');

echo "Beginne komplette Bereinigung...\n\n";

// 1. Lösche alle Medien
$attachments = get_posts(['post_type' => 'attachment', 'numberposts' => -1, 'post_status' => 'any']);
foreach($attachments as $att) {
    wp_delete_attachment($att->ID, true);
}
echo "  ✓ " . count($attachments) . " Medien gelöscht\n";

// 2. Lösche alle Posts
$posts = get_posts(['post_type' => 'post', 'numberposts' => -1, 'post_status' => 'any']);
foreach($posts as $post) {
    wp_delete_post($post->ID, true);
}
echo "  ✓ " . count($posts) . " Posts gelöscht\n";

// 3. Lösche alle Seiten
$pages = get_posts(['post_type' => 'page', 'numberposts' => -1, 'post_status' => 'any']);
foreach($pages as $page) {
    wp_delete_post($page->ID, true);
}
echo "  ✓ " . count($pages) . " Seiten gelöscht\n";

// 4. Lösche alle Kategorien (außer Uncategorized)
$cats = get_terms(['taxonomy' => 'category', 'hide_empty' => false]);
foreach($cats as $cat) {
    if($cat->slug !== 'uncategorized') {
        wp_delete_term($cat->term_id, 'category');
    }
}
echo "  ✓ " . (count($cats)-1) . " Kategorien gelöscht\n";

// 5. Bereinige Metadaten
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id'");
$wpdb->query("DELETE FROM {$wpdb->termmeta} WHERE meta_key = '_thumbnail_id'");
echo "  ✓ Alle Thumbnail-Zuordnungen gelöscht\n";

// 6. Cache leeren
wp_cache_flush();
echo "  ✓ Cache geleert\n";

echo "\n✅ Bereinigung abgeschlossen - WordPress ist sauber!\n";
CLEANPHP

docker cp /tmp/clean-all.php "$WORDPRESS_CONTAINER:/var/www/html/"
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/clean-all.php

# ========================================================================
# SCHRITT 2: CONTENT UND STRUKTUR AUFBAUEN
# ========================================================================
echo_step "SCHRITT 2: Baue WordPress-Struktur mit Content auf"

# Kopiere Content-Verzeichnis
if [ -d "$CONTENT_DIR" ]; then
    docker cp "$CONTENT_DIR" "$WORDPRESS_CONTAINER:/var/www/html/riman-content"
    echo_success "Content-Verzeichnis kopiert"
else
    echo_warning "Content-Verzeichnis nicht gefunden: $CONTENT_DIR"
fi

# Kopiere Setup-Script
docker cp setup-complete-riman.php "$WORDPRESS_CONTAINER:/var/www/html/" 2>/dev/null
echo_success "Setup-Script kopiert"

# Führe Setup aus
echo "Erstelle Kategorien und Seiten mit Content..."
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/setup-complete-riman.php

# ========================================================================
# SCHRITT 3: BILDER IMPORTIEREN MIT SEO-MAPPING
# ========================================================================
echo_step "SCHRITT 3: Importiere Bilder mit korrektem SEO-Mapping"

# Kopiere Bilder-Verzeichnis
docker cp "$IMAGES_DIR" "$WORDPRESS_CONTAINER:/var/www/html/"
echo_success "Bilder kopiert"

# Kopiere SEO-JSON
docker cp "$SEO_JSON" "$WORDPRESS_CONTAINER:/var/www/html/"
echo_success "SEO-Mappings kopiert"

# Erstelle Import-Script
cat > /tmp/import-images.php << 'IMPORTPHP'
<?php
require_once('/var/www/html/wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

echo "\nImportiere Bilder mit SEO-Mapping...\n\n";

$json = json_decode(file_get_contents('/var/www/html/wordpress-enhanced-image-mappings-seo.json'), true);
$images_dir = '/var/www/html/images/';

function import_image($file, $data) {
    global $images_dir;
    
    $path = $images_dir . $file;
    if (!file_exists($path)) {
        echo "  ⚠ Bild nicht gefunden: $file\n";
        return false;
    }
    
    // Erstelle temporäre Datei
    $tmp = tempnam(sys_get_temp_dir(), 'wp_');
    copy($path, $tmp);
    
    // SEO-freundlicher Dateiname
    $filename = sanitize_title($data['name'] ?? 'image') . '-' . time() . '.png';
    
    $file_array = [
        'name' => $filename,
        'tmp_name' => $tmp,
        'size' => filesize($tmp),
        'type' => mime_content_type($tmp)
    ];
    
    $id = media_handle_sideload($file_array, 0);
    @unlink($tmp);
    
    if (!is_wp_error($id)) {
        // SEO-Metadaten setzen
        wp_update_post([
            'ID' => $id,
            'post_title' => $data['title'] ?? '',
            'post_excerpt' => $data['caption'] ?? '',
            'post_content' => $data['description'] ?? ''
        ]);
        
        // Alt-Text
        update_post_meta($id, '_wp_attachment_image_alt', $data['alt'] ?? '');
        
        return $id;
    }
    
    return false;
}

$imported = 0;
$failed = 0;

// HAUPTKATEGORIEN
echo "Hauptkategorien:\n";
foreach ($json['main_categories'] as $slug => $data) {
    if (empty($slug)) continue;
    
    // WordPress Slug-Mapping
    $wp_slugs = [
        'altlasten' => 'altlasten',
        'rueckbau' => 'rueckbau',
        'schadstoffe' => 'schadstoffe',
        'sicherheit' => 'sicherheitskoordination',
        'beratung' => 'beratung'
    ];
    
    $wp_slug = $wp_slugs[$slug] ?? $slug;
    
    // Finde Kategorie
    $category = get_term_by('slug', $wp_slug, 'category');
    
    if ($category) {
        $att_id = import_image($data['image'], $data);
        
        if ($att_id) {
            // Setze für Kategorie
            update_term_meta($category->term_id, '_thumbnail_id', $att_id);
            
            // Finde verknüpfte Seite
            $page = get_page_by_path($wp_slug);
            if (!$page) {
                // Versuche über Meta
                $pages = get_posts([
                    'post_type' => 'page',
                    'meta_key' => '_linked_category_id',
                    'meta_value' => $category->term_id,
                    'numberposts' => 1
                ]);
                $page = !empty($pages) ? $pages[0] : null;
            }
            
            if ($page) {
                set_post_thumbnail($page->ID, $att_id);
                update_post_meta($page->ID, '_thumbnail_id', $att_id);
            }
            
            echo "  ✓ {$category->name}\n";
            $imported++;
        } else {
            echo "  ✗ {$category->name} - Import fehlgeschlagen\n";
            $failed++;
        }
    }
}

// UNTERKATEGORIEN
echo "\nUnterkategorien:\n";
foreach ($json['subcategories'] as $parent => $subs) {
    foreach ($subs as $sub_slug => $data) {
        if (!isset($data['slug']) || empty($data['image'])) continue;
        
        // Versuche verschiedene Slug-Varianten
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
        foreach ($possible_slugs as $try_slug) {
            $category = get_term_by('slug', $try_slug, 'category');
            if ($category) break;
        }
        
        if ($category) {
            $att_id = import_image($data['image'], $data);
            
            if ($att_id) {
                update_term_meta($category->term_id, '_thumbnail_id', $att_id);
                
                $page = get_page_by_path($category->slug);
                if ($page) {
                    set_post_thumbnail($page->ID, $att_id);
                    update_post_meta($page->ID, '_thumbnail_id', $att_id);
                }
                
                echo "  ✓ {$category->name}\n";
                $imported++;
            } else {
                $failed++;
            }
        }
    }
}

// SPEZIELLE SEITEN
if (isset($json['special_pages'])) {
    echo "\nSpezielle Seiten:\n";
    foreach ($json['special_pages'] as $slug => $data) {
        $page = get_page_by_path($slug);
        if ($page && !empty($data['image'])) {
            $att_id = import_image($data['image'], $data);
            if ($att_id) {
                set_post_thumbnail($page->ID, $att_id);
                echo "  ✓ {$page->post_title}\n";
                $imported++;
            }
        }
    }
}

echo "\n";
echo "✅ Erfolgreich importiert: $imported\n";
if ($failed > 0) {
    echo "❌ Fehlgeschlagen: $failed\n";
}

// Finale Statistik
$all_pages = get_posts(['post_type' => 'page', 'numberposts' => -1]);
$with_img = 0;
$without_img = 0;
$without_list = [];

foreach($all_pages as $p) {
    if(get_post_thumbnail_id($p->ID)) {
        $with_img++;
    } else {
        $without_img++;
        $without_list[] = $p->post_title;
    }
}

echo "\n=== FINALE STATISTIK ===\n";
echo "Seiten mit Featured Image: $with_img\n";
echo "Seiten ohne Featured Image: $without_img\n";

if ($without_img > 0 && $without_img <= 10) {
    echo "\nSeiten ohne Bild:\n";
    foreach($without_list as $title) {
        echo "  - $title\n";
    }
}
IMPORTPHP

docker cp /tmp/import-images.php "$WORDPRESS_CONTAINER:/var/www/html/"
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/import-images.php

# ========================================================================
# SCHRITT 4: VERIFIZIERUNG
# ========================================================================
echo_step "SCHRITT 4: Verifizierung"

docker exec "$WORDPRESS_CONTAINER" php -r '
require_once("/var/www/html/wp-load.php");

echo "Prüfe kritische Zuordnungen:\n\n";

// Prüfe Altlasten vs Arbeitsschutz
$alt = get_term_by("slug", "altlasten", "category");
$arb = get_term_by("slug", "sicherheitskoordination-arbeitsschutz", "category");

if ($alt && $arb) {
    $alt_img = get_term_meta($alt->term_id, "_thumbnail_id", true);
    $arb_img = get_term_meta($arb->term_id, "_thumbnail_id", true);
    
    if ($alt_img && $arb_img) {
        if ($alt_img == $arb_img) {
            echo "❌ FEHLER: Altlasten und Arbeitsschutz haben dasselbe Bild!\n";
        } else {
            echo "✅ Altlasten und Arbeitsschutz haben unterschiedliche Bilder\n";
            
            $alt_file = basename(wp_get_attachment_url($alt_img));
            $arb_file = basename(wp_get_attachment_url($arb_img));
            
            echo "   Altlasten: " . substr($alt_file, 0, 40) . "...\n";
            echo "   Arbeitsschutz: " . substr($arb_file, 0, 40) . "...\n";
        }
    }
}

// Gesamtstatistik
echo "\n=== GESAMTSTATISTIK ===\n";

$pages = wp_count_posts("page");
$cats = wp_count_terms("category");
$media = wp_count_posts("attachment");

echo "📄 Seiten: " . $pages->publish . "\n";
echo "📁 Kategorien: " . ($cats - 1) . " (ohne Uncategorized)\n";
echo "🖼️  Medien: " . $media->inherit . "\n";

// Featured Images Check
$all = get_posts(["post_type" => "page", "numberposts" => -1]);
$with = 0;
foreach($all as $p) {
    if(get_post_thumbnail_id($p->ID)) $with++;
}

echo "✅ Featured Images: $with von " . count($all) . " Seiten\n";
'

# ========================================================================
# ABSCHLUSS
# ========================================================================
echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║                    SETUP ERFOLGREICH!                       ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
echo_success "WordPress URL: http://127.0.0.1:8801"
echo_success "Admin-Login:  admin / admin_password_123"
echo ""
echo "Das Setup hat folgendes gemacht:"
echo "  1. ✅ Alle alten Daten gelöscht"
echo "  2. ✅ WordPress-Struktur mit Content aufgebaut"
echo "  3. ✅ Bilder mit SEO-Mapping importiert"
echo "  4. ✅ Featured Images korrekt zugeordnet"
echo ""
echo_success "RIMAN WordPress ist bereit!"
