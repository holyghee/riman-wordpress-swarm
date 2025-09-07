/**
 * Neural ML System - Main Entry Point
 * Semantic Image Understanding and Content Mapping with DAA Integration
 */

const express = require('express');
const winston = require('winston');
const { SemanticImageNetwork } = require('./networks/semantic-image-network');
const { ContentMappingEngine } = require('./mapping/content-mapping-engine');
const { TrainingPipeline } = require('./training/training-pipeline');
const { InferenceEngine } = require('./inference/inference-engine');
const { DAACoordinator } = require('./optimization/daa-coordinator');
const { ModelManager } = require('./models/model-manager');
const config = require('../config/neural-config');

class NeuralMLSystem {
  constructor() {
    this.logger = this.setupLogger();
    this.app = express();
    this.initializeComponents();
    this.setupRoutes();
  }

  setupLogger() {
    return winston.createLogger({
      level: 'info',
      format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.json()
      ),
      transports: [
        new winston.transports.File({ filename: 'neural-ml.log' }),
        new winston.transports.Console()
      ]
    });
  }

  async initializeComponents() {
    try {
      this.logger.info('Initializing Neural ML System components...');

      // Initialize core components
      this.modelManager = new ModelManager(config.models);
      this.semanticNetwork = new SemanticImageNetwork(config.semanticNetwork);
      this.contentMapper = new ContentMappingEngine(config.contentMapping);
      this.trainingPipeline = new TrainingPipeline(config.training);
      this.inferenceEngine = new InferenceEngine(config.inference);
      this.daaCoordinator = new DAACoordinator(config.daa);

      // Load pre-trained models
      await this.modelManager.loadModels();
      
      // Initialize networks
      await this.semanticNetwork.initialize();
      await this.contentMapper.initialize();

      // Setup DAA coordination
      await this.daaCoordinator.initialize();

      this.logger.info('Neural ML System initialized successfully');
    } catch (error) {
      this.logger.error('Failed to initialize Neural ML System:', error);
      throw error;
    }
  }

  setupRoutes() {
    this.app.use(express.json({ limit: '50mb' }));
    this.app.use(express.urlencoded({ extended: true, limit: '50mb' }));

    // Health check
    this.app.get('/health', (req, res) => {
      res.json({ status: 'healthy', timestamp: new Date().toISOString() });
    });

    // Semantic image analysis
    this.app.post('/analyze/image', async (req, res) => {
      try {
        const { imageData, options } = req.body;
        const analysis = await this.semanticNetwork.analyzeImage(imageData, options);
        res.json({ success: true, analysis });
      } catch (error) {
        this.logger.error('Image analysis error:', error);
        res.status(500).json({ success: false, error: error.message });
      }
    });

    // Content mapping
    this.app.post('/map/content', async (req, res) => {
      try {
        const { content, context } = req.body;
        const mapping = await this.contentMapper.mapContent(content, context);
        res.json({ success: true, mapping });
      } catch (error) {
        this.logger.error('Content mapping error:', error);
        res.status(500).json({ success: false, error: error.message });
      }
    });

    // Training endpoint
    this.app.post('/train', async (req, res) => {
      try {
        const { trainingData, modelType, options } = req.body;
        const result = await this.trainingPipeline.train(trainingData, modelType, options);
        res.json({ success: true, result });
      } catch (error) {
        this.logger.error('Training error:', error);
        res.status(500).json({ success: false, error: error.message });
      }
    });

    // Inference endpoint
    this.app.post('/predict', async (req, res) => {
      try {
        const { input, modelId, options } = req.body;
        const prediction = await this.inferenceEngine.predict(input, modelId, options);
        res.json({ success: true, prediction });
      } catch (error) {
        this.logger.error('Prediction error:', error);
        res.status(500).json({ success: false, error: error.message });
      }
    });

    // Model management
    this.app.get('/models', async (req, res) => {
      try {
        const models = await this.modelManager.listModels();
        res.json({ success: true, models });
      } catch (error) {
        this.logger.error('Model listing error:', error);
        res.status(500).json({ success: false, error: error.message });
      }
    });

    // DAA coordination status
    this.app.get('/daa/status', async (req, res) => {
      try {
        const status = await this.daaCoordinator.getStatus();
        res.json({ success: true, status });
      } catch (error) {
        this.logger.error('DAA status error:', error);
        res.status(500).json({ success: false, error: error.message });
      }
    });
  }

  async start(port = 3001) {
    try {
      await this.initializeComponents();
      
      this.server = this.app.listen(port, () => {
        this.logger.info(`Neural ML System running on port ${port}`);
      });

      // Setup graceful shutdown
      process.on('SIGTERM', () => this.shutdown());
      process.on('SIGINT', () => this.shutdown());

    } catch (error) {
      this.logger.error('Failed to start Neural ML System:', error);
      throw error;
    }
  }

  async shutdown() {
    this.logger.info('Shutting down Neural ML System...');
    
    if (this.server) {
      this.server.close();
    }

    await this.daaCoordinator?.shutdown();
    await this.modelManager?.cleanup();
    
    this.logger.info('Neural ML System shutdown complete');
    process.exit(0);
  }
}

// Auto-start if run directly
if (require.main === module) {
  const system = new NeuralMLSystem();
  system.start(process.env.PORT || 3001).catch(console.error);
}

module.exports = { NeuralMLSystem };