#!/bin/bash
# Test SFTP access to All-Inkl

source "$(dirname "$0")/../.env.local" 2>/dev/null

if [ -z "$KAS_PASSWORD" ]; then
    echo "Error: Please configure .env.local first"
    exit 1
fi

echo "🔍 Testing SFTP access..."
echo ""

# Test SFTP connection
echo "Listing WordPress directory via SFTP:"
sshpass -p "$KAS_PASSWORD" sftp -o StrictHostKeyChecking=no w0181e1b@w0181e1b.kasserver.com <<EOF 2>/dev/null
cd /www/htdocs/w0181e1b/
ls -la
pwd
bye
EOF

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ SFTP access works!"
    echo ""
    echo "You can use SFTP for file operations:"
    echo "- Upload files"
    echo "- Download backups"
    echo "- Manage WordPress files"
else
    echo "❌ SFTP access failed"
fi