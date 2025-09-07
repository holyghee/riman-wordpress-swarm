#!/usr/bin/env python3
"""
Download Discord images with category-based filenames.
This script reads the comprehensive mapping and downloads all images
with proper WordPress category-based naming.
"""

import json
import requests
import os
import sys
from urllib.parse import urlparse
from pathlib import Path

def download_image(url, filename, output_dir="downloaded_images"):
    """Download an image from URL and save with given filename."""
    try:
        # Create output directory if it doesn't exist
        os.makedirs(output_dir, exist_ok=True)
        
        # Full path for the file
        filepath = os.path.join(output_dir, filename)
        
        # Skip if file already exists
        if os.path.exists(filepath):
            print(f"Skipping {filename} - already exists")
            return True
            
        print(f"Downloading {filename}...")
        
        # Download the image
        response = requests.get(url, stream=True)
        response.raise_for_status()
        
        # Save the image
        with open(filepath, 'wb') as f:
            for chunk in response.iter_content(chunk_size=8192):
                f.write(chunk)
        
        print(f"✓ Downloaded {filename}")
        return True
        
    except Exception as e:
        print(f"✗ Failed to download {filename}: {str(e)}")
        return False

def main():
    """Main function to download all images from the mapping."""
    
    # Load the comprehensive mapping
    mapping_file = "discord_wordpress_mapping.json"
    if not os.path.exists(mapping_file):
        print(f"Error: {mapping_file} not found!")
        sys.exit(1)
    
    with open(mapping_file, 'r') as f:
        mapping_data = json.load(f)
    
    mappings = mapping_data['mappings']
    total_images = len(mappings)
    
    print(f"Found {total_images} images to download")
    print("=" * 50)
    
    # Download each image
    downloaded = 0
    failed = 0
    
    for mapping in mappings:
        url = mapping['image_url']
        filename = mapping['suggested_filename']
        category = mapping['wordpress_mapping']['main_category']
        subcategory = mapping['wordpress_mapping']['subcategory']
        confidence = mapping['wordpress_mapping']['confidence']
        
        print(f"\nMapping: {category}/{subcategory} (confidence: {confidence})")
        print(f"Prompt: {mapping['prompt'][:80]}...")
        
        if download_image(url, filename):
            downloaded += 1
        else:
            failed += 1
    
    print("\n" + "=" * 50)
    print(f"Download Summary:")
    print(f"✓ Successfully downloaded: {downloaded}/{total_images}")
    print(f"✗ Failed downloads: {failed}/{total_images}")
    
    if downloaded > 0:
        print(f"\nImages saved to: ./downloaded_images/")
        print(f"Mapping file: {mapping_file}")

if __name__ == "__main__":
    main()