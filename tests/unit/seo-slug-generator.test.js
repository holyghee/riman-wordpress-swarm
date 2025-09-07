/**
 * Unit Tests for SEO Slug Generator
 * Quality Engineer - SEO URL Generation Testing
 */

describe('SEO Slug Generator', () => {
  let seoGenerator;

  beforeAll(() => {
    // Mock SEO slug generator
    seoGenerator = {
      generateSlug: (title, category = null, options = {}) => {
        const maxLength = options.maxLength || 60;
        const includeCategory = options.includeCategory !== false;
        
        // Convert to lowercase
        let slug = title.toLowerCase();
        
        // Replace German umlauts
        slug = slug
          .replace(/ä/g, 'ae')
          .replace(/ö/g, 'oe')
          .replace(/ü/g, 'ue')
          .replace(/ß/g, 'ss');
        
        // Remove special characters except hyphens
        slug = slug.replace(/[^a-z0-9\s-]/g, '');
        
        // Replace spaces with hyphens
        slug = slug.replace(/\s+/g, '-');
        
        // Remove multiple consecutive hyphens
        slug = slug.replace(/-+/g, '-');
        
        // Remove leading/trailing hyphens
        slug = slug.replace(/^-+|-+$/g, '');
        
        // Add category prefix if provided
        if (category && includeCategory) {
          const categorySlug = category.toLowerCase().replace(/[^a-z0-9-]/g, '-');
          slug = `${categorySlug}-${slug}`;
        }
        
        // Truncate to max length
        if (slug.length > maxLength) {
          slug = slug.substring(0, maxLength);
          // Remove trailing incomplete word
          const lastHyphen = slug.lastIndexOf('-');
          if (lastHyphen > maxLength * 0.8) {
            slug = slug.substring(0, lastHyphen);
          }
        }
        
        return slug;
      },
      
      validateSlug: (slug) => {
        const seoPattern = /^[a-z0-9-]+$/;
        return {
          isValid: seoPattern.test(slug) && 
                   slug.length <= 60 && 
                   !slug.startsWith('-') && 
                   !slug.endsWith('-') &&
                   slug.length > 0,
          length: slug.length,
          pattern: seoPattern.test(slug),
          noLeadingHyphen: !slug.startsWith('-'),
          noTrailingHyphen: !slug.endsWith('-')
        };
      },
      
      optimizeForSEO: (slug, keywords = []) => {
        // Move important keywords to the beginning
        if (keywords.length === 0) return slug;
        
        const words = slug.split('-');
        const priorityWords = [];
        const regularWords = [];
        
        words.forEach(word => {
          if (keywords.some(keyword => keyword.toLowerCase() === word)) {
            priorityWords.push(word);
          } else {
            regularWords.push(word);
          }
        });
        
        return [...priorityWords, ...regularWords].join('-');
      }
    };
  });

  describe('Basic Slug Generation', () => {
    test('should convert title to valid SEO slug', () => {
      const title = 'Asbestsanierung nach TRGS 519';
      const result = seoGenerator.generateSlug(title);
      
      expect(result).toBeValidSEOSlug();
      expect(result).toBe('asbestsanierung-nach-trgs-519');
    });

    test('should handle German umlauts correctly', () => {
      const title = 'Rückbau und Entsorgung für Ölschäden';
      const result = seoGenerator.generateSlug(title);
      
      expect(result).toBe('rueckbau-und-entsorgung-fuer-oelschaeden');
      expect(result).toBeValidSEOSlug();
    });

    test('should remove special characters', () => {
      const title = 'SiGeKo-Planung (§ 2 BaustellV)';
      const result = seoGenerator.generateSlug(title);
      
      expect(result).toBe('sigeko-planung-2-baustellv');
      expect(result).toBeValidSEOSlug();
    });

    test('should handle multiple spaces and hyphens', () => {
      const title = 'Test    Title  ---  With   Spaces';
      const result = seoGenerator.generateSlug(title);
      
      expect(result).toBe('test-title-with-spaces');
      expect(result).toBeValidSEOSlug();
    });
  });

  describe('Category Integration', () => {
    test('should include category prefix', () => {
      const title = 'TRGS 519 Compliance';
      const category = 'Schadstoffe';
      const result = seoGenerator.generateSlug(title, category);
      
      expect(result).toBe('schadstoffe-trgs-519-compliance');
      expect(result).toBeValidSEOSlug();
    });

    test('should handle category with special characters', () => {
      const title = 'Safety Planning';
      const category = 'Sicherheit & Koordination';
      const result = seoGenerator.generateSlug(title, category);
      
      expect(result).toBe('sicherheit-koordination-safety-planning');
      expect(result).toBeValidSEOSlug();
    });

    test('should skip category when disabled', () => {
      const title = 'Test Title';
      const category = 'Test Category';
      const result = seoGenerator.generateSlug(title, category, { includeCategory: false });
      
      expect(result).toBe('test-title');
      expect(result).not.toContain('category');
    });
  });

  describe('Length Constraints', () => {
    test('should truncate long titles to 60 characters', () => {
      const longTitle = 'This is a very long title that exceeds the maximum SEO slug length of sixty characters';
      const result = seoGenerator.generateSlug(longTitle);
      
      expect(result.length).toBeLessThanOrEqual(60);
      expect(result).toBeValidSEOSlug();
    });

    test('should respect custom max length', () => {
      const title = 'Medium length title for testing';
      const result = seoGenerator.generateSlug(title, null, { maxLength: 20 });
      
      expect(result.length).toBeLessThanOrEqual(20);
      expect(result).toBeValidSEOSlug();
    });

    test('should preserve word boundaries when truncating', () => {
      const title = 'Environmental consulting and remediation services';
      const result = seoGenerator.generateSlug(title, null, { maxLength: 30 });
      
      expect(result.length).toBeLessThanOrEqual(30);
      expect(result).not.toMatch(/-$/); // Should not end with hyphen
      expect(result).toBeValidSEOSlug();
    });
  });

  describe('SEO Optimization', () => {
    test('should move keywords to beginning', () => {
      const slug = 'consulting-environmental-asbestos-removal';
      const keywords = ['asbestos', 'environmental'];
      const result = seoGenerator.optimizeForSEO(slug, keywords);
      
      expect(result).toMatch(/^(asbestos|environmental)/);
      expect(result).toContain('asbestos');
      expect(result).toContain('environmental');
    });

    test('should handle empty keywords array', () => {
      const slug = 'test-slug-for-optimization';
      const result = seoGenerator.optimizeForSEO(slug, []);
      
      expect(result).toBe(slug);
    });

    test('should maintain valid SEO format after optimization', () => {
      const slug = 'general-consulting-asbestos-removal-services';
      const keywords = ['asbestos'];
      const result = seoGenerator.optimizeForSEO(slug, keywords);
      
      expect(result).toBeValidSEOSlug();
      expect(result).toMatch(/^asbestos/);
    });
  });

  describe('Validation', () => {
    test('should validate correct SEO slugs', () => {
      const validSlugs = [
        'asbestsanierung-trgs-519',
        'sigeko-planung-2024',
        'umweltberatung-hamburg'
      ];

      validSlugs.forEach(slug => {
        const validation = seoGenerator.validateSlug(slug);
        expect(validation.isValid).toBe(true);
      });
    });

    test('should reject invalid SEO slugs', () => {
      const invalidSlugs = [
        'slug-with-UPPERCASE',
        'slug_with_underscores',
        '-starting-with-hyphen',
        'ending-with-hyphen-',
        '',
        'slug with spaces',
        'slug.with.dots'
      ];

      invalidSlugs.forEach(slug => {
        const validation = seoGenerator.validateSlug(slug);
        expect(validation.isValid).toBe(false);
      });
    });

    test('should provide detailed validation feedback', () => {
      const slug = 'Test-Invalid_Slug-';
      const validation = seoGenerator.validateSlug(slug);
      
      expect(validation).toHaveProperty('isValid', false);
      expect(validation).toHaveProperty('length');
      expect(validation).toHaveProperty('pattern');
      expect(validation).toHaveProperty('noLeadingHyphen');
      expect(validation).toHaveProperty('noTrailingHyphen');
    });
  });

  describe('Edge Cases', () => {
    test('should handle empty title', () => {
      const result = seoGenerator.generateSlug('');
      
      expect(result).toBe('');
      expect(seoGenerator.validateSlug(result).isValid).toBe(false);
    });

    test('should handle title with only special characters', () => {
      const title = '!@#$%^&*()';
      const result = seoGenerator.generateSlug(title);
      
      expect(result).toBe('');
    });

    test('should handle numeric titles', () => {
      const title = '2024 TRGS 519 Updates';
      const result = seoGenerator.generateSlug(title);
      
      expect(result).toBe('2024-trgs-519-updates');
      expect(result).toBeValidSEOSlug();
    });

    test('should handle single character words', () => {
      const title = 'A B C D E F G';
      const result = seoGenerator.generateSlug(title);
      
      expect(result).toBe('a-b-c-d-e-f-g');
      expect(result).toBeValidSEOSlug();
    });
  });

  describe('German SEO Specifics', () => {
    test('should handle compound German words', () => {
      const title = 'Grundwassersanierungsverfahren';
      const result = seoGenerator.generateSlug(title);
      
      expect(result).toBe('grundwassersanierungsverfahren');
      expect(result).toBeValidSEOSlug();
    });

    test('should optimize for German location keywords', () => {
      const slug = 'beratung-umwelt-hamburg-norddeutschland';
      const keywords = ['hamburg', 'umwelt'];
      const result = seoGenerator.optimizeForSEO(slug, keywords);
      
      expect(result).toMatch(/^(hamburg|umwelt)/);
      expect(result).toBeValidSEOSlug();
    });

    test('should handle technical German terms', () => {
      const titles = [
        'Schadstoffkataster nach BBodSchV',
        'KMF-Sanierung DIN-konform',
        'PAK-Analytik im Altlastenbereich'
      ];

      titles.forEach(title => {
        const result = seoGenerator.generateSlug(title);
        expect(result).toBeValidSEOSlug();
        expect(result.length).toBeGreaterThan(0);
      });
    });
  });
});

describe('SEO Slug Performance', () => {
  let seoGenerator;

  beforeAll(() => {
    // Use the same mock as above
    seoGenerator = {
      generateSlug: (title, category = null, options = {}) => {
        const maxLength = options.maxLength || 60;
        let slug = title.toLowerCase()
          .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
          .replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-')
          .replace(/^-+|-+$/g, '');
        if (category) slug = `${category.toLowerCase().replace(/[^a-z0-9-]/g, '-')}-${slug}`;
        return slug.length > maxLength ? slug.substring(0, maxLength).replace(/-[^-]*$/, '') : slug;
      }
    };
  });

  test('should generate slugs quickly for batch processing', () => {
    const titles = Array.from({ length: 1000 }, (_, i) => `Test Title ${i}`);
    
    const startTime = performance.now();
    const results = titles.map(title => seoGenerator.generateSlug(title));
    const endTime = performance.now();
    
    expect(endTime - startTime).toBeLessThan(1000); // Should complete in under 1 second
    expect(results).toHaveLength(1000);
    results.forEach(result => {
      expect(result).toMatch(/^[a-z0-9-]+$/);
    });
  });
});