/**
 * Test Configuration for Semantic Image-Content Mapping System
 * Quality Engineer - Test Configuration Setup
 */

module.exports = {
  // Test Environment Configuration
  testEnvironment: 'node',
  
  // Test Directories
  testMatch: [
    '<rootDir>/tests/unit/**/*.test.js',
    '<rootDir>/tests/integration/**/*.test.js',
    '<rootDir>/tests/performance/**/*.test.js',
    '<rootDir>/tests/e2e/**/*.test.js'
  ],
  
  // Setup Files
  setupFilesAfterEnv: [
    '<rootDir>/tests/setup/jest.setup.js',
    '<rootDir>/tests/setup/wordpress.setup.js'
  ],
  
  // Module Paths
  moduleNameMapping: {
    '^@/(.*)$': '<rootDir>/$1',
    '^@tests/(.*)$': '<rootDir>/tests/$1',
    '^@fixtures/(.*)$': '<rootDir>/tests/fixtures/$1'
  },
  
  // Coverage Configuration
  collectCoverageFrom: [
    '**/*.php',
    '**/*.js',
    '!node_modules/**',
    '!tests/**',
    '!wp-content/themes/twenty*/**',
    '!wp-content/plugins/akismet/**',
    '!**/node_modules/**'
  ],
  
  // Coverage Thresholds
  coverageThreshold: {
    global: {
      branches: 75,
      functions: 80,
      lines: 80,
      statements: 80
    },
    './assign-semantic-images.php': {
      branches: 85,
      functions: 90,
      lines: 90,
      statements: 90
    }
  },
  
  // Test Timeout
  testTimeout: 30000,
  
  // Reporters
  reporters: [
    'default',
    ['jest-html-reporters', {
      publicPath: './tests/reports',
      filename: 'test-report.html',
      expand: true
    }],
    ['jest-junit', {
      outputDirectory: './tests/reports',
      outputName: 'junit.xml'
    }]
  ],
  
  // WordPress Testing Configuration
  wordpress: {
    dbHost: process.env.WP_DB_HOST || '127.0.0.1',
    dbName: process.env.WP_DB_NAME || 'wordpress_test',
    dbUser: process.env.WP_DB_USER || 'root',
    dbPassword: process.env.WP_DB_PASSWORD || '',
    wpVersion: process.env.WP_VERSION || 'latest',
    phpVersion: process.env.PHP_VERSION || '8.1'
  },
  
  // Performance Test Thresholds
  performance: {
    imageProcessingTimeout: 5000,
    semanticMatchingTimeout: 3000,
    databaseOperationTimeout: 2000,
    maxMemoryUsage: 512 * 1024 * 1024, // 512MB
    maxImageProcessingTime: 1000
  },
  
  // Neural Network Test Configuration
  neuralTests: {
    minAccuracy: 0.85,
    maxInferenceTime: 500,
    testDataSize: 1000,
    validationSplit: 0.2
  },
  
  // DAA Coordination Test Settings
  daaTests: {
    maxCoordinationDelay: 1000,
    minConsensusThreshold: 0.75,
    faultToleranceLevel: 2,
    networkLatencySimulation: true
  }
};