#!/usr/bin/env python3
"""
Image Splitter for Midjourney 2x2 Grids
Splits a 2x2 grid image into 4 individual variants for analysis
"""

import os
import sys
from PIL import Image
import argparse

def split_2x2_grid(input_path, output_dir, prefix):
    """
    Split a 2x2 grid image into 4 individual images
    
    Args:
        input_path (str): Path to the 2x2 grid image
        output_dir (str): Directory to save the split images
        prefix (str): Prefix for the output filenames
    """
    try:
        # Open the image
        img = Image.open(input_path)
        width, height = img.size
        
        # Calculate dimensions for each quadrant
        half_width = width // 2
        half_height = height // 2
        
        # Define the quadrants (left, top, right, bottom)
        quadrants = [
            (0, 0, half_width, half_height),  # Top-left
            (half_width, 0, width, half_height),  # Top-right  
            (0, half_height, half_width, height),  # Bottom-left
            (half_width, half_height, width, height)  # Bottom-right
        ]
        
        # Create output directory if it doesn't exist
        os.makedirs(output_dir, exist_ok=True)
        
        # Split and save each quadrant
        variant_paths = []
        for i, (left, top, right, bottom) in enumerate(quadrants, 1):
            # Crop the quadrant
            quadrant = img.crop((left, top, right, bottom))
            
            # Save with descriptive filename
            variant_filename = f"{prefix}_variant_{i}.jpg"
            variant_path = os.path.join(output_dir, variant_filename)
            quadrant.save(variant_path, "JPEG", quality=95)
            variant_paths.append(variant_path)
            
            print(f"✓ Saved variant {i}: {variant_path}")
        
        return variant_paths
        
    except Exception as e:
        print(f"❌ Error splitting image {input_path}: {e}")
        return []

def main():
    parser = argparse.ArgumentParser(description="Split 2x2 grid image into 4 variants")
    parser.add_argument("input_path", help="Path to the 2x2 grid image")
    parser.add_argument("output_dir", help="Output directory for split images")
    parser.add_argument("prefix", help="Prefix for output filenames")
    
    args = parser.parse_args()
    
    if not os.path.exists(args.input_path):
        print(f"❌ Input file not found: {args.input_path}")
        sys.exit(1)
    
    variant_paths = split_2x2_grid(args.input_path, args.output_dir, args.prefix)
    
    if variant_paths:
        print(f"✅ Successfully split image into {len(variant_paths)} variants")
        return variant_paths
    else:
        print("❌ Failed to split image")
        sys.exit(1)

if __name__ == "__main__":
    main()