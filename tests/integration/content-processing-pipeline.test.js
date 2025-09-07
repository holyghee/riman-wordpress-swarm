/**
 * Integration Tests for Content Processing Pipeline
 * Quality Engineer - End-to-End Content Processing Testing
 */

const fs = require('fs');
const path = require('path');

describe('Content Processing Pipeline Integration', () => {
  let contentProcessor;
  let testContentFiles;
  let mockImageDatabase;

  beforeAll(async () => {
    // Mock content processing pipeline
    contentProcessor = {
      processContentDirectory: async (contentDir, imageDatabase, options = {}) => {
        const results = {
          processed: 0,
          failed: 0,
          mappings: [],
          statistics: {
            total_contents: 0,
            unique_agents_used: 0,
            average_confidence: 0,
            unmapped_contents: []
          }
        };

        const contentFiles = await this.findContentFiles(contentDir);
        results.statistics.total_contents = contentFiles.length;

        for (const contentFile of contentFiles) {
          try {
            const content = await this.parseMarkdownContent(contentFile);
            const imageMatch = await this.findOptimalImageMatch(content, imageDatabase);
            const seoSlug = this.generateSEOSlug(content.title, content.category);

            if (imageMatch && imageMatch.confidence_score >= (options.minConfidence || 0.7)) {
              const mapping = {
                content_file: contentFile,
                title: content.title,
                category: content.category,
                subcategory: content.subcategory,
                seo_slug: seoSlug,
                assigned_image: imageMatch,
                meta: {
                  description: content.excerpt || content.title,
                  keywords: content.keywords || []
                }
              };

              results.mappings.push(mapping);
              results.processed++;
            } else {
              results.statistics.unmapped_contents.push(contentFile);
              results.failed++;
            }
          } catch (error) {
            results.failed++;
            results.statistics.unmapped_contents.push(contentFile);
          }
        }

        // Calculate statistics
        const confidenceScores = results.mappings.map(m => m.assigned_image.confidence_score);
        results.statistics.average_confidence = confidenceScores.length > 0 
          ? confidenceScores.reduce((a, b) => a + b) / confidenceScores.length 
          : 0;
        
        const uniqueAgents = new Set(results.mappings.map(m => m.assigned_image.agent_id));
        results.statistics.unique_agents_used = uniqueAgents.size;

        return results;
      },

      findContentFiles: async (contentDir) => {
        // Mock file discovery
        return testContentFiles;
      },

      parseMarkdownContent: async (filePath) => {
        // Mock markdown parsing
        const filename = path.basename(filePath, '.md');
        const mockContents = {
          'asbestsanierung-trgs-519.md': {
            title: 'Asbestsanierung nach TRGS 519',
            category: 'schadstoffe',
            subcategory: 'asbest',
            keywords: ['asbest', 'sanierung', 'trgs', 'sicherheit'],
            excerpt: 'Professional asbestos removal following German safety standards'
          },
          'sigeko-planung.md': {
            title: 'SiGeKo-Planung für Bauprojekte',
            category: 'sicherheit',
            subcategory: 'sigeko-planung',
            keywords: ['sigeko', 'sicherheit', 'koordination', 'baustelle'],
            excerpt: 'Safety coordination planning for construction projects'
          },
          'altlastenuntersuchung.md': {
            title: 'Altlastenuntersuchung und Bewertung',
            category: 'altlasten',
            subcategory: 'erkundung',
            keywords: ['altlasten', 'untersuchung', 'bodenschutz', 'umwelt'],
            excerpt: 'Comprehensive contaminated site investigation services'
          }
        };

        return mockContents[filename] || {
          title: filename.replace('-', ' '),
          category: 'general',
          subcategory: '',
          keywords: [],
          excerpt: ''
        };
      },

      findOptimalImageMatch: async (content, imageDatabase) => {
        // Mock semantic matching logic
        const agents = imageDatabase.agents || [];
        const matches = [];

        agents.forEach(agent => {
          let themeScore = 0;
          let quadrantScore = 0;

          // Calculate theme match
          if (agent.theme && content.keywords) {
            const themeWords = agent.theme.toLowerCase().split(/\s+/);
            const keywordMatches = content.keywords.filter(keyword =>
              themeWords.some(word => word.includes(keyword.toLowerCase()))
            ).length;
            themeScore = Math.min(keywordMatches / content.keywords.length, 1.0);
          }

          // Calculate quadrant description match
          if (agent.quadrants && content.excerpt) {
            const allDescriptions = Object.values(agent.quadrants).join(' ').toLowerCase();
            const contextWords = content.excerpt.toLowerCase().split(/\s+/);
            const contextMatches = contextWords.filter(word =>
              allDescriptions.includes(word)
            ).length;
            quadrantScore = Math.min(contextMatches / contextWords.length, 1.0);
          }

          const totalScore = (themeScore * 0.4) + (quadrantScore * 0.6);
          
          matches.push({
            agent_id: agent.id,
            theme: agent.theme,
            selected_quadrant: 'top_left', // Simplified for testing
            filename: `agent_${agent.id}_top_left.png`,
            path: `./images/agent_${agent.id}_top_left.png`,
            theme_match: content.keywords.join(', '),
            description_match: content.excerpt,
            confidence_score: totalScore,
            matching_details: {
              theme_score: themeScore,
              description_score: quadrantScore,
              total_score: totalScore
            }
          });
        });

        return matches.sort((a, b) => b.confidence_score - a.confidence_score)[0];
      },

      generateSEOSlug: (title, category) => {
        let slug = title.toLowerCase()
          .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
          .replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-')
          .replace(/^-+|-+$/g, '');

        if (category) {
          const categorySlug = category.toLowerCase().replace(/[^a-z0-9-]/g, '-');
          slug = `${categorySlug}-${slug}`;
        }

        return slug.length > 60 ? slug.substring(0, 60).replace(/-[^-]*$/, '') : slug;
      },

      validateMappings: async (mappings) => {
        const validation = {
          valid: 0,
          invalid: 0,
          issues: []
        };

        for (const mapping of mappings) {
          const issues = [];

          // Validate SEO slug
          if (!/^[a-z0-9-]+$/.test(mapping.seo_slug)) {
            issues.push('Invalid SEO slug format');
          }

          // Validate confidence score
          if (mapping.assigned_image.confidence_score < 0.5) {
            issues.push('Low confidence score');
          }

          // Validate required fields
          if (!mapping.title || !mapping.category) {
            issues.push('Missing required fields');
          }

          if (issues.length === 0) {
            validation.valid++;
          } else {
            validation.invalid++;
            validation.issues.push({
              content_file: mapping.content_file,
              issues
            });
          }
        }

        return validation;
      }
    };

    // Setup test data
    testContentFiles = [
      '/test/content/asbestsanierung-trgs-519.md',
      '/test/content/sigeko-planung.md',
      '/test/content/altlastenuntersuchung.md',
      '/test/content/invalid-content.md'
    ];

    mockImageDatabase = {
      agents: [
        {
          id: 1,
          theme: 'Professional asbestos removal with safety equipment and protective gear',
          quadrants: {
            top_left: 'Workers in protective suits handling hazardous asbestos materials',
            top_right: 'Safety equipment and warning signs for asbestos work',
            bottom_left: 'Asbestos removal process documentation and testing',
            bottom_right: 'Clean work environment after asbestos remediation'
          },
          image_paths: {
            top_left: './images/agent_1_top_left.png',
            top_right: './images/agent_1_top_right.png',
            bottom_left: './images/agent_1_bottom_left.png',
            bottom_right: './images/agent_1_bottom_right.png'
          }
        },
        {
          id: 2,
          theme: 'Construction safety coordination and site management planning',
          quadrants: {
            top_left: 'Safety coordinator reviewing construction site plans',
            top_right: 'Construction workers following safety protocols',
            bottom_left: 'Safety equipment and coordination documentation',
            bottom_right: 'Site safety meeting and coordination activities'
          },
          image_paths: {
            top_left: './images/agent_2_top_left.png',
            top_right: './images/agent_2_top_right.png',
            bottom_left: './images/agent_2_bottom_left.png',
            bottom_right: './images/agent_2_bottom_right.png'
          }
        },
        {
          id: 3,
          theme: 'Environmental contamination assessment and soil investigation',
          quadrants: {
            top_left: 'Environmental scientists collecting soil samples',
            top_right: 'Laboratory analysis of contaminated materials',
            bottom_left: 'Site investigation equipment and testing tools',
            bottom_right: 'Environmental assessment documentation and reporting'
          },
          image_paths: {
            top_left: './images/agent_3_top_left.png',
            top_right: './images/agent_3_top_right.png',
            bottom_left: './images/agent_3_bottom_left.png',
            bottom_right: './images/agent_3_bottom_right.png'
          }
        }
      ]
    };
  });

  describe('Full Content Processing Pipeline', () => {
    test('should process content directory and generate mappings', async () => {
      const result = await contentProcessor.processContentDirectory(
        '/test/content',
        mockImageDatabase,
        { minConfidence: 0.6 }
      );

      expect(result.processed).toBeGreaterThan(0);
      expect(result.mappings).toHaveLength(result.processed);
      expect(result.statistics.total_contents).toBe(testContentFiles.length);
      expect(result.statistics.average_confidence).toBeGreaterThan(0.6);
    });

    test('should handle high-quality content matching', async () => {
      const result = await contentProcessor.processContentDirectory(
        '/test/content',
        mockImageDatabase,
        { minConfidence: 0.8 }
      );

      // All mappings should have high confidence
      result.mappings.forEach(mapping => {
        expect(mapping.assigned_image.confidence_score).toBeGreaterThanOrEqual(0.8);
        expect(mapping.seo_slug).toBeValidSEOSlug();
      });
    });

    test('should generate unique SEO slugs', async () => {
      const result = await contentProcessor.processContentDirectory(
        '/test/content',
        mockImageDatabase
      );

      const slugs = result.mappings.map(m => m.seo_slug);
      const uniqueSlugs = new Set(slugs);
      
      expect(slugs.length).toBe(uniqueSlugs.size);
    });
  });

  describe('Content Parsing Integration', () => {
    test('should extract content metadata correctly', async () => {
      const content = await contentProcessor.parseMarkdownContent('/test/content/asbestsanierung-trgs-519.md');

      expect(content).toHaveProperty('title');
      expect(content).toHaveProperty('category');
      expect(content).toHaveProperty('keywords');
      expect(content.keywords).toBeInstanceOf(Array);
      expect(content.keywords.length).toBeGreaterThan(0);
    });

    test('should handle markdown with missing metadata', async () => {
      const content = await contentProcessor.parseMarkdownContent('/test/content/unknown-file.md');

      expect(content).toHaveProperty('title');
      expect(content).toHaveProperty('category', 'general');
      expect(content.keywords).toBeInstanceOf(Array);
    });
  });

  describe('Semantic Image Matching Integration', () => {
    test('should match content to appropriate images', async () => {
      const asbestContent = await contentProcessor.parseMarkdownContent('/test/content/asbestsanierung-trgs-519.md');
      const match = await contentProcessor.findOptimalImageMatch(asbestContent, mockImageDatabase);

      expect(match.agent_id).toBe(1); // Should match asbestos agent
      expect(match.confidence_score).toBeGreaterThan(0.7);
      expect(match).toHaveProperty('matching_details');
    });

    test('should handle content with no good matches', async () => {
      const poorContent = {
        title: 'Cooking Recipes',
        keywords: ['cooking', 'recipes', 'food'],
        excerpt: 'How to cook delicious meals'
      };
      
      const match = await contentProcessor.findOptimalImageMatch(poorContent, mockImageDatabase);
      
      expect(match.confidence_score).toBeLessThan(0.3);
    });
  });

  describe('SEO Slug Generation Integration', () => {
    test('should generate German SEO-optimized slugs', async () => {
      const testCases = [
        { title: 'Asbestsanierung nach TRGS 519', category: 'schadstoffe', expected: 'schadstoffe-asbestsanierung-nach-trgs-519' },
        { title: 'Rückbau und Entsorgung', category: 'rueckbau', expected: 'rueckbau-rueckbau-und-entsorgung' },
        { title: 'SiGeKo-Planung', category: 'sicherheit', expected: 'sicherheit-sigeko-planung' }
      ];

      testCases.forEach(testCase => {
        const slug = contentProcessor.generateSEOSlug(testCase.title, testCase.category);
        expect(slug).toBeValidSEOSlug();
        expect(slug).toContain(testCase.category.toLowerCase());
      });
    });

    test('should handle long titles correctly', async () => {
      const longTitle = 'Umfassende Asbestsanierung nach TRGS 519 mit vollständiger Dokumentation und Qualitätskontrolle';
      const slug = contentProcessor.generateSEOSlug(longTitle, 'schadstoffe');

      expect(slug.length).toBeLessThanOrEqual(60);
      expect(slug).toBeValidSEOSlug();
      expect(slug).toMatch(/^schadstoffe/);
    });
  });

  describe('Mapping Validation Integration', () => {
    test('should validate generated mappings', async () => {
      const result = await contentProcessor.processContentDirectory('/test/content', mockImageDatabase);
      const validation = await contentProcessor.validateMappings(result.mappings);

      expect(validation.valid).toBeGreaterThan(0);
      expect(validation.invalid + validation.valid).toBe(result.mappings.length);
    });

    test('should identify mapping issues', async () => {
      const invalidMappings = [
        {
          content_file: '/test/invalid.md',
          title: '',
          category: '',
          seo_slug: 'Invalid-Slug-With-Capitals',
          assigned_image: { confidence_score: 0.3 }
        }
      ];

      const validation = await contentProcessor.validateMappings(invalidMappings);

      expect(validation.invalid).toBe(1);
      expect(validation.issues).toHaveLength(1);
      expect(validation.issues[0].issues).toContain('Invalid SEO slug format');
    });
  });

  describe('Performance Integration', () => {
    test('should process large content directories efficiently', async () => {
      // Mock large content directory
      const largeContentFiles = Array.from({ length: 100 }, (_, i) => `/test/content/article-${i}.md`);
      contentProcessor.findContentFiles = jest.fn().mockResolvedValue(largeContentFiles);

      const startTime = performance.now();
      const result = await contentProcessor.processContentDirectory('/test/large-content', mockImageDatabase);
      const endTime = performance.now();

      expect(endTime - startTime).toBeLessThan(10000); // Should complete within 10 seconds
      expect(result.statistics.total_contents).toBe(100);
    });

    test('should handle concurrent content processing', async () => {
      const processPromises = Array.from({ length: 3 }, () =>
        contentProcessor.processContentDirectory('/test/content', mockImageDatabase)
      );

      const results = await Promise.all(processPromises);

      results.forEach(result => {
        expect(result.processed).toBeGreaterThan(0);
        expect(result.statistics.average_confidence).toBeGreaterThan(0);
      });
    });
  });

  describe('Error Handling Integration', () => {
    test('should handle malformed content files', async () => {
      // Mock file reading error
      contentProcessor.parseMarkdownContent = jest.fn()
        .mockResolvedValueOnce({ title: 'Valid Content', category: 'test' })
        .mockRejectedValueOnce(new Error('File parsing failed'))
        .mockResolvedValueOnce({ title: 'Another Valid Content', category: 'test' });

      const result = await contentProcessor.processContentDirectory('/test/content', mockImageDatabase);

      expect(result.processed).toBe(2);
      expect(result.failed).toBe(1);
      expect(result.statistics.unmapped_contents).toHaveLength(1);
    });

    test('should continue processing after individual failures', async () => {
      // Mock intermittent failures in image matching
      contentProcessor.findOptimalImageMatch = jest.fn()
        .mockResolvedValueOnce({ confidence_score: 0.9, agent_id: 1 })
        .mockRejectedValueOnce(new Error('Image matching failed'))
        .mockResolvedValueOnce({ confidence_score: 0.8, agent_id: 2 });

      const result = await contentProcessor.processContentDirectory('/test/content', mockImageDatabase);

      expect(result.processed).toBe(2);
      expect(result.failed).toBe(1);
    });
  });

  describe('Database Integration', () => {
    test('should handle empty image database', async () => {
      const emptyDatabase = { agents: [] };
      
      const result = await contentProcessor.processContentDirectory('/test/content', emptyDatabase);

      expect(result.processed).toBe(0);
      expect(result.failed).toBe(testContentFiles.length);
      expect(result.statistics.unmapped_contents).toHaveLength(testContentFiles.length);
    });

    test('should utilize all available image agents', async () => {
      const result = await contentProcessor.processContentDirectory('/test/content', mockImageDatabase);

      expect(result.statistics.unique_agents_used).toBeGreaterThan(0);
      expect(result.statistics.unique_agents_used).toBeLessThanOrEqual(mockImageDatabase.agents.length);
    });
  });
});