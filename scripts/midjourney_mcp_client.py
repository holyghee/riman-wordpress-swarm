#!/usr/bin/env python3
"""
Midjourney MCP Client for RIMAN Schadstoffe Images
Direct communication with the MCP Midjourney server
"""

import json
import subprocess
import sys
import time
from pathlib import Path
from typing import Dict, Any

class MidjourneyMCPClient:
    def __init__(self):
        self.mcp_server_path = "/Users/holgerbrandt/dev/claude-code/tools/midjourney-mcp-server"
        
    def send_mcp_request(self, method: str, params: Dict[str, Any]) -> Dict[str, Any]:
        """Send request to MCP server via stdio"""
        request = {
            "jsonrpc": "2.0",
            "id": int(time.time()),
            "method": method,
            "params": params
        }
        
        try:
            # Start the MCP server process
            process = subprocess.Popen(
                ["node", "dist/index.js"],
                cwd=self.mcp_server_path,
                stdin=subprocess.PIPE,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True
            )
            
            # Send the request
            request_json = json.dumps(request) + "\n"
            stdout, stderr = process.communicate(input=request_json, timeout=300)
            
            if stderr:
                print(f"MCP Server stderr: {stderr}", file=sys.stderr)
            
            # Parse response
            if stdout.strip():
                response = json.loads(stdout.strip())
                return response
            else:
                return {"error": "No response from MCP server"}
                
        except subprocess.TimeoutExpired:
            process.kill()
            return {"error": "MCP request timeout"}
        except Exception as e:
            return {"error": f"MCP request failed: {str(e)}"}
    
    def generate_image(self, prompt: str) -> Dict[str, Any]:
        """Generate image using Midjourney via MCP"""
        print(f"🎨 Sending prompt to Midjourney...")
        print(f"📝 Prompt: {prompt[:100]}...")
        
        response = self.send_mcp_request("tools/call", {
            "name": "midjourney_imagine",
            "arguments": {
                "prompt": prompt
            }
        })
        
        return response
    
    def describe_image(self, image_url: str) -> Dict[str, Any]:
        """Analyze image using Midjourney /describe"""
        print(f"🔍 Analyzing image with /describe...")
        
        response = self.send_mcp_request("tools/call", {
            "name": "midjourney_describe",
            "arguments": {
                "image_url": image_url
            }
        })
        
        return response
    
    def test_connection(self) -> bool:
        """Test connection to MCP server"""
        print("🔌 Testing MCP server connection...")
        
        response = self.send_mcp_request("initialize", {
            "protocolVersion": "2024-11-05",
            "capabilities": {
                "tools": {},
                "resources": {}
            },
            "clientInfo": {
                "name": "RIMAN Schadstoffe Generator",
                "version": "1.0.0"
            }
        })
        
        if "error" not in response:
            print("✅ MCP server connection successful")
            return True
        else:
            print(f"❌ MCP server connection failed: {response['error']}")
            return False

def main():
    """Test the MCP client"""
    client = MidjourneyMCPClient()
    
    if client.test_connection():
        # Test with a simple prompt
        test_prompt = "German asbestos removal specialist in protective suit --ar 16:9 --v 7.0"
        result = client.generate_image(test_prompt)
        print(f"📊 Result: {result}")
    
if __name__ == "__main__":
    main()