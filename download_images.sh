#!/bin/bash
# Download Discord images with category-based filenames
# This script reads the comprehensive mapping and downloads all images

echo "Creating download directory..."
mkdir -p downloaded_images

echo "Reading mapping file..."
if [ ! -f "discord_wordpress_mapping.json" ]; then
    echo "Error: discord_wordpress_mapping.json not found!"
    exit 1
fi

# Extract URLs and filenames using Python
python3 -c "
import json
import os

with open('discord_wordpress_mapping.json', 'r') as f:
    data = json.load(f)

with open('download_list.txt', 'w') as f:
    for mapping in data['mappings']:
        f.write(f\"{mapping['image_url']}\t{mapping['suggested_filename']}\n\")

print(f'Generated download list with {len(data[\"mappings\"])} entries')
"

echo "Starting downloads..."
downloaded=0
failed=0
total=$(wc -l < download_list.txt)

while IFS=$'\t' read -r url filename; do
    echo "Downloading: $filename"
    
    if [ -f "downloaded_images/$filename" ]; then
        echo "  Skipping - already exists"
        continue
    fi
    
    if curl -s -L "$url" -o "downloaded_images/$filename"; then
        echo "  ✓ Success"
        ((downloaded++))
    else
        echo "  ✗ Failed"
        ((failed++))
    fi
done < download_list.txt

echo "============================================"
echo "Download Summary:"
echo "✓ Successfully downloaded: $downloaded/$total"
echo "✗ Failed downloads: $failed/$total"
echo ""
echo "Images saved to: ./downloaded_images/"
echo "Mapping file: discord_wordpress_mapping.json"

# Clean up temporary file
rm -f download_list.txt