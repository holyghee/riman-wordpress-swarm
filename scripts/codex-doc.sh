#!/bin/bash
# Append a structured entry to docs/CHANGELOG.md based on a summary and staged/working changes
set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || true)"
if [ -z "$ROOT_DIR" ]; then
  echo "❌ Not in a Git repository" >&2
  exit 1
fi
cd "$ROOT_DIR"

SUMMARY=""
SCOPE="staged"  # staged|working

usage(){
  cat <<EOF
Usage: $0 --summary "Kurze Beschreibung" [--scope staged|working]

Schreibt einen Eintrag an das Ende von docs/CHANGELOG.md:
- Datum (UTC)
- Zusammenfassung
- Liste der Dateien (je nach Scope)

Beispiele:
  $0 --summary "Deploy-Skripte ergänzt und Medien-Import automatisiert"
  $0 --summary "Theme Hotfix" --scope working
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --summary) SUMMARY="$2"; shift 2;;
    --scope) SCOPE="$2"; shift 2;;
    -h|--help) usage; exit 0;;
    *) echo "Unknown option: $1"; usage; exit 1;;
  esac
done

if [ -z "$SUMMARY" ]; then
  echo "❌ Missing --summary" >&2
  exit 1
fi

CHANGELOG="docs/CHANGELOG.md"
[ -f "$CHANGELOG" ] || echo "# Changelog" > "$CHANGELOG"

# Collect files
if [ "$SCOPE" = "staged" ]; then
  FILES=$(git diff --cached --name-only)
else
  FILES=$(git diff --name-only)
fi

TS=$(date -u '+%Y-%m-%d %H:%M:%S UTC')

{
  echo "\n## $TS – $SUMMARY"
  if [ -n "$FILES" ]; then
    echo "\nGeänderte Dateien:"
    echo "$FILES" | sed 's/^/- /'
  else
    echo "\n(Keine Dateiänderungen im gewählten Scope gefunden)"
  fi
} >> "$CHANGELOG"

echo "✅ CHANGELOG aktualisiert: $CHANGELOG"

