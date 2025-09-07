#!/bin/bash
# WordPress SSH Management für All-Inkl

source "$(dirname "$0")/../.env.local" 2>/dev/null

if [ -z "$KAS_PASSWORD" ]; then
    echo "❌ Bitte .env.local konfigurieren"
    exit 1
fi

# SSH command with correct username
SSH_CMD="sshpass -p \"$KAS_PASSWORD\" ssh -o StrictHostKeyChecking=no ssh-w0181e1b@w0181e1b.kasserver.com"

echo "🔧 WordPress SSH Manager"
echo "========================"
echo ""

case "$1" in
    test)
        echo "📡 Teste SSH-Verbindung..."
        $SSH_CMD "echo 'Verbunden!' && pwd && ls -la | head -5"
        ;;
    
    status)
        echo "📊 WordPress Status:"
        $SSH_CMD "cd /www/htdocs/w0181e1b && php wp-cli.phar --info 2>/dev/null || echo 'WP-CLI nicht verfügbar'"
        ;;
    
    wp-version)
        echo "📦 WordPress Version:"
        $SSH_CMD "cd /www/htdocs/w0181e1b && grep \"wp_version =\" wp-includes/version.php"
        ;;
    
    php-info)
        echo "🐘 PHP Info:"
        $SSH_CMD "php -v"
        ;;
    
    disk-usage)
        echo "💾 Speichernutzung:"
        $SSH_CMD "cd /www/htdocs/w0181e1b && du -sh . && du -sh wp-content/uploads wp-content/themes wp-content/plugins 2>/dev/null"
        ;;
    
    list-sites)
        echo "🌐 Verfügbare Websites:"
        $SSH_CMD "cd /www/htdocs/w0181e1b && ls -d *.de *.com 2>/dev/null | head -20"
        ;;
    
    backup-db)
        echo "💾 Erstelle Datenbank-Export..."
        TIMESTAMP=$(date +%Y%m%d-%H%M%S)
        $SSH_CMD "cd /www/htdocs/w0181e1b && php -r 'require\"wp-config.php\"; echo \"DB: \".DB_NAME.\"\n\";'"
        echo "Hinweis: Nutze phpMyAdmin im KAS für DB-Export"
        ;;
    
    clear-cache)
        echo "🧹 Cache leeren..."
        $SSH_CMD "cd /www/htdocs/w0181e1b && find wp-content/cache -type f -delete 2>/dev/null && echo 'Cache geleert'"
        ;;
    
    check-permissions)
        echo "🔐 Überprüfe Dateiberechtigungen..."
        $SSH_CMD "cd /www/htdocs/w0181e1b && ls -la wp-config.php .htaccess wp-content/"
        ;;
    
    error-log)
        echo "📋 Letzte Fehler (error_log):"
        $SSH_CMD "cd /www/htdocs/w0181e1b && tail -20 error_log 2>/dev/null || echo 'Kein error_log gefunden'"
        ;;
    
    shell)
        echo "🖥️  Öffne SSH-Shell..."
        echo "Nutze: ssh ssh-w0181e1b@w0181e1b.kasserver.com"
        echo ""
        sshpass -p "$KAS_PASSWORD" ssh -o StrictHostKeyChecking=no ssh-w0181e1b@w0181e1b.kasserver.com
        ;;
    
    run)
        if [ -z "$2" ]; then
            echo "Usage: $0 run '<command>'"
            exit 1
        fi
        echo "🏃 Führe Befehl aus: $2"
        $SSH_CMD "$2"
        ;;
    
    deploy-file)
        if [ -z "$2" ] || [ -z "$3" ]; then
            echo "Usage: $0 deploy-file <local-file> <remote-path>"
            exit 1
        fi
        echo "⬆️  Deploying via SCP..."
        sshpass -p "$KAS_PASSWORD" scp -o StrictHostKeyChecking=no "$2" ssh-w0181e1b@w0181e1b.kasserver.com:"$3"
        ;;
    
    *)
        echo "WordPress SSH Management"
        echo ""
        echo "Usage: $0 <command>"
        echo ""
        echo "Befehle:"
        echo "  test              - SSH-Verbindung testen"
        echo "  status            - WordPress Status"
        echo "  wp-version        - WordPress Version anzeigen"
        echo "  php-info          - PHP Version anzeigen"
        echo "  disk-usage        - Speichernutzung anzeigen"
        echo "  list-sites        - Verfügbare Websites"
        echo "  backup-db         - Datenbank-Info"
        echo "  clear-cache       - Cache leeren"
        echo "  check-permissions - Dateiberechtigungen prüfen"
        echo "  error-log         - Fehlerlog anzeigen"
        echo "  shell             - SSH-Shell öffnen"
        echo "  run '<cmd>'       - Befehl ausführen"
        echo "  deploy-file       - Datei via SCP deployen"
        echo ""
        ;;
esac