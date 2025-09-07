#!/usr/bin/env node
/**
 * Update WordPress category descriptions via REST API
 *
 * Env:
 *  - WP_API_URL (e.g. http://127.0.0.1:8801/)
 *  - WP_API_USERNAME
 *  - WP_API_PASSWORD (Application Password)
 * Args:
 *  --mapping <path>  Path to SEO mapping JSON
 */

const fs = require('fs');
const path = require('path');
const https = require('https');
const http = require('http');

function parseArgs() {
  const args = process.argv.slice(2);
  const out = {};
  for (let i = 0; i < args.length; i++) {
    if (args[i] === '--mapping') { out.mapping = args[++i]; }
  }
  return out;
}

function getEnv(name, fallback) {
  return process.env[name] || fallback || '';
}

function base64(s) { return Buffer.from(s).toString('base64'); }

function request(method, urlStr, auth, body) {
  return new Promise((resolve, reject) => {
    const u = new URL(urlStr);
    const isHttps = u.protocol === 'https:';
    const options = {
      method,
      hostname: u.hostname,
      port: u.port || (isHttps ? 443 : 80),
      path: u.pathname + (u.search || ''),
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Basic ' + base64(auth),
      },
    };
    let dataStr = null;
    if (body) {
      dataStr = JSON.stringify(body);
      options.headers['Content-Type'] = 'application/json';
      options.headers['Content-Length'] = Buffer.byteLength(dataStr);
    }
    const req = (isHttps ? https : http).request(options, (res) => {
      let chunks = '';
      res.on('data', (d) => (chunks += d));
      res.on('end', () => {
        const status = res.statusCode || 0;
        const contentType = res.headers['content-type'] || '';
        let json = null;
        if (contentType.includes('application/json')) {
          try { json = JSON.parse(chunks); } catch (_) { /* ignore */ }
        }
        if (status >= 200 && status < 300) {
          resolve({ status, json, raw: chunks });
        } else {
          const err = new Error(`HTTP ${status}: ${chunks}`);
          err.status = status;
          err.body = chunks;
          reject(err);
        }
      });
    });
    req.on('error', reject);
    if (dataStr) req.write(dataStr);
    req.end();
  });
}

function mapSlug(slug) {
  if (slug === 'sicherheit') return 'sicherheitskoordination';
  if (slug.startsWith('sicherheit-')) return 'sicherheitskoordination-' + slug.slice('sicherheit-'.length);
  return slug;
}

function pickDescription(termName, type, data) {
  if (data && data.description) return data.description;
  if (data && data.caption) return data.caption;
  if (data && data.title) return data.title;
  if (type === 'main') {
    return `Leistungen, Beratung und fachgerechte Umsetzung im Bereich ${termName}.`;
  }
  return `Spezialisierte Leistungen und praxisnahe Lösungen im Bereich ${termName}.`;
}

function buildSectionMeta(slug, termName, type, data) {
  // Defaults + check if mapping provided explicit values
  const mappedLabel = data && (data.section_label || data._section_label);
  const mappedTitle = data && (data.section_title || data._section_title);
  const mappedDesc  = data && (data.section_description || data._section_description);

  let label = mappedLabel || 'DIE LEISTUNGEN';
  let title = mappedTitle || 'Leistungen & Schwerpunkte der {category}';
  let section_desc = mappedDesc || pickDescription(termName, type, data);

  const slugEff = slug;
  let root = slugEff, leaf = '';
  if (slugEff.includes('-')) {
    const parts = slugEff.split('-', 2);
    root = parts[0];
    leaf = parts[1];
  }

  // Kategorie-Überbegriff (Label) nach Root-Kategorie, falls nicht explizit gemappt
  if (!mappedLabel) {
    if (root === 'rueckbau') label = 'NACHHALTIG';
    else if (root === 'altlasten') label = 'FACHGERECHT';
    else if (root === 'schadstoffe') label = 'KOMPETENT';
    else if (root === 'sicherheitskoordination') label = 'SICHER';
    else if (root === 'beratung') label = 'NEUTRAL';
  }

  // Hauptkategorien (nur überschreiben, wenn keine expliziten Mapping-Werte gesetzt wurden)
  if (slugEff === 'rueckbau') {
    if (!mappedTitle) title = 'Rückbaumanagement – Leistungen & Schwerpunkte';
    if (!mappedDesc) section_desc = 'Planung, Ausschreibung, Durchführung, Entsorgung, Recycling und Dokumentation – sicher und ressourcenschonend.';
  } else if (slugEff === 'altlasten') {
    if (!mappedTitle) title = 'Altlastensanierung – Leistungen & Schwerpunkte';
    if (!mappedDesc) section_desc = 'Erkundung, Sanierungsplanung, Boden- und Grundwassersanierung sowie Monitoring – rechtssicher und nachhaltig.';
  } else if (slugEff === 'schadstoffe') {
    if (!mappedTitle) title = 'Schadstoff-Management – Leistungen & Schwerpunkte';
    if (!mappedDesc) section_desc = 'Asbest, PCB, PAK, KMF und Schwermetalle – Erkundung, Sanierung und Freimessung nach Regelwerk.';
  } else if (slugEff === 'sicherheitskoordination') {
    if (!mappedTitle) title = 'Sicherheitskoordination – Leistungen & Schwerpunkte';
    if (!mappedDesc) section_desc = 'SiGeKo in Planung und Ausführung, Arbeitsschutz, Gefährdungsbeurteilung und Notfallmanagement – normkonform organisiert.';
  } else if (slugEff === 'beratung') {
    if (!mappedTitle) title = 'Beratung & Mediation – Leistungen & Schwerpunkte';
    if (!mappedDesc) section_desc = 'Projektberatung, Mediation, Gutachten, Compliance und Schulungen – praxisnah und lösungsorientiert.';
  }

  // Unterkategorien
  if (leaf) {
    if (root === 'rueckbau') {
      if (leaf === 'planung') { if (!mappedTitle) title = '{category} – Planung & Konzept'; if (!mappedDesc) section_desc = 'BIM-gestützte Rückbauplanung: Bestandsaufnahme, Mengenermittlung, LV und Genehmigungen.'; }
      else if (leaf === 'ausschreibung') { if (!mappedTitle) title = '{category} – Ausschreibung & Vergabe'; if (!mappedDesc) section_desc = 'Leistungsbeschreibungen, Vergabeunterlagen, Angebotsprüfung und Vergabebegleitung.'; }
      else if (leaf === 'durchfuehrung') { if (!mappedTitle) title = '{category} – Durchführung & Steuerung'; if (!mappedDesc) section_desc = 'Sichere Ausführung, Baustellenlogistik, Qualitätssicherung und Monitoring.'; }
      else if (leaf === 'entsorgung') { if (!mappedTitle) title = '{category} – Entsorgung & Stoffstrom'; if (!mappedDesc) section_desc = 'Rechtssichere Entsorgung, Nachweisführung und Kreislaufwirtschaft.'; }
      else if (leaf === 'recycling') { if (!mappedTitle) title = '{category} – Recycling & Verwertung'; if (!mappedDesc) section_desc = 'Sortenreine Trennung, Wiederverwertung und Dokumentation nach GewAbfV.'; }
      else if (leaf === 'dokumentation') { if (!mappedTitle) title = '{category} – Dokumentation & Nachweise'; if (!mappedDesc) section_desc = 'Lückenlose Nachweisführung, Fotodokumentation und Abschlussberichte.'; }
    } else if (root === 'altlasten') {
      if (leaf === 'erkundung') { if (!mappedTitle) title = '{category} – Erkundung & Bewertung'; if (!mappedDesc) section_desc = 'Historische Recherche, Probenahme und Gefährdungsabschätzung gemäß BBodSchV.'; }
      else if (leaf === 'sanierungsplanung') { if (!mappedTitle) title = '{category} – Sanierungsplanung'; if (!mappedDesc) section_desc = 'Verfahrenswahl, Kosten- und Genehmigungsplanung, Ausschreibung.'; }
      else if (leaf === 'bodensanigung' || leaf === 'bodensanierung') { if (!mappedTitle) title = '{category} – Bodensanierung'; if (!mappedDesc) section_desc = 'Technisch und rechtssicher: Aushub, Behandlung, Wiedereinbau und Qualitätssicherung.'; }
      else if (leaf === 'grundwassersanierung') { if (!mappedTitle) title = '{category} – Grundwassersanierung'; if (!mappedDesc) section_desc = 'Pump & Treat, Barrieren, Begleitmessungen und Betriebskonzepte.'; }
      else if (leaf === 'monitoring') { if (!mappedTitle) title = '{category} – Monitoring & Nachsorge'; if (!mappedDesc) section_desc = 'Langzeitüberwachung, Freigabekonzepte und Berichtswesen.'; }
    } else if (root === 'schadstoffe') {
      if (leaf === 'asbest') { if (!mappedTitle) title = '{category} – Asbestsanierung'; if (!mappedDesc) section_desc = 'Erkundung, Sanierung und Freimessung gemäß TRGS 519 – sicher und dokumentiert.'; }
      else if (leaf === 'pcb') { if (!mappedTitle) title = '{category} – PCB‑Sanierung'; if (!mappedDesc) section_desc = 'Identifikation, Sanierung und Freigabemessung an Anlagen und Bauteilen.'; }
      else if (leaf === 'pak') { if (!mappedTitle) title = '{category} – PAK‑Sanierung'; if (!mappedDesc) section_desc = 'Behandlung PAK‑belasteter Baustoffe – Verfahren und Nachweise.'; }
      else if (leaf === 'kmf') { if (!mappedTitle) title = '{category} – KMF‑Sanierung'; if (!mappedDesc) section_desc = 'Sichere Entfernung künstlicher Mineralfasern inklusive Entsorgung.'; }
      else if (leaf === 'schwermetalle') { if (!mappedTitle) title = '{category} – Schwermetalle'; if (!mappedDesc) section_desc = 'Analyse, Bewertung und Sanierung schwermetallbelasteter Substrate.'; }
    } else if (root === 'sicherheitskoordination') {
      if (leaf === 'sigeko-planung') { if (!mappedTitle) title = '{category} – SiGeKo in der Planung'; if (!mappedDesc) section_desc = 'SiGe‑Plan, Unterlage für spätere Arbeiten und Koordination mit Planungsteams.'; }
      else if (leaf === 'sigeko-ausfuehrung') { if (!mappedTitle) title = '{category} – SiGeKo in der Ausführung'; if (!mappedDesc) section_desc = 'Baustellenkoordination, Unterweisungen und Kontrollen nach BaustellV.'; }
      else if (leaf === 'arbeitsschutz') { if (!mappedTitle) title = '{category} – Arbeitsschutz'; if (!mappedDesc) section_desc = 'PSA‑Konzepte, Organisation und Wirksamkeitskontrollen.'; }
      else if (leaf === 'gefaehrdungsbeurteilung') { if (!mappedTitle) title = '{category} – Gefährdungsbeurteilung'; if (!mappedDesc) section_desc = 'Risikoanalyse, Maßnahmenplanung und Dokumentation gemäß ArbSchG.'; }
      else if (leaf === 'notfallmanagement') { if (!mappedTitle) title = '{category} – Notfallmanagement'; if (!mappedDesc) section_desc = 'Notfallplanung, Alarmierungsketten und Krisenübungen.'; }
    } else if (root === 'beratung') {
      if (leaf === 'baumediation') { if (!mappedTitle) title = '{category} – Baumediation'; if (!mappedDesc) section_desc = 'Strukturiert zu tragfähigen Lösungen – neutral, effizient, ergebnisorientiert.'; }
      else if (leaf === 'projektberatung') { if (!mappedTitle) title = '{category} – Projektberatung'; if (!mappedDesc) section_desc = 'Organisation, Vergabe, Qualität und Risiko – praxisnah und umsetzbar.'; }
      else if (leaf === 'gutachten') { if (!mappedTitle) title = '{category} – Gutachten'; if (!mappedDesc) section_desc = 'Sachverständige Bewertungen und Entscheidungsgrundlagen – transparent und belastbar.'; }
      else if (leaf === 'schulungen') { if (!mappedTitle) title = '{category} – Schulungen & Seminare'; if (!mappedDesc) section_desc = 'Weiterbildung zu Umwelttechnik, Arbeitsschutz und Recht – aus der Praxis.'; }
      else if (leaf === 'compliance') { if (!mappedTitle) title = '{category} – Compliance & Recht'; if (!mappedDesc) section_desc = 'Rechtskonformität, Zertifizierung und Dokumentation – sicher und nachvollziehbar.'; }
    }
  }

  return {
    _section_label: label,
    _section_title: title,
    _section_description: section_desc,
  };
}

async function main() {
  const { mapping } = parseArgs();
  const WP_API_URL = getEnv('WP_API_URL');
  const WP_API_USERNAME = getEnv('WP_API_USERNAME') || getEnv('WP_USERNAME');
  const WP_API_PASSWORD = getEnv('WP_API_PASSWORD') || getEnv('WP_PASSWORD');

  if (!WP_API_URL || !WP_API_USERNAME || !WP_API_PASSWORD) {
    console.error('❌ Missing WP_API_* env (WP_API_URL, WP_API_USERNAME, WP_API_PASSWORD)');
    process.exit(1);
  }
  if (!mapping || !fs.existsSync(mapping)) {
    console.error(`❌ Mapping not found: ${mapping}`);
    process.exit(1);
  }

  const base = WP_API_URL.replace(/\/$/, '');
  const auth = `${WP_API_USERNAME}:${WP_API_PASSWORD}`;
  const json = JSON.parse(fs.readFileSync(mapping, 'utf8'));

  // Fetch all categories once to get ids and names
  let categories = [];
  try {
    // fetch in pages just in case
    for (let page = 1; page <= 10; page++) {
      const res = await request('GET', `${base}/wp-json/wp/v2/categories?per_page=100&page=${page}`, auth);
      const arr = Array.isArray(res.json) ? res.json : [];
      if (arr.length === 0) break;
      categories = categories.concat(arr);
      if (arr.length < 100) break;
    }
  } catch (e) {
    console.error('❌ Failed to fetch categories:', e.message);
    process.exit(1);
  }

  const catBySlug = new Map(categories.map(c => [c.slug, c]));

  function collectEntries() {
    const out = [];
    if (json.main_categories) {
      for (const [key, val] of Object.entries(json.main_categories)) {
        const slug = mapSlug(val.slug || key);
        out.push({ slug, data: val, type: 'main' });
      }
    }
    if (json.subcategories) {
      for (const [parent, list] of Object.entries(json.subcategories)) {
        for (const [subKey, val] of Object.entries(list)) {
          const slug = mapSlug(val.slug || `${parent}-${subKey}`);
          out.push({ slug, data: val, type: 'sub' });
        }
      }
    }
    return out;
  }

  const entries = collectEntries();
  let updated = 0, skipped = 0, errors = 0;

  for (const { slug, data, type } of entries) {
    const term = catBySlug.get(slug);
    if (!term) {
      console.log(`⚠️  Kategorie nicht gefunden (slug): ${slug}`);
      skipped++;
      continue;
    }
    const desc = pickDescription(term.name || slug, type, data).slice(0, 260);
    const meta = buildSectionMeta(slug, term.name || slug, type, data);
    try {
      const res = await request('PATCH', `${base}/wp-json/wp/v2/categories/${term.id}`, auth, { description: desc, meta });
      if (res && res.json && res.json.id === term.id) {
        console.log(`✓ Aktualisiert #${term.id} ${slug} → "${desc}"`);
        updated++;
      } else {
        console.log(`⚠️  Unerwartete Antwort für ${slug}`);
      }
    } catch (e) {
      console.log(`❌ Fehler für ${slug}: ${e.message}`);
      errors++;
    }
  }

  console.log(`\n✅ Kategorien aktualisiert: ${updated}`);
  console.log(`⏭️  Übersprungen: ${skipped}`);
  console.log(`❌ Fehler: ${errors}`);
}

main().catch((e) => {
  console.error('Fatal:', e);
  process.exit(1);
});
