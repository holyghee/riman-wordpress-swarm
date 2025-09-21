<?php
// Featured Video Metabox für riman_seiten (analog zu Seiten)
if (!defined('ABSPATH')) exit;

class RIMAN_Wireframe_Featured_Video {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_box'));
        add_action('save_post_riman_seiten', array($this, 'save'));
        add_action('admin_enqueue_scripts', array($this, 'media')); 
    }
    public function media($hook){ if (in_array($hook, ['post.php','post-new.php'])) wp_enqueue_media(); }
    public function add_box() {
        add_meta_box('riman_featured_video', __('Featured Video', 'riman'), array($this,'render'), 'riman_seiten', 'side', 'default');
    }
    public function render($post){
        wp_nonce_field('riman_featured_video_save', 'riman_featured_video_nonce');
        $video_id  = (int) get_post_meta($post->ID, '_riman_featured_video_id', true);
        $video_url = (string) get_post_meta($post->ID, '_riman_featured_video_url', true);
        $media_url = $video_id ? wp_get_attachment_url($video_id) : '';
        ?>
        <style>.riman-field{margin:8px 0}.riman-field input[type="text"]{width:100%}.riman-actions button{margin-right:6px}.riman-hint{font-size:12px;color:#666}</style>
        <div class="riman-field">
          <strong><?php esc_html_e('Video aus Mediathek', 'riman'); ?></strong>
          <div class="riman-actions" style="margin-top:6px;">
            <button type="button" class="button" id="riman_pick_video"><?php echo $video_id ? esc_html__('Video ändern', 'riman') : esc_html__('Video wählen', 'riman'); ?></button>
            <button type="button" class="button" id="riman_clear_video"><?php esc_html_e('Entfernen', 'riman'); ?></button>
          </div>
          <input type="hidden" id="riman_video_id" name="riman_video_id" value="<?php echo esc_attr($video_id); ?>">
          <input type="text" id="riman_video_media_url" value="<?php echo esc_url($media_url); ?>" placeholder="<?php esc_attr_e('Kein Video gewählt', 'riman'); ?>" readonly>
          <p class="riman-hint"><?php esc_html_e('MP4/WebM empfohlen. Dieses Video wird in Hero/Modulen verwendet.', 'riman'); ?></p>
        </div>
        <div class="riman-field">
          <em><?php esc_html_e('Oder direkte Video-URL (MP4/WebM):', 'riman'); ?></em>
          <input type="url" id="riman_video_url" name="riman_video_url" value="<?php echo esc_attr($video_url); ?>" placeholder="https://…/video.mp4">
        </div>
        <script>(function($){let f;$('#riman_pick_video').on('click',function(e){e.preventDefault();f=wp.media({title:'Video auswählen',library:{type:['video']},multiple:false,button:{text:'Übernehmen'}});f.on('select',function(){const a=f.state().get('selection').first().toJSON();$('#riman_video_id').val(a.id);$('#riman_video_media_url').val(a.url);});f.open();});$('#riman_clear_video').on('click',function(){$('#riman_video_id').val('');$('#riman_video_media_url').val('');});})(jQuery);</script>
        <?php
    }
    public function save($post_id){
        if (!isset($_POST['riman_featured_video_nonce']) || !wp_verify_nonce($_POST['riman_featured_video_nonce'], 'riman_featured_video_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        $video_id  = isset($_POST['riman_video_id']) ? (int) $_POST['riman_video_id'] : 0;
        $video_url = isset($_POST['riman_video_url']) ? esc_url_raw(trim($_POST['riman_video_url'])) : '';
        $video_id  ? update_post_meta($post_id, '_riman_featured_video_id', $video_id) : delete_post_meta($post_id, '_riman_featured_video_id');
        $video_url ? update_post_meta($post_id, '_riman_featured_video_url', $video_url) : delete_post_meta($post_id, '_riman_featured_video_url');
    }
}

