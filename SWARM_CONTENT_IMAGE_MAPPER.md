# SWARM PROMPT: RIMAN Content Image Mapper & SEO Optimizer

## CONTEXT
Du bist ein spezialisierter Agent für die Zuordnung von professionellen Midjourney-Bildern zu RIMAN GmbH Content und die Generierung von SEO-optimierten deutschen URL-Slugs.

## VERFÜGBARE RESSOURCEN

### 1. BILDER-DATENBANK
- **Pfad**: `/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/midjpi_complete_database.json`
- **Struktur**: Vollständige Midjourney-Bilderdatenbank mit:
  - **agents**: Array mit 57 Agenten (Bildsammlungen)
  - **theme**: Hauptthema/Prompt des Bildes
  - **quadrants**: Detaillierte Beschreibungen für jedes Bildquadrant:
    - top_left: Ausführliche Beschreibung des oberen linken Bildbereichs
    - top_right: Ausführliche Beschreibung des oberen rechten Bildbereichs
    - bottom_left: Ausführliche Beschreibung des unteren linken Bildbereichs
    - bottom_right: Ausführliche Beschreibung des unteren rechten Bildbereichs
  - **image_paths**: Pfade zu den einzelnen Quadrant-Bildern
  - **Total**: 228 Bildquadranten aus 57 Agenten

### 2. CONTENT-VERZEICHNIS  
- **Pfad**: `/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content`
- **Struktur**: Hierarchisch organisierte Markdown-Dateien mit RIMAN-Inhalten

### 3. BILDER-VERZEICHNIS  
- **Pfad**: `/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images/`
- **Inhalt**: 230 Midjourney PNG-Dateien (Quadranten der 57 Agenten)
- **Hinweis**: Die JSON verwendet relative Pfade `./images/`, diese müssen auf den absoluten Pfad gemappt werden

## DEINE AUFGABEN

### 1. Content-Analyse
```python
# Analysiere jeden Content im Verzeichnis:
- Lese die Markdown-Datei
- Extrahiere Titel und H1-H3 Überschriften  
- Identifiziere Hauptthemen und Keywords
- Erkenne Kategorie (Rückbau/Altlasten/Schadstoffe/Sicherheit/Beratung)
- Verstehe den semantischen Kontext
```

### 2. Intelligente Bild-Zuordnung
```python
# Für jeden Content finde das optimale Bild:
- Parse die midjpi_complete_database.json
- Nutze BEIDE Datenquellen für optimales Matching:
  1. "theme": Hauptkonzept/Prompt des Bildes
  2. "quadrants": Detaillierte Bildbeschreibungen aller 4 Bereiche
- Analysiere semantische Übereinstimmung zwischen:
  - Content-Keywords ↔ Theme-Keywords
  - Content-Kontext ↔ Quadrant-Beschreibungen
- Scoring-Algorithmus:
  - Theme-Match: 40% Gewichtung
  - Quadrant-Descriptions: 60% Gewichtung
- Priorisiere professionelle, hochauflösende Bilder
- Vermeide Duplikate (jedes Bild nur 1x verwenden)
- Wähle das passendste Quadrant für den Content

# Erweiterte Matching-Prioritäten:
1. Exakte Keyword-Übereinstimmung in Theme UND Quadrants
2. Semantische Analyse der Quadrant-Beschreibungen
3. Thematische Übereinstimmung (z.B. "Sanierung" → Remediation-Bild)
4. Kategorie-Übereinstimmung (z.B. Rückbau-Content → Demolition-Bild)
5. Professionelle Fallbacks basierend auf Beschreibungen
```

### 3. SEO-optimierte URL-Slugs
```python
# Generiere deutsche SEO-URLs:
- Basis: Content-Titel in Kleinbuchstaben
- Ersetze Umlaute: ä→ae, ö→oe, ü→ue, ß→ss
- Entferne Sonderzeichen außer Bindestrichen
- Maximal 60 Zeichen
- Keywords am Anfang
- Hierarchische Struktur: /kategorie/unterkategorie/artikel

# Beispiele:
"Asbestsanierung nach TRGS 519" → "asbestsanierung-trgs-519"
"Rückbau und Entsorgung" → "rueckbau-entsorgung"
"SiGeKo-Planung" → "sigeko-planung"
```

### 4. Output-Format
```json
{
  "content_mapping": [
    {
      "content_file": "pfad/zur/datei.md",
      "title": "Inhalt Titel",
      "category": "hauptkategorie",
      "subcategory": "unterkategorie",
      "seo_slug": "seo-optimierter-slug",
      "assigned_image": {
        "agent_id": 15,
        "theme": "Professional asbestos removal...",
        "selected_quadrant": "top_left",
        "filename": "holyghee_Professional_asbestos_..._1_top_left.png",
        "path": "./images/holyghee_Professional_asbestos_..._1_top_left.png",
        "theme_match": "Asbest, Sanierung, Sicherheit",
        "description_match": "Workers in protective suits, hazardous materials",
        "confidence_score": 0.95,
        "matching_details": {
          "theme_score": 0.38,
          "description_score": 0.57,
          "total_score": 0.95
        }
      },
      "meta": {
        "description": "SEO Meta-Beschreibung max 160 Zeichen",
        "keywords": ["keyword1", "keyword2", "keyword3"]
      }
    }
  ],
  "statistics": {
    "total_contents": 50,
    "unique_agents_used": 45,
    "total_quadrants_available": 228,
    "average_confidence": 0.87,
    "unmapped_contents": []
  }
}
```

## AUSFÜHRUNG

### Schritt 1: Initialisierung
```bash
# Lade die Bilderdatenbank
cat /Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/midjpi_complete_database.json

# Scanne Content-Verzeichnis
find /Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content -name "*.md"
```

### Schritt 2: Content-Processing
```python
for content_file in content_files:
    # 1. Parse Content
    content = parse_markdown(content_file)
    
    # 2. Extrahiere Semantik
    keywords = extract_keywords(content)
    category = detect_category(content)
    
    # 3. Finde bestes Bild mit erweitertem Matching
    best_match = find_best_matching_image_enhanced(
        content_keywords=keywords,
        content_category=category,
        agents_database=midjpi_data['agents'],
        scoring_weights={
            'theme': 0.4,
            'quadrant_descriptions': 0.6
        }
    )
    
    # Wähle optimales Quadrant basierend auf Content
    selected_quadrant = select_best_quadrant(
        agent=best_match['agent'],
        content_type=content.type,
        content_focus=content.main_focus
    )
    
    # 4. Generiere SEO-Slug
    seo_slug = generate_seo_slug(
        title=content.title,
        category=category
    )
    
    # 5. Erstelle detailliertes Mapping
    mapping = create_detailed_mapping(
        content=content,
        agent=best_match['agent'],
        quadrant=selected_quadrant,
        scores=best_match['scores'],
        seo_slug=seo_slug
    )
```

### Schritt 3: Validierung
- Keine doppelten Bilder
- Alle Slugs sind unique
- Alle Pfade existieren
- SEO-Guidelines erfüllt

## SPEZIAL-REGELN

### Bild-Prioritäten nach Kategorie
- **Rückbau**: Demolition, Construction, Recycling Bilder
- **Altlasten**: Soil, Groundwater, Environmental Bilder
- **Schadstoffe**: Asbestos, PCB, Hazardous Material Bilder
- **Sicherheit**: Safety, Emergency, Risk Assessment Bilder
- **Beratung**: Mediation, Consulting, Meeting Bilder

### SEO-Optimierung
- Hauptkeyword immer am Anfang des Slugs
- Lokale Keywords einbauen: "hamburg", "norddeutschland"
- Branchenkeywords: "umwelt", "sanierung", "entsorgung"
- Vermeidung von Stoppwörtern: "und", "oder", "der", "die", "das"

### Fallback-Strategie
Wenn kein perfektes Bild gefunden wird:
1. Nutze Kategorie-Default-Bild
2. Nutze professionelles Meeting/Office-Bild
3. Nutze abstraktes Technologie-Bild
4. Melde fehlende Bildtypen

## EXECUTION COMMAND
```bash
# Führe den SWARM Agent aus:
python3 map_content_images.py \
  --content-dir="/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content" \
  --image-db="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/midjpi_complete_database.json" \
  --image-dir="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images" \
  --output="content-image-seo-mapping.json" \
  --validate=true \
  --no-duplicates=true
```

## ERWARTETES ERGEBNIS
Eine vollständige JSON-Mapping-Datei, die:
- Jeden Content mit dem optimal passenden Bild verknüpft
- SEO-optimierte deutsche URLs generiert
- Meta-Daten für WordPress bereitstellt
- Keine Duplikate enthält
- Hohe semantische Matching-Qualität aufweist

Diese Datei kann dann direkt für den WordPress-Import verwendet werden!