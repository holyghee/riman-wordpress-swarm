/**
 * SEO-Optimized German URL Slug Generator
 * Handles German umlauts, ß conversion, hierarchical URLs, and WordPress metadata
 */

class SEOSlugGenerator {
    constructor() {
        this.usedSlugs = new Set();
        this.germanCharMap = {
            'ä': 'ae', 'Ä': 'ae',
            'ö': 'oe', 'Ö': 'oe', 
            'ü': 'ue', 'Ü': 'ue',
            'ß': 'ss'
        };
        
        this.germanStopWords = [
            'und', 'oder', 'der', 'die', 'das', 'den', 'dem', 'des',
            'ein', 'eine', 'einer', 'einem', 'eines', 'von', 'zu', 'mit',
            'auf', 'für', 'an', 'in', 'bei', 'über', 'unter', 'durch',
            'gegen', 'ohne', 'um', 'nach', 'vor', 'zwischen', 'während'
        ];
        
        this.localKeywords = [
            'hamburg', 'norddeutschland', 'schleswig-holstein', 'niedersachsen',
            'bremen', 'mecklenburg-vorpommern', 'nord-deutschland'
        ];
        
        this.industryKeywords = [
            'umwelt', 'sanierung', 'entsorgung', 'recycling', 'abfall',
            'schadstoff', 'altlast', 'bodensanierung', 'gewaesserschutz',
            'umweltschutz', 'nachhaltig', 'oeko'
        ];
        
        this.categoryHierarchy = {
            'umweltsanierung': {
                'bodensanierung': ['altlasten', 'kontamination', 'sanierungsverfahren'],
                'gewaessersanierung': ['grundwasser', 'oberflaechenwasser', 'abwasserbehandlung'],
                'luftreinhaltung': ['emissionsmessung', 'filteranlagen', 'abgasreinigung']
            },
            'abfallentsorgung': {
                'gefahrstoff': ['sonderabfall', 'chemikalien', 'asbest'],
                'recycling': ['wertstoff', 'kreislaufwirtschaft', 'wiederverwertung'],
                'deponierung': ['muelldeponien', 'ablagerung', 'deponiebau']
            },
            'beratung': {
                'umweltrecht': ['genehmigungsverfahren', 'compliance', 'gutachten'],
                'projektmanagement': ['sanierungsplanung', 'baubegleitung', 'qualitaetssicherung'],
                'schulung': ['mitarbeiterschulung', 'sicherheitstraining', 'weiterbildung']
            }
        };
    }
    
    /**
     * Convert German characters according to SEO rules
     */
    convertGermanChars(text) {
        let converted = text;
        for (const [german, replacement] of Object.entries(this.germanCharMap)) {
            converted = converted.replace(new RegExp(german, 'g'), replacement);
        }
        return converted;
    }
    
    /**
     * Remove German stop words while preserving industry keywords
     */
    removeStopWords(words) {
        return words.filter(word => {
            const lowercaseWord = word.toLowerCase();
            // Keep industry and local keywords even if they're in stop words
            if (this.industryKeywords.includes(lowercaseWord) || 
                this.localKeywords.includes(lowercaseWord)) {
                return true;
            }
            return !this.germanStopWords.includes(lowercaseWord);
        });
    }
    
    /**
     * Optimize keyword placement for SEO
     */
    optimizeKeywordPlacement(words, targetKeywords = []) {
        const priorityWords = [];
        const regularWords = [];
        
        words.forEach(word => {
            const lowercaseWord = word.toLowerCase();
            if (targetKeywords.includes(lowercaseWord) || 
                this.industryKeywords.includes(lowercaseWord) ||
                this.localKeywords.includes(lowercaseWord)) {
                priorityWords.push(word);
            } else {
                regularWords.push(word);
            }
        });
        
        // Place priority keywords at the beginning
        return [...priorityWords, ...regularWords];
    }
    
    /**
     * Generate base slug from title
     */
    generateBaseSlug(title, targetKeywords = []) {
        // Convert German characters
        let slug = this.convertGermanChars(title);
        
        // Convert to lowercase and replace spaces/special chars with hyphens
        slug = slug.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
        
        // Split into words and process
        let words = slug.split('-').filter(word => word.length > 0);
        
        // Remove stop words
        words = this.removeStopWords(words);
        
        // Optimize keyword placement
        words = this.optimizeKeywordPlacement(words, targetKeywords);
        
        // Rejoin and ensure max 60 characters
        slug = words.join('-');
        if (slug.length > 60) {
            // Truncate at word boundary
            const truncated = slug.substring(0, 57);
            const lastHyphen = truncated.lastIndexOf('-');
            slug = lastHyphen > 30 ? truncated.substring(0, lastHyphen) : truncated;
        }
        
        return slug.replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
    }
    
    /**
     * Generate hierarchical URL structure
     */
    generateHierarchicalURL(category, subcategory, articleTitle, targetKeywords = []) {
        const categorySlug = this.generateBaseSlug(category, targetKeywords);
        const subcategorySlug = subcategory ? this.generateBaseSlug(subcategory, targetKeywords) : null;
        const articleSlug = this.generateBaseSlug(articleTitle, targetKeywords);
        
        let hierarchicalPath;
        if (subcategorySlug) {
            hierarchicalPath = `/${categorySlug}/${subcategorySlug}/${articleSlug}`;
        } else {
            hierarchicalPath = `/${categorySlug}/${articleSlug}`;
        }
        
        return hierarchicalPath;
    }
    
    /**
     * Ensure slug uniqueness
     */
    ensureUniqueness(baseSlug) {
        let uniqueSlug = baseSlug;
        let counter = 1;
        
        while (this.usedSlugs.has(uniqueSlug)) {
            uniqueSlug = `${baseSlug}-${counter}`;
            counter++;
        }
        
        this.usedSlugs.add(uniqueSlug);
        return uniqueSlug;
    }
    
    /**
     * Generate WordPress meta description
     */
    generateMetaDescription(title, content, targetKeywords = []) {
        let description = '';
        
        // Start with title if it's short enough
        if (title.length <= 100) {
            description = title;
        }
        
        // Extract first meaningful sentence from content
        if (content) {
            const sentences = content.split(/[.!?]+/).filter(s => s.trim().length > 20);
            if (sentences.length > 0) {
                let firstSentence = sentences[0].trim();
                
                // If we already have title, append content
                if (description) {
                    const remaining = 157 - description.length; // Leave space for " - "
                    if (remaining > 30) {
                        firstSentence = firstSentence.substring(0, remaining - 3);
                        description += ` - ${firstSentence}`;
                    }
                } else {
                    description = firstSentence.substring(0, 157);
                }
            }
        }
        
        // Ensure we include target keywords if possible
        if (targetKeywords.length > 0 && description.length < 140) {
            const keywordsStr = targetKeywords.slice(0, 2).join(', ');
            const remaining = 160 - description.length;
            if (keywordsStr.length < remaining - 3) {
                description += ` | ${keywordsStr}`;
            }
        }
        
        // Ensure max 160 characters
        if (description.length > 160) {
            description = description.substring(0, 157) + '...';
        }
        
        return description;
    }
    
    /**
     * Generate WordPress-ready metadata
     */
    generateWordPressMetadata(data) {
        const {
            title,
            content = '',
            category,
            subcategory = null,
            targetKeywords = [],
            author = 'RIMAN GmbH',
            publishDate = new Date().toISOString()
        } = data;
        
        // Generate hierarchical URL
        const hierarchicalURL = this.generateHierarchicalURL(category, subcategory, title, targetKeywords);
        
        // Generate unique slug
        const baseSlug = hierarchicalURL.split('/').pop();
        const uniqueSlug = this.ensureUniqueness(baseSlug);
        const finalURL = hierarchicalURL.replace(baseSlug, uniqueSlug);
        
        // Generate meta description
        const metaDescription = this.generateMetaDescription(title, content, targetKeywords);
        
        // Generate focus keywords
        const focusKeywords = [
            ...targetKeywords,
            ...this.industryKeywords.filter(kw => 
                title.toLowerCase().includes(kw) || content.toLowerCase().includes(kw)
            ),
            ...this.localKeywords.filter(kw => 
                title.toLowerCase().includes(kw) || content.toLowerCase().includes(kw)
            )
        ].slice(0, 5);
        
        return {
            wordpress: {
                post_title: title,
                post_name: uniqueSlug,
                post_content: content,
                post_status: 'draft',
                post_author: author,
                post_date: publishDate,
                post_category: [category, subcategory].filter(Boolean),
                meta: {
                    _yoast_wpseo_title: title.length > 60 ? title.substring(0, 57) + '...' : title,
                    _yoast_wpseo_metadesc: metaDescription,
                    _yoast_wpseo_focuskw: focusKeywords[0] || '',
                    _yoast_wpseo_canonical: finalURL,
                    _yoast_wpseo_opengraph_title: title,
                    _yoast_wpseo_opengraph_description: metaDescription,
                    _yoast_wpseo_twitter_title: title,
                    _yoast_wpseo_twitter_description: metaDescription
                }
            },
            seo: {
                slug: uniqueSlug,
                hierarchicalURL: finalURL,
                metaDescription: metaDescription,
                focusKeywords: focusKeywords,
                targetKeywords: targetKeywords,
                localOptimization: this.localKeywords.filter(kw => 
                    finalURL.includes(kw) || metaDescription.toLowerCase().includes(kw)
                ),
                industryOptimization: this.industryKeywords.filter(kw => 
                    finalURL.includes(kw) || metaDescription.toLowerCase().includes(kw)
                ),
                urlLength: finalURL.length,
                descriptionLength: metaDescription.length,
                keywordDensity: this.calculateKeywordDensity(content, focusKeywords[0] || ''),
                readabilityScore: this.calculateReadabilityScore(content)
            },
            validation: {
                isUnique: !this.usedSlugs.has(uniqueSlug),
                followsGermanRules: this.validateGermanRules(uniqueSlug),
                seoOptimized: this.validateSEOOptimization(finalURL, metaDescription, focusKeywords),
                wordPressCompatible: this.validateWordPressCompatibility(uniqueSlug)
            }
        };
    }
    
    /**
     * Calculate keyword density
     */
    calculateKeywordDensity(content, keyword) {
        if (!content || !keyword) return 0;
        
        const words = content.toLowerCase().split(/\s+/);
        const keywordCount = words.filter(word => word.includes(keyword.toLowerCase())).length;
        return (keywordCount / words.length * 100).toFixed(2);
    }
    
    /**
     * Calculate basic readability score (Flesch-Kincaid adapted for German)
     */
    calculateReadabilityScore(content) {
        if (!content) return 0;
        
        const sentences = content.split(/[.!?]+/).length;
        const words = content.split(/\s+/).length;
        const syllables = this.countGermanSyllables(content);
        
        // Adapted Flesch-Kincaid for German
        const score = 180 - (words / sentences) - (syllables / words * 58.5);
        return Math.max(0, Math.min(100, score)).toFixed(1);
    }
    
    /**
     * Count syllables in German text (approximation)
     */
    countGermanSyllables(text) {
        return text.toLowerCase()
            .replace(/[^a-zäöüß]/g, '')
            .replace(/[aeiouäöü]{2,}/g, 'a')
            .match(/[aeiouäöü]/g)?.length || 0;
    }
    
    /**
     * Validate German character conversion rules
     */
    validateGermanRules(slug) {
        const hasUmlauts = /[äöüÄÖÜß]/.test(slug);
        const hasCorrectConversion = slug.includes('ae') || slug.includes('oe') || 
                                   slug.includes('ue') || slug.includes('ss');
        
        return {
            noUmlautsRemaining: !hasUmlauts,
            hasCorrectConversion: hasCorrectConversion,
            followsConventions: /^[a-z0-9-]+$/.test(slug)
        };
    }
    
    /**
     * Validate SEO optimization
     */
    validateSEOOptimization(url, description, keywords) {
        return {
            urlOptimal: url.length <= 100 && url.length >= 10,
            descriptionOptimal: description.length >= 120 && description.length <= 160,
            keywordsInUrl: keywords.some(kw => url.toLowerCase().includes(kw.toLowerCase())),
            keywordsInDescription: keywords.some(kw => description.toLowerCase().includes(kw.toLowerCase())),
            noStopWordsInUrl: !this.germanStopWords.some(sw => url.includes(`-${sw}-`) || url.includes(`/${sw}-`) || url.includes(`-${sw}/`))
        };
    }
    
    /**
     * Validate WordPress compatibility
     */
    validateWordPressCompatibility(slug) {
        return {
            validFormat: /^[a-z0-9-]+$/.test(slug),
            noConsecutiveHyphens: !slug.includes('--'),
            noLeadingTrailingHyphens: !slug.startsWith('-') && !slug.endsWith('-'),
            appropriateLength: slug.length >= 3 && slug.length <= 60
        };
    }
    
    /**
     * Batch process multiple articles
     */
    batchGenerate(articles) {
        return articles.map(article => this.generateWordPressMetadata(article));
    }
    
    /**
     * Export current state for coordination
     */
    exportState() {
        return {
            usedSlugs: Array.from(this.usedSlugs),
            stats: {
                totalSlugsGenerated: this.usedSlugs.size,
                categoryHierarchyDepth: Object.keys(this.categoryHierarchy).length,
                stopWordsCount: this.germanStopWords.length,
                industryKeywordsCount: this.industryKeywords.length,
                localKeywordsCount: this.localKeywords.length
            }
        };
    }
    
    /**
     * Import state from coordination
     */
    importState(state) {
        if (state.usedSlugs) {
            this.usedSlugs = new Set(state.usedSlugs);
        }
    }
}

module.exports = SEOSlugGenerator;