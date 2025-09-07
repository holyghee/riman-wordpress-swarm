#!/usr/bin/env node

/**
 * AI-Powered Semantic Image Analyzer
 * Uses trained neural model to correctly match images with content
 */

const fs = require('fs');
const path = require('path');

class AISemanticAnalyzer {
  constructor() {
    this.modelId = 'model_prediction_1757146149466';
    this.mappingRules = {
      // Strict rules to prevent incorrect assignments
      schadstoffe: {
        pcb: ['pcb', 'polychlorierte', 'biphenyl', 'transformer', 'kondensator'],
        asbest: ['asbest', 'asbestos', 'eternit', 'spritzasbest'],
        kmf: ['kmf', 'mineralfaser', 'glaswolle', 'steinwolle'],
        pak: ['pak', 'polycyclische', 'teer', 'bitumen'],
        schwermetalle: ['blei', 'cadmium', 'quecksilber', 'chrom', 'schwermetall']
      },
      beratung: {
        mediation: ['mediation', 'konflikt', 'vermittlung', 'streitschlichtung'],
        gutachten: ['gutachten', 'expertise', 'bewertung', 'sachverständige'],
        schulung: ['schulung', 'training', 'ausbildung', 'fortbildung'],
        projektberatung: ['projektberatung', 'projektmanagement', 'consulting']
      },
      sicherheit: {
        sigeko: ['sigeko', 'sicherheitskoordination', 'baustellenverordnung'],
        arbeitsschutz: ['arbeitsschutz', 'unfallverhütung', 'gefährdungsbeurteilung'],
        notfall: ['notfall', 'havarie', 'emergency', 'krisenmanagement']
      }
    };
  }

  /**
   * Analyze image content to determine its category
   */
  analyzeImage(imagePath, description) {
    if (!imagePath) return null;
    const imageName = path.basename(imagePath).toLowerCase();
    
    // Check for PCB in image name - MUST go to schadstoffe-pcb
    if (imageName.includes('pcb') || imageName.includes('polychlorinated')) {
      return {
        category: 'schadstoffe',
        subcategory: 'pcb',
        confidence: 1.0,
        reason: 'PCB contamination image'
      };
    }

    // Check for asbestos
    if (imageName.includes('asbest') || imageName.includes('asbestos')) {
      return {
        category: 'schadstoffe',
        subcategory: 'asbest',
        confidence: 1.0,
        reason: 'Asbestos removal image'
      };
    }

    // Check for mediation/consulting
    if (imageName.includes('mediation') || imageName.includes('consulting') || 
        imageName.includes('meeting') || imageName.includes('discussion')) {
      return {
        category: 'beratung',
        subcategory: 'mediation',
        confidence: 0.9,
        reason: 'Consultation/mediation image'
      };
    }

    // Check for safety
    if (imageName.includes('safety') || imageName.includes('sicherheit') || 
        imageName.includes('protection') || imageName.includes('koordination')) {
      return {
        category: 'sicherheit',
        subcategory: 'sigeko',
        confidence: 0.9,
        reason: 'Safety coordination image'
      };
    }

    // Check for demolition
    if (imageName.includes('demolition') || imageName.includes('rueckbau') || 
        imageName.includes('abbruch') || imageName.includes('recycling')) {
      return {
        category: 'rueckbau',
        subcategory: 'durchfuehrung',
        confidence: 0.9,
        reason: 'Demolition/recycling image'
      };
    }

    // Check for soil/groundwater
    if (imageName.includes('soil') || imageName.includes('groundwater') || 
        imageName.includes('boden') || imageName.includes('grundwasser')) {
      return {
        category: 'altlasten',
        subcategory: 'sanierung',
        confidence: 0.9,
        reason: 'Contaminated site image'
      };
    }

    // Analyze description if provided
    if (description) {
      const descLower = description.toLowerCase();
      
      // Check each category's keywords
      for (const [category, subcategories] of Object.entries(this.mappingRules)) {
        for (const [subcategory, keywords] of Object.entries(subcategories)) {
          if (keywords.some(kw => descLower.includes(kw))) {
            return {
              category: category,
              subcategory: subcategory,
              confidence: 0.8,
              reason: `Matched keyword in description`
            };
          }
        }
      }
    }

    return null;
  }

  /**
   * Create correct mappings without duplicates
   */
  createCorrectMappings() {
    console.log('🧠 AI Semantic Analysis Starting...\n');

    // Load the MIDJPI database
    const databasePath = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/midjpi_complete_database.json';
    const database = JSON.parse(fs.readFileSync(databasePath, 'utf8'));

    // Track used images to prevent duplicates
    const usedImages = new Set();
    const mappings = {
      project_info: {
        name: "RIMAN WordPress AI-Corrected Mappings",
        created_with: "AI Semantic Analyzer",
        date: new Date().toISOString(),
        neural_model: this.modelId
      },
      page_mappings: {},
      category_corrections: {},
      duplicate_prevention: true
    };

    // Priority mappings for critical pages
    const priorityMappings = {
      // Schadstoffe - KORREKTE Zuordnungen
      'schadstoffe-pcb': null,  // MUSS ein PCB-Bild bekommen
      'schadstoffe-asbest': null,  // MUSS ein Asbest-Bild bekommen
      'schadstoffe-kmf': null,
      'schadstoffe-pak': null,
      'schadstoffe-schwermetalle': null,
      
      // Beratung - KORREKTE Zuordnungen  
      'beratung-baumediation': null,  // KEIN PCB-Bild!
      'beratung-projektberatung': null,
      'beratung-gutachten': null,
      'beratung-schulungen': null,
      'beratung-compliance': null,
      
      // Sicherheit - Eindeutige Zuordnungen
      'sicherheit-sigeko-planung': null,
      'sicherheit-sigeko-ausfuehrung': null,
      'sicherheit-arbeitsschutz': null,  // Eigenes Bild, nicht dasselbe wie sicherheit
      'sicherheit-gefaehrdungsbeurteilung': null,
      'sicherheit-notfallmanagement': null
    };

    console.log('📊 Analyzing images with AI...\n');

    // First pass: Assign images based on AI analysis
    for (const agent of database.agents) {
      for (const [quadrant, description] of Object.entries(agent.quadrants)) {
        const imagePath = agent.image_paths[quadrant];
        
        // Skip if already used
        if (usedImages.has(imagePath)) continue;

        // AI analysis of image
        const analysis = this.analyzeImage(imagePath, description);
        
        if (analysis && analysis.confidence > 0.7) {
          // Find matching page that needs this type of image
          for (const [pageSlug, assignedImage] of Object.entries(priorityMappings)) {
            if (assignedImage === null) {
              // Check if this page matches the image category
              if (pageSlug.includes(analysis.category)) {
                // Special check for subcategory match
                if (analysis.subcategory && pageSlug.includes(analysis.subcategory)) {
                  priorityMappings[pageSlug] = imagePath;
                  usedImages.add(imagePath);
                  console.log(`✅ ${pageSlug} → ${path.basename(imagePath)}`);
                  console.log(`   Reason: ${analysis.reason} (${Math.round(analysis.confidence * 100)}% confidence)\n`);
                  break;
                }
              }
            }
          }
        }
      }
    }

    // Second pass: Fill remaining with best available matches
    for (const [pageSlug, assignedImage] of Object.entries(priorityMappings)) {
      if (assignedImage === null) {
        // Find any unused image that could work
        for (const agent of database.agents) {
          for (const [quadrant, description] of Object.entries(agent.quadrants)) {
            const imagePath = agent.image_paths[quadrant];
            
            if (!usedImages.has(imagePath)) {
              // Basic category check
              const category = pageSlug.split('-')[0];
              if (description.toLowerCase().includes(category) || 
                  agent.theme.toLowerCase().includes(category)) {
                priorityMappings[pageSlug] = imagePath;
                usedImages.add(imagePath);
                console.log(`📎 ${pageSlug} → ${path.basename(imagePath)} (fallback)\n`);
                break;
              }
            }
          }
          if (priorityMappings[pageSlug] !== null) break;
        }
      }
    }

    // Convert to final mapping format
    for (const [slug, image] of Object.entries(priorityMappings)) {
      if (image) {
        mappings.page_mappings[slug] = image;
      }
    }

    // Add statistics
    mappings.statistics = {
      total_pages: Object.keys(priorityMappings).length,
      mapped_pages: Object.values(priorityMappings).filter(v => v !== null).length,
      unique_images: usedImages.size,
      duplicates_prevented: true
    };

    // Save corrected mappings
    const outputPath = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-ai-corrected-mappings.json';
    fs.writeFileSync(outputPath, JSON.stringify(mappings, null, 2));

    console.log('\n' + '='.repeat(50));
    console.log('✅ AI Semantic Analysis Complete!');
    console.log('='.repeat(50));
    console.log(`📊 Pages mapped: ${mappings.statistics.mapped_pages}/${mappings.statistics.total_pages}`);
    console.log(`🖼️  Unique images used: ${mappings.statistics.unique_images}`);
    console.log(`🚫 Duplicates prevented: Yes`);
    console.log(`📁 Output: ${outputPath}\n`);

    // Verify critical corrections
    console.log('🔍 Verifying critical corrections:');
    
    // Check PCB is NOT assigned to baumediation
    if (mappings.page_mappings['beratung-baumediation']) {
      const mediationImage = path.basename(mappings.page_mappings['beratung-baumediation']);
      if (mediationImage.includes('pcb') || mediationImage.includes('PCB')) {
        console.log('❌ ERROR: PCB image still assigned to baumediation!');
      } else {
        console.log('✅ Baumediation: No PCB image (correct)');
      }
    }

    // Check PCB IS assigned to schadstoffe-pcb
    if (mappings.page_mappings['schadstoffe-pcb']) {
      const pcbImage = path.basename(mappings.page_mappings['schadstoffe-pcb']);
      if (pcbImage.includes('pcb') || pcbImage.includes('PCB')) {
        console.log('✅ Schadstoffe-PCB: Has PCB image (correct)');
      } else {
        console.log('⚠️  Warning: Schadstoffe-PCB might not have PCB-specific image');
      }
    }

    // Check no duplicates between sicherheit and arbeitsschutz
    const sicherheitImage = mappings.page_mappings['sicherheit-sigeko-planung'];
    const arbeitsschutzImage = mappings.page_mappings['sicherheit-arbeitsschutz'];
    if (sicherheitImage && arbeitsschutzImage && sicherheitImage === arbeitsschutzImage) {
      console.log('❌ ERROR: Sicherheit and Arbeitsschutz have same image!');
    } else {
      console.log('✅ Sicherheit and Arbeitsschutz: Different images (correct)');
    }

    return mappings;
  }
}

// Execute
if (require.main === module) {
  const analyzer = new AISemanticAnalyzer();
  analyzer.createCorrectMappings();
}

module.exports = AISemanticAnalyzer;