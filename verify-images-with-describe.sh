#!/bin/bash

# Verwende Midjourney's Describe-Funktion um die Bilder zu verifizieren
# Dadurch können wir sicherstellen, dass die richtige Zuordnung erfolgt

MIDJOURNEY_DIR="/Users/holgerbrandt/dev/claude-code/tools/midjourney-mcp-server/midjourney-images"
MCP_SERVER_DIR="/Users/holgerbrandt/dev/claude-code/tools/midjourney-mcp-server"

echo "=== Verifiziere Bilder mit Midjourney Describe ==="
echo ""

# Die zwei bekannten Beispiele
echo "Bekannte falsche Zuordnung:"
echo "----------------------------"
echo "midjourney_upscaled_1756980995723.png wurde fälschlich Compliance zugeordnet"
echo "-> Tatsächlicher Inhalt: Soil Remediation (Bodensanierung)"
echo ""
echo "midjourney_upscaled_1756981185185.png wurde fälschlich Bodensanierung zugeordnet"  
echo "-> Tatsächlicher Inhalt: Regulatory Compliance"
echo ""

# Statt Discord/MIDJPI zu verwenden, nutzen wir die direkte Zuordnung aus den Bash Prozessen
echo "Lösung: Extrahiere die Dateinamen direkt aus den Background Bash Prozessen"
echo "Jeder Prozess generiert genau ein Bild mit einem eindeutigen Dateinamen"
echo ""

echo "Nächste Schritte:"
echo "1. Alle Bash Prozesse nach ihren generierten Dateinamen abfragen"
echo "2. Eine korrekte Mapping-Tabelle erstellen"
echo "3. Die Bilder mit den richtigen Namen kopieren"
echo "4. In WordPress importieren mit korrekter Zuordnung"