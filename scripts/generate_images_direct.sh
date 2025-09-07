#!/bin/bash

# Direct image generation for Beratung subservices
# Uses the running MCP server

MCP_DIR="/Users/holgerbrandt/dev/claude-code/tools/midjourney-mcp-server"

echo "🚀 Starting Beratung image generation..."

# Function to call MCP
call_mcp() {
    local prompt="$1"
    local service="$2"
    
    echo "🎨 Generating $service image..."
    
    # Create JSON request
    local request='{
        "jsonrpc": "2.0",
        "id": 1, 
        "method": "tools/call",
        "params": {
            "name": "midjourney_imagine",
            "arguments": {
                "prompt": "'"$prompt"'",
                "waitForCompletion": true
            }
        }
    }'
    
    echo "📤 Request: $request"
    
    # Send to MCP server
    cd "$MCP_DIR"
    echo "$request" | node dist/index.js --stdin
    
    echo "✅ $service generation complete"
    echo "---"
}

# Baumediation
call_mcp "German construction mediation specialist facilitating complex building project dispute resolution in professional conference setting. Expert mediator with conflict resolution documentation, stakeholder analysis materials, and German construction law references. Modern mediation room with round table setup, digital presentation systems, and neutral professional atmosphere. Strategic approach to construction conflict management. MediationCore, ConflictCore, ResolutionCore Camera: Phase One XF, Schneider 80mm lens, professional mediation photography. --s 250 --ar 16:9 --v 7.0 --p 9lhewle" "Baumediation"

# Projektberatung  
call_mcp "German construction project consultant providing strategic advisory services with comprehensive project analysis and risk management. Professional project advisor with multiple monitors displaying project timelines, cost analyses, and stakeholder coordination systems. Executive consulting environment with project documentation, regulatory compliance materials, and German construction standards references. Expert strategic project guidance approach. ConsultingCore, StrategyCore, ProjectCore Camera: Phase One XF, Schneider 80mm lens, executive consulting photography. --s 250 --ar 16:9 --v 7.0 --p 9lhewle" "Projektberatung"

# Gutachten
call_mcp "German construction expert witness preparing comprehensive technical assessments and legal expert opinions. Professional forensic engineer with advanced measurement equipment, building damage analysis tools, and German construction code documentation. Technical assessment laboratory with testing equipment, documentation systems, and legal compliance materials. Authoritative expert witness approach. ExpertiseCore, AnalysisCore, ForensicCore Camera: Phase One XF, Schneider 80mm lens, expert witness photography. --s 250 --ar 16:9 --v 7.0 --p 9lhewle" "Gutachten"

# Schulungen
call_mcp "German construction training specialist delivering professional development programs for construction industry professionals. Expert trainer with interactive training materials, certification programs, and German construction education standards. Modern training facility with presentation systems, hands-on demonstration areas, and digital learning platforms. Educational excellence approach to construction training. TrainingCore, EducationCore, DevelopmentCore Camera: Phase One XF, Schneider 80mm lens, professional training photography. --s 250 --ar 16:9 --v 7.0 --p 9lhewle" "Schulungen"

# Compliance
call_mcp "German construction compliance specialist managing comprehensive regulatory adherence and certification processes. Professional compliance officer with regulatory monitoring systems, certification documentation, and German construction law compliance tools. Executive compliance office with audit preparation materials, regulatory update systems, and legal compliance reporting platforms. Systematic regulatory management approach. ComplianceCore, RegulatoryCore, CertificationCore Camera: Phase One XF, Schneider 80mm lens, regulatory compliance photography. --s 250 --ar 16:9 --v 7.0 --p 9lhewle" "Compliance"

echo "🎉 All Beratung images generation initiated!"