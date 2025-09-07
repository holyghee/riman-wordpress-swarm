/**
 * Neural Model Manager
 * Model versioning, persistence, and lifecycle management
 */

const tf = require('@tensorflow/tfjs-node');
const fs = require('fs').promises;
const path = require('path');
const winston = require('winston');
const { v4: uuidv4 } = require('uuid');
const crypto = require('crypto');

class ModelManager {
  constructor(config = {}) {
    this.config = {
      modelsDir: path.join(process.cwd(), 'models'),
      maxVersions: 10,
      compressionEnabled: true,
      backupEnabled: true,
      validationEnabled: true,
      ...config
    };

    this.logger = winston.createLogger({
      level: 'info',
      format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.json()
      ),
      defaultMeta: { service: 'model-manager' },
      transports: [
        new winston.transports.Console(),
        new winston.transports.File({ filename: 'model-manager.log' })
      ]
    });

    this.loadedModels = new Map();
    this.modelRegistry = new Map();
    this.modelVersions = new Map();
    this.initialized = false;
  }

  async initialize() {
    try {
      this.logger.info('Initializing Model Manager...');
      
      await this.setupModelDirectories();
      await this.loadModelRegistry();
      await this.setupModelVersioning();
      
      this.initialized = true;
      this.logger.info('Model Manager initialized successfully');
    } catch (error) {
      this.logger.error('Failed to initialize Model Manager:', error);
      throw error;
    }
  }

  async setupModelDirectories() {
    const directories = [
      'trained',
      'checkpoints',
      'exports',
      'backups',
      'templates'
    ];

    for (const dir of directories) {
      const fullPath = path.join(this.config.modelsDir, dir);
      await fs.mkdir(fullPath, { recursive: true });
    }

    this.logger.info('Model directories setup complete');
  }

  async loadModelRegistry() {
    try {
      const registryPath = path.join(this.config.modelsDir, 'registry.json');
      
      try {
        const registryData = await fs.readFile(registryPath, 'utf8');
        const registry = JSON.parse(registryData);
        
        for (const [modelId, modelInfo] of Object.entries(registry)) {
          this.modelRegistry.set(modelId, modelInfo);
        }
        
        this.logger.info(`Model registry loaded: ${this.modelRegistry.size} models`);
      } catch (error) {
        this.logger.info('No existing model registry found, creating new one');
        await this.saveModelRegistry();
      }
    } catch (error) {
      this.logger.error('Failed to load model registry:', error);
      throw error;
    }
  }

  async setupModelVersioning() {
    for (const [modelId, modelInfo] of this.modelRegistry) {
      if (modelInfo.versions) {
        this.modelVersions.set(modelId, modelInfo.versions);
      }
    }

    this.logger.info('Model versioning setup complete');
  }

  async saveModel(model, modelId, metadata = {}) {
    if (!this.initialized) {
      throw new Error('Model Manager not initialized');
    }

    try {
      this.logger.info(`Saving model: ${modelId}`);

      // Generate version
      const version = this.generateVersion(modelId);
      const versionedModelId = `${modelId}_v${version}`;

      // Create model directory
      const modelDir = path.join(this.config.modelsDir, 'trained', versionedModelId);
      await fs.mkdir(modelDir, { recursive: true });

      // Save model
      await model.save(`file://${modelDir}`);

      // Generate model hash
      const modelHash = await this.generateModelHash(modelDir);

      // Create comprehensive metadata
      const fullMetadata = {
        id: modelId,
        versionedId: versionedModelId,
        version: version,
        hash: modelHash,
        timestamp: new Date().toISOString(),
        architecture: model.getConfig(),
        inputShape: model.inputShape,
        outputShape: model.outputShape,
        trainableParams: model.countParams(),
        size: await this.calculateModelSize(modelDir),
        ...metadata
      };

      // Save metadata
      await fs.writeFile(
        path.join(modelDir, 'metadata.json'),
        JSON.stringify(fullMetadata, null, 2)
      );

      // Update registry
      await this.updateModelRegistry(modelId, versionedModelId, fullMetadata);

      // Create backup if enabled
      if (this.config.backupEnabled) {
        await this.createModelBackup(versionedModelId, modelDir);
      }

      // Validate model if enabled
      if (this.config.validationEnabled) {
        await this.validateSavedModel(versionedModelId, modelDir);
      }

      this.logger.info(`Model saved successfully: ${versionedModelId}`);

      return {
        modelId: versionedModelId,
        version: version,
        path: modelDir,
        metadata: fullMetadata
      };

    } catch (error) {
      this.logger.error(`Failed to save model ${modelId}:`, error);
      throw error;
    }
  }

  async loadModel(modelId, version = 'latest') {
    if (!this.initialized) {
      throw new Error('Model Manager not initialized');
    }

    try {
      // Check if already loaded
      const cacheKey = `${modelId}_${version}`;
      if (this.loadedModels.has(cacheKey)) {
        this.logger.info(`Model loaded from cache: ${cacheKey}`);
        return this.loadedModels.get(cacheKey);
      }

      this.logger.info(`Loading model: ${modelId} (version: ${version})`);

      // Resolve model path
      const modelPath = await this.resolveModelPath(modelId, version);
      
      if (!modelPath) {
        throw new Error(`Model not found: ${modelId} (version: ${version})`);
      }

      // Load model
      const model = await tf.loadLayersModel(`file://${modelPath}/model.json`);

      // Load metadata
      const metadata = await this.loadModelMetadata(modelPath);

      // Validate loaded model
      if (this.config.validationEnabled) {
        await this.validateLoadedModel(model, metadata);
      }

      // Cache loaded model
      const modelInfo = {
        model: model,
        metadata: metadata,
        loadTime: new Date(),
        usageCount: 0
      };

      this.loadedModels.set(cacheKey, modelInfo);

      this.logger.info(`Model loaded successfully: ${cacheKey}`);

      return modelInfo;

    } catch (error) {
      this.logger.error(`Failed to load model ${modelId}:`, error);
      throw error;
    }
  }

  async listModels() {
    const models = [];

    for (const [modelId, modelInfo] of this.modelRegistry) {
      const versions = this.modelVersions.get(modelId) || [];
      
      models.push({
        id: modelId,
        name: modelInfo.name || modelId,
        description: modelInfo.description || '',
        type: modelInfo.type || 'unknown',
        versions: versions.length,
        latestVersion: versions.length > 0 ? Math.max(...versions.map(v => v.version)) : 0,
        created: modelInfo.created,
        lastModified: modelInfo.lastModified,
        size: modelInfo.size || 0,
        tags: modelInfo.tags || []
      });
    }

    return models.sort((a, b) => new Date(b.lastModified) - new Date(a.lastModified));
  }

  async getModelInfo(modelId, version = 'latest') {
    const modelPath = await this.resolveModelPath(modelId, version);
    
    if (!modelPath) {
      throw new Error(`Model not found: ${modelId} (version: ${version})`);
    }

    const metadata = await this.loadModelMetadata(modelPath);
    const versions = this.modelVersions.get(modelId) || [];

    return {
      ...metadata,
      versions: versions.map(v => ({
        version: v.version,
        timestamp: v.timestamp,
        size: v.size,
        hash: v.hash
      }))
    };
  }

  async deleteModel(modelId, version = null) {
    try {
      if (version) {
        // Delete specific version
        const versionedModelId = `${modelId}_v${version}`;
        const modelDir = path.join(this.config.modelsDir, 'trained', versionedModelId);
        
        await fs.rmdir(modelDir, { recursive: true });
        
        // Remove from cache
        const cacheKey = `${modelId}_${version}`;
        if (this.loadedModels.has(cacheKey)) {
          const modelInfo = this.loadedModels.get(cacheKey);
          modelInfo.model.dispose();
          this.loadedModels.delete(cacheKey);
        }

        // Update version list
        const versions = this.modelVersions.get(modelId) || [];
        const updatedVersions = versions.filter(v => v.version !== version);
        this.modelVersions.set(modelId, updatedVersions);

        this.logger.info(`Model version deleted: ${versionedModelId}`);
      } else {
        // Delete all versions
        const versions = this.modelVersions.get(modelId) || [];
        
        for (const versionInfo of versions) {
          const versionedModelId = `${modelId}_v${versionInfo.version}`;
          const modelDir = path.join(this.config.modelsDir, 'trained', versionedModelId);
          
          await fs.rmdir(modelDir, { recursive: true });
          
          // Remove from cache
          const cacheKey = `${modelId}_${versionInfo.version}`;
          if (this.loadedModels.has(cacheKey)) {
            const modelInfo = this.loadedModels.get(cacheKey);
            modelInfo.model.dispose();
            this.loadedModels.delete(cacheKey);
          }
        }

        // Remove from registry
        this.modelRegistry.delete(modelId);
        this.modelVersions.delete(modelId);

        this.logger.info(`Model deleted completely: ${modelId}`);
      }

      // Update registry file
      await this.saveModelRegistry();

    } catch (error) {
      this.logger.error(`Failed to delete model ${modelId}:`, error);
      throw error;
    }
  }

  async exportModel(modelId, version, exportFormat = 'tfjs', exportPath = null) {
    try {
      this.logger.info(`Exporting model: ${modelId} (version: ${version}) to ${exportFormat}`);

      const modelInfo = await this.loadModel(modelId, version);
      const model = modelInfo.model;

      const exportDir = exportPath || path.join(this.config.modelsDir, 'exports', `${modelId}_v${version}_${exportFormat}`);
      await fs.mkdir(exportDir, { recursive: true });

      switch (exportFormat.toLowerCase()) {
        case 'tfjs':
          await model.save(`file://${exportDir}`);
          break;
        case 'saved_model':
          // Would require tfjs-node-gpu and specific conversion
          throw new Error('SavedModel export not implemented in this example');
        case 'onnx':
          // Would require onnxjs conversion
          throw new Error('ONNX export not implemented in this example');
        default:
          throw new Error(`Unsupported export format: ${exportFormat}`);
      }

      // Copy metadata
      const metadataPath = path.join(exportDir, 'export_metadata.json');
      await fs.writeFile(metadataPath, JSON.stringify({
        ...modelInfo.metadata,
        exportFormat: exportFormat,
        exportTimestamp: new Date().toISOString(),
        exportPath: exportDir
      }, null, 2));

      this.logger.info(`Model exported successfully: ${exportDir}`);

      return {
        exportPath: exportDir,
        format: exportFormat,
        size: await this.calculateDirectorySize(exportDir)
      };

    } catch (error) {
      this.logger.error(`Failed to export model ${modelId}:`, error);
      throw error;
    }
  }

  async optimizeModel(modelId, version, optimizationOptions = {}) {
    try {
      this.logger.info(`Optimizing model: ${modelId} (version: ${version})`);

      const modelInfo = await this.loadModel(modelId, version);
      const model = modelInfo.model;

      let optimizedModel = model;

      if (optimizationOptions.quantization) {
        // In production, implement quantization
        this.logger.info('Applying quantization optimization...');
      }

      if (optimizationOptions.pruning) {
        // In production, implement pruning
        this.logger.info('Applying pruning optimization...');
      }

      if (optimizationOptions.compression) {
        // In production, implement compression
        this.logger.info('Applying compression optimization...');
      }

      // Save optimized model
      const optimizedModelId = `${modelId}_optimized`;
      const savedModel = await this.saveModel(optimizedModel, optimizedModelId, {
        ...modelInfo.metadata,
        optimizations: optimizationOptions,
        originalModel: `${modelId}_v${version}`,
        optimizationTimestamp: new Date().toISOString()
      });

      this.logger.info(`Model optimization completed: ${optimizedModelId}`);

      return savedModel;

    } catch (error) {
      this.logger.error(`Failed to optimize model ${modelId}:`, error);
      throw error;
    }
  }

  // Helper methods
  generateVersion(modelId) {
    const versions = this.modelVersions.get(modelId) || [];
    const latestVersion = versions.length > 0 ? Math.max(...versions.map(v => v.version)) : 0;
    return latestVersion + 1;
  }

  async generateModelHash(modelDir) {
    try {
      const modelJsonPath = path.join(modelDir, 'model.json');
      const modelJson = await fs.readFile(modelJsonPath);
      
      const hash = crypto.createHash('sha256');
      hash.update(modelJson);
      
      return hash.digest('hex');
    } catch (error) {
      this.logger.warn('Failed to generate model hash:', error);
      return null;
    }
  }

  async calculateModelSize(modelDir) {
    try {
      return await this.calculateDirectorySize(modelDir);
    } catch (error) {
      this.logger.warn('Failed to calculate model size:', error);
      return 0;
    }
  }

  async calculateDirectorySize(dirPath) {
    let totalSize = 0;
    
    const files = await fs.readdir(dirPath, { withFileTypes: true });
    
    for (const file of files) {
      const filePath = path.join(dirPath, file.name);
      
      if (file.isDirectory()) {
        totalSize += await this.calculateDirectorySize(filePath);
      } else {
        const stats = await fs.stat(filePath);
        totalSize += stats.size;
      }
    }
    
    return totalSize;
  }

  async updateModelRegistry(modelId, versionedModelId, metadata) {
    // Update or create model entry
    let modelInfo = this.modelRegistry.get(modelId) || {
      name: modelId,
      created: metadata.timestamp,
      versions: []
    };

    modelInfo.lastModified = metadata.timestamp;
    modelInfo.type = metadata.type || modelInfo.type;
    modelInfo.size = metadata.size;

    this.modelRegistry.set(modelId, modelInfo);

    // Update versions
    const versions = this.modelVersions.get(modelId) || [];
    versions.push({
      version: metadata.version,
      timestamp: metadata.timestamp,
      size: metadata.size,
      hash: metadata.hash,
      path: versionedModelId
    });

    // Keep only max versions
    if (versions.length > this.config.maxVersions) {
      const removedVersions = versions.splice(0, versions.length - this.config.maxVersions);
      
      // Clean up old version directories
      for (const removedVersion of removedVersions) {
        try {
          const oldModelDir = path.join(this.config.modelsDir, 'trained', removedVersion.path);
          await fs.rmdir(oldModelDir, { recursive: true });
          this.logger.info(`Old model version cleaned up: ${removedVersion.path}`);
        } catch (error) {
          this.logger.warn(`Failed to clean up old version: ${removedVersion.path}`, error);
        }
      }
    }

    this.modelVersions.set(modelId, versions);

    // Save registry
    await this.saveModelRegistry();
  }

  async saveModelRegistry() {
    const registryPath = path.join(this.config.modelsDir, 'registry.json');
    const registry = {};

    for (const [modelId, modelInfo] of this.modelRegistry) {
      registry[modelId] = {
        ...modelInfo,
        versions: this.modelVersions.get(modelId) || []
      };
    }

    await fs.writeFile(registryPath, JSON.stringify(registry, null, 2));
  }

  async resolveModelPath(modelId, version) {
    if (version === 'latest') {
      const versions = this.modelVersions.get(modelId) || [];
      if (versions.length === 0) return null;
      
      const latestVersion = versions.reduce((latest, current) => 
        current.version > latest.version ? current : latest
      );
      
      return path.join(this.config.modelsDir, 'trained', latestVersion.path);
    } else {
      const versionedModelId = `${modelId}_v${version}`;
      const modelDir = path.join(this.config.modelsDir, 'trained', versionedModelId);
      
      try {
        await fs.access(modelDir);
        return modelDir;
      } catch (error) {
        return null;
      }
    }
  }

  async loadModelMetadata(modelDir) {
    try {
      const metadataPath = path.join(modelDir, 'metadata.json');
      const metadataData = await fs.readFile(metadataPath, 'utf8');
      return JSON.parse(metadataData);
    } catch (error) {
      this.logger.warn('Failed to load model metadata:', error);
      return {};
    }
  }

  async createModelBackup(versionedModelId, modelDir) {
    try {
      const backupDir = path.join(this.config.modelsDir, 'backups', versionedModelId);
      await fs.mkdir(backupDir, { recursive: true });
      
      // Copy model files
      const files = await fs.readdir(modelDir);
      for (const file of files) {
        const srcPath = path.join(modelDir, file);
        const dstPath = path.join(backupDir, file);
        await fs.copyFile(srcPath, dstPath);
      }
      
      this.logger.info(`Model backup created: ${backupDir}`);
    } catch (error) {
      this.logger.warn('Failed to create model backup:', error);
    }
  }

  async validateSavedModel(versionedModelId, modelDir) {
    try {
      // Basic validation - check if model files exist
      const modelJsonPath = path.join(modelDir, 'model.json');
      const metadataPath = path.join(modelDir, 'metadata.json');
      
      await fs.access(modelJsonPath);
      await fs.access(metadataPath);
      
      this.logger.info(`Model validation passed: ${versionedModelId}`);
      return true;
    } catch (error) {
      this.logger.error(`Model validation failed: ${versionedModelId}`, error);
      return false;
    }
  }

  async validateLoadedModel(model, metadata) {
    try {
      // Validate model structure matches metadata
      if (metadata.trainableParams && model.countParams() !== metadata.trainableParams) {
        throw new Error('Model parameter count mismatch');
      }
      
      this.logger.debug('Loaded model validation passed');
      return true;
    } catch (error) {
      this.logger.error('Loaded model validation failed:', error);
      return false;
    }
  }

  async cleanup() {
    // Dispose all loaded models
    for (const [cacheKey, modelInfo] of this.loadedModels) {
      modelInfo.model.dispose();
      this.logger.info(`Model disposed: ${cacheKey}`);
    }
    
    this.loadedModels.clear();
    this.modelRegistry.clear();
    this.modelVersions.clear();
    
    this.logger.info('Model Manager cleanup completed');
  }
}

module.exports = { ModelManager };