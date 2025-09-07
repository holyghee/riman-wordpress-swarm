#!/bin/bash
# WordPress Database Manager für All-Inkl

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

echo -e "${BLUE}🗄️  WordPress Database Manager${NC}"
echo "================================"
echo ""

# Function to run SSH commands
run_ssh() {
    sshpass -p "${KAS_PASSWORD}" ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR ssh-w0181e1b@w0181e1b.kasserver.com "$@"
}

case "$1" in
    info)
        echo -e "${YELLOW}📊 Datenbank-Information:${NC}"
        run_ssh "cd /www/htdocs/w0181e1b && grep -E 'DB_NAME|DB_USER|DB_HOST|DB_PREFIX' wp-config.php"
        ;;
    
    test)
        echo -e "${YELLOW}🔍 Teste Datenbank-Verbindung...${NC}"
        run_ssh "cd /www/htdocs/w0181e1b && php -r '
            require \"wp-config.php\";
            \$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if (\$conn) {
                echo \"✅ Datenbank-Verbindung erfolgreich\\n\";
                echo \"Datenbank: \" . DB_NAME . \"\\n\";
                echo \"Benutzer: \" . DB_USER . \"\\n\";
                echo \"Host: \" . DB_HOST . \"\\n\";
                mysqli_close(\$conn);
            } else {
                echo \"❌ Verbindung fehlgeschlagen\\n\";
            }
        '"
        ;;
    
    tables)
        echo -e "${YELLOW}📋 Datenbank-Tabellen:${NC}"
        run_ssh "cd /www/htdocs/w0181e1b && php -r '
            require \"wp-config.php\";
            \$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            \$result = mysqli_query(\$conn, \"SHOW TABLES\");
            echo \"Tabellen in \" . DB_NAME . \":\\n\";
            echo \"-------------------\\n\";
            while (\$row = mysqli_fetch_array(\$result)) {
                echo \"  - \" . \$row[0] . \"\\n\";
            }
            mysqli_close(\$conn);
        '"
        ;;
    
    size)
        echo -e "${YELLOW}💾 Datenbank-Größe:${NC}"
        run_ssh "cd /www/htdocs/w0181e1b && php -r '
            require \"wp-config.php\";
            \$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            \$result = mysqli_query(\$conn, \"
                SELECT 
                    table_name AS Tabelle,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS Groesse_MB
                FROM information_schema.TABLES 
                WHERE table_schema = '\" . DB_NAME . \"'
                ORDER BY (data_length + index_length) DESC
            \");
            echo \"Tabellen-Größen:\\n\";
            echo \"-------------------\\n\";
            \$total = 0;
            while (\$row = mysqli_fetch_assoc(\$result)) {
                printf(\"%-30s %8.2f MB\\n\", \$row[\"Tabelle\"], \$row[\"Groesse_MB\"]);
                \$total += \$row[\"Groesse_MB\"];
            }
            echo \"-------------------\\n\";
            printf(\"%-30s %8.2f MB\\n\", \"GESAMT:\", \$total);
            mysqli_close(\$conn);
        '"
        ;;
    
    export)
        TIMESTAMP=$(date +%Y%m%d-%H%M%S)
        BACKUP_FILE="db-backup-${TIMESTAMP}.sql"
        echo -e "${YELLOW}💾 Exportiere Datenbank...${NC}"
        
        # Get DB credentials
        DB_NAME=$(run_ssh "cd /www/htdocs/w0181e1b && grep DB_NAME wp-config.php | cut -d\"'\" -f4")
        DB_USER=$(run_ssh "cd /www/htdocs/w0181e1b && grep DB_USER wp-config.php | cut -d\"'\" -f4")
        DB_PASS=$(run_ssh "cd /www/htdocs/w0181e1b && grep DB_PASSWORD wp-config.php | cut -d\"'\" -f4")
        
        echo "Datenbank: $DB_NAME"
        echo "Export nach: $BACKUP_FILE"
        
        run_ssh "cd /www/htdocs/w0181e1b && mysqldump -u${DB_USER} -p${DB_PASS} ${DB_NAME} > ${BACKUP_FILE} 2>/dev/null && echo '✅ Export erfolgreich: ${BACKUP_FILE}' || echo '❌ Export fehlgeschlagen'"
        
        echo ""
        echo "Download mit:"
        echo "./scripts/wp-ssh-v2.sh download /www/htdocs/w0181e1b/${BACKUP_FILE} ./${BACKUP_FILE}"
        ;;
    
    optimize)
        echo -e "${YELLOW}⚡ Optimiere Datenbank-Tabellen...${NC}"
        run_ssh "cd /www/htdocs/w0181e1b && php -r '
            require \"wp-config.php\";
            \$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            \$tables = mysqli_query(\$conn, \"SHOW TABLES\");
            while (\$table = mysqli_fetch_array(\$tables)) {
                \$result = mysqli_query(\$conn, \"OPTIMIZE TABLE \" . \$table[0]);
                echo \"Optimiere: \" . \$table[0] . \"... \";
                if (\$result) echo \"✅\\n\"; else echo \"❌\\n\";
            }
            mysqli_close(\$conn);
        '"
        ;;
    
    repair)
        echo -e "${YELLOW}🔧 Repariere Datenbank-Tabellen...${NC}"
        run_ssh "cd /www/htdocs/w0181e1b && php -r '
            require \"wp-config.php\";
            \$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            \$tables = mysqli_query(\$conn, \"SHOW TABLES\");
            while (\$table = mysqli_fetch_array(\$tables)) {
                \$result = mysqli_query(\$conn, \"REPAIR TABLE \" . \$table[0]);
                echo \"Repariere: \" . \$table[0] . \"... \";
                if (\$result) echo \"✅\\n\"; else echo \"❌\\n\";
            }
            mysqli_close(\$conn);
        '"
        ;;
    
    search-replace)
        if [ -z "$2" ] || [ -z "$3" ]; then
            echo "Usage: $0 search-replace '<old-domain>' '<new-domain>'"
            exit 1
        fi
        echo -e "${YELLOW}🔄 Suche und Ersetze in Datenbank...${NC}"
        echo "Ersetze: $2"
        echo "Mit: $3"
        echo ""
        echo -e "${RED}⚠️  WARNUNG: Dies ändert die Datenbank!${NC}"
        echo "Erstelle zuerst ein Backup mit: $0 export"
        echo ""
        read -p "Fortfahren? (y/n): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            run_ssh "cd /www/htdocs/w0181e1b && php -r '
                require \"wp-config.php\";
                \$old = \"$2\";
                \$new = \"$3\";
                \$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                
                // Update options
                mysqli_query(\$conn, \"UPDATE \" . \$table_prefix . \"options SET option_value = REPLACE(option_value, '\$old', '\$new') WHERE option_value LIKE '%\$old%'\");
                echo \"✅ Options aktualisiert\\n\";
                
                // Update posts
                mysqli_query(\$conn, \"UPDATE \" . \$table_prefix . \"posts SET post_content = REPLACE(post_content, '\$old', '\$new')\");
                mysqli_query(\$conn, \"UPDATE \" . \$table_prefix . \"posts SET guid = REPLACE(guid, '\$old', '\$new')\");
                echo \"✅ Posts aktualisiert\\n\";
                
                // Update postmeta
                mysqli_query(\$conn, \"UPDATE \" . \$table_prefix . \"postmeta SET meta_value = REPLACE(meta_value, '\$old', '\$new')\");
                echo \"✅ Postmeta aktualisiert\\n\";
                
                mysqli_close(\$conn);
            '"
        fi
        ;;
    
    *)
        echo "WordPress Database Management"
        echo ""
        echo "Usage: $0 <command>"
        echo ""
        echo "Info-Befehle:"
        echo "  info           - Datenbank-Konfiguration anzeigen"
        echo "  test           - Datenbank-Verbindung testen"
        echo "  tables         - Alle Tabellen anzeigen"
        echo "  size           - Tabellen-Größen anzeigen"
        echo ""
        echo "Wartung:"
        echo "  export         - Datenbank exportieren (SQL-Dump)"
        echo "  optimize       - Tabellen optimieren"
        echo "  repair         - Tabellen reparieren"
        echo ""
        echo "Erweitert:"
        echo "  search-replace - Domain in DB ersetzen"
        echo ""
        echo -e "${YELLOW}Hinweis:${NC}"
        echo "Für neue Datenbanken nutze das KAS-Interface:"
        echo "https://kas.all-inkl.com → Datenbanken → Neue Datenbank anlegen"
        echo ""
        ;;
esac