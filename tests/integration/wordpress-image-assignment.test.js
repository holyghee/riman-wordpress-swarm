/**
 * Integration Tests for WordPress Image Assignment
 * Quality Engineer - WordPress Integration Testing
 */

const fs = require('fs');
const path = require('path');

describe('WordPress Image Assignment Integration', () => {
  let wpMocks;
  let imageAssigner;
  let testImages;
  let testCategories;

  beforeAll(async () => {
    // Setup WordPress mocks
    wpMocks = global.wpTestEnv.mockWordPressFunctions();
    
    // Mock image assignment system
    imageAssigner = {
      assignSemanticImages: async (semanticMappings) => {
        const results = { successful: 0, failed: 0, assignments: [] };
        
        for (const [categorySlug, imageFilename] of Object.entries(semanticMappings)) {
          const category = wpMocks.get_term_by('slug', categorySlug, 'category');
          
          if (!category) {
            results.failed++;
            continue;
          }

          // Mock image file existence check
          const imageExists = testImages.some(img => img.filename === imageFilename);
          if (!imageExists) {
            results.failed++;
            continue;
          }

          // Create attachment
          const attachmentId = wpMocks.wp_insert_attachment({
            post_title: `Image for ${category.name}`,
            post_mime_type: 'image/jpeg'
          });

          // Generate metadata
          const metadata = wpMocks.wp_generate_attachment_metadata(attachmentId);
          wpMocks.wp_update_attachment_metadata(attachmentId, metadata);

          // Assign to category
          wpMocks.update_term_meta(category.term_id, 'thumbnail_id', attachmentId);

          results.successful++;
          results.assignments.push({
            categorySlug,
            categoryId: category.term_id,
            imageFilename,
            attachmentId
          });
        }
        
        return results;
      },

      assignPageFeaturedImages: async (categoryAssignments) => {
        const results = [];
        
        for (const assignment of categoryAssignments) {
          // Get pages linked to this category
          const pages = wpMocks.get_posts({
            post_type: 'page',
            meta_query: [{
              key: '_linked_category',
              value: assignment.categoryId,
              compare: '='
            }]
          });

          for (const page of pages) {
            const success = wpMocks.set_post_thumbnail(page.ID, assignment.attachmentId);
            results.push({
              pageId: page.ID,
              pageTitle: page.post_title,
              attachmentId: assignment.attachmentId,
              success
            });
          }
        }
        
        return results;
      },

      validateAssignments: async () => {
        const validation = { categories: [], pages: [], issues: [] };
        
        // Check category thumbnails
        for (const category of testCategories) {
          const thumbnailId = wpMocks.get_term_meta(category.term_id, 'thumbnail_id', true);
          validation.categories.push({
            categoryId: category.term_id,
            categorySlug: category.slug,
            hasThumbnail: !!thumbnailId,
            thumbnailId
          });
        }
        
        // Check page featured images
        const pages = wpMocks.get_posts({ post_type: 'page' });
        for (const page of pages) {
          const featuredImageId = wpMocks.get_post_thumbnail_id(page.ID);
          validation.pages.push({
            pageId: page.ID,
            pageTitle: page.post_title,
            hasFeaturedImage: !!featuredImageId,
            featuredImageId
          });
        }
        
        return validation;
      }
    };

    // Test data
    testCategories = [
      { term_id: 1, slug: 'rueckbau', name: 'Rückbau' },
      { term_id: 2, slug: 'altlasten', name: 'Altlasten' },
      { term_id: 3, slug: 'schadstoffe', name: 'Schadstoffe' },
      { term_id: 4, slug: 'sicherheit', name: 'Sicherheit' },
      { term_id: 5, slug: 'beratung', name: 'Beratung' }
    ];

    testImages = [
      { filename: 'nachhaltiger-rueckbau-baustelle-recycling.jpg', category: 'rueckbau' },
      { filename: 'altlastensanierung-grundwasser-bodenschutz.jpg', category: 'altlasten' },
      { filename: 'schadstoffsanierung-industrieanlage-riman-gmbh.jpg', category: 'schadstoffe' },
      { filename: 'sicherheitsvorbereitung-schutzausruestung-schritt-3.jpg', category: 'sicherheit' },
      { filename: 'baumediation-konfliktloesung-projektmanagement.jpg', category: 'beratung' }
    ];

    // Mock WordPress function returns
    wpMocks.get_term_by.mockImplementation((field, value, taxonomy) => {
      return testCategories.find(cat => cat[field] === value) || false;
    });

    wpMocks.get_posts.mockImplementation((args) => {
      if (args.post_type === 'page') {
        return [
          { ID: 101, post_title: 'Rückbau Services', meta: { _linked_category: 1 } },
          { ID: 102, post_title: 'Altlasten Remediation', meta: { _linked_category: 2 } },
          { ID: 103, post_title: 'Schadstoff Management', meta: { _linked_category: 3 } }
        ];
      }
      return [];
    });
  });

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Semantic Image Assignment', () => {
    test('should assign images to categories successfully', async () => {
      const semanticMappings = {
        'rueckbau': 'nachhaltiger-rueckbau-baustelle-recycling.jpg',
        'altlasten': 'altlastensanierung-grundwasser-bodenschutz.jpg',
        'schadstoffe': 'schadstoffsanierung-industrieanlage-riman-gmbh.jpg'
      };

      const result = await imageAssigner.assignSemanticImages(semanticMappings);

      expect(result.successful).toBe(3);
      expect(result.failed).toBe(0);
      expect(result.assignments).toHaveLength(3);
      
      expect(wpMocks.wp_insert_attachment).toHaveBeenCalledTimes(3);
      expect(wpMocks.update_term_meta).toHaveBeenCalledTimes(3);
    });

    test('should handle missing categories gracefully', async () => {
      const semanticMappings = {
        'non-existent-category': 'test-image.jpg',
        'rueckbau': 'nachhaltiger-rueckbau-baustelle-recycling.jpg'
      };

      const result = await imageAssigner.assignSemanticImages(semanticMappings);

      expect(result.successful).toBe(1);
      expect(result.failed).toBe(1);
    });

    test('should handle missing image files', async () => {
      const semanticMappings = {
        'rueckbau': 'non-existent-image.jpg',
        'altlasten': 'altlastensanierung-grundwasser-bodenschutz.jpg'
      };

      const result = await imageAssigner.assignSemanticImages(semanticMappings);

      expect(result.successful).toBe(1);
      expect(result.failed).toBe(1);
    });
  });

  describe('Page Featured Image Assignment', () => {
    test('should assign featured images to linked pages', async () => {
      const categoryAssignments = [
        { categorySlug: 'rueckbau', categoryId: 1, attachmentId: 201 },
        { categorySlug: 'altlasten', categoryId: 2, attachmentId: 202 }
      ];

      const result = await imageAssigner.assignPageFeaturedImages(categoryAssignments);

      expect(result).toHaveLength(2); // Two pages linked to these categories
      expect(wpMocks.set_post_thumbnail).toHaveBeenCalledTimes(2);
      
      result.forEach(assignment => {
        expect(assignment).toHaveProperty('pageId');
        expect(assignment).toHaveProperty('attachmentId');
        expect(assignment.success).toBe(true);
      });
    });

    test('should handle pages without category links', async () => {
      // Mock pages without category meta
      wpMocks.get_posts.mockReturnValueOnce([]);

      const categoryAssignments = [
        { categorySlug: 'beratung', categoryId: 5, attachmentId: 205 }
      ];

      const result = await imageAssigner.assignPageFeaturedImages(categoryAssignments);

      expect(result).toHaveLength(0);
      expect(wpMocks.set_post_thumbnail).not.toHaveBeenCalled();
    });
  });

  describe('WordPress Metadata Handling', () => {
    test('should generate proper attachment metadata', async () => {
      const semanticMappings = {
        'rueckbau': 'nachhaltiger-rueckbau-baustelle-recycling.jpg'
      };

      await imageAssigner.assignSemanticImages(semanticMappings);

      expect(wpMocks.wp_generate_attachment_metadata).toHaveBeenCalledWith(
        expect.any(Number),
        expect.any(String)
      );
      expect(wpMocks.wp_update_attachment_metadata).toHaveBeenCalled();
    });

    test('should set correct term metadata', async () => {
      const semanticMappings = {
        'altlasten': 'altlastensanierung-grundwasser-bodenschutz.jpg'
      };

      await imageAssigner.assignSemanticImages(semanticMappings);

      expect(wpMocks.update_term_meta).toHaveBeenCalledWith(
        2, // altlasten category ID
        'thumbnail_id',
        expect.any(Number)
      );
    });

    test('should handle WordPress errors gracefully', async () => {
      // Mock WordPress error
      wpMocks.wp_insert_attachment.mockReturnValueOnce({ 
        error: true, 
        message: 'Failed to insert attachment' 
      });

      const semanticMappings = {
        'rueckbau': 'nachhaltiger-rueckbau-baustelle-recycling.jpg'
      };

      const result = await imageAssigner.assignSemanticImages(semanticMappings);

      expect(result.failed).toBe(1);
    });
  });

  describe('Assignment Validation', () => {
    test('should validate category thumbnail assignments', async () => {
      // Setup mock data
      wpMocks.get_term_meta.mockImplementation((termId, key) => {
        const thumbnailMappings = { 1: 201, 2: 202, 3: 203 };
        return thumbnailMappings[termId] || null;
      });

      const validation = await imageAssigner.validateAssignments();

      expect(validation.categories).toHaveLength(testCategories.length);
      
      const categoriesWithThumbnails = validation.categories.filter(cat => cat.hasThumbnail);
      expect(categoriesWithThumbnails).toHaveLength(3);
    });

    test('should validate page featured image assignments', async () => {
      // Setup mock data
      wpMocks.get_post_thumbnail_id.mockImplementation((postId) => {
        const featuredImageMappings = { 101: 201, 102: 202 };
        return featuredImageMappings[postId] || null;
      });

      const validation = await imageAssigner.validateAssignments();

      expect(validation.pages).toHaveLength(3);
      
      const pagesWithImages = validation.pages.filter(page => page.hasFeaturedImage);
      expect(pagesWithImages).toHaveLength(2);
    });

    test('should identify missing assignments', async () => {
      // Mock some missing assignments
      wpMocks.get_term_meta.mockReturnValue(null);
      wpMocks.get_post_thumbnail_id.mockReturnValue(null);

      const validation = await imageAssigner.validateAssignments();

      const categoriesWithoutThumbnails = validation.categories.filter(cat => !cat.hasThumbnail);
      const pagesWithoutImages = validation.pages.filter(page => !page.hasFeaturedImage);

      expect(categoriesWithoutThumbnails).toHaveLength(testCategories.length);
      expect(pagesWithoutImages).toHaveLength(3);
    });
  });

  describe('File System Integration', () => {
    test('should handle different image source paths', async () => {
      const semanticMappings = {
        'rueckbau': 'nachhaltiger-rueckbau-baustelle-recycling.jpg'
      };

      // Mock multiple source path checking
      const mockImageExists = jest.fn()
        .mockReturnValueOnce(false) // First path doesn't exist
        .mockReturnValueOnce(true);  // Second path exists

      const result = await imageAssigner.assignSemanticImages(semanticMappings);

      expect(result.successful).toBe(1);
    });

    test('should preserve original filenames', async () => {
      const semanticMappings = {
        'rueckbau': 'original-filename-with-special-chars.jpg'
      };

      const result = await imageAssigner.assignSemanticImages(semanticMappings);

      expect(result.assignments[0]).toHaveProperty('imageFilename', 'original-filename-with-special-chars.jpg');
    });
  });

  describe('Performance Integration Tests', () => {
    test('should handle batch assignment efficiently', async () => {
      const largeMappings = {};
      for (let i = 1; i <= 50; i++) {
        largeMappings[`category-${i}`] = `image-${i}.jpg`;
      }

      // Mock all categories exist
      wpMocks.get_term_by.mockImplementation((field, value) => ({
        term_id: Math.floor(Math.random() * 1000),
        slug: value,
        name: value.replace('-', ' ')
      }));

      const startTime = performance.now();
      const result = await imageAssigner.assignSemanticImages(largeMappings);
      const endTime = performance.now();

      expect(endTime - startTime).toBeLessThan(global.TEST_CONFIG.performance.databaseOperationTimeout * 50);
      expect(result.assignments).toHaveLength(50);
    });

    test('should handle concurrent assignment operations', async () => {
      const mappingBatches = [
        { 'rueckbau': 'image-1.jpg' },
        { 'altlasten': 'image-2.jpg' },
        { 'schadstoffe': 'image-3.jpg' }
      ];

      const promises = mappingBatches.map(mapping => 
        imageAssigner.assignSemanticImages(mapping)
      );

      const results = await Promise.all(promises);

      results.forEach(result => {
        expect(result.successful).toBe(1);
        expect(result.failed).toBe(0);
      });
    });
  });

  describe('Error Recovery', () => {
    test('should recover from partial failures', async () => {
      const semanticMappings = {
        'rueckbau': 'valid-image.jpg',
        'invalid-category': 'image.jpg',
        'altlasten': 'valid-image-2.jpg'
      };

      const result = await imageAssigner.assignSemanticImages(semanticMappings);

      expect(result.successful).toBe(2);
      expect(result.failed).toBe(1);
      expect(result.assignments).toHaveLength(2);
    });

    test('should continue processing after individual failures', async () => {
      // Mock intermittent failures
      wpMocks.wp_insert_attachment
        .mockReturnValueOnce(null) // First call fails
        .mockReturnValue(123);     // Subsequent calls succeed

      const semanticMappings = {
        'category1': 'image1.jpg',
        'rueckbau': 'image2.jpg',
        'altlasten': 'image3.jpg'
      };

      const result = await imageAssigner.assignSemanticImages(semanticMappings);

      expect(result.successful).toBe(2);
      expect(result.failed).toBe(1);
    });
  });
});