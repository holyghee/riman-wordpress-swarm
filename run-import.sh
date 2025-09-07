#!/bin/bash

# RIMAN WordPress Import Script - Vollautomatischer Content Import
# Importiert 121 Content-Image-SEO Zuordnungen in WordPress

echo "=== RIMAN WordPress Import mit Semantic Image Matching ==="
echo ""

# Farben für bessere Ausgabe
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
WORDPRESS_CONTAINER="riman-wordpress-swarm-wordpress-1"
SCRIPT_PATH="/var/www/html/wordpress-import-script.php"
HOST_SCRIPT_PATH="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-import-script.php"

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
    
    # Warte zusätzlich bis WordPress wirklich bereit ist
    echo "Prüfe WordPress-Bereitschaft..."
    for i in {1..30}; do
        if docker exec $WORDPRESS_CONTAINER wp-cli.phar --allow-root core is-installed 2>/dev/null; then
            echo_success "WordPress ist bereit!"
            break
        else
            echo -n "."
            sleep 2
        fi
    done
fi

echo ""
echo "2. Kopiere Dateien in Container..."

# Kopiere Import-Script
echo_info "Kopiere Import-Script in WordPress Container..."
docker cp "$HOST_SCRIPT_PATH" "$WORDPRESS_CONTAINER:$SCRIPT_PATH"

if [ $? -eq 0 ]; then
    echo_success "Import-Script kopiert"
else
    echo_error "Fehler beim Kopieren des Scripts"
    exit 1
fi

# Kopiere Mapping-Datei
echo_info "Kopiere Content-Image-SEO Mapping..."
docker cp "/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/content-image-seo-mapping-enhanced.json" "$WORDPRESS_CONTAINER:/tmp/content-image-seo-mapping-enhanced.json"

if [ $? -eq 0 ]; then
    echo_success "Mapping-Datei kopiert"
else
    echo_error "Fehler beim Kopieren der Mapping-Datei"
    exit 1
fi

# Kopiere Bilder
if [ -d "/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images" ]; then
    echo_info "Kopiere Midjourney-Bilder..."
    docker cp "/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images" "$WORDPRESS_CONTAINER:/tmp/"
    echo_success "Bilder kopiert"
else
    echo_warning "Bilder-Verzeichnis nicht gefunden"
fi

# Kopiere Content-Dateien
if [ -d "/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content" ]; then
    echo_info "Kopiere Content-Dateien für enhanced import..."
    docker cp "/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content" "$WORDPRESS_CONTAINER:/tmp/"
    echo_success "Content-Dateien kopiert"
else
    echo_warning "Content-Verzeichnis nicht gefunden"
fi

# Kopiere WordPress unique mappings
echo_info "Kopiere WordPress unique image mappings..."
docker cp "/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-unique-image-mappings.json" "$WORDPRESS_CONTAINER:/tmp/"
echo_success "WordPress mappings kopiert"

# Kopiere organisierte Bilder
if [ -d "/Users/holgerbrandt/dev/claude-code/projects/riman-content/image-server" ]; then
    echo_info "Kopiere organisierte Bilder..."
    docker cp "/Users/holgerbrandt/dev/claude-code/projects/riman-content/image-server" "$WORDPRESS_CONTAINER:/tmp/organized-images"
    echo_success "Organisierte Bilder kopiert"
else
    echo_warning "Organisierte Bilder-Verzeichnis nicht gefunden"
fi

# Function to run import batch
run_batch() {
    local batch_num=$1
    echo_info "Running import batch $batch_num..."
    
    docker exec -it "$WORDPRESS_CONTAINER" php "$SCRIPT_PATH" "batch=$batch_num"
    
    if [ $? -eq 0 ]; then
        echo_success "Batch $batch_num completed"
        return 0
    else
        echo_error "Batch $batch_num failed"
        return 1
    fi
}

# Function to run rollback
run_rollback() {
    local batch_num=$1
    echo_warning "Running rollback for batch $batch_num..."
    
    docker exec -it "$WORDPRESS_CONTAINER" php "$SCRIPT_PATH" "batch=$batch_num" rollback
    
    if [ $? -eq 0 ]; then
        echo_success "Rollback for batch $batch_num completed"
    else
        echo_error "Rollback for batch $batch_num failed"
    fi
}

# Function to check WordPress status
check_wordpress() {
    echo_info "Checking WordPress status..."
    
    # Test WordPress connection
    local wp_status=$(docker exec "$WORDPRESS_CONTAINER" php -r "
        require_once '/var/www/html/wp-config.php';
        require_once '/var/www/html/wp-load.php';
        
        if (function_exists('wp_get_current_user')) {
            echo 'Connected';
        } else {
            echo 'Failed';
        }
    ")
    
    if [ "$wp_status" = "Connected" ]; then
        echo_success "WordPress is accessible"
        
        # Get current stats
        local stats=$(docker exec "$WORDPRESS_CONTAINER" php -r "
            require_once '/var/www/html/wp-config.php';
            require_once '/var/www/html/wp-load.php';
            
            echo 'Posts: ' . wp_count_posts()->publish . PHP_EOL;
            echo 'Pages: ' . wp_count_posts('page')->publish . PHP_EOL;
            echo 'Attachments: ' . wp_count_posts('attachment')->inherit . PHP_EOL;
            echo 'Categories: ' . wp_count_terms('category') . PHP_EOL;
        ")
        
        echo_info "Current WordPress stats:"
        echo "$stats" | sed 's/^/  /'
        
    else
        echo_error "Cannot connect to WordPress"
        return 1
    fi
}

# Function to preview mappings
preview_mappings() {
    echo_info "Preview of mappings to be imported:"
    
    local preview=$(docker exec "$WORDPRESS_CONTAINER" php -r "
        \$mapping_file = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/content-image-seo-mapping-enhanced.json';
        if (file_exists(\$mapping_file)) {
            \$data = json_decode(file_get_contents(\$mapping_file), true);
            if (\$data && isset(\$data['mappings'])) {
                echo 'Total mappings: ' . count(\$data['mappings']) . PHP_EOL;
                echo 'Successful mappings: ' . \$data['statistics']['content_analysis']['successfully_mapped'] . PHP_EOL;
                echo 'Coverage: ' . \$data['statistics']['content_analysis']['mapping_coverage_percent'] . '%' . PHP_EOL;
                echo 'Average confidence: ' . round(\$data['statistics']['quality_metrics']['average_confidence'], 3) . PHP_EOL;
                echo PHP_EOL . 'High confidence examples:' . PHP_EOL;
                
                \$high_conf = array_filter(\$data['mappings'], function(\$m) { return \$m['confidence_score'] >= 0.8; });
                \$count = 0;
                foreach (\$high_conf as \$mapping) {
                    if (\$count >= 3) break;
                    echo '  ' . \$mapping['content_title'] . ' (confidence: ' . \$mapping['confidence_score'] . ')' . PHP_EOL;
                    \$count++;
                }
            } else {
                echo 'Invalid mapping file format';
            }
        } else {
            echo 'Mapping file not found';
        }
    ")
    
    echo "$preview" | sed 's/^/  /'
}

echo ""
echo "1. Führe WordPress-Setup aus..."
echo "   Importiere 121 Content-Image-SEO Zuordnungen..."
docker exec $WORDPRESS_CONTAINER php $SCRIPT_PATH

echo ""
echo_success "=== Import abgeschlossen ==="
echo ""
echo "WordPress ist verfügbar unter:"
echo_success "👉 http://127.0.0.1:8801"
echo ""
echo "Admin-Login:"
echo "👤 Benutzer: admin"
echo "🔑 Passwort: admin_password_123"
echo ""

# Prüfe Ergebnisse
echo "Prüfe Import-Ergebnisse..."
docker exec $WORDPRESS_CONTAINER bash -c "
php -r '
require_once("/var/www/html/wp-load.php");
\$pages = get_posts(array("post_type" => "page", "numberposts" => -1));
\$with_image = 0;
\$without_image = 0;
foreach(\$pages as \$page) {
    if(get_post_thumbnail_id(\$page->ID)) {
        \$with_image++;
    } else {
        \$without_image++;
    }
}
echo "📊 Seiten mit Featured Images: " . \$with_image . "\n";
echo "📊 Seiten ohne Featured Images: " . \$without_image . "\n";
'
"

if [ $? -eq 0 ]; then
    echo_success "✅ Alle 121 Content-Mappings sollten jetzt importiert sein!"
else
    echo_warning "⚠️  Bitte prüfe die Ergebnisse manuell im WordPress Admin"
fi