#!/bin/bash

# Hauptkategorien erstellen (falls noch nicht vorhanden)
docker-compose run --rm wpcli wp term create category "Rückbau & Abbruch" --slug="rueckbau" --description="Professioneller Rückbau und Abbruch von Gebäuden und Anlagen" --porcelain
docker-compose run --rm wpcli wp term create category "Schadstoffsanierung" --slug="schadstoffe" --description="Fachgerechte Sanierung von Schadstoffen in Gebäuden" --porcelain
docker-compose run --rm wpcli wp term create category "Arbeitssicherheit" --slug="sicherheit" --description="Umfassende Arbeitssicherheit und Gesundheitsschutz" --porcelain
docker-compose run --rm wpcli wp term create category "Beratung & Mediation" --slug="beratung" --description="Professionelle Beratung und Konfliktlösung" --porcelain

echo "Hauptkategorien erstellt"

# Rückbau & Abbruch - Unterkategorien
RUECKBAU_ID=$(docker-compose run --rm wpcli wp term list category --slug=rueckbau --field=term_id)
docker-compose run --rm wpcli wp term create category "Wohnungsentkernungen: Fachgerechte Entkernung von Wohnräumen" --slug="wohnungsentkernungen" --parent=$RUECKBAU_ID --description="Die RIMAN GmbH führt professionelle Wohnungsentkernungen durch. Von der Entfernung nicht tragender Wände bis zur kompletten Entkernung von Sanitär- und Elektroinstallationen." --porcelain
docker-compose run --rm wpcli wp term create category "Teilabbruch: Präziser Rückbau einzelner Gebäudeteile" --slug="teilabbruch" --parent=$RUECKBAU_ID --description="Kontrollierter Teilabbruch von Gebäudeabschnitten unter Erhalt der verbleibenden Bausubstanz. Höchste Präzision für anspruchsvolle Umbauprojekte." --porcelain
docker-compose run --rm wpcli wp term create category "Komplettabbruch: Vollständiger Gebäuderückbau" --slug="komplettabbruch" --parent=$RUECKBAU_ID --description="Komplette Demontage und Rückbau von Gebäuden aller Art. Von der Planung bis zur Entsorgung - alles aus einer Hand." --porcelain
docker-compose run --rm wpcli wp term create category "Industrierückbau: Demontage industrieller Anlagen" --slug="industrierueckbau" --parent=$RUECKBAU_ID --description="Spezialisiert auf den Rückbau von Industrieanlagen, Produktionshallen und technischen Einrichtungen mit besonderen Anforderungen." --porcelain

# Schadstoffsanierung - Unterkategorien
SCHAD_ID=$(docker-compose run --rm wpcli wp term list category --slug=schadstoffe --field=term_id)
docker-compose run --rm wpcli wp term create category "Asbestsanierung: Sichere Entfernung von Asbestmaterialien" --slug="asbestsanierung" --parent=$SCHAD_ID --description="Professionelle Asbestsanierung nach TRGS 519. Sichere Entfernung und Entsorgung von asbesthaltigen Materialien durch zertifizierte Fachkräfte." --porcelain
docker-compose run --rm wpcli wp term create category "PCB-Sanierung: Beseitigung von PCB-Belastungen" --slug="pcb-sanierung" --parent=$SCHAD_ID --description="Fachgerechte Sanierung von PCB-belasteten Bauteilen und Materialien. Komplette Dekontamination nach geltenden Richtlinien." --porcelain
docker-compose run --rm wpcli wp term create category "Schimmelsanierung: Nachhaltige Schimmelbeseitigung" --slug="schimmelsanierung" --parent=$SCHAD_ID --description="Professionelle Schimmelpilzsanierung mit nachhaltiger Ursachenbeseitigung. Wiederherstellung gesunder Raumluftqualität." --porcelain
docker-compose run --rm wpcli wp term create category "KMF-Sanierung: Entfernung künstlicher Mineralfasern" --slug="kmf-sanierung" --parent=$SCHAD_ID --description="Sichere Sanierung von künstlichen Mineralfasern (alte Mineralwolle). Schutz vor gesundheitsgefährdenden Fasern." --porcelain

# Arbeitssicherheit - Unterkategorien  
SICHER_ID=$(docker-compose run --rm wpcli wp term list category --slug=sicherheit --field=term_id)
docker-compose run --rm wpcli wp term create category "Sicherheitskoordination: SiGe-Koordination nach Baustellenverordnung" --slug="sige-koordination" --parent=$SICHER_ID --description="Professionelle Sicherheits- und Gesundheitsschutzkoordination für Baustellen. Erfüllung aller gesetzlichen Anforderungen." --porcelain
docker-compose run --rm wpcli wp term create category "Gefährdungsbeurteilung: Systematische Risikoanalyse" --slug="gefaehrdungsbeurteilung" --parent=$SICHER_ID --description="Erstellung rechtssicherer Gefährdungsbeurteilungen. Identifikation und Bewertung von Arbeitsplatzrisiken." --porcelain
docker-compose run --rm wpcli wp term create category "Arbeitsschutzbetreuung: Externe Fachkraft für Arbeitssicherheit" --slug="arbeitsschutzbetreuung" --parent=$SICHER_ID --description="Kompetente Betreuung durch externe Fachkräfte für Arbeitssicherheit. Erfüllung der DGUV Vorschrift 2." --porcelain

# Beratung & Mediation - Unterkategorien
BERATUNG_ID=$(docker-compose run --rm wpcli wp term list category --slug=beratung --field=term_id)
docker-compose run --rm wpcli wp term create category "Bauberatung: Expertenberatung für Bauprojekte" --slug="bauberatung" --parent=$BERATUNG_ID --description="Umfassende Beratung bei Bauprojekten. Von der Machbarkeitsstudie bis zur Bauüberwachung." --porcelain
docker-compose run --rm wpcli wp term create category "Umweltberatung: Nachhaltige Umweltkonzepte" --slug="umweltberatung" --parent=$BERATUNG_ID --description="Entwicklung nachhaltiger Umweltkonzepte. Beratung zu Umweltauflagen und ökologischen Baustandards." --porcelain
docker-compose run --rm wpcli wp term create category "Konfliktmediation: Professionelle Streitschlichtung" --slug="konfliktmediation" --parent=$BERATUNG_ID --description="Neutrale Mediation bei Baustreitigkeiten. Außergerichtliche Konfliktlösung für alle Beteiligten." --porcelain

echo "Alle Kategorien und Unterkategorien wurden erstellt!"
