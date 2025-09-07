# RIMAN WordPress Website

WordPress-Installation für RIMAN GmbH - Umweltmanagement und Schadstoffsanierung

## 🌐 Live Website
https://riman-wordpress-swarm.ecomwy.com

## 🚀 Deployment

### Automatisches Deployment
Jeder Push zum `main` Branch wird automatisch via GitHub Actions deployed.

### GitHub Secrets konfigurieren
Folgende Secrets müssen in den GitHub Repository Settings → Secrets konfiguriert werden:

1. `SSH_USERNAME`: ssh-w0181e1b
2. `KAS_PASSWORD`: [Das All-Inkl Passwort]
3. `KAS_HOST`: w0181e1b.kasserver.com

### Manuelles Deployment
```bash
# Push zum GitHub Repository
git push origin main

# Oder direkt mit den Scripts
./scripts/deploy-ftp.sh            # FTP/SFTP Deployment-Tools (Theme/Plugin)
./scripts/wp-ssh-v2.sh deploy-wp --dry-run   # Themes+Plugins per SSH (Vorschau)
./scripts/wp-ssh-v2.sh deploy-wp --no-delete # Themes+Plugins ohne Löschung
./scripts/wp-ssh-v2.sh deploy-uploads ./wp-content/uploads [remote-subdir]
# Dry‑Run (ohne Änderungen, zeigt geplante Transfers)
# ./scripts/wp-ssh-v2.sh deploy-uploads --dry-run ./wp-content/uploads/2025/09 2025/09
# Ohne Löschungen auf dem Server (additiv):
# ./scripts/wp-ssh-v2.sh deploy-uploads --no-delete ./wp-content/uploads

Direkter Code‑Deploy (ohne Git)
- Alles (Themes+Plugins):
  - Vorschau: ./scripts/wp-ssh-v2.sh deploy-wp --dry-run
  - Sicher (additiv): ./scripts/wp-ssh-v2.sh deploy-wp --no-delete
  - Spiegeln (vorsichtig): ./scripts/wp-ssh-v2.sh deploy-wp
- Einzelnes Theme/Plugin:
  - Theme:  ./scripts/wp-ssh-v2.sh deploy-theme --no-delete ./wp-content/themes/dein-theme
  - Plugin: ./scripts/wp-ssh-v2.sh deploy-plugin --no-delete ./wp-content/plugins/dein-plugin
# Beispiel: Inhalte nach wp-content/uploads/correct-images/
# ./scripts/wp-ssh-v2.sh deploy-uploads ./wp-content/uploads/correct-images correct-images
```

Laien‑freundliche SSH‑Deploy‑Doku: siehe `docs/SSH-DEPLOYMENT.md`

## 📁 Projekt-Struktur
```
├── wp-content/
│   ├── themes/       # WordPress Themes
│   ├── plugins/      # WordPress Plugins  
│   └── uploads/      # Media Dateien
├── scripts/
│   ├── deploy-ftp.sh # FTP Deployment Script
│   └── wp-ssh-v2.sh  # SSH Management Script
└── .github/
    └── workflows/
        └── deploy.yml # GitHub Actions Workflow
```

## 🛠️ Lokale Entwicklung

### Voraussetzungen
- Docker & Docker Compose
- Git
- Node.js (optional für Theme-Entwicklung)

### Setup
```bash
# Repository klonen
git clone https://github.com/holyghee/riman-wordpress-swarm.git
cd riman-wordpress-swarm

# Docker Container starten
docker-compose up -d

# Lokale Umgebung: http://localhost:8080
```

Hinweis lokale Uploads
- Der Container verwendet den Ordner `wp-content/uploads` aus diesem Projekt als Upload‑Quelle (ein einziger, klarer Pfad).
- Große Dateien unter `wp-content/uploads` sind in `.gitignore` ausgeschlossen und werden nicht gepusht.
- Für den Live‑Server synchronisiere Uploads mit: `./scripts/wp-ssh-v2.sh deploy-uploads ./wp-content/uploads [subdir]`.

## 📦 Clean Setup + SEO Import

Das Script `clean-and-setup-seo.sh` richtet eine saubere WordPress-Instanz ein, importiert Inhalte/Bilder und pflegt Kategorie-Beschreibungen inkl. Metafelder des Plugins „Category Section Customizer“.

Wichtige Punkte
- Löscht alle bestehenden Inhalte (Seiten, Beiträge, Medien, Kategorien) und leert das Upload-Verzeichnis.
- Erzeugt Seiten-/Kategorienstruktur aus dem Content-Ordner (falls vorhanden).
- Importiert Bilder ohne Duplikate mit SEO-Metadaten aus dem Mapping.
- Aktualisiert Kategorien via REST (Description + Term‑Metas `_section_label`, `_section_title`, `_section_description`).
- Fallback in WP (PHP), falls REST nicht möglich ist.

Ausführung
```bash
chmod +x ./clean-and-setup-seo.sh
./clean-and-setup-seo.sh
```

WP‑API Konfiguration (optional als Env)
```bash
export WP_API_URL="http://127.0.0.1:8801/"
export WP_API_USERNAME="admin"
export WP_API_PASSWORD="<ANWENDUNGSPASSWORT>"  # ggf. in Anführungszeichen
```

SEO‑Mapping Felder (Auszug)
- description: Kategorie‑Beschreibung (Core-Feld)
- section_label | _section_label: Label über den Karten (z. B. „NACHHALTIG“)
- section_title | _section_title: Überschrift (unterstützt {category})
- section_description | _section_description: Untertitel/Beschreibung

Hinweise
- Automatische Label‑Vergabe, falls nicht gemappt: rueckbau=NACHHALTIG, altlasten=FACHGERECHT, schadstoffe=KOMPETENT, sicherheitskoordination=SICHER, beratung=NEUTRAL.
- Slug‑Normalisierung: `sicherheit` → `sicherheitskoordination`, `sicherheit-*` → `sicherheitskoordination-*`.

## 🧱 RIMAN Blocks – Category Hero Slider

Neuer Block: „Riman: Category Hero Slider“ (Full‑Width Hero Slider für Hauptkategorien)

Features
- Zeigt Top‑Level Kategorien (außer „Uncategorized“) mit Bild, Titel, Beschreibung (bevorzugt `_section_description`) und CTA zur Kategorie.
- Bildquellen: `_thumbnail_id`/`thumbnail_id` der Kategorie, `_linked_page_id` (Seite → Featured Image), Seiten‑Meta `_linked_category_id`, letztlich Seite per Slug (Featured Image). Ohne Bild wird ein Farbverlauf angezeigt.
- Animationen: fade oder slide; Dots‑Stile; Pfeil‑Layout (Form/Stil/Position); Autoplay/Intervall; Overlay‑Dimmung; minHeight.
- Parallax: drei Modi – Transform (sanft), Scroll (deutlich), Fixed (klassisch).

Konfiguration (Block‑Einstellungen im Editor)
- animation: fade | slide
- transition: Dauer in ms
- dotsStyle: pills | dots | squares
- arrowsShape: round | square
- arrowsStyle: light | dark | ghost
- arrowsPosition: inset | outside
- includeCategories: Kommagetrennte Top‑Level‑Slugs (leer = alle)
- ctaText: Button‑Text
- parallax: an/aus
- parallaxMode: transform | scroll | fixed
- parallaxStrength: 0–1 (für transform/scroll; z. B. 0.4–0.6)

Wichtige Hinweise zu Parallax
- Fixed (klassisch): nutzt `background-attachment: fixed` direkt auf der Slide. In Kombination mit „slide“ (CSS transform am Track) ist das in Browsern unzuverlässig, daher wird automatisch auf „fade“ umgestellt.
- Transform/Scroll funktionieren mit „slide“ und „fade“.
- Mobile Browser unterstützen „fixed“ nicht immer; ggf. „scroll“ verwenden.

Troubleshooting
- Block nicht sichtbar auf Startseite: Front‑Page‑Template enthält evtl. keinen „Post Content“ Block. Lösung: Template anpassen oder Slider direkt ins Front‑Page‑Template einfügen. Alternativ statische Startseite auf die Seite mit dem Block setzen (Einstellungen → Lesen).
- Assets werden nicht geladen: Im Frontend sollten diese Dateien erscheinen:
  - `wp-content/plugins/riman-blocks/assets/category-hero-slider.css`
  - `wp-content/plugins/riman-blocks/assets/category-hero-slider.js`
- Nur lila Verlauf: Es wurde kein Bild gefunden – ein Kategorien‑Bild oder ein Featured Image auf der verknüpften Seite setzen.

## 🔐 Sicherheit
- Keine Passwörter im Repository
- Alle Credentials via GitHub Secrets oder lokale .env Dateien
- SSH-Zugang über All-Inkl KAS

## 📝 Wartung

### Datenbank-Backup
```bash
./scripts/wp-ssh-v2.sh backup
```

### WordPress Updates
Updates können über das WordPress Admin-Panel durchgeführt werden:
https://riman-wordpress-swarm.ecomwy.com/wp-admin

## 🤝 Support
Bei Fragen oder Problemen bitte ein Issue im GitHub Repository erstellen.
