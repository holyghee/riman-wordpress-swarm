#!/bin/bash
# Erstellt einen eingeschränkten SSH-Key für Claude Code

echo "=== Eingeschränkter SSH-Key für Claude Code ==="
echo ""

# Generate new SSH key for Claude Code
SSH_KEY_PATH="$HOME/.ssh/claude_code_allinkl"

if [ ! -f "$SSH_KEY_PATH" ]; then
    echo "Erstelle neuen SSH-Key..."
    ssh-keygen -t ed25519 -f "$SSH_KEY_PATH" -N "" -C "claude-code-deployment"
    echo ""
fi

echo "=== Öffentlicher Schlüssel für KAS: ==="
cat "${SSH_KEY_PATH}.pub"
echo ""

echo "=== Füge diese Zeile in ~/.ssh/authorized_keys auf dem Server hinzu: ==="
echo ""
echo "command=\"/usr/bin/rsync --server --daemon .\",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty $(cat ${SSH_KEY_PATH}.pub)"
echo ""

echo "=== SSH Config für Claude Code: ==="
cat << EOF

Host allinkl-deploy
    HostName w0181e1b.kasserver.com
    User w0181e1b
    IdentityFile $SSH_KEY_PATH
    StrictHostKeyChecking no
    PreferredAuthentications publickey
EOF