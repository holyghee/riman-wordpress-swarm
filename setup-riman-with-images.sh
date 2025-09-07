#!/bin/bash

# Riman WordPress Setup mit Bildern
# Vollständiges Setup inklusive kategorisierter Midjourney-Bilder

echo "=== Riman WordPress Setup mit Bildern ==="
echo ""

# Prüfe ob Docker läuft
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker ist nicht gestartet. Bitte Docker starten."
    exit 1
fi

# Prüfe ob der Container läuft
if ! docker ps | grep -q riman-wordpress-swarm-wordpress-1; then
    echo "⚠️  Container nicht gefunden. Starte Docker Swarm..."
    docker swarm init 2>/dev/null || true
    docker stack deploy -c docker-compose.yml riman-wordpress-swarm
    
    echo "Warte auf WordPress-Start..."
    sleep 30
fi

# Skip Midjourney organization - we use image-server directly
# echo "1. Organisiere Midjourney-Bilder..."
# bash organize-midjourney-images.sh

echo ""
echo "2. Kopiere Dateien in Container..."

# Kopiere Setup-Script
docker cp setup-complete-riman.php riman-wordpress-swarm-wordpress-1:/var/www/html/

# Kopiere Import-Script
docker cp import-categorized-images.php riman-wordpress-swarm-wordpress-1:/var/www/html/

# Kopiere image-server Bilder direkt - RICHTIGER PFAD!
IMAGE_SERVER_PATH="/Users/holgerbrandt/dev/claude-code/projects/riman-content/image-server"
if [ -d "$IMAGE_SERVER_PATH" ]; then
    echo "📁 Kopiere image-server Bilder aus dem richtigen Verzeichnis..."
    docker exec riman-wordpress-swarm-wordpress-1 mkdir -p /var/www/html/wp-content/uploads/image-server
    docker cp "$IMAGE_SERVER_PATH/." riman-wordpress-swarm-wordpress-1:/var/www/html/wp-content/uploads/image-server/
    echo "✅ Image-server Bilder kopiert aus: $IMAGE_SERVER_PATH"
else
    echo "❌ Image-server Verzeichnis nicht gefunden: $IMAGE_SERVER_PATH"
    # Fallback zu kategorisierten Bildern
    if [ -d categorized-images ]; then
        docker cp categorized-images riman-wordpress-swarm-wordpress-1:/tmp/
        echo "✅ Kategorisierte Bilder kopiert (Fallback)"
    else
        echo "⚠️  Keine Bildquellen gefunden"
    fi
fi

echo ""
echo "3. Führe WordPress-Setup aus..."
docker exec riman-wordpress-swarm-wordpress-1 php /var/www/html/setup-complete-riman.php

# Skip Step 4 - we don't need random Midjourney images since we use semantic assignment
# echo ""
# echo "4. Importiere kategorisierte Bilder..."
# docker exec riman-wordpress-swarm-wordpress-1 php /var/www/html/import-categorized-images.php

# Kopiere Assignment Script
# docker cp assign-category-images-to-pages.php riman-wordpress-swarm-wordpress-1:/var/www/html/

# Kopiere Semantic Assignment Script
docker cp assign-semantic-images.php riman-wordpress-swarm-wordpress-1:/var/www/html/

echo ""
echo "4. Weise semantisch korrekte Images zu (aus swarm-config.yaml)..."
docker exec riman-wordpress-swarm-wordpress-1 php /var/www/html/assign-semantic-images.php

# Kopiere Sync Script
docker cp sync-page-featured-images.php riman-wordpress-swarm-wordpress-1:/var/www/html/

echo ""
echo "5. Synchronisiere Featured Images zu allen Seiten..."
docker exec riman-wordpress-swarm-wordpress-1 php /var/www/html/sync-page-featured-images.php

echo ""
echo "=== Setup abgeschlossen ==="
echo ""
echo "WordPress ist verfügbar unter:"
echo "👉 http://127.0.0.1:8801"
echo ""
echo "Admin-Login:"
echo "👤 Benutzer: admin"
echo "🔑 Passwort: admin_password_123"
echo ""
echo "✅ Alle Kategorien und Seiten haben jetzt passende Featured Images!"