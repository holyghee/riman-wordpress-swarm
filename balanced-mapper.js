#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Load data
const midjpiData = JSON.parse(fs.readFileSync('./midjpi_complete_database.json', 'utf8'));
const existingMappings = JSON.parse(fs.readFileSync('./wordpress-enhanced-image-mappings.json', 'utf8'));

console.log('🚀 Balanced WordPress Image Mapper');
console.log('='.repeat(50));

// Critical rules that MUST be enforced
const CRITICAL_RULES = {
    'mediation': {
        banned_words: ['pcb', 'contamination', 'electrical', 'hazardous', 'chemical', 'toxic'],
        preferred_words: ['consultation', 'meeting', 'professional', 'discussion', 'strategic']
    },
    'pcb': {
        required_words: ['pcb', 'electrical', 'contamination'],
        banned_words: ['mediation', 'consultation', 'meeting']
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

// Enhanced scoring with balanced approach
function scoreImageForContent(contentId, imageAgent) {
    let score = 0;
    const content = contentId.toLowerCase();
    const theme = (imageAgent.theme || '').toLowerCase();
    const desc = (imageAgent.description || '').toLowerCase();
    const imageText = `${theme} ${desc}`;
    
    // CRITICAL RULE ENFORCEMENT
    const isCritical = Object.keys(CRITICAL_RULES).some(rule => content.includes(rule));
    
    if (isCritical) {
        // Apply critical rules strictly
        Object.entries(CRITICAL_RULES).forEach(([key, rules]) => {
            if (content.includes(key)) {
                // Check banned words - strong penalty but not instant disqualification
                if (rules.banned_words && rules.banned_words.some(word => imageText.includes(word))) {
                    score -= 200; // Strong penalty
                    return;
                }
                
                // Check required words
                if (rules.required_words) {
                    const hasRequired = rules.required_words.some(word => imageText.includes(word));
                    if (hasRequired) score += 100;
                    else score -= 50;
                }
                
                // Check preferred words
                if (rules.preferred_words) {
                    rules.preferred_words.forEach(word => {
                        if (imageText.includes(word)) score += 30;
                    });
                }
            }
        });
    }
    
    // General semantic matching for non-critical content
    if (!isCritical || score >= 0) {
        // Service category matching
        if (content.includes('altlastensanierung') && (imageText.includes('environmental') || imageText.includes('remediation') || imageText.includes('groundwater'))) {
            score += 40;
        }
        if (content.includes('rueckbau') && (imageText.includes('demolition') || imageText.includes('deconstruction') || imageText.includes('removal'))) {
            score += 40;
        }
        if (content.includes('sicherheit') && (imageText.includes('safety') || imageText.includes('protection') || imageText.includes('training'))) {
            score += 40;
        }
        if (content.includes('schadstoff') && (imageText.includes('contamination') || imageText.includes('hazardous') || imageText.includes('remediation'))) {
            score += 40;
        }
        if (content.includes('beratung') && (imageText.includes('consultation') || imageText.includes('professional') || imageText.includes('strategic'))) {
            score += 40;
        }
        
        // Keyword matching
        const contentWords = content.split('-').filter(word => word.length > 3);
        contentWords.forEach(word => {
            if (imageText.includes(word)) score += 15;
        });
        
        // Professional context bonus
        if (imageText.includes('professional') || imageText.includes('expert') || imageText.includes('specialist')) {
            score += 20;
        }
    }
    
    return score;
}

// Main processing
const contentFiles = findContentFiles();
const results = {
    description: "Balanced WordPress Mappings - High Coverage with Critical Rule Enforcement",
    version: "4.0-balanced",
    generated: new Date().toISOString(),
    algorithm: "Balanced semantic scoring with critical rule enforcement",
    main_categories: {},
    subcategories: {},
    pages: {},
    validation: { passed: true, critical_violations: [], warnings: [] }
};

console.log(`Processing ${contentFiles.length} content files...`);

const usedImages = new Set();
let mapped = 0;
let criticalViolations = 0;

contentFiles.forEach(file => {
    let bestScore = -Infinity;
    let bestImage = null;
    let bestAgent = null;
    
    // Try to find the best match
    midjpiData.agents.forEach(agent => {
        if (!agent.image_paths || agent.image_paths.length === 0) return;
        
        const score = scoreImageForContent(file.id, agent);
        
        if (agent.image_paths && agent.image_paths[0]) {
            const imagePath = agent.image_paths[0].replace('./images/', '');
            
            // Only consider non-disqualified images
            if (score > -500 && score > bestScore && !usedImages.has(imagePath)) {
                bestScore = score;
                bestImage = imagePath;
                bestAgent = agent;
            }
        }
    });
    
    // Create mapping if we found something reasonable
    if (bestImage && bestAgent && bestScore > -50) {
        usedImages.add(bestImage);
        mapped++;
        
        const mapping = {
            image: bestImage,
            alt: `${file.id.replace(/-/g, ' ')} - RIMAN Professional Services`,
            title: bestAgent.theme || file.id,
            caption: bestAgent.description || `Professional ${file.id} services`,
            description: `${bestAgent.theme} - Expert solutions by RIMAN GmbH`,
            score: bestScore,
            validated: bestScore > 0 ? 'high' : 'medium'
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
        
        const quality = bestScore > 50 ? '🟢' : bestScore > 0 ? '🟡' : '🔴';
        console.log(`${quality} ${file.id}: Score=${bestScore}, Image=${bestImage.substring(0, 40)}...`);
    } else {
        console.log(`❌ ${file.id}: No suitable image found (best score: ${bestScore})`);
    }
});

// Critical validation
console.log('\n🔍 Critical Validation:');
const allMappings = { ...results.main_categories, ...results.subcategories, ...results.pages };

let pcbInMediation = 0;
let mediationOk = 0;
let pcbOk = 0;

Object.entries(allMappings).forEach(([key, value]) => {
    // Check for PCB in mediation (CRITICAL)
    if (key.includes('mediation') || key.includes('baumediation')) {
        const imageText = `${value.image} ${value.title} ${value.description}`.toLowerCase();
        if (imageText.includes('pcb') || imageText.includes('contamination') || imageText.includes('electrical')) {
            pcbInMediation++;
            criticalViolations++;
            results.validation.critical_violations.push(`CRITICAL: PCB content in mediation mapping: ${key}`);
            console.log(`❌ CRITICAL: PCB content in mediation: ${key}`);
        } else {
            mediationOk++;
            console.log(`✅ Mediation OK: ${key}`);
        }
    }
    
    // Check for PCB mappings
    if (key.includes('pcb')) {
        const imageText = `${value.image} ${value.title} ${value.description}`.toLowerCase();
        if (imageText.includes('pcb') || imageText.includes('electrical') || imageText.includes('contamination')) {
            pcbOk++;
            console.log(`✅ PCB has appropriate image: ${key}`);
        } else {
            results.validation.warnings.push(`WARNING: PCB content without PCB imagery: ${key}`);
            console.log(`⚠️  WARNING: PCB without PCB imagery: ${key}`);
        }
    }
});

// Final validation
if (criticalViolations === 0) {
    console.log('\n🎉 CRITICAL VALIDATION PASSED: No PCB in mediation!');
} else {
    results.validation.passed = false;
    console.log(`\n❌ CRITICAL VIOLATIONS: ${criticalViolations}`);
}

// Save results
results.statistics = {
    total_content: contentFiles.length,
    mapped: mapped,
    coverage: Math.round((mapped / contentFiles.length) * 100),
    critical_violations: criticalViolations,
    mediation_mappings_ok: mediationOk,
    pcb_mappings_ok: pcbOk,
    validation_passed: results.validation.passed
};

fs.writeFileSync('./wordpress-balanced-mappings.json', JSON.stringify(results, null, 2));

console.log('\n📊 Final Results:');
console.log(`📁 Total content files: ${results.statistics.total_content}`);
console.log(`🗺️  Successfully mapped: ${results.statistics.mapped} (${results.statistics.coverage}%)`);
console.log(`✅ Mediation OK: ${results.statistics.mediation_mappings_ok}`);
console.log(`⚠️  PCB OK: ${results.statistics.pcb_mappings_ok}`);
console.log(`❌ Critical violations: ${results.statistics.critical_violations}`);
console.log(`🎯 Overall validation: ${results.validation.passed ? 'PASSED' : 'FAILED'}`);
console.log(`💾 Saved to: wordpress-balanced-mappings.json`);

process.exit(results.validation.passed ? 0 : 1);