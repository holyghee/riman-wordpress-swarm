# SWARM: Build Complete RIMAN WordPress Site from Scratch

## 🎯 Mission
Deploy a coordinated swarm of AI agents to create a brand new WordPress installation with the complete RIMAN GmbH website (148 pages) using Docker, matching the PHP version's functionality and Cholot design.

## 📁 Project Setup

### New Project Location
```
/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/
```

### Port Configuration
```
WordPress: http://127.0.0.1:8801
phpMyAdmin: http://127.0.0.1:8802
```

## 🤖 Swarm Agent Configuration

```yaml
swarm:
  name: riman-wordpress-builder
  topology: hierarchical
  coordinator: project-orchestrator
  
agents:
  - project-orchestrator:
      role: Master coordinator and project setup
      priority: critical
      
  - infrastructure-engineer:
      role: Docker and WordPress installation
      priority: high
      
  - wordpress-architect:
      role: Theme and structure planning
      priority: high
      
  - content-migrator:
      role: Import 148 pages with hierarchy
      priority: medium
      
  - design-implementer:
      role: Cholot design system
      priority: medium
      
  - quality-validator:
      role: Testing and validation
      priority: low

execution_strategy: parallel-with-dependencies
max_concurrent: 3
```

## 📋 Phase 1: Infrastructure Setup
**Agent: infrastructure-engineer**

### Create Docker Environment

Create `/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/docker-compose.yml`:
```yaml
version: '3.8'

services:
  db:
    image: mysql:8.0
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: riman_wp
      MYSQL_USER: riman_user
      MYSQL_PASSWORD: riman_pass_2024
      MYSQL_ROOT_PASSWORD: riman_root_2024
    volumes:
      - db_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  wordpress:
    image: wordpress:6.7-php8.2-apache
    restart: unless-stopped
    depends_on:
      db:
        condition: service_healthy
    ports:
      - "127.0.0.1:8801:80"
    environment:
      WORDPRESS_DB_HOST: db:3306
      WORDPRESS_DB_USER: riman_user
      WORDPRESS_DB_PASSWORD: riman_pass_2024
      WORDPRESS_DB_NAME: riman_wp
      WORDPRESS_DEBUG: 1
      WORDPRESS_CONFIG_EXTRA: |
        define('WP_DEFAULT_THEME', 'twentytwentyfive');
        define('WP_MEMORY_LIMIT', '256M');
        define('WP_MAX_MEMORY_LIMIT', '512M');
    volumes:
      - wp_data:/var/www/html
      - ./wp-content:/var/www/html/wp-content
      - ./uploads:/var/www/html/wp-content/uploads

  phpmyadmin:
    image: phpmyadmin:latest
    restart: unless-stopped
    depends_on:
      - db
    ports:
      - "127.0.0.1:8802:80"
    environment:
      PMA_HOST: db
      PMA_USER: riman_user
      PMA_PASSWORD: riman_pass_2024

  wpcli:
    image: wordpress:cli
    depends_on:
      db:
        condition: service_healthy
    user: "33:33"
    environment:
      WORDPRESS_DB_HOST: db:3306
      WORDPRESS_DB_USER: riman_user
      WORDPRESS_DB_PASSWORD: riman_pass_2024
      WORDPRESS_DB_NAME: riman_wp
    volumes:
      - wp_data:/var/www/html
      - ./wp-content:/var/www/html/wp-content

volumes:
  db_data:
  wp_data:
```

### Initialize Project
```bash
# Create project directory
mkdir -p /Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm
cd /Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm

# Create .env file
echo "MYSQL_DATABASE=riman_wp
MYSQL_USER=riman_user  
MYSQL_PASSWORD=riman_pass_2024
MYSQL_ROOT_PASSWORD=riman_root_2024
WORDPRESS_DEBUG=1" > .env

# Start Docker containers
docker-compose up -d

# Wait for WordPress
sleep 30

# Install WordPress
docker-compose run --rm wpcli wp core install \
  --url="http://127.0.0.1:8801" \
  --title="RIMAN GmbH" \
  --admin_user="admin" \
  --admin_password="riman_admin_2024" \
  --admin_email="admin@riman.de"
```

## 📋 Phase 2: Theme Development
**Agent: wordpress-architect + design-implementer**

### Create Twenty Twenty-Five Child Theme

Directory structure:
```
wp-content/themes/riman-cholot/
├── style.css
├── functions.php
├── theme.json
├── patterns/
│   ├── service-card.php
│   ├── service-grid.php
│   ├── navigation-mega.php
│   └── breadcrumb.php
├── templates/
│   ├── page.html
│   ├── page-service.html
│   └── front-page.html
├── parts/
│   ├── header.html
│   └── footer.html
└── assets/
    ├── css/
    │   └── cholot.css
    └── images/
        └── [74 images from image-server]
```

### Cholot Design System CSS
```css
/* RIMAN Cholot Design System */
:root {
    --riman-gold: #b68c2f;
    --riman-blue: #1e4a6d;
    --riman-green: #4a7c59;
}

/* SIGNATURE: Elliptical Service Cards */
.riman-service-card {
    background: white;
    border-radius: 16px;
    overflow: visible;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border: 1px dashed rgba(182, 140, 47, 0.3);
    position: relative;
    transition: transform 0.3s ease;
}

.riman-service-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
}

.riman-card-image {
    height: 280px;
    position: relative;
    overflow: hidden;
    /* CRITICAL: Cholot elliptical shape */
    clip-path: ellipse(100% 85% at 50% 0%);
    -webkit-clip-path: ellipse(100% 85% at 50% 0%);
}

.riman-card-icon {
    width: 80px;
    height: 80px;
    background: var(--riman-gold);
    border-radius: 50%;
    position: absolute;
    top: 240px;
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid white;
    z-index: 10;
}

.riman-play-button {
    width: 50px;
    height: 50px;
    background: rgba(182, 140, 47, 0.9);
    border-radius: 50%;
    position: absolute;
    top: 40%;
    left: 50%;
    transform: translate(-50%, -50%);
}
```

## 📋 Phase 3: Content Migration
**Agent: content-migrator**

### Page Creation Strategy

Total: 148 pages
```javascript
const pageStructure = {
  'homepage': 1,
  'services': {
    'rueckbau': {
      main: 1,
      subs: 6,
      details: 36
    },
    'altlasten': {
      main: 1,
      subs: 5,
      details: 20
    },
    'schadstoffe': {
      main: 1,
      subs: 5,
      details: 20
    },
    'sicherheit': {
      main: 1,
      subs: 5,
      details: 20
    },
    'beratung': {
      main: 1,
      subs: 5,
      details: 20
    }
  }
};
// Total: 1 + 5 + 26 + 116 = 148 pages
```

### Import Script
```php
// wp-content/themes/riman-cholot/import.php
function import_riman_pages() {
    $content_path = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content/services/';
    $image_path = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/image-server/';
    
    // Read markdown files
    // Parse meta descriptions and keywords
    // Create hierarchical pages
    // Add block patterns
    // Set featured images
}
```

## 📋 Phase 4: Navigation Implementation
**Agent: wordpress-architect**

### Navigation Requirements
- Main menu with 5 services
- Mega dropdown showing all sub-services
- Breadcrumb on every page
- Service pages show sub-service cards
- Sub-service pages show detail links

## 📋 Phase 5: Quality Validation
**Agent: quality-validator**

### Validation Checklist
```yaml
pages:
  - total_count: 148
  - homepage: exists
  - main_services: 5
  - sub_services: 26
  - detail_pages: 116
  
design:
  - elliptical_cards: implemented
  - golden_icons: positioned_correctly
  - hover_effects: working
  - responsive: mobile_ready
  
content:
  - german_text: displayed
  - meta_descriptions: in_head_only
  - images: all_74_loaded
  
navigation:
  - breadcrumbs: on_all_pages
  - mega_menu: shows_hierarchy
  - service_cards: link_correctly
```

## 🖼️ Image Assets

Copy all images from:
```bash
cp -r /Users/holgerbrandt/dev/claude-code/projects/riman-content/image-server/* \
      ./wp-content/uploads/riman/
```

Key images:
- nachhaltiger-rueckbau-baustelle-recycling.jpg
- altlastensanierung-grundwasser-bodenschutz.jpg
- schadstoffsanierung-industrieanlage-riman-gmbh.jpg
- sicherheitsvorbereitung-schutzausruestung-schritt-3.jpg
- baumediation-konfliktloesung-projektmanagement.jpg
[+ 69 additional images]

## 🔄 Swarm Execution Commands

```bash
# Phase 1: Setup
swarm init --topology hierarchical --max-agents 6
swarm spawn infrastructure-engineer --task "Setup Docker WordPress"

# Phase 2: Theme
swarm spawn wordpress-architect --task "Create child theme"
swarm spawn design-implementer --task "Implement Cholot CSS"

# Phase 3: Content
swarm spawn content-migrator --task "Import 148 pages"

# Phase 4: Navigation
swarm spawn wordpress-architect --task "Build navigation"

# Phase 5: Validation
swarm spawn quality-validator --task "Test all requirements"

# Monitor progress
swarm status --real-time
```

## ✅ Success Criteria

The WordPress site at http://127.0.0.1:8801 must:
1. Have exactly 148 hierarchical pages
2. Display Cholot design with elliptical service cards
3. Show golden icon badges at 240px position
4. Implement hover effects (translateY -8px)
5. Use German content throughout
6. Have working navigation at all levels
7. Load all 74 images correctly
8. Be mobile responsive
9. Match the PHP version's functionality

## 🚀 Start Swarm Execution

```bash
# Initialize and deploy all agents
cd /Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm
swarm deploy --config swarm-config.yaml --execute-all
```

**The swarm will build a complete WordPress site from scratch with all 148 pages and Cholot design!**