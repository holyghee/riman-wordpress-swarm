#!/bin/bash
# WordPress Management via FTP for All-Inkl

source "$(dirname "$0")/../.env.local" 2>/dev/null

if [ -z "$KAS_PASSWORD" ]; then
    echo "❌ Bitte .env.local konfigurieren"
    exit 1
fi

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# FTP base command
FTP_CMD="curl -u ${KAS_USERNAME}:${KAS_PASSWORD}"
FTP_BASE="ftp://${KAS_HOST}"

echo -e "${BLUE}🔧 WordPress FTP Manager${NC}"
echo "========================="
echo ""

case "$1" in
    wp-config-download)
        echo -e "${YELLOW}📥 Downloading wp-config.php...${NC}"
        $FTP_CMD "${FTP_BASE}/www/htdocs/w0181e1b/wp-config.php" \
                 -o "wp-config-backup.php"
        echo -e "${GREEN}✅ Downloaded to wp-config-backup.php${NC}"
        ;;
    
    htaccess-download)
        echo -e "${YELLOW}📥 Downloading .htaccess...${NC}"
        $FTP_CMD "${FTP_BASE}/www/htdocs/w0181e1b/.htaccess" \
                 -o "htaccess-backup.txt"
        echo -e "${GREEN}✅ Downloaded to htaccess-backup.txt${NC}"
        ;;
    
    theme-list)
        echo -e "${BLUE}🎨 Installed Themes:${NC}"
        $FTP_CMD "${FTP_BASE}/www/htdocs/w0181e1b/wp-content/themes/" \
                 --list-only | grep "^d" | awk '{print "  - "$NF}'
        ;;
    
    plugin-list)
        echo -e "${BLUE}🔌 Installed Plugins:${NC}"
        $FTP_CMD "${FTP_BASE}/www/htdocs/w0181e1b/wp-content/plugins/" \
                 --list-only | grep "^d" | awk '{print "  - "$NF}'
        ;;
    
    uploads-check)
        echo -e "${BLUE}📸 Recent Uploads:${NC}"
        CURRENT_YEAR=$(date +%Y)
        CURRENT_MONTH=$(date +%m)
        echo "Checking uploads/${CURRENT_YEAR}/${CURRENT_MONTH}/"
        $FTP_CMD "${FTP_BASE}/www/htdocs/w0181e1b/wp-content/uploads/${CURRENT_YEAR}/${CURRENT_MONTH}/" \
                 --list-only 2>/dev/null | head -10 || echo "No uploads this month"
        ;;
    
    maintenance-on)
        echo -e "${YELLOW}🔧 Enabling maintenance mode...${NC}"
        echo '<?php $upgrading = time(); ?>' > .maintenance
        $FTP_CMD -T .maintenance \
                 "${FTP_BASE}/www/htdocs/w0181e1b/.maintenance" \
                 --ftp-create-dirs
        rm .maintenance
        echo -e "${GREEN}✅ Maintenance mode enabled${NC}"
        ;;
    
    maintenance-off)
        echo -e "${YELLOW}🔧 Disabling maintenance mode...${NC}"
        $FTP_CMD -Q "DELE /www/htdocs/w0181e1b/.maintenance" \
                 "${FTP_BASE}/"
        echo -e "${GREEN}✅ Maintenance mode disabled${NC}"
        ;;
    
    debug-enable)
        echo -e "${YELLOW}🐛 Creating debug configuration...${NC}"
        cat > wp-debug.php << 'EOF'
<?php
// Debug settings - add to wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
define( 'SAVEQUERIES', true );
EOF
        echo -e "${GREEN}✅ Debug config created in wp-debug.php${NC}"
        echo "Add these lines to wp-config.php manually"
        ;;
    
    log-download)
        echo -e "${YELLOW}📋 Downloading debug.log...${NC}"
        $FTP_CMD "${FTP_BASE}/www/htdocs/w0181e1b/wp-content/debug.log" \
                 -o "debug-$(date +%Y%m%d-%H%M%S).log" 2>/dev/null
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✅ Debug log downloaded${NC}"
        else
            echo -e "${RED}❌ No debug log found${NC}"
        fi
        ;;
    
    deploy-file)
        if [ -z "$2" ] || [ -z "$3" ]; then
            echo "Usage: $0 deploy-file <local-file> <remote-path>"
            exit 1
        fi
        echo -e "${YELLOW}⬆️  Uploading $2...${NC}"
        $FTP_CMD -T "$2" \
                 "${FTP_BASE}$3" \
                 --ftp-create-dirs
        echo -e "${GREEN}✅ File deployed${NC}"
        ;;
    
    backup-essential)
        echo -e "${YELLOW}💾 Backing up essential files...${NC}"
        BACKUP_DIR="backup-$(date +%Y%m%d-%H%M%S)"
        mkdir -p "$BACKUP_DIR"
        
        # Download essential files
        FILES=(
            "wp-config.php"
            ".htaccess"
            "robots.txt"
        )
        
        for file in "${FILES[@]}"; do
            echo "  Downloading $file..."
            $FTP_CMD "${FTP_BASE}/www/htdocs/w0181e1b/$file" \
                     -o "$BACKUP_DIR/$file" 2>/dev/null || echo "    $file not found"
        done
        
        echo -e "${GREEN}✅ Backup saved to $BACKUP_DIR${NC}"
        ;;
    
    info)
        echo -e "${BLUE}WordPress Installation Info:${NC}"
        echo "================================"
        echo -e "${YELLOW}Site URLs found in installation:${NC}"
        $FTP_CMD "${FTP_BASE}/www/htdocs/w0181e1b/" \
                 --list-only | head -20
        echo ""
        echo -e "${YELLOW}Main directories:${NC}"
        for dir in "urangstentkoppeln.de" "angstfrei-info.de" "algarveda.com" "ecomwy.com"; do
            if $FTP_CMD "${FTP_BASE}/www/htdocs/w0181e1b/$dir/" --list-only --max-time 2 2>/dev/null | head -1 > /dev/null; then
                echo -e "  ${GREEN}✓${NC} $dir"
            fi
        done
        ;;
    
    *)
        echo "WordPress FTP Management Commands"
        echo ""
        echo "Usage: $0 <command>"
        echo ""
        echo "File Management:"
        echo "  wp-config-download  - Download wp-config.php"
        echo "  htaccess-download   - Download .htaccess"
        echo "  deploy-file         - Upload single file"
        echo "  backup-essential    - Backup essential files"
        echo ""
        echo "Information:"
        echo "  theme-list          - List installed themes"
        echo "  plugin-list         - List installed plugins"
        echo "  uploads-check       - Check recent uploads"
        echo "  info                - Show installation info"
        echo ""
        echo "Maintenance:"
        echo "  maintenance-on      - Enable maintenance mode"
        echo "  maintenance-off     - Disable maintenance mode"
        echo "  debug-enable        - Create debug configuration"
        echo "  log-download        - Download debug.log"
        echo ""
        ;;
esac