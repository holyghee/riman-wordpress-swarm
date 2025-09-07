class DynamicResourceAllocator {
  constructor() {
    this.agents = new Map();
    this.tasks = new Map();
    this.resourcePool = {
      cpu: 100,
      memory: 32, // GB
      storage: 1000, // GB
      bandwidth: 1000 // Mbps
    };
    this.allocationHistory = [];
  }

  /**
   * Register an agent in the system
   * @param {string} agentId - Unique agent identifier
   * @param {object} capabilities - Agent capabilities
   * @param {object} requirements - Resource requirements
   */
  registerAgent(agentId, capabilities = [], requirements = {}) {
    const agent = {
      id: agentId,
      capabilities,
      requirements: {
        cpu: requirements.cpu || 1,
        memory: requirements.memory || 1,
        storage: requirements.storage || 10,
        bandwidth: requirements.bandwidth || 10
      },
      status: 'idle',
      currentTasks: [],
      performance: {
        tasksCompleted: 0,
        averageExecutionTime: 0,
        successRate: 1.0
      },
      allocatedResources: {
        cpu: 0,
        memory: 0,
        storage: 0,
        bandwidth: 0
      }
    };

    this.agents.set(agentId, agent);
    console.log(`Agent ${agentId} registered with capabilities: ${capabilities.join(', ')}`);
    return agent;
  }

  /**
   * Submit a task for execution
   * @param {string} taskId - Unique task identifier
   * @param {object} taskSpec - Task specification
   */
  submitTask(taskId, taskSpec) {
    const task = {
      id: taskId,
      type: taskSpec.type,
      priority: taskSpec.priority || 'medium',
      requiredCapabilities: taskSpec.capabilities || [],
      resourceNeeds: taskSpec.resources || { cpu: 1, memory: 1 },
      deadline: taskSpec.deadline,
      status: 'pending',
      assignedAgent: null,
      submittedAt: new Date(),
      estimatedDuration: taskSpec.estimatedDuration || 300000 // 5 minutes default
    };

    this.tasks.set(taskId, task);
    console.log(`Task ${taskId} submitted with priority: ${task.priority}`);
    
    // Trigger allocation
    this.allocateResources();
    return task;
  }

  /**
   * Main resource allocation algorithm
   */
  allocateResources() {
    const pendingTasks = Array.from(this.tasks.values()).filter(t => t.status === 'pending');
    const availableAgents = Array.from(this.agents.values()).filter(a => a.status === 'idle' || a.currentTasks.length < 3);

    // Sort tasks by priority and deadline
    pendingTasks.sort((a, b) => {
      const priorityOrder = { 'critical': 4, 'high': 3, 'medium': 2, 'low': 1 };
      const priorityDiff = priorityOrder[b.priority] - priorityOrder[a.priority];
      if (priorityDiff !== 0) return priorityDiff;
      
      // If same priority, sort by deadline
      if (a.deadline && b.deadline) {
        return new Date(a.deadline) - new Date(b.deadline);
      }
      return 0;
    });

    // Allocate resources for each pending task
    for (const task of pendingTasks) {
      const bestAgent = this.findBestAgent(task, availableAgents);
      if (bestAgent && this.canAllocateResources(bestAgent, task.resourceNeeds)) {
        this.assignTaskToAgent(task, bestAgent);
      }
    }
  }

  /**
   * Find the best agent for a given task
   * @param {object} task - Task to assign
   * @param {Array} agents - Available agents
   * @returns {object} Best matching agent
   */
  findBestAgent(task, agents) {
    let bestAgent = null;
    let bestScore = -1;

    for (const agent of agents) {
      // Check capability match
      const capabilityScore = this.calculateCapabilityMatch(task.requiredCapabilities, agent.capabilities);
      if (capabilityScore === 0) continue; // No capability match

      // Calculate performance score
      const performanceScore = agent.performance.successRate * 0.5 + 
                              (1 / (agent.currentTasks.length + 1)) * 0.3 +
                              (1 / (agent.performance.averageExecutionTime || 1000)) * 0.2;

      const totalScore = capabilityScore * 0.6 + performanceScore * 0.4;

      if (totalScore > bestScore) {
        bestScore = totalScore;
        bestAgent = agent;
      }
    }

    return bestAgent;
  }

  /**
   * Calculate how well agent capabilities match task requirements
   * @param {Array} requiredCapabilities 
   * @param {Array} agentCapabilities 
   * @returns {number} Match score between 0 and 1
   */
  calculateCapabilityMatch(requiredCapabilities, agentCapabilities) {
    if (requiredCapabilities.length === 0) return 1; // No specific requirements
    
    const matches = requiredCapabilities.filter(req => agentCapabilities.includes(req));
    return matches.length / requiredCapabilities.length;
  }

  /**
   * Check if resources can be allocated to an agent
   * @param {object} agent 
   * @param {object} resourceNeeds 
   * @returns {boolean} Can allocate
   */
  canAllocateResources(agent, resourceNeeds) {
    const totalAllocated = this.getTotalAllocatedResources();
    
    return (
      totalAllocated.cpu + resourceNeeds.cpu <= this.resourcePool.cpu &&
      totalAllocated.memory + resourceNeeds.memory <= this.resourcePool.memory &&
      totalAllocated.storage + resourceNeeds.storage <= this.resourcePool.storage &&
      totalAllocated.bandwidth + resourceNeeds.bandwidth <= this.resourcePool.bandwidth
    );
  }

  /**
   * Assign a task to an agent
   * @param {object} task 
   * @param {object} agent 
   */
  assignTaskToAgent(task, agent) {
    // Update task
    task.status = 'assigned';
    task.assignedAgent = agent.id;
    task.assignedAt = new Date();

    // Update agent
    agent.currentTasks.push(task.id);
    agent.status = 'busy';
    
    // Allocate resources
    Object.keys(task.resourceNeeds).forEach(resource => {
      agent.allocatedResources[resource] += task.resourceNeeds[resource];
    });

    // Record allocation
    this.allocationHistory.push({
      taskId: task.id,
      agentId: agent.id,
      resources: task.resourceNeeds,
      timestamp: new Date()
    });

    console.log(`Task ${task.id} assigned to agent ${agent.id}`);
    
    // Simulate task completion after estimated duration
    setTimeout(() => {
      this.completeTask(task.id);
    }, task.estimatedDuration);
  }

  /**
   * Mark a task as completed and free resources
   * @param {string} taskId 
   */
  completeTask(taskId) {
    const task = this.tasks.get(taskId);
    const agent = this.agents.get(task.assignedAgent);
    
    if (!task || !agent) return;

    // Update task
    task.status = 'completed';
    task.completedAt = new Date();
    
    // Update agent
    agent.currentTasks = agent.currentTasks.filter(t => t !== taskId);
    agent.performance.tasksCompleted += 1;
    
    const executionTime = task.completedAt - task.assignedAt;
    agent.performance.averageExecutionTime = 
      (agent.performance.averageExecutionTime * (agent.performance.tasksCompleted - 1) + executionTime) / 
      agent.performance.tasksCompleted;
    
    // Free resources
    Object.keys(task.resourceNeeds).forEach(resource => {
      agent.allocatedResources[resource] -= task.resourceNeeds[resource];
    });
    
    // Update agent status
    agent.status = agent.currentTasks.length > 0 ? 'busy' : 'idle';

    console.log(`Task ${taskId} completed by agent ${agent.id}`);
    
    // Check for pending tasks that can now be allocated
    this.allocateResources();
  }

  /**
   * Get total currently allocated resources
   * @returns {object} Resource totals
   */
  getTotalAllocatedResources() {
    const totals = { cpu: 0, memory: 0, storage: 0, bandwidth: 0 };
    
    for (const agent of this.agents.values()) {
      Object.keys(totals).forEach(resource => {
        totals[resource] += agent.allocatedResources[resource] || 0;
      });
    }
    
    return totals;
  }

  /**
   * Get system status and statistics
   * @returns {object} System status
   */
  getSystemStatus() {
    const totalResources = this.getTotalAllocatedResources();
    const utilizationPercent = {};
    Object.keys(this.resourcePool).forEach(resource => {
      utilizationPercent[resource] = 
        ((totalResources[resource] || 0) / this.resourcePool[resource] * 100).toFixed(1);
    });

    const taskStats = {
      total: this.tasks.size,
      pending: Array.from(this.tasks.values()).filter(t => t.status === 'pending').length,
      assigned: Array.from(this.tasks.values()).filter(t => t.status === 'assigned').length,
      completed: Array.from(this.tasks.values()).filter(t => t.status === 'completed').length
    };

    const agentStats = {
      total: this.agents.size,
      idle: Array.from(this.agents.values()).filter(a => a.status === 'idle').length,
      busy: Array.from(this.agents.values()).filter(a => a.status === 'busy').length
    };

    return {
      resourceUtilization: utilizationPercent,
      tasks: taskStats,
      agents: agentStats,
      timestamp: new Date()
    };
  }
}

module.exports = DynamicResourceAllocator;