#!/bin/bash
# SSH Wrapper für All-Inkl Zugriff
# WICHTIG: Passwort in .env.local speichern

# Load environment variables
if [ -f "$(dirname "$0")/../.env.local" ]; then
    source "$(dirname "$0")/../.env.local"
fi

if [ -z "$KAS_PASSWORD" ]; then
    echo "Error: KAS_PASSWORD not set in .env.local"
    echo "Create .env.local with: KAS_PASSWORD='your-password'"
    exit 1
fi

# SSH connection with sshpass
if command -v sshpass &> /dev/null; then
    sshpass -p "$KAS_PASSWORD" ssh -o StrictHostKeyChecking=no w0181e1b@w0181e1b.kasserver.com "$@"
else
    echo "sshpass not installed. Installing..."
    brew install hudochenkov/sshpass/sshpass 2>/dev/null || echo "Please install sshpass manually"
fi