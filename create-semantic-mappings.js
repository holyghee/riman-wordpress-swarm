#!/usr/bin/env node

/**
 * Semantische WordPress Bildzuordnung ohne Duplikate
 * Für RIMAN GmbH Content-to-Image Mapping
 */

const fs = require('fs');
const path = require('path');

// Konfiguration
const CONTENT_DIR = process.env.CONTENT_DIR || 
  '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content';
const IMAGES_DIR = process.env.IMAGES_DIR || 
  (require('path').join(process.cwd(), 'images'));
const DATABASE_PATH = process.env.DATABASE_PATH || 
  (require('path').join(process.cwd(), 'midjpi_complete_database.json'));
const REFERENCE_FILE = './wordpress-enhanced-image-mappings-seo.json';
const OUTPUT_FILE = './wordpress-semantic-unique-mappings.json';

// Thematische Kategorien mit Schlüsselwörtern
const THEMATIC_RULES = {
    'pcb': {
        must_match: ['pcb', 'polychlorinated', 'electrical', 'contamination', 'transformer'],
        forbidden: ['mediation', 'consultation', 'meeting', 'discussion', 'conflict'],
        weight: 100
    },
    'asbest': {
        must_match: ['asbestos', 'fiber', 'insulation', 'removal', 'protective'],
        forbidden: ['pcb', 'mediation', 'electrical'],
        weight: 90
    },
    'baumediation': {
        must_match: ['mediation', 'consultation', 'meeting', 'project', 'discussion', 'strategic'],
        forbidden: ['pcb', 'asbestos', 'contamination', 'hazardous', 'chemical', 'toxic'],
        weight: 95
    },
    'altlasten': {
        must_match: ['soil', 'groundwater', 'environmental', 'remediation', 'monitoring'],
        forbidden: ['pcb', 'asbestos', 'mediation'],
        weight: 80
    },
    'rueckbau': {
        must_match: ['demolition', 'deconstruction', 'building', 'recycling', 'disposal'],
        forbidden: ['mediation', 'consultation'],
        weight: 75
    },
    'sicherheit': {
        must_match: ['safety', 'coordination', 'protection', 'emergency', 'training'],
        forbidden: ['contamination', 'mediation'],
        weight: 70
    },
    // Ergänzung: allgemeine Beratungs-Services (ohne explizites 'baumediation')
    'beratung': {
        must_match: ['consulting', 'consultation', 'project', 'meeting', 'advisory', 'documentation', 'compliance'],
        forbidden: ['pcb', 'asbestos', 'contamination', 'hazardous', 'demolition'],
        weight: 65
    }
};

class SemanticImageMapper {
    constructor() {
        this.database = null;
        this.contentFiles = [];
        this.availableImages = [];
        this.usedImages = new Set();
        this.mappings = {};
        this.referenceStructure = null;
    }

    // Lade alle Daten
    loadData() {
        console.log('📂 Loading data...');
        
        // MIDJPI Database laden
        this.database = JSON.parse(fs.readFileSync(DATABASE_PATH, 'utf8'));
        console.log(`   - Database: ${this.database.agents.length} agents loaded`);
        
        // Verfügbare Bilder scannen
        this.availableImages = fs.readdirSync(IMAGES_DIR)
            .filter(f => /(\.png|\.jpg|\.jpeg|\.webp)$/i.test(f))
            .map(f => path.parse(f).name);
        console.log(`   - Images: ${this.availableImages.length} images found`);
        
        // Content-Dateien finden
        this.contentFiles = this.findContentFiles(CONTENT_DIR);
        console.log(`   - Content: ${this.contentFiles.length} files found`);
        
        // Referenz-Struktur laden
        if (fs.existsSync(REFERENCE_FILE)) {
            this.referenceStructure = JSON.parse(fs.readFileSync(REFERENCE_FILE, 'utf8'));
            console.log('   - Reference structure loaded');
        }
    }

    // Finde alle Content-Dateien rekursiv
    findContentFiles(dir) {
        let files = [];
        const items = fs.readdirSync(dir);
        
        for (const item of items) {
            const fullPath = path.join(dir, item);
            if (fs.statSync(fullPath).isDirectory()) {
                files = files.concat(this.findContentFiles(fullPath));
            } else if (item.endsWith('.md')) {
                files.push(fullPath);
            }
        }
        
        return files;
    }

    // Extrahiere Kontext aus Dateipfad
    extractContext(filePath) {
        const relativePath = path.relative(CONTENT_DIR, filePath);
        const parts = relativePath.split(path.sep);
        const fileName = path.basename(filePath, '.md');
        
        let category = 'general';
        let service = '';
        let keywords = [];
        
        if (parts[0] === 'services' && parts.length > 1) {
            service = parts[1];
            
            // Service-Kategorien bestimmen
            if (service === 'schadstoff-management') category = 'schadstoffe';
            else if (service === 'beratung-mediation') category = 'beratung';
            else if (service === 'altlastensanierung') category = 'altlasten';
            else if (service === 'rueckbaumanagement') category = 'rueckbau';
            else if (service === 'sicherheitskoordination') category = 'sicherheit';
            
            // Alle Pfad-Teile als Keywords sammeln
            parts.slice(1).forEach(part => {
                if (part !== 'main.md') {
                    keywords.push(part.replace('.md', '').replace(/-/g, ' '));
                }
            });
        }
        
        // Spezielle Kategorien erkennen
        const identifier = relativePath.replace(/\//g, '-').replace('.md', '');
        if (identifier.includes('pcb')) category = 'pcb';
        else if (identifier.includes('asbest')) category = 'asbest';
        else if (identifier.includes('baumediation')) category = 'baumediation';
        
        return {
            filePath,
            relativePath,
            identifier: identifier.replace('services-', ''),
            category,
            service,
            keywords,
            fileName
        };
    }

    // Berechne semantischen Score zwischen Content und Bild
    calculateSemanticScore(context, agent) {
        const theme = (agent.theme || '').toLowerCase();
        const description = (agent.description || '').toLowerCase();
        const combined = `${theme} ${description}`;
        
        let score = 0;
        
        // Thematische Regeln anwenden
        const rules = THEMATIC_RULES[context.category];
        if (rules) {
            // Must-match Keywords
            let mustMatchCount = 0;
            rules.must_match.forEach(keyword => {
                if (combined.includes(keyword.toLowerCase())) {
                    score += rules.weight;
                    mustMatchCount++;
                }
            });
            
            // Bonus für mehrere Must-matches
            if (mustMatchCount > 1) score += 20;
            
            // Forbidden Keywords (kritisch!)
            rules.forbidden.forEach(keyword => {
                if (combined.includes(keyword.toLowerCase())) {
                    score -= 200; // Sehr starke Bestrafung
                }
            });
        }
        
        // Keyword-Matching aus Kontext
        context.keywords.forEach(keyword => {
            if (combined.includes(keyword.toLowerCase())) {
                score += 10;
            }
        });
        
        // Spezielle kritische Validierung
        if (context.identifier.includes('pcb') && !combined.includes('pcb')) {
            score -= 150;
        }
        if (context.identifier.includes('mediation') && combined.includes('pcb')) {
            score -= 300; // Absolut inakzeptabel
        }
        
        return score;
    }

    // Finde bestes verfügbares Bild
    findBestImage(context) {
        let bestMatch = null;
        let bestScore = -Infinity;
        let bestAgent = null;
        
        // Durchsuche alle Agents in der Database
        this.database.agents.forEach(agent => {
            if (!agent.image_paths) return;
            
            const score = this.calculateSemanticScore(context, agent);
            
            // Finde unused Quadrant
            Object.values(agent.image_paths).forEach(imagePath => {
                const imageBaseName = path.basename(imagePath, path.extname(imagePath));
                
                // Prüfe ob Bild verfügbar und unbenutzt
                if (this.availableImages.includes(imageBaseName) && 
                    !this.usedImages.has(imageBaseName) && 
                    score > bestScore) {
                    
                    bestScore = score;
                    bestMatch = imageBaseName;
                    bestAgent = agent;
                }
            });
        });
        
        // Falls kein ungenutztes Bild gefunden, nimm bestes auch wenn verwendet
        if (!bestMatch) {
            this.database.agents.forEach(agent => {
                if (!agent.image_paths) return;
                
                const score = this.calculateSemanticScore(context, agent);
                
                Object.values(agent.image_paths).forEach(imagePath => {
                    const imageBaseName = path.basename(imagePath, path.extname(imagePath));
                    
                    if (this.availableImages.includes(imageBaseName) && score > bestScore) {
                        bestScore = score;
                        bestMatch = imageBaseName;
                        bestAgent = agent;
                    }
                });
            });
        }
        
        if (bestMatch) {
            this.usedImages.add(bestMatch);
            // Behalte die tatsächliche Dateierweiterung aus Images-Verzeichnis, wenn möglich
            const extCandidate = ['.png', '.jpg', '.jpeg', '.webp'].find(ext => 
                fs.existsSync(path.join(IMAGES_DIR, bestMatch + ext))
            ) || '.png';
            return {
                image: bestMatch + extCandidate,
                score: bestScore,
                agent: bestAgent
            };
        }
        
        return null;
    }

    // Generiere SEO-Metadaten
    generateSeoMetadata(context, imageMatch) {
        // Deutsche Service-Namen
        const serviceNames = {
            'schadstoffe': 'Schadstoffsanierung',
            'beratung': 'Beratung & Mediation',
            'altlasten': 'Altlastensanierung',
            'rueckbau': 'Rückbaumanagement',
            'sicherheit': 'Sicherheitskoordination',
            'pcb': 'PCB-Sanierung',
            'asbest': 'Asbestsanierung',
            'baumediation': 'Baumediation'
        };
        
        // Deutsche Keywords aufbereiten
        const germanTitle = context.keywords.length > 0 
            ? context.keywords.map(k => k.replace(/\b\w/g, l => l.toUpperCase())).join(' ')
            : serviceNames[context.category] || context.service.replace(/\b\w/g, l => l.toUpperCase());
        
        // Deutsche Beschreibungen basierend auf Kategorie
        const germanDescriptions = {
            'pcb': 'Professionelle PCB-Sanierung durch zertifizierte Fachkräfte',
            'asbest': 'Fachgerechte Asbestsanierung mit modernsten Sicherheitsstandards',
            'baumediation': 'Professionelle Baumediation und Konfliktlösung im Bauwesen',
            'beratung': 'Expertenberatung für Bau- und Umweltprojekte',
            'altlasten': 'Umfassende Altlastensanierung und Bodenschutz',
            'rueckbau': 'Nachhaltiger Rückbau mit Recycling und Kreislaufwirtschaft',
            'sicherheit': 'Baustellensicherheit und Arbeitsschutzkoordination',
            'schadstoffe': 'Professionelle Schadstoffsanierung und Umweltschutz'
        };
        
        // Deutsche Captions basierend auf Kategorie
        const germanCaptions = {
            'pcb': 'RIMAN GmbH - Ihr Spezialist für PCB-Kontaminationssanierung',
            'asbest': 'Asbestsanierung durch zertifizierte Experten von RIMAN GmbH',
            'baumediation': 'Baumediation und Konfliktmanagement von RIMAN GmbH',
            'beratung': 'Fachberatung und Projektunterstützung durch RIMAN GmbH',
            'altlasten': 'Altlastensanierung und Umweltschutz von RIMAN GmbH',
            'rueckbau': 'Nachhaltiges Rückbaumanagement durch RIMAN GmbH',
            'sicherheit': 'Sicherheitskoordination und Arbeitsschutz von RIMAN GmbH',
            'schadstoffe': 'Schadstoffsanierung und Umwelttechnik von RIMAN GmbH'
        };
            
        return {
            image: imageMatch.image,
            alt: `${germanTitle} - RIMAN GmbH Umwelttechnik`,
            title: `${germanTitle} | RIMAN GmbH - Experten für Umwelt & Bau`,
            caption: germanCaptions[context.category] || `Professionelle ${germanTitle} durch RIMAN GmbH`,
            description: germanDescriptions[context.category] || `${germanTitle} - Expertenlösungen von RIMAN GmbH für Umwelt- und Bauprojekte`,
            score: imageMatch.score,
            category: context.category
        };
    }

    // Verarbeite alle Content-Dateien
    processAllContent() {
        console.log('\n🔍 Processing content files...');
        
        const results = {
            description: "WordPress Semantic Image Mappings - Unique assignments without duplicates",
            version: "3.0-semantic-unique",
            generated: new Date().toISOString(),
            algorithm: "Thematic semantic scoring with duplicate prevention",
            total_content_files: this.contentFiles.length,
            total_images_available: this.availableImages.length,
            main_categories: {},
            subcategories: {},
            pages: {},
            statistics: {
                mapped: 0,
                perfect_matches: 0,
                good_matches: 0,
                fallback_matches: 0,
                critical_errors: 0,
                unmapped: 0
            }
        };
        
        // Sortiere Content-Dateien nach Priorität (kritische zuerst)
        const sortedContent = this.contentFiles.map(file => ({
            file,
            context: this.extractContext(file)
        })).sort((a, b) => {
            // PCB und Mediation zuerst (kritisch)
            if (a.context.category === 'pcb' && b.context.category !== 'pcb') return -1;
            if (b.context.category === 'pcb' && a.context.category !== 'pcb') return 1;
            if (a.context.category === 'baumediation' && b.context.category !== 'baumediation') return -1;
            if (b.context.category === 'baumediation' && a.context.category !== 'baumediation') return 1;
            
            return 0;
        });
        
        // Verarbeite jede Datei
        sortedContent.forEach(({ file, context }) => {
            const imageMatch = this.findBestImage(context);
            
            if (imageMatch) {
                const mapping = this.generateSeoMetadata(context, imageMatch);
                
                // Kategorisiere nach Typ - ignoriere main.md Dateien für separate Mappings
                let identifier = context.identifier;
                
                // Entferne -main Suffix für main.md Dateien
                if (context.fileName === 'main') {
                    identifier = identifier.replace('-main', '');
                }
                
                // Service-Namen, die immer Main Categories sind
                const mainServices = [
                    'altlastensanierung', 'rueckbaumanagement', 
                    'schadstoff-management', 'sicherheitskoordination', 
                    'beratung-mediation'
                ];
                
                const parts = identifier.split('-');
                if (parts.length === 1 || mainServices.includes(identifier)) {
                    results.main_categories[identifier] = mapping;
                } else if (parts.length === 2) {
                    results.subcategories[identifier] = mapping;
                } else {
                    // Nur echte Unterseiten, keine main.md Dateien
                    if (context.fileName !== 'main') {
                        results.pages[context.identifier] = mapping;
                    }
                }
                
                // Statistiken
                results.statistics.mapped++;
                if (imageMatch.score >= 100) results.statistics.perfect_matches++;
                else if (imageMatch.score >= 50) results.statistics.good_matches++;
                else if (imageMatch.score >= 0) results.statistics.fallback_matches++;
                else results.statistics.critical_errors++;
                
                console.log(`✓ ${context.identifier}: ${imageMatch.image} (Score: ${imageMatch.score})`);
            } else {
                results.statistics.unmapped++;
                console.log(`✗ ${context.identifier}: NO IMAGE FOUND`);
            }
        });
        
        return results;
    }

    // Validiere kritische Zuordnungen
    validateCriticalMappings(results) {
        console.log('\n🔍 Validating critical mappings...');
        
        const validation = {
            passed: true,
            errors: [],
            warnings: [],
            checks: []
        };
        
        const allMappings = {
            ...results.main_categories,
            ...results.subcategories,
            ...results.pages
        };
        
        Object.entries(allMappings).forEach(([key, mapping]) => {
            // PCB in Mediation check
            if ((key.includes('mediation') || key.includes('beratung')) && 
                mapping.image.toLowerCase().includes('pcb')) {
                validation.passed = false;
                validation.errors.push(`❌ CRITICAL: PCB image in mediation - ${key}: ${mapping.image}`);
            }
            
            // PCB content should have PCB image
            if (key.includes('pcb') && !mapping.image.toLowerCase().includes('pcb')) {
                validation.warnings.push(`⚠️  WARNING: PCB content without PCB image - ${key}`);
            }
            
            // Mediation should have consultation images
            if (key.includes('mediation') && 
                !mapping.image.toLowerCase().includes('consultation') &&
                !mapping.image.toLowerCase().includes('project') &&
                !mapping.image.toLowerCase().includes('professional')) {
                validation.warnings.push(`⚠️  WARNING: Mediation without consultation image - ${key}`);
            }
        });
        
        // Duplikat-Check
        const imageUsage = new Map();
        Object.entries(allMappings).forEach(([key, mapping]) => {
            const img = mapping.image;
            if (imageUsage.has(img)) {
                imageUsage.get(img).push(key);
            } else {
                imageUsage.set(img, [key]);
            }
        });
        
        imageUsage.forEach((keys, image) => {
            if (keys.length > 1) {
                validation.warnings.push(`⚠️  DUPLICATE: ${image} used by ${keys.length} entries: ${keys.join(', ')}`);
            }
        });
        
        validation.checks.push(`✓ Total mappings: ${Object.keys(allMappings).length}`);
        validation.checks.push(`✓ Unique images used: ${imageUsage.size}`);
        validation.checks.push(`✓ Critical errors: ${validation.errors.length}`);
        validation.checks.push(`✓ Warnings: ${validation.warnings.length}`);
        
        return validation;
    }

    // Hauptausführung
    async run() {
        console.log('🚀 RIMAN Semantic Image Mapping - Unique Assignment\n');
        
        // Lade alle Daten
        this.loadData();
        
        // Verarbeite Content
        const results = this.processAllContent();
        
        // Validiere
        const validation = this.validateCriticalMappings(results);
        results.validation = validation;
        
        // Speichere Ergebnis
        fs.writeFileSync(OUTPUT_FILE, JSON.stringify(results, null, 2));
        
        // Bericht
        console.log('\n' + '='.repeat(60));
        console.log('📊 MAPPING RESULTS');
        console.log('='.repeat(60));
        console.log(`Total Content Files: ${results.total_content_files}`);
        console.log(`Available Images: ${results.total_images_available}`);
        console.log(`Successfully Mapped: ${results.statistics.mapped}/${results.total_content_files}`);
        console.log(`Perfect Matches (Score ≥100): ${results.statistics.perfect_matches}`);
        console.log(`Good Matches (Score ≥50): ${results.statistics.good_matches}`);
        console.log(`Fallback Matches (Score ≥0): ${results.statistics.fallback_matches}`);
        console.log(`Critical Errors (Score <0): ${results.statistics.critical_errors}`);
        console.log(`Unmapped: ${results.statistics.unmapped}`);
        
        console.log('\n🔍 VALIDATION RESULTS:');
        validation.checks.forEach(check => console.log(check));
        
        if (validation.errors.length > 0) {
            console.log('\n❌ CRITICAL ERRORS:');
            validation.errors.forEach(error => console.log(error));
        }
        
        if (validation.warnings.length > 0 && validation.warnings.length <= 10) {
            console.log('\n⚠️  WARNINGS:');
            validation.warnings.forEach(warning => console.log(warning));
        } else if (validation.warnings.length > 10) {
            console.log(`\n⚠️  ${validation.warnings.length} warnings (showing first 5):`);
            validation.warnings.slice(0, 5).forEach(warning => console.log(warning));
        }
        
        console.log(`\n✨ Results saved to: ${OUTPUT_FILE}`);
        console.log(`\n${validation.passed ? '✅ VALIDATION PASSED' : '❌ VALIDATION FAILED'}`);
        
        return validation.passed;
    }
}

// Ausführung
if (require.main === module) {
    const mapper = new SemanticImageMapper();
    mapper.run().catch(console.error);
}

module.exports = SemanticImageMapper;
