#!/usr/bin/env node
// Convert wordpress-semantic-unique-mappings.json to wordpress-enhanced-image-mappings-seo.json shape

const fs = require('fs');
const path = require('path');

const ROOT = process.cwd();
const INPUT = path.join(ROOT, 'wordpress-semantic-unique-mappings.json');
const OUTPUT = path.join(ROOT, 'wordpress-enhanced-image-mappings-seo.json');

if (!fs.existsSync(INPUT)) {
  console.error(`❌ Input not found: ${INPUT}`);
  process.exit(1);
}

const semantic = JSON.parse(fs.readFileSync(INPUT, 'utf8'));

// Maps semantic main keys to WP slugs and display names
const MAIN_MAP = {
  'rueckbaumanagement': { slug: 'rueckbau', name: 'Rückbaumanagement' },
  'altlastensanierung': { slug: 'altlasten', name: 'Altlastensanierung' },
  'schadstoff-management': { slug: 'schadstoffe', name: 'Schadstoff-Management' },
  'sicherheitskoordination': { slug: 'sicherheit', name: 'Sicherheitskoordination' },
  'beratung-mediation': { slug: 'beratung', name: 'Beratung & Mediation' },
};

// Convert main categories
const main_categories = {};
for (const [key, data] of Object.entries(semantic.main_categories || {})) {
  const map = MAIN_MAP[key];
  if (!map) continue;
  main_categories[map.slug] = {
    image: data.image,
    alt: data.alt,
    title: data.title,
    caption: data.caption,
    description: data.description,
    name: map.name,
    slug: map.slug,
  };
}

// Helper to convert subcategory key to WP slug
function convertSubKey(k) {
  // e.g., 'rueckbaumanagement-entsorgung' => 'rueckbau-entsorgung'
  for (const [prefix, map] of Object.entries(MAIN_MAP)) {
    if (k.startsWith(prefix + '-')) {
      return map.slug + '-' + k.substring(prefix.length + 1);
    }
  }
  return k;
}

// Convert subcategories grouped by parent
const subcategories = { rueckbau: {}, altlasten: {}, schadstoffe: {}, sicherheit: {}, beratung: {} };
for (const [key, data] of Object.entries(semantic.subcategories || {})) {
  // Handle pages/company keys into pages (later)
  if (key.startsWith('company-') || key.startsWith('pages-')) continue;

  const wpSlug = convertSubKey(key);
  const parent = wpSlug.split('-')[0];
  subcategories[parent] = subcategories[parent] || {};
  subcategories[parent][wpSlug.split(parent + '-')[1]] = {
    image: data.image,
    alt: data.alt,
    title: data.title,
    caption: data.caption,
    description: data.description,
    parent,
    name: wpSlug.split(parent + '-')[1].replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase()),
    slug: wpSlug,
  };
}

// Pages mapping
const pages = {};
// Semantic sometimes uses subcategories entries for company/pages; map them explicitly
const specialPageKeys = {
  'company-about': 'ueber-uns',
  'company-contact': 'kontakt',
  'company-impressum': 'impressum',
  'pages-home': 'startseite',
};

for (const [key, wpSlug] of Object.entries(specialPageKeys)) {
  const data = semantic.subcategories?.[key];
  if (!data) continue;
  pages[wpSlug] = {
    image: data.image,
    alt: data.alt,
    title: data.title,
    caption: data.caption,
    description: data.description,
    kategorie: 'general',
    slug: wpSlug,
    name: wpSlug === 'ueber-uns' ? 'Über uns' : wpSlug.charAt(0).toUpperCase() + wpSlug.slice(1),
  };
}

const output = {
  description: 'WordPress Enhanced Image Mappings mit SEO-Metadaten - Aus Semantic Unique Mapping konvertiert',
  version: 'seo-optimized-from-semantic-1.0',
  image_base_path: path.join(ROOT, 'images'),
  last_updated: new Date().toISOString(),
  main_categories,
  subcategories,
  pages,
};

fs.writeFileSync(OUTPUT, JSON.stringify(output, null, 2), 'utf8');
console.log(`✅ Converted semantic mapping to: ${OUTPUT}`);

