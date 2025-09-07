#!/usr/bin/env python3
"""
RIMAN Schadstoffe Sequential Image Generation
Generates one Schadstoffe image at a time to avoid rate limits
"""

import json
import subprocess
import sys
import time
import shutil
from pathlib import Path
from PIL import Image
from typing import Dict, Any

class SchadstoffeSequentialGenerator:
    def __init__(self):
        self.project_root = Path("/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm")
        self.output_dir = self.project_root / "assets" / "schadstoffe"
        self.output_dir.mkdir(parents=True, exist_ok=True)
        self.mcp_server_path = "/Users/holgerbrandt/dev/claude-code/tools/midjourney-mcp-server"
        
        # Schadstoffe prompts with TRGS compliance
        self.subservices = {
            "asbest": "German asbestos removal specialists in full protective suits conducting systematic asbestos abatement in industrial building. Professional TRGS 519 certified team with P3 respiratory protection, negative pressure containment system, and decontamination chambers. High-tech laboratory analysis equipment for fiber detection, German safety warning signage, and specialized asbestos removal tools. Clinical precision approach to hazardous material elimination. AsbestCore, SafetyCore, ComplianceCore Camera: Phase One XF, Schneider 80mm lens, hazmat documentation photography. --s 250 --ar 16:9 --v 7.0",
            
            "kmf": "German mineral fiber specialists analyzing artificial mineral fiber contamination with advanced microscopy equipment. Professional KMF experts in specialized protective equipment conducting fiber identification, risk assessment, and safe removal planning. Modern analytical laboratory with electron microscopy, sample preparation stations, and German regulatory compliance documentation. Scientific approach to mineral fiber hazard assessment. FiberCore, AnalyticalCore, SafetyCore Camera: Phase One XF, Schneider 80mm lens, scientific analysis photography. --s 250 --ar 16:9 --v 7.0",
            
            "pak": "German PAH contamination specialist conducting comprehensive polycyclic aromatic hydrocarbon analysis and remediation planning. Environmental chemist with advanced analytical equipment processing contaminated building materials, coal tar samples, and soil specimens. High-tech laboratory with GC-MS systems, sample preparation areas, and specialized PAH detection instruments. Precise chemical analysis approach to hydrocarbon contamination. ChemicalCore, AnalyticalCore, TechnicalCore Camera: Phase One XF, Schneider 80mm lens, chemical analysis photography. --s 250 --ar 16:9 --v 7.0",
            
            "pcb": "German PCB contamination experts conducting systematic polychlorinated biphenyl identification and decontamination in industrial facility. Specialized hazmat team with advanced detection equipment, protective suits, and PCB-specific analytical instruments. Professional laboratory setup with chromatography systems, contamination screening devices, and German environmental compliance protocols. Technical expertise in persistent organic pollutant management. PCBCore, DetectionCore, DecontaminationCore Camera: Phase One XF, Schneider 80mm lens, industrial decontamination photography. --s 250 --ar 16:9 --v 7.0",
            
            "schwermetalle": "German heavy metal contamination specialist analyzing lead, mercury, and cadmium contamination with precision analytical equipment. Environmental toxicologist with ICP-MS systems, soil digestion apparatus, and heavy metal extraction equipment. Modern environmental laboratory with atomic absorption spectroscopy, sample preparation clean rooms, and German metal contamination standards documentation. Scientific approach to toxic metal assessment and remediation. MetalCore, ToxicologyCore, AnalyticalCore Camera: Phase One XF, Schneider 80mm lens, toxicology laboratory photography. --s 250 --ar 16:9 --v 7.0"
        }
    
    def send_mcp_request(self, method: str, params: Dict[str, Any]) -> Dict[str, Any]:
        """Send request to MCP server via stdio"""
        request = {
            "jsonrpc": "2.0",
            "id": int(time.time() * 1000),
            "method": method,
            "params": params
        }
        
        try:
            process = subprocess.Popen(
                ["node", "dist/index.js"],
                cwd=self.mcp_server_path,
                stdin=subprocess.PIPE,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True
            )
            
            request_json = json.dumps(request) + "\n"
            stdout, stderr = process.communicate(input=request_json, timeout=300)
            
            if stdout.strip():
                lines = stdout.strip().split('\n')
                for line in lines:
                    try:
                        response = json.loads(line)
                        return response
                    except json.JSONDecodeError:
                        continue
                return {"error": "No valid JSON response"}
            else:
                return {"error": "No response from MCP server"}
                
        except Exception as e:
            return {"error": f"MCP request failed: {str(e)}"}
    
    def generate_single_image(self, service_name: str, prompt: str) -> str:
        """Generate a single image for one subservice"""
        print(f"\n🧪 Generating {service_name.upper()} image...")
        print(f"📝 Prompt: {prompt[:100]}...")
        print(f"⏱️  Waiting 15 seconds to avoid rate limits...")
        time.sleep(15)  # Rate limiting
        
        response = self.send_mcp_request("tools/call", {
            "name": "midjourney_imagine",
            "arguments": {
                "prompt": prompt
            }
        })
        
        if "result" in response and "content" in response["result"]:
            content = response["result"]["content"][0]["text"]
            print(f"📋 Response: {content[:100]}...")
            
            if "Local File:" in content and "✅ Image generated successfully!" in content:
                try:
                    local_file = content.split("Local File: ")[1].split("\n")[0]
                    source_path = Path(local_file)
                    target_path = self.output_dir / f"{service_name}_optimized_trgs_compliant.jpg"
                    
                    if source_path.exists():
                        # Copy and optimize the image
                        img = Image.open(source_path)
                        if img.mode != 'RGB':
                            img = img.convert('RGB')
                        
                        img.save(target_path, 'JPEG', quality=95, optimize=True)
                        print(f"✅ Optimized image saved: {target_path}")
                        return str(target_path)
                    else:
                        print(f"⚠️  Source file not found: {source_path}")
                except Exception as e:
                    print(f"⚠️  Error processing image: {e}")
            else:
                print("❌ Image generation failed or incomplete")
                print(f"📋 Full response: {content}")
        else:
            print(f"❌ Request failed: {response}")
        
        return None
    
    def generate_all_sequential(self):
        """Generate all Schadstoffe images sequentially"""
        print("🚀 RIMAN Schadstoffe Sequential Image Generation")
        print("=" * 50)
        print("Focus: TRGS compliance & German safety standards")
        print(f"Services: {', '.join(self.subservices.keys())}")
        print("=" * 50)
        
        results = {}
        total_start = time.time()
        
        for i, (service_name, prompt) in enumerate(self.subservices.items(), 1):
            print(f"\n[{i}/5] Processing: {service_name.upper()}")
            print("-" * 30)
            
            result_path = self.generate_single_image(service_name, prompt)
            
            if result_path:
                results[service_name] = {
                    "success": True,
                    "image_path": result_path,
                    "prompt": prompt
                }
                print(f"✅ {service_name.upper()} completed successfully")
            else:
                results[service_name] = {
                    "success": False,
                    "error": "Generation failed",
                    "prompt": prompt
                }
                print(f"❌ {service_name.upper()} failed")
        
        total_time = time.time() - total_start
        
        # Generate summary
        successful = sum(1 for r in results.values() if r["success"])
        
        print(f"\n🎯 Generation Complete!")
        print(f"✅ Successful: {successful}/5 subservices")
        print(f"⏱️  Total time: {total_time:.1f}s")
        print(f"📁 Output directory: {self.output_dir}")
        
        # Save summary
        summary = {
            "timestamp": time.strftime('%Y-%m-%d %H:%M:%S'),
            "total_time": total_time,
            "successful": successful,
            "failed": 5 - successful,
            "results": results
        }
        
        summary_path = self.output_dir / "schadstoffe_generation_summary.json"
        with open(summary_path, 'w') as f:
            json.dump(summary, f, indent=2)
        
        print(f"📋 Summary saved: {summary_path}")
        
        return results

def main():
    """Main execution function"""
    if len(sys.argv) > 1:
        service_name = sys.argv[1].lower()
        generator = SchadstoffeSequentialGenerator()
        
        if service_name in generator.subservices:
            prompt = generator.subservices[service_name]
            result = generator.generate_single_image(service_name, prompt)
            if result:
                print(f"\n✅ Successfully generated {service_name} image: {result}")
            else:
                print(f"\n❌ Failed to generate {service_name} image")
        else:
            print(f"❌ Unknown service: {service_name}")
            print(f"Available services: {', '.join(generator.subservices.keys())}")
    else:
        generator = SchadstoffeSequentialGenerator()
        results = generator.generate_all_sequential()

if __name__ == "__main__":
    main()