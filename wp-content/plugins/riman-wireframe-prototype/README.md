# RIMAN Wireframe Prototype

Ein WordPress Plugin zur Erstellung einer prototypischen Website-Struktur basierend auf einem Wireframe, ohne die bestehende WordPress-Installation zu beeinträchtigen.

## 🎯 Überblick

Das Plugin implementiert eine hierarchische Website-Struktur mit spezialisierten Inhaltstypen für verschiedene Seitenebenen:

- **Hauptseiten**: Einstiegspunkte mit großen Videos und Beschreibungen  
- **Unterseiten**: Themenbereiche mit verlinkten Detailseiten
- **Detailseiten**: Spezifische Inhalte mit wiederholbaren Video-Info Feldern

## ✨ Funktionen

### Custom Post Type "RIMAN Seiten"
- Hierarchische Seitenstruktur (Parent-Child Beziehungen)
- Eigenes Admin-Menü
- Unterstützung für Titel, Editor, Featured Images und Seitenhierarchie
- Öffentliche URLs und REST API Support

### Custom Taxonomy "Seitentyp"  
- Kategorisierung in Hauptseite, Unterseite, Detailseite
- Admin-Spalte für schnelle Übersicht
- Automatische Erstellung der Standard-Terms

### Seitentyp-spezifische Meta Boxes
- **Hauptseite**: Video URL + Beschreibung
- **Unterseite**: Video URL + Anzahl verlinkter Detailseiten (2-4)
- **Detailseite**: 4 wiederholbare Video-Info Felder (Video, Überschrift, Beschreibung)

### Admin-Übersicht
- Wireframe Struktur-Baum aller Seiten
- Statistiken und Schnellzugriff
- Einstellungsseite mit Entwickler-Tools

### Frontend Templates
- Responsive Templates für jeden Seitentyp
- Video-Integration mit Hover-Effekten
- Breadcrumb-Navigation
- Mobile-optimiert

### Sample Content
- Automatische Demo-Inhalte bei Aktivierung
- Realistische Struktur basierend auf Sicherheits-/Mediation-Themen
- Beispiel-Videos und Lorem Ipsum Content

## 📁 Plugin-Struktur

```
riman-wireframe-prototype/
├── riman-wireframe-prototype.php     # Haupt-Plugin-Datei
├── includes/                         # PHP Klassen
│   ├── class-post-types.php         # Custom Post Type Registrierung
│   ├── class-taxonomies.php         # Custom Taxonomy Verwaltung  
│   ├── class-meta-boxes.php         # Meta Boxes für Seitentypen
│   ├── class-admin-pages.php        # Admin-Seiten und Übersichten
│   └── class-sample-content.php     # Demo-Content Generator
├── templates/                        # Frontend Templates
│   ├── single-riman_seiten-hauptseite.php
│   ├── single-riman_seiten-unterseite.php  
│   ├── single-riman_seiten-detailseite.php
│   └── single-riman_seiten.php      # Fallback Template
├── admin/                           # Admin Assets
│   └── admin-styles.css             # Admin-Styling
└── README.md                        # Diese Datei
```

## 🚀 Installation

1. Plugin-Ordner in `/wp-content/plugins/` kopieren
2. Plugin in WordPress Admin aktivieren
3. Automatische Erstellung von Standard-Terms und Demo-Content
4. RIMAN Seiten im Admin-Menü verwenden

## 🔧 Verwendung

### Neue Seiten erstellen
1. Zu "RIMAN Seiten" → "Hinzufügen" navigieren
2. Seitentyp in der Sidebar wählen
3. Entsprechende Meta-Felder ausfüllen
4. Bei Unterseiten: Parent-Seite zuweisen

### Wireframe-Struktur verwalten
1. "RIMAN Seiten" → "Wireframe Übersicht" öffnen
2. Struktur-Baum zeigt alle Seiten hierarchisch
3. Schnellzugriff zum Bearbeiten und Ansehen

### Meta-Felder verwenden
- **Video URLs**: MP4/WebM URLs für Hintergrund-Videos
- **Wiederholbare Felder**: Dynamisch Video-Info Blöcke hinzufügen/entfernen
- **Auto-Save**: Alle Änderungen werden automatisch gespeichert

## 🎨 Frontend-Features

### Video-Integration
- Autoplay-Videos mit Fallback-Postern
- Hover-Effekte für Video-Previews
- Responsive Video-Container

### Navigation
- Automatische Breadcrumb-Navigation
- Parent-Child Verlinkung
- Geschwister-Seiten Navigation

### Responsive Design
- Mobile-first Approach
- Grid-Layouts mit CSS Grid
- Touch-optimierte Bedienelemente

## ⚙️ Einstellungen

### Plugin-Deaktivierung
- Option: "Alle Daten beim Deaktivieren löschen"
- Standardmäßig bleiben Daten erhalten

### Entwickler-Tools
- Sample Content neu erstellen
- Permalink-Struktur neu laden
- Debug-Informationen

## 🔒 Sicherheit

- WordPress Nonces für alle Formulare
- Sanitization aller Eingaben
- Capability-Checks für Berechtigungen
- Verhindert direkten Dateizugriff

## 🎯 Wireframe-Kontext

Das Plugin implementiert eine geplante Website-Struktur:

```
Home
├── Sicherheits-Koordination & Mediation
│   ├── Koordination in der Planungsphase (3 Detailseiten)
│   ├── Ausführungsbegleitung (4 Detailseiten)
│   └── Mediation & Konfliktlösung (2 Detailseiten)
└── Sicherheits-Management im Baubereich  
    ├── Arbeitsschutz-Management (3 Detailseiten)
    ├── Qualitätssicherung (2 Detailseiten)
    └── Schulungen & Weiterbildung (4 Detailseiten)
```

## 📊 Statistiken

- **Gesamtzeilen**: 3.426 Lines of Code
- **PHP-Dateien**: 10 Dateien
- **Templates**: 4 Frontend-Templates
- **CSS**: 500+ Zeilen responsive Styling
- **JavaScript**: Interaktive Video-Features

## 🛠️ Technische Details

- **WordPress Version**: 5.0+  
- **PHP Version**: 7.4+
- **Standards**: WordPress Coding Standards
- **Features**: REST API, Gutenberg, Hierarchie
- **Performance**: Optimiert für große Seitenanzahl

## 📈 Erweiterung

Das Plugin ist modular aufgebaut und kann erweitert werden:

- Weitere Seitentypen hinzufügen
- Custom Fields erweitern  
- Template-Varianten erstellen
- API-Endpoints für externe Integration

## 🐛 Debugging

- Debug-Modus in WordPress aktivieren
- `_riman_sample_content` Meta-Key für Demo-Inhalte
- Browser-Konsole für JavaScript-Fehler
- WordPress Debug-Log prüfen

## 📝 Changelog

### Version 1.0.0
- Initiale Veröffentlichung
- Alle Basis-Features implementiert
- Sample Content und Templates
- Admin-Interface vollständig

## 👤 Entwickelt für RIMAN

Plugin speziell entwickelt für RIMAN Wireframe-Prototyping ohne Beeinträchtigung der bestehenden WordPress-Installation.