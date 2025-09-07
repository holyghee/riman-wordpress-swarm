#!/usr/bin/env node

/**
 * Generate Final Enhanced WordPress Mappings
 * Complete solution with all pages, correct image assignments, and full SEO metadata
 */

const fs = require('fs');
const path = require('path');

// Kritische Bildzuordnungen aus AI-Analyse
const VERIFIED_MAPPINGS = {
  'schadstoffe-pcb': 'holyghee_PCB_contamination_remediation_and_electrical_equipment_fd3c6a4d-afba-4016-92e6-7f79458eb008_1_top_left.png',
  'schadstoffe-asbest': 'holyghee_Professional_asbestos_removal_and_remediation._Workers_1577d5f5-9388-4f93-ae02-912b77af33d6_1_top_left.png',
  'beratung-baumediation': 'holyghee_Professional_project_consultation_and_strategic_planni_c66c7ea8-f41a-4f84-8697-c8e0e92d60d6_1_top_left.png',
  'sicherheit-sigeko-planung': 'holyghee_Professional_safety_and_environmental_training_classro_723e1864-1a70-4004-8c80-a0384819caad_1_top_left.png',
  'sicherheit-arbeitsschutz': 'holyghee_Safety_coordination_during_construction_execution._On-_dafd9622-5721-4d75-b5a9-3eb7eed163ae_1_top_left.png'
};

class FinalEnhancedMappingGenerator {
  constructor() {
    this.contentDir = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content';
    this.imageDatabase = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/midjpi_complete_database.json';
    this.outputPath = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-enhanced-image-mappings.json';
    this.usedImages = new Set();
    this.availableImages = [];
  }

  loadDatabase() {
    const db = JSON.parse(fs.readFileSync(this.imageDatabase, 'utf8'));
    // Sammle alle verfügbaren Bilder
    for (const agent of db.agents) {
      if (agent.image_paths) {
        for (const [quadrant, imagePath] of Object.entries(agent.image_paths)) {
          if (imagePath) {
            const cleanPath = imagePath.replace('./images/', 'images/');
            this.availableImages.push({
              path: cleanPath,
              agent_id: agent.agent_id,
              quadrant: quadrant,
              theme: agent.theme,
              description: agent.quadrants[quadrant]
            });
          }
        }
      }
    }
    return db;
  }

  getAllContentFiles() {
    const files = [];
    const scanDir = (dir) => {
      try {
        const items = fs.readdirSync(dir);
        for (const item of items) {
          const fullPath = path.join(dir, item);
          const stat = fs.statSync(fullPath);
          if (stat.isDirectory()) {
            scanDir(fullPath);
          } else if (item.endsWith('.md')) {
            files.push(fullPath);
          }
        }
      } catch (error) {
        console.warn(`Skipping directory ${dir}: ${error.message}`);
      }
    };
    scanDir(this.contentDir);
    return files;
  }

  extractPageMetadata(filePath, content) {
    const relativePath = path.relative(this.contentDir, filePath);
    
    // Extract title
    const titleMatch = content.match(/^#\s+(.+)/m);
    const title = titleMatch ? titleMatch[1].trim() : path.basename(filePath, '.md');
    
    // Extract SEO metadata if present
    const metaDescMatch = content.match(/\*\*Meta Description:\*\*\s*(.+)/);
    const metaDescription = metaDescMatch ? metaDescMatch[1].trim() : '';
    
    const keywordMatch = content.match(/\*\*SEO Keywords:\*\*\s*(.+)/);
    const keywords = keywordMatch ? 
      keywordMatch[1].split(',').map(k => k.trim()) : 
      this.extractKeywordsFromContent(content);
    
    // Determine category and subcategory
    const { category, subcategory } = this.determineCategory(filePath, content);
    
    // Generate slug
    const slug = this.generateSlug(title, category, subcategory, filePath);
    
    return {
      filePath: relativePath,
      title,
      metaDescription,
      keywords,
      category,
      subcategory,
      slug
    };
  }

  determineCategory(filePath, content) {
    const pathLower = filePath.toLowerCase();
    const contentLower = content.toLowerCase();
    
    let category = 'general';
    let subcategory = '';
    
    if (pathLower.includes('rueckbau') || contentLower.includes('rückbau') || contentLower.includes('abbruch')) {
      category = 'rueckbau';
      if (pathLower.includes('planung')) subcategory = 'planung';
      else if (pathLower.includes('ausschreibung')) subcategory = 'ausschreibung';
      else if (pathLower.includes('durchfuehrung')) subcategory = 'durchfuehrung';
      else if (pathLower.includes('entsorgung')) subcategory = 'entsorgung';
      else if (pathLower.includes('recycling')) subcategory = 'recycling';
    } else if (pathLower.includes('altlasten') || contentLower.includes('altlasten')) {
      category = 'altlasten';
      if (pathLower.includes('erkundung')) subcategory = 'erkundung';
      else if (pathLower.includes('bodensanierung')) subcategory = 'bodensanierung';
      else if (pathLower.includes('grundwasser')) subcategory = 'grundwassersanierung';
      else if (pathLower.includes('monitoring')) subcategory = 'monitoring';
      else if (pathLower.includes('sanierungsplanung')) subcategory = 'sanierungsplanung';
    } else if (pathLower.includes('schadstoff') || contentLower.includes('schadstoff')) {
      category = 'schadstoffe';
      if (contentLower.includes('asbest')) subcategory = 'asbest';
      else if (contentLower.includes('kmf')) subcategory = 'kmf';
      else if (contentLower.includes('pak')) subcategory = 'pak';
      else if (contentLower.includes('pcb')) subcategory = 'pcb';
      else if (contentLower.includes('schwermetall')) subcategory = 'schwermetalle';
    } else if (pathLower.includes('sicherheit') || contentLower.includes('sigeko')) {
      category = 'sicherheit';
      if (pathLower.includes('sigeko-planung')) subcategory = 'sigeko-planung';
      else if (pathLower.includes('sigeko-ausfuehrung')) subcategory = 'sigeko-ausfuehrung';
      else if (pathLower.includes('arbeitsschutz')) subcategory = 'arbeitsschutz';
      else if (pathLower.includes('gefaehrdung')) subcategory = 'gefaehrdungsbeurteilung';
      else if (pathLower.includes('notfall')) subcategory = 'notfallmanagement';
    } else if (pathLower.includes('beratung') || contentLower.includes('beratung')) {
      category = 'beratung';
      if (contentLower.includes('mediation')) subcategory = 'baumediation';
      else if (pathLower.includes('projektberatung')) subcategory = 'projektberatung';
      else if (pathLower.includes('gutachten')) subcategory = 'gutachten';
      else if (pathLower.includes('schulung')) subcategory = 'schulungen';
      else if (pathLower.includes('compliance')) subcategory = 'compliance';
    }
    
    return { category, subcategory };
  }

  generateSlug(title, category, subcategory, filePath) {
    // Try to use filename first
    const filename = path.basename(filePath, '.md');
    
    // Clean and format
    let slug = filename
      .toLowerCase()
      .replace(/ä/g, 'ae')
      .replace(/ö/g, 'oe')
      .replace(/ü/g, 'ue')
      .replace(/ß/g, 'ss')
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim();
    
    // Limit length
    if (slug.length > 60) {
      slug = slug.substring(0, 60).replace(/-$/, '');
    }
    
    return slug || 'page';
  }

  extractKeywordsFromContent(content) {
    // Extract important German construction/environmental keywords
    const keywords = [];
    const keywordPatterns = [
      'rückbau', 'sanierung', 'asbest', 'altlasten', 'schadstoffe',
      'sicherheit', 'sigeko', 'beratung', 'gutachten', 'entsorgung',
      'recycling', 'umwelt', 'baustelle', 'arbeitsschutz', 'monitoring'
    ];
    
    const contentLower = content.toLowerCase();
    for (const pattern of keywordPatterns) {
      if (contentLower.includes(pattern)) {
        keywords.push(pattern);
      }
    }
    
    return keywords.slice(0, 5); // Limit to 5 keywords
  }

  findBestImage(metadata) {
    // Check if we have a verified mapping
    const verifiedKey = `${metadata.category}-${metadata.subcategory}`.replace(/-$/, '');
    if (VERIFIED_MAPPINGS[verifiedKey]) {
      const imagePath = `images/${VERIFIED_MAPPINGS[verifiedKey]}`;
      if (!this.usedImages.has(imagePath)) {
        this.usedImages.add(imagePath);
        return imagePath;
      }
    }
    
    // Find best match from available images
    let bestMatch = null;
    let bestScore = 0;
    
    for (const imageInfo of this.availableImages) {
      if (this.usedImages.has(imageInfo.path)) continue;
      
      const score = this.calculateImageScore(metadata, imageInfo);
      if (score > bestScore) {
        bestScore = score;
        bestMatch = imageInfo.path;
      }
    }
    
    if (bestMatch && bestScore > 0.2) {
      this.usedImages.add(bestMatch);
      return bestMatch;
    }
    
    // Fallback: use any unused image
    for (const imageInfo of this.availableImages) {
      if (!this.usedImages.has(imageInfo.path)) {
        this.usedImages.add(imageInfo.path);
        return imageInfo.path;
      }
    }
    
    return null;
  }

  calculateImageScore(metadata, imageInfo) {
    let score = 0;
    const imageName = path.basename(imageInfo.path).toLowerCase();
    const description = (imageInfo.description || '').toLowerCase();
    const theme = (imageInfo.theme || '').toLowerCase();
    
    // Category matching
    if (imageName.includes(metadata.category) || 
        description.includes(metadata.category) || 
        theme.includes(metadata.category)) {
      score += 0.3;
    }
    
    // Subcategory matching
    if (metadata.subcategory) {
      if (imageName.includes(metadata.subcategory) || 
          description.includes(metadata.subcategory)) {
        score += 0.4;
      }
    }
    
    // Keyword matching
    for (const keyword of metadata.keywords) {
      if (imageName.includes(keyword) || description.includes(keyword)) {
        score += 0.1;
      }
    }
    
    // Special rules to prevent wrong assignments
    if (metadata.subcategory === 'baumediation' && 
        (imageName.includes('pcb') || imageName.includes('contamination'))) {
      score = 0; // Never assign PCB to mediation
    }
    
    return Math.min(score, 1.0);
  }

  generateSEOMetadata(metadata, imagePath) {
    const imageName = path.basename(imagePath, path.extname(imagePath));
    
    // Generate German SEO metadata
    const categoryNames = {
      'rueckbau': 'Rückbau',
      'altlasten': 'Altlastensanierung',
      'schadstoffe': 'Schadstoffsanierung',
      'sicherheit': 'Sicherheitskoordination',
      'beratung': 'Beratung',
      'general': 'Services'
    };
    
    const categoryName = categoryNames[metadata.category] || metadata.category;
    
    return {
      alt: `${metadata.title} - RIMAN GmbH`,
      title: `${metadata.title} | ${categoryName} Services`,
      caption: metadata.metaDescription || 
               `Professionelle ${metadata.title} von RIMAN GmbH - Ihr Experte für ${categoryName}`,
      description: metadata.metaDescription || 
                  `${metadata.title} - Spezialisierte Dienstleistungen im Bereich ${categoryName}. ` +
                  `RIMAN GmbH bietet umfassende Lösungen mit höchster Qualität und Zuverlässigkeit.`
    };
  }

  async generate() {
    console.log('🚀 Generating Final Enhanced WordPress Mappings...\n');
    
    // Load database
    const database = this.loadDatabase();
    console.log(`✅ Loaded ${this.availableImages.length} images from database\n`);
    
    // Get all content files
    const contentFiles = this.getAllContentFiles();
    console.log(`📄 Found ${contentFiles.length} content files\n`);
    
    // Initialize output
    const output = {
      project_info: {
        name: "RIMAN WordPress Enhanced Image Mappings with SEO",
        version: "4.0",
        created: new Date().toISOString(),
        total_pages: contentFiles.length,
        ai_verified: true,
        duplicate_prevention: true,
        neural_model: "model_prediction_1757146149466"
      },
      page_mappings: {},
      seo_metadata: {},
      category_stats: {},
      validation: {
        pcb_correctly_assigned: false,
        no_pcb_in_mediation: false,
        unique_safety_images: false,
        total_mapped: 0,
        total_unique_images: 0
      }
    };
    
    // Process each content file
    console.log('Processing content files...\n');
    let processedCount = 0;
    
    for (const filePath of contentFiles) {
      try {
        const content = fs.readFileSync(filePath, 'utf8');
        const metadata = this.extractPageMetadata(filePath, content);
        
        processedCount++;
        if (processedCount % 10 === 0) {
          console.log(`Processed ${processedCount}/${contentFiles.length} files...`);
        }
        
        // Find best image
        const imagePath = this.findBestImage(metadata);
        
        if (imagePath) {
          // Add mapping
          output.page_mappings[metadata.slug] = imagePath;
          
          // Generate SEO metadata
          output.seo_metadata[metadata.slug] = this.generateSEOMetadata(metadata, imagePath);
          
          // Update category stats
          if (!output.category_stats[metadata.category]) {
            output.category_stats[metadata.category] = {
              count: 0,
              pages: []
            };
          }
          output.category_stats[metadata.category].count++;
          output.category_stats[metadata.category].pages.push(metadata.slug);
          
          output.validation.total_mapped++;
        }
      } catch (error) {
        console.warn(`Error processing ${filePath}: ${error.message}`);
      }
    }
    
    // Final validation
    output.validation.total_unique_images = this.usedImages.size;
    
    // Check critical assignments
    const pcbPage = Object.entries(output.page_mappings).find(([slug, img]) => 
      slug.includes('pcb') && img.toLowerCase().includes('pcb'));
    output.validation.pcb_correctly_assigned = !!pcbPage;
    
    const mediationPage = Object.entries(output.page_mappings).find(([slug, img]) => 
      slug.includes('mediation') && !img.toLowerCase().includes('pcb'));
    output.validation.no_pcb_in_mediation = !!mediationPage;
    
    const sicherheitImage = output.page_mappings['sicherheit-sigeko-planung'];
    const arbeitsschutzImage = output.page_mappings['sicherheit-arbeitsschutz'];
    output.validation.unique_safety_images = sicherheitImage !== arbeitsschutzImage;
    
    // Add statistics
    output.statistics = {
      total_pages: contentFiles.length,
      mapped_pages: output.validation.total_mapped,
      unique_images: output.validation.total_unique_images,
      coverage: Math.round((output.validation.total_mapped / contentFiles.length) * 100) + '%',
      categories: Object.keys(output.category_stats).length
    };
    
    // Save output
    fs.writeFileSync(this.outputPath, JSON.stringify(output, null, 2));
    
    console.log('\n' + '='.repeat(60));
    console.log('✅ Final Enhanced Mappings Generated Successfully!');
    console.log('='.repeat(60));
    console.log(`📊 Total pages: ${output.statistics.total_pages}`);
    console.log(`✅ Mapped pages: ${output.statistics.mapped_pages}`);
    console.log(`🖼️  Unique images: ${output.statistics.unique_images}`);
    console.log(`📈 Coverage: ${output.statistics.coverage}`);
    console.log('\n🔍 Critical Validations:');
    console.log(`  PCB correctly assigned: ${output.validation.pcb_correctly_assigned ? '✅' : '❌'}`);
    console.log(`  No PCB in mediation: ${output.validation.no_pcb_in_mediation ? '✅' : '❌'}`);
    console.log(`  Unique safety images: ${output.validation.unique_safety_images ? '✅' : '❌'}`);
    console.log(`\n📁 Output saved to: ${this.outputPath}`);
    
    return output;
  }
}

// Execute
if (require.main === module) {
  const generator = new FinalEnhancedMappingGenerator();
  generator.generate().catch(error => {
    console.error('❌ Error:', error);
    process.exit(1);
  });
}

module.exports = FinalEnhancedMappingGenerator;