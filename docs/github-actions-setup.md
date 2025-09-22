# GitHub Actions Deployment Setup

## 🔧 Required GitHub Secrets

Gehe zu: **GitHub Repository → Settings → Secrets and variables → Actions**

Erstelle diese Secrets:

### SSH Credentials:
- `SSH_HOST` = `w0181e1b.kasserver.com`
- `SSH_USER` = `ssh-w0181e1b`
- `SSH_PASSWORD` = `Bf3whqqPEh8XdUt6c8dD`
- `REMOTE_PATH` = `/www/htdocs/w0181e1b/riman-wordpress-swarm.ecomwy.com`

## 🚀 Deployment Process

### Automatisch:
- **Bei jedem Push auf `main` Branch** wird automatisch deployed

### Manuell:
1. Gehe zu **GitHub Repository → Actions**
2. Wähle "Deploy to Live Server" Workflow
3. Klicke "Run workflow"

## 📱 Nach dem Deployment:

1. **Plugin aktivieren:**
   - `https://riman-wordpress-swarm.ecomwy.com/wp-admin/plugins.php`
   - Aktiviere "RIMAN Video Compressor"

2. **Video-Komprimierung starten:**
   - `https://riman-wordpress-swarm.ecomwy.com/wp-admin/upload.php?page=riman-video-compressor`
   - Prüfe FFmpeg Status
   - Klicke "Alle Videos Komprimieren"

3. **Mobile Videos testen:**
   - Öffne Website auf mobilem Gerät
   - Prüfe Browser Network Tab für `*-mobile.mp4` Dateien

## ✅ Files die deployed werden:

### Neue Files:
- `wp-content/plugins/riman-video-compressor/` (kompletter Plugin)
- `wp-content/plugins/riman-blocks/assets/hero-responsive-video.js`

### Updated Files:
- `wp-content/plugins/riman-blocks/assets/cover-lazy-video.js`
- `wp-content/plugins/riman-blocks/assets/service-cards-mobile-slider.js`
- `wp-content/plugins/riman-blocks/assets/service-cards-mobile-slider.css`
- `wp-content/plugins/riman-blocks/assets/category-hero-slider.js`
- `wp-content/plugins/riman-blocks/blocks/riman-page-hero.php`
- `wp-content/plugins/riman-blocks/blocks/service-cards.php`
- `wp-content/plugins/riman-blocks/blocks/category-hero-slider.php`
- `wp-content/plugins/riman-blocks/includes/cover-video-lazy.php`