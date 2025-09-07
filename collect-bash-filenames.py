#!/usr/bin/env python3

import json

# Mapping of bash IDs to categories based on the prompts
bash_to_category = {
    "eefad8": {"category": "altlasten-erkundung", "keyword": "contaminated-site-investigation"},
    "42bdb3": {"category": "altlasten-sanierungsplanung", "keyword": "remediation-planning-engineering"},
    "80a5bf": {"category": "bodensanierung", "keyword": "soil-remediation"},
    "584db7": {"category": "grundwassersanierung", "keyword": "groundwater-remediation"},
    "a39f9e": {"category": "altlasten-monitoring", "keyword": "environmental-monitoring"},
    "eb0eec": {"category": "dokumentation", "keyword": "project-documentation"},
    "b2d88e": {"category": "recycling", "keyword": "material-recycling"},
    "abfb15": {"category": "asbestsanierung", "keyword": "asbestos-removal"},
    "9ff906": {"category": "pcb-sanierung", "keyword": "pcb-contamination"},
    "cd5c2d": {"category": "pak-sanierung", "keyword": "pah-hydrocarbons"},
    "2163c3": {"category": "kmf-sanierung", "keyword": "mineral-fiber"},
    "5d702f": {"category": "schwermetalle", "keyword": "heavy-metal"},
    "f793b9": {"category": "sigeko-planung", "keyword": "safety-planning"},
    "326e9d": {"category": "sigeko-ausfuehrung", "keyword": "safety-coordination"},
    "fd8160": {"category": "gefaehrdungsbeurteilung", "keyword": "risk-assessment"},
    "654521": {"category": "arbeitsschutz", "keyword": "workplace-safety"},
    "472380": {"category": "notfallmanagement", "keyword": "emergency-response"},
    "6a68d5": {"category": "projektberatung", "keyword": "construction-consulting"},
    "f04077": {"category": "baumediation", "keyword": "mediation-conflict"},
    "2ab255": {"category": "gutachten", "keyword": "expert-assessment"},
    "f9fc7f": {"category": "compliance", "keyword": "regulatory-compliance"},
    "d3c0f3": {"category": "schulungen", "keyword": "training-workshop"}
}

# Filenames collected from bash outputs (we'll update this with actual data)
bash_filenames = {
    "80a5bf": "midjourney_upscaled_1756980995723.png",
    "f9fc7f": "midjourney_upscaled_1756981185185.png"
}

print("#!/bin/bash")
print("# Auto-generated script to collect bash process filenames")
print("# Run BashOutput commands to get the saved filenames")
print()
print("echo '=== Collecting filenames from bash processes ==='")
print()

for bash_id in bash_to_category:
    print(f"echo 'Checking bash {bash_id} ({bash_to_category[bash_id][\"category\"]})...'")
    print(f"# Run: BashOutput bash_id={bash_id} filter=midjourney_upscaled")
    print()

print()
print("# Known mappings so far:")
for bash_id, filename in bash_filenames.items():
    cat_info = bash_to_category.get(bash_id, {})
    print(f"# {bash_id} ({cat_info.get('category', 'unknown')}) -> {filename}")