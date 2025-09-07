/**
 * Training Pipeline for Continuous Learning
 * Advanced ML training system with incremental and transfer learning
 */

const tf = require('@tensorflow/tfjs-node');
const winston = require('winston');
const fs = require('fs').promises;
const path = require('path');
const { v4: uuidv4 } = require('uuid');
const { EventEmitter } = require('events');

class TrainingPipeline extends EventEmitter {
  constructor(config = {}) {
    super();
    
    this.config = {
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
      transferLearning: true,
      ...config
    };

    this.logger = winston.createLogger({
      level: 'info',
      format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.json()
      ),
      defaultMeta: { service: 'training-pipeline' },
      transports: [
        new winston.transports.Console(),
        new winston.transports.File({ filename: 'training.log' })
      ]
    });

    this.trainingJobs = new Map();
    this.models = new Map();
    this.dataPipeline = null;
    this.initialized = false;
  }

  async initialize() {
    try {
      this.logger.info('Initializing Training Pipeline...');
      
      await this.setupDataPipeline();
      await this.setupModelRegistry();
      await this.setupMetricsTracking();
      
      this.initialized = true;
      this.logger.info('Training Pipeline initialized successfully');
    } catch (error) {
      this.logger.error('Failed to initialize Training Pipeline:', error);
      throw error;
    }
  }

  async setupDataPipeline() {
    this.dataPipeline = {
      preprocessors: new Map(),
      augmentors: new Map(),
      validators: new Map()
    };

    // Register default preprocessors
    this.registerPreprocessor('image', this.preprocessImage.bind(this));
    this.registerPreprocessor('text', this.preprocessText.bind(this));
    this.registerPreprocessor('mixed', this.preprocessMixed.bind(this));

    // Register augmentation functions
    this.registerAugmentor('image', this.augmentImage.bind(this));
    this.registerAugmentor('text', this.augmentText.bind(this));

    this.logger.info('Data pipeline setup complete');
  }

  async setupModelRegistry() {
    this.modelRegistry = {
      'semantic-image': {
        factory: this.createSemanticImageModel.bind(this),
        type: 'image-classification',
        inputShape: [224, 224, 3]
      },
      'content-mapping': {
        factory: this.createContentMappingModel.bind(this),
        type: 'text-classification',
        inputShape: [512]
      },
      'multimodal': {
        factory: this.createMultimodalModel.bind(this),
        type: 'multimodal',
        inputShapes: [[224, 224, 3], [512]]
      }
    };

    this.logger.info('Model registry setup complete');
  }

  async setupMetricsTracking() {
    this.metricsTracker = {
      trainingHistory: new Map(),
      validationHistory: new Map(),
      modelPerformance: new Map(),
      resourceUsage: new Map()
    };

    this.logger.info('Metrics tracking setup complete');
  }

  async train(trainingData, modelType, options = {}) {
    if (!this.initialized) {
      throw new Error('Training Pipeline not initialized');
    }

    const jobId = uuidv4();
    this.logger.info(`Starting training job: ${jobId} for model type: ${modelType}`);

    try {
      // Create training job
      const job = {
        id: jobId,
        modelType,
        status: 'preparing',
        startTime: new Date().toISOString(),
        options: { ...this.config, ...options },
        progress: 0,
        currentEpoch: 0,
        metrics: {}
      };

      this.trainingJobs.set(jobId, job);
      this.emit('jobStarted', job);

      // Prepare data
      job.status = 'preprocessing';
      this.emit('jobProgress', job);
      
      const { trainData, valData } = await this.prepareTrainingData(trainingData, modelType, job.options);

      // Create or load model
      job.status = 'building_model';
      this.emit('jobProgress', job);
      
      const model = await this.createOrLoadModel(modelType, job.options);
      
      // Setup training callbacks
      const callbacks = this.setupTrainingCallbacks(jobId, job);

      // Train model
      job.status = 'training';
      job.model = model;
      this.emit('jobProgress', job);

      const history = await this.trainModel(model, trainData, valData, job.options, callbacks);
      
      // Evaluate model
      job.status = 'evaluating';
      this.emit('jobProgress', job);
      
      const evaluation = await this.evaluateModel(model, valData);
      
      // Save model and artifacts
      job.status = 'saving';
      this.emit('jobProgress', job);
      
      const modelPath = await this.saveModel(model, jobId, modelType);
      
      // Complete job
      job.status = 'completed';
      job.endTime = new Date().toISOString();
      job.modelPath = modelPath;
      job.evaluation = evaluation;
      job.history = history;
      job.progress = 100;
      
      this.emit('jobCompleted', job);
      this.logger.info(`Training job completed: ${jobId}`);

      return {
        jobId,
        modelPath,
        evaluation,
        history: this.formatHistory(history),
        metrics: job.metrics
      };

    } catch (error) {
      const job = this.trainingJobs.get(jobId);
      if (job) {
        job.status = 'failed';
        job.error = error.message;
        job.endTime = new Date().toISOString();
        this.emit('jobFailed', job);
      }
      
      this.logger.error(`Training job failed: ${jobId}`, error);
      throw error;
    }
  }

  async prepareTrainingData(rawData, modelType, options) {
    try {
      this.logger.info(`Preparing training data for model type: ${modelType}`);

      // Preprocess data
      const preprocessor = this.dataPipeline.preprocessors.get(modelType) || 
                          this.dataPipeline.preprocessors.get('default');
      
      const preprocessedData = await preprocessor(rawData, options);

      // Split into training and validation sets
      const { trainData, valData } = await this.splitData(preprocessedData, options.validationSplit);

      // Apply data augmentation if enabled
      if (options.dataAugmentation) {
        const augmentor = this.dataPipeline.augmentors.get(modelType);
        if (augmentor) {
          trainData.x = await augmentor(trainData.x, options);
        }
      }

      // Convert to tensors
      const trainTensors = {
        x: Array.isArray(trainData.x) ? trainData.x.map(x => tf.tensor(x)) : tf.tensor(trainData.x),
        y: tf.tensor(trainData.y)
      };

      const valTensors = {
        x: Array.isArray(valData.x) ? valData.x.map(x => tf.tensor(x)) : tf.tensor(valData.x),
        y: tf.tensor(valData.y)
      };

      this.logger.info('Training data preparation complete');
      return { trainData: trainTensors, valData: valTensors };

    } catch (error) {
      this.logger.error('Training data preparation failed:', error);
      throw error;
    }
  }

  async createOrLoadModel(modelType, options) {
    try {
      const modelConfig = this.modelRegistry[modelType];
      if (!modelConfig) {
        throw new Error(`Unknown model type: ${modelType}`);
      }

      let model;
      
      if (options.resumeFromCheckpoint && options.checkpointPath) {
        // Load existing model
        this.logger.info(`Loading model from checkpoint: ${options.checkpointPath}`);
        model = await tf.loadLayersModel(`file://${options.checkpointPath}`);
      } else if (options.transferLearning && options.baseModelPath) {
        // Transfer learning
        this.logger.info(`Creating model with transfer learning from: ${options.baseModelPath}`);
        model = await this.createTransferLearningModel(modelType, options);
      } else {
        // Create new model
        this.logger.info(`Creating new model of type: ${modelType}`);
        model = await modelConfig.factory(options);
      }

      return model;

    } catch (error) {
      this.logger.error('Model creation failed:', error);
      throw error;
    }
  }

  async createSemanticImageModel(options) {
    const model = tf.sequential();
    
    // Input layer
    model.add(tf.layers.inputLayer({
      inputShape: [224, 224, 3]
    }));

    // Feature extraction backbone
    model.add(tf.layers.conv2d({
      filters: 64,
      kernelSize: 3,
      activation: 'relu',
      padding: 'same'
    }));
    
    model.add(tf.layers.batchNormalization());
    model.add(tf.layers.maxPooling2d({ poolSize: 2 }));

    model.add(tf.layers.conv2d({
      filters: 128,
      kernelSize: 3,
      activation: 'relu',
      padding: 'same'
    }));
    
    model.add(tf.layers.batchNormalization());
    model.add(tf.layers.maxPooling2d({ poolSize: 2 }));

    model.add(tf.layers.conv2d({
      filters: 256,
      kernelSize: 3,
      activation: 'relu',
      padding: 'same'
    }));
    
    model.add(tf.layers.batchNormalization());
    model.add(tf.layers.maxPooling2d({ poolSize: 2 }));

    // Global pooling and classification
    model.add(tf.layers.globalAveragePooling2d());
    model.add(tf.layers.dropout({ rate: 0.5 }));
    
    model.add(tf.layers.dense({
      units: 512,
      activation: 'relu'
    }));
    
    model.add(tf.layers.dropout({ rate: 0.3 }));
    
    model.add(tf.layers.dense({
      units: options.numClasses || 1000,
      activation: 'softmax'
    }));

    model.compile({
      optimizer: tf.train.adam(options.learningRate || 0.001),
      loss: 'categoricalCrossentropy',
      metrics: ['accuracy', 'topKCategoricalAccuracy']
    });

    return model;
  }

  async createContentMappingModel(options) {
    const model = tf.sequential();
    
    // Input layer
    model.add(tf.layers.inputLayer({
      inputShape: [options.sequenceLength || 512]
    }));

    // Embedding and LSTM layers
    model.add(tf.layers.dense({
      units: 256,
      activation: 'relu'
    }));
    
    model.add(tf.layers.dropout({ rate: 0.3 }));
    
    model.add(tf.layers.dense({
      units: 128,
      activation: 'relu'
    }));
    
    model.add(tf.layers.dropout({ rate: 0.2 }));
    
    model.add(tf.layers.dense({
      units: options.numClasses || 20,
      activation: 'sigmoid'
    }));

    model.compile({
      optimizer: tf.train.adam(options.learningRate || 0.001),
      loss: 'binaryCrossentropy',
      metrics: ['accuracy', 'precision', 'recall']
    });

    return model;
  }

  async createMultimodalModel(options) {
    // Image input branch
    const imageInput = tf.input({ shape: [224, 224, 3], name: 'image_input' });
    const imageFeatures = tf.layers.conv2d({ filters: 64, kernelSize: 3, activation: 'relu' }).apply(imageInput);
    const imageFeaturesPooled = tf.layers.globalAveragePooling2d().apply(imageFeatures);
    
    // Text input branch
    const textInput = tf.input({ shape: [512], name: 'text_input' });
    const textFeatures = tf.layers.dense({ units: 128, activation: 'relu' }).apply(textInput);
    
    // Combine features
    const combined = tf.layers.concatenate().apply([imageFeaturesPooled, textFeatures]);
    const dense1 = tf.layers.dense({ units: 256, activation: 'relu' }).apply(combined);
    const dropout1 = tf.layers.dropout({ rate: 0.3 }).apply(dense1);
    const output = tf.layers.dense({ 
      units: options.numClasses || 10, 
      activation: 'softmax',
      name: 'predictions'
    }).apply(dropout1);
    
    const model = tf.model({
      inputs: [imageInput, textInput],
      outputs: output
    });

    model.compile({
      optimizer: tf.train.adam(options.learningRate || 0.001),
      loss: 'categoricalCrossentropy',
      metrics: ['accuracy']
    });

    return model;
  }

  async createTransferLearningModel(modelType, options) {
    // Load pre-trained base model
    const baseModel = await tf.loadLayersModel(`file://${options.baseModelPath}`);
    
    // Freeze early layers
    const freezeLayerCount = options.freezeLayers || Math.floor(baseModel.layers.length * 0.8);
    
    for (let i = 0; i < freezeLayerCount; i++) {
      baseModel.layers[i].trainable = false;
    }

    // Add custom classification head
    const model = tf.sequential();
    
    // Add base model
    model.add(baseModel);
    
    // Add custom layers
    model.add(tf.layers.globalAveragePooling2d());
    model.add(tf.layers.dropout({ rate: 0.5 }));
    
    model.add(tf.layers.dense({
      units: 256,
      activation: 'relu'
    }));
    
    model.add(tf.layers.dropout({ rate: 0.3 }));
    
    model.add(tf.layers.dense({
      units: options.numClasses || 10,
      activation: 'softmax'
    }));

    model.compile({
      optimizer: tf.train.adam(options.learningRate || 0.0001),
      loss: 'categoricalCrossentropy',
      metrics: ['accuracy']
    });

    return model;
  }

  setupTrainingCallbacks(jobId, job) {
    const callbacks = [];

    // Progress callback
    callbacks.push(tf.callbacks.customCallback({
      onEpochEnd: (epoch, logs) => {
        job.currentEpoch = epoch + 1;
        job.progress = Math.round((epoch + 1) / job.options.epochs * 100);
        job.metrics = { ...job.metrics, ...logs };
        
        this.emit('epochCompleted', {
          jobId,
          epoch: epoch + 1,
          metrics: logs,
          progress: job.progress
        });

        this.logger.info(`Job ${jobId} - Epoch ${epoch + 1}/${job.options.epochs}: loss=${logs.loss.toFixed(4)}, accuracy=${logs.acc?.toFixed(4) || 'N/A'}`);
      }
    }));

    // Early stopping
    if (job.options.earlyStopping) {
      callbacks.push(tf.callbacks.earlyStopping({
        monitor: job.options.earlyStopping.monitor,
        patience: job.options.earlyStopping.patience,
        restoreBestWeights: job.options.earlyStopping.restoreBestWeights
      }));
    }

    // Model checkpointing
    if (job.options.checkpointFreq) {
      callbacks.push(tf.callbacks.customCallback({
        onEpochEnd: async (epoch, logs) => {
          if ((epoch + 1) % job.options.checkpointFreq === 0) {
            const checkpointPath = path.join('models', 'checkpoints', `${jobId}_epoch_${epoch + 1}`);
            await job.model.save(`file://${checkpointPath}`);
            this.logger.info(`Checkpoint saved: ${checkpointPath}`);
          }
        }
      }));
    }

    return callbacks;
  }

  async trainModel(model, trainData, valData, options, callbacks) {
    try {
      this.logger.info('Starting model training...');

      const history = await model.fit(trainData.x, trainData.y, {
        epochs: options.epochs,
        batchSize: options.batchSize,
        validationData: [valData.x, valData.y],
        callbacks: callbacks,
        verbose: 0
      });

      this.logger.info('Model training completed');
      return history;

    } catch (error) {
      this.logger.error('Model training failed:', error);
      throw error;
    }
  }

  async evaluateModel(model, valData) {
    try {
      this.logger.info('Evaluating model...');

      const evaluation = await model.evaluate(valData.x, valData.y, {
        batchSize: this.config.batchSize
      });

      const evalResults = {};
      const metricNames = model.metricsNames;
      
      evaluation.forEach((value, index) => {
        evalResults[metricNames[index]] = value.dataSync()[0];
      });

      this.logger.info('Model evaluation completed:', evalResults);
      return evalResults;

    } catch (error) {
      this.logger.error('Model evaluation failed:', error);
      throw error;
    }
  }

  async saveModel(model, jobId, modelType) {
    try {
      const modelDir = path.join('models', 'trained', `${modelType}_${jobId}`);
      await fs.mkdir(modelDir, { recursive: true });
      
      const modelPath = path.join(modelDir, 'model.json');
      await model.save(`file://${modelDir}`);
      
      // Save metadata
      const metadata = {
        jobId,
        modelType,
        timestamp: new Date().toISOString(),
        architecture: model.getConfig(),
        inputShape: model.inputShape,
        outputShape: model.outputShape,
        trainableParams: model.countParams()
      };
      
      await fs.writeFile(
        path.join(modelDir, 'metadata.json'),
        JSON.stringify(metadata, null, 2)
      );

      this.logger.info(`Model saved to: ${modelDir}`);
      return modelDir;

    } catch (error) {
      this.logger.error('Model saving failed:', error);
      throw error;
    }
  }

  // Data preprocessing functions
  async preprocessImage(rawData, options) {
    // Mock image preprocessing
    return {
      x: rawData.images || [],
      y: rawData.labels || []
    };
  }

  async preprocessText(rawData, options) {
    // Mock text preprocessing
    return {
      x: rawData.texts || [],
      y: rawData.labels || []
    };
  }

  async preprocessMixed(rawData, options) {
    // Mock multimodal preprocessing
    return {
      x: [rawData.images || [], rawData.texts || []],
      y: rawData.labels || []
    };
  }

  // Data augmentation functions
  async augmentImage(images, options) {
    // Mock image augmentation
    return images;
  }

  async augmentText(texts, options) {
    // Mock text augmentation
    return texts;
  }

  async splitData(data, validationSplit) {
    const trainSize = Math.floor(data.x.length * (1 - validationSplit));
    
    return {
      trainData: {
        x: data.x.slice(0, trainSize),
        y: data.y.slice(0, trainSize)
      },
      valData: {
        x: data.x.slice(trainSize),
        y: data.y.slice(trainSize)
      }
    };
  }

  registerPreprocessor(type, preprocessor) {
    this.dataPipeline.preprocessors.set(type, preprocessor);
  }

  registerAugmentor(type, augmentor) {
    this.dataPipeline.augmentors.set(type, augmentor);
  }

  formatHistory(history) {
    const formatted = {};
    
    for (const [key, values] of Object.entries(history.history)) {
      formatted[key] = values;
    }
    
    return formatted;
  }

  getTrainingJob(jobId) {
    return this.trainingJobs.get(jobId);
  }

  listTrainingJobs() {
    return Array.from(this.trainingJobs.values());
  }

  async cleanup() {
    // Clean up resources
    for (const model of this.models.values()) {
      model.dispose();
    }
    this.models.clear();
    this.trainingJobs.clear();
  }
}

module.exports = { TrainingPipeline };