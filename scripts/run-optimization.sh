#!/bin/bash

# Schadstoffe Optimization Execution Script
# Combines setup, generation, and documentation

set -e

echo "🚀 Starting Schadstoffe Image Optimization Process"
echo "=================================================="

# Check if Node.js is available
if ! command -v node &> /dev/null; then
    echo "❌ Node.js not found. Please install Node.js first."
    exit 1
fi

# Check if MCP server is running
echo "🔍 Checking Midjourney MCP server status..."
if pgrep -f "midjourney-mcp-server" > /dev/null; then
    echo "✅ Midjourney MCP server is running"
else
    echo "⚠️  Midjourney MCP server not detected. Starting..."
    # Note: The server should be started manually as shown in background bash
fi

echo ""
echo "📋 Available optimization options:"
echo "1. Run interactive optimization process"
echo "2. View optimization report"
echo "3. View Discord interaction guide"
echo "4. Generate batch results template"
echo ""

read -p "Select option (1-4): " choice

case $choice in
    1)
        echo "🎨 Starting interactive optimization..."
        node scripts/midjourney-generator.js
        ;;
    2)
        echo "📊 Displaying optimization report..."
        if [ -f "docs/schadstoffe-optimization-results.md" ]; then
            cat docs/schadstoffe-optimization-results.md
        else
            echo "❌ Optimization report not found. Run setup first."
            node scripts/schadstoffe-optimization.js
        fi
        ;;
    3)
        echo "📖 Displaying Discord guide..."
        if [ -f "docs/midjourney-discord-guide.md" ]; then
            cat docs/midjourney-discord-guide.md
        else
            echo "❌ Discord guide not found."
        fi
        ;;
    4)
        echo "📝 Generating batch results template..."
        node -e "
        const { SCHADSTOFFE_CONFIG } = require('./scripts/schadstoffe-optimization');
        console.log('# Batch Generation Results Template\n');
        Object.entries(SCHADSTOFFE_CONFIG).forEach(([key, config]) => {
            console.log(\`## \${config.name}\`);
            console.log(\`**Job ID:** [ENTER_JOB_ID]\`);
            console.log(\`**Selected Variant:** [1-4]\`);
            console.log(\`**RIMAN Score:** [1-10]/10\`);
            console.log(\`**Analysis:** [ENTER_ANALYSIS]\`);
            console.log('---\n');
        });
        "
        ;;
    *)
        echo "❌ Invalid option selected."
        exit 1
        ;;
esac

echo ""
echo "✅ Process completed!"
echo ""
echo "📝 Next steps:"
echo "1. Check generated documentation in docs/"
echo "2. Review Discord interaction guide"
echo "3. Execute Midjourney commands in Discord"
echo "4. Analyze and select optimal variants"
echo "5. Document final results"