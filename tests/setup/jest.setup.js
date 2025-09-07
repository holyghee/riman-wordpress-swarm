/**
 * Jest Setup Configuration
 * Quality Engineer - Test Environment Setup
 */

const path = require('path');
const fs = require('fs');

// Global Test Configuration
global.TEST_CONFIG = require('../test-config.js');

// Mock WordPress Functions
global.wp = {
  hooks: {
    addAction: jest.fn(),
    addFilter: jest.fn(),
    doAction: jest.fn(),
    applyFilters: jest.fn()
  },
  ajax: {
    post: jest.fn()
  },
  data: {
    select: jest.fn(),
    dispatch: jest.fn()
  }
};

// Mock PHP WordPress Functions
global.wpMocks = {
  get_posts: jest.fn(),
  get_term_by: jest.fn(),
  wp_insert_attachment: jest.fn(),
  wp_generate_attachment_metadata: jest.fn(),
  wp_update_attachment_metadata: jest.fn(),
  set_post_thumbnail: jest.fn(),
  get_post_thumbnail_id: jest.fn(),
  update_post_meta: jest.fn(),
  get_post_meta: jest.fn(),
  update_term_meta: jest.fn(),
  get_term_meta: jest.fn(),
  wp_upload_dir: jest.fn(() => ({
    path: '/tmp/test-uploads',
    url: 'http://test.local/wp-content/uploads'
  })),
  mime_content_type: jest.fn(() => 'image/jpeg')
};

// Custom Matchers
expect.extend({
  toBeValidSemanticMatch(received, threshold = 0.7) {
    const pass = received.confidence_score >= threshold && 
                 received.matching_details &&
                 received.matching_details.total_score >= threshold;
    
    if (pass) {
      return {
        message: () => `Expected semantic match to be invalid, but got confidence ${received.confidence_score}`,
        pass: true,
      };
    } else {
      return {
        message: () => `Expected semantic match to be valid with confidence >= ${threshold}, but got ${received.confidence_score}`,
        pass: false,
      };
    }
  },

  toBeValidSEOSlug(received) {
    const seoPattern = /^[a-z0-9-]+$/;
    const maxLength = 60;
    const pass = seoPattern.test(received) && 
                 received.length <= maxLength && 
                 !received.startsWith('-') && 
                 !received.endsWith('-');
    
    if (pass) {
      return {
        message: () => `Expected "${received}" to be invalid SEO slug`,
        pass: true,
      };
    } else {
      return {
        message: () => `Expected "${received}" to be valid SEO slug (lowercase, alphanumeric + hyphens, max 60 chars)`,
        pass: false,
      };
    }
  },

  toHaveValidImageDimensions(received) {
    const pass = received.width > 0 && 
                 received.height > 0 && 
                 received.width <= 7000 && 
                 received.height <= 7000;
    
    if (pass) {
      return {
        message: () => `Expected image dimensions to be invalid`,
        pass: true,
      };
    } else {
      return {
        message: () => `Expected valid image dimensions (0 < width,height <= 7000), got ${received.width}x${received.height}`,
        pass: false,
      };
    }
  }
});

// Test Database Setup
beforeAll(async () => {
  // Create test directories
  const testDirs = ['tmp', 'fixtures/images', 'fixtures/content', 'reports'];
  testDirs.forEach(dir => {
    const fullPath = path.join(__dirname, '..', dir);
    if (!fs.existsSync(fullPath)) {
      fs.mkdirSync(fullPath, { recursive: true });
    }
  });
  
  console.log('Test environment initialized');
});

// Clean up after tests
afterAll(async () => {
  // Clean up test files
  const tmpDir = path.join(__dirname, '..', 'tmp');
  if (fs.existsSync(tmpDir)) {
    fs.rmSync(tmpDir, { recursive: true, force: true });
  }
  
  console.log('Test environment cleaned up');
});

// Reset mocks before each test
beforeEach(() => {
  jest.clearAllMocks();
  
  // Reset mock implementations
  Object.keys(global.wpMocks).forEach(key => {
    if (jest.isMockFunction(global.wpMocks[key])) {
      global.wpMocks[key].mockReset();
    }
  });
});

// Error handling for async tests
process.on('unhandledRejection', (reason, promise) => {
  console.error('Unhandled Rejection at:', promise, 'reason:', reason);
});