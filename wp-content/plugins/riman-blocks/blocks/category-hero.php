<?php
// RIMAN Blocks: Category Hero – zentriert, weiß; nutzt Featured Video der verknüpften Seite, sonst Bild.
if (!defined('ABSPATH')) exit;

/* ---------------------------------------
 * Metabox: Featured Video auf SEITEN
 * --------------------------------------- */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'riman_featured_video',
        __('Featured Video', 'riman'),
        function ($post) {
            wp_nonce_field('riman_featured_video_save', 'riman_featured_video_nonce');

            $video_id  = (int) get_post_meta($post->ID, '_riman_featured_video_id', true);
            $video_url = (string) get_post_meta($post->ID, '_riman_featured_video_url', true);
            $media_url = $video_id ? wp_get_attachment_url($video_id) : '';
            ?>
            <style>
              .riman-field{margin:8px 0}
              .riman-field input[type="text"]{width:100%}
              .riman-actions button{margin-right:6px}
              .riman-hint{font-size:12px;color:#666}
            </style>

            <div class="riman-field">
              <strong><?php esc_html_e('Video aus Mediathek', 'riman'); ?></strong>
              <div class="riman-actions" style="margin-top:6px;">
                <button type="button" class="button" id="riman_pick_video"><?php echo $video_id ? esc_html__('Video ändern', 'riman') : esc_html__('Video wählen', 'riman'); ?></button>
                <button type="button" class="button" id="riman_clear_video"><?php esc_html_e('Entfernen', 'riman'); ?></button>
              </div>
              <input type="hidden" id="riman_video_id" name="riman_video_id" value="<?php echo esc_attr($video_id); ?>">
              <input type="text" id="riman_video_media_url" value="<?php echo esc_url($media_url); ?>" placeholder="<?php esc_attr_e('Kein Video gewählt', 'riman'); ?>" readonly>
              <p class="riman-hint"><?php esc_html_e('MP4/WebM empfohlen. Dieses Video wird im Category Hero als Hintergrund genutzt.', 'riman'); ?></p>
            </div>

            <div class="riman-field">
              <em><?php esc_html_e('Oder direkte Video-URL (MP4/WebM):', 'riman'); ?></em>
              <input type="url" id="riman_video_url" name="riman_video_url" value="<?php echo esc_attr($video_url); ?>" placeholder="https://…/video.mp4">
            </div>

            <script>
            (function($){
              let frame;
              $('#riman_pick_video').on('click', function(e){
                e.preventDefault();
                frame = wp.media({ title:'Video auswählen', library:{ type:['video'] }, multiple:false, button:{ text:'Übernehmen' }});
                frame.on('select', function(){
                  const att = frame.state().get('selection').first().toJSON();
                  $('#riman_video_id').val(att.id);
                  $('#riman_video_media_url').val(att.url);
                });
                frame.open();
              });
              $('#riman_clear_video').on('click', function(){
                $('#riman_video_id').val('');
                $('#riman_video_media_url').val('');
              });
            })(jQuery);
            </script>
            <?php
        },
        'page',
        'side',
        'default'
    );
});
add_action('admin_enqueue_scripts', function ($hook) {
    if (in_array($hook, ['post.php','post-new.php'])) wp_enqueue_media();
});
add_action('save_post_page', function ($post_id) {
    if (!isset($_POST['riman_featured_video_nonce']) || !wp_verify_nonce($_POST['riman_featured_video_nonce'], 'riman_featured_video_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $video_id  = isset($_POST['riman_video_id']) ? (int) $_POST['riman_video_id'] : 0;
    $video_url = isset($_POST['riman_video_url']) ? esc_url_raw(trim($_POST['riman_video_url'])) : '';

    $video_id  ? update_post_meta($post_id, '_riman_featured_video_id', $video_id) : delete_post_meta($post_id, '_riman_featured_video_id');
    $video_url ? update_post_meta($post_id, '_riman_featured_video_url', $video_url) : delete_post_meta($post_id, '_riman_featured_video_url');
});

/* ---------------------------------------
 * Block-Registrierung (dynamic)
 * --------------------------------------- */
add_action('init', function () {

    // Editor-Script (optional, kleine Vorschau im Editor)
    if (defined('RIMAN_BLOCKS_URL')) {
        wp_register_script(
            'riman-category-hero-block',
            RIMAN_BLOCKS_URL . 'assets/category-hero-block.js',
            ['wp-blocks','wp-element','wp-block-editor','wp-components'],
            '1.2.3',
            true
        );
    }

    register_block_type('riman/category-hero', [
        'editor_script' => 'riman-category-hero-block',
        'attributes' => [
            'minHeight'          => ['type' => 'number',  'default' => 600],
            'dim'                => ['type' => 'number',  'default' => 40], // 0..100
            'showDescription'    => ['type' => 'boolean', 'default' => true],
            'useLinkedPageTitle' => ['type' => 'boolean', 'default' => true],
            'overlapHeader'      => ['type' => 'boolean', 'default' => false],
            'titleMode'          => ['type' => 'string',  'default' => 'category'],
            'debug'              => ['type' => 'boolean', 'default' => false],
        ],
        'supports' => ['align' => ['full']],
        'uses_context' => ['postId', 'postType'],
        'api_version' => 2,
        'render_callback' => function ($attributes, $content, $block) {
            // Dynamisches Rendering - wird bei jedem Seitenaufruf ausgeführt


            // ---- Attribute / Defaults
            $min_h      = isset($attributes['minHeight']) ? (int) $attributes['minHeight'] : 600;
            $dim        = isset($attributes['dim']) ? (int) $attributes['dim'] : 40;
            $alpha      = max(0, min(1, $dim / 100));
            $overlap    = !empty($attributes['overlapHeader']);
            $show_desc  = !empty($attributes['showDescription']);
            $title_mode = $attributes['titleMode'] ?? 'category';
            $debug      = !empty($attributes['debug']);

            // ---- Titel / Beschreibung / Area Label / Icon (erweiterte Logik für Riman-Seiten)
            $area_label = '';
            $icon_class = '';
            $desc = '';
            $title = '';

            if ($title_mode === 'page' && is_singular() && is_singular('riman_seiten') && class_exists('RIMAN_Hero_Meta')) {
                // RIMAN-SEITEN: Verwende Hero-Meta-Felder
                $hero_meta = RIMAN_Hero_Meta::get_hero_meta(get_the_ID());

                if ($debug) {
                    echo "\n<!-- RIMAN Hero Meta Raw: " . esc_html(json_encode($hero_meta)) . " -->\n";
                }

                // Hero-Titel (ohne "- Riman GmbH")
                $title = !empty($hero_meta['title']) ? $hero_meta['title'] :
                         str_replace(' - Riman GmbH', '', get_the_title());

                // Hero-Beschreibung
                $desc = $hero_meta['subtitle'] ?? '';

                // Bereichs-Label und Icon
                $area_label = $hero_meta['area_label'] ?? '';
                $icon_class = $hero_meta['icon'] ?? '';

                // Fallback für Beschreibung wenn leer
                if (empty($desc)) {
                    // 1. Priorität: Benutzerdefinierte Hero-Beschreibung (falls vorhanden)
                    $hero_desc = get_post_meta(get_the_ID(), '_riman_hero_description', true);
                    if (!empty($hero_desc)) {
                        $desc = $hero_desc;
                    } else {
                        // 2. Priorität: Featured Image Meta-Description
                        $featured_image_id = get_post_thumbnail_id();
                        if ($featured_image_id) {
                            $image_alt = get_post_meta($featured_image_id, '_wp_attachment_image_alt', true);
                            $image_caption = wp_get_attachment_caption($featured_image_id);
                            $image_description = get_post_field('post_content', $featured_image_id);

                            // Verwende Caption falls vorhanden, sonst Description, sonst Alt-Text
                            if (!empty($image_caption)) {
                                $desc = $image_caption;
                            } elseif (!empty($image_description)) {
                                $desc = $image_description;
                            } elseif (!empty($image_alt)) {
                                $desc = $image_alt;
                            }
                        }

                        // 3. Fallback: Page Excerpt
                        if (empty($desc)) {
                            $desc = get_the_excerpt() ?: '';
                        }
                    }
                }
            } elseif ($title_mode === 'page' && is_singular()) {
                // NORMALE SEITEN: bisherige Page-Logik
                $title = get_the_title();
                $desc = get_the_excerpt() ?: '';
            } elseif (is_category() || is_tax()) {
                // KATEGORIEN/TAXONOMIEN
                $term  = get_queried_object();
                $title = $term ? $term->name : get_the_archive_title();
                $desc  = $term ? term_description($term) : '';
            } else {
                // FALLBACK
                $title = get_the_title() ?: get_bloginfo('name');
                $desc  = is_singular() ? (get_the_excerpt() ?: '') : '';
            }

            // ---- Verknüpfte Seite zur Kategorie: Meta _linked_category_id, Fallback Slug
            $linked_page_id = 0;
            if (is_category() || is_tax()) {
                $term = get_queried_object();
                if ($term && !is_wp_error($term)) {
                    $linked = get_posts([
                        'post_type'   => 'page',
                        'meta_key'    => '_linked_category_id',
                        'meta_value'  => $term->term_id,
                        'numberposts' => 1,
                        'post_status' => 'publish',
                        'fields'      => 'ids',
                    ]);
                    if (!empty($linked)) {
                        $linked_page_id = (int) $linked[0];
                    } else {
                        $page = get_page_by_path($term->slug);
                        if ($page) $linked_page_id = (int) $page->ID;
                    }
                }
            } elseif (is_singular('page') || is_singular('riman_seiten')) {
                $linked_page_id = get_the_ID();
            }

            // ---- Hero-Metadaten der verknüpften Seite laden (für Kategorien)
            if ($linked_page_id && (is_category() || is_tax()) && class_exists('RIMAN_Hero_Meta')) {
                $linked_hero_meta = RIMAN_Hero_Meta::get_hero_meta($linked_page_id);

                if (!empty($linked_hero_meta['area_label'])) {
                    $area_label = $linked_hero_meta['area_label'];
                }
                if (!empty($linked_hero_meta['icon'])) {
                    $icon_class = $linked_hero_meta['icon'];
                }
            }

            // ---- Fallback: Hardcoded Area Labels für Kategorien ohne verknüpfte Seite
            if (empty($area_label) && (is_category() || is_tax())) {
                $term = get_queried_object();
                if ($term) {
                    $category_labels = [
                        'rueckbau' => ['label' => 'NACHHALTIG', 'icon' => 'fas fa-leaf'],
                        'altlasten' => ['label' => 'FACHGERECHT', 'icon' => 'fas fa-award'],
                        'schadstoffe' => ['label' => 'KOMPETENT', 'icon' => 'fas fa-star'],
                        'sicherheitskoordination' => ['label' => 'SICHER', 'icon' => 'fas fa-shield-alt'],
                        'beratung' => ['label' => 'VERTRAUENSVOLL', 'icon' => 'fas fa-handshake'],
                        'mediation' => ['label' => 'PROFESSIONELL', 'icon' => 'fas fa-briefcase']
                    ];

                    if (isset($category_labels[$term->slug])) {
                        $area_label = $category_labels[$term->slug]['label'];
                        $icon_class = $category_labels[$term->slug]['icon'];
                    }
                }
            }

            // ---- Media der verknüpften Seite: Featured Video (neu) oder Featured Image (wie früher)
            $video_src = ''; $video_mime = ''; $poster_url = '';
            if ($linked_page_id) {
                $vid_id  = (int) get_post_meta($linked_page_id, '_riman_featured_video_id', true);
                $vid_url = (string) get_post_meta($linked_page_id, '_riman_featured_video_url', true);

                if ($vid_id) {
                    $video_src  = wp_get_attachment_url($vid_id) ?: '';
                    $video_mime = get_post_mime_type($vid_id) ?: '';
                } elseif ($vid_url) {
                    $video_src  = $vid_url;
                    $video_mime = wp_check_filetype($video_src)['type'] ?? 'video/mp4';
                }

                $poster_url = get_the_post_thumbnail_url($linked_page_id, 'full') ?: '';
            }
            if (!$poster_url && is_singular()) {
                $poster_url = get_the_post_thumbnail_url(null, 'full') ?: '';
            }
            $image_url = $poster_url; // Fallback-Bild (wie früher)

            // ---- Ausgabe
            ob_start();
            $margin_top = $overlap ? '-60px' : '0';

            if ($video_src) : ?>
                <!-- VIDEO-HERO -->
                <section class="riman-hero alignfull"
                         style="min-height:<?php echo esc_attr($min_h); ?>px;margin-top:<?php echo esc_attr($margin_top); ?>;position:relative;overflow:hidden;">
                    <div class="riman-hero__media">
                        <video class="riman-hero__video"
                               autoplay muted loop playsinline preload="metadata"
                               <?php echo $image_url ? 'poster="'.esc_url($image_url).'"' : ''; ?>>
                            <source src="<?php echo esc_url($video_src); ?>" type="<?php echo esc_attr($video_mime ?: 'video/mp4'); ?>" />
                        </video>
                    </div>
                    <div class="riman-hero__overlay" style="background:rgba(0,0,0,<?php echo esc_attr(number_format($alpha,2,'.','')); ?>);"></div>

                    <div class="riman-hero__inner wp-block-group has-global-padding"
                         style="min-height:<?php echo esc_attr($min_h); ?>px;display:flex;align-items:center;justify-content:center;text-align:center;">
                        <div class="wp-block-group" style="max-width:var(--wp--style--global--content-size);margin:0 auto;">
                            <h1 class="wp-block-heading has-text-color has-base-color"
                                style="color:var(--wp--preset--color--base,#fff);margin-bottom:1rem;">
                                <?php echo esc_html($title); ?>
                            </h1>
                            <?php if ($show_desc && $desc) : ?>
                                <p class="has-text-color has-base-color" style="color:var(--wp--preset--color--base,#fff);opacity:.9;">
                                    <?php echo wp_kses_post($desc); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php else : ?>
                <!-- BILD-HERO wie ursprünglich: Gradient + Background Image -->
                <section class="riman-hero alignfull"
                         style="min-height:<?php echo esc_attr($min_h); ?>px;margin-top:<?php echo esc_attr($margin_top); ?>;
                                background:
                                  <?php /* Dim-Overlay integriert */ ?>
                                  linear-gradient(rgba(0,0,0,<?php echo esc_attr(number_format($alpha,2,'.','')); ?>), rgba(0,0,0,<?php echo esc_attr(number_format($alpha,2,'.','')); ?>)),
                                  url('<?php echo esc_url($image_url); ?>');
                                background-size:cover;background-position:center;
                                display:flex;align-items:center;justify-content:center;text-align:center;">
                    <div class="wp-block-group has-global-padding" style="max-width:var(--wp--style--global--content-size);margin:0 auto;">
                        <h1 class="wp-block-heading has-text-color has-base-color"
                            style="color:var(--wp--preset--color--base,#fff);margin-bottom:1rem;">
                            <?php echo esc_html($title); ?>
                        </h1>
                        <?php if ($show_desc && $desc): ?>
                            <p class="has-text-color has-base-color" style="color:var(--wp--preset--color--base,#fff);opacity:.9;">
                                <?php echo wp_kses_post($desc); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif;

            if ($debug) {
                echo "\n<!-- RIMAN Category Hero Debug: -->\n";
                echo "\n<!-- Post Type: " . esc_html(get_post_type()) . " -->\n";
                echo "\n<!-- Title Mode: " . esc_html($title_mode) . " -->\n";
                echo "\n<!-- Is Singular: " . (is_singular() ? 'true' : 'false') . " -->\n";
                echo "\n<!-- Is Singular riman_seiten: " . (is_singular('riman_seiten') ? 'true' : 'false') . " -->\n";
                echo "\n<!-- RIMAN_Hero_Meta exists: " . (class_exists('RIMAN_Hero_Meta') ? 'true' : 'false') . " -->\n";
                echo "\n<!-- linked_page_id={$linked_page_id} video_src=" . esc_html($video_src) . " poster_url=" . esc_html($poster_url) . " -->\n";
                echo "\n<!-- Hero Meta Debug: area_label='" . esc_html($area_label) . "' icon_class='" . esc_html($icon_class) . "' title='" . esc_html($title) . "' desc='" . esc_html($desc) . "' -->\n";

                // Direkte Meta-Abfrage
                $direct_meta = [
                    '_riman_hero_title' => get_post_meta(get_the_ID(), '_riman_hero_title', true),
                    '_riman_hero_subtitle' => get_post_meta(get_the_ID(), '_riman_hero_subtitle', true),
                    '_riman_hero_area_label' => get_post_meta(get_the_ID(), '_riman_hero_area_label', true),
                    '_riman_hero_icon' => get_post_meta(get_the_ID(), '_riman_hero_icon', true)
                ];
                echo "\n<!-- Direct Meta Fields: " . esc_html(json_encode($direct_meta)) . " -->\n";
            }

            return ob_get_clean();
        },
    ]);
});
