/**
 * Jest test setup configuration
 * 
 * Global setup and configuration for all test files
 */

// Increase timeout for async operations
jest.setTimeout(30000);

// Mock file system operations for tests
const fs = require('fs').promises;
const originalMkdir = fs.mkdir;
const originalWriteFile = fs.writeFile;
const originalReadFile = fs.readFile;
const originalReaddir = fs.readdir;
const originalUnlink = fs.unlink;

beforeAll(() => {
    // Mock file operations to prevent actual file creation during tests
    fs.mkdir = jest.fn().mockResolvedValue(undefined);
    fs.writeFile = jest.fn().mockResolvedValue(undefined);
    fs.readFile = jest.fn().mockResolvedValue('{"test": "data"}');
    fs.readdir = jest.fn().mockResolvedValue([]);
    fs.unlink = jest.fn().mockResolvedValue(undefined);
});

afterAll(() => {
    // Restore original functions
    fs.mkdir = originalMkdir;
    fs.writeFile = originalWriteFile;
    fs.readFile = originalReadFile;
    fs.readdir = originalReaddir;
    fs.unlink = originalUnlink;
});

// Global test utilities
global.testUtils = {
    createTestTask: (overrides = {}) => ({
        id: `test-task-${Date.now()}`,
        type: 'generic',
        description: 'Test task for unit testing',
        input: 'test input data',
        ...overrides
    }),
    
    createTestConfig: (overrides = {}) => ({
        maxAgents: 3,
        executionStrategy: 'parallel',
        logLevel: 'error',
        persistentStorage: false,
        validateTasks: true,
        ...overrides
    }),
    
    sleep: (ms) => new Promise(resolve => setTimeout(resolve, ms)),
    
    createMockAgent: (type = 'generic') => ({
        id: `mock-agent-${Date.now()}`,
        type,
        status: 'idle',
        execute: jest.fn().mockResolvedValue({
            type: `${type}-execution`,
            result: 'mock success',
            executedAt: new Date().toISOString()
        }),
        sendMessage: jest.fn().mockResolvedValue('mock-message-id')
    })
};

// Suppress console output during tests unless debugging
if (!process.env.DEBUG_TESTS) {
    const originalConsole = { ...console };
    
    beforeEach(() => {
        console.log = jest.fn();
        console.info = jest.fn();
        console.warn = jest.fn();
        console.error = jest.fn();
        console.debug = jest.fn();
    });
    
    afterEach(() => {
        Object.assign(console, originalConsole);
    });
}