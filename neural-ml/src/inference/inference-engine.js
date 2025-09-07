/**
 * Inference Engine with Performance Optimization
 * High-performance neural network inference system
 */

const tf = require('@tensorflow/tfjs-node');
const winston = require('winston');
const { v4: uuidv4 } = require('uuid');
const path = require('path');
const fs = require('fs').promises;

class InferenceEngine {
  constructor(config = {}) {
    this.config = {
      batchSize: 32,
      maxConcurrentRequests: 10,
      cacheSize: 1000,
      warmupIterations: 5,
      quantization: false,
      optimization: 'speed', // 'speed' or 'accuracy'
      gpuAcceleration: false,
      ...config
    };

    this.logger = winston.createLogger({
      level: 'info',
      format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.json()
      ),
      defaultMeta: { service: 'inference-engine' },
      transports: [
        new winston.transports.Console(),
        new winston.transports.File({ filename: 'inference.log' })
      ]
    });

    this.models = new Map();
    this.modelMetadata = new Map();
    this.cache = new Map();
    this.requestQueue = [];
    this.activeRequests = new Set();
    this.metrics = {
      totalRequests: 0,
      averageLatency: 0,
      cacheHitRate: 0,
      errorRate: 0
    };
    this.initialized = false;
  }

  async initialize() {
    try {
      this.logger.info('Initializing Inference Engine...');
      
      await this.setupTensorFlowOptimizations();
      await this.loadAvailableModels();
      await this.setupRequestProcessing();
      await this.setupMetricsCollection();
      
      this.initialized = true;
      this.logger.info('Inference Engine initialized successfully');
    } catch (error) {
      this.logger.error('Failed to initialize Inference Engine:', error);
      throw error;
    }
  }

  async setupTensorFlowOptimizations() {
    // Configure TensorFlow.js for optimal performance
    if (this.config.gpuAcceleration) {
      try {
        await tf.setBackend('tensorflow');
        this.logger.info('GPU acceleration enabled');
      } catch (error) {
        this.logger.warn('GPU acceleration not available, falling back to CPU');
        await tf.setBackend('cpu');
      }
    } else {
      await tf.setBackend('cpu');
    }

    // Set memory growth
    tf.env().set('WEBGL_CPU_FORWARD', false);
    tf.env().set('WEBGL_PACK', true);
    
    this.logger.info(`TensorFlow backend: ${tf.getBackend()}`);
  }

  async loadAvailableModels() {
    try {
      const modelsDir = path.join('models', 'trained');
      
      try {
        const modelDirs = await fs.readdir(modelsDir);
        
        for (const modelDir of modelDirs) {
          const modelPath = path.join(modelsDir, modelDir);
          const metadataPath = path.join(modelPath, 'metadata.json');
          
          try {
            const metadata = JSON.parse(await fs.readFile(metadataPath, 'utf8'));
            this.modelMetadata.set(modelDir, metadata);
            this.logger.info(`Model metadata loaded: ${modelDir}`);
          } catch (error) {
            this.logger.warn(`Failed to load metadata for model: ${modelDir}`);
          }
        }
      } catch (error) {
        this.logger.info('No trained models directory found, starting with empty model registry');
      }
      
    } catch (error) {
      this.logger.error('Failed to load available models:', error);
    }
  }

  async setupRequestProcessing() {
    // Start request processing loop
    this.processingInterval = setInterval(() => {
      this.processRequestQueue();
    }, 10); // Process queue every 10ms

    this.logger.info('Request processing setup complete');
  }

  async setupMetricsCollection() {
    // Setup metrics collection
    this.metricsInterval = setInterval(() => {
      this.updateMetrics();
    }, 5000); // Update metrics every 5 seconds

    this.logger.info('Metrics collection setup complete');
  }

  async loadModel(modelId, options = {}) {
    try {
      if (this.models.has(modelId)) {
        this.logger.info(`Model already loaded: ${modelId}`);
        return this.models.get(modelId);
      }

      this.logger.info(`Loading model: ${modelId}`);
      const startTime = Date.now();

      // Determine model path
      let modelPath;
      if (options.modelPath) {
        modelPath = options.modelPath;
      } else {
        const metadata = this.modelMetadata.get(modelId);
        if (!metadata) {
          throw new Error(`Model metadata not found: ${modelId}`);
        }
        modelPath = path.join('models', 'trained', modelId);
      }

      // Load model
      const model = await tf.loadLayersModel(`file://${modelPath}/model.json`);

      // Apply optimizations
      if (this.config.quantization) {
        // In production, implement model quantization
        this.logger.info(`Applying quantization to model: ${modelId}`);
      }

      // Warm up model
      await this.warmupModel(model, modelId);

      // Store model
      this.models.set(modelId, {
        model: model,
        loadTime: Date.now() - startTime,
        metadata: this.modelMetadata.get(modelId),
        requestCount: 0,
        averageLatency: 0
      });

      this.logger.info(`Model loaded successfully: ${modelId} (${Date.now() - startTime}ms)`);
      return model;

    } catch (error) {
      this.logger.error(`Failed to load model ${modelId}:`, error);
      throw error;
    }
  }

  async warmupModel(model, modelId) {
    try {
      this.logger.info(`Warming up model: ${modelId}`);

      // Create dummy input based on model input shape
      const inputShape = model.inputShape;
      const dummyInput = tf.randomNormal(inputShape);

      // Run warmup iterations
      for (let i = 0; i < this.config.warmupIterations; i++) {
        const prediction = await model.predict(dummyInput);
        prediction.dispose();
      }

      dummyInput.dispose();
      this.logger.info(`Model warmup completed: ${modelId}`);

    } catch (error) {
      this.logger.warn(`Model warmup failed: ${modelId}`, error);
    }
  }

  async predict(input, modelId, options = {}) {
    if (!this.initialized) {
      throw new Error('Inference Engine not initialized');
    }

    const requestId = uuidv4();
    const startTime = Date.now();

    try {
      this.logger.debug(`Prediction request: ${requestId} for model: ${modelId}`);

      // Check cache first
      const cacheKey = this.generateCacheKey(input, modelId, options);
      if (this.cache.has(cacheKey)) {
        this.metrics.totalRequests++;
        this.updateCacheHitRate(true);
        
        const cachedResult = this.cache.get(cacheKey);
        this.logger.debug(`Cache hit for request: ${requestId}`);
        
        return {
          requestId,
          prediction: cachedResult.prediction,
          confidence: cachedResult.confidence,
          latency: Date.now() - startTime,
          cached: true,
          modelId
        };
      }

      // Queue request if at capacity
      if (this.activeRequests.size >= this.config.maxConcurrentRequests) {
        return new Promise((resolve, reject) => {
          this.requestQueue.push({
            requestId,
            input,
            modelId,
            options,
            startTime,
            resolve,
            reject
          });
        });
      }

      // Process request immediately
      return await this.processRequest(requestId, input, modelId, options, startTime);

    } catch (error) {
      this.logger.error(`Prediction failed for request ${requestId}:`, error);
      this.metrics.errorRate = (this.metrics.errorRate * this.metrics.totalRequests + 1) / (this.metrics.totalRequests + 1);
      this.metrics.totalRequests++;
      throw error;
    }
  }

  async processRequest(requestId, input, modelId, options, startTime) {
    this.activeRequests.add(requestId);

    try {
      // Load model if not already loaded
      const modelInfo = this.models.get(modelId) || await this.loadModel(modelId, options);
      const model = modelInfo.model;

      // Preprocess input
      const preprocessedInput = await this.preprocessInput(input, modelId, options);

      // Run inference
      const rawPrediction = await this.runInference(model, preprocessedInput, options);

      // Post-process results
      const processedResult = await this.postprocessOutput(rawPrediction, modelId, options);

      // Calculate metrics
      const latency = Date.now() - startTime;
      this.updateModelMetrics(modelId, latency);

      // Cache result
      const cacheKey = this.generateCacheKey(input, modelId, options);
      this.cacheResult(cacheKey, processedResult);

      // Clean up tensors
      preprocessedInput.dispose();
      rawPrediction.dispose();

      this.logger.debug(`Prediction completed: ${requestId} (${latency}ms)`);

      return {
        requestId,
        prediction: processedResult.prediction,
        confidence: processedResult.confidence,
        latency,
        cached: false,
        modelId,
        metadata: processedResult.metadata
      };

    } finally {
      this.activeRequests.delete(requestId);
    }
  }

  async preprocessInput(input, modelId, options) {
    try {
      // Get model metadata for preprocessing requirements
      const modelInfo = this.models.get(modelId);
      const metadata = modelInfo?.metadata;

      if (!metadata) {
        throw new Error(`Model metadata not available for preprocessing: ${modelId}`);
      }

      let tensor;

      if (metadata.modelType === 'semantic-image' || metadata.modelType === 'image-classification') {
        tensor = await this.preprocessImage(input, metadata);
      } else if (metadata.modelType === 'content-mapping' || metadata.modelType === 'text-classification') {
        tensor = await this.preprocessText(input, metadata);
      } else if (metadata.modelType === 'multimodal') {
        tensor = await this.preprocessMultimodal(input, metadata);
      } else {
        // Generic preprocessing
        tensor = tf.tensor(input);
      }

      return tensor;

    } catch (error) {
      this.logger.error('Input preprocessing failed:', error);
      throw error;
    }
  }

  async preprocessImage(input, metadata) {
    try {
      let imageData;

      if (typeof input === 'string') {
        // Handle base64 or URL
        if (input.startsWith('data:')) {
          const base64Data = input.split(',')[1];
          imageData = Buffer.from(base64Data, 'base64');
        } else {
          // URL - would need to fetch in real implementation
          throw new Error('URL image input not implemented in this example');
        }
      } else if (Buffer.isBuffer(input)) {
        imageData = input;
      } else if (Array.isArray(input)) {
        // Direct array input
        return tf.tensor(input).expandDims(0);
      } else {
        throw new Error('Unsupported image input format');
      }

      // In real implementation, use sharp or similar for image processing
      // For now, create mock tensor
      const inputShape = metadata.inputShape || [224, 224, 3];
      const mockImage = tf.randomNormal([1, ...inputShape]);
      
      return mockImage;

    } catch (error) {
      this.logger.error('Image preprocessing failed:', error);
      throw error;
    }
  }

  async preprocessText(input, metadata) {
    try {
      let textData;

      if (typeof input === 'string') {
        textData = input;
      } else if (input.text) {
        textData = input.text;
      } else {
        throw new Error('Invalid text input format');
      }

      // In real implementation, tokenize and convert to embeddings
      // For now, create mock tensor
      const sequenceLength = metadata.sequenceLength || 512;
      const mockEmbedding = tf.randomNormal([1, sequenceLength]);
      
      return mockEmbedding;

    } catch (error) {
      this.logger.error('Text preprocessing failed:', error);
      throw error;
    }
  }

  async preprocessMultimodal(input, metadata) {
    try {
      if (!input.image || !input.text) {
        throw new Error('Multimodal input requires both image and text');
      }

      const imageTensor = await this.preprocessImage(input.image, metadata);
      const textTensor = await this.preprocessText(input.text, metadata);

      return [imageTensor, textTensor];

    } catch (error) {
      this.logger.error('Multimodal preprocessing failed:', error);
      throw error;
    }
  }

  async runInference(model, preprocessedInput, options) {
    try {
      const prediction = await model.predict(preprocessedInput, {
        batchSize: options.batchSize || this.config.batchSize
      });

      return prediction;

    } catch (error) {
      this.logger.error('Inference execution failed:', error);
      throw error;
    }
  }

  async postprocessOutput(rawPrediction, modelId, options) {
    try {
      const predictionData = await rawPrediction.data();
      const predictionArray = Array.from(predictionData);

      // Get model metadata for post-processing
      const modelInfo = this.models.get(modelId);
      const metadata = modelInfo?.metadata;

      let processedResult;

      if (metadata?.modelType === 'semantic-image') {
        processedResult = this.postprocessImagePrediction(predictionArray, metadata);
      } else if (metadata?.modelType === 'content-mapping') {
        processedResult = this.postprocessTextPrediction(predictionArray, metadata);
      } else if (metadata?.modelType === 'multimodal') {
        processedResult = this.postprocessMultimodalPrediction(predictionArray, metadata);
      } else {
        // Generic post-processing
        processedResult = {
          prediction: predictionArray,
          confidence: Math.max(...predictionArray),
          metadata: { rawOutput: predictionArray }
        };
      }

      return processedResult;

    } catch (error) {
      this.logger.error('Output post-processing failed:', error);
      throw error;
    }
  }

  postprocessImagePrediction(predictionArray, metadata) {
    // Find top predictions
    const topK = 5;
    const indexed = predictionArray.map((value, index) => ({ value, index }));
    const sorted = indexed.sort((a, b) => b.value - a.value);
    const topPredictions = sorted.slice(0, topK);

    return {
      prediction: {
        topClass: topPredictions[0].index,
        topPredictions: topPredictions.map(p => ({
          classId: p.index,
          className: `class_${p.index}`,
          confidence: p.value
        }))
      },
      confidence: topPredictions[0].value,
      metadata: {
        modelType: 'semantic-image',
        totalClasses: predictionArray.length
      }
    };
  }

  postprocessTextPrediction(predictionArray, metadata) {
    // Multi-label classification result
    const threshold = 0.5;
    const activeLabels = predictionArray
      .map((value, index) => ({ value, index }))
      .filter(item => item.value > threshold)
      .sort((a, b) => b.value - a.value);

    return {
      prediction: {
        activeLabels: activeLabels.map(item => ({
          labelId: item.index,
          labelName: `label_${item.index}`,
          confidence: item.value
        })),
        scores: predictionArray
      },
      confidence: activeLabels.length > 0 ? activeLabels[0].value : 0,
      metadata: {
        modelType: 'content-mapping',
        threshold: threshold,
        totalLabels: predictionArray.length
      }
    };
  }

  postprocessMultimodalPrediction(predictionArray, metadata) {
    // Combined multimodal result
    const topPrediction = Math.max(...predictionArray);
    const topIndex = predictionArray.indexOf(topPrediction);

    return {
      prediction: {
        classId: topIndex,
        className: `multimodal_class_${topIndex}`,
        allScores: predictionArray
      },
      confidence: topPrediction,
      metadata: {
        modelType: 'multimodal',
        totalClasses: predictionArray.length
      }
    };
  }

  async processRequestQueue() {
    while (this.requestQueue.length > 0 && this.activeRequests.size < this.config.maxConcurrentRequests) {
      const request = this.requestQueue.shift();
      
      try {
        const result = await this.processRequest(
          request.requestId,
          request.input,
          request.modelId,
          request.options,
          request.startTime
        );
        request.resolve(result);
      } catch (error) {
        request.reject(error);
      }
    }
  }

  generateCacheKey(input, modelId, options) {
    // Simple cache key generation - in production, use more sophisticated hashing
    const inputHash = typeof input === 'string' ? input.slice(0, 100) : JSON.stringify(input).slice(0, 100);
    const optionsHash = JSON.stringify(options);
    return `${modelId}:${inputHash}:${optionsHash}`;
  }

  cacheResult(cacheKey, result) {
    // Simple LRU-like cache management
    if (this.cache.size >= this.config.cacheSize) {
      const firstKey = this.cache.keys().next().value;
      this.cache.delete(firstKey);
    }

    this.cache.set(cacheKey, {
      prediction: result.prediction,
      confidence: result.confidence,
      timestamp: Date.now()
    });
  }

  updateModelMetrics(modelId, latency) {
    const modelInfo = this.models.get(modelId);
    if (modelInfo) {
      modelInfo.requestCount++;
      modelInfo.averageLatency = (modelInfo.averageLatency * (modelInfo.requestCount - 1) + latency) / modelInfo.requestCount;
    }

    // Update global metrics
    this.metrics.totalRequests++;
    this.metrics.averageLatency = (this.metrics.averageLatency * (this.metrics.totalRequests - 1) + latency) / this.metrics.totalRequests;
  }

  updateCacheHitRate(hit) {
    const previousHits = this.metrics.cacheHitRate * (this.metrics.totalRequests || 1);
    this.metrics.cacheHitRate = (previousHits + (hit ? 1 : 0)) / ((this.metrics.totalRequests || 1) + 1);
  }

  updateMetrics() {
    // Update system metrics
    this.metrics.activeRequests = this.activeRequests.size;
    this.metrics.queuedRequests = this.requestQueue.length;
    this.metrics.loadedModels = this.models.size;
    this.metrics.cacheSize = this.cache.size;
    this.metrics.memoryUsage = process.memoryUsage();

    this.logger.debug('Inference Engine Metrics:', this.metrics);
  }

  getMetrics() {
    return {
      ...this.metrics,
      models: Array.from(this.models.entries()).map(([id, info]) => ({
        modelId: id,
        requestCount: info.requestCount,
        averageLatency: info.averageLatency,
        loadTime: info.loadTime
      }))
    };
  }

  getModelInfo(modelId) {
    return this.models.get(modelId);
  }

  listModels() {
    return Array.from(this.models.keys());
  }

  async unloadModel(modelId) {
    const modelInfo = this.models.get(modelId);
    if (modelInfo) {
      modelInfo.model.dispose();
      this.models.delete(modelId);
      this.logger.info(`Model unloaded: ${modelId}`);
    }
  }

  async cleanup() {
    // Clear intervals
    if (this.processingInterval) {
      clearInterval(this.processingInterval);
    }
    if (this.metricsInterval) {
      clearInterval(this.metricsInterval);
    }

    // Dispose all models
    for (const [modelId, modelInfo] of this.models) {
      modelInfo.model.dispose();
      this.logger.info(`Model disposed: ${modelId}`);
    }

    this.models.clear();
    this.cache.clear();
    this.requestQueue.length = 0;
    this.activeRequests.clear();

    this.logger.info('Inference Engine cleanup completed');
  }
}

module.exports = { InferenceEngine };