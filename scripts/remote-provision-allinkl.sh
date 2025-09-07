#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/../.env.local"

if [ -f "$ENV_FILE" ]; then
  # shellcheck disable=SC1090
  source "$ENV_FILE"
else
  echo "❌ .env.local nicht gefunden!"; exit 1
fi

SSH_USERNAME=${SSH_USERNAME:-ssh-w0181e1b}
KAS_HOST=${KAS_HOST:-w0181e1b.kasserver.com}
REMOTE_ROOT=${REMOTE_ROOT:-${WP_REMOTE_PATH:-/www/htdocs/w0181e1b/riman-wordpress-swarm.ecomwy.com}}

if [ -z "${KAS_PASSWORD:-}" ]; then
  echo "❌ KAS_PASSWORD nicht gesetzt"; exit 1
fi

echo "🔧 Provision All-Inkl (scoped to $REMOTE_ROOT)"

run_ssh() {
  sshpass -p "${KAS_PASSWORD}" ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR "${SSH_USERNAME}@${KAS_HOST}" "$@"
}

echo "• Erzeuge Verzeichnis backups/"
run_ssh "mkdir -p '$REMOTE_ROOT/backups'"

echo "• Prüfe PHP CLI"
run_ssh "php -v >/dev/null 2>&1" || { echo "❌ PHP CLI nicht verfügbar"; exit 1; }

echo "• Installiere wp-cli.phar (falls fehlt)"
run_ssh "cd '$REMOTE_ROOT' && [ -f wp-cli.phar ] || curl -sSLo wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar"

echo "✅ Provision abgeschlossen. WP-CLI via: php $REMOTE_ROOT/wp-cli.phar"

