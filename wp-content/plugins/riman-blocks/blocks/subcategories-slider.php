<?php
// RIMAN Blocks: Subcategories Slider (dynamic)
if (!defined('ABSPATH')) exit;

add_action('init', function() {
    // Editor Script (placeholder + settings)
    wp_register_script(
        'riman-subcategories-slider-editor',
        plugin_dir_url(__FILE__) . '../assets/subcategories-slider-block.js',
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'],
        '1.0',
        true
    );

    register_block_type('riman/subcategories-slider', [
        'editor_script' => 'riman-subcategories-slider-editor',
        'render_callback' => function($attributes) {
            if (!is_category()) { return ''; }

            $current_category = get_queried_object();
            $subcategories = get_categories([
                'parent' => $current_category->term_id,
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ]);
            if (empty($subcategories)) return '';

            $slider_id = 'slider-' . $current_category->term_id;
            ob_start();
            ?>
            <div class="riman-subcategories-slider-wrapper">
              <div class="riman-slider-container">
                <h2 style="text-align: center; margin-bottom: 40px;">Unsere Leistungen im Bereich <?php echo esc_html($current_category->name); ?></h2>
                <div class="riman-slider" id="<?php echo esc_attr($slider_id); ?>">
                  <div class="riman-slider-inner">
                  <?php foreach ($subcategories as $index => $subcategory):
                      $image_url = '';
                      // 1) Kategorie-Thumbnail
                      $term_thumb_id = get_term_meta($subcategory->term_id, '_thumbnail_id', true);
                      if ($term_thumb_id) {
                          $img = wp_get_attachment_image_url($term_thumb_id, 'large');
                          if ($img) { $image_url = $img; }
                      }
                      // 2) Verknüpfte Seite
                      if (!$image_url) {
                          $linked_pages = get_posts([
                              'post_type' => 'page',
                              'meta_key' => '_linked_category_id',
                              'meta_value' => $subcategory->term_id,
                              'numberposts' => 1
                          ]);
                          if (!empty($linked_pages)) {
                              $thumbnail_id = get_post_thumbnail_id($linked_pages[0]->ID);
                              if ($thumbnail_id) {
                                  $image_url = wp_get_attachment_image_url($thumbnail_id, 'large');
                              }
                          }
                      }
                  ?>
                    <div class="riman-slide" data-slide="<?php echo intval($index); ?>" style="<?php echo $index === 0 ? 'display: block;' : 'display: none;'; ?>">
                      <div class="riman-slide-card">
                        <?php if ($image_url): ?>
                          <div class="riman-slide-image">
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($subcategory->name); ?>">
                          </div>
                        <?php else: ?>
                          <div class="riman-slide-placeholder">
                            <svg width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#667eea" stroke-width="2">
                              <rect x="3" y="3" width="18" height="18" rx="2" />
                              <path d="M9 9h6v6H9z" />
                            </svg>
                          </div>
                        <?php endif; ?>
                        <div class="riman-slide-content">
                          <h3><?php echo esc_html($subcategory->name); ?></h3>
                          <?php if ($subcategory->description): ?>
                          <p><?php echo esc_html(wp_trim_words($subcategory->description, 25)); ?></p>
                          <?php endif; ?>
                          <a href="<?php echo esc_url(get_category_link($subcategory->term_id)); ?>" class="riman-button">Mehr erfahren →</a>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                  </div>
                  <?php if (count($subcategories) > 1): ?>
                    <div class="riman-slider-controls">
                      <button class="slider-prev">‹</button>
                      <button class="slider-next">›</button>
                    </div>
                    <div class="riman-slider-indicators">
                      <?php for ($i = 0; $i < count($subcategories); $i++): ?>
                        <span class="indicator <?php echo $i === 0 ? 'active' : ''; ?>" data-slide="<?php echo intval($i); ?>"></span>
                      <?php endfor; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <style>
            .riman-subcategories-slider-wrapper { background: linear-gradient(135deg,#f5f7fa 0%,#c3cfe2 100%); padding:60px 0; margin-bottom:40px }
            .riman-slider-container { max-width:1200px; margin:0 auto; padding:0 20px }
            .riman-slider { position:relative; overflow:hidden }
            .riman-slider-inner { position:relative; min-height:500px }
            .riman-slide-card { background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,.1); display:flex; min-height:500px }
            .riman-slide-image{flex:0 0 50%; overflow:hidden}
            .riman-slide-image img{width:100%; height:100%; object-fit:cover}
            .riman-slide-placeholder{flex:0 0 50%; background:linear-gradient(135deg,#667eea20 0%,#764ba220 100%); display:flex; align-items:center; justify-content:center}
            .riman-slide-content{flex:1; padding:60px; display:flex; flex-direction:column; justify-content:center}
            .riman-slide-content h3{font-size:2rem; margin-bottom:20px; color:#333}
            .riman-slide-content p{font-size:1.1rem; line-height:1.8; color:#666; margin-bottom:30px}
            .riman-button{display:inline-block; background:#667eea; color:#fff!important; padding:12px 30px; text-decoration:none; border-radius:5px; align-self:flex-start; transition:background .3s}
            .riman-button:hover{background:#5569d0; color:#fff!important}
            .riman-slider-controls{position:absolute; top:50%; transform:translateY(-50%); width:100%; display:flex; justify-content:space-between; padding:0 20px; pointer-events:none; z-index:10}
            .riman-slider-controls button{pointer-events:all; background:#fff; border:none; width:50px; height:50px; border-radius:50%; font-size:24px; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,.2); transition:transform .3s}
            .riman-slider-controls button:hover{transform:scale(1.1)}
            .riman-slider-indicators{position:absolute; bottom:-40px; left:50%; transform:translateX(-50%); display:flex; gap:10px}
            .indicator{width:12px; height:12px; border-radius:50%; background:#fff; opacity:.5; cursor:pointer; transition:all .3s}
            .indicator.active{opacity:1; width:30px; border-radius:6px; background:#667eea}
            @media (max-width:768px){ .riman-slide-card{flex-direction:column; min-height:auto} .riman-slide-image,.riman-slide-placeholder{flex:none; height:250px; width:100%} .riman-slide-content{padding:40px 30px} }
            </style>
            <script> (function(){ if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',init);} else{init();} function init(){ var s=document.getElementById('<?php echo esc_js($slider_id); ?>'); if(!s) return; var slides=s.querySelectorAll('.riman-slide'); var indicators=s.querySelectorAll('.indicator'); var prev=s.querySelector('.slider-prev'); var next=s.querySelector('.slider-next'); var i=0, n=slides.length; function show(k){ for(var a=0;a<n;a++){slides[a].style.display='none'; if(indicators[a]) indicators[a].classList.remove('active'); } if(slides[k]) slides[k].style.display='block'; if(indicators[k]) indicators[k].classList.add('active'); i=k; } if(prev) prev.addEventListener('click',function(){ show((i-1+n)%n); }); if(next) next.addEventListener('click',function(){ show((i+1)%n); }); for(var a=0;a<indicators.length;a++){ (function(idx){ indicators[idx].addEventListener('click',function(){ show(idx); }); })(a); } if(n>1){ setInterval(function(){ show((i+1)%n); }, 5000); } show(0);} })(); </script>
            <?php
            return ob_get_clean();
        }
    ]);
});
