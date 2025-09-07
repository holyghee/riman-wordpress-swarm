#!/bin/bash

# Für Rückbau - fehlende Kategorien aus dem Codex
RUECKBAU_ID=23

# Prüfe ob Kategorie existiert, bevor sie erstellt wird
if ! docker-compose run --rm wpcli wp term list category --slug=planung-rueckbau --field=term_id 2>/dev/null; then
  docker-compose run --rm wpcli wp term create category "Rückbauplanung: Strategische Konzepte" --slug="planung-rueckbau" --parent=$RUECKBAU_ID --description="Umfassende Planung von Rückbauprojekten. Von der Bestandsaufnahme bis zum Ausführungskonzept." --porcelain
fi

if ! docker-compose run --rm wpcli wp term list category --slug=ausschreibung --field=term_id 2>/dev/null; then
  docker-compose run --rm wpcli wp term create category "Ausschreibung & Vergabe: Rechtssichere Verfahren" --slug="ausschreibung" --parent=$RUECKBAU_ID --description="Professionelle Ausschreibung von Rückbauleistungen. Transparente Vergabeverfahren und faire Bewertung." --porcelain
fi

if ! docker-compose run --rm wpcli wp term list category --slug=bauleitung --field=term_id 2>/dev/null; then
  docker-compose run --rm wpcli wp term create category "Bauleitung Rückbau: Professionelle Projektsteuerung" --slug="bauleitung" --parent=$RUECKBAU_ID --description="Erfahrene Bauleitung für Rückbauprojekte. Koordination aller Gewerke und Überwachung der Ausführung." --porcelain
fi

if ! docker-compose run --rm wpcli wp term list category --slug=durchfuehrung --field=term_id 2>/dev/null; then
  docker-compose run --rm wpcli wp term create category "Rückbau-Durchführung: Sichere Ausführung" --slug="durchfuehrung" --parent=$RUECKBAU_ID --description="Fachgerechte Durchführung von Rückbauarbeiten. Modernste Technik und erfahrene Fachkräfte." --porcelain
fi

if ! docker-compose run --rm wpcli wp term list category --slug=entsorgung --field=term_id 2>/dev/null; then
  docker-compose run --rm wpcli wp term create category "Entsorgung: Fachgerechte Abfallverwertung" --slug="entsorgung" --parent=$RUECKBAU_ID --description="Umweltgerechte Entsorgung von Rückbaumaterial. Sortenreine Trennung und maximales Recycling." --porcelain
fi

if ! docker-compose run --rm wpcli wp term list category --slug=recycling --field=term_id 2>/dev/null; then
  docker-compose run --rm wpcli wp term create category "Recycling: Nachhaltige Materialverwertung" --slug="recycling" --parent=$RUECKBAU_ID --description="Maximale Wiederverwertung von Baumaterialien. Ressourcenschonung durch professionelles Recycling." --porcelain
fi

if ! docker-compose run --rm wpcli wp term list category --slug=dokumentation --field=term_id 2>/dev/null; then
  docker-compose run --rm wpcli wp term create category "Dokumentation: Lückenlose Nachweise" --slug="dokumentation" --parent=$RUECKBAU_ID --description="Vollständige Dokumentation aller Rückbauarbeiten. Rechtssichere Nachweise für Behörden und Auftraggeber." --porcelain
fi

echo "Rückbau-Kategorien erstellt!"

# Für Schadstoffsanierung - ergänze fehlende
SCHAD_ID=24

if ! docker-compose run --rm wpcli wp term list category --slug=pak-sanierung --field=term_id 2>/dev/null; then
  docker-compose run --rm wpcli wp term create category "PAK-Sanierung: Teerölbelastungen beseitigen" --slug="pak-sanierung" --parent=$SCHAD_ID --description="Sanierung PAK-belasteter Materialien. Sichere Entfernung von Teerölen und Bitumen." --porcelain
fi

# Für Arbeitssicherheit - ergänze fehlende  
SICHER_ID=25

if ! docker-compose run --rm wpcli wp term list category --slug=notfallmanagement --field=term_id 2>/dev/null; then
  docker-compose run --rm wpcli wp term create category "Notfallmanagement: Krisenvorsorge auf Baustellen" --slug="notfallmanagement" --parent=$SICHER_ID --description="Entwicklung von Notfallplänen für Baustellen. Schnelle Reaktion im Ernstfall." --porcelain
fi

echo "Alle fehlenden Kategorien wurden ergänzt!"
