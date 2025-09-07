#!/bin/bash
# WordPress SSH Management für All-Inkl (Version 2)

# Load environment
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/../.env.local"

if [ -f "$ENV_FILE" ]; then
    source "$ENV_FILE"
else
    echo "❌ .env.local nicht gefunden!"
    exit 1
fi

if [ -z "$KAS_PASSWORD" ]; then
    echo "❌ KAS_PASSWORD nicht in .env.local gesetzt!"
    exit 1
fi

# Resolve connection parameters from env with sensible defaults
SSH_USERNAME=${SSH_USERNAME:-ssh-w0181e1b}
KAS_HOST=${KAS_HOST:-w0181e1b.kasserver.com}
# Prefer REMOTE_ROOT, fall back to WP_REMOTE_PATH for backward compatibility
REMOTE_ROOT=${REMOTE_ROOT:-${WP_REMOTE_PATH:-/www/htdocs/w0181e1b/riman-wordpress-swarm.ecomwy.com}}

echo "🔧 WordPress SSH Manager V2"
echo "==========================="
echo ""

# Function to run SSH commands
run_ssh() {
    sshpass -p "${KAS_PASSWORD}" ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR "${SSH_USERNAME}@${KAS_HOST}" "$@"
}

# Function to run SCP
run_scp() {
    sshpass -p "${KAS_PASSWORD}" scp -o StrictHostKeyChecking=no -o LogLevel=ERROR "$@"
}

case "$1" in
    test)
        echo "📡 Teste SSH-Verbindung..."
        run_ssh "echo '✅ Verbunden!' && pwd && echo '' && echo 'WordPress-Verzeichnisse:' && ls -d /www/htdocs/w0181e1b/*.de 2>/dev/null | head -5"
        ;;
    
    php)
        echo "🐘 PHP Version:"
        run_ssh "php -v | head -3"
        ;;
    
    sites)
        echo "🌐 Verfügbare Websites:"
        run_ssh "cd /www/htdocs/w0181e1b && ls -d *.de *.com *.ch 2>/dev/null"
        ;;
    
    wp-check)
        echo "📦 WordPress Installation prüfen:"
        run_ssh "cd /www/htdocs/w0181e1b && [ -f wp-config.php ] && echo '✅ WordPress gefunden' || echo '❌ Keine WordPress Installation'"
        run_ssh "cd /www/htdocs/w0181e1b && [ -f wp-cli.phar ] && echo '✅ WP-CLI verfügbar' || echo '❌ WP-CLI nicht gefunden'"
        ;;
    
    themes)
        echo "🎨 Installierte Themes:"
        run_ssh "cd /www/htdocs/w0181e1b/wp-content/themes && ls -d */ 2>/dev/null"
        ;;
    
    plugins)
        echo "🔌 Installierte Plugins:"
        run_ssh "cd /www/htdocs/w0181e1b/wp-content/plugins && ls -d */ 2>/dev/null | head -20"
        ;;
    
    disk)
        echo "💾 Speichernutzung:"
        run_ssh "cd /www/htdocs/w0181e1b && du -sh . && echo '' && echo 'Größte Verzeichnisse:' && du -sh * 2>/dev/null | sort -rh | head -10"
        ;;
    
    permissions)
        echo "🔐 Dateiberechtigungen:"
        run_ssh "cd /www/htdocs/w0181e1b && ls -la wp-config.php .htaccess 2>/dev/null"
        ;;
    
    run)
        if [ -z "$2" ]; then
            echo "Usage: $0 run '<command>'"
            exit 1
        fi
        echo "🏃 Führe aus: $2"
        run_ssh "$2"
        ;;
    
    upload)
        if [ -z "$2" ] || [ -z "$3" ]; then
            echo "Usage: $0 upload <local-file> <remote-path>"
            exit 1
        fi
        echo "⬆️  Uploade $2 nach $3..."
        run_scp "$2" "${SSH_USERNAME}@${KAS_HOST}:$3"
        echo "✅ Upload abgeschlossen"
        ;;
    
    download)
        if [ -z "$2" ] || [ -z "$3" ]; then
            echo "Usage: $0 download <remote-file> <local-path>"
            exit 1
        fi
        echo "⬇️  Downloade $2..."
        run_scp "${SSH_USERNAME}@${KAS_HOST}:$2" "$3"
        echo "✅ Download abgeschlossen"
        ;;

    deploy-uploads)
        # Sync a local directory to wp-content/uploads[/<subdir>]
        # Usage: deploy-uploads [--dry-run|-n] [--no-delete|-k] <local-dir> [remote-subdir]
        DRY_RUN=0
        NO_DELETE=0
        # Parse up to two flags in any order
        ARG1="$2"; ARG2="$3"; ARG3="$4"; ARG4="$5"
        for _ in 1 2; do
            if [ "$ARG1" = "--dry-run" ] || [ "$ARG1" = "-n" ]; then
                DRY_RUN=1; ARG1="$ARG2"; ARG2="$ARG3"; ARG3="$ARG4"; ARG4=""; continue
            fi
            if [ "$ARG1" = "--no-delete" ] || [ "$ARG1" = "-k" ]; then
                NO_DELETE=1; ARG1="$ARG2"; ARG2="$ARG3"; ARG3="$ARG4"; ARG4=""; continue
            fi
            break
        done
        if [ -z "$ARG1" ]; then
            echo "Usage: $0 deploy-uploads [--dry-run|-n] [--no-delete|-k] <local-dir> [remote-subdir]"
            exit 1
        fi
        LOCAL_DIR="$ARG1"
        REMOTE_SUBDIR="$ARG2"
        if [ ! -d "$LOCAL_DIR" ]; then
            echo "❌ Lokales Verzeichnis nicht gefunden: $LOCAL_DIR"
            exit 1
        fi
        DEST_BASE="$REMOTE_ROOT/wp-content/uploads"
        DEST_PATH="$DEST_BASE"
        if [ -n "$REMOTE_SUBDIR" ]; then
            DEST_PATH="$DEST_BASE/$REMOTE_SUBDIR"
        fi
        FILE_COUNT=$(find "$LOCAL_DIR" -type f | wc -l | tr -d ' ')
        TOTAL_SIZE=$(du -sh "$LOCAL_DIR" | awk '{print $1}')
        ACTION_DESC="Synchronisiere Uploads"; [ "$DRY_RUN" = "1" ] && ACTION_DESC="DRY-RUN: Prüfe Synchronisation"
        DELETE_DESC="mit Löschung"; [ "$NO_DELETE" = "1" ] && DELETE_DESC="ohne Löschung"
        echo "🧭 $ACTION_DESC ($DELETE_DESC): $LOCAL_DIR → $DEST_PATH"
        echo "   Lokal: $FILE_COUNT Dateien, $TOTAL_SIZE"
        [ "$DRY_RUN" = "0" ] && run_ssh "mkdir -p '$DEST_PATH'"
        # Prefer rsync if available on remote; fallback to scp -r
        if run_ssh "command -v rsync >/dev/null 2>&1" >/dev/null 2>&1; then
            echo "➡️  Verwende rsync"
            RSYNC_FLAGS="-avz"
            [ "$NO_DELETE" = "0" ] && RSYNC_FLAGS="$RSYNC_FLAGS --delete"
            [ "$DRY_RUN" = "1" ] && RSYNC_FLAGS="$RSYNC_FLAGS -n -i"
            sshpass -p "${KAS_PASSWORD}" rsync $RSYNC_FLAGS \
              --exclude='.DS_Store' --exclude='._*' \
              -e "ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR" \
              "$LOCAL_DIR/" "${SSH_USERNAME}@${KAS_HOST}:$DEST_PATH/"
        else
            if [ "$DRY_RUN" = "1" ]; then
                echo "ℹ️  rsync nicht verfügbar (remote) – würde mit scp -r kopieren (ohne Löschvorschau)."
                echo "    Vorschau lokaler Dateien:"
                find "$LOCAL_DIR" -type f | sed 's/^/    • /' | head -200
                [ "$FILE_COUNT" -gt 200 ] && echo "    … und $((FILE_COUNT-200)) weitere Dateien"
            else
                echo "➡️  rsync nicht verfügbar – verwende scp -r"
                run_scp -r "$LOCAL_DIR/" "${SSH_USERNAME}@${KAS_HOST}:$DEST_PATH/"
            fi
        fi
        if [ "$DRY_RUN" = "1" ]; then
            echo "✅ DRY-RUN abgeschlossen (keine Änderungen vorgenommen)"
        else
            echo "✅ Uploads synchronisiert ($DELETE_DESC)"
        fi
        ;;

    deploy-wp)
        # Deploy wp-content/themes and wp-content/plugins via SSH rsync
        # Usage: deploy-wp [--dry-run|-n] [--no-delete|-k]
        DRY_RUN=0
        NO_DELETE=0
        ARG1="$2"; ARG2="$3"
        for _ in 1 2; do
            if [ "$ARG1" = "--dry-run" ] || [ "$ARG1" = "-n" ]; then DRY_RUN=1; ARG1="$ARG2"; ARG2=""; continue; fi
            if [ "$ARG1" = "--no-delete" ] || [ "$ARG1" = "-k" ]; then NO_DELETE=1; ARG1="$ARG2"; ARG2=""; continue; fi
            break
        done
        DEST_ROOT="$REMOTE_ROOT/wp-content"
        echo "🧭 $( [ $DRY_RUN -eq 1 ] && echo 'DRY-RUN:' || echo 'Deploy:' ) wp-content → $DEST_ROOT"
        RSYNC_FLAGS="-avz"
        [ $NO_DELETE -eq 0 ] && RSYNC_FLAGS="$RSYNC_FLAGS --delete"
        [ $DRY_RUN -eq 1 ] && RSYNC_FLAGS="$RSYNC_FLAGS -n -i"
        # Themes
        if [ -d "${SCRIPT_DIR}/../wp-content/themes" ]; then
          echo "🎨 Themes sync"
          sshpass -p "${KAS_PASSWORD}" rsync $RSYNC_FLAGS \
            --exclude='node_modules/' --exclude='.git/' --exclude='*.log' \
            -e "ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR" \
            "${SCRIPT_DIR}/../wp-content/themes/" "${SSH_USERNAME}@${KAS_HOST}:$DEST_ROOT/themes/"
        else
          echo "ℹ️  Keine lokalen Themes gefunden (wp-content/themes)"
        fi
        # Plugins
        if [ -d "${SCRIPT_DIR}/../wp-content/plugins" ]; then
          echo "🔌 Plugins sync"
          sshpass -p "${KAS_PASSWORD}" rsync $RSYNC_FLAGS \
            --exclude='node_modules/' --exclude='.git/' --exclude='*.log' --exclude='tests/' \
            -e "ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR" \
            "${SCRIPT_DIR}/../wp-content/plugins/" "${SSH_USERNAME}@${KAS_HOST}:$DEST_ROOT/plugins/"
        else
          echo "ℹ️  Keine lokalen Plugins gefunden (wp-content/plugins)"
        fi
        [ $DRY_RUN -eq 1 ] && echo "✅ DRY-RUN abgeschlossen (keine Änderungen)" || echo "✅ Code-Deploy abgeschlossen"
        ;;

    deploy-theme)
        # Usage: deploy-theme [--dry-run|-n] [--no-delete|-k] <theme-dir>
        DRY_RUN=0; NO_DELETE=0
        ARG1="$2"; ARG2="$3"; ARG3="$4"
        for _ in 1 2; do
            if [ "$ARG1" = "--dry-run" ] || [ "$ARG1" = "-n" ]; then DRY_RUN=1; ARG1="$ARG2"; ARG2="$ARG3"; ARG3=""; continue; fi
            if [ "$ARG1" = "--no-delete" ] || [ "$ARG1" = "-k" ]; then NO_DELETE=1; ARG1="$ARG2"; ARG2="$ARG3"; ARG3=""; continue; fi
            break
        done
        [ -z "$ARG1" ] && echo "Usage: $0 deploy-theme [--dry-run|-n] [--no-delete|-k] <theme-dir>" && exit 1
        THEME_PATH="$ARG1"
        [ ! -d "$THEME_PATH" ] && echo "❌ Theme-Verzeichnis nicht gefunden: $THEME_PATH" && exit 1
        THEME_NAME=$(basename "$THEME_PATH")
        DEST_PATH="$REMOTE_ROOT/wp-content/themes/$THEME_NAME"
        RSYNC_FLAGS="-avz"; [ $NO_DELETE -eq 0 ] && RSYNC_FLAGS="$RSYNC_FLAGS --delete"; [ $DRY_RUN -eq 1 ] && RSYNC_FLAGS="$RSYNC_FLAGS -n -i"
        echo "🎨 Deploy Theme $THEME_NAME → $DEST_PATH"
        sshpass -p "${KAS_PASSWORD}" rsync $RSYNC_FLAGS \
          --exclude='node_modules/' --exclude='.git/' --exclude='*.log' \
          -e "ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR" \
          "$THEME_PATH/" "${SSH_USERNAME}@${KAS_HOST}:$DEST_PATH/"
        [ $DRY_RUN -eq 1 ] && echo "✅ DRY-RUN abgeschlossen" || echo "✅ Theme deployed"
        ;;

    deploy-plugin)
        # Usage: deploy-plugin [--dry-run|-n] [--no-delete|-k] <plugin-dir>
        DRY_RUN=0; NO_DELETE=0
        ARG1="$2"; ARG2="$3"; ARG3="$4"
        for _ in 1 2; do
            if [ "$ARG1" = "--dry-run" ] || [ "$ARG1" = "-n" ]; then DRY_RUN=1; ARG1="$ARG2"; ARG2="$ARG3"; ARG3=""; continue; fi
            if [ "$ARG1" = "--no-delete" ] || [ "$ARG1" = "-k" ]; then NO_DELETE=1; ARG1="$ARG2"; ARG2="$ARG3"; ARG3=""; continue; fi
            break
        done
        [ -z "$ARG1" ] && echo "Usage: $0 deploy-plugin [--dry-run|-n] [--no-delete|-k] <plugin-dir>" && exit 1
        PLUGIN_PATH="$ARG1"
        [ ! -d "$PLUGIN_PATH" ] && echo "❌ Plugin-Verzeichnis nicht gefunden: $PLUGIN_PATH" && exit 1
        PLUGIN_NAME=$(basename "$PLUGIN_PATH")
        DEST_PATH="$REMOTE_ROOT/wp-content/plugins/$PLUGIN_NAME"
        RSYNC_FLAGS="-avz"; [ $NO_DELETE -eq 0 ] && RSYNC_FLAGS="$RSYNC_FLAGS --delete"; [ $DRY_RUN -eq 1 ] && RSYNC_FLAGS="$RSYNC_FLAGS -n -i"
        echo "🔌 Deploy Plugin $PLUGIN_NAME → $DEST_PATH"
        sshpass -p "${KAS_PASSWORD}" rsync $RSYNC_FLAGS \
          --exclude='node_modules/' --exclude='.git/' --exclude='*.log' --exclude='tests/' \
          -e "ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR" \
          "$PLUGIN_PATH/" "${SSH_USERNAME}@${KAS_HOST}:$DEST_PATH/"
        [ $DRY_RUN -eq 1 ] && echo "✅ DRY-RUN abgeschlossen" || echo "✅ Plugin deployed"
        ;;

    cache-clear)
        echo "🧹 Leere WordPress Cache (scoped to REMOTE_ROOT)"
        run_ssh "rm -rf '${REMOTE_ROOT}/wp-content/cache/'* 2>/dev/null || true"
        echo "✅ Cache geleert (falls vorhanden)"
        ;;

    clean-uploads-dir)
        # Safely remove a subdirectory under wp-content/uploads (scoped)
        # Usage: clean-uploads-dir <subdir>
        SUBDIR="$2"
        if [ -z "$SUBDIR" ]; then
            echo "Usage: $0 clean-uploads-dir <subdir>"
            exit 1
        fi
        # Basic safety checks: no absolute path, no parent traversal
        case "$SUBDIR" in
            /*|*..*) echo "❌ Unsicherer Pfad: $SUBDIR"; exit 1 ;;
        esac
        TARGET="${REMOTE_ROOT}/wp-content/uploads/${SUBDIR}"
        echo "🛡️  Entferne Uploads-Unterordner (scoped): $TARGET"
        run_ssh "if [ -d '$TARGET' ]; then rm -rf -- '$TARGET'; echo '✅ Entfernt'; else echo 'ℹ️  Ordner nicht vorhanden'; fi"
        ;;

    wpcli-install)
        echo "🛠️  Installiere/aktualisiere WP-CLI im REMOTE_ROOT"
        run_ssh "cd '${REMOTE_ROOT}' && curl -sSLo wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && ( (command -v php84 >/dev/null 2>&1 && php84 --version) || (command -v php82 >/dev/null 2>&1 && php82 --version) || php --version ) >/dev/null 2>&1 && ( (command -v php84 >/dev/null 2>&1 && php84 wp-cli.phar --info | head -3) || (command -v php82 >/dev/null 2>&1 && php82 wp-cli.phar --info | head -3) || php wp-cli.phar --info | head -3 ) || true"
        echo "✅ WP-CLI bereit unter: ${REMOTE_ROOT}/wp-cli.phar"
        ;;

    wp)
        # Run WP-CLI on the remote with path preset to REMOTE_ROOT using php82 when available
        # Usage: wp "<wp-cli arguments>"
        if [ -z "$2" ]; then
            echo "Usage: $0 wp \"<wp-cli arguments>\""
            echo "Beispiel: $0 wp \"option get siteurl\""
            exit 1
        fi
        ARGS="$2"
        echo "🔧 WP-CLI (remote): $ARGS"
        run_ssh "cd '${REMOTE_ROOT}' && PHP_BIN=php; if command -v php84 >/dev/null 2>&1; then PHP_BIN=php84; elif command -v php82 >/dev/null 2>&1; then PHP_BIN=php82; fi; \"$PHP_BIN\" wp-cli.phar --path='${REMOTE_ROOT}' $ARGS"
        ;;

    wp-db-import)
        # Upload and import a local .sql dump into live DB using WP-CLI (scoped to REMOTE_ROOT)
        # Usage: wp-db-import <local-sql-file>
        LOCAL_SQL="$2"
        if [ -z "$LOCAL_SQL" ]; then
            echo "Usage: $0 wp-db-import <local-sql-file>"
            exit 1
        fi
        if [ ! -f "$LOCAL_SQL" ]; then
            echo "❌ Datei nicht gefunden: $LOCAL_SQL"
            exit 1
        fi
        echo "⬆️  Lade Dump hoch: $LOCAL_SQL"
        BASENAME=$(basename "$LOCAL_SQL")
        REMOTE_SQL="${REMOTE_ROOT}/$BASENAME"
        run_scp "$LOCAL_SQL" "${SSH_USERNAME}@${KAS_HOST}:$REMOTE_SQL"
        echo "🗄️  Importiere Dump auf Live..."
        run_ssh "PHP_BIN=php; if command -v php84 >/dev/null 2>&1; then PHP_BIN=php84; elif command -v php82 >/dev/null 2>&1; then PHP_BIN=php82; fi; \"$PHP_BIN\" '${REMOTE_ROOT}/wp-cli.phar' --path='${REMOTE_ROOT}' db import '$REMOTE_SQL' && rm -f '$REMOTE_SQL'"
        echo "✅ DB-Import abgeschlossen"
        ;;

    wp-search-replace)
        # Search-Replace URLs across all tables (guid skipped)
        # Usage: wp-search-replace <from> <to>
        FROM="$2"; TO="$3"
        if [ -z "$FROM" ] || [ -z "$TO" ]; then
            echo "Usage: $0 wp-search-replace <from> <to>"
            exit 1
        fi
        echo "🔎 Search-Replace: $FROM → $TO"
        run_ssh "PHP_BIN=php; if command -v php84 >/dev/null 2>&1; then PHP_BIN=php84; elif command -v php82 >/dev/null 2>&1; then PHP_BIN=php82; fi; \"$PHP_BIN\" '${REMOTE_ROOT}/wp-cli.phar' --path='${REMOTE_ROOT}' search-replace '$FROM' '$TO' --all-tables --precise --recurse-objects --skip-columns=guid"
        echo "✅ Search-Replace abgeschlossen"
        ;;

    wp-rewrite-flush)
        echo "🔁 Flush Permalinks"
        run_ssh "PHP_BIN=php; if command -v php84 >/dev/null 2>&1; then PHP_BIN=php84; elif command -v php82 >/dev/null 2>&1; then PHP_BIN=php82; fi; \"$PHP_BIN\" '${REMOTE_ROOT}/wp-cli.phar' --path='${REMOTE_ROOT}' rewrite flush --hard"
        echo "✅ Permalinks geflusht"
        ;;

    wp-cache-flush)
        echo "🧹 Cache flush"
        run_ssh "PHP_BIN=php; if command -v php84 >/dev/null 2>&1; then PHP_BIN=php84; elif command -v php82 >/dev/null 2>&1; then PHP_BIN=php82; fi; \"$PHP_BIN\" '${REMOTE_ROOT}/wp-cli.phar' --path='${REMOTE_ROOT}' cache flush"
        echo "✅ Cache geleert"
        ;;

    wp-media-regenerate)
        echo "🖼️  Regeneriere fehlende Thumbnails"
        run_ssh "PHP_BIN=php; if command -v php84 >/dev/null 2>&1; then PHP_BIN=php84; elif command -v php82 >/dev/null 2>&1; then PHP_BIN=php82; fi; \"$PHP_BIN\" '${REMOTE_ROOT}/wp-cli.phar' --path='${REMOTE_ROOT}' media regenerate --only-missing --yes"
        echo "✅ Media regenerate gestartet (nur fehlende)"
        ;;
    
    shell)
        echo "🖥️  Öffne SSH-Shell..."
        echo "Verbinde zu: ${SSH_USERNAME}@${KAS_HOST}"
        echo ""
        sshpass -p "${KAS_PASSWORD}" ssh -o StrictHostKeyChecking=no "${SSH_USERNAME}@${KAS_HOST}"
        ;;
    
    *)
        echo "WordPress SSH Management V2"
        echo ""
        echo "Usage: $0 <command>"
        echo ""
        echo "Info-Befehle:"
        echo "  test        - SSH-Verbindung testen"
        echo "  php         - PHP Version anzeigen"
        echo "  sites       - Verfügbare Websites"
        echo "  wp-check    - WordPress Installation prüfen"
        echo "  themes      - Installierte Themes"
        echo "  plugins     - Installierte Plugins"
        echo "  disk        - Speichernutzung"
        echo "  permissions - Dateiberechtigungen"
        echo ""
        echo "Aktionen:"
        echo "  run '<cmd>' - Befehl ausführen"
        echo "  upload      - Datei hochladen"
        echo "  download    - Datei herunterladen"
        echo "  deploy-uploads [--dry-run|-n] [--no-delete|-k] <local> [subdir] - Verzeichnis nach uploads/ synchronisieren"
        echo "  wpcli-install - WP-CLI im REMOTE_ROOT installieren/aktualisieren"
        echo "  wp \"<args>\" - WP-CLI mit --path=REMOTE_ROOT ausführen (php82 wenn verfügbar)"
        echo "  wp-db-import <local.sql> - Lokalen Dump hochladen und importieren"
        echo "  wp-search-replace <from> <to> - URLs global ersetzen (guid ausgespart)"
        echo "  wp-rewrite-flush - Permalinks flushen"
        echo "  wp-cache-flush - Cache leeren"
        echo "  wp-media-regenerate - Fehlende Thumbnails regenerieren"
        echo "  cache-clear - Cache unter REMOTE_ROOT/wp-content/cache leeren"
        echo "  clean-uploads-dir <subdir> - Unterordner von uploads sicher entfernen"
        echo "  shell       - SSH-Shell öffnen"
        echo ""
        ;;
esac
