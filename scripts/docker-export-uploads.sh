#!/bin/bash
# Exportiert WordPress-Uploads aus einem Docker-Container lokal in wp-content/uploads-from-docker
# und synchronisiert optional inkrementell (mirror oder additiv).

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="${SCRIPT_DIR}/.."

# Defaults
WORDPRESS_CONTAINER=${WORDPRESS_CONTAINER:-riman-wordpress-swarm-wordpress-1}
DEST_REL=${DEST_REL:-wp-content/uploads-from-docker}
DEST_ABS="${REPO_ROOT}/${DEST_REL}"
DRY_RUN=0
NO_DELETE=0

usage() {
  cat << EOF
Docker-Export für Medien (uploads)

Usage:
  $0 [--container <name>] [--dest <relative-path>] [--dry-run|-n] [--no-delete|-k]

Optionen:
  --container <name>   Docker-Containername (Default: riman-wordpress-swarm-wordpress-1)
  --dest <path>        Zielordner relativ zum Repo (Default: wp-content/uploads-from-docker)
  --dry-run, -n        Vorschau (keine Änderungen, rsync -n -i)
  --no-delete, -k      Additiv synchronisieren (ohne Löschen)

Beispiele:
  $0 --dry-run
  $0                               # Export + Mirror nach ./wp-content/uploads-from-docker
  $0 --no-delete                   # Export + additiv nach ./wp-content/uploads-from-docker
  $0 --container my-wp --dest wp-content/docker-uploads

Hinweis:
  - Exportiert /var/www/html/wp-content/uploads aus dem Container.
  - Entfernt Meta-Dateien (.DS_Store, ._*). Sonst unverändert.
  - Dieser Export berührt NICHT den Server. Zum Deploy siehe: ./scripts/wp-ssh-v2.sh deploy-uploads <dest>
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --container) WORDPRESS_CONTAINER="$2"; shift 2;;
    --dest) DEST_REL="$2"; DEST_ABS="${REPO_ROOT}/${DEST_REL}"; shift 2;;
    --dry-run|-n) DRY_RUN=1; shift;;
    --no-delete|-k) NO_DELETE=1; shift;;
    -h|--help) usage; exit 0;;
    *) echo "Unbekannte Option: $1"; usage; exit 1;;
  esac
done

require_cmd() { command -v "$1" >/dev/null 2>&1 || { echo "❌ Benötigt: $1"; exit 1; }; }
require_cmd docker
require_cmd rsync

# Prüfe Container
if ! docker ps --format '{{.Names}}' | grep -qx "$WORDPRESS_CONTAINER"; then
  echo "❌ Container nicht gefunden: $WORDPRESS_CONTAINER"
  echo "   Starte ihn zuerst oder gib --container <name> an."
  exit 1
fi

TMP_DIR="$(mktemp -d 2>/dev/null || mktemp -d -t uploads-export)"
cleanup() { rm -rf "$TMP_DIR"; }
trap cleanup EXIT

EXPORT_ROOT="$TMP_DIR/uploads"
mkdir -p "$EXPORT_ROOT"

echo "🎯 Container: $WORDPRESS_CONTAINER"
echo "📦 Exportiere: /var/www/html/wp-content/uploads -> $EXPORT_ROOT"
docker cp "$WORDPRESS_CONTAINER:/var/www/html/wp-content/uploads/." "$EXPORT_ROOT/" 1>/dev/null

echo "🧹 Entferne Meta-Dateien ..."
find "$EXPORT_ROOT" -type f \( -name ".DS_Store" -o -name "._*" \) -delete || true

echo "🔄 Sync -> $DEST_REL ($([ $NO_DELETE -eq 1 ] && echo 'additiv' || echo 'mirror'); $([ $DRY_RUN -eq 1 ] && echo 'dry-run' || echo 'live'))"
mkdir -p "$DEST_ABS"
RSYNC_FLAGS="-av"
[ $NO_DELETE -eq 0 ] && RSYNC_FLAGS="$RSYNC_FLAGS --delete --delete-excluded --prune-empty-dirs"
[ $DRY_RUN -eq 1 ] && RSYNC_FLAGS="$RSYNC_FLAGS -n -i"
rsync $RSYNC_FLAGS \
  --exclude='.DS_Store' --exclude='._*' \
  "$EXPORT_ROOT/" "$DEST_ABS/"

if [ $DRY_RUN -eq 1 ]; then
  echo "✅ Dry-Run abgeschlossen (keine Änderungen vorgenommen)"
  exit 0
fi

echo "📊 Ziel-Zusammenfassung:"
COUNT=$(find "$DEST_ABS" -type f | wc -l | tr -d ' ')
SIZE=$(du -sh "$DEST_ABS" | awk '{print $1}')
echo "   Dateien: $COUNT | Größe: $SIZE | Pfad: $DEST_REL"
echo "🎉 Fertig"

