#!/bin/bash

# CORRECTED WordPress Setup with SEO JSON Structure
echo "=== WordPress Setup mit korrekten SEO-Mappings ==="

# Configuration
WORDPRESS_CONTAINER="riman-wordpress-swarm-wordpress-1"
SEO_MAPPINGS="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-enhanced-image-mappings-seo.json"
IMAGES_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images"

# Check Docker
if ! docker ps | grep -q "$WORDPRESS_CONTAINER"; then
    echo "Starting WordPress..."
    docker swarm init 2>/dev/null || true
    docker stack deploy -c docker-compose.yml riman-wordpress-swarm
    sleep 30
fi

# Create working directory
mkdir -p /tmp/seo-setup
rm -f /tmp/seo-setup/*

echo "1. Creating WordPress pages from SEO JSON..."

# Create pages based on SEO structure
cat > /tmp/seo-setup/create-pages-from-seo.php << 'EOF'
<?php
require_once('/var/www/html/wp-load.php');

echo "=== Creating Pages from SEO JSON ===\n\n";

$seo_json = '/tmp/wordpress-enhanced-image-mappings-seo.json';
if (!file_exists($seo_json)) {
    die("❌ SEO JSON not found\n");
}

$seo = json_decode(file_get_contents($seo_json), true);
$created = 0;
$existing = 0;

// Create pages from all sections
$all_pages = array();

// Main categories
if (isset($seo['main_categories'])) {
    foreach ($seo['main_categories'] as $data) {
        if (isset($data['slug']) && isset($data['name'])) {
            $all_pages[$data['slug']] = $data['name'];
        }
    }
}

// Subcategories
if (isset($seo['subcategories'])) {
    foreach ($seo['subcategories'] as $parent => $subcats) {
        foreach ($subcats as $data) {
            if (isset($data['slug']) && isset($data['name'])) {
                $all_pages[$data['slug']] = $data['name'];
            }
        }
    }
}

// Special pages
if (isset($seo['pages'])) {
    foreach ($seo['pages'] as $data) {
        if (isset($data['slug'])) {
            $title = isset($data['title']) ? str_replace(' von RIMAN GmbH', '', $data['title']) : ucfirst($data['slug']);
            $all_pages[$data['slug']] = $title;
        }
    }
}

echo "Found " . count($all_pages) . " pages to create\n\n";

// Create pages
foreach ($all_pages as $slug => $title) {
    $existing_page = get_page_by_path($slug);
    
    if ($existing_page) {
        echo "⏭️  Exists: $slug → $title\n";
        $existing++;
    } else {
        $page_data = array(
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => "Content for $title will be added here.",
            'post_status'  => 'publish',
            'post_type'    => 'page'
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            echo "✅ Created: $slug → $title\n";
            $created++;
        } else {
            echo "❌ Failed: $slug\n";
        }
    }
}

echo "\n=== Results ===\n";
echo "✅ Created: $created\n";
echo "⏭️  Existing: $existing\n";
echo "📊 Total: " . count($all_pages) . "\n\n";
EOF

# Create image import script that works with SEO structure
cat > /tmp/seo-setup/import-seo-mappings.php << 'EOF'
<?php
require_once('/var/www/html/wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

echo "=== Import with SEO Mappings ===\n\n";

$seo_json = '/tmp/wordpress-enhanced-image-mappings-seo.json';
$images_dir = '/tmp/images/';

if (!file_exists($seo_json)) {
    die("❌ SEO JSON not found\n");
}

$seo = json_decode(file_get_contents($seo_json), true);
$imported = 0;
$skipped = 0;

// Function to import image with SEO metadata
function import_with_seo($image_filename, $seo_data, $page_slug) {
    global $images_dir, $imported, $skipped;
    
    // Find image file
    $image_path = $images_dir . $image_filename;
    if (!file_exists($image_path)) {
        echo "  ⚠️  Image not found: $image_filename\n";
        return false;
    }
    
    // Find page
    $page = get_page_by_path($page_slug);
    if (!$page) {
        echo "  ⚠️  Page not found: $page_slug\n";
        return false;
    }
    
    // Check if page already has featured image
    if (get_post_thumbnail_id($page->ID)) {
        echo "  ⏭️  Page already has featured image\n";
        $skipped++;
        return false;
    }
    
    // Import image
    $upload = wp_upload_bits($image_filename, null, file_get_contents($image_path));
    
    if ($upload['error']) {
        echo "  ❌ Upload failed: {$upload['error']}\n";
        return false;
    }
    
    // Create attachment
    $attachment_data = array(
        'guid' => $upload['url'],
        'post_mime_type' => mime_content_type($image_path),
        'post_title' => isset($seo_data['title']) ? $seo_data['title'] : pathinfo($image_filename, PATHINFO_FILENAME),
        'post_content' => isset($seo_data['description']) ? $seo_data['description'] : '',
        'post_excerpt' => isset($seo_data['caption']) ? $seo_data['caption'] : '',
        'post_status' => 'inherit'
    );
    
    $attachment_id = wp_insert_attachment($attachment_data, $upload['file'], $page->ID);
    
    if (is_wp_error($attachment_id)) {
        echo "  ❌ Attachment creation failed\n";
        return false;
    }
    
    // Generate metadata
    $attach_metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    wp_update_attachment_metadata($attachment_id, $attach_metadata);
    
    // Set alt text
    if (isset($seo_data['alt'])) {
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $seo_data['alt']);
    }
    
    // Set as featured image
    set_post_thumbnail($page->ID, $attachment_id);
    
    echo "  ✅ Featured image set with SEO metadata\n";
    $imported++;
    return true;
}

// Process main categories
if (isset($seo['main_categories'])) {
    echo "Processing main categories...\n";
    foreach ($seo['main_categories'] as $data) {
        if (isset($data['slug']) && isset($data['image'])) {
            echo "Main: {$data['slug']}\n";
            import_with_seo($data['image'], $data, $data['slug']);
        }
    }
}

// Process subcategories
if (isset($seo['subcategories'])) {
    echo "\nProcessing subcategories...\n";
    foreach ($seo['subcategories'] as $parent => $subcats) {
        foreach ($subcats as $data) {
            if (isset($data['slug']) && isset($data['image'])) {
                echo "Sub: {$data['slug']}\n";
                import_with_seo($data['image'], $data, $data['slug']);
            }
        }
    }
}

// Process special pages
if (isset($seo['pages'])) {
    echo "\nProcessing special pages...\n";
    foreach ($seo['pages'] as $data) {
        if (isset($data['slug']) && isset($data['image'])) {
            echo "Page: {$data['slug']}\n";
            import_with_seo($data['image'], $data, $data['slug']);
        }
    }
}

echo "\n=== Final Results ===\n";
echo "✅ Imported: $imported\n";
echo "⏭️  Skipped: $skipped\n";

// Final statistics
$pages = get_posts(['post_type' => 'page', 'numberposts' => -1]);
$with_images = 0;
$without_images = 0;

foreach ($pages as $page) {
    if (get_post_thumbnail_id($page->ID)) {
        $with_images++;
    } else {
        $without_images++;
    }
}

echo "\n📊 Pages with images: $with_images\n";
echo "📊 Pages without images: $without_images\n";
EOF

echo "2. Copy files to container..."

# Copy SEO JSON
docker cp "$SEO_MAPPINGS" "$WORDPRESS_CONTAINER:/tmp/wordpress-enhanced-image-mappings-seo.json"
echo "✅ SEO JSON copied"

# Copy images
docker cp "$IMAGES_DIR" "$WORDPRESS_CONTAINER:/tmp/"
echo "✅ Images copied"

# Copy scripts
docker cp /tmp/seo-setup/create-pages-from-seo.php "$WORDPRESS_CONTAINER:/var/www/html/"
docker cp /tmp/seo-setup/import-seo-mappings.php "$WORDPRESS_CONTAINER:/var/www/html/"
echo "✅ Scripts copied"

echo "3. Create WordPress pages from SEO structure..."
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/create-pages-from-seo.php

echo "4. Import images with SEO metadata..."
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/import-seo-mappings.php

echo ""
echo "=== Setup Complete ==="
echo "👉 http://127.0.0.1:8801"
echo "Admin: admin / admin_password_123"

# Final check
echo ""
echo "Final status check:"
docker exec "$WORDPRESS_CONTAINER" php -r '
require_once("/var/www/html/wp-load.php");
$pages = get_posts(["post_type" => "page", "numberposts" => -1]);
$with = 0;
$without = 0;
foreach($pages as $page) {
    if(get_post_thumbnail_id($page->ID)) {
        $with++;
    } else {
        $without++;
    }
}
echo "📊 FINAL: $with pages with Featured Images, $without without\n";
'

echo "✅ SEO Setup Complete!"