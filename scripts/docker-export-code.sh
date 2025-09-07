#!/bin/bash
# Exportiere nur den WordPress-Code (Themes/Plugins) aus dem Docker-Container
# und synchronisiere ihn in dein Repo unter ./wp-content (ohne Uploads).
# Optionales Commit/Push nur für ./wp-content, damit keine Dev-Skripte mitkommen.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="${SCRIPT_DIR}/.."

# Defaults
WORDPRESS_CONTAINER=${WORDPRESS_CONTAINER:-riman-wordpress-swarm-wordpress-1}
DRY_RUN=0
NO_DELETE=0
DO_PUSH=0
# Optional individuelle Commit-Message für --push
COMMIT_MSG=""
# Standard: ALLE Themes/Plugins exportieren (Core-Defaults ausgeschlossen)
EXPORT_ALL=1
BRANCH=${BRANCH:-main}

# Allowlist-Muster (leerzeichengetrennt)
THEME_PATTERNS=${THEME_PATTERNS:-"riman-*"}
PLUGIN_PATTERNS=${PLUGIN_PATTERNS:-"riman-* category-* navigation-* auto-populate-* wordpress-mcp category-section-customizer navigation-subcategories-* add-shortcode-to-category.php category-hero-plugin.php category-page-connector.php"}

usage() {
  cat << EOF
Docker-Export nur für WordPress-Code (ohne Uploads)

Usage:
  $0 [--container <name>] [--dry-run|-n] [--no-delete|-k] [--push] [--branch <branch>]
     [--message "<commit message>"] [--themes "pattern1 pattern2"] [--plugins "pattern1 pattern2"] [--allowlist]

Beispiele:
  $0 --dry-run                         # Vorschau der Änderungen an ./wp-content
  $0                                   # Mirror-Code aus Container -> ./wp-content
  $0 --no-delete                       # Additiv (keine Löschungen)
  $0 --push                            # Commit + Push nur für ./wp-content
  $0 --push --message "feat: update"   # Eigene Commit-Message
  $0 --allowlist                       # Statt ALL den Muster-Filter verwenden

Hinweis:
  - Es werden NUR Themes/Plugins aus dem Container exportiert (ohne uploads).
  - Es werden dev-Dateien ausgeschlossen (node_modules, .git, *.log, *.map, tests).
  - Mit --push werden nur Pfade unter ./wp-content committed/gepusht.
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --container) WORDPRESS_CONTAINER="$2"; shift 2;;
    --dry-run|-n) DRY_RUN=1; shift;;
    --no-delete|-k) NO_DELETE=1; shift;;
    --push) DO_PUSH=1; shift;;
    --branch) BRANCH="$2"; shift 2;;
    --themes) THEME_PATTERNS="$2"; shift 2;;
    --plugins) PLUGIN_PATTERNS="$2"; shift 2;;
    --message) COMMIT_MSG="$2"; shift 2;;
    --allowlist) EXPORT_ALL=0; shift;;
    --commit) DO_COMMIT=1; shift;;
    --stage-only) STAGE_ONLY=1; shift;;
    -h|--help) usage; exit 0;;
    *) echo "Unbekannte Option: $1"; usage; exit 1;;
  esac
done

require_cmd() { command -v "$1" >/dev/null 2>&1 || { echo "❌ Benötigt: $1"; exit 1; }; }
require_cmd docker
require_cmd rsync
require_cmd git

# Prüfe Container
docker ps --format '{{.Names}}' | grep -qx "$WORDPRESS_CONTAINER" || {
  echo "❌ Container nicht gefunden: $WORDPRESS_CONTAINER"; exit 1;
}

TMP_DIR="$(mktemp -d 2>/dev/null || mktemp -d -t export)"
cleanup() { rm -rf "$TMP_DIR"; }
trap cleanup EXIT

EXPORT_ROOT="$TMP_DIR/wp-content"
mkdir -p "$EXPORT_ROOT/themes" "$EXPORT_ROOT/plugins"

echo "🎯 Container: $WORDPRESS_CONTAINER"
echo "📦 Export nach: $EXPORT_ROOT"
echo "⚙️  Modus: $([ $EXPORT_ALL -eq 1 ] && echo 'ALL (Standard) – alle Themes/Plugins, Core-Defaults ausgeschlossen' || echo 'Allowlist') | Sync: $([ $NO_DELETE -eq 1 ] && echo 'additiv' || echo 'mirror') | $([ $DRY_RUN -eq 1 ] && echo 'dry-run' || echo 'live')"
if [ $EXPORT_ALL -ne 1 ]; then
  echo "   Themes:  $THEME_PATTERNS"
  echo "   Plugins: $PLUGIN_PATTERNS"
fi

# Export Themes
echo "🎨 Exportiere Themes..."
if [ "$EXPORT_ALL" -eq 1 ]; then
  THEME_PATTERNS="*"
fi
for pat in $THEME_PATTERNS; do
  docker exec "$WORDPRESS_CONTAINER" bash -lc "shopt -s nullglob; for d in /var/www/html/wp-content/themes/$pat; do [ -d \"\$d\" ] && echo \"\$d\"; done" \
    | while read -r theme_dir; do
        tname="$(basename "$theme_dir")"
        # Standard-Themes auslassen (twenty*), ABER twentytwentyfive (2025) explizit erlauben
        if [[ "$tname" == index.php ]]; then
          continue
        fi
        if [[ "$tname" == twenty* ]] && [[ "$tname" != "twentytwentyfive" ]]; then
          continue
        fi
        echo "  → $tname"
        docker cp "$WORDPRESS_CONTAINER:$theme_dir" "$EXPORT_ROOT/themes/" 2>/dev/null || true
      done
done

# Export Plugins
echo "🔌 Exportiere Plugins..."
if [ "$EXPORT_ALL" -eq 1 ]; then
  PLUGIN_PATTERNS="*"
fi
for pat in $PLUGIN_PATTERNS; do
  docker exec "$WORDPRESS_CONTAINER" bash -lc "shopt -s nullglob; for d in /var/www/html/wp-content/plugins/$pat; do [ -e \"\$d\" ] && echo \"\$d\"; done" \
    | while read -r plug_path; do
        base="$(basename "$plug_path")"
        # Standard-Plugins/Kennzeichner auslassen
        case "$base" in akismet|hello.php|index.php) continue;; esac
        if docker exec "$WORDPRESS_CONTAINER" test -f "$plug_path"; then
          echo "  → file: $base"
          mkdir -p "$EXPORT_ROOT/plugins"
          docker cp "$WORDPRESS_CONTAINER:$plug_path" "$EXPORT_ROOT/plugins/"
        else
          echo "  → dir: $base"
          docker cp "$WORDPRESS_CONTAINER:$plug_path" "$EXPORT_ROOT/plugins/" 2>/dev/null || true
        fi
      done
done

# Dev-/Build-Dateien entfernen
echo "🧹 Entferne Dev-/Build-Dateien aus Export..."
find "$EXPORT_ROOT" -type d \( -name node_modules -o -name .git -o -name tests -o -name .cache \) -prune -exec rm -rf {} +
find "$EXPORT_ROOT" -type f \( -name "*.map" -o -name "*.log" -o -name ".DS_Store" -o -name "._*" \) -delete || true

# Zusammenfassung anzeigen
echo "📄 Zusammenfassung des Exports:"
if [ -d "$EXPORT_ROOT/themes" ]; then
  echo "  Themes:"; ls -1 "$EXPORT_ROOT/themes" 2>/dev/null || true
fi
if [ -d "$EXPORT_ROOT/plugins" ]; then
  echo "  Plugins (Dateien/Ordner):"; ls -1 "$EXPORT_ROOT/plugins" 2>/dev/null || true
fi

# Sync in Repo
echo "🔄 Synchronisiere in Repo: ./wp-content"
RSYNC_FLAGS="-avz"
[ $NO_DELETE -eq 0 ] && RSYNC_FLAGS="$RSYNC_FLAGS --delete"
[ $DRY_RUN -eq 1 ] && RSYNC_FLAGS="$RSYNC_FLAGS -n -i"

mkdir -p "$REPO_ROOT/wp-content/themes" "$REPO_ROOT/wp-content/plugins"
rsync $RSYNC_FLAGS \
  --exclude='node_modules/' --exclude='.git/' --exclude='*.log' --exclude='*.map' --exclude='tests/' \
  "$EXPORT_ROOT/" "$REPO_ROOT/wp-content/"

if [ $DRY_RUN -eq 1 ]; then
  echo "✅ Dry-Run abgeschlossen (keine Änderungen vorgenommen)"
  exit 0
fi

# Optional Commit/Push nur für ./wp-content
# Optional Commit/Push nur für ./wp-content
if [ $STAGE_ONLY -eq 1 ]; then
  cd "$REPO_ROOT"
  echo "📝 Nur Staging: wp-content in den Index aufnehmen"
  git add wp-content
  echo "ℹ️  Staged. Du kannst jetzt weitere Pfade adden und selbst committen/pushen."
  exit 0
fi

if [ $DO_PUSH -eq 1 ] || [ ${DO_COMMIT:-0} -eq 1 ]; then
  cd "$REPO_ROOT"
  echo "📝 Git commit nur für ./wp-content"
  git add wp-content
  if git diff --staged --quiet; then
    echo "ℹ️  Keine Änderungen in wp-content"
    exit 0
  fi
  if [ -z "$COMMIT_MSG" ]; then
    COMMIT_MSG="chore: export code from container (themes/plugins)"
  fi
  git commit -m "$COMMIT_MSG"
  if [ $DO_PUSH -eq 1 ]; then
    echo "🚀 Push nach origin/$BRANCH"
    git push origin "$BRANCH"
    echo "✅ Push abgeschlossen"
  else
    echo "✅ Commit erstellt (kein Push). Du kannst jetzt weitere Änderungen committen oder pushen."
  fi
else
  echo "ℹ️  Änderungen wurden in ./wp-content synchronisiert. Prüfe diff und committe bei Bedarf:"
  echo "   git add wp-content && git commit -m 'chore: export code from container' && git push"
fi

echo "🎉 Fertig"
