/**
 * SwarmExecutor - Core execution engine for distributed swarm tasks
 * 
 * Handles coordination, execution, and monitoring of swarm-based tasks
 * with full integration to claude-flow hooks and memory management.
 */

const { EventEmitter } = require('events');
const { MemoryManager } = require('../memory/MemoryManager');
const { ConfigManager } = require('../config/ConfigManager');
const { TaskOrchestrator } = require('../tasks/TaskOrchestrator');
const { AgentCoordinator } = require('../agents/AgentCoordinator');
const { SecurityValidator } = require('../security/SecurityValidator');
const { Logger } = require('../utils/Logger');

class SwarmExecutor extends EventEmitter {
    constructor(options = {}) {
        super();
        
        this.swarmId = options.swarmId || `swarm_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        this.config = new ConfigManager(options.config);
        this.memory = new MemoryManager(this.swarmId);
        this.taskOrchestrator = new TaskOrchestrator(this);
        this.agentCoordinator = new AgentCoordinator(this);
        this.security = new SecurityValidator();
        this.logger = new Logger(`SwarmExecutor[${this.swarmId}]`);
        
        this.state = {
            status: 'initializing',
            startTime: null,
            endTime: null,
            activeAgents: new Map(),
            completedTasks: new Set(),
            failedTasks: new Set(),
            metrics: {
                totalTasks: 0,
                completedTasks: 0,
                failedTasks: 0,
                avgExecutionTime: 0,
                tokenUsage: 0
            }
        };

        this._initializeHooks();
    }

    /**
     * Initialize the swarm execution environment
     */
    async initialize() {
        try {
            this.logger.info('Initializing SwarmExecutor', { swarmId: this.swarmId });
            
            // Initialize core components
            await this.config.load();
            await this.memory.initialize();
            await this.agentCoordinator.initialize();
            
            // Store swarm initialization in memory
            await this.memory.store('swarm/initialization', {
                swarmId: this.swarmId,
                timestamp: new Date().toISOString(),
                config: this.config.getAll(),
                status: 'initialized'
            });

            this.state.status = 'ready';
            this.emit('initialized', { swarmId: this.swarmId });
            
            this.logger.info('SwarmExecutor initialized successfully');
            return true;
            
        } catch (error) {
            this.logger.error('Failed to initialize SwarmExecutor', { error: error.message });
            this.state.status = 'failed';
            this.emit('error', error);
            throw error;
        }
    }

    /**
     * Execute a swarm task with distributed coordination
     */
    async executeTask(taskDefinition) {
        try {
            this.logger.info('Starting task execution', { taskId: taskDefinition.id });
            
            // Security validation
            await this.security.validateTask(taskDefinition);
            
            // Pre-task hooks
            await this._executeHook('pre-task', taskDefinition);
            
            // Update state
            this.state.status = 'executing';
            this.state.startTime = new Date();
            this.state.metrics.totalTasks++;
            
            // Orchestrate task execution
            const result = await this.taskOrchestrator.execute(taskDefinition);
            
            // Update metrics and state
            this.state.metrics.completedTasks++;
            this.state.completedTasks.add(taskDefinition.id);
            
            // Post-task hooks
            await this._executeHook('post-task', { task: taskDefinition, result });
            
            // Store execution results
            await this.memory.store(`swarm/task/${taskDefinition.id}/result`, {
                taskId: taskDefinition.id,
                result,
                completedAt: new Date().toISOString(),
                executionTime: Date.now() - this.state.startTime.getTime()
            });

            this.logger.info('Task execution completed', { 
                taskId: taskDefinition.id, 
                executionTime: Date.now() - this.state.startTime.getTime() 
            });
            
            this.emit('taskCompleted', { task: taskDefinition, result });
            return result;
            
        } catch (error) {
            this.logger.error('Task execution failed', { 
                taskId: taskDefinition.id, 
                error: error.message 
            });
            
            this.state.metrics.failedTasks++;
            this.state.failedTasks.add(taskDefinition.id);
            
            // Store failure information
            await this.memory.store(`swarm/task/${taskDefinition.id}/failure`, {
                taskId: taskDefinition.id,
                error: error.message,
                stack: error.stack,
                failedAt: new Date().toISOString()
            });

            this.emit('taskFailed', { task: taskDefinition, error });
            throw error;
        }
    }

    /**
     * Execute multiple tasks with coordination and optimization
     */
    async executeTasks(tasks) {
        const results = [];
        const executionStrategy = this.config.get('executionStrategy', 'parallel');
        
        this.logger.info('Executing multiple tasks', { 
            count: tasks.length, 
            strategy: executionStrategy 
        });

        try {
            if (executionStrategy === 'parallel') {
                // Execute tasks in parallel with concurrency control
                const maxConcurrency = this.config.get('maxConcurrency', 5);
                const chunks = this._chunkArray(tasks, maxConcurrency);
                
                for (const chunk of chunks) {
                    const chunkResults = await Promise.allSettled(
                        chunk.map(task => this.executeTask(task))
                    );
                    results.push(...chunkResults);
                }
            } else {
                // Sequential execution
                for (const task of tasks) {
                    try {
                        const result = await this.executeTask(task);
                        results.push({ status: 'fulfilled', value: result });
                    } catch (error) {
                        results.push({ status: 'rejected', reason: error });
                    }
                }
            }

            return results;
            
        } catch (error) {
            this.logger.error('Multiple task execution failed', { error: error.message });
            throw error;
        }
    }

    /**
     * Get current swarm status and metrics
     */
    getStatus() {
        return {
            swarmId: this.swarmId,
            state: { ...this.state },
            activeAgents: Array.from(this.state.activeAgents.keys()),
            metrics: { ...this.state.metrics },
            memory: this.memory.getStats(),
            uptime: this.state.startTime ? Date.now() - this.state.startTime.getTime() : 0
        };
    }

    /**
     * Gracefully shutdown the swarm
     */
    async shutdown() {
        try {
            this.logger.info('Shutting down SwarmExecutor');
            
            // Stop all active agents
            await this.agentCoordinator.shutdown();
            
            // Store final metrics
            await this.memory.store('swarm/final_metrics', {
                swarmId: this.swarmId,
                finalState: this.state,
                shutdownTime: new Date().toISOString()
            });
            
            // Execute shutdown hooks
            await this._executeHook('session-end', { 
                swarmId: this.swarmId, 
                metrics: this.state.metrics 
            });
            
            this.state.status = 'shutdown';
            this.state.endTime = new Date();
            
            this.emit('shutdown', { swarmId: this.swarmId });
            this.logger.info('SwarmExecutor shutdown completed');
            
        } catch (error) {
            this.logger.error('Error during shutdown', { error: error.message });
            throw error;
        }
    }

    /**
     * Initialize hooks integration
     */
    _initializeHooks() {
        this.hooks = {
            'pre-task': [],
            'post-task': [],
            'post-edit': [],
            'notify': [],
            'session-end': []
        };
    }

    /**
     * Execute a specific hook with data
     */
    async _executeHook(hookName, data) {
        try {
            const hooks = this.hooks[hookName] || [];
            await Promise.all(hooks.map(hook => hook(data)));
            
            // Also execute claude-flow hooks if available
            if (typeof global.claudeFlowHooks !== 'undefined') {
                await global.claudeFlowHooks.execute(hookName, data);
            }
        } catch (error) {
            this.logger.warn('Hook execution failed', { 
                hookName, 
                error: error.message 
            });
        }
    }

    /**
     * Utility function to chunk array for parallel processing
     */
    _chunkArray(array, chunkSize) {
        const chunks = [];
        for (let i = 0; i < array.length; i += chunkSize) {
            chunks.push(array.slice(i, i + chunkSize));
        }
        return chunks;
    }

    /**
     * Register a hook callback
     */
    registerHook(hookName, callback) {
        if (!this.hooks[hookName]) {
            this.hooks[hookName] = [];
        }
        this.hooks[hookName].push(callback);
    }

    /**
     * Get memory instance for external access
     */
    getMemory() {
        return this.memory;
    }

    /**
     * Get configuration instance
     */
    getConfig() {
        return this.config;
    }
}

module.exports = { SwarmExecutor };