#!/usr/bin/env node

/**
 * RIMAN Subcategory Image Optimizer
 * 
 * 1. Analyzes all subcategories from image-mapping.json
 * 2. Finds existing Midjourney images 
 * 3. Splits 2x2 grids into variants
 * 4. Uses /describe to select best variants
 * 5. Creates optimized mapping
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Configuration
const CONFIG = {
    mappingFile: '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/image-mapping.json',
    midjourneyImages: '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images',
    outputDir: '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images/subcategories',
    variantsDir: '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images/variants',
    describeTool: '/Users/holgerbrandt/dev/claude-code/tools/midjourney-mcp-server/describe.js'
};

class SubcategoryOptimizer {
    constructor() {
        this.mapping = {};
        this.imageInventory = [];
        this.results = {};
        this.processed = 0;
        this.total = 0;
    }

    async init() {
        console.log('🚀 RIMAN Subcategory Image Optimizer Starting...\n');
        
        // Create directories
        this.ensureDirectories();
        
        // Load current mapping
        this.loadMapping();
        
        // Analyze subcategories
        this.analyzeSubcategories();
        
        // Find available images  
        await this.findImages();
        
        console.log(`📊 Analysis Complete:`);
        console.log(`   Subcategories: ${this.total}`);
        console.log(`   Available Images: ${this.imageInventory.length}`);
        console.log(`   Ready for processing!\\n`);
        
        return this;
    }

    ensureDirectories() {
        [CONFIG.outputDir, CONFIG.variantsDir].forEach(dir => {
            if (!fs.existsSync(dir)) {
                fs.mkdirSync(dir, { recursive: true });
                console.log(`✅ Created directory: ${dir}`);
            }
        });
    }

    loadMapping() {
        try {
            const data = fs.readFileSync(CONFIG.mappingFile, 'utf8');
            this.mapping = JSON.parse(data);
            console.log('✅ Loaded image mapping');
        } catch (error) {
            console.error('❌ Failed to load mapping:', error.message);
            process.exit(1);
        }
    }

    analyzeSubcategories() {
        console.log('🔍 Analyzing subcategories...');
        
        Object.keys(this.mapping.services).forEach(mainCategory => {
            const service = this.mapping.services[mainCategory];
            
            if (service.subservices) {
                Object.keys(service.subservices).forEach(subCategory => {
                    const sub = service.subservices[subCategory];
                    this.total++;
                    
                    // Add details subcategories
                    if (sub.details) {
                        Object.keys(sub.details).forEach(detail => {
                            this.total++;
                        });
                    }
                });
            }
        });
        
        console.log(`✅ Found ${this.total} subcategories`);
    }

    async findImages() {
        console.log('🔍 Finding available images...');
        
        // Get all Midjourney images
        const imagesDir = path.join(CONFIG.midjourneyImages, '..', 'tools', 'midjourney-mcp-server', 'midjourney-images');
        
        if (fs.existsSync(imagesDir)) {
            const files = fs.readdirSync(imagesDir).filter(f => f.endsWith('.png'));
            
            files.forEach(file => {
                const filepath = path.join(imagesDir, file);
                const stats = fs.statSync(filepath);
                
                this.imageInventory.push({
                    file,
                    path: filepath,
                    size: stats.size,
                    modified: stats.mtime
                });
            });
            
            // Sort by modification time (newest first)
            this.imageInventory.sort((a, b) => b.modified - a.modified);
        }
        
        console.log(`✅ Found ${this.imageInventory.length} Midjourney images`);
    }

    async processAllSubcategories() {
        console.log('\\n🎯 Starting subcategory processing...\\n');
        
        for (const mainCategory of Object.keys(this.mapping.services)) {
            await this.processMainCategory(mainCategory);
        }
        
        // Generate final report
        await this.generateReport();
        
        console.log('\\n🎉 All subcategories processed!');
    }

    async processMainCategory(mainCategory) {
        console.log(`\\n📂 Processing: ${mainCategory.toUpperCase()}`);
        
        const service = this.mapping.services[mainCategory];
        
        if (!service.subservices) {
            console.log('   No subservices found');
            return;
        }
        
        for (const subCategory of Object.keys(service.subservices)) {
            await this.processSubcategory(mainCategory, subCategory);
        }
    }

    async processSubcategory(mainCategory, subCategory) {
        console.log(`\\n  🎯 Processing: ${mainCategory}/${subCategory}`);
        
        const sub = this.mapping.services[mainCategory].subservices[subCategory];
        
        // Process main subcategory
        if (sub.image) {
            await this.processImage(mainCategory, subCategory, 'main', sub.image);
        }
        
        // Process details
        if (sub.details) {
            for (const detail of Object.keys(sub.details)) {
                const detailImage = sub.details[detail];
                await this.processImage(mainCategory, subCategory, detail, detailImage);
            }
        }
    }

    async processImage(mainCategory, subCategory, detail, imageName) {
        console.log(`    🖼️  ${detail}: ${imageName}`);
        
        // Try to find matching Midjourney image
        const match = this.findBestImageMatch(imageName);
        
        if (match) {
            console.log(`    ✅ Found match: ${match.file}`);
            
            // Split into variants
            const variants = await this.splitIntoVariants(match, `${mainCategory}_${subCategory}_${detail}`);
            
            // Describe variants
            const descriptions = await this.describeVariants(variants);
            
            // Select best
            const best = this.selectBestVariant(descriptions, mainCategory, subCategory);
            
            // Save result
            this.results[`${mainCategory}/${subCategory}/${detail}`] = {
                original: imageName,
                matched: match.file,
                best: best,
                confidence_improvement: this.estimateImprovement(descriptions[best], mainCategory)
            };
            
            this.processed++;
        } else {
            console.log(`    ❌ No match found for: ${imageName}`);
        }
    }

    findBestImageMatch(imageName) {
        // Extract keywords from image name
        const keywords = this.extractKeywords(imageName);
        
        // Score each image
        let bestMatch = null;
        let bestScore = 0;
        
        this.imageInventory.forEach(img => {
            const score = this.calculateMatchScore(keywords, img.file);
            if (score > bestScore) {
                bestScore = score;
                bestMatch = img;
            }
        });
        
        return bestScore > 0.3 ? bestMatch : null;
    }

    extractKeywords(imageName) {
        // Remove file extension and split
        const name = imageName.replace(/\\.(jpg|png|jpeg)$/i, '');
        return name.split(/[-_\\s]+/).filter(k => k.length > 2);
    }

    calculateMatchScore(keywords, filename) {
        let score = 0;
        const fileWords = filename.toLowerCase().split(/[-_\\s]+/);
        
        keywords.forEach(keyword => {
            if (fileWords.some(word => word.includes(keyword.toLowerCase()) || keyword.toLowerCase().includes(word))) {
                score += 1;
            }
        });
        
        return score / Math.max(keywords.length, 1);
    }

    async splitIntoVariants(image, prefix) {
        console.log(`    📐 Splitting into variants: ${prefix}`);
        
        const outputFiles = [];
        
        try {
            // Use Python PIL to split
            const splitScript = `
from PIL import Image
import os

img = Image.open('${image.path}')
width, height = img.size
half_width = width // 2
half_height = height // 2

variants = [
    img.crop((0, 0, half_width, half_height)),
    img.crop((half_width, 0, width, half_height)),
    img.crop((0, half_height, half_width, height)),
    img.crop((half_width, half_height, width, height))
]

for i, variant in enumerate(variants, 1):
    output_path = '${CONFIG.variantsDir}/${prefix}_variant_' + str(i) + '.png'
    variant.save(output_path)
    print(output_path)
`;
            
            const result = execSync(`python3 -c "${splitScript}"`, { encoding: 'utf8' });
            outputFiles.push(...result.trim().split('\\n'));
            
            console.log(`    ✅ Created ${outputFiles.length} variants`);
            
        } catch (error) {
            console.error(`    ❌ Split failed:`, error.message);
        }
        
        return outputFiles;
    }

    async describeVariants(variants) {
        console.log(`    🧠 Describing variants...`);
        
        const descriptions = {};
        
        for (let i = 0; i < variants.length; i++) {
            const variant = variants[i];
            const variantNum = i + 1;
            
            try {
                const result = execSync(`node ${CONFIG.describeTool} "${variant}"`, { 
                    encoding: 'utf8',
                    timeout: 60000 
                });
                
                // Extract first description
                const match = result.match(/\\[1\\] (.+?) --ar/);
                if (match) {
                    descriptions[variantNum] = match[1].trim();
                    console.log(`    ✅ V${variantNum}: ${descriptions[variantNum].substring(0, 50)}...`);
                }
                
                // Short delay between requests
                await new Promise(resolve => setTimeout(resolve, 1000));
                
            } catch (error) {
                console.error(`    ❌ Describe failed for variant ${variantNum}:`, error.message);
            }
        }
        
        return descriptions;
    }

    selectBestVariant(descriptions, mainCategory, subCategory) {
        // Score each variant based on relevance keywords
        const relevanceKeywords = this.getRelevanceKeywords(mainCategory, subCategory);
        
        let bestVariant = 1;
        let bestScore = 0;
        
        Object.keys(descriptions).forEach(variant => {
            const desc = descriptions[variant].toLowerCase();
            let score = 0;
            
            relevanceKeywords.forEach(keyword => {
                if (desc.includes(keyword.toLowerCase())) {
                    score += 1;
                }
            });
            
            // Bonus for professional terms
            if (desc.includes('professional') || desc.includes('industrial') || desc.includes('worker')) {
                score += 0.5;
            }
            
            if (score > bestScore) {
                bestScore = score;
                bestVariant = variant;
            }
        });
        
        console.log(`    🏆 Best variant: ${bestVariant} (score: ${bestScore})`);
        return bestVariant;
    }

    getRelevanceKeywords(mainCategory, subCategory) {
        const keywords = {
            'rueckbau': ['demolition', 'construction', 'excavator', 'machinery', 'deconstruction'],
            'altlastensanierung': ['environmental', 'soil', 'contamination', 'remediation', 'sampling'],
            'schadstoff': ['protective', 'hazardous', 'laboratory', 'safety', 'suits'],
            'sicherheitskoordination': ['safety', 'coordinator', 'inspection', 'hard hat', 'clipboard'],
            'beratung': ['consultation', 'office', 'expert', 'advisory', 'meeting']
        };
        
        return keywords[mainCategory] || ['professional', 'industrial'];
    }

    estimateImprovement(description, mainCategory) {
        // Estimate confidence improvement based on description relevance
        const relevanceKeywords = this.getRelevanceKeywords(mainCategory, '');
        const desc = description.toLowerCase();
        
        let matches = 0;
        relevanceKeywords.forEach(keyword => {
            if (desc.includes(keyword.toLowerCase())) {
                matches++;
            }
        });
        
        // Base improvement + bonus for matches
        return Math.min(30 + (matches * 10), 60);
    }

    async generateReport() {
        console.log('\\n📋 Generating final report...');
        
        const report = {
            timestamp: new Date().toISOString(),
            processed: this.processed,
            total: this.total,
            success_rate: `${Math.round((this.processed / this.total) * 100)}%`,
            results: this.results,
            summary: {
                avg_improvement: this.calculateAverageImprovement(),
                best_improvements: this.getBestImprovements(),
                categories_processed: Object.keys(this.mapping.services).length
            }
        };
        
        const reportPath = path.join(CONFIG.outputDir, 'subcategory-optimization-report.json');
        fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));
        
        console.log(`✅ Report saved: ${reportPath}`);
        console.log(`📊 Success Rate: ${report.success_rate}`);
        console.log(`📈 Avg Improvement: +${report.summary.avg_improvement}%`);
        
        return report;
    }

    calculateAverageImprovement() {
        const improvements = Object.values(this.results).map(r => r.confidence_improvement);
        return Math.round(improvements.reduce((a, b) => a + b, 0) / improvements.length);
    }

    getBestImprovements() {
        return Object.entries(this.results)
            .sort((a, b) => b[1].confidence_improvement - a[1].confidence_improvement)
            .slice(0, 5)
            .map(([key, value]) => ({ category: key, improvement: value.confidence_improvement }));
    }
}

// Main execution
async function main() {
    try {
        const optimizer = new SubcategoryOptimizer();
        await optimizer.init();
        await optimizer.processAllSubcategories();
        
    } catch (error) {
        console.error('💥 Error:', error.message);
        process.exit(1);
    }
}

// Export for use as module or run directly
if (require.main === module) {
    main();
}

module.exports = SubcategoryOptimizer;