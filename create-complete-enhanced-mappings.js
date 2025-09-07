#!/usr/bin/env node

/**
 * Create Complete Enhanced WordPress Mappings with SEO
 * Full complexity like wordpress-enhanced-image-mappings-seo.json
 * But with AI-corrected image assignments
 */

const fs = require('fs');
const path = require('path');

class CompleteEnhancedMappingCreator {
  constructor() {
    this.contentDir = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content';
    this.imageDatabase = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/midjpi_complete_database.json';
    this.outputPath = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-enhanced-image-mappings.json';
    this.usedImages = new Set();
    this.imageAssignments = new Map();
  }

  /**
   * Get all markdown files from content directory
   */
  getAllContentFiles() {
    const files = [];
    const scanDir = (dir) => {
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
    };
    scanDir(this.contentDir);
    return files;
  }

  /**
   * Extract metadata from content file
   */
  extractContentMetadata(filePath) {
    const content = fs.readFileSync(filePath, 'utf8');
    const relativePath = path.relative(this.contentDir, filePath);
    
    // Extract title
    const titleMatch = content.match(/^#\s+(.+)/m);
    const title = titleMatch ? titleMatch[1] : path.basename(filePath, '.md');
    
    // Extract meta description
    const descMatch = content.match(/\*\*Meta Description:\*\*\s*(.+)/);
    const description = descMatch ? descMatch[1] : '';
    
    // Extract keywords
    const keywordMatch = content.match(/\*\*SEO Keywords:\*\*\s*(.+)/);
    const keywords = keywordMatch ? keywordMatch[1].split(',').map(k => k.trim()) : [];
    
    // Determine category from path
    let category = 'general';
    let subcategory = '';
    
    const pathLower = filePath.toLowerCase();
    const contentLower = content.toLowerCase();
    
    if (pathLower.includes('rueckbau') || contentLower.includes('rückbau')) {
      category = 'rueckbau';
      if (pathLower.includes('planung')) subcategory = 'planung';
      else if (pathLower.includes('ausschreibung')) subcategory = 'ausschreibung';
      else if (pathLower.includes('durchfuehrung')) subcategory = 'durchfuehrung';
      else if (pathLower.includes('entsorgung')) subcategory = 'entsorgung';
      else if (pathLower.includes('recycling')) subcategory = 'recycling';
    } else if (pathLower.includes('altlasten')) {
      category = 'altlasten';
      if (pathLower.includes('erkundung')) subcategory = 'erkundung';
      else if (pathLower.includes('sanierungsplanung')) subcategory = 'sanierungsplanung';
      else if (pathLower.includes('bodensanierung')) subcategory = 'bodensanierung';
      else if (pathLower.includes('grundwasser')) subcategory = 'grundwassersanierung';
      else if (pathLower.includes('monitoring')) subcategory = 'monitoring';
    } else if (pathLower.includes('schadstoff') || contentLower.includes('asbest') || contentLower.includes('pcb')) {
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
    } else if (pathLower.includes('beratung') || contentLower.includes('mediation')) {
      category = 'beratung';
      if (contentLower.includes('mediation')) subcategory = 'baumediation';
      else if (pathLower.includes('projektberatung')) subcategory = 'projektberatung';
      else if (pathLower.includes('gutachten')) subcategory = 'gutachten';
      else if (pathLower.includes('schulung')) subcategory = 'schulungen';
      else if (pathLower.includes('compliance')) subcategory = 'compliance';
    }
    
    // Generate WordPress slug
    const slug = this.generateSlug(title, category, subcategory);
    
    return {
      filePath: relativePath,
      title,
      description,
      keywords,
      category,
      subcategory,
      slug
    };
  }

  /**
   * Generate WordPress-compatible slug
   */
  generateSlug(title, category, subcategory) {
    let slug = title
      .toLowerCase()
      .replace(/ä/g, 'ae')
      .replace(/ö/g, 'oe')
      .replace(/ü/g, 'ue')
      .replace(/ß/g, 'ss')
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim();
    
    // Add category prefix if not already present
    if (category && !slug.startsWith(category)) {
      slug = `${category}-${slug}`;
    }
    
    // Add subcategory if present
    if (subcategory && !slug.includes(subcategory)) {
      slug = `${slug.split('-').slice(0, 3).join('-')}-${subcategory}`;
    }
    
    return slug.substring(0, 60);
  }

  /**
   * Find best matching image using AI logic
   */
  findBestImage(metadata, database) {
    // Critical rules to prevent wrong assignments
    const forbiddenAssignments = {
      'baumediation': ['pcb', 'asbest', 'contamination', 'hazardous'],
      'schulungen': ['pcb', 'asbest', 'demolition'],
      'projektberatung': ['pcb', 'contamination', 'hazardous']
    };
    
    // Priority matching for specific content types
    const priorityKeywords = {
      'pcb': ['pcb', 'polychlorinated', 'transformer', 'electrical_equipment'],
      'asbest': ['asbestos', 'asbest', 'removal', 'remediation'],
      'kmf': ['kmf', 'mineral', 'fiber', 'insulation'],
      'pak': ['pak', 'tar', 'bitumen', 'polycyclic'],
      'mediation': ['mediation', 'consulting', 'meeting', 'discussion', 'conflict'],
      'sigeko': ['safety', 'coordination', 'protection', 'security'],
      'arbeitsschutz': ['workplace', 'safety', 'protection', 'prevention']
    };
    
    let bestMatch = null;
    let bestScore = 0;
    
    for (const agent of database.agents) {
      for (const [quadrant, description] of Object.entries(agent.quadrants)) {
        const imagePath = agent.image_paths ? agent.image_paths[quadrant] : null;
        
        // Skip if no image path or already used
        if (!imagePath || this.usedImages.has(imagePath)) continue;
        
        const imageName = path.basename(imagePath).toLowerCase();
        const descLower = description.toLowerCase();
        
        // Check forbidden assignments
        if (metadata.subcategory && forbiddenAssignments[metadata.subcategory]) {
          const forbidden = forbiddenAssignments[metadata.subcategory];
          if (forbidden.some(term => imageName.includes(term) || descLower.includes(term))) {
            continue; // Skip this image
          }
        }
        
        // Calculate match score
        let score = 0;
        
        // Category match
        if (imageName.includes(metadata.category) || descLower.includes(metadata.category)) {
          score += 0.3;
        }
        
        // Subcategory match (higher priority)
        if (metadata.subcategory) {
          const subKeywords = priorityKeywords[metadata.subcategory] || [metadata.subcategory];
          for (const keyword of subKeywords) {
            if (imageName.includes(keyword) || descLower.includes(keyword)) {
              score += 0.5;
              break;
            }
          }
        }
        
        // Keyword matches
        for (const keyword of metadata.keywords) {
          if (imageName.includes(keyword.toLowerCase()) || descLower.includes(keyword.toLowerCase())) {
            score += 0.1;
          }
        }
        
        // Theme match from agent
        if (agent.theme.toLowerCase().includes(metadata.category)) {
          score += 0.1;
        }
        
        if (score > bestScore) {
          bestScore = score;
          bestMatch = {
            path: imagePath,
            agent_id: agent.agent_id,
            quadrant: quadrant,
            description: description,
            score: score
          };
        }
      }
    }
    
    return bestMatch;
  }

  /**
   * Generate SEO metadata for image
   */
  generateSEOMetadata(metadata, imagePath) {
    const imageName = path.basename(imagePath, path.extname(imagePath));
    
    // Generate alt text
    let altText = metadata.title;
    if (metadata.subcategory) {
      const subcatNames = {
        'asbest': 'Asbestsanierung',
        'pcb': 'PCB-Sanierung',
        'kmf': 'KMF-Sanierung',
        'pak': 'PAK-Sanierung',
        'schwermetalle': 'Schwermetallsanierung',
        'baumediation': 'Baumediation',
        'sigeko-planung': 'SiGeKo Planung',
        'sigeko-ausfuehrung': 'SiGeKo Ausführung',
        'arbeitsschutz': 'Arbeitsschutz'
      };
      altText = subcatNames[metadata.subcategory] || metadata.title;
    }
    altText += ' - RIMAN GmbH';
    
    // Generate title
    const title = `${metadata.title} | ${metadata.category.charAt(0).toUpperCase() + metadata.category.slice(1)} Services`;
    
    // Generate caption
    const caption = metadata.description || `Professionelle ${metadata.title} von RIMAN GmbH - Ihr Experte für ${metadata.category}`;
    
    // Generate description
    const description = `${metadata.title} - ${metadata.description || 'Spezialisierte Dienstleistungen'}. ` +
                       `RIMAN GmbH bietet umfassende Lösungen im Bereich ${metadata.category}.`;
    
    return {
      alt: altText,
      title: title,
      caption: caption,
      description: description
    };
  }

  /**
   * Main processing function
   */
  async process() {
    console.log('🚀 Creating Complete Enhanced WordPress Mappings...\n');
    
    // Load image database
    const database = JSON.parse(fs.readFileSync(this.imageDatabase, 'utf8'));
    console.log(`✅ Loaded ${database.agents.length} agents\n`);
    
    // Get all content files
    const contentFiles = this.getAllContentFiles();
    console.log(`📄 Found ${contentFiles.length} content files\n`);
    
    // Initialize output structure
    const output = {
      project_info: {
        name: "RIMAN WordPress Enhanced Image Mappings with SEO",
        version: "3.0",
        created: new Date().toISOString(),
        total_pages: contentFiles.length,
        ai_enhanced: true,
        duplicate_prevention: true
      },
      page_mappings: {},
      seo_metadata: {},
      category_stats: {},
      image_usage: {}
    };
    
    // Process each content file
    console.log('Processing content files...\n');
    
    for (let i = 0; i < contentFiles.length; i++) {
      const filePath = contentFiles[i];
      const metadata = this.extractContentMetadata(filePath);
      
      console.log(`[${i+1}/${contentFiles.length}] ${metadata.slug}`);
      
      // Find best matching image
      const imageMatch = this.findBestImage(metadata, database);
      
      if (imageMatch && imageMatch.score > 0.2) {
        // Use relative path for images
        const imagePath = `images/${path.basename(imageMatch.path)}`;
        
        // Add to mappings
        output.page_mappings[metadata.slug] = imagePath;
        this.usedImages.add(imageMatch.path);
        
        // Generate SEO metadata
        output.seo_metadata[metadata.slug] = this.generateSEOMetadata(metadata, imagePath);
        
        // Track image usage
        if (!output.image_usage[imagePath]) {
          output.image_usage[imagePath] = [];
        }
        output.image_usage[imagePath].push(metadata.slug);
        
        // Update category stats
        if (!output.category_stats[metadata.category]) {
          output.category_stats[metadata.category] = {
            count: 0,
            pages: []
          };
        }
        output.category_stats[metadata.category].count++;
        output.category_stats[metadata.category].pages.push(metadata.slug);
        
        console.log(`  ✅ Matched: ${path.basename(imagePath)} (Score: ${Math.round(imageMatch.score * 100)}%)`);
      } else {
        console.log(`  ⚠️  No suitable match found`);
      }
    }
    
    // Add summary statistics
    output.statistics = {
      total_pages: contentFiles.length,
      mapped_pages: Object.keys(output.page_mappings).length,
      unique_images: this.usedImages.size,
      coverage: Math.round((Object.keys(output.page_mappings).length / contentFiles.length) * 100) + '%',
      categories: Object.keys(output.category_stats).length,
      duplicates_prevented: Array.from(Object.values(output.image_usage)).every(pages => pages.length === 1)
    };
    
    // Critical validation checks
    output.validation = {
      pcb_correctly_assigned: false,
      no_pcb_in_mediation: false,
      unique_safety_images: false
    };
    
    // Check PCB assignment
    for (const [slug, image] of Object.entries(output.page_mappings)) {
      if (slug.includes('pcb') && image.toLowerCase().includes('pcb')) {
        output.validation.pcb_correctly_assigned = true;
      }
      if (slug.includes('mediation') && !image.toLowerCase().includes('pcb')) {
        output.validation.no_pcb_in_mediation = true;
      }
    }
    
    // Check unique safety images
    const sicherheitImage = output.page_mappings['sicherheit-sigeko-planung'];
    const arbeitsschutzImage = output.page_mappings['sicherheit-arbeitsschutz'];
    if (sicherheitImage && arbeitsschutzImage && sicherheitImage !== arbeitsschutzImage) {
      output.validation.unique_safety_images = true;
    }
    
    // Save output
    fs.writeFileSync(this.outputPath, JSON.stringify(output, null, 2));
    
    console.log('\n' + '='.repeat(60));
    console.log('✅ Complete Enhanced Mappings Created Successfully!');
    console.log('='.repeat(60));
    console.log(`📊 Total pages: ${output.statistics.total_pages}`);
    console.log(`✅ Mapped pages: ${output.statistics.mapped_pages}`);
    console.log(`🖼️  Unique images: ${output.statistics.unique_images}`);
    console.log(`📈 Coverage: ${output.statistics.coverage}`);
    console.log(`🚫 Duplicates prevented: ${output.statistics.duplicates_prevented}`);
    console.log('\n🔍 Validation Results:');
    console.log(`  PCB correctly assigned: ${output.validation.pcb_correctly_assigned ? '✅' : '❌'}`);
    console.log(`  No PCB in mediation: ${output.validation.no_pcb_in_mediation ? '✅' : '❌'}`);
    console.log(`  Unique safety images: ${output.validation.unique_safety_images ? '✅' : '❌'}`);
    console.log(`\n📁 Output saved to: ${this.outputPath}`);
    
    return output;
  }
}

// Execute
if (require.main === module) {
  const creator = new CompleteEnhancedMappingCreator();
  creator.process().catch(error => {
    console.error('❌ Error:', error);
    process.exit(1);
  });
}

module.exports = CompleteEnhancedMappingCreator;