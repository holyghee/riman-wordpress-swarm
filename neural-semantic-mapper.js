#!/usr/bin/env node

/**
 * Neural Semantic Mapper with AI-Powered Understanding
 * Uses Claude Flow's neural capabilities for true semantic matching
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

class NeuralSemanticMapper {
  constructor() {
    this.imageDatabase = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/midjpi_complete_database.json';
    this.contentDir = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content';
    this.outputPath = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-enhanced-image-mappings.json';
    this.usedImages = new Set();
    this.semanticCache = new Map();
  }

  /**
   * Use AI to understand content semantically
   */
  async analyzeContentWithAI(content, filePath) {
    const prompt = `Analyze this German construction/environmental service content and extract semantic themes.
    
Content: ${content.substring(0, 2000)}
File: ${path.basename(filePath)}

Extract:
1. Primary service category (rueckbau/altlasten/schadstoffe/sicherheit/beratung)
2. Specific technical focus (e.g., asbest, pcb, pak, kmf for schadstoffe)
3. Key themes and concepts
4. Service type (planning/execution/monitoring/consulting)
5. Target audience (construction/industrial/municipal/private)

Return as JSON with fields: category, subcategory, themes[], service_type, audience, technical_focus`;

    try {
      // Use Claude Flow's neural analysis
      const result = execSync(`npx claude-flow@alpha neural analyze --prompt "${prompt.replace(/"/g, '\\"')}" --format json`, {
        encoding: 'utf8',
        maxBuffer: 10 * 1024 * 1024
      });
      
      return JSON.parse(result);
    } catch (error) {
      console.warn('AI analysis failed, using fallback:', error.message);
      return this.fallbackAnalysis(content, filePath);
    }
  }

  /**
   * Use AI to understand image content
   */
  async analyzeImageWithAI(imagePath, description) {
    const prompt = `Analyze this image for German construction/environmental services context.
    
Image: ${path.basename(imagePath)}
Description: ${description}

Determine:
1. Primary service it represents (rueckbau/altlasten/schadstoffe/sicherheit/beratung)
2. Specific technical area (e.g., for schadstoffe: asbest/kmf/pak/pcb/schwermetalle)
3. Activity shown (planning/execution/monitoring/documentation)
4. Professional level (management/technical/operational)
5. Industry context (construction/industrial/environmental)

Return as JSON with fields: service, technical_area, activity, level, context, confidence_score`;

    try {
      const result = execSync(`npx claude-flow@alpha neural analyze --prompt "${prompt.replace(/"/g, '\\"')}" --format json`, {
        encoding: 'utf8',
        maxBuffer: 10 * 1024 * 1024
      });
      
      return JSON.parse(result);
    } catch (error) {
      console.warn('Image AI analysis failed:', error.message);
      return this.fallbackImageAnalysis(imagePath, description);
    }
  }

  /**
   * Calculate semantic similarity using AI understanding
   */
  async calculateSemanticSimilarity(contentAnalysis, imageAnalysis) {
    // Direct category match is critical
    if (contentAnalysis.category !== imageAnalysis.service) {
      return 0.2; // Low score for category mismatch
    }

    let score = 0.5; // Base score for category match

    // Technical area match (very important)
    if (contentAnalysis.technical_focus === imageAnalysis.technical_area) {
      score += 0.3;
    }

    // Activity type match
    if (contentAnalysis.service_type === imageAnalysis.activity) {
      score += 0.1;
    }

    // Audience/context alignment
    if (contentAnalysis.audience === imageAnalysis.context) {
      score += 0.1;
    }

    // Apply confidence from image analysis
    if (imageAnalysis.confidence_score) {
      score *= imageAnalysis.confidence_score;
    }

    return Math.min(score, 1.0);
  }

  /**
   * Fallback analysis when AI is unavailable
   */
  fallbackAnalysis(content, filePath) {
    const analysis = {
      category: '',
      subcategory: '',
      themes: [],
      service_type: '',
      audience: '',
      technical_focus: ''
    };

    // Determine category from file path and content
    const pathLower = filePath.toLowerCase();
    const contentLower = content.toLowerCase();

    if (pathLower.includes('rueckbau') || contentLower.includes('rückbau') || contentLower.includes('abbruch')) {
      analysis.category = 'rueckbau';
    } else if (pathLower.includes('altlasten') || contentLower.includes('altlasten') || contentLower.includes('bodensanierung')) {
      analysis.category = 'altlasten';
    } else if (pathLower.includes('schadstoff') || contentLower.includes('asbest') || contentLower.includes('pcb')) {
      analysis.category = 'schadstoffe';
      
      // Determine specific pollutant
      if (contentLower.includes('asbest')) analysis.technical_focus = 'asbest';
      else if (contentLower.includes('kmf') || contentLower.includes('mineralfaser')) analysis.technical_focus = 'kmf';
      else if (contentLower.includes('pak')) analysis.technical_focus = 'pak';
      else if (contentLower.includes('pcb')) analysis.technical_focus = 'pcb';
      else if (contentLower.includes('schwermetall')) analysis.technical_focus = 'schwermetalle';
    } else if (pathLower.includes('sicherheit') || contentLower.includes('sigeko') || contentLower.includes('arbeitsschutz')) {
      analysis.category = 'sicherheit';
    } else if (pathLower.includes('beratung') || contentLower.includes('mediation') || contentLower.includes('gutachten')) {
      analysis.category = 'beratung';
    }

    // Extract themes
    const themeKeywords = {
      'planning': ['planung', 'konzept', 'vorbereitung', 'ausschreibung'],
      'execution': ['durchführung', 'ausführung', 'umsetzung', 'sanierung'],
      'monitoring': ['überwachung', 'kontrolle', 'monitoring', 'messung'],
      'consulting': ['beratung', 'gutachten', 'bewertung', 'analyse']
    };

    for (const [theme, keywords] of Object.entries(themeKeywords)) {
      if (keywords.some(kw => contentLower.includes(kw))) {
        analysis.themes.push(theme);
        if (!analysis.service_type) analysis.service_type = theme;
      }
    }

    return analysis;
  }

  /**
   * Fallback image analysis
   */
  fallbackImageAnalysis(imagePath, description) {
    const analysis = {
      service: '',
      technical_area: '',
      activity: '',
      level: '',
      context: '',
      confidence_score: 0.5
    };

    const descLower = description.toLowerCase();
    const pathLower = imagePath.toLowerCase();

    // Detect service from image name/description
    if (pathLower.includes('demolition') || descLower.includes('rückbau')) {
      analysis.service = 'rueckbau';
    } else if (pathLower.includes('contamination') || pathLower.includes('pcb') || pathLower.includes('asbest')) {
      analysis.service = 'schadstoffe';
      
      if (pathLower.includes('pcb')) analysis.technical_area = 'pcb';
      else if (pathLower.includes('asbest')) analysis.technical_area = 'asbest';
    } else if (pathLower.includes('safety') || pathLower.includes('sicherheit')) {
      analysis.service = 'sicherheit';
    } else if (pathLower.includes('mediation') || pathLower.includes('consulting')) {
      analysis.service = 'beratung';
    } else if (pathLower.includes('soil') || pathLower.includes('groundwater')) {
      analysis.service = 'altlasten';
    }

    return analysis;
  }

  /**
   * Main processing function
   */
  async process() {
    console.log('🧠 Starting Neural Semantic Mapping...\n');

    // Load image database
    const database = JSON.parse(fs.readFileSync(this.imageDatabase, 'utf8'));
    console.log(`✅ Loaded ${database.agents.length} agents with images\n`);

    // Get all content files
    const contentFiles = this.getAllMarkdownFiles(this.contentDir);
    console.log(`📄 Found ${contentFiles.length} content files\n`);

    const mappings = {
      project_info: {
        name: "RIMAN WordPress Enhanced Image Mappings",
        created_with: "Neural Semantic Mapper",
        date: new Date().toISOString(),
        neural_powered: true
      },
      page_mappings: {},
      category_mappings: {},
      statistics: {
        total_pages: 0,
        mapped_pages: 0,
        unique_images: 0,
        ai_confidence_avg: 0
      }
    };

    let totalConfidence = 0;
    let mappedCount = 0;

    // Process each content file
    for (let i = 0; i < contentFiles.length; i++) {
      const filePath = contentFiles[i];
      const relativePath = path.relative(this.contentDir, filePath);
      console.log(`\n[${i+1}/${contentFiles.length}] Processing: ${relativePath}`);

      try {
        const content = fs.readFileSync(filePath, 'utf8');
        
        // AI-powered content analysis
        const contentAnalysis = await this.analyzeContentWithAI(content, filePath);
        console.log(`  📊 Category: ${contentAnalysis.category}, Focus: ${contentAnalysis.technical_focus || 'general'}`);

        // Find best matching image
        let bestMatch = null;
        let bestScore = 0;

        for (const agent of database.agents) {
          for (const [quadrant, description] of Object.entries(agent.quadrants)) {
            const imagePath = agent.image_paths[quadrant];
            
            // Skip already used images
            if (this.usedImages.has(imagePath)) continue;

            // AI-powered image analysis
            const imageAnalysis = await this.analyzeImageWithAI(imagePath, description);
            
            // Calculate semantic similarity
            const score = await this.calculateSemanticSimilarity(contentAnalysis, imageAnalysis);

            if (score > bestScore) {
              bestScore = score;
              bestMatch = {
                image: imagePath,
                agent_id: agent.agent_id,
                quadrant: quadrant,
                score: score,
                analysis: imageAnalysis
              };
            }
          }
        }

        if (bestMatch && bestScore > 0.4) {
          // Generate WordPress slug
          const slug = this.generateWordPressSlug(filePath, contentAnalysis);
          
          // Prevent duplicates
          if (!mappings.page_mappings[slug]) {
            mappings.page_mappings[slug] = bestMatch.image;
            this.usedImages.add(bestMatch.image);
            
            console.log(`  ✅ Matched: ${path.basename(bestMatch.image)} (Score: ${Math.round(bestScore * 100)}%)`);
            
            totalConfidence += bestScore;
            mappedCount++;

            // Track category mappings
            if (contentAnalysis.category) {
              if (!mappings.category_mappings[contentAnalysis.category]) {
                mappings.category_mappings[contentAnalysis.category] = [];
              }
              mappings.category_mappings[contentAnalysis.category].push({
                page: slug,
                image: bestMatch.image,
                confidence: bestScore
              });
            }
          }
        } else {
          console.log(`  ⚠️  No suitable match found (best score: ${Math.round(bestScore * 100)}%)`);
        }

      } catch (error) {
        console.error(`  ❌ Error: ${error.message}`);
      }
    }

    // Update statistics
    mappings.statistics.total_pages = contentFiles.length;
    mappings.statistics.mapped_pages = mappedCount;
    mappings.statistics.unique_images = this.usedImages.size;
    mappings.statistics.ai_confidence_avg = mappedCount > 0 ? totalConfidence / mappedCount : 0;

    // Save enhanced mappings
    fs.writeFileSync(this.outputPath, JSON.stringify(mappings, null, 2));

    console.log('\n' + '='.repeat(50));
    console.log('🎯 Neural Semantic Mapping Complete!');
    console.log('='.repeat(50));
    console.log(`📊 Total pages: ${mappings.statistics.total_pages}`);
    console.log(`✅ Mapped pages: ${mappings.statistics.mapped_pages}`);
    console.log(`🖼️  Unique images: ${mappings.statistics.unique_images}`);
    console.log(`🧠 AI Confidence: ${Math.round(mappings.statistics.ai_confidence_avg * 100)}%`);
    console.log(`📁 Output: ${this.outputPath}`);

    return mappings;
  }

  /**
   * Get all markdown files recursively
   */
  getAllMarkdownFiles(dir) {
    let results = [];
    const files = fs.readdirSync(dir);
    
    for (const file of files) {
      const fullPath = path.join(dir, file);
      const stat = fs.statSync(fullPath);
      
      if (stat.isDirectory()) {
        results = results.concat(this.getAllMarkdownFiles(fullPath));
      } else if (file.endsWith('.md')) {
        results.push(fullPath);
      }
    }
    
    return results;
  }

  /**
   * Generate WordPress-compatible slug
   */
  generateWordPressSlug(filePath, analysis) {
    const basename = path.basename(filePath, '.md');
    let slug = basename;

    // Add category prefix for clarity
    if (analysis.category) {
      slug = `${analysis.category}-${slug}`;
    }

    // Add technical focus if present
    if (analysis.technical_focus) {
      slug = `${slug}-${analysis.technical_focus}`;
    }

    // Clean and format
    return slug
      .toLowerCase()
      .replace(/ä/g, 'ae')
      .replace(/ö/g, 'oe')
      .replace(/ü/g, 'ue')
      .replace(/ß/g, 'ss')
      .replace(/[^a-z0-9-]/g, '-')
      .replace(/-+/g, '-')
      .substring(0, 60);
  }
}

// Execute if run directly
if (require.main === module) {
  const mapper = new NeuralSemanticMapper();
  mapper.process().catch(error => {
    console.error('❌ Fatal error:', error);
    process.exit(1);
  });
}

module.exports = NeuralSemanticMapper;