const tf = require('@tensorflow/tfjs-node');
const mobilenet = require('@tensorflow-models/mobilenet');
const fs = require('fs');
const path = require('path');

let model;

async function loadModel() {
  if (model) {
    return model;
  }
  console.log('Loading MobileNet model...');
  model = await mobilenet.load();
  console.log('Model loaded.');
  return model;
}

function loadImage(imagePath) {
  const imageBuffer = fs.readFileSync(imagePath);
  const tensor = tf.node.decodeImage(imageBuffer, 3);
  return tensor;
}

async function preprocessImage(imageTensor) {
  const resized = tf.image.resizeBilinear(imageTensor, [224, 224]);
  const normalized = resized.toFloat().div(tf.scalar(127)).sub(tf.scalar(1));
  const batched = normalized.expandDims(0);
  return batched;
}

async function extractFeatures(imagePath) {
  await loadModel();
  const imageTensor = loadImage(imagePath);
  const preprocessed = await preprocessImage(imageTensor);
  const embeddings = model.infer(preprocessed, true);
  const features = await embeddings.array();

  const predictions = await model.classify(preprocessed);

  const featureData = {
    features: features[0],
    predictions: predictions.map(p => ({
      className: p.className,
      probability: p.probability
    })),
    createdAt: new Date().toISOString()
  };

  return featureData;
}

module.exports = {
  extractFeatures,
  loadModel
};