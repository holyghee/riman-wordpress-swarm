<?php
/**
 * RIMAN Wireframe Prototype - Migrator
 * Übernimmt Inhalte (Content, Auszüge, Bilder, Videos) aus bestehender Struktur
 */

if (!defined('ABSPATH')) { exit; }

class RIMAN_Wireframe_Migrator {

    public function run() {
        $report = array(
            'mapped'   => 0,
            'skipped'  => 0,
            'updated'  => 0,
            'details'  => array(),
        );

        // Alle RIMAN Seiten (Ziel)
        $targets = get_posts(array(
            'post_type' => 'riman_seiten',
            'post_status' => 'any',
            'numberposts' => -1,
        ));

        if (empty($targets)) return $report;

        // Index nach normalisiertem Titel
        $tindex = array();
        foreach ($targets as $t) {
            $key = $this->norm($t->post_title);
            $tindex[$key] = $t;
        }

        // Quellseiten: reguläre Seiten
        $sources = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'any',
            'numberposts' => -1,
        ));

        foreach ($sources as $s) {
            $key = $this->norm($s->post_title);
            if (!isset($tindex[$key])) { $report['skipped']++; continue; }

            $target = $tindex[$key];
            $changed = $this->copy_to_target($s, $target);
            $report['mapped']++;
            if ($changed) { $report['updated']++; }
            $report['details'][] = sprintf('#%d "%s" → #%d "%s" %s', $s->ID, $s->post_title, $target->ID, $target->post_title, $changed ? '(updated)' : '(no changes)');
        }

        // Zusätzlich: via Kategorie-Verknüpfung (_linked_page_id)
        $terms = get_terms(array('taxonomy' => 'category', 'hide_empty' => false));
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $linked_page_id = (int) get_term_meta($term->term_id, '_linked_page_id', true);
                if (!$linked_page_id) continue;
                $s = get_post($linked_page_id);
                if (!$s) continue;
                // Map anhand des Kategorienamens
                $key = $this->norm($term->name);
                if (!isset($tindex[$key])) continue;
                $target = $tindex[$key];
                $changed = $this->copy_to_target($s, $target);
                $report['mapped']++;
                if ($changed) { $report['updated']++; }
                $report['details'][] = sprintf('[cat %s] #%d "%s" → #%d "%s" %s', $term->slug, $s->ID, $s->post_title, $target->ID, $target->post_title, $changed ? '(updated)' : '(no changes)');
            }
        }

        // Nachpflege: Info-Kinder ohne Bild -> Bild vom Eltern-Detail übernehmen
        $infos = get_posts(array(
            'post_type' => 'riman_seiten',
            'numberposts' => -1,
            'post_status' => 'publish',
            'tax_query' => array(array('taxonomy'=>'seitentyp','field'=>'slug','terms'=>array('info'))),
        ));
        foreach ($infos as $info) {
            if (has_post_thumbnail($info->ID)) continue;
            $parent_id = wp_get_post_parent_id($info->ID);
            if ($parent_id && has_post_thumbnail($parent_id)) {
                set_post_thumbnail($info->ID, get_post_thumbnail_id($parent_id));
                $report['updated']++;
                $report['details'][] = sprintf('Info #%d Bild vom Elternteil #%d übernommen', $info->ID, $parent_id);
            }
        }

        return $report;
    }

    private function norm($s) {
        $s = strtolower((string) $s);
        $s = remove_accents($s);
        $s = preg_replace('~[\s\-_/&]+~', ' ', $s);
        $s = preg_replace('~[^a-z0-9 ]+~', '', $s);
        $s = trim($s);
        return $s;
    }

    private function copy_to_target($src, $target) {
        $changed = false;

        // Content/Excerpt nur setzen, wenn Ziel leer ist
        if (empty($target->post_content) && !empty($src->post_content)) {
            wp_update_post(array('ID' => $target->ID, 'post_content' => $src->post_content));
            $changed = true;
        }
        if (empty($target->post_excerpt) && !empty($src->post_excerpt)) {
            wp_update_post(array('ID' => $target->ID, 'post_excerpt' => $src->post_excerpt));
            $changed = true;
        }

        // Featured Image übernehmen, wenn Ziel keines hat
        if (!has_post_thumbnail($target->ID)) {
            $thumb_id = get_post_thumbnail_id($src->ID);
            if ($thumb_id) { set_post_thumbnail($target->ID, $thumb_id); $changed = true; }
        }

        // Video-Metas übernehmen
        $vid_id  = get_post_meta($src->ID, '_riman_featured_video_id', true);
        $vid_url = get_post_meta($src->ID, '_riman_featured_video_url', true);
        if ($vid_id)  { update_post_meta($target->ID, '_riman_featured_video_id', $vid_id); $changed = true; }
        if ($vid_url) { update_post_meta($target->ID, '_riman_featured_video_url', $vid_url); $changed = true; }

        // Zusätzlich: pro Seitentyp dedizierte Felder befüllen
        $terms = wp_get_post_terms($target->ID, 'seitentyp');
        $type = !empty($terms) && !is_wp_error($terms) ? $terms[0]->slug : '';
        if ($type === 'hauptseite' && $vid_url) {
            update_post_meta($target->ID, '_riman_hauptseite_video_url', $vid_url); $changed = true;
        }
        if ($type === 'unterseite' && $vid_url) {
            update_post_meta($target->ID, '_riman_unterseite_video_url', $vid_url); $changed = true;
        }

        return $changed;
    }
}
