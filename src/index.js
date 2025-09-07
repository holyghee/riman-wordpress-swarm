/**
 * SwarmCore - Main entry point for the swarm execution system
 * 
 * Exports all core components and provides a unified interface
 * for swarm task execution and coordination.
 */

const { SwarmExecutor } = require('./core/SwarmExecutor');
const { MemoryManager } = require('./memory/MemoryManager');
const { TaskOrchestrator } = require('./tasks/TaskOrchestrator');
const { AgentCoordinator } = require('./agents/AgentCoordinator');
const { ConfigManager } = require('./config/ConfigManager');
const { Logger } = require('./utils/Logger');
const { SecurityValidator, SecurityValidationError } = require('./security/SecurityValidator');

/**
 * Factory function to create a pre-configured SwarmExecutor
 */
async function createSwarm(options = {}) {
    const swarmExecutor = new SwarmExecutor(options);
    await swarmExecutor.initialize();
    return swarmExecutor;
}

/**
 * Quick execution function for single tasks
 */
async function executeTask(taskDefinition, options = {}) {
    const swarm = await createSwarm(options);
    
    try {
        const result = await swarm.executeTask(taskDefinition);
        await swarm.shutdown();
        return result;
    } catch (error) {
        await swarm.shutdown();
        throw error;
    }
}

/**
 * Quick execution function for multiple tasks
 */
async function executeTasks(tasks, options = {}) {
    const swarm = await createSwarm(options);
    
    try {
        const results = await swarm.executeTasks(tasks);
        await swarm.shutdown();
        return results;
    } catch (error) {
        await swarm.shutdown();
        throw error;
    }
}

/**
 * Utility function to validate task before execution
 */
async function validateTask(taskDefinition, securityOptions = {}) {
    const validator = new SecurityValidator(securityOptions);
    return await validator.validateTask(taskDefinition);
}

/**
 * Create a logger instance with swarm-specific configuration
 */
function createLogger(context, options = {}) {
    return new Logger(context, {
        level: 'info',
        format: 'json',
        output: 'console',
        includeTimestamp: true,
        includeContext: true,
        ...options
    });
}

/**
 * Create a memory manager instance
 */
function createMemoryManager(swarmId, options = {}) {
    return new MemoryManager(swarmId, options);
}

/**
 * Create a configuration manager instance
 */
function createConfigManager(initialConfig = {}) {
    return new ConfigManager(initialConfig);
}

// Export all components and utilities
module.exports = {
    // Core components
    SwarmExecutor,
    MemoryManager,
    TaskOrchestrator,
    AgentCoordinator,
    ConfigManager,
    Logger,
    SecurityValidator,
    SecurityValidationError,
    
    // Factory and utility functions
    createSwarm,
    executeTask,
    executeTasks,
    validateTask,
    createLogger,
    createMemoryManager,
    createConfigManager,
    
    // Version information
    version: '1.0.0',
    
    // Default configurations
    defaultConfig: {
        maxAgents: 10,
        defaultTopology: 'hierarchical',
        executionStrategy: 'parallel',
        maxConcurrency: 5,
        defaultTimeout: 300000,
        cacheSize: 1000,
        logLevel: 'info',
        validateTasks: true,
        sanitizeInputs: true
    }
};