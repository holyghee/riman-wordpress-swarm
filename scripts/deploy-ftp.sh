#!/bin/bash
# FTP/SFTP Deployment für All-Inkl (ohne SSH-Shell)

source "$(dirname "$0")/../.env.local" 2>/dev/null

if [ -z "$KAS_PASSWORD" ]; then
    echo "❌ Bitte .env.local konfigurieren"
    exit 1
fi

echo "🚀 All-Inkl WordPress Deployment"
echo "================================="
echo ""

# Function for SFTP batch operations
sftp_batch() {
    local commands="$1"
    echo "$commands" | sshpass -p "$KAS_PASSWORD" sftp -o StrictHostKeyChecking=no -b - w0181e1b@w0181e1b.kasserver.com
}

# Function for FTP operations using curl
ftp_upload() {
    local local_file="$1"
    local remote_path="$2"
    curl -T "$local_file" \
         -u "${KAS_USERNAME}:${KAS_PASSWORD}" \
         "ftp://${KAS_HOST}${remote_path}" \
         --ftp-create-dirs
}

case "$1" in
    test)
        echo "📡 Teste FTP-Verbindung..."
        curl -u "${KAS_USERNAME}:${KAS_PASSWORD}" \
             "ftp://${KAS_HOST}/" \
             --list-only \
             --max-time 10 \
             2>/dev/null
        
        if [ $? -eq 0 ]; then
            echo "✅ FTP-Verbindung erfolgreich!"
        else
            echo "❌ FTP-Verbindung fehlgeschlagen"
        fi
        ;;
    
    list)
        echo "📂 WordPress-Verzeichnis:"
        curl -u "${KAS_USERNAME}:${KAS_PASSWORD}" \
             "ftp://${KAS_HOST}/www/htdocs/w0181e1b/" \
             --list-only
        ;;
    
    upload)
        if [ -z "$2" ] || [ -z "$3" ]; then
            echo "Usage: $0 upload <local-file> <remote-path>"
            exit 1
        fi
        echo "⬆️  Uploading $2 to $3..."
        ftp_upload "$2" "$3"
        echo "✅ Upload complete"
        ;;
    
    download)
        if [ -z "$2" ] || [ -z "$3" ]; then
            echo "Usage: $0 download <remote-file> <local-path>"
            exit 1
        fi
        echo "⬇️  Downloading $2..."
        curl -u "${KAS_USERNAME}:${KAS_PASSWORD}" \
             "ftp://${KAS_HOST}$2" \
             -o "$3"
        echo "✅ Download complete"
        ;;
    
    backup)
        echo "💾 Erstelle lokales Backup von wp-content..."
        BACKUP_DIR="backups/$(date +%Y%m%d-%H%M%S)"
        mkdir -p "$BACKUP_DIR"
        
        # Download wp-content structure
        curl -u "${KAS_USERNAME}:${KAS_PASSWORD}" \
             "ftp://${KAS_HOST}/www/htdocs/w0181e1b/wp-content/" \
             --list-only > "$BACKUP_DIR/structure.txt"
        
        echo "✅ Backup-Struktur gespeichert in $BACKUP_DIR"
        ;;
    
    deploy-theme)
        if [ -z "$2" ]; then
            echo "Usage: $0 deploy-theme <theme-directory>"
            exit 1
        fi
        
        THEME_NAME=$(basename "$2")
        echo "🎨 Deploying theme: $THEME_NAME"
        
        # Upload all theme files
        find "$2" -type f | while read file; do
            relative_path="${file#$2/}"
            remote_path="/www/htdocs/w0181e1b/wp-content/themes/$THEME_NAME/$relative_path"
            echo "  Uploading: $relative_path"
            ftp_upload "$file" "$remote_path"
        done
        
        echo "✅ Theme deployed successfully"
        ;;
    
    deploy-plugin)
        if [ -z "$2" ]; then
            echo "Usage: $0 deploy-plugin <plugin-directory>"
            exit 1
        fi
        
        PLUGIN_NAME=$(basename "$2")
        echo "🔌 Deploying plugin: $PLUGIN_NAME"
        
        # Upload all plugin files
        find "$2" -type f | while read file; do
            relative_path="${file#$2/}"
            remote_path="/www/htdocs/w0181e1b/wp-content/plugins/$PLUGIN_NAME/$relative_path"
            echo "  Uploading: $relative_path"
            ftp_upload "$file" "$remote_path"
        done
        
        echo "✅ Plugin deployed successfully"
        ;;
    
    *)
        echo "All-Inkl FTP Deployment Tool"
        echo ""
        echo "Usage: $0 {test|list|upload|download|backup|deploy-theme|deploy-plugin}"
        echo ""
        echo "Commands:"
        echo "  test          - Test FTP connection"
        echo "  list          - List WordPress directory"
        echo "  upload        - Upload single file"
        echo "  download      - Download single file"
        echo "  backup        - Create local backup structure"
        echo "  deploy-theme  - Deploy complete theme"
        echo "  deploy-plugin - Deploy complete plugin"
        echo ""
        echo "Examples:"
        echo "  $0 test"
        echo "  $0 upload file.php /www/htdocs/w0181e1b/"
        echo "  $0 deploy-theme ./my-theme"
        ;;
esac