# RIMAN WordPress Setup Script - Agent Data Package

## 🎯 **Aufgabe für neuen Agent:**
Erstelle ein WordPress Setup-Script das die 121 gemappten Content-Image-SEO Zuordnungen automatisch in WordPress importiert.

## 📊 **Verfügbare Daten:**

### 1. **Haupt-Mapping-Datei:**
```
/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/content-image-seo-mapping-enhanced.json
```
- **121 fertige Zuordnungen** (94% Coverage)
- Confidence Scores bis 93.5%
- SEO-optimierte URLs und Meta-Daten

### 2. **Beispiel einer fertigen Zuordnung:**
```json
{
  "content_file": "services/rueckbaumanagement/bestandsaufnahme/main.md",
  "content_title": "Bestandsaufnahme: Grundstein für erfolgreichen Rückbau",
  "content_category": "rueckbau",
  "matched_image": {
    "agent_id": 38,
    "theme": "Systematic building deconstruction with materials",
    "quadrant": "bottom_left",
    "image_path": "./images/holyghee_Systematic_building_deconstruction_with_materials_bein_eed8d922-c81b-443a-8c95-b56b5b638680_3_bottom_left.png",
    "description": "Close-up detail reveals ground-level perspective of systematic material processing..."
  },
  "seo_optimization": {
    "url_slug": "rueckbau-bestandsaufnahme-grundstein-fuer-erfolgreichen-rueckbau",
    "meta_title": "Bestandsaufnahme: Grundstein für erfolgreichen Rückbau | RIMAN GmbH - Umwelt & Sicherheit",
    "meta_description": "Professionelle Bestandsaufnahme für Rückbau durch RIMAN GmbH...",
    "focus_keywords": ["bestandsaufnahme rückbau", "bauliche substanz", ...]
  },
  "confidence_score": 0.935
}
```

### 3. **Bild-Verzeichnis:**
```
/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images/
```
- 230 Midjourney PNG-Dateien verfügbar
- Alle Pfade in der JSON sind korrekt referenziert

### 4. **WordPress-Umgebung:**
```
http://localhost:8801 (Docker Container)
Database: WordPress 6.7, MySQL 8.0
PhpMyAdmin: http://localhost:8802
WP-CLI: Docker Service verfügbar
Existing Pages: 148
```

## 🔧 **Gewünschtes Setup-Script:**

### **Funktionen:**
1. **Content Import:** Markdown → WordPress Pages/Posts
2. **Image Import:** PNG → WordPress Media Library
3. **Featured Images:** Automatische Zuordnung via `set_post_thumbnail()`
4. **SEO Integration:** Yoast/RankMath kompatible Meta-Daten
5. **Category Assignment:** Hierarchische Kategorien
6. **Slug Optimization:** Deutsche SEO-URLs

### **Technische Requirements:**
- PHP Script für WordPress CLI/Admin
- Batch-Processing (10-20 Items pro Durchlauf)
- Error Handling und Logging
- Rollback-Funktionalität
- Progress Tracking

### **Qualitätskontrolle:**
- Nur Mappings mit Confidence > 0.7 verwenden
- Image-Duplikat-Vermeidung
- URL-Slug Einzigartigkeit prüfen
- Kategorien automatisch erstellen falls nicht vorhanden

## 📁 **Alle Ressourcen bereit:**
- ✅ 121 Content-Image Mappings
- ✅ 230 Hochauflösende Bilder  
- ✅ SEO-optimierte Meta-Daten
- ✅ Deutsche URL-Slugs
- ✅ WordPress Docker-Umgebung

## 🚀 **Nächster Schritt:**
Übergib diese Datei an deinen Setup-Script Agent mit dem Auftrag:

"Erstelle ein WordPress Setup-Script basierend auf diesem AGENT_SETUP_PACKAGE.md"