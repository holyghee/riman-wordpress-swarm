#!/usr/bin/env node
/**
 * Fixes wordpress-enhanced-image-mappings-seo.json by:
 * - Replacing non-existent images with available ones
 * - Avoiding duplicate image assignments across different pages
 * - Filling missing special page mapping for "ueber-uns"
 * - Using domain-specific image pools per category for better thematic matches
 */

const fs = require('fs');
const path = require('path');

const ROOT = process.cwd();
const IMAGES_DIR = path.join(ROOT, 'images');
const INPUT = path.join(ROOT, 'wordpress-enhanced-image-mappings-seo.json');
const OUTPUT = path.join(ROOT, 'wordpress-enhanced-image-mappings-seo.fixed.json');

function readJSON(file) {
  return JSON.parse(fs.readFileSync(file, 'utf8'));
}

function listImages(dir) {
  const files = fs.readdirSync(dir, { withFileTypes: true });
  return files
    .filter((ent) => ent.isFile())
    .map((ent) => ent.name)
    .filter((f) => /(png|jpg|jpeg|webp)$/i.test(f));
}

function pickFirstAvailable(candidates, available, used) {
  for (const pat of candidates) {
    const re = typeof pat === 'string' ? new RegExp(pat.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i') : pat;
    const found = Array.from(available).filter((f) => re.test(f));
    for (const f of found) {
      if (!used.has(f)) return f;
    }
  }
  return null;
}

function dedupePush(arr, item) {
  if (!arr.includes(item)) arr.push(item);
}

function main() {
  if (!fs.existsSync(INPUT)) {
    console.error(`❌ Mapping not found: ${INPUT}`);
    process.exit(1);
  }
  if (!fs.existsSync(IMAGES_DIR)) {
    console.error(`❌ Images dir not found: ${IMAGES_DIR}`);
    process.exit(1);
  }

  const mapping = readJSON(INPUT);
  const images = new Set(listImages(IMAGES_DIR));
  const used = new Set();
  const fixes = [];
  const missing = [];
  const duplicates = [];

  const ensureImage = (slug, current, candidates) => {
    // If current exists and not used, keep
    if (current && images.has(current) && !used.has(current)) {
      used.add(current);
      return current;
    }
    // Pick first available from candidates
    const choice = pickFirstAvailable(candidates, images, used);
    if (choice) {
      used.add(choice);
      dedupePush(fixes, `${slug} → ${choice}`);
      return choice;
    }
    // As last resort, keep current if exists (even if duplicate)
    if (current && images.has(current)) {
      if (used.has(current)) {
        dedupePush(duplicates, `${slug} → ${current}`);
      } else {
        used.add(current);
      }
      return current;
    }
    dedupePush(missing, `${slug} (no suitable image)`);
    return current || null;
  };

  // Pools by topic
  const pools = {
    rueckbau: {
      main: [/Systematic_building_deconstruction/i, /Professional_demolition_planning_scene/i],
      planung: [/Professional_demolition_planning_scene/i],
      ausschreibung: [/Professional_demolition_management_and_recycling_opera/i],
      durchfuehrung: [/Massive_construction_site_with_selective_demolition/i],
      entsorgung: [/Construction_material_recycling_facility/i],
      recycling: [/Construction_material_recycling_facility/i],
      dokumentation: [/Professional_project_documentation_and_digital_archivi/i],
    },
    altlasten: {
      main: [/Professional_remediation_planning_and_engineering/i],
      erkundung: [/Hazardous_material_laboratory_analysis/i, /hazardous_material_management_facility/i],
      sanierungsplanung: [/Professional_remediation_planning_and_engineering/i],
      bodensanierung: [/Professional_soil_remediation_with_excavators/i, /contaminated_soil_remediation_site/i],
      grundwassersanierung: [/Groundwater_remediation_treatment_plant/i, /Groundwater_remediation_site/i],
      monitoring: [/Risk_assessment_and_hazard_evaluation_documentation/i],
    },
    schadstoffe: {
      main: [/hazardous_material_management_facility/i],
      asbest: [/Professional_asbestos_removal_and_remediation/i],
      pcb: [/PCB_contamination_remediation/i],
      pak: [/PAH_polycyclic_aromatic_hydrocarbons_remediation_site/i],
      kmf: [/Industrial_decontamination_facility/i],
      schwermetalle: [/Heavy_metal_contamination_analysis_and_remediation/i],
    },
    sicherheit: {
      main: [/Construction_site_safety_coordination/i, /Construction_safety_planning_office/i],
      'sigeko-planung': [/Construction_safety_planning_office/i],
      'sigeko-ausfuehrung': [/Safety_coordination_during_construction_execution/i, /Construction_site_safety_coordination/i],
      arbeitsschutz: [/Workplace_safety_and_occupational_health_protection/i, /Safety_equipment_preparation_area/i],
      gefaehrdungsbeurteilung: [/Risk_assessment_and_hazard_evaluation_documentation/i],
      notfallmanagement: [/Emergency_response_and_crisis_management_center/i],
    },
    beratung: {
      main: [/Professional_mediation_meeting_in_modern_conference_ro/i, /Construction_mediation_and_conflict_resolution/i],
      baumediation: [/Professional_mediation_meeting_in_modern_conference_ro/i, /Construction_mediation_and_conflict_resolution/i],
      projektberatung: [/Professional_construction_project_consulting_office/i],
      gutachten: [/Risk_assessment_and_hazard_evaluation_documentation/i],
      schulungen: [/Professional_safety_and_environmental_training_classro/i],
      compliance: [/Regulatory_compliance_and_certification_documentation/i],
    },
    pages: {
      startseite: [/Professional_construction_mediation_and_consulting_mee/i],
      kontakt: [/Professional_building_signage_for_RIMAN_GmbH/i],
      impressum: [/Professional_building_signage_for_RIMAN_GmbH/i],
      'ueber-uns': [/Professional_female_project_manager_in_safety_vest/i, /Professional_safety_and_environmental_training_classro/i],
    },
  };

  // Helper to ensure unique variant for same base topic (e.g., recycling 1/2)
  function rotateChoice(baseCandidates, count) {
    const found = [];
    for (const re of baseCandidates) {
      found.push(...Array.from(images).filter((f) => re.test(f)));
    }
    // Return list of distinct images, prefer different quadrant suffixes
    return found.slice(0, count);
  }

  // Fix main categories
  if (mapping.main_categories) {
    for (const [key, data] of Object.entries(mapping.main_categories)) {
      const pool = pools[key]?.main;
      if (!pool) continue;
      data.image = ensureImage(key, data.image, pool);
    }
  }

  // Fix subcategories
  if (mapping.subcategories) {
    for (const [parent, subs] of Object.entries(mapping.subcategories)) {
      for (const [subkey, data] of Object.entries(subs)) {
        const slug = data.slug || `${parent}-${subkey}`;
        const root = parent;
        const leaf = subkey;
        const pool = pools[root]?.[leaf] || pools[root]?.main || [];

        // Special handling for pairs that belong together (entsorgung/recycling)
        if (root === 'rueckbau' && (leaf === 'entsorgung' || leaf === 'recycling')) {
          const seq = rotateChoice([/Construction_material_recycling_facility/i], 4);
          data.image = ensureImage(slug, data.image, seq.map((s) => new RegExp(s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))));
        } else if (root === 'altlasten' && (leaf === 'bodensanierung' || leaf === 'grundwassersanierung')) {
          const seq = leaf === 'bodensanierung'
            ? rotateChoice([/Professional_soil_remediation_with_excavators/i, /contaminated_soil_remediation_site/i], 4)
            : rotateChoice([/Groundwater_remediation_treatment_plant/i, /Groundwater_remediation_site/i], 4);
          data.image = ensureImage(slug, data.image, seq.map((s) => new RegExp(s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))));
        } else if (root === 'beratung' && leaf === 'baumediation') {
          const seq = rotateChoice([
            /Professional_mediation_meeting_in_modern_conference_ro/i,
            /Construction_mediation_and_conflict_resolution/i,
            /Professional_mediation_meeting_scene_with_diverse_stak/i,
          ], 12);
          data.image = ensureImage(slug, data.image, seq.map((s) => new RegExp(s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))));
        } else {
          data.image = ensureImage(slug, data.image, pool);
        }
      }
    }
  }

  // Fix special pages; add ueber-uns if missing
  mapping.pages = mapping.pages || {};
  const pagesSpec = mapping.pages;
  const pageDefs = [
    { key: 'startseite', pool: pools.pages.startseite, name: 'Startseite' },
    { key: 'kontakt', pool: pools.pages.kontakt, name: 'Kontakt' },
    { key: 'impressum', pool: pools.pages.impressum, name: 'Impressum' },
    { key: 'ueber-uns', pool: pools.pages['ueber-uns'], name: 'Über uns' },
  ];
  for (const p of pageDefs) {
    pagesSpec[p.key] = pagesSpec[p.key] || { slug: p.key, name: p.name };
    pagesSpec[p.key].slug = p.key;
    pagesSpec[p.key].image = ensureImage(`page:${p.key}`, pagesSpec[p.key].image, p.pool);
  }

  // Save output
  mapping.generated = new Date().toISOString();
  mapping.notes = {
    fixed_by: 'scripts/fix-seo-mapping.js',
    fixes,
    missing,
    duplicates,
    total_images: images.size,
  };

  fs.writeFileSync(OUTPUT, JSON.stringify(mapping, null, 2), 'utf8');
  console.log(`✅ Wrote fixed mapping: ${OUTPUT}`);
  console.log(`🔧 Fixes: ${fixes.length}, ⚠️ Missing: ${missing.length}, 🚫 Duplicates kept: ${duplicates.length}`);
  if (fixes.length) console.log('Examples:', fixes.slice(0, 10));
}

main();

