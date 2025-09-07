<?php
/**
 * Correct Image Mapping - Map images based on actual prompts from bash processes
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Correcting Image Mapping Based on Prompts ===\n";

// Define the correct prompt-to-category mapping
$prompt_mappings = [
    'Emergency response and crisis management' => 'sicherheitskoordination-notfallmanagement',
    'Workplace safety and occupational health' => 'sicherheitskoordination-arbeitsschutz',
    'Professional contaminated site investigation' => 'altlasten-erkundung',
    'Professional remediation planning and engineering' => 'altlasten-sanierungsplanung',
    'Professional soil remediation with excavators' => 'altlasten-bodensanierung',
    'Groundwater remediation treatment plant' => 'altlasten-grundwassersanierung',
    'Environmental monitoring station with data' => 'altlasten-monitoring',
    'Professional project documentation and digital' => 'rueckbau-dokumentation',
    'Construction material recycling facility' => 'rueckbau-recycling',
    'Professional asbestos removal and remediation' => 'schadstoffe-asbest',
    'PCB contamination remediation and electrical' => 'schadstoffe-pcb',
    'PAH polycyclic aromatic hydrocarbons' => 'schadstoffe-pak',
    'Mineral fiber insulation removal' => 'schadstoffe-kmf',
    'Heavy metal contamination analysis' => 'schadstoffe-schwermetalle',
    'Construction safety planning office' => 'sicherheitskoordination-sigeko-planung',
    'Safety coordination during construction execution' => 'sicherheitskoordination-sigeko-ausfuehrung',
    'Risk assessment and hazard evaluation' => 'sicherheitskoordination-gefaehrdungsbeurteilung',
    'Professional construction project consulting' => 'beratung-projektberatung',
    'Construction mediation and conflict resolution' => 'beratung-baumediation',
    'Professional expert assessment and technical' => 'beratung-gutachten',
    'Regulatory compliance and certification' => 'beratung-compliance',
    'Professional safety and environmental training' => 'beratung-schulungen'
];

// Get all current upscaled images
$upscaled_images = get_posts([
    'post_type' => 'attachment',
    'posts_per_page' => -1,
    'meta_key' => '_midjourney_upscaled_image',
    'meta_value' => true
]);

echo "Found " . count($upscaled_images) . " upscaled images to reassign\n\n";

$corrected_count = 0;

foreach ($upscaled_images as $image) {
    $image_title = $image->post_title;
    $image_id = $image->ID;
    
    echo "Processing: $image_title (ID: $image_id)\n";
    
    // Find the correct category based on the image title or filename
    $correct_category_slug = null;
    $filename = basename(get_attached_file($image_id));
    
    // Try to match based on the current filename pattern
    foreach ($prompt_mappings as $prompt_key => $category_slug) {
        if (stripos($image_title, $prompt_key) !== false || 
            stripos($filename, str_replace('-', '_', $category_slug)) !== false) {
            $correct_category_slug = $category_slug;
            break;
        }
    }
    
    // If no match found, try a more flexible approach
    if (!$correct_category_slug) {
        if (stripos($image_title, 'contaminated') !== false || stripos($filename, 'erkundung') !== false) {
            $correct_category_slug = 'altlasten-erkundung';
        } elseif (stripos($image_title, 'remediation planning') !== false || stripos($filename, 'sanierungsplanung') !== false) {
            $correct_category_slug = 'altlasten-sanierungsplanung';
        } elseif (stripos($image_title, 'soil remediation') !== false || stripos($filename, 'bodensanierung') !== false) {
            $correct_category_slug = 'altlasten-bodensanierung';
        } elseif (stripos($image_title, 'groundwater') !== false || stripos($filename, 'grundwassersanierung') !== false) {
            $correct_category_slug = 'altlasten-grundwassersanierung';
        } elseif (stripos($image_title, 'monitoring') !== false || stripos($filename, 'monitoring') !== false) {
            $correct_category_slug = 'altlasten-monitoring';
        } elseif (stripos($image_title, 'documentation') !== false || stripos($filename, 'dokumentation') !== false) {
            $correct_category_slug = 'rueckbau-dokumentation';
        } elseif (stripos($image_title, 'recycling') !== false || stripos($filename, 'recycling') !== false) {
            $correct_category_slug = 'rueckbau-recycling';
        } elseif (stripos($image_title, 'asbestos') !== false || stripos($filename, 'asbest') !== false) {
            $correct_category_slug = 'schadstoffe-asbest';
        } elseif (stripos($image_title, 'PCB') !== false || stripos($filename, 'pcb') !== false) {
            $correct_category_slug = 'schadstoffe-pcb';
        } elseif (stripos($image_title, 'PAH') !== false || stripos($filename, 'pak') !== false) {
            $correct_category_slug = 'schadstoffe-pak';
        } elseif (stripos($image_title, 'fiber') !== false || stripos($filename, 'kmf') !== false) {
            $correct_category_slug = 'schadstoffe-kmf';
        } elseif (stripos($image_title, 'metal') !== false || stripos($filename, 'schwermetalle') !== false) {
            $correct_category_slug = 'schadstoffe-schwermetalle';
        } elseif (stripos($image_title, 'safety planning') !== false || stripos($filename, 'sigeko_planung') !== false) {
            $correct_category_slug = 'sicherheitskoordination-sigeko-planung';
        } elseif (stripos($image_title, 'safety coordination') !== false || stripos($filename, 'sigeko_ausfuehrung') !== false) {
            $correct_category_slug = 'sicherheitskoordination-sigeko-ausfuehrung';
        } elseif (stripos($image_title, 'risk assessment') !== false || stripos($filename, 'gefaehrdungsbeurteilung') !== false) {
            $correct_category_slug = 'sicherheitskoordination-gefaehrdungsbeurteilung';
        } elseif (stripos($image_title, 'workplace safety') !== false || stripos($filename, 'arbeitsschutz') !== false) {
            $correct_category_slug = 'sicherheitskoordination-arbeitsschutz';
        } elseif (stripos($image_title, 'emergency') !== false || stripos($filename, 'notfallmanagement') !== false) {
            $correct_category_slug = 'sicherheitskoordination-notfallmanagement';
        } elseif (stripos($image_title, 'consulting') !== false || stripos($filename, 'projektberatung') !== false) {
            $correct_category_slug = 'beratung-projektberatung';
        } elseif (stripos($image_title, 'mediation') !== false || stripos($filename, 'baumediation') !== false) {
            $correct_category_slug = 'beratung-baumediation';
        } elseif (stripos($image_title, 'expert assessment') !== false || stripos($filename, 'gutachten') !== false) {
            $correct_category_slug = 'beratung-gutachten';
        } elseif (stripos($image_title, 'compliance') !== false || stripos($filename, 'compliance') !== false) {
            $correct_category_slug = 'beratung-compliance';
        } elseif (stripos($image_title, 'training') !== false || stripos($filename, 'schulungen') !== false) {
            $correct_category_slug = 'beratung-schulungen';
        }
    }
    
    if (!$correct_category_slug) {
        echo "  ⚠️  Could not determine correct category\n";
        continue;
    }
    
    $category = get_term_by('slug', $correct_category_slug, 'category');
    if (!$category) {
        echo "  ⚠️  Category not found: $correct_category_slug\n";
        continue;
    }
    
    // Get current thumbnail for this category
    $current_thumbnail = get_term_meta($category->term_id, 'thumbnail_id', true);
    
    // Only reassign if this image is more appropriate (or if no thumbnail exists)
    if (!$current_thumbnail || $current_thumbnail != $image_id) {
        // Set as category featured image
        update_term_meta($category->term_id, 'thumbnail_id', $image_id);
        
        // Find and update linked page
        $page_slug = str_replace('-', '', $category->slug);
        $page = get_page_by_path($page_slug);
        if (!$page) {
            // Try alternative slug formats
            $page = get_page_by_path($category->slug);
        }
        
        if ($page) {
            set_post_thumbnail($page->ID, $image_id);
            echo "  ✅ Set as featured image for category: {$category->name} and page: {$page->post_title}\n";
        } else {
            echo "  ✅ Set as featured image for category: {$category->name} (no page found)\n";
        }
        
        $corrected_count++;
    } else {
        echo "  ➡️  Already correctly assigned\n";
    }
}

echo "\n=== Correction Complete ===\n";
echo "✅ Corrected: $corrected_count image assignments\n";
echo "🎯 Images should now match their intended categories!\n";