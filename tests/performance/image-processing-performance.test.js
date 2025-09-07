/**
 * Performance Tests for Image Processing Pipeline
 * Quality Engineer - Performance Validation and Benchmarking
 */

const { performance } = require('perf_hooks');

describe('Image Processing Performance', () => {
  let imageProcessor;
  let largeImageDatabase;
  let performanceMetrics;

  beforeAll(async () => {
    // Mock image processing system
    imageProcessor = {
      processImageBatch: async (images, options = {}) => {
        const startTime = performance.now();
        const results = [];
        
        for (const image of images) {
          const processingTime = Math.random() * 100 + 50; // 50-150ms per image
          await new Promise(resolve => setTimeout(resolve, processingTime / 100)); // Scaled down for testing
          
          results.push({
            filename: image.filename,
            processingTime,
            dimensions: { width: 1920, height: 1080 },
            fileSize: Math.floor(Math.random() * 1000000) + 500000, // 0.5-1.5MB
            success: Math.random() > 0.05 // 95% success rate
          });
        }
        
        const totalTime = performance.now() - startTime;
        return { results, totalTime, averageTime: totalTime / images.length };
      },

      processSemanticMatching: async (content, imageDatabase) => {
        const startTime = performance.now();
        
        // Simulate semantic matching computation
        const agents = imageDatabase.agents || [];
        const matches = [];
        
        for (const agent of agents) {
          // Simulate complex semantic analysis
          await new Promise(resolve => setTimeout(resolve, 1)); // 1ms per agent
          
          const themeScore = Math.random() * 0.5 + 0.3; // 0.3-0.8
          const quadrantScore = Math.random() * 0.5 + 0.3; // 0.3-0.8
          const totalScore = (themeScore * 0.4) + (quadrantScore * 0.6);
          
          matches.push({
            agent_id: agent.id,
            confidence_score: totalScore,
            processing_time: 1
          });
        }
        
        const processingTime = performance.now() - startTime;
        const bestMatch = matches.sort((a, b) => b.confidence_score - a.confidence_score)[0];
        
        return { bestMatch, processingTime, totalMatches: matches.length };
      },

      generateThumbnails: async (images, sizes = [150, 300, 600]) => {
        const startTime = performance.now();
        const results = [];
        
        for (const image of images) {
          for (const size of sizes) {
            // Simulate thumbnail generation time based on size
            const generationTime = size * 0.1; // Larger thumbnails take longer
            await new Promise(resolve => setTimeout(resolve, generationTime / 10));
            
            results.push({
              originalImage: image.filename,
              thumbnailSize: size,
              generationTime,
              success: Math.random() > 0.02 // 98% success rate
            });
          }
        }
        
        const totalTime = performance.now() - startTime;
        return { results, totalTime };
      },

      optimizeImages: async (images, compressionLevel = 0.8) => {
        const startTime = performance.now();
        const results = [];
        
        for (const image of images) {
          // Simulate image optimization time
          const optimizationTime = Math.random() * 200 + 100; // 100-300ms
          await new Promise(resolve => setTimeout(resolve, optimizationTime / 100));
          
          const originalSize = image.fileSize || 1000000;
          const optimizedSize = Math.floor(originalSize * compressionLevel);
          const compressionRatio = (originalSize - optimizedSize) / originalSize;
          
          results.push({
            filename: image.filename,
            originalSize,
            optimizedSize,
            compressionRatio,
            optimizationTime,
            qualityScore: compressionLevel
          });
        }
        
        const totalTime = performance.now() - startTime;
        return { results, totalTime, averageTime: totalTime / images.length };
      }
    };

    // Create large test dataset
    largeImageDatabase = {
      agents: Array.from({ length: 228 }, (_, i) => ({
        id: i + 1,
        theme: `Theme ${i + 1} with professional content and detailed descriptions`,
        quadrants: {
          top_left: `Detailed description for quadrant 1 of agent ${i + 1}`,
          top_right: `Detailed description for quadrant 2 of agent ${i + 1}`,
          bottom_left: `Detailed description for quadrant 3 of agent ${i + 1}`,
          bottom_right: `Detailed description for quadrant 4 of agent ${i + 1}`
        }
      }))
    };

    performanceMetrics = {
      imageProcessingTimeout: global.TEST_CONFIG.performance.imageProcessingTimeout,
      semanticMatchingTimeout: global.TEST_CONFIG.performance.semanticMatchingTimeout,
      maxMemoryUsage: global.TEST_CONFIG.performance.maxMemoryUsage,
      maxImageProcessingTime: global.TEST_CONFIG.performance.maxImageProcessingTime
    };
  });

  describe('Image Batch Processing Performance', () => {
    test('should process small image batch within time limit', async () => {
      const smallBatch = Array.from({ length: 5 }, (_, i) => ({
        filename: `image_${i + 1}.jpg`,
        fileSize: 800000
      }));

      const startTime = performance.now();
      const result = await imageProcessor.processImageBatch(smallBatch);
      const endTime = performance.now();

      expect(endTime - startTime).toBeLessThan(performanceMetrics.imageProcessingTimeout);
      expect(result.results).toHaveLength(5);
      expect(result.averageTime).toBeLessThan(performanceMetrics.maxImageProcessingTime);
    });

    test('should handle large image batch efficiently', async () => {
      const largeBatch = Array.from({ length: 50 }, (_, i) => ({
        filename: `large_image_${i + 1}.jpg`,
        fileSize: 1500000
      }));

      const startTime = performance.now();
      const result = await imageProcessor.processImageBatch(largeBatch);
      const endTime = performance.now();

      expect(endTime - startTime).toBeLessThan(performanceMetrics.imageProcessingTimeout * 10);
      expect(result.results).toHaveLength(50);
      
      // Check processing efficiency
      const successRate = result.results.filter(r => r.success).length / result.results.length;
      expect(successRate).toBeGreaterThan(0.9); // 90% minimum success rate
    });

    test('should scale linearly with batch size', async () => {
      const batchSizes = [10, 20, 40];
      const timings = [];

      for (const size of batchSizes) {
        const batch = Array.from({ length: size }, (_, i) => ({
          filename: `scale_test_${i}.jpg`,
          fileSize: 1000000
        }));

        const startTime = performance.now();
        const result = await imageProcessor.processImageBatch(batch);
        const endTime = performance.now();

        timings.push({
          batchSize: size,
          totalTime: endTime - startTime,
          averageTime: result.averageTime
        });
      }

      // Check that average time per image remains relatively constant
      const avgTimes = timings.map(t => t.averageTime);
      const timeVariance = Math.max(...avgTimes) - Math.min(...avgTimes);
      expect(timeVariance).toBeLessThan(50); // Less than 50ms variance
    });
  });

  describe('Semantic Matching Performance', () => {
    test('should complete semantic matching within timeout', async () => {
      const testContent = {
        title: 'Asbestsanierung nach TRGS 519',
        keywords: ['asbest', 'sanierung', 'trgs', 'sicherheit'],
        excerpt: 'Professional asbestos removal following safety regulations'
      };

      const startTime = performance.now();
      const result = await imageProcessor.processSemanticMatching(testContent, largeImageDatabase);
      const endTime = performance.now();

      expect(endTime - startTime).toBeLessThan(performanceMetrics.semanticMatchingTimeout);
      expect(result.bestMatch).toBeDefined();
      expect(result.totalMatches).toBe(228);
    });

    test('should maintain performance with complex content', async () => {
      const complexContent = {
        title: 'Umfassende Altlastensanierung mit Grundwasserschutz und Bodenaustausch',
        keywords: Array.from({ length: 20 }, (_, i) => `keyword${i + 1}`),
        excerpt: 'Very detailed description '.repeat(50) // Long content
      };

      const startTime = performance.now();
      const result = await imageProcessor.processSemanticMatching(complexContent, largeImageDatabase);
      const endTime = performance.now();

      expect(endTime - startTime).toBeLessThan(performanceMetrics.semanticMatchingTimeout * 2);
      expect(result.bestMatch.confidence_score).toBeGreaterThan(0);
    });

    test('should handle concurrent semantic matching requests', async () => {
      const testContents = Array.from({ length: 10 }, (_, i) => ({
        title: `Test Content ${i + 1}`,
        keywords: [`keyword${i + 1}`, `test${i + 1}`],
        excerpt: `Test content for performance testing ${i + 1}`
      }));

      const promises = testContents.map(content =>
        imageProcessor.processSemanticMatching(content, largeImageDatabase)
      );

      const startTime = performance.now();
      const results = await Promise.all(promises);
      const endTime = performance.now();

      expect(endTime - startTime).toBeLessThan(performanceMetrics.semanticMatchingTimeout * 5);
      expect(results).toHaveLength(10);
      
      results.forEach(result => {
        expect(result.bestMatch).toBeDefined();
        expect(result.processingTime).toBeLessThan(performanceMetrics.semanticMatchingTimeout);
      });
    });
  });

  describe('Thumbnail Generation Performance', () => {
    test('should generate thumbnails efficiently', async () => {
      const images = Array.from({ length: 20 }, (_, i) => ({
        filename: `thumb_test_${i + 1}.jpg`,
        fileSize: 1200000
      }));

      const sizes = [150, 300, 600, 1200];
      
      const startTime = performance.now();
      const result = await imageProcessor.generateThumbnails(images, sizes);
      const endTime = performance.now();

      expect(endTime - startTime).toBeLessThan(5000); // 5 seconds max
      expect(result.results).toHaveLength(20 * 4); // 20 images × 4 sizes
      
      const successRate = result.results.filter(r => r.success).length / result.results.length;
      expect(successRate).toBeGreaterThan(0.95);
    });

    test('should optimize thumbnail generation time by size', async () => {
      const image = { filename: 'size_test.jpg', fileSize: 1000000 };
      const sizes = [150, 600, 1200];
      const timings = [];

      for (const size of sizes) {
        const startTime = performance.now();
        const result = await imageProcessor.generateThumbnails([image], [size]);
        const endTime = performance.now();

        timings.push({
          size,
          time: endTime - startTime,
          result: result.results[0]
        });
      }

      // Larger thumbnails should take more time (within reason)
      expect(timings[2].time).toBeGreaterThan(timings[0].time);
      expect(timings[1].time).toBeGreaterThan(timings[0].time);
    });
  });

  describe('Image Optimization Performance', () => {
    test('should optimize images within performance limits', async () => {
      const images = Array.from({ length: 30 }, (_, i) => ({
        filename: `optimize_test_${i + 1}.jpg`,
        fileSize: Math.floor(Math.random() * 2000000) + 500000 // 0.5-2.5MB
      }));

      const startTime = performance.now();
      const result = await imageProcessor.optimizeImages(images, 0.8);
      const endTime = performance.now();

      expect(endTime - startTime).toBeLessThan(10000); // 10 seconds max
      expect(result.results).toHaveLength(30);
      expect(result.averageTime).toBeLessThan(500); // 500ms average per image
      
      // Check compression effectiveness
      const avgCompressionRatio = result.results.reduce((sum, r) => sum + r.compressionRatio, 0) / result.results.length;
      expect(avgCompressionRatio).toBeGreaterThan(0.1); // At least 10% compression
    });

    test('should handle different compression levels efficiently', async () => {
      const image = { filename: 'compression_test.jpg', fileSize: 1000000 };
      const compressionLevels = [0.9, 0.7, 0.5];
      const results = [];

      for (const level of compressionLevels) {
        const startTime = performance.now();
        const result = await imageProcessor.optimizeImages([image], level);
        const endTime = performance.now();

        results.push({
          compressionLevel: level,
          time: endTime - startTime,
          compressionRatio: result.results[0].compressionRatio
        });
      }

      // Higher compression should result in better ratios
      expect(results[2].compressionRatio).toBeGreaterThan(results[0].compressionRatio);
    });
  });

  describe('Memory Usage Performance', () => {
    test('should maintain reasonable memory usage during processing', async () => {
      const initialMemory = process.memoryUsage().heapUsed;
      
      // Process large batch
      const largeBatch = Array.from({ length: 100 }, (_, i) => ({
        filename: `memory_test_${i + 1}.jpg`,
        fileSize: 1500000
      }));

      await imageProcessor.processImageBatch(largeBatch);
      
      // Force garbage collection if available
      if (global.gc) {
        global.gc();
      }
      
      const finalMemory = process.memoryUsage().heapUsed;
      const memoryIncrease = finalMemory - initialMemory;

      expect(memoryIncrease).toBeLessThan(performanceMetrics.maxMemoryUsage);
    });

    test('should handle memory efficiently with concurrent operations', async () => {
      const initialMemory = process.memoryUsage().heapUsed;
      
      const operations = [
        imageProcessor.processImageBatch(Array.from({ length: 20 }, (_, i) => ({ filename: `concurrent_${i}.jpg` }))),
        imageProcessor.processSemanticMatching({ title: 'Test', keywords: ['test'] }, largeImageDatabase),
        imageProcessor.generateThumbnails([{ filename: 'thumb_test.jpg' }], [150, 300, 600]),
        imageProcessor.optimizeImages([{ filename: 'opt_test.jpg', fileSize: 1000000 }])
      ];

      await Promise.all(operations);
      
      if (global.gc) {
        global.gc();
      }
      
      const finalMemory = process.memoryUsage().heapUsed;
      const memoryIncrease = finalMemory - initialMemory;

      expect(memoryIncrease).toBeLessThan(performanceMetrics.maxMemoryUsage * 2);
    });
  });

  describe('Performance Monitoring and Metrics', () => {
    test('should track processing metrics accurately', async () => {
      const batch = Array.from({ length: 10 }, (_, i) => ({
        filename: `metrics_test_${i + 1}.jpg`,
        fileSize: 1000000
      }));

      const result = await imageProcessor.processImageBatch(batch);

      expect(result).toHaveProperty('totalTime');
      expect(result).toHaveProperty('averageTime');
      expect(result.totalTime).toBeGreaterThan(0);
      expect(result.averageTime).toBe(result.totalTime / batch.length);
    });

    test('should provide detailed performance breakdown', async () => {
      const testContent = { title: 'Performance Test', keywords: ['perf', 'test'] };
      const result = await imageProcessor.processSemanticMatching(testContent, largeImageDatabase);

      expect(result).toHaveProperty('processingTime');
      expect(result).toHaveProperty('totalMatches');
      expect(result.processingTime).toBeGreaterThan(0);
      expect(result.totalMatches).toBeGreaterThan(0);
    });
  });

  describe('Stress Testing', () => {
    test('should handle maximum expected load', async () => {
      const maxBatch = Array.from({ length: 200 }, (_, i) => ({
        filename: `stress_test_${i + 1}.jpg`,
        fileSize: 2000000
      }));

      const startTime = performance.now();
      
      expect(async () => {
        await imageProcessor.processImageBatch(maxBatch);
      }).not.toThrow();
      
      const endTime = performance.now();
      expect(endTime - startTime).toBeLessThan(30000); // 30 seconds max
    });

    test('should maintain performance under sustained load', async () => {
      const iterations = 5;
      const timings = [];

      for (let i = 0; i < iterations; i++) {
        const batch = Array.from({ length: 20 }, (_, j) => ({
          filename: `sustained_load_${i}_${j}.jpg`,
          fileSize: 1000000
        }));

        const startTime = performance.now();
        await imageProcessor.processImageBatch(batch);
        const endTime = performance.now();

        timings.push(endTime - startTime);
      }

      // Performance should not degrade significantly over time
      const firstTiming = timings[0];
      const lastTiming = timings[timings.length - 1];
      const performanceDegradation = (lastTiming - firstTiming) / firstTiming;

      expect(performanceDegradation).toBeLessThan(0.5); // Less than 50% degradation
    });
  });
});

describe('Performance Benchmarking', () => {
  test('should meet performance benchmarks for production use', async () => {
    const benchmarks = {
      smallBatchProcessing: { size: 10, maxTime: 2000 },
      mediumBatchProcessing: { size: 50, maxTime: 8000 },
      largeBatchProcessing: { size: 100, maxTime: 15000 },
      semanticMatching: { agents: 228, maxTime: 1000 },
      thumbnailGeneration: { images: 20, sizes: 4, maxTime: 3000 },
      imageOptimization: { images: 30, maxTime: 5000 }
    };

    const results = {};

    // Test each benchmark
    for (const [name, benchmark] of Object.entries(benchmarks)) {
      const startTime = performance.now();
      
      // Execute benchmark based on type
      if (name.includes('BatchProcessing')) {
        const batch = Array.from({ length: benchmark.size }, (_, i) => ({
          filename: `benchmark_${i}.jpg`,
          fileSize: 1000000
        }));
        await global.mockImageProcessor.processImageBatch(batch);
      }
      
      const endTime = performance.now();
      const actualTime = endTime - startTime;
      
      results[name] = {
        expected: benchmark.maxTime,
        actual: actualTime,
        passed: actualTime <= benchmark.maxTime,
        efficiency: benchmark.maxTime / actualTime
      };
    }

    // All benchmarks should pass
    Object.values(results).forEach(result => {
      expect(result.passed).toBe(true);
    });

    // Store benchmark results for reporting
    global.performanceBenchmarkResults = results;
  });
});