#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Load MIDJPI database and current mappings
const midjpiData = JSON.parse(fs.readFileSync('./midjpi_complete_database.json', 'utf8'));
const currentMappings = JSON.parse(fs.readFileSync('./wordpress-enhanced-image-mappings.json', 'utf8'));

console.log('🚀 Intelligent WordPress Image Mapper');
console.log('='.repeat(50));

// Thematic rules
const RULES = {
    'baumediation': {
        must_not_contain: ['pcb', 'contamination', 'chemical', 'hazard'],
        should_contain: ['consultation', 'project', 'mediation', 'professional', 'meeting']
    },
    'pcb': {
        must_contain: ['pcb', 'electrical', 'contamination'],
        must_not_contain: ['mediation', 'consultation']
    }
};

// Find all content files
function findContentFiles() {
    const contentBase = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content';
    const files = [];
    
    function scanDir(dir) {
        const items = fs.readdirSync(dir);
        for (const item of items) {
            const fullPath = path.join(dir, item);
            if (fs.statSync(fullPath).isDirectory()) {
                scanDir(fullPath);
            } else if (item.endsWith('.md')) {
                const relative = path.relative(contentBase, fullPath);
                const id = relative.replace(/\//g, '-').replace('.md', '').replace('services-', '');
                files.push({ id, path: fullPath, relative });
            }
        }
    }
    
    scanDir(contentBase);
    return files;
}

// Score image for content based on semantic rules
function scoreImage(contentId, imageAgent) {
    let score = 0;
    const content = contentId.toLowerCase();
    const theme = (imageAgent.theme || '').toLowerCase();
    const desc = (imageAgent.description || '').toLowerCase();
    const imageText = `${theme} ${desc}`;
    
    // Apply thematic rules
    Object.entries(RULES).forEach(([key, rules]) => {
        if (content.includes(key)) {
            // Must contain rules
            if (rules.must_contain) {
                rules.must_contain.forEach(term => {
                    if (imageText.includes(term)) score += 50;
                    else score -= 20;
                });
            }
            
            // Must not contain rules
            if (rules.must_not_contain) {
                rules.must_not_contain.forEach(term => {
                    if (imageText.includes(term)) score -= 100;
                });
            }
            
            // Should contain rules
            if (rules.should_contain) {
                rules.should_contain.forEach(term => {
                    if (imageText.includes(term)) score += 20;
                });
            }
        }
    });
    
    // Basic keyword matching
    const contentWords = content.split('-');
    contentWords.forEach(word => {
        if (word.length > 3 && imageText.includes(word)) {
            score += 10;
        }
    });
    
    return score;
}

// Main processing
const contentFiles = findContentFiles();
const results = {
    description: "Intelligent WordPress Mappings - Fixed Thematic Issues",
    version: "3.0-intelligent",
    generated: new Date().toISOString(),
    main_categories: {},
    subcategories: {},
    pages: {},
    validation: { passed: true, issues: [] }
};

console.log(`Processing ${contentFiles.length} content files...`);

const usedImages = new Set();
let mapped = 0;

contentFiles.forEach(file => {
    let bestScore = -Infinity;
    let bestImage = null;
    let bestAgent = null;
    
    // Find best matching image
    midjpiData.agents.forEach(agent => {
        if (!agent.image_paths || agent.image_paths.length === 0) return;
        
        const score = scoreImage(file.id, agent);
        if (agent.image_paths && agent.image_paths[0]) {
            const imagePath = agent.image_paths[0].replace('./images/', '');
            
            if (score > bestScore && !usedImages.has(imagePath)) {
                bestScore = score;
                bestImage = imagePath;
                bestAgent = agent;
            }
        }
    });
    
    // Create mapping
    if (bestImage && bestAgent) {
        usedImages.add(bestImage);
        mapped++;
        
        const mapping = {
            image: bestImage,
            alt: `${file.id.replace(/-/g, ' ')} - RIMAN Professional Services`,
            title: bestAgent.theme || file.id,
            caption: bestAgent.description || `Professional ${file.id} services`,
            description: `${bestAgent.theme} - Expert solutions by RIMAN GmbH`,
            score: bestScore
        };
        
        // Categorize
        const parts = file.id.split('-');
        if (parts.length <= 2) {
            results.main_categories[file.id] = mapping;
        } else if (parts.length === 3) {
            results.subcategories[file.id] = mapping;
        } else {
            results.pages[file.id] = mapping;
        }
        
        console.log(`✅ ${file.id}: Score=${bestScore}, Image=${bestImage.substring(0, 40)}...`);
    } else {
        console.log(`❌ ${file.id}: No suitable image found`);
    }
});

// Validation
console.log('\n🔍 Validation:');
const allMappings = { ...results.main_categories, ...results.subcategories, ...results.pages };

let pcbInMediation = 0;
let mediationNoPcb = 0;
let pcbHasPcb = 0;

Object.entries(allMappings).forEach(([key, value]) => {
    if (key.includes('mediation') || key.includes('beratung')) {
        if (value.image.toLowerCase().includes('pcb')) {
            pcbInMediation++;
            results.validation.issues.push(`PCB image in mediation: ${key}`);
        } else {
            mediationNoPcb++;
        }
    }
    
    if (key.includes('pcb')) {
        if (value.image.toLowerCase().includes('pcb')) {
            pcbHasPcb++;
        }
    }
});

console.log(`✅ Mediation ohne PCB: ${mediationNoPcb}`);
console.log(`❌ PCB in Mediation: ${pcbInMediation}`);
console.log(`✅ PCB hat PCB-Bild: ${pcbHasPcb}`);

if (pcbInMediation === 0) {
    console.log('🎉 PERFEKT: Keine PCB-Bilder in Mediation!');
} else {
    results.validation.passed = false;
}

// Save results
results.statistics = {
    total_content: contentFiles.length,
    mapped: mapped,
    coverage: Math.round((mapped / contentFiles.length) * 100),
    validation_passed: results.validation.passed
};

fs.writeFileSync('./wordpress-intelligent-mappings.json', JSON.stringify(results, null, 2));

console.log('\n📊 Ergebnis:');
console.log(`📁 Inhalt: ${results.statistics.total_content}`);
console.log(`🗺️  Gemappt: ${results.statistics.mapped} (${results.statistics.coverage}%)`);
console.log(`✅ Validierung: ${results.validation.passed ? 'BESTANDEN' : 'FEHLGESCHLAGEN'}`);
console.log(`💾 Gespeichert: wordpress-intelligent-mappings.json`);

process.exit(results.validation.passed ? 0 : 1);