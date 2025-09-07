#!/bin/bash

# Organisiere Midjourney-Bilder mit sprechenden Namen
# Basierend auf den generierten Prompts

MIDJOURNEY_DIR="/Users/holgerbrandt/dev/claude-code/tools/midjourney-mcp-server/midjourney-images"
TARGET_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/categorized-images"

# Erstelle Zielverzeichnis
mkdir -p "$TARGET_DIR"

# Hole die neuesten 30 upscaled Bilder und kopiere sie mit sprechenden Namen
echo "=== Organisiere Midjourney-Bilder ==="

# Mapping basierend auf der Reihenfolge der Generierung (neueste zuerst)
# Die Bilder wurden in dieser Reihenfolge generiert:

declare -a mappings=(
    "altlasten-erkundung:contaminated-site-investigation"
    "altlasten-sanierungsplanung:remediation-planning-engineering" 
    "altlasten-bodensanierung:soil-remediation-excavators"
    "altlasten-grundwassersanierung:groundwater-remediation-treatment"
    "altlasten-monitoring:environmental-monitoring-station"
    "rueckbau-dokumentation:project-documentation-digital"
    "rueckbau-recycling:construction-material-recycling"
    "schadstoffe-asbest:asbestos-removal-remediation"
    "schadstoffe-pcb:pcb-contamination-remediation"
    "schadstoffe-pak:pah-hydrocarbon-remediation"
    "schadstoffe-kmf:mineral-fiber-insulation"
    "schadstoffe-schwermetalle:heavy-metal-contamination"
    "sicherheitskoordination-sigeko-planung:safety-planning-office"
    "sicherheitskoordination-sigeko-ausfuehrung:safety-coordination-execution"
    "sicherheitskoordination-gefaehrdungsbeurteilung:risk-assessment-hazard"
    "sicherheitskoordination-arbeitsschutz:workplace-safety-protection"
    "sicherheitskoordination-notfallmanagement:emergency-response-crisis"
    "beratung-projektberatung:construction-project-consulting"
    "beratung-baumediation:construction-mediation-conflict"
    "beratung-gutachten:expert-assessment-evaluation"
    "beratung-compliance:regulatory-compliance-certification"
    "beratung-schulungen:safety-environmental-training"
)

# Hole die neuesten upscaled Bilder
IMAGES=($(ls -t "$MIDJOURNEY_DIR"/*upscaled*.png 2>/dev/null | head -30))

echo "Gefundene Bilder: ${#IMAGES[@]}"

# Ordne die Bilder zu
for i in "${!mappings[@]}"; do
    if [ $i -lt ${#IMAGES[@]} ]; then
        IFS=':' read -r category description <<< "${mappings[$i]}"
        source="${IMAGES[$i]}"
        target="$TARGET_DIR/${category}_${description}.png"
        
        if [ -f "$source" ]; then
            cp "$source" "$target"
            echo "✅ Kopiert: $(basename $source) → ${category}_${description}.png"
        fi
    fi
done

echo ""
echo "=== Bilder organisiert in: $TARGET_DIR ==="
ls -la "$TARGET_DIR"/*.png 2>/dev/null | wc -l
echo "Bilder mit sprechenden Namen erstellt"