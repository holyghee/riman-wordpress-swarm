#!/bin/bash
set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || true)"
[ -n "$ROOT_DIR" ] && cd "$ROOT_DIR"

echo "================ Codex Onboarding ================"
echo "Repo: $(basename "$ROOT_DIR")"
echo "Branch: $(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo '-')"
echo "--------------------------------------------------"
echo "# CODEX.md (Kurzanleitung)"
echo
sed -n '1,200p' docs/CODEX.md 2>/dev/null || echo "(docs/CODEX.md fehlt)"
echo
echo "# SSH-DEPLOYMENT.md (Befehle)"
echo
sed -n '1,160p' docs/SSH-DEPLOYMENT.md 2>/dev/null || echo "(docs/SSH-DEPLOYMENT.md fehlt)"
echo
echo "# .codex.yml (Commit/Push-Prefs)"
echo
sed -n '1,120p' .codex.yml 2>/dev/null || echo "(.codex.yml fehlt)"
echo "=================================================="

