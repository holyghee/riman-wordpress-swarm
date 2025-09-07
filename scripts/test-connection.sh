#!/bin/bash
# Test SSH connection to All-Inkl

source "$(dirname "$0")/../.env.local" 2>/dev/null

if [ -z "$KAS_PASSWORD" ]; then
    echo "⚠️  Bitte erst .env.local konfigurieren:"
    echo ""
    echo "1. Öffne .env.local"
    echo "2. Setze KAS_PASSWORD='dein-passwort'"
    echo "3. Speichere die Datei"
    echo ""
    exit 1
fi

echo "🔌 Teste Verbindung zu All-Inkl..."
echo ""

# Test with sshpass
if command -v sshpass &> /dev/null; then
    sshpass -p "$KAS_PASSWORD" ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR w0181e1b@w0181e1b.kasserver.com 'echo "✅ Verbindung erfolgreich!" && echo "" && echo "Server Info:" && echo "------------" && uname -a && echo "" && echo "PHP Version:" && php -v | head -1 && echo "" && echo "WordPress Pfad:" && ls -la /www/htdocs/w0181e1b/ | head -5'
else
    echo "❌ sshpass nicht installiert"
    echo "Installiere mit: brew install hudochenkov/sshpass/sshpass"
fi