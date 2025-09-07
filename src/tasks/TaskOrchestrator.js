/**
 * TaskOrchestrator - Intelligent task coordination and execution
 * 
 * Manages task dependencies, resource allocation, and execution strategies
 * for optimal swarm performance and coordination.
 */

const { EventEmitter } = require('events');
const { Logger } = require('../utils/Logger');

class TaskOrchestrator extends EventEmitter {
    constructor(swarmExecutor) {
        super();
        
        this.swarmExecutor = swarmExecutor;
        this.memory = swarmExecutor.getMemory();
        this.config = swarmExecutor.getConfig();
        this.logger = new Logger(`TaskOrchestrator[${swarmExecutor.swarmId}]`);
        
        this.taskQueue = new Map();
        this.executingTasks = new Map();
        this.completedTasks = new Map();
        this.dependencyGraph = new Map();
        
        this.metrics = {
            totalTasks: 0,
            completedTasks: 0,
            failedTasks: 0,
            avgExecutionTime: 0,
            queueWaitTime: 0
        };
    }

    /**
     * Execute a task with full orchestration
     */
    async execute(taskDefinition) {
        try {
            const task = this._normalizeTask(taskDefinition);
            this.logger.info('Orchestrating task execution', { taskId: task.id });
            
            // Store task in memory
            await this.memory.store(`swarm/task/${task.id}/definition`, task);
            
            // Analyze task and determine execution strategy
            const executionPlan = await this._createExecutionPlan(task);
            
            // Execute with the determined strategy
            const result = await this._executeWithStrategy(task, executionPlan);
            
            // Store results and update metrics
            await this._recordTaskCompletion(task, result);
            
            this.logger.info('Task orchestration completed', { taskId: task.id });
            return result;
            
        } catch (error) {
            this.logger.error('Task orchestration failed', { 
                taskId: taskDefinition.id || 'unknown', 
                error: error.message 
            });
            
            await this._recordTaskFailure(taskDefinition, error);
            throw error;
        }
    }

    /**
     * Execute multiple tasks with dependency resolution
     */
    async executeBatch(tasks) {
        try {
            this.logger.info('Executing task batch', { count: tasks.length });
            
            // Normalize and validate tasks
            const normalizedTasks = tasks.map(task => this._normalizeTask(task));
            
            // Build dependency graph
            const dependencyGraph = this._buildDependencyGraph(normalizedTasks);
            
            // Execute tasks in dependency order
            const results = await this._executeWithDependencies(normalizedTasks, dependencyGraph);
            
            this.logger.info('Batch execution completed', { 
                total: tasks.length, 
                successful: results.filter(r => r.status === 'fulfilled').length 
            });
            
            return results;
            
        } catch (error) {
            this.logger.error('Batch execution failed', { error: error.message });
            throw error;
        }
    }

    /**
     * Create optimal execution plan for a task
     */
    async _createExecutionPlan(task) {
        const plan = {
            strategy: 'single',
            resources: {},
            agents: [],
            parallelism: 1,
            timeout: 300000, // 5 minutes default
            retries: 2,
            priority: 'normal'
        };

        // Analyze task complexity and requirements
        if (task.type) {
            switch (task.type) {
                case 'data-processing':
                    plan.strategy = 'parallel';
                    plan.parallelism = this.config.get('maxParallelism', 4);
                    plan.agents = ['data-processor', 'validator'];
                    break;
                    
                case 'code-generation':
                    plan.strategy = 'pipeline';
                    plan.agents = ['analyzer', 'coder', 'reviewer', 'tester'];
                    break;
                    
                case 'integration-test':
                    plan.strategy = 'sequential';
                    plan.agents = ['test-runner'];
                    plan.timeout = 600000; // 10 minutes
                    break;
                    
                default:
                    plan.strategy = 'single';
                    plan.agents = ['generalist'];
            }
        }

        // Resource estimation
        plan.resources = {
            memory: this._estimateMemoryRequirement(task),
            cpu: this._estimateCPURequirement(task),
            network: this._estimateNetworkRequirement(task)
        };

        // Store execution plan
        await this.memory.store(`swarm/task/${task.id}/execution_plan`, plan);
        
        this.logger.debug('Created execution plan', { taskId: task.id, strategy: plan.strategy });
        return plan;
    }

    /**
     * Execute task with the specified strategy
     */
    async _executeWithStrategy(task, plan) {
        const startTime = Date.now();
        this.executingTasks.set(task.id, { task, plan, startTime });
        
        try {
            let result;
            
            switch (plan.strategy) {
                case 'parallel':
                    result = await this._executeParallel(task, plan);
                    break;
                    
                case 'pipeline':
                    result = await this._executePipeline(task, plan);
                    break;
                    
                case 'sequential':
                    result = await this._executeSequential(task, plan);
                    break;
                    
                default:
                    result = await this._executeSingle(task, plan);
            }
            
            const executionTime = Date.now() - startTime;
            this._updateMetrics('completed', executionTime);
            
            return result;
            
        } catch (error) {
            this._updateMetrics('failed', Date.now() - startTime);
            throw error;
        } finally {
            this.executingTasks.delete(task.id);
        }
    }

    /**
     * Execute task with parallel strategy
     */
    async _executeParallel(task, plan) {
        const subtasks = this._decomposeTask(task, plan.parallelism);
        const promises = subtasks.map((subtask, index) => 
            this._executeSubtask(subtask, plan.agents[0] || 'worker', `${task.id}_${index}`)
        );
        
        const results = await Promise.allSettled(promises);
        return this._aggregateResults(results, task);
    }

    /**
     * Execute task with pipeline strategy
     */
    async _executePipeline(task, plan) {
        let currentData = task.input || task.data;
        const results = [];
        
        for (const agentType of plan.agents) {
            const pipelineTask = {
                ...task,
                id: `${task.id}_${agentType}`,
                data: currentData,
                agent: agentType
            };
            
            const result = await this._executeSubtask(pipelineTask, agentType, pipelineTask.id);
            results.push(result);
            currentData = result.output || result.data || result;
        }
        
        return {
            pipelineResults: results,
            finalOutput: currentData,
            taskId: task.id
        };
    }

    /**
     * Execute task sequentially
     */
    async _executeSequential(task, plan) {
        const results = [];
        
        for (const agentType of plan.agents) {
            const sequentialTask = {
                ...task,
                id: `${task.id}_${agentType}`,
                agent: agentType
            };
            
            const result = await this._executeSubtask(sequentialTask, agentType, sequentialTask.id);
            results.push(result);
        }
        
        return {
            sequentialResults: results,
            taskId: task.id
        };
    }

    /**
     * Execute single task
     */
    async _executeSingle(task, plan) {
        const agentType = plan.agents[0] || 'generalist';
        return await this._executeSubtask(task, agentType, task.id);
    }

    /**
     * Execute a subtask with a specific agent
     */
    async _executeSubtask(task, agentType, subtaskId) {
        try {
            this.logger.debug('Executing subtask', { subtaskId, agentType });
            
            // Get agent coordinator to handle execution
            const agentCoordinator = this.swarmExecutor.agentCoordinator;
            const agent = await agentCoordinator.getAgent(agentType);
            
            // Execute the task
            const result = await agent.execute(task);
            
            // Store subtask result
            await this.memory.store(`swarm/subtask/${subtaskId}/result`, result);
            
            this.logger.debug('Subtask completed', { subtaskId, agentType });
            return result;
            
        } catch (error) {
            this.logger.error('Subtask failed', { subtaskId, agentType, error: error.message });
            throw error;
        }
    }

    /**
     * Build dependency graph for tasks
     */
    _buildDependencyGraph(tasks) {
        const graph = new Map();
        
        tasks.forEach(task => {
            graph.set(task.id, {
                task,
                dependencies: task.dependencies || [],
                dependents: []
            });
        });
        
        // Build dependent relationships
        graph.forEach((node, taskId) => {
            node.dependencies.forEach(depId => {
                const depNode = graph.get(depId);
                if (depNode) {
                    depNode.dependents.push(taskId);
                }
            });
        });
        
        return graph;
    }

    /**
     * Execute tasks with dependency resolution
     */
    async _executeWithDependencies(tasks, dependencyGraph) {
        const results = new Map();
        const executing = new Set();
        const completed = new Set();
        
        const executeTask = async (taskId) => {
            if (completed.has(taskId) || executing.has(taskId)) {
                return;
            }
            
            const node = dependencyGraph.get(taskId);
            if (!node) return;
            
            // Wait for dependencies
            for (const depId of node.dependencies) {
                if (!completed.has(depId)) {
                    await executeTask(depId);
                }
            }
            
            // Execute this task
            executing.add(taskId);
            try {
                const result = await this.execute(node.task);
                results.set(taskId, { status: 'fulfilled', value: result });
                completed.add(taskId);
            } catch (error) {
                results.set(taskId, { status: 'rejected', reason: error });
            } finally {
                executing.delete(taskId);
            }
        };
        
        // Execute all tasks
        await Promise.all(tasks.map(task => executeTask(task.id)));
        
        return Array.from(results.values());
    }

    /**
     * Normalize task definition
     */
    _normalizeTask(taskDefinition) {
        return {
            id: taskDefinition.id || `task_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
            type: taskDefinition.type || 'generic',
            description: taskDefinition.description || '',
            input: taskDefinition.input || taskDefinition.data,
            dependencies: taskDefinition.dependencies || [],
            priority: taskDefinition.priority || 'normal',
            timeout: taskDefinition.timeout || 300000,
            retries: taskDefinition.retries || 2,
            ...taskDefinition
        };
    }

    /**
     * Decompose task for parallel execution
     */
    _decomposeTask(task, parallelism) {
        const subtasks = [];
        const data = task.input || task.data;
        
        if (Array.isArray(data)) {
            const chunkSize = Math.ceil(data.length / parallelism);
            for (let i = 0; i < data.length; i += chunkSize) {
                subtasks.push({
                    ...task,
                    id: `${task.id}_chunk_${i}`,
                    data: data.slice(i, i + chunkSize)
                });
            }
        } else {
            // For non-array data, create identical subtasks
            for (let i = 0; i < parallelism; i++) {
                subtasks.push({
                    ...task,
                    id: `${task.id}_parallel_${i}`,
                    data
                });
            }
        }
        
        return subtasks;
    }

    /**
     * Aggregate results from parallel execution
     */
    _aggregateResults(results, originalTask) {
        const successful = results.filter(r => r.status === 'fulfilled');
        const failed = results.filter(r => r.status === 'rejected');
        
        return {
            taskId: originalTask.id,
            successful: successful.map(r => r.value),
            failed: failed.map(r => r.reason),
            totalSubtasks: results.length,
            successRate: (successful.length / results.length * 100).toFixed(2) + '%'
        };
    }

    /**
     * Resource estimation methods
     */
    _estimateMemoryRequirement(task) {
        const baseMemory = 64; // MB
        const dataSize = task.input ? JSON.stringify(task.input).length / 1024 / 1024 : 1;
        return Math.max(baseMemory, dataSize * 2);
    }

    _estimateCPURequirement(task) {
        const baseCPU = 0.5; // CPU cores
        const complexity = task.type === 'code-generation' ? 2 : 1;
        return baseCPU * complexity;
    }

    _estimateNetworkRequirement(task) {
        return task.type === 'integration-test' ? 'high' : 'low';
    }

    /**
     * Record task completion
     */
    async _recordTaskCompletion(task, result) {
        this.completedTasks.set(task.id, { task, result, completedAt: Date.now() });
        
        await this.memory.store(`swarm/task/${task.id}/completion`, {
            taskId: task.id,
            result,
            completedAt: new Date().toISOString(),
            metrics: this.metrics
        });
    }

    /**
     * Record task failure
     */
    async _recordTaskFailure(task, error) {
        await this.memory.store(`swarm/task/${task.id}/failure`, {
            taskId: task.id,
            error: error.message,
            stack: error.stack,
            failedAt: new Date().toISOString()
        });
    }

    /**
     * Update execution metrics
     */
    _updateMetrics(status, executionTime) {
        this.metrics.totalTasks++;
        
        if (status === 'completed') {
            this.metrics.completedTasks++;
        } else if (status === 'failed') {
            this.metrics.failedTasks++;
        }
        
        // Update average execution time
        const totalCompleted = this.metrics.completedTasks + this.metrics.failedTasks;
        this.metrics.avgExecutionTime = (
            (this.metrics.avgExecutionTime * (totalCompleted - 1) + executionTime) / totalCompleted
        );
    }

    /**
     * Get orchestrator status
     */
    getStatus() {
        return {
            taskQueue: this.taskQueue.size,
            executingTasks: this.executingTasks.size,
            completedTasks: this.completedTasks.size,
            metrics: { ...this.metrics }
        };
    }
}

module.exports = { TaskOrchestrator };