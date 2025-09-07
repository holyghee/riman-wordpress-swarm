#!/bin/bash
# Codex Commit Helper: Commit (and optionally push) with repo-level preferences from .codex.yml
set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || true)"
if [ -z "$ROOT_DIR" ]; then
  echo "❌ Not in a Git repository" >&2
  exit 1
fi
cd "$ROOT_DIR"

PUSH=0
MESSAGE=""

usage(){
  cat <<EOF
Usage: $0 [-m "message"] [--push|--no-push]

Options:
  -m, --message   Commit message (without prefix); required unless --stage-only
  --push          Push to default branch after commit
  --no-push       Do not push (overrides config)
  --stage-only    Only stage changes according to config, no commit

Examples:
  $0 --message "feat: update blocks"
  $0 --message "fix: nav spacing" --push
  $0 --stage-only   # just stage changes, commit yourself
EOF
}

STAGE_ONLY=0
NO_PUSH=0
while [[ $# -gt 0 ]]; do
  case "$1" in
    -m|--message) MESSAGE="$2"; shift 2;;
    --push) PUSH=1; shift;;
    --no-push) NO_PUSH=1; shift;;
    --stage-only) STAGE_ONLY=1; shift;;
    -h|--help) usage; exit 0;;
    *) echo "Unknown option: $1"; usage; exit 1;;
  esac
done

# Read minimal prefs from .codex.yml
CFG=".codex.yml"
get_cfg(){
  local key="$1"
  local val
  val=$(sed -nE "s/^\s*${key}:\s*\"?([^\"]*)\"?\s*$/\1/p" "$CFG" 2>/dev/null | head -1 || true)
  echo "$val"
}

DEFAULT_BRANCH=$(get_cfg 'codex:\s*\n\s*default_branch')
DEFAULT_BRANCH=${DEFAULT_BRANCH:-$(sed -nE 's/^\s*default_branch:\s*"?([^"\n]+)"?\s*$/\1/p' "$CFG" 2>/dev/null | head -1 || echo main)}
PREFIX=$(sed -nE 's/^\s*commit_prefix:\s*"?([^"\n]+)"?\s*$/\1/p' "$CFG" 2>/dev/null | head -1 || echo "chore(codex)")
INCLUDE_FILES=$(sed -nE 's/^\s*include_file_list:\s*([^\n]+)\s*$/\1/p' "$CFG" 2>/dev/null | head -1 || echo true)
COMMIT_SCOPE=$(sed -nE 's/^\s*commit_scope:\s*([^\n]+)\s*$/\1/p' "$CFG" 2>/dev/null | head -1 || echo all)
ADD_ALL=$(sed -nE 's/^\s*add_all_by_default:\s*([^\n]+)\s*$/\1/p' "$CFG" 2>/dev/null | head -1 || echo true)
PUSH_BY_DEFAULT_RAW=$(sed -nE 's/^\s*push_by_default:\s*([^\n]+)\s*$/\1/p' "$CFG" 2>/dev/null | head -1 || echo false)

# Decide push behavior based on flags and config
if [ "$NO_PUSH" -eq 1 ]; then
  PUSH=0
elif [ "$PUSH" -eq 0 ]; then
  case "$PUSH_BY_DEFAULT_RAW" in
    true|True|TRUE|1|yes|Yes) PUSH=1 ;;
    *) PUSH=0 ;;
  esac
fi

# Stage according to config
if [ "$STAGE_ONLY" -eq 1 ] || [ -n "$MESSAGE" ] || [ "$ADD_ALL" = "true" ]; then
  git add -A
fi

if [ "$STAGE_ONLY" -eq 1 ]; then
  echo "📝 Staged changes. You can now run git commit manually."
  exit 0
fi

if [ -z "$MESSAGE" ]; then
  echo "❌ Missing commit message. Use -m \"message\"" >&2
  exit 1
fi

# Build commit message
TS=$(date '+%Y-%m-%d %H:%M:%S %Z')
MSG_PREFIX="$PREFIX"
FINAL_MSG="$MSG_PREFIX: $MESSAGE"
if [ "$INCLUDE_FILES" = "true" ]; then
  FILES=$(git diff --cached --name-only | tr '\n' ' ' | sed 's/ *$//')
  [ -n "$FILES" ] && FINAL_MSG="$FINAL_MSG\n\nFiles: $FILES"
fi
FINAL_MSG="$FINAL_MSG\n\nTime: $TS"

# If nothing staged, stage per scope
if git diff --cached --quiet; then
  if [ "$COMMIT_SCOPE" = "all" ]; then
    git add -A
  fi
fi

if git diff --cached --quiet; then
  echo "ℹ️  No staged changes. Nothing to commit."
  exit 0
fi

git commit -m "$FINAL_MSG"

if [ "$PUSH" -eq 1 ]; then
  BR=$(git rev-parse --abbrev-ref HEAD)
  BR=${BR:-$DEFAULT_BRANCH}
  echo "🚀 Push: origin/$BR"
  git push origin "$BR"
fi

echo "✅ Commit created"
