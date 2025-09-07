#!/bin/bash
# WordPress Remote Management via SSH

source "$(dirname "$0")/../.env.local" 2>/dev/null

if [ -z "$KAS_PASSWORD" ]; then
    echo "Error: Please configure .env.local first"
    exit 1
fi

# Function to run remote commands
run_remote() {
    sshpass -p "$KAS_PASSWORD" ssh -o StrictHostKeyChecking=no w0181e1b@w0181e1b.kasserver.com "$@"
}

# Function to run WP-CLI commands
wp_cli() {
    run_remote "cd /www/htdocs/w0181e1b && php wp-cli.phar $*"
}

# Main command handler
case "$1" in
    status)
        echo "📊 WordPress Status:"
        wp_cli core version
        wp_cli plugin list --status=active
        ;;
    
    backup)
        echo "💾 Creating backup..."
        run_remote "cd /www/htdocs/w0181e1b && tar -czf backup-$(date +%Y%m%d-%H%M%S).tar.gz wp-content/"
        echo "✅ Backup created"
        ;;
    
    update)
        echo "🔄 Updating WordPress..."
        wp_cli core update
        wp_cli plugin update --all
        wp_cli theme update --all
        ;;
    
    cache)
        echo "🧹 Clearing cache..."
        wp_cli cache flush
        wp_cli transient delete --all
        ;;
    
    deploy)
        echo "🚀 Deploying files..."
        if [ -d "$2" ]; then
            rsync -avz --delete -e "sshpass -p '$KAS_PASSWORD' ssh -o StrictHostKeyChecking=no" \
                "$2/" w0181e1b@w0181e1b.kasserver.com:/www/htdocs/w0181e1b/wp-content/themes/
        else
            echo "Usage: $0 deploy <local-theme-directory>"
        fi
        ;;
    
    shell)
        echo "🖥️  Opening SSH shell..."
        sshpass -p "$KAS_PASSWORD" ssh -o StrictHostKeyChecking=no w0181e1b@w0181e1b.kasserver.com
        ;;
    
    *)
        echo "WordPress Remote Management"
        echo ""
        echo "Usage: $0 {status|backup|update|cache|deploy|shell}"
        echo ""
        echo "  status  - Show WordPress status"
        echo "  backup  - Create backup of wp-content"
        echo "  update  - Update WordPress core & plugins"
        echo "  cache   - Clear all caches"
        echo "  deploy  - Deploy theme files via rsync"
        echo "  shell   - Open SSH shell"
        ;;
esac