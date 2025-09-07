#!/bin/bash

# RIMAN WordPress Setup mit eindeutigen, organisierten Bildern
# Verwendet NUR die kuratierten Bilder aus wordpress-unique-image-mappings.json

echo "=== Riman WordPress Setup mit eindeutigen Bildern ==="
echo ""

# Farben für bessere Ausgabe
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
WORDPRESS_CONTAINER="riman-wordpress-swarm-wordpress-1"
ORGANIZED_IMAGES_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-content/image-server"
CATEGORIZED_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/categorized-images"
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
echo "1. Organisiere Bilder aus Unique Mappings..."

# Erstelle kategorisiertes Verzeichnis
mkdir -p "$CATEGORIZED_DIR"

# Kopiere NUR die kuratierten Bilder aus unique mappings
if [ -f "$UNIQUE_MAPPINGS" ]; then
    echo_info "Verwende kuratierte Bildzuordnungen..."
    
    php -r '
    $unique = json_decode(file_get_contents("'$UNIQUE_MAPPINGS'"), true);
    
    $copied = 0;
    $target_dir = "'$CATEGORIZED_DIR'";
    $source_dir = "'$ORGANIZED_IMAGES_DIR'";
    $processed = array();
    
    // Hauptkategorien
    if (isset($unique["main_categories"])) {
        foreach ($unique["main_categories"] as $category => $image) {
            if (in_array($image, $processed)) continue;
            
            $source = $source_dir . "/" . $image;
            if (file_exists($source)) {
                $target = $target_dir . "/" . $category . "_" . $image;
                copy($source, $target);
                echo "✅ Hauptkategorie " . $category . ": " . $image . "\n";
                $copied++;
                $processed[] = $image;
            }
        }
    }
    
    // Unterkategorien
    if (isset($unique["subcategories"])) {
        foreach ($unique["subcategories"] as $main => $subs) {
            foreach ($subs as $sub => $image) {
                if (in_array($image, $processed)) continue;
                
                $source = $source_dir . "/" . $image;
                if (file_exists($source)) {
                    $target = $target_dir . "/" . $sub . "_" . $image;
                    copy($source, $target);
                    echo "✅ Unterkategorie " . $sub . ": " . $image . "\n";
                    $copied++;
                    $processed[] = $image;
                }
            }
        }
    }
    
    echo "\n=== " . $copied . " eindeutige Bilder organisiert ===\n";
    '
else
    echo_error "Unique Mappings Datei nicht gefunden!"
    exit 1
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

# Kopiere kategorisierte Bilder
docker cp "$CATEGORIZED_DIR" "$WORDPRESS_CONTAINER:/tmp/" 2>/dev/null
echo_success "Kategorisierte Bilder kopiert"

# Kopiere Content falls vorhanden
if [ -d "/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex" ]; then
    docker cp /Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex "$WORDPRESS_CONTAINER:/var/www/html/riman-content"
    echo_success "Content kopiert"
fi

echo ""
echo "3. Führe WordPress-Setup aus..."
echo "   Erstelle Kategorien und Seiten..."

docker exec "$WORDPRESS_CONTAINER" php /var/www/html/setup-complete-riman.php

echo ""
echo "4. Importiere kategorisierte Bilder..."
echo "   Weise Bilder den Seiten zu..."

docker exec "$WORDPRESS_CONTAINER" php /var/www/html/import-categorized-images.php

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
echo \"📊 Seiten mit Featured Images: \" . \$with_image . \"\n\";
echo \"📊 Seiten ohne Featured Images: \" . \$without_image . \"\n\";
'
"

echo_success "✅ Setup mit eindeutigen, organisierten Bildern komplett!"