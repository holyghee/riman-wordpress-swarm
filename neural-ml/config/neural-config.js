/**
 * Neural ML System Configuration
 */

const path = require('path');

module.exports = {
  // Model configurations
  models: {
    modelsDir: path.join(__dirname, '..', 'models'),
    maxVersions: 10,
    compressionEnabled: true,
    backupEnabled: true,
    validationEnabled: true
  },

  // Semantic Image Network configuration
  semanticNetwork: {
    inputSize: [224, 224, 3],
    batchSize: 32,
    learningRate: 0.001,
    dropout: 0.5,
    numClasses: 1000
  },

  // Content Mapping configuration
  contentMapping: {
    embeddingDim: 256,
    maxSequenceLength: 512,
    vocabularySize: 50000,
    learningRate: 0.001,
    batchSize: 16,
    numAttentionHeads: 8
  },

  // Training Pipeline configuration
  training: {
    batchSize: 32,
    epochs: 100,
    learningRate: 0.001,
    validationSplit: 0.2,
    earlyStopping: {
      monitor: 'val_loss',
      patience: 10,
      restoreBestWeights: true
    },
    checkpointFreq: 5,
    dataAugmentation: true,
    transferLearning: true
  },

  // Inference Engine configuration
  inference: {
    batchSize: 32,
    maxConcurrentRequests: 10,
    cacheSize: 1000,
    warmupIterations: 5,
    quantization: false,
    optimization: 'speed',
    gpuAcceleration: false
  },

  // DAA Coordinator configuration
  daa: {
    coordinationMode: 'adaptive',
    optimizationTargets: ['performance', 'accuracy', 'efficiency'],
    adaptationInterval: 30000,
    resourceThresholds: {
      cpu: 80,
      memory: 85,
      latency: 100
    },
    neuralOptimization: {
      enabled: true,
      learningRate: 0.001,
      adaptationRate: 0.1
    }
  },

  // WordPress Integration configuration
  wordpress: {
    wordpressUrl: process.env.WORDPRESS_URL || 'http://localhost',
    apiEndpoint: '/wp-json/neural-ml/v1',
    authToken: process.env.WP_AUTH_TOKEN,
    enableHooks: true,
    cacheResults: true,
    batchProcessing: true
  },

  // System configuration
  system: {
    port: process.env.PORT || 3001,
    environment: process.env.NODE_ENV || 'development',
    logLevel: process.env.LOG_LEVEL || 'info',
    dataDir: path.join(__dirname, '..', 'data'),
    tempDir: path.join(__dirname, '..', 'temp')
  },

  // Performance monitoring
  monitoring: {
    enabled: true,
    metricsInterval: 5000,
    performanceInterval: 10000,
    alertThresholds: {
      latency: 1000,
      errorRate: 0.05,
      memoryUsage: 0.9
    }
  },

  // Security configuration
  security: {
    rateLimit: {
      windowMs: 15 * 60 * 1000, // 15 minutes
      max: 100 // limit each IP to 100 requests per windowMs
    },
    cors: {
      origin: process.env.ALLOWED_ORIGINS?.split(',') || ['http://localhost'],
      methods: ['GET', 'POST', 'PUT', 'DELETE'],
      credentials: true
    },
    auth: {
      jwtSecret: process.env.JWT_SECRET || 'default-secret',
      jwtExpiry: '1h'
    }
  }
};