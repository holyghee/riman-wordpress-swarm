#!/usr/bin/env node

/**
 * Midjourney Image Generation Script for Schadstoffe Optimization
 * Interfaces with the running Midjourney MCP server to generate optimized images
 */

const { SCHADSTOFFE_CONFIG, RIMAN_CRITERIA } = require('./schadstoffe-optimization');
const fs = require('fs').promises;
const path = require('path');
const readline = require('readline');

// Colors for console output
const colors = {
  reset: '\x1b[0m',
  bright: '\x1b[1m',
  red: '\x1b[31m',
  green: '\x1b[32m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  magenta: '\x1b[35m',
  cyan: '\x1b[36m'
};

/**
 * Create readline interface for user interaction
 */
const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

/**
 * Utility function to ask questions
 */
function question(prompt) {
  return new Promise(resolve => rl.question(prompt, resolve));
}

/**
 * Display colored console output
 */
function log(color, text) {
  console.log(`${colors[color]}${text}${colors.reset}`);
}

/**
 * Generate Midjourney prompt with grid parameter
 */
function generateGridPrompt(basePrompt) {
  // Add --tile parameter for 2x2 grid generation
  return `${basePrompt} --tile`;
}

/**
 * Display subservice information
 */
function displaySubserviceInfo(key, config) {
  log('cyan', `\n${'='.repeat(60)}`);
  log('bright', `🎯 ${config.name} Optimization`);
  log('cyan', `${'='.repeat(60)}`);
  log('yellow', `Current Confidence: ${config.currentConfidence}`);
  log('green', `Target Confidence: ${config.targetConfidence} (${config.improvement})`);
  log('blue', `Current Image: ${config.currentImage}`);
  console.log('\n📝 Optimized Prompt:');
  console.log(`${colors.magenta}${config.prompt}${colors.reset}\n`);
}

/**
 * Display RIMAN evaluation criteria
 */
function displayRIMANCriteria() {
  log('bright', '\n🔍 RIMAN Evaluation Criteria:');
  for (const [key, description] of Object.entries(RIMAN_CRITERIA)) {
    log('yellow', `• ${key}: ${description}`);
  }
  console.log('');
}

/**
 * Process a single subservice
 */
async function processSubservice(key, config) {
  displaySubserviceInfo(key, config);
  
  log('green', '📋 Process Steps:');
  console.log('1. Generate 2x2 grid with 4 variants');
  console.log('2. Use /describe command to analyze each variant');
  console.log('3. Select best variant based on RIMAN criteria');
  console.log('4. Save optimized image');
  
  const proceed = await question('\n🤖 Ready to generate images? (y/n): ');
  
  if (proceed.toLowerCase() !== 'y') {
    log('yellow', 'Skipping this subservice...');
    return null;
  }
  
  // Step 1: Generate 2x2 grid
  log('blue', '\n🎨 Step 1: Generating 2x2 grid...');
  const gridPrompt = generateGridPrompt(config.prompt);
  log('magenta', `Prompt: ${gridPrompt}`);
  
  console.log('\n📝 Instructions for Midjourney Discord:');
  console.log('1. Copy the prompt above');
  console.log('2. Use /imagine command in Discord');
  console.log('3. Wait for the 2x2 grid to generate');
  console.log('4. Note down the job ID for analysis');
  
  const jobId = await question('\n🆔 Enter the Midjourney job ID (or "skip"): ');
  
  if (jobId === 'skip') {
    log('yellow', 'Skipping analysis for this subservice...');
    return null;
  }
  
  // Step 2: Analyze variants
  log('blue', '\n🔍 Step 2: Analyzing variants...');
  console.log('Use /describe command on each of the 4 variants');
  console.log('Evaluate each variant against RIMAN criteria:');
  displayRIMANCriteria();
  
  const variants = [];
  for (let i = 1; i <= 4; i++) {
    const analysis = await question(`\n📊 Analysis for Variant ${i} (or "skip"): `);
    if (analysis !== 'skip') {
      variants.push({
        id: i,
        analysis: analysis,
        score: await question(`Score (1-10) for RIMAN relevance: `)
      });
    }
  }
  
  // Step 3: Select best variant
  if (variants.length > 0) {
    log('blue', '\n🏆 Step 3: Selecting best variant...');
    const bestVariant = variants.reduce((best, current) => 
      parseInt(current.score) > parseInt(best.score) ? current : best
    );
    
    log('green', `\n✅ Best Variant: #${bestVariant.id} (Score: ${bestVariant.score}/10)`);
    log('cyan', `Analysis: ${bestVariant.analysis}`);
    
    const saveImage = await question('\n💾 Save this as optimized image? (y/n): ');
    
    if (saveImage.toLowerCase() === 'y') {
      const fileName = await question('📁 Enter filename (without extension): ');
      
      return {
        subservice: key,
        jobId: jobId,
        selectedVariant: bestVariant.id,
        score: bestVariant.score,
        analysis: bestVariant.analysis,
        fileName: fileName,
        gridPrompt: gridPrompt
      };
    }
  }
  
  return null;
}

/**
 * Save results to documentation
 */
async function saveResults(results) {
  if (results.length === 0) {
    log('yellow', 'No results to save.');
    return;
  }
  
  const resultsPath = path.join(__dirname, '../docs/midjourney-generation-results.md');
  
  let content = `# 🎨 Midjourney Generation Results - Schadstoffe Optimization

**Generated:** ${new Date().toISOString()}  
**Process:** 2x2 grid generation → variant analysis → RIMAN selection

---

`;

  for (const result of results) {
    const config = SCHADSTOFFE_CONFIG[result.subservice];
    content += `
## 🎯 ${config.name}

**Job ID:** ${result.jobId}  
**Selected Variant:** #${result.selectedVariant}  
**RIMAN Score:** ${result.score}/10  
**Filename:** ${result.fileName}

**Grid Prompt:**
\`\`\`
${result.gridPrompt}
\`\`\`

**Analysis:**
${result.analysis}

**Confidence Improvement:**
- Current: ${config.currentConfidence}
- Target: ${config.targetConfidence}
- Improvement: ${config.improvement}

---
`;
  }
  
  // Add summary
  const avgScore = results.reduce((sum, r) => sum + parseInt(r.score), 0) / results.length;
  content += `
## 📈 Generation Summary

**Total Subservices Processed:** ${results.length}/5  
**Average RIMAN Score:** ${avgScore.toFixed(1)}/10  
**Expected Confidence Improvement:** 50% → 84% (+34%)

## 🔍 RIMAN Evaluation Criteria Applied

${Object.entries(RIMAN_CRITERIA).map(([key, desc]) => `- **${key}:** ${desc}`).join('\n')}

---

## 📝 Next Steps

1. Download high-resolution versions of selected variants
2. Apply final optimization and branding
3. Update WordPress media library
4. Test confidence improvements with target audience
5. Document final performance metrics
`;

  await fs.writeFile(resultsPath, content);
  log('green', `\n✅ Results saved to: ${resultsPath}`);
}

/**
 * Main execution function
 */
async function main() {
  log('bright', '🚀 Midjourney Generator - Schadstoffe Optimization');
  log('cyan', '=' .repeat(60));
  
  console.log('\n🎯 This script will guide you through generating optimized images for:');
  for (const [key, config] of Object.entries(SCHADSTOFFE_CONFIG)) {
    log('yellow', `• ${config.name} (${config.currentConfidence} → ${config.targetConfidence})`);
  }
  
  displayRIMANCriteria();
  
  const startProcess = await question('\n🤖 Start the optimization process? (y/n): ');
  
  if (startProcess.toLowerCase() !== 'y') {
    log('yellow', 'Process cancelled.');
    rl.close();
    return;
  }
  
  const results = [];
  
  // Process each subservice
  for (const [key, config] of Object.entries(SCHADSTOFFE_CONFIG)) {
    const result = await processSubservice(key, config);
    if (result) {
      results.push(result);
    }
    
    if (Object.keys(SCHADSTOFFE_CONFIG).indexOf(key) < Object.keys(SCHADSTOFFE_CONFIG).length - 1) {
      const continueProcess = await question('\n➡️  Continue to next subservice? (y/n): ');
      if (continueProcess.toLowerCase() !== 'y') {
        break;
      }
    }
  }
  
  // Save results
  await saveResults(results);
  
  log('green', '\n✅ Optimization process complete!');
  log('cyan', `📊 Processed ${results.length} subservices successfully`);
  
  rl.close();
}

// Export for use in other scripts
module.exports = {
  processSubservice,
  generateGridPrompt,
  saveResults,
  main
};

// Run if called directly
if (require.main === module) {
  main().catch(error => {
    console.error('Error:', error);
    rl.close();
  });
}