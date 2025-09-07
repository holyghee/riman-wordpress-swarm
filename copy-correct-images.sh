#!/bin/bash

# Copy Midjourney images with correct names based on bash process outputs
# This ensures each category gets the right image based on the actual generation

SOURCE_DIR="/Users/holgerbrandt/dev/claude-code/tools/midjourney-mcp-server/midjourney-images"
DEST_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/categorized-images"

echo "=== Copying images with correct category mapping ==="
echo ""

# Create destination directory if it doesn't exist
mkdir -p "$DEST_DIR"

# Remove old incorrectly named files
echo "Removing old incorrectly named images..."
rm -f "$DEST_DIR"/*.png

echo ""
echo "Copying images with correct names based on bash process outputs:"
echo ""

# Copy each file with its correct name based on the bash process that generated it
declare -a mappings=(
    "midjourney_upscaled_1756980979254.png:altlasten-erkundung_contaminated-site-investigation.png"
    "midjourney_upscaled_1756980985972.png:altlasten-sanierungsplanung_remediation-planning-engineering.png"
    "midjourney_upscaled_1756980995723.png:altlasten-bodensanierung_soil-remediation.png"
    "midjourney_upscaled_1756981018755.png:altlasten-grundwassersanierung_groundwater-remediation.png"
    "midjourney_upscaled_1756981011403.png:altlasten-monitoring_environmental-monitoring.png"
    "midjourney_upscaled_1756981040549.png:rueckbau-dokumentation_project-documentation.png"
    "midjourney_upscaled_1756981024733.png:rueckbau-recycling_material-recycling.png"
    "midjourney_upscaled_1756981050685.png:schadstoffe-asbest_asbestos-removal.png"
    "midjourney_upscaled_1756981054301.png:schadstoffe-pcb_pcb-contamination.png"
    "midjourney_upscaled_1756981075809.png:schadstoffe-pak_pah-hydrocarbons.png"
    "midjourney_upscaled_1756981087262.png:schadstoffe-kmf_mineral-fiber.png"
    "midjourney_upscaled_1756981104576.png:schadstoffe-schwermetalle_heavy-metal.png"
    "midjourney_upscaled_1756981090397.png:sicherheitskoordination-sigeko-planung_safety-planning.png"
    "midjourney_upscaled_1756981118393.png:sicherheitskoordination-sigeko-ausfuehrung_safety-coordination.png"
    "midjourney_upscaled_1756981120421.png:sicherheitskoordination-gefaehrdungsbeurteilung_risk-assessment.png"
    "midjourney_upscaled_1756981135339.png:sicherheitskoordination-arbeitsschutz_workplace-safety.png"
    "midjourney_upscaled_1756981152714.png:sicherheitskoordination-notfallmanagement_emergency-response.png"
    "midjourney_upscaled_1756981167863.png:beratung-projektberatung_construction-consulting.png"
    "midjourney_upscaled_1756981153032.png:beratung-baumediation_mediation-conflict.png"
    "midjourney_upscaled_1756981181993.png:beratung-gutachten_expert-assessment.png"
    "midjourney_upscaled_1756981185185.png:beratung-compliance_regulatory-compliance.png"
    "midjourney_upscaled_1756981199360.png:beratung-schulungen_training-workshop.png"
)

for mapping in "${mappings[@]}"; do
    IFS=':' read -r source_file dest_file <<< "$mapping"
    
    if [ -f "$SOURCE_DIR/$source_file" ]; then
        cp "$SOURCE_DIR/$source_file" "$DEST_DIR/$dest_file"
        echo "✅ Copied: $source_file -> $dest_file"
    else
        echo "⚠️  Not found: $source_file"
    fi
done

echo ""
echo "=== Copy complete ==="
echo ""
echo "Listing categorized images:"
ls -la "$DEST_DIR"/*.png | head -25