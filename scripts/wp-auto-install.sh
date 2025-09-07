#!/bin/bash
# Automatisierte WordPress-Installation für All-Inkl

# Load environment
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/../.env.local"

if [ -f "$ENV_FILE" ]; then
    source "$ENV_FILE"
else
    echo "❌ .env.local nicht gefunden!"
    exit 1
fi

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🚀 WordPress Auto-Installer für All-Inkl${NC}"
echo "=========================================="
echo ""

# Function to run SSH commands
run_ssh() {
    sshpass -p "${KAS_PASSWORD}" ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR ssh-w0181e1b@w0181e1b.kasserver.com "$@"
}

# Function for FTP upload
ftp_upload() {
    local local_file="$1"
    local remote_path="$2"
    curl -T "$local_file" \
         -u "${KAS_USERNAME}:${KAS_PASSWORD}" \
         "ftp://${KAS_HOST}${remote_path}" \
         --ftp-create-dirs \
         --silent
}

case "$1" in
    prepare)
        SITE_DIR="$2"
        if [ -z "$SITE_DIR" ]; then
            echo "Usage: $0 prepare <site-directory>"
            echo "Beispiel: $0 prepare my-new-site.de"
            exit 1
        fi
        
        echo -e "${YELLOW}📁 Bereite Installation vor: $SITE_DIR${NC}"
        echo ""
        
        # Create directory
        echo "1. Erstelle Verzeichnis..."
        run_ssh "mkdir -p /www/htdocs/w0181e1b/${SITE_DIR}"
        
        # Download WordPress
        echo "2. Lade WordPress herunter..."
        run_ssh "cd /www/htdocs/w0181e1b/${SITE_DIR} && wget -q https://wordpress.org/latest.tar.gz && tar -xzf latest.tar.gz --strip-components=1 && rm latest.tar.gz && echo '✅ WordPress heruntergeladen'"
        
        # Set permissions
        echo "3. Setze Berechtigungen..."
        run_ssh "cd /www/htdocs/w0181e1b/${SITE_DIR} && chmod 755 . && chmod 644 .htaccess 2>/dev/null || true"
        
        echo ""
        echo -e "${GREEN}✅ Verzeichnis vorbereitet!${NC}"
        echo ""
        echo "Nächste Schritte:"
        echo "1. Erstelle eine Datenbank im KAS"
        echo "2. Führe aus: $0 config $SITE_DIR <db-name> <db-user> <db-pass>"
        ;;
    
    config)
        SITE_DIR="$2"
        DB_NAME="$3"
        DB_USER="$4"
        DB_PASS="$5"
        
        if [ -z "$SITE_DIR" ] || [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
            echo "Usage: $0 config <site-dir> <db-name> <db-user> <db-pass>"
            exit 1
        fi
        
        echo -e "${YELLOW}⚙️  Konfiguriere WordPress für: $SITE_DIR${NC}"
        echo ""
        
        # Generate wp-config.php
        echo "Erstelle wp-config.php..."
        
        cat > /tmp/wp-config-temp.php << EOF
<?php
/**
 * WordPress Konfiguration
 * Automatisch generiert für $SITE_DIR
 */

// Datenbank-Einstellungen
define( 'DB_NAME', '$DB_NAME' );
define( 'DB_USER', '$DB_USER' );
define( 'DB_PASSWORD', '$DB_PASS' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// Sicherheitsschlüssel
define( 'AUTH_KEY',         '$(openssl rand -base64 32)' );
define( 'SECURE_AUTH_KEY',  '$(openssl rand -base64 32)' );
define( 'LOGGED_IN_KEY',    '$(openssl rand -base64 32)' );
define( 'NONCE_KEY',        '$(openssl rand -base64 32)' );
define( 'AUTH_SALT',        '$(openssl rand -base64 32)' );
define( 'SECURE_AUTH_SALT', '$(openssl rand -base64 32)' );
define( 'LOGGED_IN_SALT',   '$(openssl rand -base64 32)' );
define( 'NONCE_SALT',       '$(openssl rand -base64 32)' );

// WordPress Tabellen-Prefix
\$table_prefix = 'wp_';

// Debug-Modus
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );

// Speicher-Limit
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );

// Automatische Updates
define( 'WP_AUTO_UPDATE_CORE', 'minor' );

// SSL für Admin
define( 'FORCE_SSL_ADMIN', true );

// Dateisystem-Methode
define( 'FS_METHOD', 'direct' );

/* Das war's, Schluss mit dem Bearbeiten! Viel Spaß. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
EOF
        
        # Upload wp-config.php
        echo "Lade wp-config.php hoch..."
        ftp_upload "/tmp/wp-config-temp.php" "/www/htdocs/w0181e1b/${SITE_DIR}/wp-config.php"
        rm /tmp/wp-config-temp.php
        
        echo ""
        echo -e "${GREEN}✅ WordPress konfiguriert!${NC}"
        echo ""
        echo "WordPress-Installation abschließen:"
        echo "1. Öffne: http://${SITE_DIR}/wp-admin/install.php"
        echo "2. Oder nutze: $0 install $SITE_DIR"
        ;;
    
    install)
        SITE_DIR="$2"
        SITE_TITLE="${3:-WordPress Site}"
        ADMIN_USER="${4:-admin}"
        ADMIN_PASS="${5:-$(openssl rand -base64 12)}"
        ADMIN_EMAIL="${6:-admin@${SITE_DIR}}"
        
        if [ -z "$SITE_DIR" ]; then
            echo "Usage: $0 install <site-dir> [title] [admin-user] [admin-pass] [admin-email]"
            exit 1
        fi
        
        echo -e "${YELLOW}🔧 Installiere WordPress für: $SITE_DIR${NC}"
        echo ""
        echo "Titel: $SITE_TITLE"
        echo "Admin: $ADMIN_USER"
        echo "Email: $ADMIN_EMAIL"
        echo "Pass:  $ADMIN_PASS"
        echo ""
        
        # Run WordPress installation
        run_ssh "cd /www/htdocs/w0181e1b/${SITE_DIR} && php -r '
            define(\"WP_INSTALLING\", true);
            require_once \"wp-load.php\";
            require_once \"wp-admin/includes/upgrade.php\";
            
            // Install WordPress
            wp_install(
                \"$SITE_TITLE\",
                \"$ADMIN_USER\",
                \"$ADMIN_EMAIL\",
                true,  // public
                \"\",   // deprecated
                \"$ADMIN_PASS\",
                \"de_DE\"
            );
            
            // Update options
            update_option(\"blogdescription\", \"Eine weitere WordPress-Website\");
            update_option(\"timezone_string\", \"Europe/Berlin\");
            update_option(\"date_format\", \"d.m.Y\");
            update_option(\"time_format\", \"H:i\");
            update_option(\"start_of_week\", 1);
            
            echo \"✅ WordPress installiert!\\n\";
        ' 2>&1"
        
        echo ""
        echo -e "${GREEN}✅ Installation abgeschlossen!${NC}"
        echo ""
        echo "Zugangsdaten:"
        echo "URL:      http://${SITE_DIR}/wp-admin/"
        echo "Benutzer: $ADMIN_USER"
        echo "Passwort: $ADMIN_PASS"
        echo ""
        echo "WICHTIG: Speichere diese Zugangsdaten!"
        ;;
    
    quick)
        SITE_DIR="$2"
        if [ -z "$SITE_DIR" ]; then
            echo "Usage: $0 quick <site-directory>"
            echo ""
            echo "⚠️  VORAUSSETZUNG: Datenbank muss im KAS angelegt sein!"
            echo ""
            echo "Beispiel: $0 quick test.example.de"
            exit 1
        fi
        
        echo -e "${BLUE}⚡ Schnell-Installation für: $SITE_DIR${NC}"
        echo ""
        echo -e "${RED}WICHTIG: Stelle sicher, dass:${NC}"
        echo "1. Eine Datenbank im KAS angelegt ist"
        echo "2. Du die Zugangsdaten kennst"
        echo ""
        read -p "Datenbank-Name: " DB_NAME
        read -p "Datenbank-Benutzer: " DB_USER
        read -sp "Datenbank-Passwort: " DB_PASS
        echo ""
        read -p "Site-Titel: " SITE_TITLE
        read -p "Admin-Benutzer [admin]: " ADMIN_USER
        ADMIN_USER="${ADMIN_USER:-admin}"
        read -p "Admin-Email: " ADMIN_EMAIL
        
        ADMIN_PASS=$(openssl rand -base64 12)
        
        echo ""
        echo "Starte Installation..."
        echo ""
        
        # Run all steps
        $0 prepare "$SITE_DIR"
        $0 config "$SITE_DIR" "$DB_NAME" "$DB_USER" "$DB_PASS"
        $0 install "$SITE_DIR" "$SITE_TITLE" "$ADMIN_USER" "$ADMIN_PASS" "$ADMIN_EMAIL"
        ;;
    
    *)
        echo "WordPress Auto-Installation für All-Inkl"
        echo ""
        echo "Usage: $0 <command> [options]"
        echo ""
        echo "Befehle:"
        echo "  prepare <dir>     - WordPress-Dateien vorbereiten"
        echo "  config <dir> ...  - wp-config.php erstellen"
        echo "  install <dir> ... - WordPress installieren"
        echo "  quick <dir>       - Komplette Installation (interaktiv)"
        echo ""
        echo "Workflow:"
        echo "1. Datenbank im KAS anlegen"
        echo "2. $0 prepare my-site.de"
        echo "3. $0 config my-site.de db_name db_user db_pass"
        echo "4. $0 install my-site.de 'Site Title' admin pass email"
        echo ""
        echo "Oder einfach:"
        echo "  $0 quick my-site.de"
        echo ""
        echo -e "${YELLOW}⚠️  Hinweis:${NC}"
        echo "Datenbanken müssen über KAS angelegt werden:"
        echo "https://kas.all-inkl.com → Datenbanken → Neue Datenbank"
        echo ""
        ;;
esac