# RIMAN Hauptkategorien - Midjourney Bild-Test

## 🎯 **Auftrag:**
Generiere optimierte Midjourney-Bilder für die 5 RIMAN Hauptkategorien und vergleiche diese mit den aktuell verwendeten Bildern.

## 📁 **Ziel-Kategorien:**

### 1. **Rückbau-Management**
- **Content:** `/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content/services/rueckbaumanagement/main.md`
- **Aktuell:** Agent 52 (59% Confidence)
- **Keywords:** Rückbau, Bauleitung, Projektmanagement, Demontage

### 2. **Altlastensanierung** 
- **Content:** `/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content/services/altlastensanierung/main.md`
- **Aktuell:** Agent 1 (54% Confidence)
- **Keywords:** Altlasten, Bodensanierung, Grundwasser, Umweltsanierung

### 3. **Schadstoff-Management**
- **Content:** `/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content/services/schadstoff-management/main.md`
- **Aktuell:** Agent 31 (52% Confidence)
- **Keywords:** Schadstoffe, Asbest, KMF, PCB, Gefahrstoffe

### 4. **Sicherheitskoordination**
- **Content:** `/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content/services/sicherheitskoordination/main.md`
- **Aktuell:** Agent 27 (30% Confidence)
- **Keywords:** SiGeKo, Arbeitssicherheit, Baustellensicherheit, Koordination

### 5. **Beratung & Mediation**
- **Content:** `/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content/services/beratung-mediation/main.md`
- **Aktuell:** Agent 30 (38% Confidence)
- **Keywords:** Beratung, Mediation, Compliance, Gutachten

## 🎨 **Arbeitsschritte:**

### **Für jede Kategorie:**
1. **Content-Analyse:** Lese die main.md Datei
2. **SEO-Keywords:** Extrahiere relevante deutsche Fachbegriffe
3. **Prompt-Erstellung:** Verwende `MIDJOURNEY_PROMPT_GENERATOR.md` Guidelines
4. **Bild-Generierung:** Nutze MCP Midjourney Server (`/Users/holgerbrandt/dev/claude-code/tools/midjourney-mcp-server`)
5. **Optimierung:** 16:9 Format für WordPress Featured Images

### **Prompt-Qualitätsanforderungen:**
- ✅ RIMAN Brand Guidelines (Professional Blue, Safety Orange, Clean White)
- ✅ Deutsche B2B-Zielgruppe (Bauunternehmen, Industriebetriebe)
- ✅ Professionelle Corporate Photography Style
- ✅ Sicherheitsausrüstung und Deutsche Standards sichtbar
- ✅ Vertrauen und Kompetenz ausstrahlend

## 📊 **Erwartetes Ergebnis:**

```json
{
  "kategorie": "Rückbau-Management",
  "generierter_prompt": "Professional demolition project manager...",
  "bild_datei": "riman_rueckbau_management_hero.png",
  "seo_metadaten": {
    "alt_text": "Professionelles Rückbau-Management mit Bauleitung",
    "description": "RIMAN GmbH Projektleitung für kontrollierten Rückbau",
    "tags": ["rückbau", "bauleitung", "projektmanagement"]
  },
  "verbesserung_zu_aktuell": "Perfekt auf RIMAN Brand und Zielgruppe zugeschnitten vs. generisches Bild"
}
```

## 🎯 **Erfolgs-Kriterien:**
- **Visueller Eindruck:** Deutlich professioneller als aktuelle Bilder
- **Brand-Konformität:** 100% RIMAN Corporate Identity
- **SEO-Optimierung:** Deutsche Keywords visuell repräsentiert
- **Zielgruppen-Ansprache:** B2B-Kunden sofort angesprochen
- **Technische Qualität:** WordPress-ready, optimale Auflösung

## 🚀 **Start-Befehl:**
Analysiere die 5 Content-Files, erstelle optimierte Midjourney-Prompts basierend auf den RIMAN Guidelines und generiere die Bilder mit dem MCP Server.

**Ziel: 5 perfekte Hauptkategorien-Bilder die deutlich besser sind als die aktuellen!** 🎯✨