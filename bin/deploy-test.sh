#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# deploy-test.sh — Push local changes to test.mppdev.net
#
# FIRST-TIME SETUP (run once):
#   bash bin/deploy-test.sh --init
#
# NORMAL DEPLOY (after that, just):
#   bash bin/deploy-test.sh
# ---------------------------------------------------------------------------

set -euo pipefail

SSH_KEY="$HOME/.ssh/mppdev_key"
SSH_USER="mppdev"
SSH_HOST="35.183.253.194"
REMOTE_PATH="/var/www/vhosts/mppdev.net/test.mppdev.net/wp-content/plugins/wp-cookie-shield"
PHP="/opt/plesk/php/8.2/bin/php"
WPCLI="/usr/local/bin/wp"
WP_PATH="/var/www/vhosts/mppdev.net/test.mppdev.net"

ssh_cmd() {
    ssh -i "$SSH_KEY" "$SSH_USER@$SSH_HOST" "$@"
}

# ── First-time init: replace SCP-deployed files with a git clone ───────────
if [[ "${1:-}" == "--init" ]]; then
    echo "→ First-time setup: cloning repo on server..."

    REPO_URL=$(git remote get-url origin 2>/dev/null || echo "")
    if [[ -z "$REPO_URL" ]]; then
        echo "ERROR: No git remote 'origin' configured locally."
        echo "       Push this repo to GitHub first, then re-run with --init."
        exit 1
    fi

    ssh_cmd "
        set -e
        echo 'Backing up existing plugin directory...'
        if [ -d '$REMOTE_PATH' ]; then
            mv '$REMOTE_PATH' '${REMOTE_PATH}.bak.$(date +%s)'
        fi
        echo 'Cloning $REPO_URL ...'
        git clone '$REPO_URL' '$REMOTE_PATH'
        echo 'Done.'
    "
    echo "✓ Server is now using git. Run 'bash bin/deploy-test.sh' for future updates."
    exit 0
fi

# ── Normal deploy: push local commits then pull on server ──────────────────
echo "→ Pushing to origin..."
# Load PAT from .env if present (keeps credentials out of git config)
ROOT="$(git rev-parse --show-toplevel)"
PAT=""
if [ -f "$ROOT/.env" ]; then
    PAT=$(grep GIT_PAT_CONSENT_PLUGIN "$ROOT/.env" | cut -d= -f2)
fi
if [ -n "$PAT" ]; then
    git -c "url.https://web-mpp:${PAT}@github.com/.insteadOf=https://github.com/" push origin HEAD
else
    git push origin HEAD
fi

echo "→ Pulling on test server..."
ssh_cmd "cd '$REMOTE_PATH' && git pull --ff-only"

echo "✓ Deployed. WordPress picks up PHP changes immediately (no restart needed)."
