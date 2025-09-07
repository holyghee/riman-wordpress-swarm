#!/usr/bin/env python3
"""
Beratung Subservice Image Generation Script
Generates, splits, analyzes and optimizes images for all 5 Beratung subservices
"""

import json
import subprocess
import os
import sys
import time
from pathlib import Path

# Prompts from the optimization document
BERATUNG_PROMPTS = {
    "baumediation": "German construction mediation specialist facilitating complex building project dispute resolution in professional conference setting. Expert mediator with conflict resolution documentation, stakeholder analysis materials, and German construction law references. Modern mediation room with round table setup, digital presentation systems, and neutral professional atmosphere. Strategic approach to construction conflict management. MediationCore, ConflictCore, ResolutionCore Camera: Phase One XF, Schneider 80mm lens, professional mediation photography. --s 250 --ar 16:9 --v 7.0 --p 9lhewle",
    
    "projektberatung": "German construction project consultant providing strategic advisory services with comprehensive project analysis and risk management. Professional project advisor with multiple monitors displaying project timelines, cost analyses, and stakeholder coordination systems. Executive consulting environment with project documentation, regulatory compliance materials, and German construction standards references. Expert strategic project guidance approach. ConsultingCore, StrategyCore, ProjectCore Camera: Phase One XF, Schneider 80mm lens, executive consulting photography. --s 250 --ar 16:9 --v 7.0 --p 9lhewle",
    
    "gutachten": "German construction expert witness preparing comprehensive technical assessments and legal expert opinions. Professional forensic engineer with advanced measurement equipment, building damage analysis tools, and German construction code documentation. Technical assessment laboratory with testing equipment, documentation systems, and legal compliance materials. Authoritative expert witness approach. ExpertiseCore, AnalysisCore, ForensicCore Camera: Phase One XF, Schneider 80mm lens, expert witness photography. --s 250 --ar 16:9 --v 7.0 --p 9lhewle",
    
    "schulungen": "German construction training specialist delivering professional development programs for construction industry professionals. Expert trainer with interactive training materials, certification programs, and German construction education standards. Modern training facility with presentation systems, hands-on demonstration areas, and digital learning platforms. Educational excellence approach to construction training. TrainingCore, EducationCore, DevelopmentCore Camera: Phase One XF, Schneider 80mm lens, professional training photography. --s 250 --ar 16:9 --v 7.0 --p 9lhewle",
    
    "compliance": "German construction compliance specialist managing comprehensive regulatory adherence and certification processes. Professional compliance officer with regulatory monitoring systems, certification documentation, and German construction law compliance tools. Executive compliance office with audit preparation materials, regulatory update systems, and legal compliance reporting platforms. Systematic regulatory management approach. ComplianceCore, RegulatoryCore, CertificationCore Camera: Phase One XF, Schneider 80mm lens, regulatory compliance photography. --s 250 --ar 16:9 --v 7.0 --p 9lhewle"
}

def call_midjourney_mcp(method, params):
    """Call Midjourney MCP server with JSON-RPC"""
    mcp_request = {
        "jsonrpc": "2.0",
        "id": 1,
        "method": method,
        "params": params
    }
    
    mcp_server_path = "/Users/holgerbrandt/dev/claude-code/tools/midjourney-mcp-server"
    
    try:
        result = subprocess.run(
            ["node", "dist/index.js", "--stdin"],
            input=json.dumps(mcp_request),
            cwd=mcp_server_path,
            capture_output=True,
            text=True,
            timeout=300  # 5 minute timeout
        )
        
        if result.returncode != 0:
            print(f"❌ MCP call failed: {result.stderr}")
            return None
            
        response = json.loads(result.stdout)
        return response.get("result")
        
    except Exception as e:
        print(f"❌ Error calling MCP: {e}")
        return None

def generate_image(service_name, prompt):
    """Generate a 2x2 grid image using Midjourney"""
    print(f"🎨 Generating {service_name} image with Midjourney...")
    
    result = call_midjourney_mcp("tools/call", {
        "name": "midjourney_imagine",
        "arguments": {
            "prompt": prompt,
            "waitForCompletion": True
        }
    })
    
    if not result:
        print(f"❌ Failed to generate {service_name} image")
        return None
        
    print(f"✓ Generated {service_name} image")
    return result

def main():
    print("🚀 Starting Beratung subservice image generation...")
    
    # Ensure directories exist
    images_dir = Path("images/beratung")
    images_dir.mkdir(parents=True, exist_ok=True)
    
    results = {}
    
    for service_name, prompt in BERATUNG_PROMPTS.items():
        print(f"\n📸 Processing {service_name.upper()}...")
        
        # Generate the image
        result = generate_image(service_name, prompt)
        if result:
            results[service_name] = result
            print(f"✅ {service_name} image generated successfully")
        else:
            print(f"❌ Failed to generate {service_name} image")
            
        # Wait between generations to avoid rate limits
        time.sleep(5)
    
    # Save results for later processing
    results_file = images_dir / "generation_results.json"
    with open(results_file, 'w') as f:
        json.dump(results, f, indent=2)
    
    print(f"\n✅ Image generation complete. Results saved to {results_file}")
    return results

if __name__ == "__main__":
    main()