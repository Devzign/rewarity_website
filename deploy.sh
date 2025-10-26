#!/usr/bin/env bash
set -euo pipefail

# ==========================================================
# 🚀 Simple rsync deployment script for Hostinger over SSH
# ==========================================================
# Usage:
#   ./deploy.sh                    # real deploy
#   ./deploy.sh --dry-run          # preview only
# Environment overrides:
#   DEPLOY_REMOTE_DIR=/abs/remote/dir
#   DEPLOY_PATH="user@host:/abs/remote/dir"
# ==========================================================

HOST="217.21.95.207"
PORT="65002"
USER="u788248317"

# ✅ Hostinger primary domain public_html (as shown in your File Manager)
REMOTE_DIR="${DEPLOY_REMOTE_DIR:-/home/u788248317/public_html/}"

# Optional override via DEPLOY_PATH (e.g., user@host:/path)
if [[ -n "${DEPLOY_PATH:-}" ]]; then
  TARGET_USERHOST="${DEPLOY_PATH%%:*}"
  TARGET_PATH="${DEPLOY_PATH#*:}"

  if [[ "$TARGET_USERHOST" == *"@"* ]]; then
    USER="${TARGET_USERHOST%@*}"
    HOST="${TARGET_USERHOST#*@}"
  else
    HOST="$TARGET_USERHOST"
  fi

  if [[ -n "$TARGET_PATH" && "$TARGET_PATH" != "$TARGET_USERHOST" ]]; then
    REMOTE_DIR="$TARGET_PATH"
  fi
fi

DRY_RUN=0

# -------------------------------------
# Parse Arguments
# -------------------------------------
for arg in "$@"; do
  case "$arg" in
    --dry-run) DRY_RUN=1; shift ;;
    --remote-dir=*) REMOTE_DIR="${arg#*=}"; shift ;;
    --host=*) HOST="${arg#*=}"; shift ;;
    --port=*) PORT="${arg#*=}"; shift ;;
    --user=*) USER="${arg#*=}"; shift ;;
    *) ;;
  esac
done

# -------------------------------------
# Sanity Checks
# -------------------------------------
if ! command -v rsync >/dev/null 2>&1; then
  echo -e "\033[0;31m❌ Error:\033[0m rsync not installed. Please install rsync first." >&2
  exit 1
fi

EXCLUDE_FILE=".deployignore"
RSYNC_FLAGS=(-avz --delete --human-readable --progress)

# Protect server-only config files from deletion/overwrite
RSYNC_FLAGS+=(--filter='P includes/env.php' --filter='P includes/env.local.php')

if [[ -f "$EXCLUDE_FILE" ]]; then
  RSYNC_FLAGS+=(--exclude-from="$EXCLUDE_FILE")
else
  echo -e "\033[0;33m⚠️  Warning:\033[0m .deployignore not found; proceeding without excludes."
fi

SSH_CMD=(ssh -p "$PORT" -o StrictHostKeyChecking=no)

# -------------------------------------
# Deploy Execution
# -------------------------------------
if [[ $DRY_RUN -eq 1 ]]; then
  RSYNC_FLAGS+=(-n)
  echo -e "\033[0;36m🔍 Performing dry-run deploy to\033[0m $USER@$HOST:$REMOTE_DIR"
else
  echo -e "\033[0;32m🚀 Deploying to\033[0m $USER@$HOST:$REMOTE_DIR"
fi

# Ensure the remote directory exists (create if missing)
"${SSH_CMD[@]}" "$USER@$HOST" "mkdir -p '$REMOTE_DIR'" || {
  echo -e "\033[0;31m❌ Failed to create remote directory: $REMOTE_DIR\033[0m"
  exit 1
}

# Run rsync deployment
rsync "${RSYNC_FLAGS[@]}" \
  -e "${SSH_CMD[*]}" \
  ./ "$USER@$HOST:$REMOTE_DIR"

echo -e "\033[0;32m✅ Deployment completed successfully!\033[0m"
