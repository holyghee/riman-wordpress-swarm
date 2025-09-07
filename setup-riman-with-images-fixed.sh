#!/bin/bash

# Riman WordPress Setup mit korrektem Featured Image Support
# Vollständiges Setup inklusive kategorisierter Midjourney-Bilder

echo "=== Riman WordPress Setup mit Featured Images Fix ==="
echo ""

# Farben für bessere Ausgabe
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Prüfe ob Docker läuft
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Docker ist nicht gestartet. Bitte Docker starten.${NC}"
    exit 1
fi

# Prüfe ob der Container läuft
if ! docker ps | grep -q riman-wordpress-swarm-wordpress-1; then
    echo -e "${YELLOW}⚠️  Container nicht gefunden. Starte Docker Swarm...${NC}"
    docker swarm init 2>/dev/null || true
    docker stack deploy -c docker-compose.yml riman-wordpress-swarm
    
    echo "Warte auf WordPress-Start (30 Sekunden)..."
    sleep 30
    
    # Warte zusätzlich bis WordPress wirklich bereit ist
    echo "Prüfe WordPress-Bereitschaft..."
    for i in {1..30}; do
        if docker exec riman-wordpress-swarm-wordpress-1 wp-cli.phar --allow-root core is-installed 2>/dev/null; then
            echo -e "${GREEN}✅ WordPress ist bereit!${NC}"
            break
        else
            echo -n "."
            sleep 2
        fi
    done
fi

echo ""
echo "1. Organisiere Midjourney-Bilder..."
if [ -f organize-midjourney-images.sh ]; then
    bash organize-midjourney-images.sh
    echo -e "${GREEN}✅ Bilder organisiert${NC}"
else
    echo -e "${YELLOW}⚠️  organize-midjourney-images.sh nicht gefunden${NC}"
fi

echo ""
echo "2. Kopiere Dateien in Container..."

# Liste der zu kopierenden PHP-Scripts
PHP_SCRIPTS=(
    "setup-complete-riman.php"
    "import-categorized-images.php"
    "fix-featured-images-properly.php"
)

# Kopiere alle PHP Scripts
for script in "${PHP_SCRIPTS[@]}"; do
    if [ -f "$script" ]; then
        docker cp "$script" riman-wordpress-swarm-wordpress-1:/var/www/html/
        echo -e "${GREEN}✅ $script kopiert${NC}"
    else
        echo -e "${YELLOW}⚠️  $script nicht gefunden${NC}"
    fi
done

# Kopiere organisierte Bilder
if [ -d categorized-images ]; then
    docker cp categorized-images riman-wordpress-swarm-wordpress-1:/tmp/
    echo -e "${GREEN}✅ Kategorisierte Bilder kopiert${NC}"
else
    echo -e "${YELLOW}⚠️  Kategorisierte Bilder nicht gefunden${NC}"
fi

echo ""
echo "3. Führe WordPress-Setup aus..."
echo "   Erstelle Kategorien und Seiten..."
docker exec riman-wordpress-swarm-wordpress-1 php /var/www/html/setup-complete-riman.php

echo ""
echo "4. Importiere kategorisierte Bilder..."
echo "   Weise Bilder den Kategorien zu..."
docker exec riman-wordpress-swarm-wordpress-1 php /var/www/html/import-categorized-images.php

echo ""
echo "5. Fixe Featured Images für Seiten..."
echo "   Stelle sicher, dass alle Seiten ihre Featured Images haben..."
docker exec riman-wordpress-swarm-wordpress-1 php /var/www/html/fix-featured-images-properly.php

echo ""
echo -e "${GREEN}=== Setup abgeschlossen ===${NC}"
echo ""
echo "WordPress ist verfügbar unter:"
echo -e "${GREEN}👉 http://127.0.0.1:8801${NC}"
echo ""
echo "Admin-Login:"
echo "👤 Benutzer: admin"
echo "🔑 Passwort: admin_password_123"
echo ""

# Prüfe ob alle Seiten Featured Images haben
echo "Prüfe Featured Images Status..."
docker exec riman-wordpress-swarm-wordpress-1 bash -c "
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

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Alle Kategorien und Seiten sollten jetzt passende Featured Images haben!${NC}"
else
    echo -e "${YELLOW}⚠️  Bitte prüfe die Featured Images manuell im WordPress Admin${NC}"
fi
