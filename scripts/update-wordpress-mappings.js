#!/usr/bin/env node

/**
 * Update WordPress Enhanced Image Mappings mit optimierten Bildern
 * Ersetzt die alten kryptischen Namen mit den tatsächlich optimierten Bildern
 */

const fs = require('fs');
const path = require('path');

const SUBSERVICES_MAPPING = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/subservices-enhanced-image-mappings.json';
const WORDPRESS_MAPPING = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-enhanced-image-mappings.json';
const OPTIMIZED_IMAGES_DIR = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images/subservices-optimized';

console.log('🔄 Update WordPress Mappings mit optimierten Bildern...\n');

// Lade Subservices Mapping (mit tatsächlich optimierten Bildern)
let subservicesData;
try {
    subservicesData = JSON.parse(fs.readFileSync(SUBSERVICES_MAPPING, 'utf8'));
    console.log('✅ Subservices Mapping geladen');
} catch (error) {
    console.error('❌ Fehler beim Laden der Subservices Mappings:', error.message);
    process.exit(1);
}

// Lade WordPress Mapping (zu aktualisieren)
let wordpressData;
try {
    wordpressData = JSON.parse(fs.readFileSync(WORDPRESS_MAPPING, 'utf8'));
    console.log('✅ WordPress Mapping geladen');
} catch (error) {
    console.error('❌ Fehler beim Laden der WordPress Mappings:', error.message);
    process.exit(1);
}

// Erstelle Mapping von Subservice zu optimiertem Bild mit Metadaten
const optimizedImageMap = {};
const imageMetadata = {};
let mappedCount = 0;

console.log('\n🔍 Erstelle Optimized Image Mapping...');

// Durchsuche alle Services
Object.keys(subservicesData.services).forEach(category => {
    const services = subservicesData.services[category];
    
    Object.keys(services).forEach(subservice => {
        const serviceData = services[subservice];
        
        if (serviceData.optimized_path) {
            // Extrahiere Filename aus optimized_path
            const filename = path.basename(serviceData.optimized_path);
            const key = `${category}-${subservice}`;
            
            // Relativer Pfad für WordPress Mapping
            const relativePath = `subservices-optimized/${category}/${filename}`;
            
            optimizedImageMap[key] = relativePath;
            
            // Erstelle SEO-Metadaten aus Subservice-Daten
            const categoryName = {
                'rueckbau': 'Rückbau',
                'altlasten': 'Altlastensanierung', 
                'schadstoffe': 'Schadstoffmanagement',
                'sicherheit': 'Sicherheitskoordination',
                'beratung': 'Beratung & Mediation'
            }[category] || category;
            
            const subserviceName = {
                'planung': 'Planung',
                'ausschreibung': 'Ausschreibung',
                'durchfuehrung': 'Durchführung',
                'entsorgung': 'Entsorgung',
                'recycling': 'Recycling',
                'dokumentation': 'Dokumentation',
                'erkundung': 'Erkundung',
                'sanierungsplanung': 'Sanierungsplanung',
                'bodensanierung': 'Bodensanierung',
                'grundwassersanierung': 'Grundwassersanierung',
                'monitoring': 'Monitoring',
                'asbest': 'Asbestsanierung',
                'kmf': 'KMF-Sanierung',
                'pak': 'PAK-Sanierung',
                'pcb': 'PCB-Sanierung',
                'schwermetalle': 'Schwermetall-Sanierung',
                'sigeko-planung': 'SiGeKo Planung',
                'sigeko-ausfuehrung': 'SiGeKo Ausführung',
                'arbeitsschutz': 'Arbeitsschutz',
                'gefaehrdungsbeurteilung': 'Gefährdungsbeurteilung',
                'notfallmanagement': 'Notfallmanagement',
                'baumediation': 'Baumediation',
                'projektberatung': 'Projektberatung',
                'gutachten': 'Gutachten',
                'schulungen': 'Schulungen',
                'compliance': 'Compliance'
            }[subservice] || subservice;
            
            imageMetadata[key] = {
                filename: filename,
                confidence: serviceData.new_confidence || serviceData.original_confidence,
                improvement: serviceData.improvement || 0,
                matched_keywords: serviceData.matched_keywords || [],
                alt: `RIMAN ${categoryName} - ${subserviceName} professionell durchgeführt`,
                title: `${subserviceName} von RIMAN GmbH - ${categoryName}`,
                caption: `${subserviceName} durch RIMAN GmbH - Ihr Partner für ${categoryName}`,
                description: `Professionelle ${subserviceName} im Bereich ${categoryName} durch RIMAN GmbH - Deutschlands Experten für Umwelttechnik und Rückbau`,
                category: category,
                subservice: subservice,
                selected_variant: serviceData.selected_variant,
                timestamp: serviceData.timestamp
            };
            
            mappedCount++;
            
            console.log(`  ${key} → ${filename}`);
        }
    });
});

console.log(`\n✅ ${mappedCount} optimierte Bilder gemappt\n`);

// Update WordPress Mappings
console.log('🔄 Update WordPress subcategories...');

let updatedCount = 0;

// Update subcategories
Object.keys(wordpressData.subcategories).forEach(category => {
    const subcategories = wordpressData.subcategories[category];
    
    Object.keys(subcategories).forEach(subservice => {
        const key = `${category}-${subservice}`;
        
        // Skip .md Dateien
        if (subservice.includes('.md')) return;
        
        if (optimizedImageMap[key]) {
            const oldValue = subcategories[subservice];
            subcategories[subservice] = optimizedImageMap[key];
            updatedCount++;
            
            console.log(`  ✅ ${key}: ${path.basename(oldValue)} → ${path.basename(optimizedImageMap[key])}`);
        }
    });
});

// Update main_categories mit ersten Bildern jeder Kategorie
console.log('\n🔄 Update main_categories...');

Object.keys(subservicesData.services).forEach(category => {
    const services = subservicesData.services[category];
    const firstService = Object.values(services)[0];
    
    if (firstService && firstService.optimized_path) {
        const filename = path.basename(firstService.optimized_path);
        const relativePath = `subservices-optimized/${category}/${filename}`;
        
        if (wordpressData.main_categories[category]) {
            const oldValue = wordpressData.main_categories[category];
            wordpressData.main_categories[category] = relativePath;
            console.log(`  ✅ ${category}: ${path.basename(oldValue)} → ${filename}`);
            updatedCount++;
        }
    }
});

// Update image_base_path
console.log('\n🔄 Update image_base_path...');
wordpressData.image_base_path = "/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images";

// Füge Metadaten-Sektion hinzu
wordpressData.seo_metadata = imageMetadata;

// Update Metadaten
wordpressData.last_updated = new Date().toISOString();
wordpressData.version = "enhanced-2.0";
wordpressData.optimized_images_count = mappedCount;
wordpressData.metadata_enabled = true;

console.log(`\n✅ ${updatedCount} WordPress Mappings aktualisiert\n`);

// Erstelle Backup
const backupPath = WORDPRESS_MAPPING + '.backup.' + Date.now();
fs.copyFileSync(WORDPRESS_MAPPING, backupPath);
console.log(`💾 Backup erstellt: ${path.basename(backupPath)}`);

// Speichere aktualisierte WordPress Mappings
fs.writeFileSync(WORDPRESS_MAPPING, JSON.stringify(wordpressData, null, 2));

console.log('✅ WordPress Enhanced Image Mappings aktualisiert!');
console.log(`\n📊 Statistik:`);
console.log(`   SEO-Metadaten: ${Object.keys(imageMetadata).length}`);
console.log(`   Optimierte Bilder: ${mappedCount}`);
console.log(`   Aktualisierte Mappings: ${updatedCount}`);
console.log(`   WordPress Mapping: ${WORDPRESS_MAPPING}`);
console.log(`\n🚀 Das Setup-Script kann jetzt die optimierten Bilder mit Metadaten verwenden!`);
console.log(`\n💡 Verfügbare Metadaten pro Bild:`); 
console.log(`   - Alt-Text für Accessibility`);
console.log(`   - Title für Hover-Text`);
console.log(`   - Caption für Bildunterschrift`);
console.log(`   - Description für Media Library`);
console.log(`   - Confidence-Score und Keywords`);
console.log(`   - Kategorie und Subservice-Zuordnung`);