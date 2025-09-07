/**
 * Integration Tests for Neural ML System
 */

const { NeuralMLSystem } = require('../../src/index');
const request = require('supertest');

describe('NeuralMLSystem Integration', () => {
  let neuralSystem;
  let server;

  beforeAll(async () => {
    neuralSystem = new NeuralMLSystem();
    await neuralSystem.start(0); // Use random port for testing
    server = neuralSystem.app;
  }, 30000); // Increase timeout for initialization

  afterAll(async () => {
    if (neuralSystem) {
      await neuralSystem.shutdown();
    }
  });

  describe('health check', () => {
    test('should return healthy status', async () => {
      const response = await request(server)
        .get('/health')
        .expect(200);
      
      expect(response.body.status).toBe('healthy');
      expect(response.body.timestamp).toBeDefined();
    });
  });

  describe('image analysis endpoint', () => {
    test('should analyze image data', async () => {
      const mockImageData = new Array(224 * 224 * 3).fill(0.5);
      
      const response = await request(server)
        .post('/analyze/image')
        .send({
          imageData: mockImageData,
          options: { enhanceResults: true }
        })
        .expect(200);
      
      expect(response.body.success).toBe(true);
      expect(response.body.analysis).toBeDefined();
      expect(response.body.analysis.id).toBeDefined();
      expect(response.body.analysis.semantics).toBeDefined();
    }, 15000);

    test('should handle missing image data', async () => {
      const response = await request(server)
        .post('/analyze/image')
        .send({})
        .expect(500);
      
      expect(response.body.success).toBe(false);
      expect(response.body.error).toBeDefined();
    });
  });

  describe('content mapping endpoint', () => {
    test('should map content semantically', async () => {
      const response = await request(server)
        .post('/map/content')
        .send({
          content: 'This is a test article about machine learning and artificial intelligence.',
          context: { type: 'article' }
        })
        .expect(200);
      
      expect(response.body.success).toBe(true);
      expect(response.body.mapping).toBeDefined();
      expect(response.body.mapping.id).toBeDefined();
      expect(response.body.mapping.semanticMap).toBeDefined();
    }, 10000);

    test('should handle missing content', async () => {
      const response = await request(server)
        .post('/map/content')
        .send({})
        .expect(500);
      
      expect(response.body.success).toBe(false);
    });
  });

  describe('model prediction endpoint', () => {
    test('should make predictions', async () => {
      const mockInput = new Array(256).fill(0.1);
      
      const response = await request(server)
        .post('/predict')
        .send({
          input: mockInput,
          modelId: 'test-model',
          options: { batchSize: 1 }
        })
        .expect(200);
      
      expect(response.body.success).toBe(true);
      expect(response.body.prediction).toBeDefined();
    }, 10000);
  });

  describe('training endpoint', () => {
    test('should initiate training', async () => {
      const mockTrainingData = {
        images: [new Array(224 * 224 * 3).fill(0.5)],
        labels: [[1, 0, 0, 0, 0]]
      };
      
      const response = await request(server)
        .post('/train')
        .send({
          trainingData: mockTrainingData,
          modelType: 'semantic-image',
          options: { epochs: 1, batchSize: 1 }
        })
        .expect(200);
      
      expect(response.body.success).toBe(true);
      expect(response.body.result).toBeDefined();
      expect(response.body.result.jobId).toBeDefined();
    }, 30000);
  });

  describe('model management', () => {
    test('should list available models', async () => {
      const response = await request(server)
        .get('/models')
        .expect(200);
      
      expect(response.body.success).toBe(true);
      expect(Array.isArray(response.body.models)).toBe(true);
    });
  });

  describe('DAA coordination status', () => {
    test('should return coordination status', async () => {
      const response = await request(server)
        .get('/daa/status')
        .expect(200);
      
      expect(response.body.success).toBe(true);
      expect(response.body.status).toBeDefined();
      expect(response.body.status.initialized).toBeDefined();
    });
  });

  describe('error handling', () => {
    test('should handle invalid endpoints gracefully', async () => {
      await request(server)
        .get('/invalid-endpoint')
        .expect(404);
    });

    test('should handle malformed requests', async () => {
      await request(server)
        .post('/analyze/image')
        .send('invalid-json')
        .expect(400);
    });
  });

  describe('performance', () => {
    test('should handle concurrent requests', async () => {
      const requests = [];
      
      for (let i = 0; i < 5; i++) {
        requests.push(
          request(server)
            .get('/health')
            .expect(200)
        );
      }
      
      const results = await Promise.all(requests);
      expect(results.length).toBe(5);
      results.forEach(result => {
        expect(result.body.status).toBe('healthy');
      });
    });

    test('should respond within acceptable time limits', async () => {
      const startTime = Date.now();
      
      await request(server)
        .get('/health')
        .expect(200);
      
      const duration = Date.now() - startTime;
      expect(duration).toBeLessThan(1000); // Should respond within 1 second
    });
  });
});