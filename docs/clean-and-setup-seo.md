# Clean & Setup SEO Script - Dokumentation

## Überblick

Das `clean-and-setup-seo.sh` Script richtet eine saubere WordPress-Instanz in Docker ein und importiert Inhalte und Bilder inklusive SEO-Metadaten. Es ist speziell für die lokale Entwicklung konzipiert und bietet eine vollständige Neuerstellung der WordPress-Installation.

## ⚠️ WICHTIGER HINWEIS

**DIESES SCRIPT IST ZERSTÖRERISCH!** Es löscht alle bestehenden Inhalte in der lokalen WordPress-Installation. Verwenden Sie es NUR für die lokale Entwicklung, niemals auf Live-Systemen.

## Was das Script macht

1. **Vollständige Bereinigung**: Löscht ALLE Inhalte der lokalen WordPress-Installation:
   - Seiten und Beiträge
   - Medien und Uploads
   - Kategorien
   - Benutzerdefinierte Inhalte

2. **Struktur-Aufbau**: Erstellt neue Seiten- und Kategorienstruktur basierend auf Ihrem Content-Ordner

3. **Bildimport**: Importiert Bilder aus dem Images-Ordner gemäß SEO-Mapping ohne Duplikate

4. **SEO-Optimierung**: Setzt Kategorie-Metafelder (Label, Titel, Beschreibung) und konfiguriert die Startseite

5. **Korrektur-Prozesse**: Führt verschiedene PHP-Importer und Zuordnungsschritte aus

## Konfiguration

### Wichtige Variablen im Script-Header

Passen Sie diese Variablen an Ihre lokalen Pfade an:

```bash
# WordPress Container Name
WORDPRESS_CONTAINER="riman-wordpress-swarm-wordpress-1"

# Pfad zu Ihren Text-/Inhaltsquellen
CONTENT_DIR="/Ihr/lokaler/Pfad/zum/Content"

# Lokaler Bilderordner
IMAGES_DIR="/Ihr/Projektpfad/riman-wordpress-swarm/images"
```

### SEO-Mapping Dateien

Das Script sucht automatisch nach diesen Mapping-Dateien (in dieser Reihenfolge):

1. `wordpress-enhanced-image-mappings-seo.llm.json`
2. `wordpress-enhanced-image-mappings-seo.fixed.json`  
3. `wordpress-enhanced-image-mappings-seo.json`

Die erste gefundene Datei wird verwendet.

## Voraussetzungen

### System-Anforderungen

- **Docker**: Docker Desktop muss laufen
- **Docker Compose Stack**: Database + WordPress Container
- **Projekt-Repository**: Geklont und geöffnet

### Verzeichnisstruktur

```
riman-wordpress-swarm/
├── clean-and-setup-seo.sh
├── images/                     # Bilderordner
├── wordpress-enhanced-image-mappings-seo.json
├── docker-compose.yml
└── content/                    # Content-Ordner (konfigurierbar)
```

### Dateien die vorhanden sein müssen

- Content-Ordner (wie in `CONTENT_DIR` definiert)
- Images-Ordner mit Bildern
- Mindestens eine SEO-Mapping-Datei
- Laufender WordPress Container

## Installation & Ausführung

### 1. Script ausführbar machen

```bash
chmod +x ./clean-and-setup-seo.sh
```

### 2. Docker Stack starten (Optional)

```bash
# Option A: Docker Compose (empfohlen)
docker compose up -d

# Option B: Docker Swarm Stack
# Script versucht automatisch den Stack zu starten
```

### 3. Konfiguration überprüfen

Bearbeiten Sie das Script und passen Sie die Pfade an:

```bash
nano ./clean-and-setup-seo.sh
# Ändern Sie CONTENT_DIR und IMAGES_DIR
```

### 4. Script ausführen

```bash
./clean-and-setup-seo.sh
```

### 5. Lokale Website aufrufen

```
http://127.0.0.1:8801
```

(Port entsprechend Ihrer `docker-compose.yml` anpassen)

## Ablauf im Detail

### Phase 1: Bereinigung
- Container-Verbindung prüfen
- Alle WordPress-Inhalte löschen
- Upload-Verzeichnis leeren
- Database cleanup

### Phase 2: Content-Import
- Seiten aus Content-Verzeichnis importieren
- Kategorienstruktur aufbauen
- Hierarchien und Beziehungen erstellen

### Phase 3: Bildimport
- SEO-Mapping laden
- Bilder ohne Duplikate importieren
- Alt-Tags und Metadaten setzen
- WordPress Medienbibliothek aktualisieren

### Phase 4: SEO-Optimierung
- Kategorie-Metafelder setzen
- Startseite konfigurieren
- URL-Struktur optimieren
- Cache leeren

### Phase 5: Finalisierung
- Korrektur-Scripts ausführen
- Zuordnungen überprüfen
- Performance-Optimierung

## Troubleshooting

### Häufige Probleme

**1. Falsche Pfade**
```bash
# Fehlermeldung: "Directory not found"
# Lösung: Überprüfen Sie CONTENT_DIR und IMAGES_DIR im Script
```

**2. SEO-Mapping fehlt**
```bash
# Fehlermeldung: "No SEO mapping file found"
# Lösung: Mindestens eine wordpress-*-seo*.json Datei muss existieren
```

**3. Container nicht erreichbar**
```bash
# Fehlermeldung: "Container not running"
# Lösung: Überprüfen Sie den Container-Namen
docker ps | grep wordpress
# Passen Sie WORDPRESS_CONTAINER im Script an
```

**4. Lange Laufzeiten**
```bash
# Bei vielen Bildern ist eine lange Laufzeit normal
# Das Script loggt den Fortschritt
# Warten Sie die Verarbeitung ab
```

**5. Berechtigungsfehler**
```bash
# Lösung: Script ausführbar machen
chmod +x ./clean-and-setup-seo.sh
```

### Debug-Modus

Für detaillierte Ausgaben aktivieren Sie den Debug-Modus:

```bash
# Am Anfang des Scripts einfügen:
set -x  # Debug-Ausgabe aktivieren
```

### Log-Überwachung

```bash
# Docker Logs überwachen
docker logs -f riman-wordpress-swarm-wordpress-1

# Script-Ausgabe in Datei speichern
./clean-and-setup-seo.sh 2>&1 | tee setup-log.txt
```

## Unterschiede zu Live-Deployment

### Für Live-Systeme verwenden Sie:

**Code-Deployment:**
```bash
./scripts/wp-ssh-v2.sh deploy-wp
```

**Medien-Upload:**
```bash
./scripts/wp-ssh-v2.sh deploy-uploads ./wp-content/uploads
```

**Database-Import:**
```bash
# 1. Database Dump erstellen
mysqldump --single-transaction --routines --triggers database_name > dump.sql

# 2. Kollationsfix anwenden
# 3. Import auf Live-System
./scripts/wp-ssh-v2.sh wp-db-import dump.sql

# 4. URLs ersetzen
wp search-replace 'http://localhost:8801' 'https://ihre-domain.com'

# 5. Rewrite Rules aktualisieren
wp rewrite flush

# 6. Cache leeren  
wp cache flush
```

## Erweiterte Konfiguration

### Custom Content-Importer

Das Script verwendet verschiedene PHP-Importer:

- `content-importer.php` - Basis-Content-Import
- `seo-metadata-importer.php` - SEO-Metadaten
- `image-optimizer.php` - Bildoptimierung
- `category-structure-builder.php` - Kategorie-Aufbau

### Performance-Tuning

Für große Datenmengen:

```bash
# PHP Memory Limit erhöhen
# In docker-compose.yml:
environment:
  - PHP_MEMORY_LIMIT=512M
  - MAX_EXECUTION_TIME=300
```

### Backup vor Script-Ausführung

```bash
# WordPress Backup erstellen
docker exec WORDPRESS_CONTAINER wp db export backup.sql
# Upload-Ordner sichern
cp -r wp-content/uploads wp-content/uploads-backup
```

## Support

Bei Problemen:

1. **Logs überprüfen**: `docker logs riman-wordpress-swarm-wordpress-1`
2. **Container-Status**: `docker ps`
3. **Pfade validieren**: Existenz von Content- und Images-Verzeichnis
4. **SEO-Mapping**: JSON-Syntax überprüfen
5. **Berechtigungen**: Script und Verzeichnis-Rechte

---

**Entwickelt für das Riman WordPress Swarm Projekt**  
*Letzte Aktualisierung: September 2025*