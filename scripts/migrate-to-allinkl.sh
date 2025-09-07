#!/bin/bash
# WordPress Migration Script - Lokale Dateien zu All-Inkl

# Load environment
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/../.env.local"
PROJECT_DIR="${SCRIPT_DIR}/.."

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

echo -e "${BLUE}🚀 WordPress Migration zu All-Inkl${NC}"
echo "====================================="
echo ""

# Target directory on server
REMOTE_PATH="/www/htdocs/w0181e1b/riman-wordpress-swarm.ecomwy.com"

case "$1" in
    analyze)
        echo -e "${YELLOW}📊 Analysiere lokale Dateien...${NC}"
        echo ""
        
        # Check what we have
        echo "wp-content Struktur:"
        echo "-------------------"
        
        if [ -d "wp-content/themes" ]; then
            echo "✅ Themes gefunden:"
            ls -1 wp-content/themes/ | sed 's/^/   - /'
        fi
        
        if [ -d "wp-content/plugins" ]; then
            echo "✅ Plugins gefunden:"
            ls -1 wp-content/plugins/ | head -10 | sed 's/^/   - /'
            PLUGIN_COUNT=$(ls -1 wp-content/plugins/ | wc -l)
            echo "   ... insgesamt $PLUGIN_COUNT Plugins"
        fi
        
        if [ -d "wp-content/uploads" ]; then
            echo "✅ Uploads gefunden:"
            find wp-content/uploads -type f | wc -l | xargs echo "   Anzahl Dateien:"
            du -sh wp-content/uploads | awk '{print "   Gesamtgröße: " $1}'
        fi
        
        echo ""
        echo "PHP-Dateien im Hauptverzeichnis:"
        ls -1 *.php 2>/dev/null | head -10 | sed 's/^/   - /'
        ;;
    
    backup-remote)
        echo -e "${YELLOW}💾 Erstelle Backup der aktuellen Remote-Installation...${NC}"
        TIMESTAMP=$(date +%Y%m%d-%H%M%S)
        
        # Create backup directory locally
        mkdir -p backups
        
        # Backup remote wp-content
        echo "Downloading current wp-content..."
        sshpass -p "${KAS_PASSWORD}" ssh -o StrictHostKeyChecking=no ssh-w0181e1b@w0181e1b.kasserver.com \
            "cd ${REMOTE_PATH} && tar -czf backup-${TIMESTAMP}.tar.gz wp-content/ 2>/dev/null || echo 'Backup erstellt'"
        
        # Download backup
        sshpass -p "${KAS_PASSWORD}" scp -o StrictHostKeyChecking=no \
            ssh-w0181e1b@w0181e1b.kasserver.com:${REMOTE_PATH}/backup-${TIMESTAMP}.tar.gz \
            ./backups/ 2>/dev/null
        
        echo -e "${GREEN}✅ Backup gespeichert in backups/backup-${TIMESTAMP}.tar.gz${NC}"
        ;;
    
    migrate-themes)
        echo -e "${YELLOW}🎨 Migriere Themes...${NC}"
        
        if [ ! -d "wp-content/themes" ]; then
            echo "❌ Keine Themes gefunden!"
            exit 1
        fi
        
        # Upload each theme
        for theme in wp-content/themes/*/; do
            if [ -d "$theme" ]; then
                THEME_NAME=$(basename "$theme")
                echo "Uploading theme: $THEME_NAME"
                
                # Create tar archive
                tar -czf /tmp/${THEME_NAME}.tar.gz -C wp-content/themes/ "$THEME_NAME"
                
                # Upload via SCP
                sshpass -p "${KAS_PASSWORD}" scp -o StrictHostKeyChecking=no \
                    /tmp/${THEME_NAME}.tar.gz \
                    ssh-w0181e1b@w0181e1b.kasserver.com:${REMOTE_PATH}/
                
                # Extract on server
                sshpass -p "${KAS_PASSWORD}" ssh -o StrictHostKeyChecking=no ssh-w0181e1b@w0181e1b.kasserver.com \
                    "cd ${REMOTE_PATH} && tar -xzf ${THEME_NAME}.tar.gz -C wp-content/themes/ && rm ${THEME_NAME}.tar.gz"
                
                rm /tmp/${THEME_NAME}.tar.gz
                echo "  ✅ $THEME_NAME uploaded"
            fi
        done
        ;;
    
    migrate-plugins)
        echo -e "${YELLOW}🔌 Migriere Plugins...${NC}"
        
        if [ ! -d "wp-content/plugins" ]; then
            echo "❌ Keine Plugins gefunden!"
            exit 1
        fi
        
        # Create tar of all plugins
        echo "Erstelle Plugin-Archiv..."
        tar -czf /tmp/plugins.tar.gz -C wp-content/ plugins/
        
        # Upload
        echo "Uploade Plugins..."
        sshpass -p "${KAS_PASSWORD}" scp -o StrictHostKeyChecking=no \
            /tmp/plugins.tar.gz \
            ssh-w0181e1b@w0181e1b.kasserver.com:${REMOTE_PATH}/
        
        # Extract on server
        echo "Entpacke auf Server..."
        sshpass -p "${KAS_PASSWORD}" ssh -o StrictHostKeyChecking=no ssh-w0181e1b@w0181e1b.kasserver.com \
            "cd ${REMOTE_PATH}/wp-content && tar -xzf ../plugins.tar.gz && rm ../plugins.tar.gz"
        
        rm /tmp/plugins.tar.gz
        echo -e "${GREEN}✅ Plugins migriert${NC}"
        ;;
    
    migrate-uploads)
        echo -e "${YELLOW}📸 Migriere Uploads...${NC}"
        
        if [ ! -d "wp-content/uploads" ]; then
            echo "❌ Keine Uploads gefunden!"
            exit 1
        fi
        
        # Use rsync for uploads (more efficient)
        echo "Synchronisiere Uploads via rsync..."
        sshpass -p "${KAS_PASSWORD}" rsync -avz --progress \
            wp-content/uploads/ \
            -e "ssh -o StrictHostKeyChecking=no" \
            ssh-w0181e1b@w0181e1b.kasserver.com:${REMOTE_PATH}/wp-content/uploads/
        
        echo -e "${GREEN}✅ Uploads synchronisiert${NC}"
        ;;
    
    migrate-custom)
        echo -e "${YELLOW}📄 Migriere Custom PHP-Dateien...${NC}"
        
        # Upload PHP files from root
        for file in *.php; do
            if [ -f "$file" ]; then
                echo "Uploade: $file"
                sshpass -p "${KAS_PASSWORD}" scp -o StrictHostKeyChecking=no \
                    "$file" \
                    ssh-w0181e1b@w0181e1b.kasserver.com:${REMOTE_PATH}/
            fi
        done
        
        echo -e "${GREEN}✅ PHP-Dateien migriert${NC}"
        ;;
    
    migrate-all)
        echo -e "${BLUE}🚀 Vollständige Migration${NC}"
        echo ""
        
        # Run all migration steps
        $0 backup-remote
        echo ""
        $0 migrate-themes
        echo ""
        $0 migrate-plugins
        echo ""
        $0 migrate-uploads
        echo ""
        $0 migrate-custom
        
        echo ""
        echo -e "${GREEN}✅ Migration abgeschlossen!${NC}"
        echo ""
        echo "Nächste Schritte:"
        echo "1. Prüfe die Website: https://riman-wordpress-swarm.ecomwy.com"
        echo "2. Aktiviere Plugins im Admin-Panel"
        echo "3. Wähle das richtige Theme"
        echo "4. Aktualisiere Permalinks"
        ;;
    
    sync)
        echo -e "${YELLOW}🔄 Schnelle Synchronisation (nur geänderte Dateien)${NC}"
        
        # Sync everything with rsync
        echo "Synchronisiere wp-content..."
        sshpass -p "${KAS_PASSWORD}" rsync -avz --delete --exclude 'cache' --exclude 'debug.log' \
            wp-content/ \
            -e "ssh -o StrictHostKeyChecking=no" \
            ssh-w0181e1b@w0181e1b.kasserver.com:${REMOTE_PATH}/wp-content/
        
        echo -e "${GREEN}✅ Synchronisation abgeschlossen${NC}"
        ;;
    
    *)
        echo "WordPress Migration Tool"
        echo ""
        echo "Usage: $0 <command>"
        echo ""
        echo "Analyse:"
        echo "  analyze         - Analysiere lokale Dateien"
        echo ""
        echo "Migration:"
        echo "  backup-remote   - Backup der Remote-Installation"
        echo "  migrate-themes  - Themes hochladen"
        echo "  migrate-plugins - Plugins hochladen"
        echo "  migrate-uploads - Media-Dateien hochladen"
        echo "  migrate-custom  - PHP-Dateien hochladen"
        echo "  migrate-all     - Komplette Migration"
        echo ""
        echo "Synchronisation:"
        echo "  sync           - Schnelle Sync (nur Änderungen)"
        echo ""
        echo "Beispiel:"
        echo "  $0 analyze       # Erst analysieren"
        echo "  $0 migrate-all   # Dann alles migrieren"
        echo ""
        ;;
esac