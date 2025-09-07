#!/bin/bash
# Ein-Kommando DB-Sync: lokal → live (Dump, Upload, Import, Replace, Flush)
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="${SCRIPT_DIR}/.."
ENV_FILE="${ROOT_DIR}/.env.local"

[ -f "$ENV_FILE" ] && source "$ENV_FILE" || { echo "❌ .env.local nicht gefunden"; exit 1; }

SSH_USERNAME=${SSH_USERNAME:-ssh-w0181e1b}
KAS_HOST=${KAS_HOST:-w0181e1b.kasserver.com}
REMOTE_ROOT=${REMOTE_ROOT:-/www/htdocs/w0181e1b/riman-wordpress-swarm.ecomwy.com}
LOCAL_URL=${LOCAL_URL:-http://127.0.0.1:8801}
LIVE_URL=${LIVE_URL:-https://riman-wordpress-swarm.ecomwy.com}

# DB‑Zugang aus docker-compose (lokal)
DB_USER=${DB_USER:-riman_user}
DB_PASS=${DB_PASS:-riman_pass_2024}
DB_NAME=${DB_NAME:-riman_wp}

require_cmd() { command -v "$1" >/dev/null 2>&1 || { echo "❌ Benötigt: $1"; exit 1; }; }
require_cmd docker
require_cmd sshpass

run_ssh() {
  sshpass -p "${KAS_PASSWORD}" ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR "${SSH_USERNAME}@${KAS_HOST}" "$@"
}

echo "🗄️  Exportiere lokale DB (kompatibel)…"
SQL="/tmp/local-db-$(date +%Y%m%d-%H%M%S).sql"
docker compose exec -T db mysqldump -u "$DB_USER" -p"$DB_PASS" \
  --default-character-set=utf8mb4 --single-transaction --quick --no-tablespaces --add-drop-table "$DB_NAME" \
  | sed -E 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g' > "$SQL"
echo "   → $SQL"

echo "⬆️  Lade Dump hoch…"
"$SCRIPT_DIR/wp-ssh-v2.sh" upload "$SQL" "$REMOTE_ROOT/local.sql"

echo "📥 Import + URL‑Replace + Flush…"
run_ssh "PHP_BIN=php; if command -v php84 >/dev/null 2>&1; then PHP_BIN=php84; elif command -v php82 >/dev/null 2>&1; then PHP_BIN=php82; fi; \"$PHP_BIN\" '$REMOTE_ROOT/wp-cli.phar' --path='$REMOTE_ROOT' db import '$REMOTE_ROOT/local.sql' && \"$PHP_BIN\" '$REMOTE_ROOT/wp-cli.phar' --path='$REMOTE_ROOT' search-replace '$LOCAL_URL' '$LIVE_URL' --all-tables --precise --recurse-objects --skip-columns=guid && \"$PHP_BIN\" '$REMOTE_ROOT/wp-cli.phar' --path='$REMOTE_ROOT' rewrite flush --hard && \"$PHP_BIN\" '$REMOTE_ROOT/wp-cli.phar' --path='$REMOTE_ROOT' cache flush && rm -f '$REMOTE_ROOT/local.sql'"

echo "✅ DB‑Sync fertig"

