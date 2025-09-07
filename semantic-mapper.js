#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// German umlaut conversion for SEO URLs
const convertUmlauts = (text) => {
  return text
    .replace(/ä/g, 'ae')
    .replace(/ö/g, 'oe')
    .replace(/ü/g, 'ue')
    .replace(/Ä/g, 'Ae')
    .replace(/Ö/g, 'Oe')
    .replace(/Ü/g, 'Ue')
    .replace(/ß/g, 'ss');
};

// Generate SEO-optimized German URL slug
const generateSeoSlug = (content, category) => {
  const categoryPrefixes = {
    'rueckbau': 'rueckbau-',
    'altlasten': 'altlastensanierung-',
    'schadstoffe': 'schadstoffe-',
    'sicherheit': 'sicherheitskoordination-',
    'beratung': 'beratung-'
  };
  
  // Extract key terms from content
  const title = content.match(/^#\s+(.+)/m)?.[1] || '';
  const keywords = content.match(/\*\*SEO Keywords:\*\*\s*(.+)/)?.[1]?.split(',').map(k => k.trim()) || [];
  
  let slug = '';
  
  // Add category prefix
  if (category && categoryPrefixes[category]) {
    slug += categoryPrefixes[category];
  }
  
  // Use title or first keyword
  const baseSlug = title || keywords[0] || 'service';
  
  slug += convertUmlauts(baseSlug)
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .substring(0, 60);
  
  return slug;
};

// Extract keywords and themes from content
const extractContentFeatures = (content, filePath) => {
  const features = {
    title: '',
    keywords: [],
    category: '',
    description: '',
    themes: [],
    focus: ''
  };
  
  // Extract title
  const titleMatch = content.match(/^#\s+(.+)/m);
  features.title = titleMatch ? titleMatch[1] : path.basename(filePath, '.md');
  
  // Extract SEO keywords
  const keywordMatch = content.match(/\*\*SEO Keywords:\*\*\s*(.+)/);
  if (keywordMatch) {
    features.keywords = keywordMatch[1].split(',').map(k => k.trim().toLowerCase());
  }
  
  // Extract meta description
  const descMatch = content.match(/\*\*Meta Description:\*\*\s*(.+)/);
  features.description = descMatch ? descMatch[1] : '';
  
  // Determine category from file path
  if (filePath.includes('rueckbau')) features.category = 'rueckbau';
  else if (filePath.includes('altlasten')) features.category = 'altlasten';
  else if (filePath.includes('schadstoff')) features.category = 'schadstoffe';
  else if (filePath.includes('sicherheit')) features.category = 'sicherheit';
  else if (filePath.includes('beratung')) features.category = 'beratung';
  
  // Extract content themes
  const text = content.toLowerCase();
  const themeKeywords = {
    'safety_training': ['schulung', 'training', 'ausbildung', 'seminar', 'kurs'],
    'compliance': ['compliance', 'vorschrift', 'regelung', 'zertifikat', 'nachweis'],
    'environmental': ['umwelt', 'emission', 'luftqualität', 'boden', 'wasser'],
    'construction': ['bau', 'abbruch', 'sanierung', 'renovierung', 'gebäude'],
    'hazardous_materials': ['asbest', 'kmf', 'pcb', 'pak', 'schwermetall', 'gefahrstoff'],
    'technical_analysis': ['untersuchung', 'analyse', 'messung', 'prüfung', 'gutachten'],
    'documentation': ['dokumentation', 'bericht', 'nachweis', 'protokoll', 'aufzeichnung'],
    'management': ['management', 'koordination', 'planung', 'überwachung', 'steuerung']
  };
  
  for (const [theme, keywords] of Object.entries(themeKeywords)) {
    if (keywords.some(keyword => text.includes(keyword))) {
      features.themes.push(theme);
    }
  }
  
  // Determine primary focus
  if (features.themes.includes('safety_training')) features.focus = 'training';
  else if (features.themes.includes('hazardous_materials')) features.focus = 'hazmat';
  else if (features.themes.includes('technical_analysis')) features.focus = 'analysis';
  else if (features.themes.includes('compliance')) features.focus = 'compliance';
  else features.focus = 'general';
  
  return features;
};

// Calculate semantic similarity score
const calculateSimilarity = (contentFeatures, agentTheme, quadrantDesc) => {
  let themeScore = 0;
  let quadrantScore = 0;
  
  // Theme matching (40% weight)
  const themeText = agentTheme.toLowerCase();
  const contentThemes = contentFeatures.themes.join(' ');
  const contentText = `${contentFeatures.title} ${contentFeatures.description} ${contentThemes}`.toLowerCase();
  
  // Simple keyword matching for theme
  const themeWords = themeText.split(' ');
  const contentWords = contentText.split(' ');
  const themeMatches = themeWords.filter(word => 
    word.length > 3 && contentWords.some(cword => cword.includes(word) || word.includes(cword))
  );
  themeScore = Math.min(themeMatches.length / Math.max(themeWords.length, 1), 1.0);
  
  // Quadrant description matching (60% weight)
  const quadrantText = quadrantDesc.toLowerCase();
  const quadrantWords = quadrantText.split(' ');
  const quadrantMatches = quadrantWords.filter(word => 
    word.length > 3 && contentWords.some(cword => cword.includes(word) || word.includes(cword))
  );
  quadrantScore = Math.min(quadrantMatches.length / Math.max(quadrantWords.length, 1) * 2, 1.0);
  
  // Bonus for category/focus alignment
  let focusBonus = 0;
  if (contentFeatures.focus === 'training' && themeText.includes('training')) focusBonus = 0.2;
  if (contentFeatures.focus === 'hazmat' && (themeText.includes('safety') || themeText.includes('environmental'))) focusBonus = 0.2;
  if (contentFeatures.focus === 'analysis' && (themeText.includes('assessment') || themeText.includes('evaluation'))) focusBonus = 0.2;
  
  const finalScore = (themeScore * 0.4) + (quadrantScore * 0.6) + focusBonus;
  return Math.min(finalScore, 1.0);
};

// Main semantic mapping function
const createSemanticMapping = () => {
  console.log('🔄 Loading MIDJPI database...');
  
  // Load database
  const databasePath = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/midjpi_complete_database.json';
  const database = JSON.parse(fs.readFileSync(databasePath, 'utf8'));
  
  console.log(`✅ Loaded ${database.agents.length} agents with ${database.agents.length * 4} quadrants`);
  
  // Get all content files
  const contentDir = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content';
  const getAllFiles = (dir) => {
    let results = [];
    const files = fs.readdirSync(dir);
    for (const file of files) {
      const fullPath = path.join(dir, file);
      const stat = fs.statSync(fullPath);
      if (stat.isDirectory()) {
        results = results.concat(getAllFiles(fullPath));
      } else if (file.endsWith('.md')) {
        results.push(fullPath);
      }
    }
    return results;
  };
  
  const contentFiles = getAllFiles(contentDir);
  console.log(`📄 Found ${contentFiles.length} content files`);
  
  const mappings = [];
  const usedImages = new Set();
  let totalConfidence = 0;
  
  console.log('🧠 Processing semantic mappings...');
  
  // Process each content file
  for (let i = 0; i < contentFiles.length; i++) {
    const filePath = contentFiles[i];
    console.log(`📊 [${i+1}/${contentFiles.length}] Processing: ${path.relative(contentDir, filePath)}`);
    
    try {
      const content = fs.readFileSync(filePath, 'utf8');
      const features = extractContentFeatures(content, filePath);
      
      let bestMatch = null;
      let bestScore = 0;
      
      // Find best matching image
      for (const agent of database.agents) {
        for (const [quadrant, description] of Object.entries(agent.quadrants)) {
          // Skip if image already used
          const imagePath = agent.image_paths[quadrant];
          if (usedImages.has(imagePath)) continue;
          
          const score = calculateSimilarity(features, agent.theme, description);
          
          if (score > bestScore) {
            bestScore = score;
            bestMatch = {
              agent_id: agent.agent_id,
              grid_number: agent.grid_number,
              theme: agent.theme,
              quadrant: quadrant,
              image_path: imagePath,
              quadrant_description: description
            };
          }
        }
      }
      
      if (bestMatch) {
        usedImages.add(bestMatch.image_path);
        totalConfidence += bestScore;
        
        const seoSlug = generateSeoSlug(content, features.category);
        
        mappings.push({
          content_file: path.relative(contentDir, filePath),
          content_title: features.title,
          content_category: features.category,
          content_keywords: features.keywords,
          content_themes: features.themes,
          content_focus: features.focus,
          matched_image: {
            agent_id: bestMatch.agent_id,
            grid_number: bestMatch.grid_number,
            theme: bestMatch.theme,
            quadrant: bestMatch.quadrant,
            image_path: bestMatch.image_path,
            description: bestMatch.quadrant_description
          },
          seo_optimization: {
            url_slug: seoSlug,
            meta_title: `${features.title} | RIMAN GmbH`,
            meta_description: features.description || `${features.title} - Professionelle Lösungen von RIMAN GmbH`,
            focus_keywords: features.keywords.slice(0, 3)
          },
          confidence_score: Math.round(bestScore * 100) / 100,
          mapping_details: {
            theme_weight: 0.4,
            quadrant_weight: 0.6,
            total_score: bestScore
          }
        });
        
        console.log(`   ✅ Matched with Agent ${bestMatch.agent_id} (${bestMatch.quadrant}) - Score: ${Math.round(bestScore * 100)}%`);
      } else {
        console.log(`   ⚠️  No suitable match found`);
      }
    } catch (error) {
      console.error(`   ❌ Error processing ${filePath}:`, error.message);
    }
  }
  
  // Generate statistics
  const stats = {
    total_content_files: contentFiles.length,
    successfully_mapped: mappings.length,
    mapping_coverage: Math.round((mappings.length / contentFiles.length) * 100),
    average_confidence: Math.round((totalConfidence / mappings.length) * 100) / 100,
    unique_images_used: usedImages.size,
    categories: {}
  };
  
  // Category statistics
  for (const mapping of mappings) {
    const cat = mapping.content_category || 'other';
    stats.categories[cat] = (stats.categories[cat] || 0) + 1;
  }
  
  // Create final output
  const output = {
    project_info: {
      name: "RIMAN Content-Image-SEO Mapping",
      execution_date: new Date().toISOString().split('T')[0],
      total_mappings: mappings.length,
      database_source: "midjpi_complete_database.json"
    },
    statistics: stats,
    mappings: mappings.sort((a, b) => b.confidence_score - a.confidence_score)
  };
  
  // Save output
  const outputPath = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/content-image-seo-mapping.json';
  fs.writeFileSync(outputPath, JSON.stringify(output, null, 2));
  
  console.log('\n📊 SEMANTIC MAPPING COMPLETE');
  console.log('=====================================');
  console.log(`📄 Content files processed: ${stats.total_content_files}`);
  console.log(`✅ Successfully mapped: ${stats.successfully_mapped}`);
  console.log(`📈 Coverage: ${stats.mapping_coverage}%`);
  console.log(`🎯 Average confidence: ${stats.average_confidence}`);
  console.log(`🖼️  Unique images used: ${stats.unique_images_used}`);
  console.log(`📁 Output saved to: ${outputPath}`);
  console.log('\n📊 Category Distribution:');
  for (const [cat, count] of Object.entries(stats.categories)) {
    console.log(`   ${cat}: ${count} files`);
  }
  
  return output;
};

// Execute if run directly
if (require.main === module) {
  try {
    createSemanticMapping();
  } catch (error) {
    console.error('❌ Fatal error:', error);
    process.exit(1);
  }
}

module.exports = { createSemanticMapping };