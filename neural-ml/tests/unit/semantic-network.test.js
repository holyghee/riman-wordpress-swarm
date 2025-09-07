/**
 * Unit Tests for Semantic Image Network
 */

const { SemanticImageNetwork } = require('../../src/networks/semantic-image-network');
const tf = require('@tensorflow/tfjs-node');

describe('SemanticImageNetwork', () => {
  let network;

  beforeEach(() => {
    network = new SemanticImageNetwork({
      inputSize: [224, 224, 3],
      batchSize: 16,
      learningRate: 0.001
    });
  });

  afterEach(async () => {
    if (network && network.initialized) {
      await network.cleanup();
    }
  });

  describe('initialization', () => {
    test('should initialize successfully', async () => {
      await network.initialize();
      expect(network.initialized).toBe(true);
      expect(network.model).toBeDefined();
      expect(network.featureExtractor).toBeDefined();
      expect(network.semanticAnalyzer).toBeDefined();
    });

    test('should build correct model architecture', async () => {
      await network.initialize();
      expect(network.model.layers.length).toBeGreaterThan(0);
      expect(network.model.inputShape).toEqual([null, 224, 224, 3]);
    });
  });

  describe('image preprocessing', () => {
    test('should preprocess base64 image data', async () => {
      await network.initialize();
      
      const mockBase64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//2Q=';
      const preprocessed = await network.preprocessImage(mockBase64);
      
      expect(preprocessed).toBeDefined();
      expect(preprocessed.shape).toEqual([1, 224, 224, 3]);
      
      preprocessed.dispose();
    });

    test('should handle buffer input', async () => {
      await network.initialize();
      
      const mockBuffer = Buffer.from([1, 2, 3, 4, 5]);
      
      // This should not throw but might not produce valid results
      await expect(async () => {
        const preprocessed = await network.preprocessImage(mockBuffer);
        preprocessed.dispose();
      }).not.toThrow();
    });

    test('should reject invalid input', async () => {
      await network.initialize();
      
      await expect(network.preprocessImage(null))
        .rejects.toThrow('Unsupported image data format');
    });
  });

  describe('semantic analysis', () => {
    test('should analyze image and return semantic information', async () => {
      await network.initialize();
      
      // Create mock image tensor
      const mockImage = tf.randomNormal([1, 224, 224, 3]);
      const mockImageData = await mockImage.data();
      
      const analysis = await network.analyzeImage(Array.from(mockImageData));
      
      expect(analysis).toBeDefined();
      expect(analysis.id).toBeDefined();
      expect(analysis.semantics).toBeDefined();
      expect(analysis.semantics.patterns).toBeDefined();
      expect(analysis.semantics.patterns.objects).toBeDefined();
      expect(analysis.confidence).toBeGreaterThanOrEqual(0);
      expect(analysis.confidence).toBeLessThanOrEqual(1);
      
      mockImage.dispose();
    });

    test('should identify semantic patterns', async () => {
      await network.initialize();
      
      const mockSemanticArray = new Array(1000).fill(0).map(() => Math.random());
      const patterns = network.analyzeSemanticPatterns(mockSemanticArray, {});
      
      expect(patterns.patterns).toBeDefined();
      expect(patterns.patterns.objects).toBeDefined();
      expect(patterns.patterns.scenes).toBeDefined();
      expect(patterns.patterns.concepts).toBeDefined();
      expect(patterns.patterns.emotions).toBeDefined();
      expect(patterns.confidence).toBeGreaterThanOrEqual(0);
    });

    test('should generate semantic description', async () => {
      await network.initialize();
      
      const mockPatterns = {
        objects: [
          { class: 'person', confidence: 0.8 },
          { class: 'car', confidence: 0.6 }
        ],
        scenes: { setting: 'outdoor' },
        emotions: { dominant: 'happy' }
      };
      
      const description = network.generateSemanticDescription(mockPatterns);
      
      expect(description).toContain('person');
      expect(description).toContain('car');
      expect(description).toContain('outdoor');
    });
  });

  describe('object detection', () => {
    test('should identify objects above threshold', async () => {
      await network.initialize();
      
      const mockSemanticArray = new Array(1000).fill(0);
      mockSemanticArray[0] = 0.8; // High confidence object
      mockSemanticArray[1] = 0.6; // Medium confidence object
      mockSemanticArray[2] = 0.3; // Low confidence object
      
      const objects = network.identifyObjects(mockSemanticArray);
      
      expect(objects.length).toBeGreaterThanOrEqual(2);
      expect(objects[0].confidence).toBeGreaterThanOrEqual(0.5);
      expect(objects).toEqual(
        expect.arrayContaining([
          expect.objectContaining({
            class: expect.any(String),
            confidence: expect.any(Number),
            id: expect.any(Number)
          })
        ])
      );
    });
  });

  describe('performance', () => {
    test('should complete analysis within reasonable time', async () => {
      await network.initialize();
      
      const startTime = Date.now();
      const mockImage = tf.randomNormal([1, 224, 224, 3]);
      const mockImageData = await mockImage.data();
      
      await network.analyzeImage(Array.from(mockImageData));
      
      const duration = Date.now() - startTime;
      expect(duration).toBeLessThan(5000); // Should complete within 5 seconds
      
      mockImage.dispose();
    });

    test('should handle multiple concurrent requests', async () => {
      await network.initialize();
      
      const requests = [];
      for (let i = 0; i < 5; i++) {
        const mockImage = tf.randomNormal([1, 224, 224, 3]);
        const mockImageData = await mockImage.data();
        requests.push(network.analyzeImage(Array.from(mockImageData)));
        mockImage.dispose();
      }
      
      const results = await Promise.all(requests);
      expect(results.length).toBe(5);
      results.forEach(result => {
        expect(result.id).toBeDefined();
        expect(result.semantics).toBeDefined();
      });
    });
  });

  describe('cleanup', () => {
    test('should dispose models properly', async () => {
      await network.initialize();
      
      const modelsBefore = tf.memory().numTensors;
      await network.cleanup();
      const modelsAfter = tf.memory().numTensors;
      
      expect(modelsAfter).toBeLessThanOrEqual(modelsBefore);
    });
  });
});