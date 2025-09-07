#!/bin/bash
# Check shell restrictions on All-Inkl

source "$(dirname "$0")/../.env.local" 2>/dev/null

if [ -z "$KAS_PASSWORD" ]; then
    echo "Error: Please configure .env.local first"
    exit 1
fi

echo "🔍 Checking shell access..."
echo ""

# Try different shell commands
echo "1. Testing basic echo:"
sshpass -p "$KAS_PASSWORD" ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR w0181e1b@w0181e1b.kasserver.com '/bin/echo "Hello"' 2>&1 || echo "Failed"

echo ""
echo "2. Testing bash explicitly:"
sshpass -p "$KAS_PASSWORD" ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR w0181e1b@w0181e1b.kasserver.com '/bin/bash -c "echo Hello from bash"' 2>&1 || echo "Failed"

echo ""
echo "3. Testing sh shell:"
sshpass -p "$KAS_PASSWORD" ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR w0181e1b@w0181e1b.kasserver.com '/bin/sh -c "echo Hello from sh"' 2>&1 || echo "Failed"

echo ""
echo "4. Testing which shells are available:"
sshpass -p "$KAS_PASSWORD" ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR w0181e1b@w0181e1b.kasserver.com 'which bash sh zsh 2>/dev/null' 2>&1 || echo "Failed"

echo ""
echo "5. Testing PHP CLI:"
sshpass -p "$KAS_PASSWORD" ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR w0181e1b@w0181e1b.kasserver.com 'php -r "echo \"PHP works\\n\";"' 2>&1 || echo "Failed"