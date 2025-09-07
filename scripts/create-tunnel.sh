#!/bin/bash
# Erstellt einen SSH-Tunnel für Claude Code

echo "=== SSH Tunnel für Claude Code ==="
echo ""
echo "Dieser Tunnel ermöglicht lokalen Zugriff ohne direkte Credentials"
echo ""

# Tunnel configuration
LOCAL_PORT=2222
REMOTE_HOST=w0181e1b.kasserver.com
REMOTE_USER=w0181e1b

echo "Starte SSH-Tunnel auf localhost:$LOCAL_PORT..."
echo "Du musst einmalig dein Passwort eingeben."
echo ""

# Create tunnel
ssh -N -L $LOCAL_PORT:localhost:22 $REMOTE_USER@$REMOTE_HOST &
TUNNEL_PID=$!

echo "Tunnel gestartet mit PID: $TUNNEL_PID"
echo ""
echo "Claude Code kann jetzt verbinden mit:"
echo "ssh -p $LOCAL_PORT localhost"
echo ""
echo "Tunnel beenden mit: kill $TUNNEL_PID"

# Save PID for later
echo $TUNNEL_PID > .tunnel.pid