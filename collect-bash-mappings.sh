#!/bin/bash

# Sammle die korrekten Zuordnungen aus den Background Bash Prozessen
# Jeder Prozess hat eine ID und einen spezifischen Prompt

echo "=== Sammle korrekte Bild-Zuordnungen ==="
echo ""
echo "# Mapping von Bash ID zu Kategorie (basierend auf Prompts)"
echo ""

# Format: bash_id:category_slug:keyword_from_prompt
declare -a mappings=(
    "eefad8:altlasten-erkundung:contaminated-site-investigation"
    "42bdb3:sanierungsplanung:remediation-planning-engineering"  
    "80a5bf:bodensanierung:soil-remediation"
    "584db7:grundwassersanierung:groundwater-remediation"
    "a39f9e:altlasten-monitoring:environmental-monitoring"
    "eb0eec:dokumentation:project-documentation"
    "b2d88e:recycling:material-recycling"
    "abfb15:asbestsanierung:asbestos-removal"
    "9ff906:pcb-sanierung:pcb-contamination"
    "cd5c2d:pak-sanierung:pah-hydrocarbons"
    "2163c3:kmf-sanierung:mineral-fiber"
    "5d702f:schwermetalle:heavy-metal"
    "f793b9:sigeko-planung:safety-planning"
    "326e9d:sigeko-ausfuehrung:safety-coordination"
    "fd8160:gefaehrdungsbeurteilung:risk-assessment"
    "654521:arbeitsschutz:workplace-safety"
    "472380:notfallmanagement:emergency-response"
    "6a68d5:projektberatung:construction-consulting"
    "f04077:baumediation:mediation-conflict"
    "2ab255:gutachten:expert-assessment"
    "f9fc7f:compliance:regulatory-compliance"
    "d3c0f3:schulungen:training-workshop"
)

echo "Bash ID | Kategorie | Keywords"
echo "--------|-----------|----------"
for mapping in "${mappings[@]}"; do
    IFS=':' read -r bash_id category keywords <<< "$mapping"
    echo "$bash_id | $category | $keywords"
done

echo ""
echo "Diese Zuordnungen basieren auf den tatsächlichen Prompts der Background Prozesse."