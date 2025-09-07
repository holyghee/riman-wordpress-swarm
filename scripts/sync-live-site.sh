#!/bin/bash
# Sync local WordPress (code + uploads + DB) to All-Inkl live (scoped to REMOTE_ROOT)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="${SCRIPT_DIR}/.."
ENV_FILE="${ROOT_DIR}/.env.local"

if [ -f "$ENV_FILE" ]; then
  # shellcheck disable=SC1090
  source "$ENV_FILE"
else
  echo "❌ .env.local nicht gefunden!"; exit 1
fi

SSH_USERNAME=${SSH_USERNAME:-ssh-w0181e1b}
KAS_HOST=${KAS_HOST:-w0181e1b.kasserver.com}
REMOTE_ROOT=${REMOTE_ROOT:-${WP_REMOTE_PATH:-/www/htdocs/w0181e1b/riman-wordpress-swarm.ecomwy.com}}
LOCAL_URL=${LOCAL_URL:-http://127.0.0.1:8801}
LIVE_URL=${LIVE_URL:-https://riman-wordpress-swarm.ecomwy.com}

if [ -z "${KAS_PASSWORD:-}" ]; then
  echo "❌ KAS_PASSWORD nicht gesetzt"; exit 1
fi

MODE="additive" # additive (no delete) | mirror (with delete)
if [ "${1:-}" = "--mirror" ]; then MODE="mirror"; shift; fi

echo "🌐 Sync local → live (scoped)"
echo "   REMOTE_ROOT: $REMOTE_ROOT"
echo "   LOCAL_URL:  $LOCAL_URL"
echo "   LIVE_URL:   $LIVE_URL"
echo "   MODE:       $MODE"

run_ssh() {
  sshpass -p "${KAS_PASSWORD}" ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR "${SSH_USERNAME}@${KAS_HOST}" "$@"
}

echo "1) Provision WP-CLI auf Live"
"$SCRIPT_DIR/remote-provision-allinkl.sh"

echo "2) Remote-Backup erstellen (DB)"
TS=$(date +%Y%m%d-%H%M%S)
run_ssh "mkdir -p '$REMOTE_ROOT/backups' && cd '$REMOTE_ROOT' && php wp-cli.phar db export 'backups/db-$TS.sql' --add-drop-table >/dev/null 2>&1 || true"
echo "   ✅ DB-Backup: backups/db-$TS.sql"

echo "3) Code deploy (Themes+Plugins)"
if [ "$MODE" = "mirror" ]; then
  "$SCRIPT_DIR/wp-ssh-v2.sh" deploy-wp
else
  "$SCRIPT_DIR/wp-ssh-v2.sh" deploy-wp --no-delete
fi

echo "4) Uploads deploy"
if [ -d "$ROOT_DIR/wp-content/uploads" ]; then
  if [ "$MODE" = "mirror" ]; then
    "$SCRIPT_DIR/wp-ssh-v2.sh" deploy-uploads "$ROOT_DIR/wp-content/uploads"
  else
    "$SCRIPT_DIR/wp-ssh-v2.sh" deploy-uploads --no-delete "$ROOT_DIR/wp-content/uploads"
  fi
else
  echo "   ℹ️  Kein lokales wp-content/uploads Verzeichnis gefunden – überspringe Uploads"
fi

echo "5) Lokale DB exportieren (Docker WP-CLI)"
LOCAL_SQL="/tmp/local-$TS.sql"
docker compose run --rm wpcli wp db export - > "$LOCAL_SQL"
echo "   ✅ Local dump: $LOCAL_SQL"

echo "6) DB-Import auf Live"
"$SCRIPT_DIR/wp-ssh-v2.sh" upload "$LOCAL_SQL" "$REMOTE_ROOT/local.sql"
run_ssh "cd '$REMOTE_ROOT' && php wp-cli.phar db import local.sql && rm -f local.sql"

echo "7) URL Search-Replace auf Live"
run_ssh "cd '$REMOTE_ROOT' && php wp-cli.phar search-replace '$LOCAL_URL' '$LIVE_URL' --all-tables --precise --recurse-objects --skip-columns=guid >/dev/null 2>&1 || true"

echo "8) Cache leeren"
"$SCRIPT_DIR/wp-ssh-v2.sh" cache-clear

echo "✅ Sync abgeschlossen: Dateien + Uploads + DB"

