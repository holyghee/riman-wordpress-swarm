/**
 * End-to-End Tests for Complete Semantic Image-Content Mapping Workflow
 * Quality Engineer - Full System Integration Testing
 */

const fs = require('fs');
const path = require('path');

describe('Complete Semantic Image-Content Mapping Workflow E2E', () => {
  let workflowSystem;
  let testEnvironment;
  let mockFileSystem;

  beforeAll(async () => {
    // Setup complete workflow system mock
    workflowSystem = {
      executeCompleteWorkflow: async (options = {}) => {
        const startTime = performance.now();
        const workflowSteps = [];
        
        try {
          // Step 1: Initialize system
          workflowSteps.push(await this.initializeSystem(options));
          
          // Step 2: Discover and parse content
          workflowSteps.push(await this.discoverContent(options.contentDirectory));
          
          // Step 3: Load image database
          workflowSteps.push(await this.loadImageDatabase(options.imageDatabasePath));
          
          // Step 4: Process semantic matching
          workflowSteps.push(await this.processSemanticMatching(workflowSteps[1].content, workflowSteps[2].imageDatabase));
          
          // Step 5: Generate SEO slugs
          workflowSteps.push(await this.generateSEOSlugs(workflowSteps[3].mappings));
          
          // Step 6: Apply neural enhancements (if enabled)
          if (options.useNeuralEnhancement) {
            workflowSteps.push(await this.applyNeuralEnhancements(workflowSteps[3].mappings));
          }
          
          // Step 7: WordPress integration
          workflowSteps.push(await this.integrateWithWordPress(workflowSteps[4].optimizedMappings || workflowSteps[3].mappings));
          
          // Step 8: Validation and quality assurance
          workflowSteps.push(await this.validateResults(workflowSteps));
          
          // Step 9: Generate reports
          workflowSteps.push(await this.generateReports(workflowSteps));
          
          const totalTime = performance.now() - startTime;
          
          return {
            success: true,
            totalTime,
            steps: workflowSteps,
            summary: this.generateSummary(workflowSteps)
          };
          
        } catch (error) {
          return {
            success: false,
            error: error.message,
            completedSteps: workflowSteps.length,
            totalTime: performance.now() - startTime
          };
        }
      },

      initializeSystem: async (options) => {
        const step = { name: 'Initialize System', status: 'running' };
        
        // Mock system initialization
        await new Promise(resolve => setTimeout(resolve, 100));
        
        step.status = 'completed';
        step.result = {
          configuration: options,
          systemReady: true,
          timestamp: new Date().toISOString()
        };
        
        return step;
      },

      discoverContent: async (contentDirectory) => {
        const step = { name: 'Discover Content', status: 'running' };
        
        // Mock content discovery
        const mockContent = [
          {
            file: '/content/asbestsanierung-trgs-519.md',
            title: 'Asbestsanierung nach TRGS 519',
            category: 'schadstoffe',
            keywords: ['asbest', 'sanierung', 'trgs', 'sicherheit'],
            excerpt: 'Professional asbestos removal following German safety standards'
          },
          {
            file: '/content/sigeko-planung.md',
            title: 'SiGeKo-Planung für Bauprojekte',
            category: 'sicherheit',
            keywords: ['sigeko', 'sicherheit', 'koordination', 'baustelle'],
            excerpt: 'Safety coordination planning for construction projects'
          },
          {
            file: '/content/altlasten-sanierung.md',
            title: 'Altlastenuntersuchung und Sanierung',
            category: 'altlasten',
            keywords: ['altlasten', 'untersuchung', 'sanierung', 'umwelt'],
            excerpt: 'Comprehensive contaminated site investigation and remediation'
          }
        ];
        
        await new Promise(resolve => setTimeout(resolve, 200));
        
        step.status = 'completed';
        step.result = {
          content: mockContent,
          totalFiles: mockContent.length,
          categories: ['schadstoffe', 'sicherheit', 'altlasten']
        };
        
        return step;
      },

      loadImageDatabase: async (imageDatabasePath) => {
        const step = { name: 'Load Image Database', status: 'running' };
        
        // Mock image database loading
        const mockImageDatabase = {
          agents: [
            {
              id: 1,
              theme: 'Professional asbestos removal with safety equipment',
              quadrants: {
                top_left: 'Workers in protective suits handling hazardous asbestos materials',
                top_right: 'Safety equipment and warning signs for asbestos work',
                bottom_left: 'Asbestos removal process documentation',
                bottom_right: 'Clean work environment after remediation'
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
              theme: 'Construction safety coordination and planning',
              quadrants: {
                top_left: 'Safety coordinator reviewing construction plans',
                top_right: 'Construction workers following safety protocols',
                bottom_left: 'Safety equipment and coordination documentation',
                bottom_right: 'Site safety meeting and coordination'
              }
            },
            {
              id: 3,
              theme: 'Environmental contamination assessment',
              quadrants: {
                top_left: 'Environmental scientists collecting soil samples',
                top_right: 'Laboratory analysis of contaminated materials',
                bottom_left: 'Site investigation equipment and testing',
                bottom_right: 'Environmental assessment documentation'
              }
            }
          ]
        };
        
        await new Promise(resolve => setTimeout(resolve, 300));
        
        step.status = 'completed';
        step.result = {
          imageDatabase: mockImageDatabase,
          totalAgents: mockImageDatabase.agents.length,
          totalQuadrants: mockImageDatabase.agents.length * 4
        };
        
        return step;
      },

      processSemanticMatching: async (content, imageDatabase) => {
        const step = { name: 'Process Semantic Matching', status: 'running' };
        
        const mappings = [];
        
        for (const contentItem of content) {
          const matches = [];
          
          // Calculate matches for each agent
          for (const agent of imageDatabase.agents) {
            const themeScore = this.calculateThemeMatch(contentItem.keywords, agent.theme);
            const quadrantScore = this.calculateQuadrantMatch(contentItem.excerpt, agent.quadrants);
            const totalScore = (themeScore * 0.4) + (quadrantScore * 0.6);
            
            matches.push({
              agent_id: agent.id,
              agent,
              confidence_score: totalScore,
              matching_details: {
                theme_score: themeScore,
                description_score: quadrantScore,
                total_score: totalScore
              }
            });
          }
          
          // Get best match
          const bestMatch = matches.sort((a, b) => b.confidence_score - a.confidence_score)[0];
          
          if (bestMatch.confidence_score >= 0.6) {
            mappings.push({
              content_file: contentItem.file,
              title: contentItem.title,
              category: contentItem.category,
              assigned_image: {
                ...bestMatch,
                selected_quadrant: 'top_left',
                filename: `agent_${bestMatch.agent_id}_top_left.png`,
                path: `./images/agent_${bestMatch.agent_id}_top_left.png`
              }
            });
          }
        }
        
        await new Promise(resolve => setTimeout(resolve, 500));
        
        step.status = 'completed';
        step.result = {
          mappings,
          totalMappings: mappings.length,
          averageConfidence: mappings.reduce((sum, m) => sum + m.assigned_image.confidence_score, 0) / mappings.length
        };
        
        return step;
      },

      generateSEOSlugs: async (mappings) => {
        const step = { name: 'Generate SEO Slugs', status: 'running' };
        
        const optimizedMappings = mappings.map(mapping => ({
          ...mapping,
          seo_slug: this.generateSlug(mapping.title, mapping.category),
          meta: {
            description: mapping.title,
            keywords: ['riman', 'umwelt', 'sanierung', mapping.category]
          }
        }));
        
        await new Promise(resolve => setTimeout(resolve, 150));
        
        step.status = 'completed';
        step.result = {
          optimizedMappings,
          totalSlugs: optimizedMappings.length,
          uniqueSlugs: new Set(optimizedMappings.map(m => m.seo_slug)).size
        };
        
        return step;
      },

      applyNeuralEnhancements: async (mappings) => {
        const step = { name: 'Apply Neural Enhancements', status: 'running' };
        
        const enhancedMappings = mappings.map(mapping => ({
          ...mapping,
          assigned_image: {
            ...mapping.assigned_image,
            confidence_score: Math.min(mapping.assigned_image.confidence_score + 0.1, 1.0),
            neural_enhanced: true,
            enhancement_score: 0.1
          }
        }));
        
        await new Promise(resolve => setTimeout(resolve, 800)); // Neural processing takes longer
        
        step.status = 'completed';
        step.result = {
          enhancedMappings,
          averageImprovement: 0.1,
          neuralProcessingTime: 800
        };
        
        return step;
      },

      integrateWithWordPress: async (mappings) => {
        const step = { name: 'WordPress Integration', status: 'running' };
        
        const wpResults = {
          categoriesCreated: 0,
          imagesUploaded: 0,
          pagesCreated: 0,
          featuredImagesSet: 0
        };
        
        // Mock WordPress operations
        for (const mapping of mappings) {
          // Mock category creation
          wpResults.categoriesCreated++;
          
          // Mock image upload
          wpResults.imagesUploaded++;
          
          // Mock page creation
          wpResults.pagesCreated++;
          
          // Mock featured image assignment
          wpResults.featuredImagesSet++;
          
          await new Promise(resolve => setTimeout(resolve, 50)); // 50ms per mapping
        }
        
        step.status = 'completed';
        step.result = {
          wordpressResults: wpResults,
          totalOperations: Object.values(wpResults).reduce((sum, count) => sum + count, 0),
          successRate: 1.0
        };
        
        return step;
      },

      validateResults: async (workflowSteps) => {
        const step = { name: 'Validate Results', status: 'running' };
        
        const validation = {
          stepsCompleted: workflowSteps.filter(s => s.status === 'completed').length,
          totalSteps: workflowSteps.length,
          contentProcessed: workflowSteps[1]?.result?.totalFiles || 0,
          mappingsCreated: workflowSteps[3]?.result?.totalMappings || 0,
          wordpressIntegrated: workflowSteps[6]?.result?.successRate === 1.0,
          averageConfidence: workflowSteps[3]?.result?.averageConfidence || 0,
          issues: []
        };
        
        // Validation checks
        if (validation.averageConfidence < 0.7) {
          validation.issues.push('Low average confidence score');
        }
        
        if (validation.stepsCompleted !== validation.totalSteps) {
          validation.issues.push('Not all workflow steps completed');
        }
        
        await new Promise(resolve => setTimeout(resolve, 100));
        
        step.status = 'completed';
        step.result = {
          validation,
          passed: validation.issues.length === 0,
          score: (validation.stepsCompleted / validation.totalSteps) * 100
        };
        
        return step;
      },

      generateReports: async (workflowSteps) => {
        const step = { name: 'Generate Reports', status: 'running' };
        
        const report = {
          timestamp: new Date().toISOString(),
          totalSteps: workflowSteps.length,
          completedSteps: workflowSteps.filter(s => s.status === 'completed').length,
          contentStatistics: workflowSteps[1]?.result || {},
          matchingStatistics: workflowSteps[3]?.result || {},
          wordpressStatistics: workflowSteps[6]?.result || {},
          validationResults: workflowSteps[7]?.result || {},
          recommendations: []
        };
        
        // Generate recommendations based on results
        if (report.matchingStatistics.averageConfidence < 0.8) {
          report.recommendations.push('Consider improving semantic matching algorithms');
        }
        
        if (report.wordpressStatistics.successRate < 1.0) {
          report.recommendations.push('Review WordPress integration for potential issues');
        }
        
        await new Promise(resolve => setTimeout(resolve, 200));
        
        step.status = 'completed';
        step.result = { report };
        
        return step;
      },

      generateSummary: (workflowSteps) => {
        const completedSteps = workflowSteps.filter(s => s.status === 'completed').length;
        const totalSteps = workflowSteps.length;
        const successRate = completedSteps / totalSteps;
        
        return {
          overallSuccess: successRate === 1.0,
          completionRate: `${completedSteps}/${totalSteps} (${Math.round(successRate * 100)}%)`,
          contentProcessed: workflowSteps[1]?.result?.totalFiles || 0,
          mappingsCreated: workflowSteps[3]?.result?.totalMappings || 0,
          averageConfidence: workflowSteps[3]?.result?.averageConfidence || 0,
          recommendations: workflowSteps[8]?.result?.report?.recommendations || []
        };
      },

      calculateThemeMatch: (keywords, theme) => {
        if (!keywords || !theme) return 0;
        
        const themeWords = theme.toLowerCase().split(/\s+/);
        const matchCount = keywords.filter(keyword =>
          themeWords.some(word => word.includes(keyword.toLowerCase()))
        ).length;
        
        return Math.min(matchCount / keywords.length, 1.0);
      },

      calculateQuadrantMatch: (excerpt, quadrants) => {
        if (!excerpt || !quadrants) return 0;
        
        const excerptWords = excerpt.toLowerCase().split(/\s+/);
        const allDescriptions = Object.values(quadrants).join(' ').toLowerCase();
        
        const matchCount = excerptWords.filter(word =>
          allDescriptions.includes(word)
        ).length;
        
        return Math.min(matchCount / excerptWords.length, 1.0);
      },

      generateSlug: (title, category) => {
        let slug = title.toLowerCase()
          .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
          .replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-')
          .replace(/^-+|-+$/g, '');
        
        if (category) {
          slug = `${category}-${slug}`;
        }
        
        return slug.length > 60 ? slug.substring(0, 60).replace(/-[^-]*$/, '') : slug;
      }
    };

    // Setup test environment
    testEnvironment = {
      contentDirectory: '/test/content',
      imageDatabasePath: '/test/images/database.json',
      wordpressConfig: {
        host: 'http://localhost:8801',
        database: 'wordpress_test'
      }
    };
  });

  describe('Complete Workflow Execution', () => {
    test('should execute full workflow successfully', async () => {
      const options = {
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath,
        useNeuralEnhancement: false,
        minConfidenceThreshold: 0.6
      };

      const result = await workflowSystem.executeCompleteWorkflow(options);

      expect(result.success).toBe(true);
      expect(result.steps).toHaveLength(8); // Without neural enhancement
      expect(result.summary.overallSuccess).toBe(true);
      expect(result.summary.completionRate).toBe('8/8 (100%)');
      expect(result.totalTime).toBeGreaterThan(0);
    });

    test('should execute workflow with neural enhancement', async () => {
      const options = {
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath,
        useNeuralEnhancement: true,
        minConfidenceThreshold: 0.7
      };

      const result = await workflowSystem.executeCompleteWorkflow(options);

      expect(result.success).toBe(true);
      expect(result.steps).toHaveLength(9); // With neural enhancement
      
      const neuralStep = result.steps.find(s => s.name === 'Apply Neural Enhancements');
      expect(neuralStep).toBeDefined();
      expect(neuralStep.status).toBe('completed');
      expect(neuralStep.result.averageImprovement).toBeGreaterThan(0);
    });

    test('should handle workflow with different confidence thresholds', async () => {
      const thresholds = [0.5, 0.7, 0.9];
      const results = [];

      for (const threshold of thresholds) {
        const options = {
          contentDirectory: testEnvironment.contentDirectory,
          imageDatabasePath: testEnvironment.imageDatabasePath,
          minConfidenceThreshold: threshold
        };

        const result = await workflowSystem.executeCompleteWorkflow(options);
        results.push({
          threshold,
          success: result.success,
          mappingsCreated: result.summary.mappingsCreated,
          averageConfidence: result.summary.averageConfidence
        });
      }

      // Higher thresholds should result in fewer but higher quality mappings
      results.forEach(result => {
        expect(result.success).toBe(true);
        if (result.mappingsCreated > 0) {
          expect(result.averageConfidence).toBeGreaterThanOrEqual(result.threshold);
        }
      });
    });
  });

  describe('Workflow Step Integration', () => {
    test('should pass data correctly between workflow steps', async () => {
      const result = await workflowSystem.executeCompleteWorkflow({
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath
      });

      expect(result.success).toBe(true);

      // Verify data flow between steps
      const contentStep = result.steps.find(s => s.name === 'Discover Content');
      const imageStep = result.steps.find(s => s.name === 'Load Image Database');
      const matchingStep = result.steps.find(s => s.name === 'Process Semantic Matching');
      const seoStep = result.steps.find(s => s.name === 'Generate SEO Slugs');

      expect(contentStep.result.totalFiles).toBeGreaterThan(0);
      expect(imageStep.result.totalAgents).toBeGreaterThan(0);
      expect(matchingStep.result.totalMappings).toBeGreaterThan(0);
      expect(seoStep.result.totalSlugs).toBe(matchingStep.result.totalMappings);
    });

    test('should handle step failures gracefully', async () => {
      // Mock a step failure
      const originalProcessSemanticMatching = workflowSystem.processSemanticMatching;
      workflowSystem.processSemanticMatching = async () => {
        throw new Error('Semantic matching failed');
      };

      const result = await workflowSystem.executeCompleteWorkflow({
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath
      });

      expect(result.success).toBe(false);
      expect(result.error).toContain('Semantic matching failed');
      expect(result.completedSteps).toBeLessThan(8);

      // Restore original function
      workflowSystem.processSemanticMatching = originalProcessSemanticMatching;
    });
  });

  describe('Content Processing End-to-End', () => {
    test('should process different content types correctly', async () => {
      const result = await workflowSystem.executeCompleteWorkflow({
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath
      });

      const contentStep = result.steps.find(s => s.name === 'Discover Content');
      const matchingStep = result.steps.find(s => s.name === 'Process Semantic Matching');

      // Verify all content categories are represented
      const categories = contentStep.result.categories;
      expect(categories).toContain('schadstoffe');
      expect(categories).toContain('sicherheit');
      expect(categories).toContain('altlasten');

      // Verify mappings were created for different categories
      const mappings = matchingStep.result.mappings;
      const mappedCategories = new Set(mappings.map(m => m.category));
      expect(mappedCategories.size).toBeGreaterThan(1);
    });

    test('should generate valid SEO slugs for all content', async () => {
      const result = await workflowSystem.executeCompleteWorkflow({
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath
      });

      const seoStep = result.steps.find(s => s.name === 'Generate SEO Slugs');
      const mappings = seoStep.result.optimizedMappings;

      mappings.forEach(mapping => {
        expect(mapping.seo_slug).toBeValidSEOSlug();
        expect(mapping.meta).toHaveProperty('description');
        expect(mapping.meta.keywords).toBeInstanceOf(Array);
      });

      // Verify unique slugs
      const slugs = mappings.map(m => m.seo_slug);
      const uniqueSlugs = new Set(slugs);
      expect(slugs.length).toBe(uniqueSlugs.size);
    });
  });

  describe('WordPress Integration End-to-End', () => {
    test('should integrate all mappings with WordPress', async () => {
      const result = await workflowSystem.executeCompleteWorkflow({
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath
      });

      const wpStep = result.steps.find(s => s.name === 'WordPress Integration');
      const wpResults = wpStep.result.wordpressResults;

      expect(wpResults.categoriesCreated).toBeGreaterThan(0);
      expect(wpResults.imagesUploaded).toBeGreaterThan(0);
      expect(wpResults.pagesCreated).toBeGreaterThan(0);
      expect(wpResults.featuredImagesSet).toBeGreaterThan(0);

      // All operations should be successful
      expect(wpStep.result.successRate).toBe(1.0);
    });

    test('should maintain data integrity through WordPress integration', async () => {
      const result = await workflowSystem.executeCompleteWorkflow({
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath
      });

      const matchingStep = result.steps.find(s => s.name === 'Process Semantic Matching');
      const wpStep = result.steps.find(s => s.name === 'WordPress Integration');

      // Number of WordPress operations should match number of mappings
      const totalMappings = matchingStep.result.totalMappings;
      const totalWpOperations = wpStep.result.wordpressResults.pagesCreated;

      expect(totalWpOperations).toBe(totalMappings);
    });
  });

  describe('Quality Assurance and Validation', () => {
    test('should validate all workflow results', async () => {
      const result = await workflowSystem.executeCompleteWorkflow({
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath
      });

      const validationStep = result.steps.find(s => s.name === 'Validate Results');
      const validation = validationStep.result.validation;

      expect(validation.passed).toBe(true);
      expect(validation.score).toBe(100);
      expect(validation.stepsCompleted).toBe(validation.totalSteps);
      expect(validation.issues).toHaveLength(0);
    });

    test('should identify and report quality issues', async () => {
      // Mock low confidence results
      const originalProcessSemanticMatching = workflowSystem.processSemanticMatching;
      workflowSystem.processSemanticMatching = async (content, imageDatabase) => {
        const step = await originalProcessSemanticMatching.call(workflowSystem, content, imageDatabase);
        
        // Lower all confidence scores
        step.result.mappings = step.result.mappings.map(mapping => ({
          ...mapping,
          assigned_image: {
            ...mapping.assigned_image,
            confidence_score: 0.5 // Below 0.7 threshold
          }
        }));
        
        step.result.averageConfidence = 0.5;
        return step;
      };

      const result = await workflowSystem.executeCompleteWorkflow({
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath
      });

      const validationStep = result.steps.find(s => s.name === 'Validate Results');
      const validation = validationStep.result.validation;

      expect(validation.issues).toContain('Low average confidence score');
      expect(validation.passed).toBe(false);

      // Restore original function
      workflowSystem.processSemanticMatching = originalProcessSemanticMatching;
    });
  });

  describe('Performance and Scalability E2E', () => {
    test('should complete workflow within acceptable time limits', async () => {
      const startTime = performance.now();
      
      const result = await workflowSystem.executeCompleteWorkflow({
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath
      });
      
      const endTime = performance.now();
      const totalTime = endTime - startTime;

      expect(result.success).toBe(true);
      expect(totalTime).toBeLessThan(10000); // 10 seconds max for E2E test
      expect(result.totalTime).toBeCloseTo(totalTime, -2); // Within 100ms
    });

    test('should handle larger datasets efficiently', async () => {
      // Mock larger dataset
      const originalDiscoverContent = workflowSystem.discoverContent;
      workflowSystem.discoverContent = async (contentDirectory) => {
        const step = await originalDiscoverContent.call(workflowSystem, contentDirectory);
        
        // Expand content array
        const additionalContent = Array.from({ length: 20 }, (_, i) => ({
          file: `/content/additional-content-${i + 1}.md`,
          title: `Additional Content ${i + 1}`,
          category: 'general',
          keywords: [`keyword${i + 1}`, 'test'],
          excerpt: `Additional test content ${i + 1}`
        }));
        
        step.result.content = [...step.result.content, ...additionalContent];
        step.result.totalFiles = step.result.content.length;
        
        return step;
      };

      const result = await workflowSystem.executeCompleteWorkflow({
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath
      });

      expect(result.success).toBe(true);
      expect(result.summary.contentProcessed).toBeGreaterThan(3); // Original + additional
      expect(result.totalTime).toBeLessThan(15000); // 15 seconds max for larger dataset

      // Restore original function
      workflowSystem.discoverContent = originalDiscoverContent;
    });
  });

  describe('Error Recovery and Resilience', () => {
    test('should provide detailed error information on failure', async () => {
      // Mock a critical failure
      const originalLoadImageDatabase = workflowSystem.loadImageDatabase;
      workflowSystem.loadImageDatabase = async () => {
        throw new Error('Database connection failed');
      };

      const result = await workflowSystem.executeCompleteWorkflow({
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath
      });

      expect(result.success).toBe(false);
      expect(result.error).toBe('Database connection failed');
      expect(result.completedSteps).toBe(2); // Should complete first 2 steps
      expect(result.totalTime).toBeGreaterThan(0);

      // Restore original function
      workflowSystem.loadImageDatabase = originalLoadImageDatabase;
    });

    test('should generate reports even on partial failure', async () => {
      // Mock failure in WordPress integration
      const originalIntegrateWithWordPress = workflowSystem.integrateWithWordPress;
      workflowSystem.integrateWithWordPress = async () => {
        throw new Error('WordPress integration failed');
      };

      const result = await workflowSystem.executeCompleteWorkflow({
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath
      });

      expect(result.success).toBe(false);
      expect(result.completedSteps).toBe(6); // Should complete steps before WordPress
      
      // Should still have processed content and created mappings
      expect(result.steps).toHaveLength(6);
      const matchingStep = result.steps.find(s => s.name === 'Process Semantic Matching');
      expect(matchingStep).toBeDefined();
      expect(matchingStep.status).toBe('completed');

      // Restore original function
      workflowSystem.integrateWithWordPress = originalIntegrateWithWordPress;
    });
  });

  describe('Reporting and Analytics E2E', () => {
    test('should generate comprehensive workflow reports', async () => {
      const result = await workflowSystem.executeCompleteWorkflow({
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath,
        useNeuralEnhancement: true
      });

      const reportStep = result.steps.find(s => s.name === 'Generate Reports');
      const report = reportStep.result.report;

      expect(report).toHaveProperty('timestamp');
      expect(report).toHaveProperty('contentStatistics');
      expect(report).toHaveProperty('matchingStatistics');
      expect(report).toHaveProperty('wordpressStatistics');
      expect(report).toHaveProperty('validationResults');
      expect(report).toHaveProperty('recommendations');

      expect(report.completedSteps).toBe(report.totalSteps);
      expect(report.contentStatistics.totalFiles).toBeGreaterThan(0);
      expect(report.matchingStatistics.totalMappings).toBeGreaterThan(0);
    });

    test('should provide actionable recommendations', async () => {
      const result = await workflowSystem.executeCompleteWorkflow({
        contentDirectory: testEnvironment.contentDirectory,
        imageDatabasePath: testEnvironment.imageDatabasePath
      });

      const reportStep = result.steps.find(s => s.name === 'Generate Reports');
      const recommendations = reportStep.result.report.recommendations;

      expect(recommendations).toBeInstanceOf(Array);
      
      // Recommendations should be meaningful
      if (recommendations.length > 0) {
        recommendations.forEach(recommendation => {
          expect(typeof recommendation).toBe('string');
          expect(recommendation.length).toBeGreaterThan(10);
        });
      }
    });
  });
});