# WordPress Integration Architecture

## Overview
The WordPress integration architecture enables seamless bi-directional sync between RIMAN's semantic image-content mapping system and WordPress sites, providing automated content generation, media management, and SEO optimization within the familiar WordPress ecosystem.

## Integration Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                    WORDPRESS INTEGRATION ARCHITECTURE                          │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  WORDPRESS ECOSYSTEM                                                            │
│  ┌─────────────────────────────────────────────────────────────────────────────┐ │
│  │                        WORDPRESS SITES                                     │ │
│  │                                                                             │ │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────────────┐  │ │
│  │  │ Blog Site       │  │ E-commerce Site │  │ Portfolio Site              │  │ │
│  │  │                 │  │                 │  │                             │  │ │
│  │  │ • WooCommerce   │  │ • WooCommerce   │  │ • Custom Theme              │  │ │
│  │  │ • Custom Theme  │  │ • Elementor     │  │ • Gallery Plugin            │  │ │
│  │  │ • SEO Plugin    │  │ • SEO Plugin    │  │ • SEO Plugin                │  │ │
│  │  └─────────────────┘  └─────────────────┘  └─────────────────────────────┘  │ │
│  │           │                       │                       │                  │ │
│  │           └───────────────────────┼───────────────────────┘                  │ │
│  │                                   ▼                                          │ │
│  │                                                                             │ │
│  │  ┌─────────────────────────────────────────────────────────────────────────┐  │ │
│  │  │                        RIMAN WORDPRESS PLUGIN                          │  │ │
│  │  │                                                                         │  │ │
│  │  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────────┐   │  │ │
│  │  │  │ Admin Dashboard │  │ Media Library   │  │ Content Generator       │   │  │ │
│  │  │  │                 │  │ Integration     │  │                         │   │  │ │
│  │  │  │ • Settings      │  │                 │  │ • Auto Alt Text         │   │  │ │
│  │  │  │ • API Config    │  │ • Bulk Upload   │  │ • Caption Generation    │   │  │ │
│  │  │  │ • Sync Status   │  │ • Sync Status   │  │ • SEO Optimization      │   │  │ │
│  │  │  │ • Analytics     │  │ • Metadata Mgmt │  │ • Title Suggestions     │   │  │ │
│  │  │  └─────────────────┘  └─────────────────┘  └─────────────────────────┘   │  │ │
│  │  │                                   │                                     │  │ │
│  │  │  ┌─────────────────┐  ┌───────────┼─────────┐  ┌─────────────────────┐   │  │ │
│  │  │  │ Gutenberg       │  │ Elementor │         │  │ WooCommerce         │   │  │ │
│  │  │  │ Blocks          │  │ Widgets   │         │  │ Integration         │   │  │ │
│  │  │  │                 │  │           │         │  │                     │   │  │ │
│  │  │  │ • Smart Gallery │  │ • AI Image│         │  │ • Product Images    │   │  │ │
│  │  │  │ • Image Block   │  │   Widget  │         │  │ • Catalog Sync      │   │  │ │
│  │  │  │ • SEO Block     │  │ • Content │         │  │ • Auto Descriptions │   │  │ │
│  │  │  │                 │  │   Block   │         │  │                     │   │  │ │
│  │  │  └─────────────────┘  └───────────┼─────────┘  └─────────────────────┘   │  │ │
│  │  └─────────────────────────────────────┼─────────────────────────────────────┘  │ │
│  └─────────────────────────────────────────┼─────────────────────────────────────────┘ │
│                                            │                                             │
│                                            ▼                                             │
│                                                                                 │
│  COMMUNICATION LAYER                                                            │
│  ┌─────────────────────────────────────────────────────────────────────────────┐ │
│  │                        API COMMUNICATION HUB                               │ │
│  │                                                                             │ │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────────────┐  │ │
│  │  │ REST API        │  │ GraphQL API     │  │ Webhook System              │  │ │
│  │  │ Gateway         │  │                 │  │                             │  │ │
│  │  │                 │  │ • Real-time     │  │ • Event Notifications       │  │ │
│  │  │ • Authentication│  │   Queries       │  │ • Status Updates            │  │ │
│  │  │ • Rate Limiting │  │ • Subscriptions │  │ • Error Reporting           │  │ │
│  │  │ • Request Valid │  │ • Batch Queries │  │ • Completion Callbacks      │  │ │
│  │  └─────────────────┘  └─────────────────┘  └─────────────────────────────┘  │ │
│  │                                   │                                         │ │
│  │  ┌─────────────────┐  ┹───────────┼─────────┐  ┌─────────────────────────┐  │ │
│  │  │ SDK & Libraries │  │ WebSocket │         │  │ Background Sync         │  │ │
│  │  │                 │  │ Server    │         │  │                         │  │ │
│  │  │ • PHP SDK       │  │           │         │  │ • WordPress Cron        │  │ │
│  │  │ • JavaScript    │  │ • Live    │         │  │ • Queue Processing      │  │ │
│  │  │ • REST Client   │  │   Updates │         │  │ • Retry Logic           │  │ │
│  │  │ • Error Handler │  │ • Push    │         │  │ • Conflict Resolution   │  │ │
│  │  └─────────────────┘  └───────────┼─────────┘  └─────────────────────────┘  │ │
│  └─────────────────────────────────────┼─────────────────────────────────────────┘ │
│                                        │                                           │
│                                        ▼                                           │
│                                                                                 │
│  RIMAN INTEGRATION SERVICES                                                     │
│  ┌─────────────────────────────────────────────────────────────────────────────┐ │
│  │                      WORDPRESS INTEGRATION SERVICE                         │ │
│  │                                                                             │ │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────────────┐  │ │
│  │  │ Site Manager    │  │ Sync Engine     │  │ Content Processor           │  │ │
│  │  │                 │  │                 │  │                             │  │ │
│  │  │ • Site Registry │  │ • Bi-directional│  │ • Content Analysis          │  │ │
│  │  │ • Auth Mgmt     │  │ • Incremental   │  │ • Quality Validation        │  │ │
│  │  │ • Health Checks │  │ • Conflict Res  │  │ • Template Matching         │  │ │
│  │  │ • Config Mgmt   │  │ • Batch Proc    │  │ • Personalization           │  │ │
│  │  └─────────────────┘  └─────────────────┘  └─────────────────────────────┘  │ │
│  │                                   │                                         │ │
│  │  ┌─────────────────┐  ┌───────────┼─────────┐  ┌─────────────────────────┐  │ │
│  │  │ Media Library   │  │ SEO       │         │  │ Analytics Engine        │  │ │
│  │  │ Sync            │  │ Optimizer │         │  │                         │  │ │
│  │  │                 │  │           │         │  │ • Usage Tracking        │  │ │
│  │  │ • Metadata Sync │  │ • Meta    │         │  │ • Performance Metrics   │  │ │
│  │  │ • Alt Text Gen  │  │   Tags    │         │  │ • Content Analytics     │  │ │
│  │  │ • Caption Gen   │  │ • Schema  │         │  │ • ROI Measurement       │  │ │
│  │  │ • Bulk Ops      │  │   Markup  │         │  │                         │  │ │
│  │  └─────────────────┘  └───────────┼─────────┘  └─────────────────────────┘  │ │
│  └─────────────────────────────────────┼─────────────────────────────────────────┘ │
│                                        │                                           │
│                                        ▼                                           │
│                                                                                 │
│  RIMAN CORE PLATFORM                                                           │
│  ┌─────────────────────────────────────────────────────────────────────────────┐ │
│  │                          CORE SERVICES                                     │ │
│  │                                                                             │ │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────────────┐  │ │
│  │  │ Neural Service  │  │ Content Service │  │ Search Service              │  │ │
│  │  │                 │  │                 │  │                             │  │ │
│  │  │ • Image Analysis│  │ • Text Gen      │  │ • Semantic Search           │  │ │
│  │  │ • Embeddings    │  │ • SEO Content   │  │ • Image Similarity          │  │ │
│  │  │ • Classification│  │ • Templates     │  │ • Content Discovery         │  │ │
│  │  └─────────────────┘  └─────────────────┘  └─────────────────────────────┘  │ │
│  └─────────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘
```

## WordPress Plugin Architecture

### 1. Plugin Structure
```
riman-wordpress/
├── riman-plugin.php              # Main plugin file
├── includes/
│   ├── class-riman-core.php      # Core plugin class
│   ├── class-riman-api.php       # API communication
│   ├── class-riman-media.php     # Media library integration
│   ├── class-riman-content.php   # Content generation
│   ├── class-riman-seo.php       # SEO optimization
│   └── class-riman-sync.php      # Sync engine
├── admin/
│   ├── class-riman-admin.php     # Admin interface
│   ├── partials/
│   │   ├── dashboard.php         # Main dashboard
│   │   ├── settings.php          # Plugin settings
│   │   └── analytics.php         # Analytics view
│   ├── css/
│   └── js/
├── public/
│   ├── class-riman-public.php    # Public-facing functionality
│   ├── css/
│   └── js/
├── blocks/                       # Gutenberg blocks
│   ├── smart-gallery/
│   ├── ai-image/
│   └── seo-content/
└── widgets/                      # Elementor widgets
    ├── ai-image-widget/
    └── smart-gallery-widget/
```

### 2. Core Plugin Components

#### 2.1 Main Plugin Class
```php
<?php
class Riman_Plugin {
    private $api_client;
    private $media_manager;
    private $content_generator;
    private $sync_engine;
    
    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->register_blocks();
    }
    
    public function run() {
        // Initialize plugin components
        $this->api_client = new Riman_API_Client(
            get_option('riman_api_key'),
            get_option('riman_api_url')
        );
        
        $this->media_manager = new Riman_Media_Manager($this->api_client);
        $this->content_generator = new Riman_Content_Generator($this->api_client);
        $this->sync_engine = new Riman_Sync_Engine($this->api_client);
        
        // Start background sync if enabled
        if (get_option('riman_auto_sync', false)) {
            $this->sync_engine->schedule_sync();
        }
    }
    
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cron jobs
        wp_schedule_event(time(), 'hourly', 'riman_sync_media');
    }
    
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'riman_sync_log';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            media_id bigint(20) NOT NULL,
            riman_id varchar(255) NOT NULL,
            sync_status varchar(50) NOT NULL,
            last_sync datetime DEFAULT CURRENT_TIMESTAMP,
            error_message text,
            PRIMARY KEY (id),
            KEY media_id (media_id),
            KEY riman_id (riman_id),
            KEY sync_status (sync_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
```

#### 2.2 API Client
```php
<?php
class Riman_API_Client {
    private $api_key;
    private $api_url;
    private $timeout = 30;
    
    public function __construct($api_key, $api_url) {
        $this->api_key = $api_key;
        $this->api_url = rtrim($api_url, '/');
    }
    
    public function upload_image($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        $file_name = basename($file_path);
        $mime_type = get_post_mime_type($attachment_id);
        
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'body' => [
                'file' => new CURLFile($file_path, $mime_type, $file_name),
                'metadata' => json_encode([
                    'wp_attachment_id' => $attachment_id,
                    'wp_site_url' => get_site_url(),
                    'title' => get_the_title($attachment_id),
                    'alt_text' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
                ])
            ],
            'timeout' => $this->timeout
        ];
        
        $response = wp_remote_post($this->api_url . '/api/v1/images/upload', $args);
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) !== 201) {
            throw new Exception('API error: ' . $data['error']);
        }
        
        return $data;
    }
    
    public function generate_content($riman_image_id, $options = []) {
        $default_options = [
            'content_type' => 'full',
            'tone' => 'professional',
            'length' => 'medium',
            'language' => 'en',
            'context' => [
                'website_type' => get_option('riman_website_type', 'blog'),
                'industry' => get_option('riman_industry', 'general')
            ]
        ];
        
        $options = array_merge($default_options, $options);
        
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'image_id' => $riman_image_id,
                'options' => $options
            ]),
            'timeout' => 60
        ];
        
        $response = wp_remote_post($this->api_url . '/api/v1/neural/generate-content', $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Content generation failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data;
    }
    
    public function search_images($query, $limit = 20) {
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'timeout' => $this->timeout
        ];
        
        $url = $this->api_url . '/api/v1/neural/search?' . http_build_query([
            'q' => $query,
            'limit' => $limit,
            'include_scores' => true
        ]);
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Search failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
```

### 3. Media Library Integration

#### 3.1 Media Manager Class
```php
<?php
class Riman_Media_Manager {
    private $api_client;
    
    public function __construct($api_client) {
        $this->api_client = $api_client;
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Hook into media upload
        add_action('add_attachment', [$this, 'handle_media_upload']);
        
        // Add custom fields to media library
        add_filter('attachment_fields_to_edit', [$this, 'add_riman_fields'], 10, 2);
        add_filter('attachment_fields_to_save', [$this, 'save_riman_fields'], 10, 2);
        
        // Add bulk actions
        add_filter('bulk_actions-upload', [$this, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-upload', [$this, 'handle_bulk_actions'], 10, 3);
        
        // Add AJAX handlers
        add_action('wp_ajax_riman_generate_content', [$this, 'ajax_generate_content']);
        add_action('wp_ajax_riman_sync_media', [$this, 'ajax_sync_media']);
    }
    
    public function handle_media_upload($attachment_id) {
        if (!get_option('riman_auto_process', false)) {
            return;
        }
        
        // Only process images
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }
        
        // Upload to RIMAN asynchronously
        wp_schedule_single_event(time(), 'riman_process_image', [$attachment_id]);
    }
    
    public function process_image($attachment_id) {
        try {
            // Upload image to RIMAN
            $response = $this->api_client->upload_image($attachment_id);
            $riman_id = $response['id'];
            
            // Store RIMAN ID
            update_post_meta($attachment_id, '_riman_id', $riman_id);
            update_post_meta($attachment_id, '_riman_status', 'uploaded');
            
            // Generate content if auto-generation is enabled
            if (get_option('riman_auto_generate', false)) {
                $this->generate_content_for_image($attachment_id, $riman_id);
            }
            
        } catch (Exception $e) {
            // Log error
            error_log('RIMAN processing failed for attachment ' . $attachment_id . ': ' . $e->getMessage());
            update_post_meta($attachment_id, '_riman_status', 'error');
            update_post_meta($attachment_id, '_riman_error', $e->getMessage());
        }
    }
    
    public function generate_content_for_image($attachment_id, $riman_id) {
        try {
            $response = $this->api_client->generate_content($riman_id);
            
            if ($response['status'] === 'completed') {
                $content = $response['content'];
                
                // Update WordPress attachment
                $updates = [
                    'ID' => $attachment_id,
                ];
                
                // Update title if empty or auto-generated
                $current_title = get_the_title($attachment_id);
                if (empty($current_title) || strpos($current_title, 'img_') === 0) {
                    $updates['post_title'] = $content['title'];
                }
                
                // Update description
                if (!empty($content['description'])) {
                    $updates['post_content'] = $content['description'];
                }
                
                wp_update_post($updates);
                
                // Update alt text
                if (!empty($content['alt_text'])) {
                    update_post_meta($attachment_id, '_wp_attachment_image_alt', $content['alt_text']);
                }
                
                // Store generated content
                update_post_meta($attachment_id, '_riman_generated_content', $content);
                update_post_meta($attachment_id, '_riman_quality_scores', $response['quality_scores']);
                update_post_meta($attachment_id, '_riman_status', 'processed');
                
            } else {
                // Content generation is still processing
                update_post_meta($attachment_id, '_riman_task_id', $response['task_id']);
                update_post_meta($attachment_id, '_riman_status', 'processing');
                
                // Schedule check for completion
                wp_schedule_single_event(time() + 30, 'riman_check_task_status', [$attachment_id, $response['task_id']]);
            }
            
        } catch (Exception $e) {
            error_log('Content generation failed for attachment ' . $attachment_id . ': ' . $e->getMessage());
            update_post_meta($attachment_id, '_riman_status', 'content_error');
        }
    }
    
    public function add_riman_fields($form_fields, $post) {
        $riman_id = get_post_meta($post->ID, '_riman_id', true);
        $riman_status = get_post_meta($post->ID, '_riman_status', true);
        $quality_scores = get_post_meta($post->ID, '_riman_quality_scores', true);
        
        $status_display = $this->get_status_display($riman_status);
        
        $form_fields['riman_status'] = [
            'label' => 'RIMAN Status',
            'input' => 'html',
            'html' => '<div class="riman-status ' . $riman_status . '">' . $status_display . '</div>'
        ];
        
        if ($quality_scores) {
            $form_fields['riman_quality'] = [
                'label' => 'Content Quality',
                'input' => 'html',
                'html' => $this->render_quality_scores($quality_scores)
            ];
        }
        
        if ($riman_id) {
            $form_fields['riman_actions'] = [
                'label' => 'RIMAN Actions',
                'input' => 'html',
                'html' => $this->render_action_buttons($post->ID, $riman_id)
            ];
        } else {
            $form_fields['riman_upload'] = [
                'label' => 'RIMAN',
                'input' => 'html',
                'html' => '<button type="button" class="button riman-upload" data-attachment-id="' . $post->ID . '">Process with RIMAN</button>'
            ];
        }
        
        return $form_fields;
    }
    
    private function render_quality_scores($scores) {
        $html = '<div class="riman-quality-scores">';
        
        foreach ($scores as $metric => $score) {
            $percentage = is_numeric($score) ? round($score * 100) : $score;
            $class = $percentage >= 80 ? 'good' : ($percentage >= 60 ? 'fair' : 'poor');
            
            $html .= '<div class="quality-metric">';
            $html .= '<span class="metric-name">' . ucfirst(str_replace('_', ' ', $metric)) . ':</span>';
            $html .= '<span class="metric-score ' . $class . '">' . $percentage . '%</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
}
```

### 4. Gutenberg Block Integration

#### 4.1 Smart Gallery Block
```javascript
// blocks/smart-gallery/index.js
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

registerBlockType('riman/smart-gallery', {
    title: __('RIMAN Smart Gallery', 'riman'),
    icon: 'format-gallery',
    category: 'media',
    
    attributes: {
        images: {
            type: 'array',
            default: []
        },
        columns: {
            type: 'number',
            default: 3
        },
        semanticQuery: {
            type: 'string',
            default: ''
        },
        autoGenerate: {
            type: 'boolean',
            default: false
        }
    },
    
    edit: ({ attributes, setAttributes }) => {
        const { images, columns, semanticQuery, autoGenerate } = attributes;
        
        const onSelectImages = (selectedImages) => {
            setAttributes({ images: selectedImages });
            
            if (autoGenerate) {
                generateContentForImages(selectedImages);
            }
        };
        
        const generateContentForImages = async (imageList) => {
            for (const image of imageList) {
                try {
                    const response = await wp.apiFetch({
                        path: '/riman/v1/generate-content',
                        method: 'POST',
                        data: {
                            attachment_id: image.id,
                            auto_update: true
                        }
                    });
                    
                    console.log('Content generated for image:', image.id, response);
                } catch (error) {
                    console.error('Failed to generate content:', error);
                }
            }
        };
        
        const performSemanticSearch = async () => {
            if (!semanticQuery.trim()) return;
            
            try {
                const response = await wp.apiFetch({
                    path: `/riman/v1/search?q=${encodeURIComponent(semanticQuery)}&limit=20`
                });
                
                const searchResults = response.results.map(result => ({
                    id: result.wp_attachment_id,
                    url: result.thumbnail_url,
                    alt: result.generated_content.alt_text,
                    caption: result.generated_content.description
                }));
                
                setAttributes({ images: searchResults });
            } catch (error) {
                console.error('Semantic search failed:', error);
            }
        };
        
        return (
            <div className="riman-smart-gallery-editor">
                <InspectorControls>
                    <PanelBody title={__('Gallery Settings', 'riman')} initialOpen={true}>
                        <RangeControl
                            label={__('Columns', 'riman')}
                            value={columns}
                            onChange={(value) => setAttributes({ columns: value })}
                            min={1}
                            max={6}
                        />
                        
                        <SelectControl
                            label={__('Auto Generate Content', 'riman')}
                            value={autoGenerate}
                            options={[
                                { label: 'Enabled', value: true },
                                { label: 'Disabled', value: false }
                            ]}
                            onChange={(value) => setAttributes({ autoGenerate: value === 'true' })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Semantic Search', 'riman')} initialOpen={false}>
                        <input
                            type="text"
                            placeholder={__('Enter search query...', 'riman')}
                            value={semanticQuery}
                            onChange={(e) => setAttributes({ semanticQuery: e.target.value })}
                            className="widefat"
                        />
                        <br /><br />
                        <Button 
                            isPrimary 
                            onClick={performSemanticSearch}
                            disabled={!semanticQuery.trim()}
                        >
                            {__('Search Images', 'riman')}
                        </Button>
                    </PanelBody>
                </InspectorControls>
                
                <div className="riman-gallery-container">
                    {images.length > 0 ? (
                        <div className={`riman-gallery columns-${columns}`}>
                            {images.map((image, index) => (
                                <div key={index} className="gallery-item">
                                    <img src={image.url} alt={image.alt || ''} />
                                    {image.caption && (
                                        <div className="gallery-caption">{image.caption}</div>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="gallery-placeholder">
                            <MediaUploadCheck>
                                <MediaUpload
                                    onSelect={onSelectImages}
                                    allowedTypes={['image']}
                                    multiple
                                    gallery
                                    render={({ open }) => (
                                        <Button 
                                            onClick={open} 
                                            isPrimary
                                            className="gallery-upload-button"
                                        >
                                            {__('Select Images', 'riman')}
                                        </Button>
                                    )}
                                />
                            </MediaUploadCheck>
                            
                            <p>{__('Or use semantic search to find relevant images automatically', 'riman')}</p>
                        </div>
                    )}
                </div>
            </div>
        );
    },
    
    save: ({ attributes }) => {
        const { images, columns } = attributes;
        
        return (
            <div className={`riman-smart-gallery columns-${columns}`}>
                {images.map((image, index) => (
                    <figure key={index} className="gallery-item">
                        <img src={image.url} alt={image.alt || ''} />
                        {image.caption && (
                            <figcaption className="gallery-caption">{image.caption}</figcaption>
                        )}
                    </figure>
                ))}
            </div>
        );
    }
});
```

### 5. WooCommerce Integration

#### 5.1 Product Image Enhancement
```php
<?php
class Riman_WooCommerce_Integration {
    
    public function __construct() {
        if (class_exists('WooCommerce')) {
            $this->init_woocommerce_hooks();
        }
    }
    
    private function init_woocommerce_hooks() {
        // Product image processing
        add_action('woocommerce_product_set_gallery_image_ids', [$this, 'process_product_images']);
        
        // Auto-generate product descriptions
        add_action('save_post_product', [$this, 'auto_generate_product_content']);
        
        // Add RIMAN metabox to product edit page
        add_action('add_meta_boxes', [$this, 'add_product_metabox']);
        
        // Bulk actions for products
        add_filter('bulk_actions-edit-product', [$this, 'add_product_bulk_actions']);
        add_filter('handle_bulk_actions-edit-product', [$this, 'handle_product_bulk_actions'], 10, 3);
    }
    
    public function process_product_images($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return;
        }
        
        $gallery_ids = $product->get_gallery_image_ids();
        $featured_image_id = $product->get_image_id();
        
        $all_image_ids = array_merge([$featured_image_id], $gallery_ids);
        $all_image_ids = array_filter($all_image_ids); // Remove empty values
        
        foreach ($all_image_ids as $image_id) {
            // Schedule processing for each image
            wp_schedule_single_event(time(), 'riman_process_product_image', [$image_id, $product_id]);
        }
    }
    
    public function process_product_image($image_id, $product_id) {
        try {
            $api_client = new Riman_API_Client(
                get_option('riman_api_key'),
                get_option('riman_api_url')
            );
            
            // Check if image is already processed
            $riman_id = get_post_meta($image_id, '_riman_id', true);
            
            if (!$riman_id) {
                // Upload image to RIMAN
                $response = $api_client->upload_image($image_id);
                $riman_id = $response['id'];
                update_post_meta($image_id, '_riman_id', $riman_id);
            }
            
            // Generate e-commerce specific content
            $product = wc_get_product($product_id);
            $content_response = $api_client->generate_content($riman_id, [
                'content_type' => 'full',
                'tone' => 'persuasive',
                'context' => [
                    'website_type' => 'ecommerce',
                    'product_name' => $product->get_name(),
                    'product_category' => $this->get_product_categories($product),
                    'price_range' => $this->get_price_context($product)
                ]
            ]);
            
            if ($content_response['status'] === 'completed') {
                $content = $content_response['content'];
                
                // Update image alt text with product-focused description
                $alt_text = $content['alt_text'];
                if (strpos($alt_text, $product->get_name()) === false) {
                    $alt_text = $product->get_name() . ' - ' . $alt_text;
                }
                update_post_meta($image_id, '_wp_attachment_image_alt', $alt_text);
                
                // Store product-specific content
                update_post_meta($image_id, '_riman_product_content', $content);
                update_post_meta($image_id, '_riman_product_id', $product_id);
            }
            
        } catch (Exception $e) {
            error_log('Product image processing failed: ' . $e->getMessage());
        }
    }
    
    public function auto_generate_product_content($post_id) {
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        
        if (!get_option('riman_wc_auto_generate', false)) {
            return;
        }
        
        $product = wc_get_product($post_id);
        $featured_image_id = $product->get_image_id();
        
        if (!$featured_image_id) {
            return;
        }
        
        $riman_id = get_post_meta($featured_image_id, '_riman_id', true);
        
        if (!$riman_id) {
            // Schedule image processing first
            wp_schedule_single_event(time(), 'riman_process_product_image', [$featured_image_id, $post_id]);
            return;
        }
        
        // Generate product-specific content
        $this->generate_product_description($post_id, $riman_id);
    }
    
    private function generate_product_description($product_id, $riman_id) {
        try {
            $api_client = new Riman_API_Client(
                get_option('riman_api_key'),
                get_option('riman_api_url')
            );
            
            $product = wc_get_product($product_id);
            
            // Check if product already has a detailed description
            $current_description = $product->get_description();
            if (strlen(trim(strip_tags($current_description))) > 100) {
                return; // Already has content
            }
            
            $content_response = $api_client->generate_content($riman_id, [
                'content_type' => 'description',
                'length' => 'long',
                'tone' => 'persuasive',
                'context' => [
                    'website_type' => 'ecommerce',
                    'product_name' => $product->get_name(),
                    'product_category' => $this->get_product_categories($product),
                    'price' => $product->get_price(),
                    'features_to_highlight' => [
                        'quality', 'benefits', 'use_cases', 'specifications'
                    ]
                ]
            ]);
            
            if ($content_response['status'] === 'completed') {
                $generated_description = $content_response['content']['description'];
                
                // Update product description
                wp_update_post([
                    'ID' => $product_id,
                    'post_content' => $generated_description
                ]);
                
                // Generate and update SEO meta
                if ($content_response['content']['seo']) {
                    $seo = $content_response['content']['seo'];
                    
                    // Update Yoast SEO meta if plugin is active
                    if (defined('WPSEO_VERSION')) {
                        update_post_meta($product_id, '_yoast_wpseo_title', $seo['title']);
                        update_post_meta($product_id, '_yoast_wpseo_metadesc', $seo['description']);
                    }
                    
                    // Update RankMath meta if plugin is active
                    if (defined('RANK_MATH_VERSION')) {
                        update_post_meta($product_id, 'rank_math_title', $seo['title']);
                        update_post_meta($product_id, 'rank_math_description', $seo['description']);
                    }
                }
                
                // Store generation metadata
                update_post_meta($product_id, '_riman_generated_at', current_time('mysql'));
                update_post_meta($product_id, '_riman_quality_score', $content_response['quality_scores']);
            }
            
        } catch (Exception $e) {
            error_log('Product content generation failed: ' . $e->getMessage());
        }
    }
    
    private function get_product_categories($product) {
        $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
        return is_array($categories) ? implode(', ', $categories) : '';
    }
    
    private function get_price_context($product) {
        $price = $product->get_price();
        
        if ($price < 50) {
            return 'budget';
        } elseif ($price < 200) {
            return 'mid-range';
        } else {
            return 'premium';
        }
    }
}
```

### 6. SEO Integration

#### 6.1 SEO Plugin Compatibility
```php
<?php
class Riman_SEO_Integration {
    
    public function __construct() {
        // Yoast SEO integration
        if (defined('WPSEO_VERSION')) {
            add_filter('wpseo_xml_sitemap_img_src', [$this, 'enhance_sitemap_images']);
            add_filter('wpseo_opengraph_image', [$this, 'optimize_og_image']);
        }
        
        // RankMath integration
        if (defined('RANK_MATH_VERSION')) {
            add_filter('rank_math/sitemap/urlimages', [$this, 'enhance_rankmath_sitemap']);
        }
        
        // All in One SEO integration
        if (defined('AIOSEO_VERSION')) {
            add_filter('aioseo_sitemap_images', [$this, 'enhance_aioseo_sitemap']);
        }
        
        // Schema markup enhancement
        add_filter('wp_get_attachment_metadata', [$this, 'enhance_image_metadata'], 10, 2);
    }
    
    public function enhance_sitemap_images($src, $attachment_id = null) {
        if (!$attachment_id) {
            return $src;
        }
        
        $riman_content = get_post_meta($attachment_id, '_riman_generated_content', true);
        
        if ($riman_content && !empty($riman_content['title'])) {
            // Enhance with RIMAN generated title for better SEO
            add_filter('wp_get_attachment_image_attributes', function($attr) use ($riman_content) {
                $attr['alt'] = $riman_content['alt_text'] ?? $attr['alt'];
                $attr['title'] = $riman_content['title'];
                return $attr;
            });
        }
        
        return $src;
    }
    
    public function enhance_image_metadata($data, $attachment_id) {
        $riman_content = get_post_meta($attachment_id, '_riman_generated_content', true);
        
        if ($riman_content) {
            // Add structured data
            $data['riman_seo'] = [
                'generated_title' => $riman_content['title'] ?? '',
                'generated_description' => $riman_content['description'] ?? '',
                'seo_keywords' => $riman_content['seo']['keywords'] ?? [],
                'quality_score' => get_post_meta($attachment_id, '_riman_quality_scores', true)
            ];
            
            // Add schema.org ImageObject properties
            $data['schema_org'] = [
                '@type' => 'ImageObject',
                'name' => $riman_content['title'] ?? get_the_title($attachment_id),
                'description' => $riman_content['description'] ?? '',
                'contentUrl' => wp_get_attachment_url($attachment_id),
                'thumbnailUrl' => wp_get_attachment_thumb_url($attachment_id),
                'uploadDate' => get_post_time('c', false, $attachment_id),
                'author' => [
                    '@type' => 'Organization',
                    'name' => get_bloginfo('name')
                ]
            ];
        }
        
        return $data;
    }
}
```

### 7. Analytics and Reporting

#### 7.1 Analytics Dashboard
```php
<?php
class Riman_Analytics {
    
    public function render_analytics_page() {
        $stats = $this->get_usage_stats();
        $performance = $this->get_performance_metrics();
        $recent_activity = $this->get_recent_activity();
        
        ?>
        <div class="wrap">
            <h1><?php _e('RIMAN Analytics', 'riman'); ?></h1>
            
            <div class="riman-analytics-grid">
                <!-- Usage Statistics -->
                <div class="analytics-card">
                    <h2><?php _e('Usage Statistics', 'riman'); ?></h2>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($stats['total_images']); ?></span>
                        <span class="stat-label"><?php _e('Images Processed', 'riman'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($stats['content_generated']); ?></span>
                        <span class="stat-label"><?php _e('Content Generated', 'riman'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['avg_quality_score']; ?>%</span>
                        <span class="stat-label"><?php _e('Avg Quality Score', 'riman'); ?></span>
                    </div>
                </div>
                
                <!-- Performance Metrics -->
                <div class="analytics-card">
                    <h2><?php _e('Performance Metrics', 'riman'); ?></h2>
                    <div class="metric-chart">
                        <canvas id="performanceChart" width="400" height="200"></canvas>
                    </div>
                    <div class="metric-details">
                        <p><?php _e('Avg Processing Time:', 'riman'); ?> <?php echo $performance['avg_processing_time']; ?>s</p>
                        <p><?php _e('Success Rate:', 'riman'); ?> <?php echo $performance['success_rate']; ?>%</p>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="analytics-card full-width">
                    <h2><?php _e('Recent Activity', 'riman'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Image', 'riman'); ?></th>
                                <th><?php _e('Action', 'riman'); ?></th>
                                <th><?php _e('Status', 'riman'); ?></th>
                                <th><?php _e('Quality Score', 'riman'); ?></th>
                                <th><?php _e('Date', 'riman'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activity as $activity): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo wp_get_attachment_thumb_url($activity['attachment_id']); ?>" 
                                         alt="" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php echo get_the_title($activity['attachment_id']); ?>
                                </td>
                                <td><?php echo $activity['action']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $activity['status']; ?>">
                                        <?php echo ucfirst($activity['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($activity['quality_score']): ?>
                                        <div class="quality-meter">
                                            <div class="quality-bar" style="width: <?php echo $activity['quality_score']; ?>%"></div>
                                            <span><?php echo $activity['quality_score']; ?>%</span>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-data">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y H:i', strtotime($activity['date'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
        // Chart rendering code
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('performanceChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($performance['labels']); ?>,
                    datasets: [{
                        label: 'Processing Time (seconds)',
                        data: <?php echo json_encode($performance['processing_times']); ?>,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
        </script>
        
        <style>
        .riman-analytics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .analytics-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        
        .analytics-card.full-width {
            grid-column: 1 / -1;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }
        
        .quality-meter {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quality-bar {
            height: 20px;
            background: linear-gradient(to right, #ff4444, #ffaa00, #44ff44);
            border-radius: 10px;
            min-width: 2px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-badge.completed { background: #46b450; color: white; }
        .status-badge.processing { background: #ffb900; color: white; }
        .status-badge.error { background: #dc3232; color: white; }
        </style>
        <?php
    }
    
    private function get_usage_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'riman_sync_log';
        
        $total_images = $wpdb->get_var(
            "SELECT COUNT(DISTINCT media_id) FROM $table_name WHERE sync_status = 'completed'"
        );
        
        $content_generated = $wpdb->get_var(
            $wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->postmeta} 
                WHERE meta_key = %s
            ", '_riman_generated_content')
        );
        
        $avg_quality = $wpdb->get_var(
            $wpdb->prepare("
                SELECT AVG(CAST(meta_value AS DECIMAL(5,2))) 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = %s
            ", '_riman_quality_scores')
        );
        
        return [
            'total_images' => (int) $total_images,
            'content_generated' => (int) $content_generated,
            'avg_quality_score' => $avg_quality ? round($avg_quality, 1) : 0
        ];
    }
    
    private function get_performance_metrics() {
        global $wpdb;
        
        // Get performance data for the last 30 days
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(last_sync) as date,
                AVG(processing_time) as avg_time,
                COUNT(*) as total_processed,
                SUM(CASE WHEN sync_status = 'completed' THEN 1 ELSE 0 END) as successful
            FROM {$wpdb->prefix}riman_sync_log 
            WHERE last_sync >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(last_sync)
            ORDER BY date DESC
            LIMIT 30
        "));
        
        $labels = [];
        $processing_times = [];
        $success_rate = 0;
        $total_processed = 0;
        $total_successful = 0;
        
        foreach ($results as $result) {
            $labels[] = date('M j', strtotime($result->date));
            $processing_times[] = round($result->avg_time, 2);
            $total_processed += $result->total_processed;
            $total_successful += $result->successful;
        }
        
        $success_rate = $total_processed > 0 ? round(($total_successful / $total_processed) * 100, 1) : 0;
        $avg_processing_time = count($processing_times) > 0 ? round(array_sum($processing_times) / count($processing_times), 2) : 0;
        
        return [
            'labels' => array_reverse($labels),
            'processing_times' => array_reverse($processing_times),
            'success_rate' => $success_rate,
            'avg_processing_time' => $avg_processing_time
        ];
    }
}
```

This comprehensive WordPress integration architecture provides seamless bi-directional synchronization, automated content generation, advanced SEO optimization, and detailed analytics tracking while maintaining full compatibility with the WordPress ecosystem and popular plugins.