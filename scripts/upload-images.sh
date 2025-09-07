#!/bin/bash
# Upload WordPress images to All-Inkl in batches

source "$(dirname "$0")/../.env.local"

echo "🖼️  Uploading WordPress Images to All-Inkl"
echo "========================================="
echo ""

REMOTE_PATH="/www/htdocs/w0181e1b/riman-wordpress-swarm.ecomwy.com/wp-content/uploads"
# Use local WordPress uploads as the default source
LOCAL_PATH=${LOCAL_PATH:-"wp-content/uploads"}

# Upload each directory separately
for dir in riman image-server correct-images 2025; do
    if [ -d "$LOCAL_PATH/$dir" ]; then
        echo "📁 Uploading $dir..."
        FILE_COUNT=$(find "$LOCAL_PATH/$dir" -type f | wc -l)
        echo "   Files: $FILE_COUNT"
        
        # Use FTP for upload (more reliable for large transfers)
        tar -czf /tmp/upload-$dir.tar.gz -C "$LOCAL_PATH" "$dir"
        
        # Upload tar file
        sshpass -p "$KAS_PASSWORD" scp -o StrictHostKeyChecking=no \
            /tmp/upload-$dir.tar.gz \
            ssh-w0181e1b@w0181e1b.kasserver.com:/www/htdocs/w0181e1b/riman-wordpress-swarm.ecomwy.com/
        
        # Extract on server
        sshpass -p "$KAS_PASSWORD" ssh -o StrictHostKeyChecking=no ssh-w0181e1b@w0181e1b.kasserver.com \
            "cd /www/htdocs/w0181e1b/riman-wordpress-swarm.ecomwy.com && tar -xzf upload-$dir.tar.gz -C wp-content/uploads/ && rm upload-$dir.tar.gz"
        
        rm /tmp/upload-$dir.tar.gz
        echo "   ✅ $dir uploaded"
        echo ""
    fi
done

echo "✅ All images uploaded!"
