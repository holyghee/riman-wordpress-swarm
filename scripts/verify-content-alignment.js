#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Paths
const CONTENT_BASE = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content';
const MAPPINGS_FILE = '../wordpress-enhanced-image-mappings.json';

// Read mappings
const mappings = JSON.parse(fs.readFileSync(path.join(__dirname, MAPPINGS_FILE), 'utf8'));

// Function to find all MD files recursively
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

// Get all content files
const contentFiles = findMDFiles(CONTENT_BASE);

// Extract identifiers from content paths
function extractIdentifier(filePath) {
    const relative = path.relative(CONTENT_BASE, filePath);
    const parts = relative.split(path.sep);
    
    // Build identifier from path
    if (parts[0] === 'services') {
        const service = parts[1];
        const subservice = parts[2];
        const file = path.basename(parts[parts.length - 1], '.md');
        
        if (file === 'main') {
            if (subservice) {
                return `${service}-${subservice}`;
            }
            return service;
        }
        
        if (subservice) {
            return `${service}-${subservice}-${file}`;
        }
        return `${service}-${file}`;
    }
    
    return path.basename(filePath, '.md');
}

// Build content structure map
const contentStructure = {
    services: {},
    pages: [],
    company: []
};

contentFiles.forEach(file => {
    const relative = path.relative(CONTENT_BASE, file);
    const parts = relative.split(path.sep);
    
    if (parts[0] === 'services') {
        const service = parts[1];
        if (!contentStructure.services[service]) {
            contentStructure.services[service] = {
                main: null,
                subservices: {}
            };
        }
        
        if (parts.length === 3 && path.basename(file) === 'main.md') {
            contentStructure.services[service].main = file;
        } else if (parts.length > 3) {
            const subservice = parts[2];
            if (!contentStructure.services[service].subservices[subservice]) {
                contentStructure.services[service].subservices[subservice] = [];
            }
            contentStructure.services[service].subservices[subservice].push(file);
        }
    } else if (parts[0] === 'pages') {
        contentStructure.pages.push(file);
    } else if (parts[0] === 'company') {
        contentStructure.company.push(file);
    }
});

// Analyze alignment
console.log('='.repeat(80));
console.log('CONTENT STRUCTURE ALIGNMENT VERIFICATION');
console.log('='.repeat(80));

console.log('\n📁 CONTENT STRUCTURE OVERVIEW:');
console.log('-'.repeat(40));
console.log(`Total content files: ${contentFiles.length}`);
console.log(`Services: ${Object.keys(contentStructure.services).length}`);
console.log(`Pages: ${contentStructure.pages.length}`);
console.log(`Company: ${contentStructure.company.length}`);

console.log('\n🗂️ SERVICE CATEGORIES:');
console.log('-'.repeat(40));
Object.keys(contentStructure.services).forEach(service => {
    const data = contentStructure.services[service];
    const subserviceCount = Object.keys(data.subservices).length;
    console.log(`\n${service}:`);
    console.log(`  - Subservices: ${subserviceCount}`);
    Object.keys(data.subservices).forEach(sub => {
        console.log(`    • ${sub}: ${data.subservices[sub].length} files`);
    });
});

// Check mapping coverage
console.log('\n📊 MAPPING ALIGNMENT ANALYSIS:');
console.log('-'.repeat(40));

const mappedPages = new Set();
if (mappings.pages) {
    Object.keys(mappings.pages).forEach(page => mappedPages.add(page));
}
if (mappings.subcategories) {
    Object.keys(mappings.subcategories).forEach(sub => mappedPages.add(sub));
}
if (mappings.main_categories) {
    Object.keys(mappings.main_categories).forEach(cat => mappedPages.add(cat));
}

console.log(`\nTotal mapped entries: ${mappedPages.size}`);

// Check each content file for mapping
const mapped = [];
const unmapped = [];

contentFiles.forEach(file => {
    const id = extractIdentifier(file);
    const normalized = id.replace(/_/g, '-').toLowerCase();
    
    // Check various possible mappings
    const found = mappedPages.has(id) || 
                  mappedPages.has(normalized) ||
                  mappedPages.has(id.replace('schadstoff-management', 'schadstoffe')) ||
                  mappedPages.has(id.replace('altlastensanierung', 'altlasten')) ||
                  mappedPages.has(id.replace('rueckbaumanagement', 'rueckbau')) ||
                  mappedPages.has(id.replace('sicherheitskoordination', 'sicherheit')) ||
                  mappedPages.has(id.replace('beratung-mediation', 'beratung'));
    
    if (found) {
        mapped.push({ file, id });
    } else {
        unmapped.push({ file, id });
    }
});

console.log(`\n✅ Mapped content files: ${mapped.length}/${contentFiles.length} (${Math.round(mapped.length/contentFiles.length*100)}%)`);
console.log(`❌ Unmapped content files: ${unmapped.length}`);

if (unmapped.length > 0) {
    console.log('\n⚠️  UNMAPPED CONTENT FILES:');
    console.log('-'.repeat(40));
    unmapped.slice(0, 20).forEach(({ file, id }) => {
        const relative = path.relative(CONTENT_BASE, file);
        console.log(`  - ${relative} (ID: ${id})`);
    });
    if (unmapped.length > 20) {
        console.log(`  ... and ${unmapped.length - 20} more`);
    }
}

// Check critical mappings
console.log('\n🔍 CRITICAL MAPPING VERIFICATION:');
console.log('-'.repeat(40));

const criticalChecks = [
    { id: 'schadstoffe-pcb', shouldContain: 'PCB', shouldNotContain: 'mediation' },
    { id: 'schadstoffe-asbest', shouldContain: 'asbest', shouldNotContain: 'PCB' },
    { id: 'beratung-baumediation', shouldContain: 'mediation', shouldNotContain: 'PCB' },
    { id: 'sicherheit-sigeko-planung', shouldContain: 'safety', shouldNotContain: 'PCB' }
];

criticalChecks.forEach(check => {
    let image = null;
    if (mappings.pages && mappings.pages[check.id]) {
        image = mappings.pages[check.id].image;
    } else if (mappings.subcategories && mappings.subcategories[check.id]) {
        image = mappings.subcategories[check.id].image;
    }
    
    if (image) {
        const correct = (!check.shouldNotContain || !image.toLowerCase().includes(check.shouldNotContain.toLowerCase()));
        const symbol = correct ? '✅' : '❌';
        console.log(`${symbol} ${check.id}: ${image ? image.substring(0, 50) + '...' : 'Not found'}`);
    } else {
        console.log(`⚠️  ${check.id}: No mapping found`);
    }
});

// Summary
console.log('\n' + '='.repeat(80));
console.log('SUMMARY');
console.log('='.repeat(80));
console.log(`\n📈 Alignment Score: ${Math.round(mapped.length/contentFiles.length*100)}%`);
console.log(`📂 Content files found: ${contentFiles.length}`);
console.log(`🗺️  Mappings created: ${mappedPages.size}`);
console.log(`✅ Successfully mapped: ${mapped.length}`);
console.log(`⚠️  Needs attention: ${unmapped.length}`);

// Recommendations
if (unmapped.length > 0) {
    console.log('\n💡 RECOMMENDATIONS:');
    console.log('-'.repeat(40));
    console.log('1. Review unmapped files to determine if they need images');
    console.log('2. Some files may be utility/index files that don\'t need images');
    console.log('3. Consider generating images for important unmapped content');
}

console.log('\n✨ Verification complete!');