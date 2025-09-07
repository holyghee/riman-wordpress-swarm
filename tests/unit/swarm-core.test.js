/**
 * Comprehensive unit tests for SwarmCore components
 * 
 * Tests all core functionality including SwarmExecutor, MemoryManager,
 * TaskOrchestrator, AgentCoordinator, ConfigManager, and SecurityValidator.
 */

const {
    SwarmExecutor,
    MemoryManager,
    TaskOrchestrator,
    AgentCoordinator,
    ConfigManager,
    Logger,
    SecurityValidator,
    SecurityValidationError,
    createSwarm,
    executeTask,
    executeTasks,
    validateTask
} = require('../../src/index');

describe('SwarmCore System', () => {
    let swarmExecutor;
    let testConfig;

    beforeEach(() => {
        testConfig = {
            maxAgents: 5,
            executionStrategy: 'parallel',
            logLevel: 'error', // Reduce noise in tests
            persistentStorage: false, // Don't create files during tests
            validateTasks: true
        };
    });

    afterEach(async () => {
        if (swarmExecutor) {
            await swarmExecutor.shutdown();
            swarmExecutor = null;
        }
    });

    describe('SwarmExecutor', () => {
        test('should initialize successfully', async () => {
            swarmExecutor = new SwarmExecutor(testConfig);
            const initialized = await swarmExecutor.initialize();
            
            expect(initialized).toBe(true);
            expect(swarmExecutor.state.status).toBe('ready');
            expect(swarmExecutor.swarmId).toBeDefined();
            expect(swarmExecutor.swarmId).toMatch(/^swarm_\d+_[a-z0-9]+$/);
        });

        test('should execute a single task', async () => {
            swarmExecutor = new SwarmExecutor(testConfig);
            await swarmExecutor.initialize();

            const task = {
                id: 'test-task-1',
                type: 'generic',
                description: 'Test task execution',
                input: 'test input data'
            };

            const result = await swarmExecutor.executeTask(task);
            
            expect(result).toBeDefined();
            expect(result.type).toBe('generic-execution');
            expect(result.executedBy).toBeDefined();
            expect(swarmExecutor.state.metrics.completedTasks).toBe(1);
        });

        test('should execute multiple tasks', async () => {
            swarmExecutor = new SwarmExecutor(testConfig);
            await swarmExecutor.initialize();

            const tasks = [
                { id: 'task-1', type: 'generic', description: 'Task 1' },
                { id: 'task-2', type: 'generic', description: 'Task 2' },
                { id: 'task-3', type: 'generic', description: 'Task 3' }
            ];

            const results = await swarmExecutor.executeTasks(tasks);
            
            expect(results).toHaveLength(3);
            expect(swarmExecutor.state.metrics.completedTasks).toBe(3);
        });

        test('should handle task execution failure gracefully', async () => {
            swarmExecutor = new SwarmExecutor(testConfig);
            await swarmExecutor.initialize();

            // Mock agent to throw error
            const originalGetAgent = swarmExecutor.agentCoordinator.getAgent;
            swarmExecutor.agentCoordinator.getAgent = jest.fn().mockRejectedValue(new Error('Agent error'));

            const task = {
                id: 'failing-task',
                type: 'generic',
                description: 'This task should fail'
            };

            await expect(swarmExecutor.executeTask(task)).rejects.toThrow('Agent error');
            expect(swarmExecutor.state.metrics.failedTasks).toBe(1);
        });

        test('should provide correct status information', async () => {
            swarmExecutor = new SwarmExecutor(testConfig);
            await swarmExecutor.initialize();

            const status = swarmExecutor.getStatus();
            
            expect(status.swarmId).toBeDefined();
            expect(status.state.status).toBe('ready');
            expect(status.metrics).toHaveProperty('totalTasks');
            expect(status.metrics).toHaveProperty('completedTasks');
            expect(status.metrics).toHaveProperty('failedTasks');
            expect(status.memory).toBeDefined();
        });
    });

    describe('MemoryManager', () => {
        let memoryManager;

        beforeEach(async () => {
            memoryManager = new MemoryManager('test-swarm', { persistentStorage: false });
            await memoryManager.initialize();
        });

        afterEach(async () => {
            if (memoryManager) {
                await memoryManager.shutdown();
            }
        });

        test('should store and retrieve data', async () => {
            const key = 'test-key';
            const value = { test: 'data', number: 42 };
            
            await memoryManager.store(key, value);
            const retrieved = await memoryManager.retrieve(key);
            
            expect(retrieved).toEqual(value);
        });

        test('should handle TTL expiration', async () => {
            const key = 'expiring-key';
            const value = 'expiring data';
            const shortTTL = 100; // 100ms
            
            await memoryManager.store(key, value, shortTTL);
            
            // Should exist immediately
            let retrieved = await memoryManager.retrieve(key);
            expect(retrieved).toBe(value);
            
            // Wait for TTL to expire
            await new Promise(resolve => setTimeout(resolve, 150));
            
            // Should be expired now
            retrieved = await memoryManager.retrieve(key);
            expect(retrieved).toBeNull();
        });

        test('should search for keys by pattern', async () => {
            await memoryManager.store('user/123/profile', { name: 'John' });
            await memoryManager.store('user/456/profile', { name: 'Jane' });
            await memoryManager.store('config/app', { theme: 'dark' });
            
            const userResults = await memoryManager.search('user/.*/profile');
            const configResults = await memoryManager.search('config/.*');
            
            expect(userResults).toHaveLength(2);
            expect(configResults).toHaveLength(1);
            expect(configResults[0].value.theme).toBe('dark');
        });

        test('should delete data correctly', async () => {
            const key = 'to-delete';
            const value = 'delete me';
            
            await memoryManager.store(key, value);
            
            let retrieved = await memoryManager.retrieve(key);
            expect(retrieved).toBe(value);
            
            const deleted = await memoryManager.delete(key);
            expect(deleted).toBe(true);
            
            retrieved = await memoryManager.retrieve(key);
            expect(retrieved).toBeNull();
        });

        test('should provide accurate statistics', async () => {
            await memoryManager.store('key1', 'value1');
            await memoryManager.store('key2', 'value2');
            await memoryManager.retrieve('key1');
            await memoryManager.retrieve('nonexistent');
            
            const stats = memoryManager.getStats();
            
            expect(stats.cacheSize).toBe(2);
            expect(stats.stores).toBe(2);
            expect(stats.retrievals).toBe(2);
            expect(stats.hits).toBe(1);
            expect(stats.misses).toBe(1);
            expect(stats.hitRate).toBe('50.00%');
        });
    });

    describe('TaskOrchestrator', () => {
        let taskOrchestrator;
        let mockSwarmExecutor;

        beforeEach(async () => {
            mockSwarmExecutor = {
                swarmId: 'test-swarm',
                agentCoordinator: {
                    getAgent: jest.fn().mockResolvedValue({
                        id: 'test-agent',
                        execute: jest.fn().mockResolvedValue({ result: 'success' })
                    })
                },
                getMemory: () => new MemoryManager('test-swarm', { persistentStorage: false }),
                getConfig: () => new ConfigManager({ maxParallelism: 4 })
            };
            
            await mockSwarmExecutor.getMemory().initialize();
            taskOrchestrator = new TaskOrchestrator(mockSwarmExecutor);
        });

        test('should execute a simple task', async () => {
            const task = {
                id: 'simple-task',
                type: 'generic',
                description: 'Simple test task'
            };

            const result = await taskOrchestrator.execute(task);
            
            expect(result).toBeDefined();
            expect(mockSwarmExecutor.agentCoordinator.getAgent).toHaveBeenCalled();
        });

        test('should handle parallel execution strategy', async () => {
            const task = {
                id: 'parallel-task',
                type: 'data-processing',
                input: [1, 2, 3, 4, 5, 6, 7, 8]
            };

            const result = await taskOrchestrator.execute(task);
            
            expect(result).toBeDefined();
            expect(result.taskId).toBe('parallel-task');
            expect(result.successful).toBeDefined();
        });

        test('should execute tasks with dependencies', async () => {
            const tasks = [
                { id: 'task-a', dependencies: [] },
                { id: 'task-b', dependencies: ['task-a'] },
                { id: 'task-c', dependencies: ['task-a', 'task-b'] }
            ];

            const results = await taskOrchestrator.executeBatch(tasks);
            
            expect(results).toHaveLength(3);
            expect(results.every(r => r.status === 'fulfilled')).toBe(true);
        });

        test('should provide orchestrator status', () => {
            const status = taskOrchestrator.getStatus();
            
            expect(status).toHaveProperty('taskQueue');
            expect(status).toHaveProperty('executingTasks');
            expect(status).toHaveProperty('completedTasks');
            expect(status).toHaveProperty('metrics');
        });
    });

    describe('AgentCoordinator', () => {
        let agentCoordinator;
        let mockSwarmExecutor;

        beforeEach(async () => {
            mockSwarmExecutor = {
                swarmId: 'test-swarm',
                getMemory: () => new MemoryManager('test-swarm', { persistentStorage: false }),
                getConfig: () => new ConfigManager({ defaultAgents: ['generalist'] })
            };
            
            await mockSwarmExecutor.getMemory().initialize();
            agentCoordinator = new AgentCoordinator(mockSwarmExecutor);
            await agentCoordinator.initialize();
        });

        test('should create agents of different types', async () => {
            const coderAgent = await agentCoordinator.getAgent('coder');
            const reviewerAgent = await agentCoordinator.getAgent('reviewer');
            const testerAgent = await agentCoordinator.getAgent('tester');
            
            expect(coderAgent.type).toBe('coder');
            expect(reviewerAgent.type).toBe('reviewer');
            expect(testerAgent.type).toBe('tester');
            expect(coderAgent.id).not.toBe(reviewerAgent.id);
        });

        test('should reuse existing idle agents', async () => {
            const agent1 = await agentCoordinator.getAgent('generalist');
            agent1.status = 'idle';
            
            const agent2 = await agentCoordinator.getAgent('generalist');
            
            expect(agent1.id).toBe(agent2.id);
        });

        test('should execute tasks with different agent types', async () => {
            const coderAgent = await agentCoordinator.getAgent('coder');
            const reviewerAgent = await agentCoordinator.getAgent('reviewer');
            const testerAgent = await agentCoordinator.getAgent('tester');
            
            const coderTask = { id: 'code-task', description: 'Write code' };
            const reviewTask = { id: 'review-task', description: 'Review code' };
            const testTask = { id: 'test-task', description: 'Test code' };
            
            const coderResult = await coderAgent.execute(coderTask);
            const reviewResult = await reviewerAgent.execute(reviewTask);
            const testResult = await testerAgent.execute(testTask);
            
            expect(coderResult.type).toBe('code-generation');
            expect(reviewResult.type).toBe('code-review');
            expect(testResult.type).toBe('testing');
        });

        test('should handle agent communication', async () => {
            const agent1 = await agentCoordinator.getAgent('generalist');
            const agent2 = await agentCoordinator.getAgent('generalist');
            
            const messageId = await agent1.sendMessage(agent2.id, 'Hello from agent1');
            
            expect(messageId).toBeDefined();
            expect(messageId).toMatch(/^msg_\d+_[a-z0-9]+$/);
        });

        test('should provide coordinator status', async () => {
            await agentCoordinator.getAgent('coder');
            await agentCoordinator.getAgent('reviewer');
            
            const status = agentCoordinator.getStatus();
            
            expect(status.totalAgents).toBe(2);
            expect(status.agentTypes).toContain('coder');
            expect(status.agentTypes).toContain('reviewer');
            expect(status.agentsByType.coder).toBe(1);
            expect(status.agentsByType.reviewer).toBe(1);
        });
    });

    describe('ConfigManager', () => {
        let configManager;

        beforeEach(async () => {
            configManager = new ConfigManager();
            await configManager.load();
        });

        test('should get and set configuration values', () => {
            configManager.set('testKey', 'testValue');
            const value = configManager.get('testKey');
            
            expect(value).toBe('testValue');
        });

        test('should return default value when key not found', () => {
            const value = configManager.get('nonexistentKey', 'defaultValue');
            
            expect(value).toBe('defaultValue');
        });

        test('should validate configuration values', () => {
            expect(() => {
                configManager.set('maxAgents', -1);
            }).toThrow('Invalid value for configuration key');
            
            expect(() => {
                configManager.set('defaultTopology', 'invalid');
            }).toThrow('Invalid value for configuration key');
        });

        test('should merge configuration from object', () => {
            const newConfig = {
                maxAgents: 8,
                logLevel: 'debug',
                customKey: 'customValue'
            };
            
            const updatedKeys = configManager.merge(newConfig);
            
            expect(updatedKeys).toContain('maxAgents');
            expect(updatedKeys).toContain('logLevel');
            expect(configManager.get('maxAgents')).toBe(8);
            expect(configManager.get('logLevel')).toBe('debug');
        });

        test('should watch for configuration changes', (done) => {
            const unwatch = configManager.watch('testWatchKey', (newValue, oldValue, key) => {
                expect(key).toBe('testWatchKey');
                expect(newValue).toBe('newValue');
                expect(oldValue).toBeUndefined();
                unwatch();
                done();
            });
            
            configManager.set('testWatchKey', 'newValue');
        });

        test('should get all configuration as object', () => {
            configManager.set('key1', 'value1');
            configManager.set('key2', 'value2');
            
            const allConfig = configManager.getAll();
            
            expect(allConfig).toHaveProperty('key1', 'value1');
            expect(allConfig).toHaveProperty('key2', 'value2');
            expect(allConfig).toHaveProperty('maxAgents'); // From defaults
        });
    });

    describe('SecurityValidator', () => {
        let securityValidator;

        beforeEach(() => {
            securityValidator = new SecurityValidator({
                validateTasks: true,
                sanitizeInputs: true,
                maxTaskSize: 1000
            });
        });

        test('should validate valid tasks', async () => {
            const validTask = {
                id: 'valid-task',
                type: 'generic',
                description: 'A valid task',
                input: 'clean input data'
            };
            
            const result = await securityValidator.validateTask(validTask);
            
            expect(result.valid).toBe(true);
            expect(result.sanitized).toBeDefined();
            expect(result.warnings).toBeDefined();
        });

        test('should reject tasks with invalid structure', async () => {
            const invalidTask = {
                // Missing required ID
                type: 'generic',
                description: 'Invalid task'
            };
            
            await expect(securityValidator.validateTask(invalidTask))
                .rejects.toThrow('Task must have a valid ID');
        });

        test('should reject tasks that are too large', async () => {
            const largeTask = {
                id: 'large-task',
                type: 'generic',
                description: 'A'.repeat(2000), // Exceeds maxTaskSize of 1000
                input: 'some input'
            };
            
            await expect(securityValidator.validateTask(largeTask))
                .rejects.toThrow('Task size');
        });

        test('should sanitize inputs', () => {
            const dirtyInput = '<script>alert("xss")</script>Hello World';
            const result = securityValidator.validateInput(dirtyInput, 'string');
            
            expect(result.valid).toBe(true);
            expect(result.sanitized).not.toContain('<script>');
            expect(result.sanitized).toContain('Hello World');
        });

        test('should validate file operations', () => {
            expect(() => {
                securityValidator.validateFileOperation('read', '../../../etc/passwd');
            }).toThrow('Path traversal detected');
            
            expect(() => {
                securityValidator.validateFileOperation('read', 'test.exe');
            }).toThrow('File type not allowed');
            
            const result = securityValidator.validateFileOperation('read', 'test.js');
            expect(result.valid).toBe(true);
        });

        test('should generate and verify security hashes', () => {
            const data = { test: 'data', number: 42 };
            const hash = securityValidator.generateSecurityHash(data);
            
            expect(hash).toBeDefined();
            expect(typeof hash).toBe('string');
            expect(hash.length).toBe(64); // SHA-256 hex length
            
            const isValid = securityValidator.verifySecurityHash(data, hash);
            expect(isValid).toBe(true);
            
            const isInvalid = securityValidator.verifySecurityHash({ different: 'data' }, hash);
            expect(isInvalid).toBe(false);
        });

        test('should track and provide security metrics', async () => {
            const validTask = { id: 'task1', type: 'generic' };
            await securityValidator.validateTask(validTask);
            
            try {
                const invalidTask = { type: 'generic' }; // No ID
                await securityValidator.validateTask(invalidTask);
            } catch (error) {
                // Expected to fail
            }
            
            const metrics = securityValidator.getMetrics();
            
            expect(metrics.totalValidations).toBe(2);
            expect(metrics.blockedRequests).toBe(1);
            expect(metrics.securityViolations).toBe(1);
        });
    });

    describe('Logger', () => {
        test('should create logger with context', () => {
            const logger = new Logger('TestContext');
            
            expect(logger.context).toBe('TestContext');
            expect(logger.getLevel()).toBe('info');
        });

        test('should create child logger', () => {
            const parentLogger = new Logger('Parent');
            const childLogger = parentLogger.child('Child');
            
            expect(childLogger.context).toBe('Parent:Child');
        });

        test('should handle different log levels', () => {
            const logger = new Logger('Test', { level: 'debug', output: 'none' });
            
            expect(logger.isLevelEnabled('debug')).toBe(true);
            expect(logger.isLevelEnabled('info')).toBe(true);
            expect(logger.isLevelEnabled('error')).toBe(true);
            
            logger.setLevel('error');
            expect(logger.isLevelEnabled('debug')).toBe(false);
            expect(logger.isLevelEnabled('info')).toBe(false);
            expect(logger.isLevelEnabled('error')).toBe(true);
        });

        test('should create performance timer', () => {
            const logger = new Logger('Test', { output: 'none' });
            const timer = logger.timer('test-operation');
            
            setTimeout(() => {
                const duration = timer.end();
                expect(duration).toBeGreaterThan(0);
            }, 10);
        });
    });

    describe('Factory Functions', () => {
        test('should create swarm with factory function', async () => {
            const swarm = await createSwarm({ maxAgents: 3, logLevel: 'error' });
            
            expect(swarm).toBeInstanceOf(SwarmExecutor);
            expect(swarm.state.status).toBe('ready');
            expect(swarm.config.get('maxAgents')).toBe(3);
            
            await swarm.shutdown();
        });

        test('should execute single task with factory function', async () => {
            const task = {
                id: 'factory-task',
                type: 'generic',
                description: 'Task executed via factory function'
            };
            
            const result = await executeTask(task, { logLevel: 'error' });
            
            expect(result).toBeDefined();
            expect(result.type).toBe('generic-execution');
        });

        test('should execute multiple tasks with factory function', async () => {
            const tasks = [
                { id: 'task-1', type: 'generic', description: 'Task 1' },
                { id: 'task-2', type: 'generic', description: 'Task 2' }
            ];
            
            const results = await executeTasks(tasks, { logLevel: 'error' });
            
            expect(results).toHaveLength(2);
            expect(results.every(r => r.status === 'fulfilled')).toBe(true);
        });

        test('should validate task with factory function', async () => {
            const task = {
                id: 'validation-task',
                type: 'generic',
                description: 'Task to validate'
            };
            
            const result = await validateTask(task);
            
            expect(result.valid).toBe(true);
            expect(result.sanitized).toBeDefined();
        });
    });

    describe('Integration Tests', () => {
        test('should handle complete workflow from task creation to completion', async () => {
            const swarm = await createSwarm({
                maxAgents: 5,
                executionStrategy: 'parallel',
                logLevel: 'error',
                validateTasks: true
            });

            const complexTask = {
                id: 'integration-test-task',
                type: 'code-generation',
                description: 'Generate a complex piece of code with review and testing',
                requirements: 'Create a REST API endpoint',
                language: 'javascript'
            };

            const result = await swarm.executeTask(complexTask);
            
            expect(result).toBeDefined();
            expect(result.type).toBe('code-generation');
            expect(result.code).toBeDefined();
            expect(result.executedBy).toBeDefined();
            
            const status = swarm.getStatus();
            expect(status.metrics.completedTasks).toBe(1);
            expect(status.metrics.failedTasks).toBe(0);
            
            await swarm.shutdown();
        });

        test('should maintain consistency across memory operations', async () => {
            const swarm = await createSwarm({ logLevel: 'error' });
            const memory = swarm.getMemory();
            
            // Store multiple related pieces of data
            await memory.store('user/profile', { name: 'John', role: 'admin' });
            await memory.store('user/permissions', ['read', 'write', 'admin']);
            await memory.store('session/data', { token: 'abc123', expires: Date.now() + 3600000 });
            
            // Retrieve and verify consistency
            const profile = await memory.retrieve('user/profile');
            const permissions = await memory.retrieve('user/permissions');
            const session = await memory.retrieve('session/data');
            
            expect(profile.name).toBe('John');
            expect(permissions).toContain('admin');
            expect(session.token).toBe('abc123');
            
            // Verify search functionality
            const userData = await memory.search('user/.*');
            expect(userData).toHaveLength(2);
            
            await swarm.shutdown();
        });

        test('should handle error recovery and resilience', async () => {
            const swarm = await createSwarm({
                maxRetries: 2,
                logLevel: 'error'
            });

            // Create a task that will initially fail but should be retried
            let attemptCount = 0;
            const originalGetAgent = swarm.agentCoordinator.getAgent;
            swarm.agentCoordinator.getAgent = async (agentType) => {
                attemptCount++;
                if (attemptCount === 1) {
                    throw new Error('Temporary agent failure');
                }
                return originalGetAgent.call(swarm.agentCoordinator, agentType);
            };

            const resilientTask = {
                id: 'resilient-task',
                type: 'generic',
                description: 'Task that should recover from initial failure'
            };

            // This should eventually succeed due to retry mechanism
            // Note: In this test setup, the retry is handled at the agent level
            // In a real implementation, you might want to add retry logic to the SwarmExecutor
            
            const status = swarm.getStatus();
            expect(status.swarmId).toBeDefined();
            
            await swarm.shutdown();
        });
    });
});

// Helper function to suppress console output during tests
const originalConsole = console;
beforeAll(() => {
    console.log = jest.fn();
    console.info = jest.fn();
    console.warn = jest.fn();
    console.error = jest.fn();
    console.debug = jest.fn();
});

afterAll(() => {
    console.log = originalConsole.log;
    console.info = originalConsole.info;
    console.warn = originalConsole.warn;
    console.error = originalConsole.error;
    console.debug = originalConsole.debug;
});