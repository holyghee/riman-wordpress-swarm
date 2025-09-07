#!/usr/bin/env node

/**
 * Intelligent WordPress Image Mapper with AI/Semantic Analysis
 * Solves the thematic mismatch problem with proper semantic understanding
 */

const fs = require('fs');
const path = require('path');

// Configuration
const CONFIG = {
    contentBase: '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content',
    midjpiDb: '../midjpi_complete_database.json',
    outputFile: '../wordpress-intelligent-mappings.json',
    logFile: './mapping-log.txt'
};

// Thematic categories with semantic keywords
const THEMATIC_CATEGORIES = {
    'schadstoffe': {
        keywords: ['pcb', 'asbest', 'schwermetall', 'kmf', 'pak', 'kontamination', 'giftig', 'chemisch', 'gefährlich', 'schadstoff'],
        forbidden: ['mediation', 'beratung', 'konflikt', 'kommunikation'],
        priority: ['contamination', 'hazardous', 'chemical', 'toxic', 'remediation']
    },
    'beratung': {
        keywords: ['mediation', 'beratung', 'konflikt', 'gutachten', 'schulung', 'compliance', 'projekt', 'kommunikation'],
        forbidden: ['pcb', 'asbest', 'kontamination', 'chemisch', 'giftig'],
        priority: ['consultation', 'project', 'strategic', 'professional', 'meeting']
    },
    'altlasten': {
        keywords: ['boden', 'grundwasser', 'sanierung', 'erkundung', 'monitoring', 'altlast'],
        forbidden: ['pcb', 'asbest'],
        priority: ['groundwater', 'soil', 'environmental', 'monitoring', 'remediation']
    },
    'rueckbau': {
        keywords: ['rückbau', 'abriss', 'entsorgung', 'recycling', 'dokumentation', 'planung'],
        forbidden: [],
        priority: ['demolition', 'deconstruction', 'recycling', 'disposal']
    },
    'sicherheit': {
        keywords: ['sigeko', 'arbeitsschutz', 'sicherheit', 'notfall', 'gefährdung'],
        forbidden: [],
        priority: ['safety', 'emergency', 'protection', 'training', 'coordination']
    }
};

// Critical mappings that must be correct
const CRITICAL_MAPPINGS = {
    'pcb': {
        mustInclude: ['pcb', 'electrical', 'contamination'],
        mustNotInclude: ['mediation', 'consultation', 'meeting']
    },
    'baumediation': {
        mustInclude: ['mediation', 'consultation', 'project', 'professional'],
        mustNotInclude: ['pcb', 'contamination', 'hazardous', 'chemical']
    },
    'asbest': {
        mustInclude: ['asbestos', 'removal', 'remediation'],
        mustNotInclude: ['pcb', 'mediation']
    }
};

class IntelligentImageMapper {
    constructor() {
        this.midjpiData = null;
        this.contentFiles = [];
        this.mappings = {};
        this.usedImages = new Set();
        this.log = [];
    }

    // Load MIDJPI database
    loadMidjpiDatabase() {
        const dbPath = path.join(__dirname, CONFIG.midjpiDb);
        this.midjpiData = JSON.parse(fs.readFileSync(dbPath, 'utf8'));
        this.log.push(`Loaded ${this.midjpiData.agents.length} agents with ${this.midjpiData.total_quadrants} quadrants`);
    }

    // Find all content files
    findContentFiles(dir = CONFIG.contentBase) {
        const files = [];
        const items = fs.readdirSync(dir);
        
        for (const item of items) {
            const fullPath = path.join(dir, item);
            const stat = fs.statSync(fullPath);
            
            if (stat.isDirectory()) {
                files.push(...this.findContentFiles(fullPath));
            } else if (item.endsWith('.md')) {
                files.push(fullPath);
            }
        }
        
        return files;
    }

    // Extract semantic context from file path and name
    extractSemanticContext(filePath) {
        const relative = path.relative(CONFIG.contentBase, filePath);
        const parts = relative.split(path.sep);
        
        // Determine category
        let category = 'general';
        let keywords = [];
        
        if (parts[0] === 'services') {
            const service = parts[1];
            
            // Map service names to categories
            if (service === 'schadstoff-management') category = 'schadstoffe';
            else if (service === 'beratung-mediation') category = 'beratung';
            else if (service === 'altlastensanierung') category = 'altlasten';
            else if (service === 'rueckbaumanagement') category = 'rueckbau';
            else if (service === 'sicherheitskoordination') category = 'sicherheit';
            
            // Extract keywords from path
            parts.forEach(part => {
                const cleaned = part.replace('.md', '').replace(/-/g, ' ');
                if (cleaned !== 'services' && cleaned !== 'main') {
                    keywords.push(cleaned);
                }
            });
        }
        
        return {
            category,
            keywords,
            filePath,
            identifier: this.createIdentifier(relative)
        };
    }

    // Create unique identifier from path
    createIdentifier(relativePath) {
        return relativePath
            .replace(/\//g, '-')
            .replace('.md', '')
            .replace('services-', '');
    }

    // Calculate semantic similarity score
    calculateSemanticScore(context, agent) {
        let score = 0;
        
        const theme = (agent.theme || '').toLowerCase();
        const desc = (agent.description || '').toLowerCase();
        const combined = `${theme} ${desc}`;
        
        // Check category alignment
        const category = THEMATIC_CATEGORIES[context.category];
        if (category) {
            // Positive scoring for matching keywords
            category.keywords.forEach(keyword => {
                if (combined.includes(keyword)) score += 10;
            });
            
            // Priority keywords get higher scores
            category.priority.forEach(keyword => {
                if (combined.includes(keyword)) score += 15;
            });
            
            // Negative scoring for forbidden keywords
            category.forbidden.forEach(keyword => {
                if (combined.includes(keyword)) score -= 50;
            });
        }
        
        // Context keywords matching
        context.keywords.forEach(keyword => {
            if (combined.includes(keyword.toLowerCase())) {
                score += 5;
            }
        });
        
        // Critical validation
        const identifier = context.identifier.toLowerCase();
        Object.entries(CRITICAL_MAPPINGS).forEach(([key, rules]) => {
            if (identifier.includes(key)) {
                // Must include checks
                rules.mustInclude.forEach(term => {
                    if (combined.includes(term)) score += 20;
                    else score -= 30;
                });
                
                // Must not include checks
                rules.mustNotInclude.forEach(term => {
                    if (combined.includes(term)) score -= 100;
                });
            }
        });
        
        return score;
    }

    // Find best matching image for content
    findBestImage(context) {
        let bestMatch = null;
        let bestScore = -Infinity;
        let bestAgent = null;
        
        this.midjpiData.agents.forEach(agent => {
            if (!agent.image_paths || agent.image_paths.length === 0) return;
            
            const score = this.calculateSemanticScore(context, agent);
            
            // Track best match
            if (score > bestScore) {
                // Find unused quadrant
                for (const imagePath of agent.image_paths) {
                    const cleanPath = imagePath.replace('./images/', '');
                    if (!this.usedImages.has(cleanPath)) {
                        bestScore = score;
                        bestMatch = cleanPath;
                        bestAgent = agent;
                        break;
                    }
                }
                
                // If all quadrants used, still track for fallback
                if (!bestMatch && score > bestScore) {
                    bestScore = score;
                    bestMatch = agent.image_paths[0].replace('./images/', '');
                    bestAgent = agent;
                }
            }
        });
        
        // Log the match
        this.log.push(`${context.identifier}: Score=${bestScore}, Image=${bestMatch ? bestMatch.substring(0, 50) : 'None'}`);
        
        if (bestMatch && bestAgent) {
            this.usedImages.add(bestMatch);
            return {
                image: bestMatch,
                score: bestScore,
                agent: bestAgent
            };
        }
        
        return null;
    }

    // Generate SEO metadata
    generateSeoMetadata(context, imageData) {
        const category = THEMATIC_CATEGORIES[context.category];
        const title = context.keywords.join(' ').replace(/\b\w/g, l => l.toUpperCase());
        
        return {
            image: imageData.image,
            alt: `${title} - RIMAN GmbH Professional Service`,
            title: `${title} | RIMAN GmbH`,
            caption: imageData.agent.description || `Professional ${context.category} services`,
            description: `${imageData.agent.theme || title} - Expert solutions by RIMAN GmbH`,
            score: imageData.score,
            category: context.category
        };
    }

    // Process all content files
    processContent() {
        this.contentFiles = this.findContentFiles();
        this.log.push(`Found ${this.contentFiles.length} content files`);
        
        const results = {
            description: "Intelligent WordPress Image Mappings with Semantic Analysis",
            version: "2.0-intelligent",
            generated: new Date().toISOString(),
            algorithm: "Semantic scoring with thematic validation",
            main_categories: {},
            subcategories: {},
            pages: {},
            statistics: {
                total_content: this.contentFiles.length,
                mapped: 0,
                perfect_matches: 0,
                good_matches: 0,
                fallback_matches: 0,
                unmapped: 0
            }
        };
        
        // Process each content file
        this.contentFiles.forEach(file => {
            const context = this.extractSemanticContext(file);
            const imageData = this.findBestImage(context);
            
            if (imageData) {
                const mapping = this.generateSeoMetadata(context, imageData);
                
                // Categorize by type
                const parts = context.identifier.split('-');
                if (parts.length === 1 || context.identifier.includes('main')) {
                    results.main_categories[context.identifier] = mapping;
                } else if (parts.length === 2) {
                    results.subcategories[context.identifier] = mapping;
                } else {
                    results.pages[context.identifier] = mapping;
                }
                
                // Update statistics
                results.statistics.mapped++;
                if (imageData.score > 50) results.statistics.perfect_matches++;
                else if (imageData.score > 0) results.statistics.good_matches++;
                else results.statistics.fallback_matches++;
            } else {
                results.statistics.unmapped++;
                this.log.push(`WARNING: No image found for ${context.identifier}`);
            }
        });
        
        return results;
    }

    // Validate critical mappings
    validateCriticalMappings(results) {
        const validation = {
            passed: true,
            checks: []
        };
        
        // Check PCB not in mediation
        const allMappings = {
            ...results.main_categories,
            ...results.subcategories,
            ...results.pages
        };
        
        Object.entries(allMappings).forEach(([key, value]) => {
            if (key.includes('mediation') || key.includes('beratung')) {
                if (value.image.toLowerCase().includes('pcb')) {
                    validation.passed = false;
                    validation.checks.push(`❌ CRITICAL: PCB image in mediation: ${key}`);
                } else {
                    validation.checks.push(`✅ OK: No PCB in mediation: ${key}`);
                }
            }
            
            if (key.includes('pcb')) {
                if (value.image.toLowerCase().includes('pcb')) {
                    validation.checks.push(`✅ PERFECT: PCB content has PCB image: ${key}`);
                } else if (value.image.toLowerCase().includes('mediation')) {
                    validation.passed = false;
                    validation.checks.push(`❌ CRITICAL: Mediation image in PCB: ${key}`);
                } else {
                    validation.checks.push(`⚠️  WARNING: PCB content without PCB image: ${key}`);
                }
            }
        });
        
        return validation;
    }

    // Main execution
    async run() {
        console.log('🚀 Starting Intelligent Image Mapping...\n');
        
        // Load data
        this.loadMidjpiDatabase();
        
        // Process content
        const results = this.processContent();
        
        // Validate
        const validation = this.validateCriticalMappings(results);
        results.validation = validation;
        
        // Save results
        const outputPath = path.join(__dirname, CONFIG.outputFile);
        fs.writeFileSync(outputPath, JSON.stringify(results, null, 2));
        
        // Save log
        const logPath = path.join(__dirname, CONFIG.logFile);
        fs.writeFileSync(logPath, this.log.join('\n'));
        
        // Report
        console.log('📊 MAPPING COMPLETE\n');
        console.log(`✅ Mapped: ${results.statistics.mapped}/${results.statistics.total_content}`);
        console.log(`⭐ Perfect matches: ${results.statistics.perfect_matches}`);
        console.log(`👍 Good matches: ${results.statistics.good_matches}`);
        console.log(`🔄 Fallback matches: ${results.statistics.fallback_matches}`);
        console.log(`❌ Unmapped: ${results.statistics.unmapped}`);
        
        console.log('\n🔍 VALIDATION:');
        validation.checks.slice(0, 10).forEach(check => console.log(check));
        
        console.log(`\n✨ Output saved to: ${outputPath}`);
        console.log(`📝 Log saved to: ${logPath}`);
        
        return validation.passed;
    }
}

// Execute
const mapper = new IntelligentImageMapper();
mapper.run().then(passed => {
    process.exit(passed ? 0 : 1);
}).catch(err => {
    console.error('Error:', err);
    process.exit(1);
});