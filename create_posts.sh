#!/bin/bash

# Für Wohnungsentkernungen
CAT_ID=$(docker-compose run --rm wpcli wp term list category --slug=wohnungsentkernungen --field=term_id)
docker-compose run --rm wpcli wp post create --post_type=post --post_status=publish \
  --post_title="Entkernung denkmalgeschützter Gebäude: Besondere Anforderungen" \
  --post_content="Die Entkernung denkmalgeschützter Gebäude erfordert besondere Sorgfalt und Expertise. Die RIMAN GmbH verfügt über langjährige Erfahrung im sensiblen Umgang mit historischer Bausubstanz. Wir arbeiten eng mit Denkmalbehörden zusammen und dokumentieren jeden Arbeitsschritt detailliert." \
  --post_category=$CAT_ID --porcelain

# Für Teilabbruch
CAT_ID=$(docker-compose run --rm wpcli wp term list category --slug=teilabbruch --field=term_id)
docker-compose run --rm wpcli wp post create --post_type=post --post_status=publish \
  --post_title="Statisch anspruchsvolle Teilabbrüche: Präzision ist alles" \
  --post_content="Bei Teilabbrüchen in bestehenden Gebäuden ist höchste Präzision gefragt. Unsere Spezialisten analysieren die Statik im Detail und entwickeln maßgeschneiderte Rückbaukonzepte. Mit modernster Technik gewährleisten wir die Stabilität der verbleibenden Bausubstanz." \
  --post_category=$CAT_ID --porcelain

# Für Komplettabbruch
CAT_ID=$(docker-compose run --rm wpcli wp term list category --slug=komplettabbruch --field=term_id)
docker-compose run --rm wpcli wp post create --post_type=post --post_status=publish \
  --post_title="Nachhaltige Abbruchkonzepte: Recycling und Ressourcenschonung" \
  --post_content="Moderne Abbrucharbeiten bedeuten mehr als nur Abriss. Die RIMAN GmbH setzt auf nachhaltige Konzepte mit maximaler Materialverwertung. Durch sortenreines Trennen und fachgerechtes Recycling schonen wir Ressourcen und reduzieren Entsorgungskosten." \
  --post_category=$CAT_ID --porcelain

# Für Industrierückbau
CAT_ID=$(docker-compose run --rm wpcli wp term list category --slug=industrierueckbau --field=term_id)
docker-compose run --rm wpcli wp post create --post_type=post --post_status=publish \
  --post_title="Rückbau von Produktionsanlagen: Komplexe Herausforderungen meistern" \
  --post_content="Der Rückbau industrieller Anlagen erfordert spezielles Know-how. Von der Demontage komplexer Maschinen bis zur Dekontamination von Produktionshallen - wir bewältigen alle Herausforderungen. Unsere Experten erstellen detaillierte Rückbaukonzepte unter Berücksichtigung aller Sicherheitsaspekte." \
  --post_category=$CAT_ID --porcelain

# Für Asbestsanierung
CAT_ID=$(docker-compose run --rm wpcli wp term list category --slug=asbestsanierung --field=term_id)
docker-compose run --rm wpcli wp post create --post_type=post --post_status=publish \
  --post_title="Asbestdetektion und -bewertung: Gefährdung richtig einschätzen" \
  --post_content="Vor jeder Asbestsanierung steht die professionelle Bewertung. Unsere zertifizierten Sachverständigen führen umfassende Untersuchungen durch und erstellen rechtssichere Gutachten. Wir identifizieren alle asbesthaltigen Materialien und bewerten das Gefährdungspotential nach TRGS 519." \
  --post_category=$CAT_ID --porcelain

# Für PCB-Sanierung
CAT_ID=$(docker-compose run --rm wpcli wp term list category --slug=pcb-sanierung --field=term_id)
docker-compose run --rm wpcli wp post create --post_type=post --post_status=publish \
  --post_title="PCB in Fugenmassen: Sanierung nach PCB-Richtlinie" \
  --post_content="PCB-belastete Fugenmassen finden sich häufig in Gebäuden der 1960er und 70er Jahre. Die RIMAN GmbH führt die Sanierung streng nach PCB-Richtlinie durch. Von der Primärquellenentfernung bis zur Raumluftmessung - wir garantieren die vollständige Dekontamination." \
  --post_category=$CAT_ID --porcelain

# Für Schimmelsanierung
CAT_ID=$(docker-compose run --rm wpcli wp term list category --slug=schimmelsanierung --field=term_id)
docker-compose run --rm wpcli wp post create --post_type=post --post_status=publish \
  --post_title="Ursachenanalyse bei Schimmelbefall: Der Schlüssel zur nachhaltigen Sanierung" \
  --post_content="Eine erfolgreiche Schimmelsanierung beginnt mit der gründlichen Ursachenanalyse. Unsere Experten untersuchen Bauphysik, Raumklima und Nutzerverhalten. Nur durch Beseitigung der Ursachen verhindern wir erneuten Befall. Wir erstellen ganzheitliche Sanierungskonzepte für dauerhaft gesunde Raumluft." \
  --post_category=$CAT_ID --porcelain

# Für KMF-Sanierung
CAT_ID=$(docker-compose run --rm wpcli wp term list category --slug=kmf-sanierung --field=term_id)
docker-compose run --rm wpcli wp post create --post_type=post --post_status=publish \
  --post_title="Alte Mineralwolle sicher entfernen: Schutz vor lungengängigen Fasern" \
  --post_content="Künstliche Mineralfasern vor 1996 können krebserregende Fasern freisetzen. Die RIMAN GmbH entfernt alte Mineralwolle unter strengsten Sicherheitsvorkehrungen. Mit Unterdruckverfahren und spezieller Schutzausrüstung gewährleisten wir den Schutz aller Beteiligten." \
  --post_category=$CAT_ID --porcelain

# Für SiGe-Koordination
CAT_ID=$(docker-compose run --rm wpcli wp term list category --slug=sige-koordination --field=term_id)
docker-compose run --rm wpcli wp post create --post_type=post --post_status=publish \
  --post_title="SiGe-Plan erstellen: Grundlage für sichere Baustellen" \
  --post_content="Ein durchdachter SiGe-Plan ist die Basis jeder sicheren Baustelle. Unsere Koordinatoren analysieren alle Gewerke und deren Schnittstellen. Wir identifizieren potenzielle Gefährdungen und entwickeln praxistaugliche Schutzmaßnahmen. Der SiGe-Plan wird kontinuierlich an den Baufortschritt angepasst." \
  --post_category=$CAT_ID --porcelain

# Für Gefährdungsbeurteilung
CAT_ID=$(docker-compose run --rm wpcli wp term list category --slug=gefaehrdungsbeurteilung --field=term_id)
docker-compose run --rm wpcli wp post create --post_type=post --post_status=publish \
  --post_title="Digitale Gefährdungsbeurteilung: Effizient und rechtssicher" \
  --post_content="Moderne Gefährdungsbeurteilungen erstellen wir digital und systematisch. Mit spezieller Software erfassen wir alle Arbeitsplätze und Tätigkeiten. Die Bewertung erfolgt nach standardisierten Verfahren. Das Ergebnis: rechtssichere Dokumentation und praktikable Maßnahmen." \
  --post_category=$CAT_ID --porcelain

# Für Arbeitsschutzbetreuung
CAT_ID=$(docker-compose run --rm wpcli wp term list category --slug=arbeitsschutzbetreuung --field=term_id)
docker-compose run --rm wpcli wp post create --post_type=post --post_status=publish \
  --post_title="Externe Fachkraft für Arbeitssicherheit: Ihr Partner für sicheres Arbeiten" \
  --post_content="Als externe Fachkraft für Arbeitssicherheit unterstützen wir Unternehmen bei der Erfüllung ihrer Arbeitsschutzpflichten. Regelmäßige Begehungen, Unterweisungen und Beratung - wir sind Ihr kompetenter Partner. Profitieren Sie von unserer Erfahrung aus verschiedensten Branchen." \
  --post_category=$CAT_ID --porcelain

# Für Bauberatung
CAT_ID=$(docker-compose run --rm wpcli wp term list category --slug=bauberatung --field=term_id)
docker-compose run --rm wpcli wp post create --post_type=post --post_status=publish \
  --post_title="Machbarkeitsstudien: Fundierte Entscheidungsgrundlagen schaffen" \
  --post_content="Bevor große Bauprojekte starten, schaffen wir Klarheit. Unsere Machbarkeitsstudien analysieren technische, rechtliche und wirtschaftliche Aspekte. Wir bewerten Risiken und Chancen objektiv. So treffen Sie fundierte Entscheidungen auf solider Datenbasis." \
  --post_category=$CAT_ID --porcelain

# Für Umweltberatung
CAT_ID=$(docker-compose run --rm wpcli wp term list category --slug=umweltberatung --field=term_id)
docker-compose run --rm wpcli wp post create --post_type=post --post_status=publish \
  --post_title="Nachhaltigkeitszertifizierung: DGNB, LEED und BREEAM Beratung" \
  --post_content="Nachhaltiges Bauen wird immer wichtiger. Wir begleiten Ihr Projekt zur erfolgreichen Zertifizierung. Von der Konzeptphase bis zur finalen Auditierung - unsere Experten kennen alle Anforderungen der gängigen Zertifizierungssysteme und optimieren Ihre Nachhaltigkeitsstrategie." \
  --post_category=$CAT_ID --porcelain

# Für Konfliktmediation
CAT_ID=$(docker-compose run --rm wpcli wp term list category --slug=konfliktmediation --field=term_id)
docker-compose run --rm wpcli wp post create --post_type=post --post_status=publish \
  --post_title="Baumediation: Konflikte lösen, Projekte retten" \
  --post_content="Konflikte auf Baustellen kosten Zeit und Geld. Als neutrale Mediatoren helfen wir, festgefahrene Situationen zu lösen. Durch strukturierte Verfahren führen wir alle Beteiligten zu einvernehmlichen Lösungen. Vermeiden Sie langwierige Gerichtsverfahren durch professionelle Mediation." \
  --post_category=$CAT_ID --porcelain

echo "Alle Beispiel-Posts wurden erstellt!"
