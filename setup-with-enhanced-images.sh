#!/bin/bash

# RIMAN WordPress Setup mit Enhanced Images
# Kombiniert das bewährte System mit den neuen Enhanced Mappings

echo "=== Riman WordPress Setup mit Enhanced Images ==="
echo ""

# Farben für bessere Ausgabe
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
WORDPRESS_CONTAINER="riman-wordpress-swarm-wordpress-1"
IMAGES_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images"
ORGANIZED_IMAGES_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-content/image-server"
CATEGORIZED_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/categorized-images"
ENHANCED_MAPPINGS="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-enhanced-image-mappings.json"
UNIQUE_MAPPINGS="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-unique-image-mappings.json"

echo_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
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

# Prüfe ob Docker läuft
if ! docker info > /dev/null 2>&1; then
    echo_error "Docker ist nicht gestartet. Bitte Docker starten."
    exit 1
fi

# Prüfe ob der Container läuft
if ! docker ps | grep -q "$WORDPRESS_CONTAINER"; then
    echo_warning "Container nicht gefunden. Starte Docker Swarm..."
    docker swarm init 2>/dev/null || true
    docker stack deploy -c docker-compose.yml riman-wordpress-swarm
    
    echo "Warte auf WordPress-Start (30 Sekunden)..."
    sleep 30
fi

echo ""
echo "1. Organisiere Bilder mit Enhanced Mappings..."

# Erstelle kategorisiertes Verzeichnis
mkdir -p "$CATEGORIZED_DIR"

# Nutze Enhanced Mappings für bessere Bildzuordnung - RICHTIGE VERSION MIT /images
if [ -f "$ENHANCED_MAPPINGS" ]; then
    echo_info "Verwende Enhanced Mappings für genaue Bildzuordnung..."
    
    # Verwende Enhanced Mappings mit Slug-Translation
    php -r '
    // Lade Slug Translation Mapping
    include("slug-translation-mapping.php");
    
    $enhanced = json_decode(file_get_contents("'$ENHANCED_MAPPINGS'"), true);
    
    $copied = 0;
    $target_dir = "'$CATEGORIZED_DIR'";
    $images_dir = "'$IMAGES_DIR'";
    $processed_images = array();
    $used_for_pages = array();
    $translated_slugs = array();
    
    // Verwende die page_mappings aus enhanced mit Slug-Translation
    if (isset($enhanced["page_mappings"])) {
        foreach ($enhanced["page_mappings"] as $enhanced_slug => $image_filename) {
            // Skip leere Einträge
            if (empty($image_filename)) continue;
            
            // Translate Enhanced Slug zu WordPress Slug
            $wp_slug = translateEnhancedSlugToWordPressSlug($enhanced_slug);
            if (!$wp_slug) {
                echo "⚠️  Keine Übersetzung gefunden für: " . $enhanced_slug . "\n";
                continue;
            }
            
            // Track Translation
            $translated_slugs[] = "$enhanced_slug → $wp_slug";
            
            // Track welches Bild für welche Seite verwendet wird
            if (!isset($used_for_pages[$image_filename])) {
                $used_for_pages[$image_filename] = array();
            }
            $used_for_pages[$image_filename][] = $wp_slug;
            
            // Kopiere nur einmal pro eindeutiger Datei
            if (in_array($image_filename, $processed_images)) {
                echo "⏭️  Bereits kopiert für andere Seite: " . basename($image_filename) . "\n";
                continue;
            }
            
            $source = $images_dir . "/" . $image_filename;
            if (file_exists($source)) {
                // Verwende WordPress Slug für Dateinamen
                $target = $target_dir . "/" . $wp_slug . "_" . basename($image_filename);
                copy($source, $target);
                echo "✅ " . $wp_slug . " (" . substr($enhanced_slug, 0, 30) . "...): " . basename($image_filename) . "\n";
                $copied++;
                $processed_images[] = $image_filename;
            } else {
                echo "⚠️  Bild nicht gefunden: " . $source . "\n";
            }
        }
    }
    
    echo "\n=== Statistik ===\n";
    echo "Kopierte Dateien: $copied\n";
    echo "Eindeutige Bilder: " . count($processed_images) . "\n";
    
    // Zeige welche Bilder mehrfach verwendet werden
    $duplicates = 0;
    foreach ($used_for_pages as $img => $pages) {
        if (count($pages) > 1) {
            $duplicates++;
        }
    }
    echo "Bilder mit mehrfacher Verwendung: $duplicates\n";
    echo "\n=== Bilder aus Enhanced Mappings organisiert ===\n";
    '
else
    echo_warning "Enhanced Mappings nicht gefunden, verwende Standard-Methode..."
    
    # Fallback zur alten Methode
    ./organize-midjourney-images.sh
fi

echo_success "Bilder organisiert"

echo ""
echo "2. Kopiere Dateien in Container..."

# Kopiere PHP-Scripts
docker cp setup-complete-riman.php "$WORDPRESS_CONTAINER:/var/www/html/" 2>/dev/null
echo_success "setup-complete-riman.php kopiert"

docker cp import-categorized-images.php "$WORDPRESS_CONTAINER:/var/www/html/" 2>/dev/null  
echo_success "import-categorized-images.php kopiert"

docker cp fix-featured-images-properly.php "$WORDPRESS_CONTAINER:/var/www/html/" 2>/dev/null
echo_success "fix-featured-images-properly.php kopiert"

# Kopiere Enhanced Import Script
cat > /tmp/import-enhanced-images.php << 'EOF'
<?php
/**
 * Import Enhanced Images with Better Mapping - No Duplicates
 */

require_once('/var/www/html/wp-load.php');

echo "=== Import Enhanced Images (Ohne Duplikate) ===\n\n";

$image_dir = '/tmp/categorized-images/';
$mapping_file = $image_dir . 'page_image_mapping.json';
$imported_count = 0;
$updated_count = 0;
$processed_images = array(); // Track bereits verarbeitete Bilder

// Lade Page-Image Mapping falls vorhanden
$page_image_map = array();
if (file_exists($mapping_file)) {
    $page_image_map = json_decode(file_get_contents($mapping_file), true);
    echo "✅ Page-Image Mapping geladen mit " . count($page_image_map) . " Zuordnungen\n\n";
}

// Lade alle Seiten
$pages = get_posts(array(
    'post_type' => 'page',
    'numberposts' => -1,
    'post_status' => 'any'
));

$categories = get_categories(array('hide_empty' => false));

// Durchsuche kategorisierte Bilder
$images = glob($image_dir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

echo "Gefunden: " . count($images) . " Bilder\n";
echo "Gefunden: " . count($pages) . " Seiten\n\n";

foreach ($images as $image_path) {
    $filename = basename($image_path);
    
    // Skip bereits verarbeitete Bilder
    if (in_array($filename, $processed_images)) {
        echo "⏭️  Bereits verarbeitet: $filename\n";
        continue;
    }
    
    // Extrahiere Slug aus Dateiname (format: slug_bildname.ext)
    if (preg_match('/^([^_]+)_(.+)$/', $filename, $matches)) {
        $slug = $matches[1];
        $original_name = $matches[2];
        
        echo "Verarbeite: $filename (Slug: $slug)\n";
        
        // Finde passende Seite
        $target_page = null;
        $matched_by = "";
        
        foreach ($pages as $page) {
            // Exakter Slug-Match
            if ($page->post_name == $slug) {
                $target_page = $page;
                $matched_by = "exakter Slug";
                break;
            }
            // Titel-basierter Match
            if (sanitize_title($page->post_title) == $slug) {
                $target_page = $page;
                $matched_by = "Titel-Slug";
                break;
            }
            // Teilweiser Match (nur wenn andere Methoden nicht funktionieren)
            if (strpos($page->post_name, $slug) !== false) {
                $target_page = $page;
                $matched_by = "Teil-Slug";
                break;
            }
        }
        
        // Wenn keine direkte Seite gefunden, suche über Kategorie
        if (!$target_page) {
            foreach ($categories as $category) {
                if ($category->slug == $slug) {
                    // Finde Seiten dieser Kategorie
                    $cat_pages = get_posts(array(
                        'post_type' => 'page',
                        'meta_key' => '_page_category',
                        'meta_value' => $category->term_id,
                        'numberposts' => -1
                    ));
                    
                    if (!empty($cat_pages)) {
                        $target_page = $cat_pages[0];
                        $matched_by = "Kategorie";
                        break;
                    }
                }
            }
        }
        
        if ($target_page) {
            echo "  → Zugeordnet zu: {$target_page->post_title} (via $matched_by)\n";
            
            // Prüfe ob Seite bereits Featured Image hat
            $existing_thumbnail = get_post_thumbnail_id($target_page->ID);
            
            if (!$existing_thumbnail) {
                // Prüfe ob dieses Bild bereits in WordPress existiert
                global $wpdb;
                $attachment_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT ID FROM $wpdb->posts 
                     WHERE post_type = 'attachment' 
                     AND post_title = %s 
                     LIMIT 1",
                    pathinfo($original_name, PATHINFO_FILENAME)
                ));
                
                if (!$attachment_id) {
                    // Importiere Bild
                    $upload = wp_upload_bits($original_name, null, file_get_contents($image_path));
                    
                    if (!$upload['error']) {
                        $attachment_data = array(
                            'guid' => $upload['url'],
                            'post_mime_type' => mime_content_type($image_path),
                            'post_title' => pathinfo($original_name, PATHINFO_FILENAME),
                            'post_content' => '',
                            'post_status' => 'inherit'
                        );
                        
                        $attachment_id = wp_insert_attachment($attachment_data, $upload['file'], $target_page->ID);
                        
                        if (!is_wp_error($attachment_id)) {
                            require_once(ABSPATH . 'wp-admin/includes/image.php');
                            $attach_metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                            wp_update_attachment_metadata($attachment_id, $attach_metadata);
                            
                            echo "  → Bild importiert: ID $attachment_id\n";
                        }
                    } else {
                        echo "  ⚠️  Upload fehlgeschlagen: {$upload['error']}\n";
                        continue;
                    }
                }
                
                // Setze als Featured Image mit Verification
                if ($attachment_id) {
                    $success = set_post_thumbnail($target_page->ID, $attachment_id);
                    
                    if (!$success) {
                        update_post_meta($target_page->ID, '_thumbnail_id', $attachment_id);
                    }
                    
                    // Verifiziere
                    $verify = get_post_thumbnail_id($target_page->ID);
                    if ($verify != $attachment_id) {
                        update_post_meta($target_page->ID, '_thumbnail_id', $attachment_id);
                    }
                    
                    echo "  ✅ Featured Image gesetzt für: {$target_page->post_title}\n";
                    $imported_count++;
                    
                    // Markiere Bild als verarbeitet
                    $processed_images[] = $filename;
                }
            } else {
                echo "  ℹ️  Seite hat bereits Featured Image\n";
            }
        } else {
            echo "  ⚠️  Keine passende Seite gefunden\n";
        }
    }
}

echo "\n=== Import abgeschlossen ===\n";
echo "✅ Importiert: $imported_count neue Bilder\n";
echo "⏭️  Übersprungen: " . (count($images) - $imported_count) . " Bilder\n";

// Finale Statistik
$pages_with = 0;
$pages_without = 0;
$pages_without_list = array();

foreach ($pages as $page) {
    if (get_post_thumbnail_id($page->ID)) {
        $pages_with++;
    } else {
        $pages_without++;
        $pages_without_list[] = $page->post_title;
    }
}

echo "\n=== Finale Statistik ===\n";
echo "📊 Seiten mit Featured Images: $pages_with\n";
echo "📊 Seiten ohne Featured Images: $pages_without\n";

if ($pages_without > 0 && $pages_without <= 10) {
    echo "\nSeiten ohne Bilder:\n";
    foreach ($pages_without_list as $title) {
        echo "  - $title\n";
    }
}
EOF

docker cp /tmp/import-enhanced-images.php "$WORDPRESS_CONTAINER:/var/www/html/"
echo_success "Enhanced Import Script kopiert"

# Kopiere kategorisierte Bilder
docker cp "$CATEGORIZED_DIR" "$WORDPRESS_CONTAINER:/tmp/" 2>/dev/null
echo_success "Kategorisierte Bilder kopiert"

# Kopiere Content falls vorhanden
if [ -d "/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex" ]; then
    docker cp /Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex "$WORDPRESS_CONTAINER:/var/www/html/riman-content"
fi

echo ""
echo "3. Führe WordPress-Setup aus..."
echo "   Erstelle Kategorien und Seiten..."

docker exec "$WORDPRESS_CONTAINER" php /var/www/html/setup-complete-riman.php

echo ""
echo "4. Importiere Enhanced Images..."
echo "   Weise Bilder den Seiten zu..."

docker exec "$WORDPRESS_CONTAINER" php /var/www/html/import-enhanced-images.php

echo ""
echo "5. Finale Featured Image Korrektur..."

docker exec "$WORDPRESS_CONTAINER" php /var/www/html/fix-featured-images-properly.php

echo ""
echo_success "=== Setup abgeschlossen ==="
echo ""
echo "WordPress ist verfügbar unter:"
echo_success "👉 http://127.0.0.1:8801"
echo ""
echo "Admin-Login:"
echo "👤 Benutzer: admin"
echo "🔑 Passwort: admin_password_123"
echo ""

# Prüfe Ergebnisse
echo "Prüfe Featured Images Status..."
docker exec "$WORDPRESS_CONTAINER" bash -c "
php -r '
require_once(\"/var/www/html/wp-load.php\");
\$pages = get_posts(array(\"post_type\" => \"page\", \"numberposts\" => -1));
\$with_image = 0;
\$without_image = 0;
foreach(\$pages as \$page) {
    if(get_post_thumbnail_id(\$page->ID)) {
        \$with_image++;
    } else {
        \$without_image++;
    }
}
echo \"📊 Seiten mit Featured Images: \" . \$with_image . \"\\n\";
echo \"📊 Seiten ohne Featured Images: \" . \$without_image . \"\\n\";
'
"

echo_success "✅ Setup mit Enhanced Images komplett!"