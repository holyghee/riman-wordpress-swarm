#!/bin/bash

# Korrekte Zuordnung basierend auf den tatsächlichen Generierungen
# Diese Zuordnung wurde aus den Background-Bash-Outputs extrahiert

MIDJOURNEY_DIR="/Users/holgerbrandt/dev/claude-code/tools/midjourney-mcp-server/midjourney-images"
TARGET_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/corrected-images"

# Erstelle Zielverzeichnis
mkdir -p "$TARGET_DIR"

echo "=== Korrigiere Bild-Zuordnungen ==="

# Basierend auf den BashOutput-Analysen:
# Bash 80a5bf (soil remediation) → midjourney_upscaled_1756980995723.png
# Bash f9fc7f (compliance) → midjourney_upscaled_1756981185185.png

# Da ich nicht alle Zuordnungen habe, verwende ich Midjourney's Describe-Funktion
# oder analysiere die Bilder visuell

# Für jetzt: Kopiere die bekannten korrekten Zuordnungen
if [ -f "$MIDJOURNEY_DIR/midjourney_upscaled_1756980995723.png" ]; then
    cp "$MIDJOURNEY_DIR/midjourney_upscaled_1756980995723.png" "$TARGET_DIR/altlasten-bodensanierung_soil-remediation.png"
    echo "✅ Bodensanierung korrekt zugeordnet"
fi

if [ -f "$MIDJOURNEY_DIR/midjourney_upscaled_1756981185185.png" ]; then
    cp "$MIDJOURNEY_DIR/midjourney_upscaled_1756981185185.png" "$TARGET_DIR/beratung-compliance_regulatory-compliance.png"
    echo "✅ Compliance korrekt zugeordnet"
fi

echo ""
echo "Hinweis: Für eine vollständige korrekte Zuordnung müssen alle Background-Prozesse"
echo "analysiert werden oder die Midjourney Describe-Funktion verwendet werden."