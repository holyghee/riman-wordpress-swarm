#!/bin/bash
# Simple background autosave for Git while working with Codex CLI (or any CLI).
# Commits only when there are staged changes; stages all tracked/untracked (respecting .gitignore).

set -euo pipefail

INTERVAL="${INTERVAL:-30}"           # seconds between checks
PREFIX="${PREFIX:-chore(codex): autosave}"  # commit message prefix
SHOW_FILES="${SHOW_FILES:-1}"        # include changed files in message (0/1)

ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || true)"
if [ -z "$ROOT_DIR" ]; then
  echo "❌ Not in a Git repository" >&2
  exit 1
fi
cd "$ROOT_DIR"

echo "📝 Codex autosave running in: $ROOT_DIR (interval=${INTERVAL}s)"
echo "    Stop with CTRL+C"

while true; do
  # Stage all changes (respects .gitignore)
  git add -A >/dev/null 2>&1 || true

  # If nothing staged, skip
  if git diff --cached --quiet; then
    sleep "$INTERVAL"; continue
  fi

  # Build commit message
  TS=$(date '+%Y-%m-%d %H:%M:%S %Z')
  MSG="$PREFIX — $TS"
  if [ "$SHOW_FILES" = "1" ]; then
    FILES=$(git diff --cached --name-only | tr '\n' ' ' | sed 's/ *$//')
    [ -n "$FILES" ] && MSG="$MSG — $FILES"
  fi

  # Create commit on current branch
  git commit -m "$MSG" >/dev/null 2>&1 || true
  echo "✅ Commit: $MSG"

  sleep "$INTERVAL"
done

