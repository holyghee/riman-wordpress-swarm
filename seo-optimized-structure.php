<?php
/**
 * SEO-optimierte Struktur für RIMAN WordPress
 * Kurze URLs + beschreibende Bildnamen
 */

// Die SEO-optimale Struktur
$seo_structure = [
    // Hauptkategorien - kurze, klare Slugs
    'altlasten' => [
        'name' => 'Altlastensanierung',
        'slug' => 'altlasten',
        'image' => 'altlasten-umweltsanierung.png',
        'children' => [
            ['name' => 'Erkundung', 'slug' => 'erkundung', 'image' => 'altlasten-erkundung-bodenanalyse.png'],
            ['name' => 'Sanierungsplanung', 'slug' => 'planung', 'image' => 'altlasten-sanierungsplanung.png'],
            ['name' => 'Bodensanierung', 'slug' => 'boden', 'image' => 'bodensanierung-dekontamination.png'],
            ['name' => 'Grundwasser', 'slug' => 'grundwasser', 'image' => 'grundwassersanierung-reinigung.png'],
            ['name' => 'Monitoring', 'slug' => 'monitoring', 'image' => 'altlasten-monitoring-ueberwachung.png']
        ]
    ],
    'rueckbau' => [
        'name' => 'Rückbaumanagement',
        'slug' => 'rueckbau',
        'image' => 'rueckbau-abriss-management.png',
        'children' => [
            ['name' => 'Planung', 'slug' => 'planung', 'image' => 'rueckbauplanung-konzept.png'],
            ['name' => 'Ausschreibung', 'slug' => 'ausschreibung', 'image' => 'rueckbau-ausschreibung-vergabe.png'],
            ['name' => 'Durchführung', 'slug' => 'durchfuehrung', 'image' => 'rueckbau-durchfuehrung-bauleitung.png'],
            ['name' => 'Entsorgung', 'slug' => 'entsorgung', 'image' => 'entsorgung-abfallmanagement.png'],
            ['name' => 'Recycling', 'slug' => 'recycling', 'image' => 'recycling-wiederverwertung.png']
        ]
    ],
    'schadstoffe' => [
        'name' => 'Schadstoff-Management',
        'slug' => 'schadstoffe',
        'image' => 'schadstoffmanagement-gefahrstoffe.png',
        'children' => [
            ['name' => 'Asbest', 'slug' => 'asbest', 'image' => 'asbestsanierung-entfernung.png'],
            ['name' => 'PCB', 'slug' => 'pcb', 'image' => 'pcb-sanierung-entsorgung.png'],
            ['name' => 'PAK', 'slug' => 'pak', 'image' => 'pak-sanierung-teeröle.png'],
            ['name' => 'KMF', 'slug' => 'kmf', 'image' => 'kmf-mineralfaser-sanierung.png'],
            ['name' => 'Schwermetalle', 'slug' => 'schwermetalle', 'image' => 'schwermetall-dekontamination.png']
        ]
    ],
    'sicherheit' => [
        'name' => 'Sicherheitskoordination',
        'slug' => 'sicherheit',
        'image' => 'sicherheitskoordination-sigeko.png',
        'children' => [
            ['name' => 'SiGeKo Planung', 'slug' => 'sigeko-planung', 'image' => 'sigeko-planung-koordination.png'],
            ['name' => 'SiGeKo Ausführung', 'slug' => 'sigeko-ausfuehrung', 'image' => 'sigeko-baustellenkoordination.png'],
            ['name' => 'Gefährdung', 'slug' => 'gefaehrdung', 'image' => 'gefaehrdungsbeurteilung-analyse.png'],
            ['name' => 'Arbeitsschutz', 'slug' => 'arbeitsschutz', 'image' => 'arbeitsschutz-sicherheit.png'],
            ['name' => 'Notfall', 'slug' => 'notfall', 'image' => 'notfallmanagement-krise.png']
        ]
    ],
    'beratung' => [
        'name' => 'Beratung & Mediation',
        'slug' => 'beratung',
        'image' => 'beratung-consulting.png',
        'children' => [
            ['name' => 'Projektberatung', 'slug' => 'projekt', 'image' => 'projektberatung-consulting.png'],
            ['name' => 'Baumediation', 'slug' => 'mediation', 'image' => 'baumediation-konfliktloesung.png'],
            ['name' => 'Gutachten', 'slug' => 'gutachten', 'image' => 'gutachten-expertise.png'],
            ['name' => 'Compliance', 'slug' => 'compliance', 'image' => 'compliance-recht.png'],
            ['name' => 'Schulungen', 'slug' => 'schulungen', 'image' => 'schulungen-weiterbildung.png']
        ]
    ]
];

// Bildnamen-Mapping für vorhandene Midjourney-Bilder
// Map die kryptischen Namen auf SEO-freundliche Namen
$image_rename_map = [
    'holyghee_Environmental_monitoring_station_*' => 'altlasten-monitoring-messstation.png',
    'holyghee_Asbestos_removal_specialists_*' => 'asbestsanierung-facharbeiter.png',
    'holyghee_Construction_material_recycling_*' => 'recycling-bauabfall-sortierung.png',
    'holyghee_Safety_coordination_*' => 'sicherheitskoordination-baustelle.png',
    // etc...
];

echo "SEO-OPTIMALE STRUKTUR:\n\n";

// Zeige die URL-Struktur
echo "URLs (kurz & hierarchisch):\n";
foreach ($seo_structure as $main) {
    echo "/{$main['slug']}/\n";
    foreach ($main['children'] as $child) {
        echo "  → /{$main['slug']}/{$child['slug']}/\n";
    }
}

echo "\nBildnamen (beschreibend & keyword-relevant):\n";
foreach ($seo_structure as $main) {
    echo "{$main['image']}\n";
    foreach ($main['children'] as $child) {
        echo "  → {$child['image']}\n";
    }
}
