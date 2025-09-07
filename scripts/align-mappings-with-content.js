#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Paths
const CONTENT_BASE = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content';
const CURRENT_MAPPINGS = '../wordpress-enhanced-image-mappings.json';
const MIDJPI_DB = '../midjpi_complete_database.json';
const OUTPUT_FILE = '../wordpress-content-aligned-mappings.json';

// Load data
const currentMappings = JSON.parse(fs.readFileSync(path.join(__dirname, CURRENT_MAPPINGS), 'utf8'));
const midjpiDb = JSON.parse(fs.readFileSync(path.join(__dirname, MIDJPI_DB), 'utf8'));

// Service name mappings
const SERVICE_MAPPINGS = {
    'altlastensanierung': 'altlasten',
    'rueckbaumanagement': 'rueckbau',
    'schadstoff-management': 'schadstoffe',
    'sicherheitskoordination': 'sicherheit',
    'beratung-mediation': 'beratung'
};

// Find all content files
function findMDFiles(dir, files = []) {
    const items = fs.readdirSync(dir);
    for (const item of items) {
        const fullPath = path.join(dir, item);
        const stat = fs.statSync(fullPath);
        if (stat.isDirectory()) {
            findMDFiles(fullPath, files);
        } else if (item.endsWith('.md')) {
            files.push(fullPath);
        }
    }
    return files;
}

// Get best matching image for content
function getBestImage(contentPath, contentId) {
    // Check existing mappings first
    if (currentMappings.pages && currentMappings.pages[contentId]) {
        return currentMappings.pages[contentId];
    }
    
    // Try shortened service names
    const parts = contentId.split('-');
    const service = parts[0];
    const shortService = SERVICE_MAPPINGS[service] || service;
    const shortId = [shortService, ...parts.slice(1)].join('-');
    
    if (currentMappings.pages && currentMappings.pages[shortId]) {
        return currentMappings.pages[shortId];
    }
    
    // Try subcategories
    if (currentMappings.subcategories && currentMappings.subcategories[shortId]) {
        return currentMappings.subcategories[shortId];
    }
    
    // Try main categories
    if (currentMappings.main_categories && currentMappings.main_categories[shortService]) {
        return currentMappings.main_categories[shortService];
    }
    
    // Semantic matching fallback
    const keywords = extractKeywords(contentPath);
    return findSemanticMatch(keywords);
}

// Extract keywords from path
function extractKeywords(filePath) {
    const relative = path.relative(CONTENT_BASE, filePath);
    const parts = relative.split(path.sep);
    const keywords = [];
    
    parts.forEach(part => {
        const cleaned = part.replace('.md', '').replace(/-/g, ' ');
        if (cleaned !== 'main' && cleaned !== 'services') {
            keywords.push(cleaned);
        }
    });
    
    return keywords;
}

// Find semantic match in MIDJPI database
function findSemanticMatch(keywords) {
    let bestMatch = null;
    let bestScore = 0;
    
    midjpiDb.agents.forEach(agent => {
        if (!agent.image_paths || agent.image_paths.length === 0) return;
        
        const theme = agent.theme || '';
        const desc = agent.description || '';
        const combined = `${theme} ${desc}`.toLowerCase();
        
        let score = 0;
        keywords.forEach(keyword => {
            if (combined.includes(keyword.toLowerCase())) {
                score += keyword.length;
            }
        });
        
        if (score > bestScore && agent.image_paths && agent.image_paths[0]) {
            bestScore = score;
            const imagePath = agent.image_paths[0].replace('./images/', '');
            bestMatch = {
                image: imagePath,
                alt: `${agent.theme} - Professionelle Dienstleistung`,
                title: agent.theme,
                caption: agent.description,
                description: `Hochwertige Visualisierung für ${keywords.join(' ')}`
            };
        }
    });
    
    return bestMatch;
}

// Build content structure
const contentFiles = findMDFiles(CONTENT_BASE);
const alignedMappings = {
    description: "WordPress Content-Aligned Image Mappings - Vollständige Abdeckung aller Inhalte",
    version: "content-aligned-1.0",
    generated: new Date().toISOString(),
    alignment: {
        content_path: CONTENT_BASE,
        total_content_files: contentFiles.length,
        mapping_strategy: "Full content structure alignment with semantic fallback"
    },
    main_categories: {},
    subcategories: {},
    pages: {},
    statistics: {
        total_mappings: 0,
        from_existing: 0,
        semantic_matches: 0,
        unmapped: 0
    }
};

// Process each content file
contentFiles.forEach(file => {
    const relative = path.relative(CONTENT_BASE, file);
    const parts = relative.split(path.sep);
    
    if (parts[0] === 'services') {
        const service = parts[1];
        const shortService = SERVICE_MAPPINGS[service] || service;
        
        // Main service file
        if (parts.length === 2 || (parts.length === 3 && path.basename(file) === 'main.md')) {
            const mapping = getBestImage(file, service);
            if (mapping) {
                alignedMappings.main_categories[service] = mapping;
                alignedMappings.statistics.total_mappings++;
                if (mapping) alignedMappings.statistics.from_existing++;
            }
        }
        // Subservice files
        else if (parts.length >= 3) {
            const subservice = parts[2];
            const filename = path.basename(file, '.md');
            
            // Subservice main
            if (filename === 'main') {
                const id = `${service}-${subservice}`;
                const mapping = getBestImage(file, id);
                if (mapping) {
                    alignedMappings.subcategories[id] = mapping;
                    alignedMappings.statistics.total_mappings++;
                }
            }
            // Individual pages
            else {
                const id = parts.length === 3 ? 
                    `${service}-${filename}` : 
                    `${service}-${subservice}-${filename}`;
                const mapping = getBestImage(file, id);
                if (mapping) {
                    alignedMappings.pages[id] = mapping;
                    alignedMappings.statistics.total_mappings++;
                }
            }
        }
    }
    // Pages and company files
    else if (parts[0] === 'pages' || parts[0] === 'company') {
        const id = path.basename(file, '.md');
        const mapping = getBestImage(file, id);
        if (mapping) {
            alignedMappings.pages[id] = mapping;
            alignedMappings.statistics.total_mappings++;
        }
    }
});

// Add summary
alignedMappings.statistics.unmapped = contentFiles.length - alignedMappings.statistics.total_mappings;
alignedMappings.statistics.coverage_percentage = Math.round(
    (alignedMappings.statistics.total_mappings / contentFiles.length) * 100
);

// Write output
fs.writeFileSync(
    path.join(__dirname, OUTPUT_FILE),
    JSON.stringify(alignedMappings, null, 2)
);

// Report
console.log('='.repeat(80));
console.log('CONTENT-ALIGNED MAPPING GENERATION COMPLETE');
console.log('='.repeat(80));
console.log(`\n📂 Content files processed: ${contentFiles.length}`);
console.log(`🗺️  Mappings created: ${alignedMappings.statistics.total_mappings}`);
console.log(`✅ Coverage: ${alignedMappings.statistics.coverage_percentage}%`);
console.log(`📁 Main categories: ${Object.keys(alignedMappings.main_categories).length}`);
console.log(`📂 Subcategories: ${Object.keys(alignedMappings.subcategories).length}`);
console.log(`📄 Pages: ${Object.keys(alignedMappings.pages).length}`);

console.log('\n✨ Output saved to:', OUTPUT_FILE);

// Show sample mappings
console.log('\n📋 SAMPLE MAPPINGS:');
console.log('-'.repeat(40));
const samples = Object.entries(alignedMappings.pages).slice(0, 5);
samples.forEach(([id, mapping]) => {
    console.log(`${id}: ${mapping.image.substring(0, 50)}...`);
});