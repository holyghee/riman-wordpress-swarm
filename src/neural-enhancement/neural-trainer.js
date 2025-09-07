const tf = require('@tensorflow/tfjs-node');

class NeuralPatternTrainer {
  constructor() {
    this.models = new Map();
    this.trainingData = [];
  }

  /**
   * Creates a simple neural network for pattern recognition
   * @param {string} modelName - Name identifier for the model
   * @param {number} inputDim - Input dimension
   * @param {number} outputDim - Output dimension
   */
  createModel(modelName, inputDim = 512, outputDim = 128) {
    const model = tf.sequential({
      layers: [
        tf.layers.dense({ inputShape: [inputDim], units: 256, activation: 'relu' }),
        tf.layers.dropout({ rate: 0.2 }),
        tf.layers.dense({ units: 128, activation: 'relu' }),
        tf.layers.dropout({ rate: 0.2 }),
        tf.layers.dense({ units: outputDim, activation: 'sigmoid' })
      ]
    });

    model.compile({
      optimizer: tf.train.adam(0.001),
      loss: 'meanSquaredError',
      metrics: ['accuracy']
    });

    this.models.set(modelName, model);
    console.log(`Neural model '${modelName}' created with input:${inputDim}, output:${outputDim}`);
    return model;
  }

  /**
   * Add training data for pattern learning
   * @param {Array} input - Input features
   * @param {Array} expected - Expected output
   * @param {string} category - Data category
   */
  addTrainingData(input, expected, category = 'default') {
    this.trainingData.push({ input, expected, category, timestamp: new Date() });
  }

  /**
   * Train the model with accumulated data
   * @param {string} modelName - Model to train
   * @param {number} epochs - Training epochs
   */
  async trainModel(modelName, epochs = 50) {
    const model = this.models.get(modelName);
    if (!model) {
      throw new Error(`Model '${modelName}' not found`);
    }

    if (this.trainingData.length === 0) {
      throw new Error('No training data available');
    }

    // Prepare training data
    const inputs = this.trainingData.map(d => d.input);
    const outputs = this.trainingData.map(d => d.expected);

    const xs = tf.tensor2d(inputs);
    const ys = tf.tensor2d(outputs);

    console.log(`Training model '${modelName}' with ${this.trainingData.length} samples for ${epochs} epochs`);
    
    const history = await model.fit(xs, ys, {
      epochs,
      batchSize: 32,
      validationSplit: 0.2,
      verbose: 1,
      callbacks: {
        onEpochEnd: (epoch, logs) => {
          if (epoch % 10 === 0) {
            console.log(`Epoch ${epoch}: loss = ${logs.loss.toFixed(4)}, accuracy = ${logs.acc?.toFixed(4) || 'N/A'}`);
          }
        }
      }
    });

    xs.dispose();
    ys.dispose();

    return history;
  }

  /**
   * Make predictions using a trained model
   * @param {string} modelName - Model to use
   * @param {Array} inputData - Input features
   * @returns {Array} Predictions
   */
  async predict(modelName, inputData) {
    const model = this.models.get(modelName);
    if (!model) {
      throw new Error(`Model '${modelName}' not found`);
    }

    const input = tf.tensor2d([inputData]);
    const prediction = model.predict(input);
    const result = await prediction.data();
    
    input.dispose();
    prediction.dispose();

    return Array.from(result);
  }

  /**
   * Save a trained model
   * @param {string} modelName - Model to save
   * @param {string} savePath - File path to save to
   */
  async saveModel(modelName, savePath) {
    const model = this.models.get(modelName);
    if (!model) {
      throw new Error(`Model '${modelName}' not found`);
    }

    await model.save(`file://${savePath}`);
    console.log(`Model '${modelName}' saved to ${savePath}`);
  }

  /**
   * Load a saved model
   * @param {string} modelName - Name for the loaded model
   * @param {string} loadPath - Path to load from
   */
  async loadModel(modelName, loadPath) {
    const model = await tf.loadLayersModel(`file://${loadPath}`);
    this.models.set(modelName, model);
    console.log(`Model '${modelName}' loaded from ${loadPath}`);
    return model;
  }

  /**
   * Get training statistics
   */
  getTrainingStats() {
    const categories = {};
    this.trainingData.forEach(data => {
      categories[data.category] = (categories[data.category] || 0) + 1;
    });

    return {
      totalSamples: this.trainingData.length,
      categories,
      modelCount: this.models.size,
      availableModels: Array.from(this.models.keys())
    };
  }
}

module.exports = NeuralPatternTrainer;