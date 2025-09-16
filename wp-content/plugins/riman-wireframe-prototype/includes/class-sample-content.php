<?php
/**
 * RIMAN Wireframe Prototype - Sample Content
 * Erstellt Demo-Inhalte basierend auf dem Wireframe
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sample Content Klasse
 * Erstellt automatisch Demo-Inhalte bei Plugin-Aktivierung
 */
class RIMAN_Wireframe_Sample_Content {

    /**
     * Beispiel-Video URLs für Demo-Content
     */
    private $sample_videos = array(
        'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4',
        'https://www.w3schools.com/html/mov_bbb.mp4',
        'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
        'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
        'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
        'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4'
    );

    /**
     * Konstruktor
     */
    public function __construct() {
        // Kein automatischer Hook - wird manuell bei Aktivierung aufgerufen
    }

    /**
     * Erstelle Sample Content basierend auf echtem Wireframe
     * Struktur: 2 Hauptseiten, 7 Unterseiten, 22 Detailseiten
     */
    public function create_sample_content() {
        // Prüfe ob Sample Content bereits existiert
        if ($this->sample_content_exists()) {
            return false; // Bereits vorhanden
        }

        // HAUPTSEITE 1: Sicherheits-Koordination & Mediation
        $hauptseite1 = $this->create_hauptseite(
            'Sicherheits-Koordination & Mediation',
            'Professionelle Sicherheitskoordination und Mediation für alle Bereiche. Von privaten Konflikten bis hin zu komplexen Bauprojekten.',
            'Umfassende Betreuung für sichere und konfliktfreie Projekte in allen Lebensbereichen. Unser erfahrenes Team bietet maßgeschneiderte Lösungen für komplexe Konfliktsituationen.'
        );

        // Unterseiten unter Hauptseite 1 (3 Stück)
        if ($hauptseite1) {
            $unterseite1_1 = $this->create_unterseite(
                'Mediation im Privat-Bereich',
                'Professionelle Mediation für private Konflikte. Konstruktive Lösungen für Familie, Nachbarschaft und Freundeskreis.',
                $hauptseite1,
                2
            );

            $unterseite1_2 = $this->create_unterseite(
                'Mediation in Unternehmen',
                'Mediation bei betrieblichen Konflikten. Für ein harmonisches Arbeitsklima und effiziente Zusammenarbeit.',
                $hauptseite1,
                2
            );

            $unterseite1_3 = $this->create_unterseite(
                'Sicherheits-Koordination & Mediation im Baubereich',
                'Spezialisierte Koordination und Mediation für Bauprojekte. Sicherheit und Konfliktlösung aus einer Hand.',
                $hauptseite1,
                2
            );

            // Detailseiten unter "Mediation im Privat-Bereich" (2 Stück)
            if ($unterseite1_1) {
                $this->create_detailseite(
                    'Mediation innerhalb von Familien',
                    'Familiäre Konflikte professionell lösen. Mediation für mehr Harmonie im Familienleben.',
                    $unterseite1_1,
                    array(
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation bei Generationenkonflikten',
                            'beschreibung' => 'Konflikte zwischen verschiedenen Generationen in der Familie konstruktiv lösen und Verständnis schaffen.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation bei Krisen in Lebensgemeinschaften',
                            'beschreibung' => 'Professionelle Unterstützung bei Krisen in Partnerschaften und Lebensgemeinschaften.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation bei wirtschaftlichen Konflikten',
                            'beschreibung' => 'Lösung von finanziellen und wirtschaftlichen Konflikten innerhalb der Familie.'
                        )
                        // 4. Infofeld: bitte Titel laut PDF bestätigen
                    )
                );

                $this->create_detailseite(
                    'Mediation im erweiterten privaten Umfeld',
                    'Konflikte in Nachbarschaft, Vereinen und erweiterten sozialen Kreisen konstruktiv lösen.',
                    $unterseite1_1,
                    array(
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation bei Konflikten in der Nachbarschaft und in Vereinen und Gemeinden',
                            'beschreibung' => 'Konflikte in Nachbarschaft, Vereinen und Gemeinden professionell lösen und langfristige Lösungen schaffen.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation bei Konflikten im Freundeskreis',
                            'beschreibung' => 'Freundschaftskonflikte behutsam lösen und Beziehungen erhalten.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation bei Konflikten im erweiterten Familienkreis',
                            'beschreibung' => 'Konflikte mit entfernteren Verwandten und Angeheirateten professionell lösen.'
                        )
                        // 4. Infofeld: bitte Titel laut PDF bestätigen
                    )
                );
            }

            // Detailseiten unter "Mediation in Unternehmen" (2 Stück)
            if ($unterseite1_2) {
                $this->create_detailseite(
                    'Mediation innerhalb von Unternehmen',
                    'Innerbetriebliche Konflikte professionell lösen. Für bessere Zusammenarbeit und höhere Produktivität.',
                    $unterseite1_2,
                    array(
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation bei Konflikten zwischen Mitarbeitern',
                            'beschreibung' => 'Lösung von zwischenmenschlichen Konflikten am Arbeitsplatz.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation bei Konflikten wegen personeller Veränderungen',
                            'beschreibung' => 'Begleitung und Konfliktlösung bei Umstrukturierungen und Personalwechseln.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation bei Konflikten wegen struktureller Veränderungen',
                            'beschreibung' => 'Unterstützung bei organisatorischen Veränderungen und damit verbundenen Konflikten.'
                        )
                        // 4. Infofeld: bitte Titel laut PDF bestätigen (Teammediation entfernt)
                    )
                );

                $this->create_detailseite(
                    'Mediation zwischen Unternehmen',
                    'Geschäftskonflikte und Streitigkeiten zwischen Unternehmen konstruktiv lösen.',
                    $unterseite1_2,
                    array(
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation bei rechtlichen Streitigkeiten',
                            'beschreibung' => 'Alternative Streitbeilegung bei Geschäftskonflikten als Gerichtsalternative.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation bei Firmenübernahmen',
                            'beschreibung' => 'Unterstützung bei komplexen Übernahme- und Fusionsprozessen.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation bei Insolvenzen',
                            'beschreibung' => 'Professionelle Begleitung bei Insolvenzverfahren und Gläubigerkonflikten.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation bei kulturell bedingten Konflikten',
                            'beschreibung' => 'Lösung interkultureller Geschäftskonflikte und Kommunikationsprobleme.'
                        )
                    )
                );
            }

            // Detailseiten unter "Sicherheits-Koordination & Mediation im Baubereich" (2 Stück)
            if ($unterseite1_3) {
                $this->create_detailseite(
                    'Sicherheitskoordination',
                    'Professionelle Sicherheitskoordination gemäß BauStVO und TRGS. Für maximale Sicherheit auf der Baustelle.',
                    $unterseite1_3,
                    array(
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Sicherheitskoordination gemäß BauStVO',
                            'beschreibung' => 'Koordination nach Baustellenverordnung für sichere Bauabläufe.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Sicherheitskoordination Asbest (TRGS 519)',
                            'beschreibung' => 'Spezialisierte Koordination bei asbestbelasteten Baustellen.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Sicherheitskoordination sonstige Gefahrstoffe',
                            'beschreibung' => 'Koordination bei anderen Gefahrstoffen gemäß TRGS 524.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'SiGe-Koordination allgemein',
                            'beschreibung' => 'Allgemeine Sicherheits- und Gesundheitsschutzkoordination.'
                        )
                    )
                );

                $this->create_detailseite(
                    'Mediation im Baubereich',
                    'Baukonflikte professionell lösen. Von der Genehmigungsplanung bis zur Ausführung.',
                    $unterseite1_3,
                    array(
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation bei der Genehmigungsplanung',
                            'beschreibung' => 'Konfliktlösung bereits in der frühen Planungsphase von Bauprojekten.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation in der Planungsphase',
                            'beschreibung' => 'Lösung von Planungskonflikten zwischen Architekten, Ingenieuren und Bauherren.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Mediation in der Ausführungsphase',
                            'beschreibung' => 'Konfliktlösung während der Bauausführung für reibungslose Abläufe.'
                        ),
                        array(
                            'video_url' => $this->get_random_video_url(),
                            'ueberschrift' => 'Baumediation allgemein',
                            'beschreibung' => 'Allgemeine Prinzipien und Methoden der Mediation im Bauwesen.'
                        )
                    )
                );
            }
        }

        // HAUPTSEITE 2: Sicherheits-Management im Baubereich
        $hauptseite2 = $this->create_hauptseite(
            'Sicherheits-Management im Baubereich',
            'Ganzheitliches Sicherheitsmanagement für den Baubereich. Abbruch, Schadstoffe, Entsorgung und Altlasten professionell managen.',
            'Komplexe Bauvorhaben erfordern spezialisiertes Management für maximale Sicherheit. Unser Expertenteam begleitet Sie durch alle kritischen Projektphasen.'
        );

        // Unterseiten unter Hauptseite 2 (4 Stück)
        if ($hauptseite2) {
            $unterseite2_1 = $this->create_unterseite(
                'Abbruch-Management',
                'Professionelles Management für Abbruchprojekte. Von der Planung bis zur fachgerechten Ausführung.',
                $hauptseite2,
                4
            );

            $unterseite2_2 = $this->create_unterseite(
                'Schadstoff-Management',
                'Umfassendes Management von Gefahrstoffen im Baubereich. Sicherheit und Vorschrifteneinhaltung garantiert.',
                $hauptseite2,
                4
            );

            $unterseite2_3 = $this->create_unterseite(
                'Entsorgungs-Management',
                'Professionelle Entsorgung und Verwertung von Baumaterialien. Umweltgerecht und kosteneffizient.',
                $hauptseite2,
                4
            );

            $unterseite2_4 = $this->create_unterseite(
                'Altlasten-Management',
                'Spezialisiertes Management für Altlastensanierung. Professionell, sicher und umweltgerecht.',
                $hauptseite2,
                4
            );

            // Detailseiten unter "Abbruch-Management" (4 Stück)
            if ($unterseite2_1) {
                $this->create_detailseite(
                    'Abbruchmanagement - braucht\'s das?',
                    'Warum professionelles Abbruchmanagement unverzichtbar ist. Risiken minimieren, Kosten optimieren.',
                    $unterseite2_1
                );

                $this->create_detailseite(
                    'Erkundung statischer Risiken',
                    'Professionelle Analyse der Bausubstanz und statischen Verhältnisse vor dem Abbruch.',
                    $unterseite2_1
                );

                $this->create_detailseite(
                    'Planung/Ausschreibung',
                    'Detaillierte Planung und professionelle Ausschreibung für Abbruchprojekte.',
                    $unterseite2_1
                );

                $this->create_detailseite(
                    'Fachbauleitung Rückbau',
                    'Professionelle Bauleitung für fachgerechten und sicheren Rückbau.',
                    $unterseite2_1
                );
            }

            // Detailseiten unter "Schadstoff-Management" (4 Stück)
            if ($unterseite2_2) {
                $this->create_detailseite(
                    'Gefahrstoffmanagement - wo haben wir Gefahrstoffe?',
                    'Systematische Identifizierung und Bewertung von Gefahrstoffen in Bausubstanz.',
                    $unterseite2_2
                );

                $this->create_detailseite(
                    'Gefahrstofferkundung',
                    'Professionelle Erkundung und Analyse von Gefahrstoffen gemäß aktueller Normen und Vorschriften.',
                    $unterseite2_2
                );

                $this->create_detailseite(
                    'Planung/Ausschreibung',
                    'Fachgerechte Planung und Ausschreibung für Gefahrstoffsanierung.',
                    $unterseite2_2
                );

                $this->create_detailseite(
                    'Fachbauleitung Gefahrstoffsanierung',
                    'Spezialisierte Bauleitung für die sichere Sanierung von Gefahrstoffen.',
                    $unterseite2_2
                );
            }

            // Detailseiten unter "Entsorgungs-Management" (4 Stück)
            if ($unterseite2_3) {
                $this->create_detailseite(
                    'Entsorgungsmanagement - für was?',
                    'Warum professionelles Entsorgungsmanagement notwendig ist und welche Vorteile es bietet.',
                    $unterseite2_3
                );

                $this->create_detailseite(
                    'Schadstofferkundung',
                    'Systematische Erkundung von entsorgungsrelevanten Schadstoffen in Baumaterialien.',
                    $unterseite2_3
                );

                $this->create_detailseite(
                    'Planung/Ausschreibung',
                    'Optimierte Planung und Ausschreibung für kosteneffiziente Entsorgungskonzepte.',
                    $unterseite2_3
                );

                $this->create_detailseite(
                    'Fachbauleitung Entsorgungsmanagement',
                    'Professionelle Überwachung und Koordination der Entsorgungsmaßnahmen.',
                    $unterseite2_3
                );
            }

            // Detailseiten unter "Altlasten-Management" (4 Stück)
            if ($unterseite2_4) {
                $this->create_detailseite(
                    'Altlastensanierung - doch nicht bei uns?',
                    'Altlasten sind häufiger als gedacht. Wann und wo Altlastensanierung notwendig wird.',
                    $unterseite2_4
                );

                $this->create_detailseite(
                    'Altlastenerkundung',
                    'Professionelle Erkundung und Bewertung von Altlasten gemäß aktueller Normen.',
                    $unterseite2_4
                );

                $this->create_detailseite(
                    'Planung/Ausschreibung',
                    'Detaillierte Planung und fachgerechte Ausschreibung für Altlastensanierung.',
                    $unterseite2_4
                );

                $this->create_detailseite(
                    'Fachbauleitung Altlasten',
                    'Spezialisierte Bauleitung für professionelle und sichere Altlastensanierung.',
                    $unterseite2_4
                );
            }
        }

        return true;
    }

    /**
     * Prüfe ob Sample Content bereits existiert
     */
    private function sample_content_exists() {
        $existing_posts = get_posts(array(
            'post_type' => 'riman_seiten',
            'meta_key' => '_riman_sample_content',
            'meta_value' => '1',
            'numberposts' => 1,
            'post_status' => 'any'
        ));

        return !empty($existing_posts);
    }

    /**
     * Erstelle Hauptseite
     */
    private function create_hauptseite($title, $excerpt, $content) {
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status' => 'publish',
            'post_type' => 'riman_seiten',
            'post_author' => get_current_user_id(),
            'menu_order' => 0
        );

        $post_id = wp_insert_post($post_data);

        if (!is_wp_error($post_id) && $post_id) {
            // Setze Seitentyp
            wp_set_post_terms($post_id, array('hauptseite'), 'seitentyp');

            // Setze Meta-Daten
            update_post_meta($post_id, '_riman_hauptseite_video_url', $this->get_random_video_url());
            update_post_meta($post_id, '_riman_hauptseite_beschreibung', $excerpt);
            update_post_meta($post_id, '_riman_sample_content', '1');

            return $post_id;
        }

        return false;
    }

    /**
     * Erstelle Unterseite
     */
    private function create_unterseite($title, $excerpt, $parent_id, $detailseiten_anzahl = 3) {
        $post_data = array(
            'post_title' => $title,
            'post_content' => $this->generate_lorem_ipsum(3),
            'post_excerpt' => $excerpt,
            'post_status' => 'publish',
            'post_type' => 'riman_seiten',
            'post_author' => get_current_user_id(),
            'post_parent' => $parent_id,
            'menu_order' => 0
        );

        $post_id = wp_insert_post($post_data);

        if (!is_wp_error($post_id) && $post_id) {
            // Setze Seitentyp
            wp_set_post_terms($post_id, array('unterseite'), 'seitentyp');

            // Setze Meta-Daten
            update_post_meta($post_id, '_riman_unterseite_video_url', $this->get_random_video_url());
            update_post_meta($post_id, '_riman_unterseite_detailseiten_anzahl', $detailseiten_anzahl);
            update_post_meta($post_id, '_riman_sample_content', '1');

            return $post_id;
        }

        return false;
    }

    /**
     * Erstelle Detailseite
     */
    private function create_detailseite($title, $excerpt, $parent_id, $video_info_fields = array()) {
        $post_data = array(
            'post_title' => $title,
            'post_content' => $this->generate_lorem_ipsum(2),
            'post_excerpt' => $excerpt,
            'post_status' => 'publish',
            'post_type' => 'riman_seiten',
            'post_author' => get_current_user_id(),
            'post_parent' => $parent_id,
            'menu_order' => 0
        );

        $post_id = wp_insert_post($post_data);

        if (!is_wp_error($post_id) && $post_id) {
            // Setze Seitentyp
            wp_set_post_terms($post_id, array('detailseite'), 'seitentyp');

            // Setze Meta-Felder (4 Video-Text-Infofelder pro Detailseite)
            if (!empty($video_info_fields)) {
                update_post_meta($post_id, '_riman_detailseite_video_info', $video_info_fields);
            } else {
                // Default: leer lassen oder generisch füllen – wir ergänzen gezielt je Detailseite unten beim Erstellen
                update_post_meta($post_id, '_riman_detailseite_video_info', array());
            }

            update_post_meta($post_id, '_riman_sample_content', '1');

            return $post_id;
        }

        return false;
    }

    // Kein separater Info-Posttyp mehr – Infoelemente bleiben Meta-Felder der Detailseite

    /**
     * Hole zufällige Video URL
     */
    private function get_random_video_url() {
        return $this->sample_videos[array_rand($this->sample_videos)];
    }

    /**
     * Generiere Lorem Ipsum Text
     */
    private function generate_lorem_ipsum($paragraphs = 1) {
        $lorem_paragraphs = array(
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
            
            'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
            
            'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.',
            
            'Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet.',
            
            'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.',
            
            'Similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque.'
        );

        $selected_paragraphs = array_slice($lorem_paragraphs, 0, min($paragraphs, count($lorem_paragraphs)));
        return '<p>' . implode('</p><p>', $selected_paragraphs) . '</p>';
    }

    /**
     * Lösche Sample Content
     * Hilfsfunktion für Entwicklung/Testing
     */
    public function delete_sample_content() {
        $sample_posts = get_posts(array(
            'post_type' => 'riman_seiten',
            'meta_key' => '_riman_sample_content',
            'meta_value' => '1',
            'numberposts' => -1,
            'post_status' => 'any'
        ));

        $deleted_count = 0;
        foreach ($sample_posts as $post) {
            if (wp_delete_post($post->ID, true)) {
                $deleted_count++;
            }
        }

        return $deleted_count;
    }

    /**
     * Hole Sample Content Statistiken
     */
    public function get_sample_content_stats() {
        $sample_posts = get_posts(array(
            'post_type' => 'riman_seiten',
            'meta_key' => '_riman_sample_content',
            'meta_value' => '1',
            'numberposts' => -1,
            'post_status' => 'any'
        ));

        $stats = array(
            'total' => count($sample_posts),
            'hauptseiten' => 0,
            'unterseiten' => 0,
            'detailseiten' => 0
        );

        foreach ($sample_posts as $post) {
            $terms = wp_get_post_terms($post->ID, 'seitentyp');
            if (!empty($terms) && !is_wp_error($terms)) {
                $seitentyp = $terms[0]->slug;
                if (isset($stats[$seitentyp . 'n'])) {
                    $stats[$seitentyp . 'n']++;
                }
            }
        }

        return $stats;
    }
}
