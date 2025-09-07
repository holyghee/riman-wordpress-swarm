#!/bin/bash

# EINFACHES WordPress Setup - KEINE Mappings, KEINE Kompatibilität!

echo "=== EINFACHES RIMAN Setup ==="
echo ""

# Configuration
WORDPRESS_CONTAINER="riman-wordpress-swarm-wordpress-1"
IMAGES_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images"

# Docker prüfen
if ! docker ps | grep -q "$WORDPRESS_CONTAINER"; then
    echo "Starting Docker Stack..."
    docker swarm init 2>/dev/null || true
    docker stack deploy -c docker-compose.yml riman-wordpress-swarm
    sleep 30
fi

echo "1. Erstelle einfache WordPress-Struktur..."

# Erstelle einfaches Setup-Script
cat > /tmp/setup-simple-wp.php << 'EOF'
<?php
require_once('/var/www/html/wp-load.php');

echo "=== Einfaches WordPress Setup ===\n";

// Standard-Seiten erstellen
$pages = array(
    'rueckbau' => 'Rückbau',
    'altlasten' => 'Altlasten', 
    'schadstoffe' => 'Schadstoffe',
    'sicherheit' => 'Sicherheit',
    'beratung' => 'Beratung',
    'asbestsanierung' => 'Asbestsanierung',
    'kontakt' => 'Kontakt',
    'impressum' => 'Impressum'
);

$created = 0;
foreach ($pages as $slug => $title) {
    if (!get_page_by_path($slug)) {
        $page_data = array(
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => "Inhalt für $title wird hier eingefügt.",
            'post_status' => 'publish',
            'post_type' => 'page'
        );
        
        $page_id = wp_insert_post($page_data);
        if ($page_id && !is_wp_error($page_id)) {
            echo "✅ Seite erstellt: $slug → $title\n";
            $created++;
        }
    } else {
        echo "⏭️  Existiert bereits: $slug\n";
    }
}

echo "\n✅ Setup komplett - $created neue Seiten erstellt\n";
EOF

docker cp /tmp/setup-simple-wp.php "$WORDPRESS_CONTAINER:/var/www/html/"
docker cp simple-image-import.php "$WORDPRESS_CONTAINER:/var/www/html/"

# Kopiere Bilder
docker cp "$IMAGES_DIR" "$WORDPRESS_CONTAINER:/tmp/"

echo "2. Führe WordPress-Setup aus..."
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/setup-simple-wp.php

echo "3. Importiere Bilder mit Wildcards..."
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/simple-image-import.php

echo ""
echo "✅ EINFACHES Setup abgeschlossen!"
echo ""
echo "WordPress verfügbar: http://127.0.0.1:8801"
echo "Admin Login: admin / admin_password_123"
echo ""

# Finale Statistik
docker exec "$WORDPRESS_CONTAINER" bash -c "
php -r '
require_once(\"/var/www/html/wp-load.php\");
\$pages = get_posts(array(\"post_type\" => \"page\", \"numberposts\" => -1));
\$with = 0; \$without = 0;
foreach(\$pages as \$page) {
    if(get_post_thumbnail_id(\$page->ID)) \$with++; else \$without++;
}
echo \"📊 Seiten mit Bildern: \$with\\n\";
echo \"📊 Seiten ohne Bilder: \$without\\n\";
'
"