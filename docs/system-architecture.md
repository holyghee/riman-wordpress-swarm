# RIMAN WordPress Swarm - System Architecture

## Architecture Overview

The RIMAN WordPress implementation follows a **layered architecture pattern with microservice components**, designed to handle the complex requirements of a 148-page website with intelligent content-image mapping and sophisticated design system.

## System Context

- **Project Type**: RIMAN GmbH WordPress website with semantic content-image mapping
- **Scale**: 148 hierarchical pages, 230 Midjourney images, professional service showcase
- **Technology Stack**: WordPress 6.7, PHP 8.2, MySQL 8.0, Docker containerization
- **Design System**: Cholot design with elliptical service cards and responsive layouts

## Architecture Layers

### 1. Presentation Layer
**Components**: WordPress Frontend, Cholot Theme, Block Patterns, Navigation System
**Responsibilities**: 
- User interface rendering with Cholot design system
- Elliptical service cards with CSS clip-path implementation
- Responsive navigation with mega menus
- SEO-optimized German content display

**Key Technologies**: WordPress 6.7, PHP 8.2, HTML5, CSS3, JavaScript

### 2. Business Logic Layer  
**Components**: Content Management, Image Processing, SEO Engine, Semantic Mapper
**Responsibilities**:
- Content organization and hierarchy management (148 pages)
- Intelligent semantic mapping of 230 images to content
- SEO optimization with German URL structure
- Service categorization and relationship management

**Key Technologies**: WordPress Core, Custom PHP, MySQL, JSON Processing

### 3. Data Layer
**Components**: MySQL Database, File System Storage, JSON Metadata, Image Repository  
**Responsibilities**:
- WordPress content and configuration storage
- Media library with 230 Midjourney images
- Semantic mapping metadata and relationships
- Hierarchical page structure persistence

**Key Technologies**: MySQL 8.0, Docker Volumes, File System, JSON

### 4. Integration Layer
**Components**: Docker Compose, Volume Mounts, Network Configuration, Port Mapping
**Responsibilities**:
- Container orchestration and service communication
- External access via ports 8801 (WordPress) and 8802 (phpMyAdmin)
- Data persistence and backup management
- Environment isolation and resource management

**Key Technologies**: Docker, Docker Compose, Linux Networking

## Core Components Architecture

### WordPress Core Engine
- **Purpose**: Primary CMS for content management and delivery
- **Interfaces**: HTTP API, Database Connection, File System, Plugin System
- **Configuration**: 6.7 with PHP 8.2, optimized for 148 pages + media

### Semantic Mapping Engine
- **Purpose**: AI-driven intelligent content-image relationship creation
- **Algorithm**: 
  - Theme matching (40% weight): Compare content keywords to image themes
  - Quadrant analysis (60% weight): Analyze detailed image descriptions
  - Confidence scoring: Target >0.8 for high-quality matches
  - Duplicate prevention: Each image used only once
- **Output**: JSON mapping file with WordPress-compatible associations

### Cholot Theme System
- **Purpose**: Custom design implementation with specific visual requirements
- **Key Features**:
  - Elliptical service cards: `clip-path: ellipse(100% 85% at 50% 0%)`
  - Golden icons: 80px circles positioned at 240px from card top
  - Hover effects: `translateY(-8px)` with smooth transitions
  - Color scheme: Gold (#b68c2f), Blue (#1e4a6d), Green (#4a7c59)

### Content Hierarchy Manager
- **Purpose**: Manages 148-page hierarchical structure
- **Structure**:
  - 1 Homepage
  - 5 Main Services (rückbau, altlasten, schadstoffe, sicherheit, beratung)  
  - 26 Sub-services (5-6 per main service)
  - 116 Detail pages (detailed service descriptions)
- **Features**: Automated navigation, breadcrumbs, SEO URLs

## Data Flow Architecture

### Content Ingestion Flow
1. Read markdown files from content repository
2. Parse metadata and extract semantic keywords
3. Create WordPress page hierarchy with parent-child relationships
4. Generate SEO-optimized German URLs
5. Store in WordPress database with proper taxonomies

### Image Processing Flow
1. Load Midjourney database JSON (57 agents, 228 quadrants)
2. Analyze content themes and extract keywords
3. Execute semantic matching algorithm
4. Select optimal image quadrant for each content page
5. Generate WordPress media library entries
6. Create featured image associations

### Design Rendering Flow
1. Load WordPress page request
2. Apply Cholot theme templates and patterns
3. Render elliptical service cards with CSS styling
4. Position golden icons and apply hover effects
5. Generate responsive layouts for all devices
6. Output optimized HTML with navigation

## Scalability and Performance Design

### Horizontal Scaling Strategy
- **Container Orchestration**: Docker Compose for development, Kubernetes for production
- **Load Balancing**: Nginx reverse proxy for multiple WordPress instances
- **Database Scaling**: MySQL master-slave replication
- **CDN Integration**: CloudFront for static asset delivery

### Performance Optimization
- **Caching**: WordPress object caching, page-level caching, browser caching
- **Database**: Indexed queries, optimized table structure, connection pooling  
- **Assets**: Minified CSS/JS, compressed images, lazy loading, critical CSS

### Monitoring and Observability
- **Application**: WordPress performance monitoring with Query Monitor
- **Infrastructure**: Docker container metrics and health checks
- **User Experience**: Core Web Vitals and performance metrics
- **Error Tracking**: PHP error logging and centralized monitoring

## Agent Coordination Architecture

### Swarm Topology
**Pattern**: Hierarchical with specialized agents
**Coordination**: Memory sharing, hook-based communication, dependency management

### Specialized Agents
1. **Infrastructure Agent**: Docker setup, WordPress installation, environment config
2. **Content Migration Agent**: Import 148 pages, create hierarchy, generate SEO URLs
3. **Semantic Mapping Agent**: Analyze images, create content-image relationships
4. **Design Implementation Agent**: Cholot theme, CSS, responsive components
5. **Navigation Builder Agent**: Menus, breadcrumbs, hierarchical navigation
6. **Quality Assurance Agent**: Testing, validation, performance monitoring

### Inter-Agent Communication
- **Shared Memory**: Cross-agent data storage via swarm memory system
- **Event System**: Hook-based notifications for task completion
- **Dependency Management**: Automatic task ordering and resource allocation
- **Conflict Resolution**: Priority-based scheduling and resource sharing

## Deployment Strategy

### Phase 1: Infrastructure Setup
- Initialize Docker containers (WordPress, MySQL, phpMyAdmin, WP-CLI)
- Configure networking and port mapping
- Set up data persistence with volumes

### Phase 2: WordPress Configuration  
- Install WordPress 6.7 with proper configuration
- Deploy Twenty Twenty-Five child theme (riman-cholot)
- Configure permalink structure for SEO

### Phase 3: Content and Media Import
- Execute semantic mapping algorithm
- Import 148 pages with hierarchical structure
- Populate media library with 230 images
- Create content-image associations

### Phase 4: Design System Implementation
- Deploy Cholot CSS framework
- Implement elliptical service cards
- Configure responsive navigation system
- Apply golden icon positioning

### Phase 5: Testing and Optimization
- Validate all 148 pages render correctly
- Test navigation and user experience
- Performance optimization and monitoring setup
- Final quality assurance and launch preparation

## Architecture Decision Records (ADRs)

### ADR-001: Containerized WordPress Architecture
**Decision**: Use Docker Compose with WordPress, MySQL, phpMyAdmin containers
**Rationale**: Consistent deployment, easy scaling, environment isolation
**Status**: Accepted

### ADR-002: Semantic Content-Image Mapping System  
**Decision**: Build semantic analysis engine with theme and quadrant matching
**Rationale**: 230 images require intelligent automated mapping to 148 content pages
**Status**: Accepted

### ADR-003: Hierarchical Content Structure
**Decision**: WordPress parent-child page hierarchy with custom navigation
**Rationale**: Matches existing content structure and provides SEO-friendly URLs
**Status**: Accepted

### ADR-004: Cholot Design System Implementation
**Decision**: Twenty Twenty-Five child theme with custom CSS and block patterns
**Rationale**: Ensures upgradability while maintaining distinctive visual design
**Status**: Accepted

## Technical Specifications Summary

- **WordPress**: 6.7 with PHP 8.2, optimized for large content volume
- **Database**: MySQL 8.0 with proper indexing for hierarchical queries
- **Images**: 230 Midjourney PNG files with semantic metadata
- **Design**: Cholot system with elliptical cards, golden icons, hover effects
- **SEO**: German-optimized URLs, meta descriptions, hierarchical breadcrumbs
- **Performance**: Caching, optimization, monitoring for production deployment

---

*Architecture designed for RIMAN WordPress Swarm by SystemArchitect Agent*
*Stored in swarm memory for implementation agent coordination*