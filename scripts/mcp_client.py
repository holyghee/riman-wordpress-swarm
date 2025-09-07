#!/usr/bin/env python3
"""
Direct MCP Client for Midjourney Integration
Communicates directly with the running MCP server via stdio
"""

import json
import sys
import subprocess
import os

def call_mcp_tool(tool_name, arguments):
    """Call MCP tool using the running server"""
    request = {
        "jsonrpc": "2.0", 
        "id": 1,
        "method": "tools/call",
        "params": {
            "name": tool_name,
            "arguments": arguments
        }
    }
    
    # Print the request for debugging
    print(f"🔄 MCP Request: {json.dumps(request, indent=2)}")
    
    try:
        # Send request via echo and pipe to the MCP server process
        cmd = f"echo '{json.dumps(request)}' | cd /Users/holgerbrandt/dev/claude-code/tools/midjourney-mcp-server && node dist/index.js --stdin"
        result = subprocess.run(cmd, shell=True, capture_output=True, text=True, timeout=120)
        
        print(f"📤 Raw response: {result.stdout}")
        print(f"📤 Errors: {result.stderr}")
        
        if result.returncode != 0:
            print(f"❌ Command failed with code {result.returncode}")
            return None
            
        # Parse JSON response
        lines = result.stdout.strip().split('\n')
        for line in lines:
            if line.startswith('{') and 'result' in line:
                response = json.loads(line)
                return response.get('result')
                
        return None
        
    except Exception as e:
        print(f"❌ Error: {e}")
        return None

def test_midjourney_imagine():
    """Test Midjourney imagine functionality"""
    print("🧪 Testing Midjourney imagine...")
    
    result = call_mcp_tool("midjourney_imagine", {
        "prompt": "test professional consulting environment --ar 16:9",
        "waitForCompletion": True
    })
    
    if result:
        print("✅ Test successful!")
        print(json.dumps(result, indent=2))
    else:
        print("❌ Test failed")
    
    return result

if __name__ == "__main__":
    if len(sys.argv) > 1 and sys.argv[1] == "test":
        test_midjourney_imagine()
    else:
        print("Usage: python3 mcp_client.py test")