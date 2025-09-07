const express = require('express');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const { extractFeatures, loadModel } = require('./image-processor');

const app = express();
const port = process.env.PORT || 3000;

const uploadDir = 'uploads';
if (!fs.existsSync(uploadDir)) {
  fs.mkdirSync(uploadDir);
}

const storage = multer.diskStorage({
  destination: function (req, file, cb) {
    cb(null, uploadDir);
  },
  filename: function (req, file, cb) {
    cb(null, Date.now() + path.extname(file.originalname));
  }
});

const upload = multer({ storage: storage });

app.use(express.json());
app.use('/uploads', express.static(uploadDir));

app.post('/process-image', upload.single('image'), async (req, res) => {
  if (!req.file) {
    return res.status(400).send({ error: 'No image file uploaded.' });
  }

  const imagePath = req.file.path;

  try {
    console.log(`Processing image: ${imagePath}`);
    const featureData = await extractFeatures(imagePath);
    
    const metadata = {
      originalFilename: req.file.originalname,
      uploadedFilename: req.file.filename,
      path: imagePath,
      size: req.file.size,
      mimetype: req.file.mimetype,
    };

    const result = {
      metadata,
      ...featureData
    };

    // Store extracted features with metadata
    const featuresDir = 'features';
    if (!fs.existsSync(featuresDir)) {
      fs.mkdirSync(featuresDir);
    }
    const featureFilePath = path.join(featuresDir, `${path.basename(imagePath, path.extname(imagePath))}.json`);
    fs.writeFileSync(featureFilePath, JSON.stringify(result, null, 2));
    console.log(`Features saved to ${featureFilePath}`);

    res.status(200).send(result);
  } catch (error) {
    console.error('Error processing image:', error);
    res.status(500).send({ error: 'Failed to process image.' });
  }
});

app.get('/health', (req, res) => {
  res.status(200).send({ status: 'ok' });
});

async function startServer() {
  await loadModel();
  app.listen(port, () => {
    console.log(`Image processing server listening on port ${port}`);
  });
}

startServer();