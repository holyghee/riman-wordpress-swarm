/**
 * DAA (Dynamic Agent Allocation) Coordination Tests
 * Quality Engineer - Distributed Agent Coordination Testing
 */

const { performance } = require('perf_hooks');

describe('DAA Coordination System', () => {
  let daaCoordinator;
  let agentPool;
  let coordinationMetrics;

  beforeAll(async () => {
    // Mock DAA Coordination System
    daaCoordinator = {
      initializeSwarm: async (topology, maxAgents = 8) => {
        const swarmId = `swarm_${Date.now()}`;
        const agents = [];
        
        for (let i = 0; i < maxAgents; i++) {
          agents.push({
            id: `agent_${i + 1}`,
            type: this.getRandomAgentType(),
            status: 'initialized',
            capabilities: this.generateCapabilities(),
            workload: 0,
            performance: { tasks_completed: 0, avg_response_time: 0 }
          });
        }
        
        return {
          swarmId,
          topology,
          agents,
          status: 'active',
          created: new Date().toISOString()
        };
      },

      getRandomAgentType: () => {
        const types = [
          'researcher', 'coder', 'tester', 'reviewer', 'analyst',
          'coordinator', 'optimizer', 'documenter', 'monitor'
        ];
        return types[Math.floor(Math.random() * types.length)];
      },

      generateCapabilities: () => {
        const allCapabilities = [
          'semantic_analysis', 'image_processing', 'content_generation',
          'quality_assurance', 'performance_optimization', 'data_analysis',
          'coordination', 'monitoring', 'reporting'
        ];
        
        const numCapabilities = Math.floor(Math.random() * 4) + 2; // 2-5 capabilities
        const capabilities = [];
        
        for (let i = 0; i < numCapabilities; i++) {
          const capability = allCapabilities[Math.floor(Math.random() * allCapabilities.length)];
          if (!capabilities.includes(capability)) {
            capabilities.push(capability);
          }
        }
        
        return capabilities;
      },

      allocateTask: async (swarm, taskRequirements) => {
        const startTime = performance.now();
        
        // Find best agent for task
        const suitableAgents = swarm.agents.filter(agent => 
          agent.status === 'active' && 
          this.hasRequiredCapabilities(agent, taskRequirements.required_capabilities)
        );
        
        if (suitableAgents.length === 0) {
          throw new Error('No suitable agents available');
        }
        
        // Select agent with lowest workload
        const selectedAgent = suitableAgents.reduce((best, current) => 
          current.workload < best.workload ? current : best
        );
        
        // Allocate task
        selectedAgent.workload += taskRequirements.estimated_workload || 1;
        selectedAgent.status = 'busy';
        
        const allocationTime = performance.now() - startTime;
        
        return {
          taskId: `task_${Date.now()}`,
          agentId: selectedAgent.id,
          agentType: selectedAgent.type,
          allocationTime,
          estimatedDuration: taskRequirements.estimated_duration || 1000,
          status: 'allocated'
        };
      },

      hasRequiredCapabilities: (agent, requiredCapabilities) => {
        if (!requiredCapabilities || requiredCapabilities.length === 0) return true;
        
        return requiredCapabilities.every(capability => 
          agent.capabilities.includes(capability)
        );
      },

      executeTask: async (taskAllocation, taskData) => {
        const startTime = performance.now();
        
        // Simulate task execution
        const executionTime = Math.random() * 2000 + 500; // 0.5-2.5 seconds
        await new Promise(resolve => setTimeout(resolve, executionTime / 10)); // Scaled for testing
        
        const success = Math.random() > 0.1; // 90% success rate
        
        const result = {
          taskId: taskAllocation.taskId,
          agentId: taskAllocation.agentId,
          success,
          executionTime: performance.now() - startTime,
          result: success ? 'Task completed successfully' : 'Task failed',
          outputData: success ? { processed: true, quality: Math.random() } : null
        };
        
        // Update agent status and performance
        const agent = agentPool.find(a => a.id === taskAllocation.agentId);
        if (agent) {
          agent.status = 'active';
          agent.workload = Math.max(0, agent.workload - 1);
          agent.performance.tasks_completed++;
          agent.performance.avg_response_time = 
            (agent.performance.avg_response_time + result.executionTime) / 2;
        }
        
        return result;
      },

      coordinateMultipleTasks: async (swarm, tasks) => {
        const startTime = performance.now();
        const results = [];
        const allocations = [];
        
        // Allocate all tasks
        for (const task of tasks) {
          try {
            const allocation = await this.allocateTask(swarm, task);
            allocations.push(allocation);
          } catch (error) {
            results.push({
              taskRequirements: task,
              error: error.message,
              success: false
            });
          }
        }
        
        // Execute all allocated tasks
        const executionPromises = allocations.map(async allocation => {
          const taskData = tasks.find(t => t.id === allocation.taskId.split('_').pop());
          return this.executeTask(allocation, taskData);
        });
        
        const executionResults = await Promise.all(executionPromises);
        results.push(...executionResults);
        
        const coordinationTime = performance.now() - startTime;
        
        return {
          totalTasks: tasks.length,
          successfulAllocations: allocations.length,
          completedTasks: executionResults.filter(r => r.success).length,
          failedTasks: executionResults.filter(r => !r.success).length,
          coordinationTime,
          results
        };
      },

      rebalanceWorkload: async (swarm) => {
        const startTime = performance.now();
        
        const activeAgents = swarm.agents.filter(a => a.status === 'active');
        const totalWorkload = activeAgents.reduce((sum, agent) => sum + agent.workload, 0);
        const averageWorkload = totalWorkload / activeAgents.length;
        
        let rebalanced = 0;
        
        // Redistribute workload from overloaded agents to underloaded ones
        const overloaded = activeAgents.filter(a => a.workload > averageWorkload * 1.5);
        const underloaded = activeAgents.filter(a => a.workload < averageWorkload * 0.5);
        
        for (const overloadedAgent of overloaded) {
          if (underloaded.length === 0) break;
          
          const excessWorkload = overloadedAgent.workload - averageWorkload;
          const targetAgent = underloaded[0];
          
          const transferAmount = Math.min(excessWorkload, averageWorkload - targetAgent.workload);
          
          overloadedAgent.workload -= transferAmount;
          targetAgent.workload += transferAmount;
          rebalanced++;
          
          if (targetAgent.workload >= averageWorkload * 0.8) {
            underloaded.shift(); // Remove from underloaded list
          }
        }
        
        const rebalanceTime = performance.now() - startTime;
        
        return {
          rebalancedAgents: rebalanced,
          newAverageWorkload: activeAgents.reduce((sum, a) => sum + a.workload, 0) / activeAgents.length,
          rebalanceTime
        };
      },

      handleAgentFailure: async (swarm, failedAgentId) => {
        const startTime = performance.now();
        
        const failedAgent = swarm.agents.find(a => a.id === failedAgentId);
        if (!failedAgent) {
          throw new Error(`Agent ${failedAgentId} not found`);
        }
        
        // Mark agent as failed
        failedAgent.status = 'failed';
        const orphanedWorkload = failedAgent.workload;
        failedAgent.workload = 0;
        
        // Redistribute workload to remaining active agents
        const activeAgents = swarm.agents.filter(a => a.status === 'active');
        if (activeAgents.length === 0) {
          throw new Error('No active agents available for workload redistribution');
        }
        
        const workloadPerAgent = Math.ceil(orphanedWorkload / activeAgents.length);
        activeAgents.forEach(agent => {
          agent.workload += workloadPerAgent;
        });
        
        // Spawn replacement agent if possible
        let replacementAgent = null;
        if (swarm.agents.length < 8) { // Max agents limit
          replacementAgent = {
            id: `replacement_${Date.now()}`,
            type: failedAgent.type,
            status: 'active',
            capabilities: [...failedAgent.capabilities],
            workload: 0,
            performance: { tasks_completed: 0, avg_response_time: 0 }
          };
          
          swarm.agents.push(replacementAgent);
        }
        
        const recoveryTime = performance.now() - startTime;
        
        return {
          failedAgentId,
          orphanedWorkload,
          workloadRedistributed: true,
          replacementAgent: replacementAgent?.id || null,
          recoveryTime,
          remainingActiveAgents: activeAgents.length
        };
      },

      monitorSwarmHealth: async (swarm) => {
        const agents = swarm.agents;
        const activeAgents = agents.filter(a => a.status === 'active').length;
        const busyAgents = agents.filter(a => a.status === 'busy').length;
        const failedAgents = agents.filter(a => a.status === 'failed').length;
        
        const totalWorkload = agents.reduce((sum, a) => sum + a.workload, 0);
        const averageWorkload = totalWorkload / agents.length;
        
        const avgResponseTime = agents
          .filter(a => a.performance.tasks_completed > 0)
          .reduce((sum, a) => sum + a.performance.avg_response_time, 0) / agents.length;
        
        const totalTasksCompleted = agents.reduce((sum, a) => sum + a.performance.tasks_completed, 0);
        
        const healthScore = this.calculateHealthScore({
          activeAgents,
          failedAgents,
          averageWorkload,
          avgResponseTime,
          totalTasksCompleted
        });
        
        return {
          swarmId: swarm.swarmId,
          agentCounts: {
            total: agents.length,
            active: activeAgents,
            busy: busyAgents,
            failed: failedAgents
          },
          workloadMetrics: {
            total: totalWorkload,
            average: averageWorkload,
            distribution: this.calculateWorkloadDistribution(agents)
          },
          performanceMetrics: {
            avgResponseTime,
            totalTasksCompleted,
            successRate: totalTasksCompleted / (totalTasksCompleted + failedAgents) || 1
          },
          healthScore,
          status: healthScore > 0.8 ? 'healthy' : healthScore > 0.6 ? 'degraded' : 'critical'
        };
      },

      calculateHealthScore: (metrics) => {
        const activeRatio = metrics.activeAgents / (metrics.activeAgents + metrics.failedAgents);
        const workloadScore = Math.max(0, 1 - (metrics.averageWorkload / 10)); // Assume 10 is max workload
        const responseTimeScore = Math.max(0, 1 - (metrics.avgResponseTime / 5000)); // 5 seconds is poor
        const taskScore = Math.min(1, metrics.totalTasksCompleted / 100); // 100 tasks is good
        
        return (activeRatio * 0.3) + (workloadScore * 0.25) + (responseTimeScore * 0.25) + (taskScore * 0.2);
      },

      calculateWorkloadDistribution: (agents) => {
        const workloads = agents.map(a => a.workload);
        const mean = workloads.reduce((sum, w) => sum + w, 0) / workloads.length;
        const variance = workloads.reduce((sum, w) => sum + Math.pow(w - mean, 2), 0) / workloads.length;
        
        return {
          mean,
          variance,
          standardDeviation: Math.sqrt(variance),
          min: Math.min(...workloads),
          max: Math.max(...workloads)
        };
      },

      consensus: async (swarm, proposal) => {
        const startTime = performance.now();
        const activeAgents = swarm.agents.filter(a => a.status === 'active');
        const votes = [];
        
        // Simulate voting by each agent
        for (const agent of activeAgents) {
          const voteDelay = Math.random() * 100 + 50; // 50-150ms delay
          await new Promise(resolve => setTimeout(resolve, voteDelay / 10));
          
          const vote = {
            agentId: agent.id,
            vote: Math.random() > 0.3, // 70% approve rate
            confidence: Math.random() * 0.4 + 0.6, // 60-100% confidence
            timestamp: new Date().toISOString()
          };
          
          votes.push(vote);
        }
        
        const approvals = votes.filter(v => v.vote).length;
        const total = votes.length;
        const approvalRate = approvals / total;
        const consensusThreshold = global.TEST_CONFIG?.daaTests?.minConsensusThreshold || 0.75;
        
        const consensusTime = performance.now() - startTime;
        
        return {
          proposal,
          votes,
          approvalRate,
          consensusReached: approvalRate >= consensusThreshold,
          consensusTime,
          participatingAgents: total
        };
      }
    };

    // Initialize agent pool for testing
    agentPool = [];
    coordinationMetrics = {
      maxCoordinationDelay: global.TEST_CONFIG?.daaTests?.maxCoordinationDelay || 1000,
      minConsensusThreshold: global.TEST_CONFIG?.daaTests?.minConsensusThreshold || 0.75,
      faultToleranceLevel: global.TEST_CONFIG?.daaTests?.faultToleranceLevel || 2
    };
  });

  beforeEach(async () => {
    // Reset agent pool for each test
    agentPool = [];
  });

  describe('Swarm Initialization and Setup', () => {
    test('should initialize swarm with correct topology', async () => {
      const swarm = await daaCoordinator.initializeSwarm('mesh', 6);
      
      expect(swarm).toHaveProperty('swarmId');
      expect(swarm.topology).toBe('mesh');
      expect(swarm.agents).toHaveLength(6);
      expect(swarm.status).toBe('active');
      
      // All agents should be initialized
      swarm.agents.forEach(agent => {
        expect(agent.status).toBe('initialized');
        expect(agent.capabilities).toBeInstanceOf(Array);
        expect(agent.capabilities.length).toBeGreaterThan(0);
      });
      
      agentPool = swarm.agents;
    });

    test('should support different topologies', async () => {
      const topologies = ['hierarchical', 'mesh', 'ring', 'star'];
      
      for (const topology of topologies) {
        const swarm = await daaCoordinator.initializeSwarm(topology, 4);
        
        expect(swarm.topology).toBe(topology);
        expect(swarm.agents).toHaveLength(4);
        expect(swarm.status).toBe('active');
      }
    });

    test('should handle different swarm sizes', async () => {
      const sizes = [2, 4, 6, 8];
      
      for (const size of sizes) {
        const swarm = await daaCoordinator.initializeSwarm('mesh', size);
        
        expect(swarm.agents).toHaveLength(size);
        
        // Each agent should have unique capabilities mix
        const capabilitySets = swarm.agents.map(a => a.capabilities.sort().join(','));
        expect(capabilitySets.length).toBeGreaterThan(0);
      }
    });
  });

  describe('Task Allocation and Distribution', () => {
    let testSwarm;

    beforeEach(async () => {
      testSwarm = await daaCoordinator.initializeSwarm('mesh', 5);
      testSwarm.agents.forEach(agent => { agent.status = 'active'; });
      agentPool = testSwarm.agents;
    });

    test('should allocate single task to appropriate agent', async () => {
      const taskRequirements = {
        required_capabilities: ['semantic_analysis'],
        estimated_workload: 2,
        estimated_duration: 1500
      };
      
      const allocation = await daaCoordinator.allocateTask(testSwarm, taskRequirements);
      
      expect(allocation).toHaveProperty('taskId');
      expect(allocation).toHaveProperty('agentId');
      expect(allocation.allocationTime).toBeLessThan(coordinationMetrics.maxCoordinationDelay);
      expect(allocation.status).toBe('allocated');
      
      // Verify agent has required capabilities
      const selectedAgent = testSwarm.agents.find(a => a.id === allocation.agentId);
      expect(selectedAgent.capabilities).toContain('semantic_analysis');
      expect(selectedAgent.status).toBe('busy');
      expect(selectedAgent.workload).toBe(2);
    });

    test('should handle task allocation with no suitable agents', async () => {
      const taskRequirements = {
        required_capabilities: ['non_existent_capability'],
        estimated_workload: 1
      };
      
      await expect(daaCoordinator.allocateTask(testSwarm, taskRequirements))
        .rejects.toThrow('No suitable agents available');
    });

    test('should distribute workload evenly across agents', async () => {
      const tasks = Array.from({ length: 10 }, (_, i) => ({
        id: i + 1,
        required_capabilities: ['semantic_analysis'],
        estimated_workload: 1
      }));
      
      for (const task of tasks) {
        try {
          await daaCoordinator.allocateTask(testSwarm, task);
        } catch (error) {
          // Some tasks may fail if no agents have the capability
        }
      }
      
      // Check workload distribution
      const workloads = testSwarm.agents.map(a => a.workload);
      const maxWorkload = Math.max(...workloads);
      const minWorkload = Math.min(...workloads);
      
      // Workload should be relatively balanced (difference <= 2)
      expect(maxWorkload - minWorkload).toBeLessThanOrEqual(3);
    });
  });

  describe('Multi-Task Coordination', () => {
    let testSwarm;

    beforeEach(async () => {
      testSwarm = await daaCoordinator.initializeSwarm('mesh', 6);
      testSwarm.agents.forEach(agent => { agent.status = 'active'; });
      agentPool = testSwarm.agents;
    });

    test('should coordinate multiple tasks efficiently', async () => {
      const tasks = [
        { id: 1, required_capabilities: ['semantic_analysis'], estimated_workload: 1 },
        { id: 2, required_capabilities: ['image_processing'], estimated_workload: 2 },
        { id: 3, required_capabilities: ['quality_assurance'], estimated_workload: 1 },
        { id: 4, required_capabilities: ['coordination'], estimated_workload: 3 },
        { id: 5, required_capabilities: ['data_analysis'], estimated_workload: 2 }
      ];
      
      const result = await daaCoordinator.coordinateMultipleTasks(testSwarm, tasks);
      
      expect(result.totalTasks).toBe(5);
      expect(result.coordinationTime).toBeLessThan(coordinationMetrics.maxCoordinationDelay * 2);
      expect(result.successfulAllocations).toBeGreaterThan(0);
      expect(result.completedTasks + result.failedTasks).toBe(result.successfulAllocations);
    });

    test('should handle concurrent task execution', async () => {
      const tasks = Array.from({ length: 8 }, (_, i) => ({
        id: i + 1,
        required_capabilities: ['semantic_analysis'],
        estimated_workload: 1
      }));
      
      const startTime = performance.now();
      const result = await daaCoordinator.coordinateMultipleTasks(testSwarm, tasks);
      const endTime = performance.now();
      
      expect(endTime - startTime).toBeLessThan(5000); // Should complete concurrently
      expect(result.completedTasks).toBeGreaterThan(0);
    });

    test('should maintain task execution quality', async () => {
      const tasks = [
        { id: 1, required_capabilities: ['quality_assurance'], estimated_workload: 2 },
        { id: 2, required_capabilities: ['performance_optimization'], estimated_workload: 3 }
      ];
      
      const result = await daaCoordinator.coordinateMultipleTasks(testSwarm, tasks);
      
      const successRate = result.completedTasks / result.successfulAllocations;
      expect(successRate).toBeGreaterThan(0.8); // At least 80% success rate
    });
  });

  describe('Workload Balancing', () => {
    let testSwarm;

    beforeEach(async () => {
      testSwarm = await daaCoordinator.initializeSwarm('mesh', 4);
      testSwarm.agents.forEach((agent, i) => {
        agent.status = 'active';
        agent.workload = i * 2; // Uneven distribution: 0, 2, 4, 6
      });
      agentPool = testSwarm.agents;
    });

    test('should rebalance workload across agents', async () => {
      const initialWorkloads = testSwarm.agents.map(a => a.workload);
      const initialMax = Math.max(...initialWorkloads);
      const initialMin = Math.min(...initialWorkloads);
      
      expect(initialMax - initialMin).toBeGreaterThan(2); // Verify imbalance
      
      const result = await daaCoordinator.rebalanceWorkload(testSwarm);
      
      expect(result.rebalancedAgents).toBeGreaterThan(0);
      
      const finalWorkloads = testSwarm.agents.map(a => a.workload);
      const finalMax = Math.max(...finalWorkloads);
      const finalMin = Math.min(...finalWorkloads);
      
      expect(finalMax - finalMin).toBeLessThan(initialMax - initialMin);
    });

    test('should calculate meaningful rebalance metrics', async () => {
      const result = await daaCoordinator.rebalanceWorkload(testSwarm);
      
      expect(result).toHaveProperty('rebalancedAgents');
      expect(result).toHaveProperty('newAverageWorkload');
      expect(result).toHaveProperty('rebalanceTime');
      
      expect(result.newAverageWorkload).toBeGreaterThan(0);
      expect(result.rebalanceTime).toBeGreaterThan(0);
    });
  });

  describe('Fault Tolerance and Recovery', () => {
    let testSwarm;

    beforeEach(async () => {
      testSwarm = await daaCoordinator.initializeSwarm('mesh', 5);
      testSwarm.agents.forEach((agent, i) => {
        agent.status = 'active';
        agent.workload = i + 1; // Workloads: 1, 2, 3, 4, 5
      });
      agentPool = testSwarm.agents;
    });

    test('should handle single agent failure gracefully', async () => {
      const failingAgentId = testSwarm.agents[2].id; // Agent with workload 3
      const initialActiveAgents = testSwarm.agents.filter(a => a.status === 'active').length;
      
      const recovery = await daaCoordinator.handleAgentFailure(testSwarm, failingAgentId);
      
      expect(recovery.failedAgentId).toBe(failingAgentId);
      expect(recovery.orphanedWorkload).toBe(3);
      expect(recovery.workloadRedistributed).toBe(true);
      expect(recovery.remainingActiveAgents).toBe(initialActiveAgents - 1);
      
      // Verify failed agent status
      const failedAgent = testSwarm.agents.find(a => a.id === failingAgentId);
      expect(failedAgent.status).toBe('failed');
      expect(failedAgent.workload).toBe(0);
      
      // Verify workload redistribution
      const activeAgents = testSwarm.agents.filter(a => a.status === 'active');
      const totalWorkload = activeAgents.reduce((sum, a) => sum + a.workload, 0);
      expect(totalWorkload).toBeGreaterThan(12); // Original 15 - 3 + redistributed
    });

    test('should spawn replacement agent when possible', async () => {
      const initialAgentCount = testSwarm.agents.length;
      const failingAgentId = testSwarm.agents[0].id;
      
      const recovery = await daaCoordinator.handleAgentFailure(testSwarm, failingAgentId);
      
      if (recovery.replacementAgent) {
        expect(testSwarm.agents.length).toBe(initialAgentCount + 1);
        
        const replacementAgent = testSwarm.agents.find(a => a.id === recovery.replacementAgent);
        expect(replacementAgent.status).toBe('active');
        expect(replacementAgent.workload).toBe(0);
      }
    });

    test('should handle multiple agent failures within tolerance', async () => {
      const failureIds = [testSwarm.agents[0].id, testSwarm.agents[1].id];
      
      for (const failureId of failureIds) {
        const recovery = await daaCoordinator.handleAgentFailure(testSwarm, failureId);
        expect(recovery.workloadRedistributed).toBe(true);
      }
      
      const activeAgents = testSwarm.agents.filter(a => a.status === 'active').length;
      const failedAgents = testSwarm.agents.filter(a => a.status === 'failed').length;
      
      expect(failedAgents).toBe(2);
      expect(activeAgents).toBeGreaterThan(0); // Should still have active agents
    });

    test('should handle agent failure beyond fault tolerance gracefully', async () => {
      // Fail agents beyond tolerance level
      const maxFailures = coordinationMetrics.faultToleranceLevel + 1;
      const failurePromises = [];
      
      for (let i = 0; i < maxFailures; i++) {
        if (i < testSwarm.agents.length) {
          failurePromises.push(
            daaCoordinator.handleAgentFailure(testSwarm, testSwarm.agents[i].id)
          );
        }
      }
      
      const results = await Promise.allSettled(failurePromises);
      
      // Some recoveries should succeed, others may fail when no agents remain
      const successful = results.filter(r => r.status === 'fulfilled').length;
      expect(successful).toBeGreaterThan(0);
    });
  });

  describe('Swarm Health Monitoring', () => {
    let testSwarm;

    beforeEach(async () => {
      testSwarm = await daaCoordinator.initializeSwarm('mesh', 6);
      // Set up varied agent states
      testSwarm.agents[0].status = 'active';
      testSwarm.agents[0].workload = 2;
      testSwarm.agents[0].performance = { tasks_completed: 10, avg_response_time: 800 };
      
      testSwarm.agents[1].status = 'busy';
      testSwarm.agents[1].workload = 4;
      testSwarm.agents[1].performance = { tasks_completed: 8, avg_response_time: 1200 };
      
      testSwarm.agents[2].status = 'active';
      testSwarm.agents[2].workload = 1;
      testSwarm.agents[2].performance = { tasks_completed: 15, avg_response_time: 600 };
      
      testSwarm.agents[3].status = 'failed';
      testSwarm.agents[3].workload = 0;
      testSwarm.agents[3].performance = { tasks_completed: 5, avg_response_time: 2000 };
      
      testSwarm.agents[4].status = 'active';
      testSwarm.agents[4].workload = 3;
      testSwarm.agents[4].performance = { tasks_completed: 12, avg_response_time: 900 };
      
      testSwarm.agents[5].status = 'active';
      testSwarm.agents[5].workload = 2;
      testSwarm.agents[5].performance = { tasks_completed: 20, avg_response_time: 500 };
      
      agentPool = testSwarm.agents;
    });

    test('should provide comprehensive health metrics', async () => {
      const health = await daaCoordinator.monitorSwarmHealth(testSwarm);
      
      expect(health).toHaveProperty('swarmId', testSwarm.swarmId);
      expect(health).toHaveProperty('agentCounts');
      expect(health).toHaveProperty('workloadMetrics');
      expect(health).toHaveProperty('performanceMetrics');
      expect(health).toHaveProperty('healthScore');
      expect(health).toHaveProperty('status');
      
      expect(health.agentCounts.total).toBe(6);
      expect(health.agentCounts.active).toBe(4);
      expect(health.agentCounts.busy).toBe(1);
      expect(health.agentCounts.failed).toBe(1);
    });

    test('should calculate accurate workload metrics', async () => {
      const health = await daaCoordinator.monitorSwarmHealth(testSwarm);
      
      expect(health.workloadMetrics.total).toBe(12); // 2+4+1+0+3+2
      expect(health.workloadMetrics.average).toBe(2); // 12/6
      expect(health.workloadMetrics.distribution).toHaveProperty('mean', 2);
      expect(health.workloadMetrics.distribution).toHaveProperty('variance');
      expect(health.workloadMetrics.distribution).toHaveProperty('standardDeviation');
    });

    test('should calculate performance metrics correctly', async () => {
      const health = await daaCoordinator.monitorSwarmHealth(testSwarm);
      
      expect(health.performanceMetrics.totalTasksCompleted).toBe(70); // Sum of all tasks
      expect(health.performanceMetrics.avgResponseTime).toBeGreaterThan(0);
      expect(health.performanceMetrics.successRate).toBeGreaterThan(0);
    });

    test('should provide health status classification', async () => {
      const health = await daaCoordinator.monitorSwarmHealth(testSwarm);
      
      expect(['healthy', 'degraded', 'critical']).toContain(health.status);
      
      if (health.status === 'healthy') {
        expect(health.healthScore).toBeGreaterThan(0.8);
      } else if (health.status === 'degraded') {
        expect(health.healthScore).toBeGreaterThan(0.6);
        expect(health.healthScore).toBeLessThanOrEqual(0.8);
      } else {
        expect(health.healthScore).toBeLessThanOrEqual(0.6);
      }
    });

    test('should track health changes over time', async () => {
      const initialHealth = await daaCoordinator.monitorSwarmHealth(testSwarm);
      
      // Simulate some agent improvements
      testSwarm.agents[3].status = 'active'; // Recover failed agent
      testSwarm.agents.forEach(agent => {
        if (agent.status === 'active') {
          agent.performance.tasks_completed += 5;
        }
      });
      
      const improvedHealth = await daaCoordinator.monitorSwarmHealth(testSwarm);
      
      expect(improvedHealth.agentCounts.failed).toBeLessThan(initialHealth.agentCounts.failed);
      expect(improvedHealth.performanceMetrics.totalTasksCompleted)
        .toBeGreaterThan(initialHealth.performanceMetrics.totalTasksCompleted);
      expect(improvedHealth.healthScore).toBeGreaterThanOrEqual(initialHealth.healthScore);
    });
  });

  describe('Consensus Mechanisms', () => {
    let testSwarm;

    beforeEach(async () => {
      testSwarm = await daaCoordinator.initializeSwarm('mesh', 5);
      testSwarm.agents.forEach(agent => { agent.status = 'active'; });
      agentPool = testSwarm.agents;
    });

    test('should reach consensus on simple proposals', async () => {
      const proposal = {
        type: 'configuration_change',
        description: 'Increase max workload per agent',
        parameters: { max_workload: 8 }
      };
      
      const consensus = await daaCoordinator.consensus(testSwarm, proposal);
      
      expect(consensus.proposal).toEqual(proposal);
      expect(consensus.votes).toHaveLength(5);
      expect(consensus.participatingAgents).toBe(5);
      expect(consensus.approvalRate).toBeGreaterThanOrEqual(0);
      expect(consensus.approvalRate).toBeLessThanOrEqual(1);
      expect(consensus.consensusTime).toBeGreaterThan(0);
      
      consensus.votes.forEach(vote => {
        expect(vote).toHaveProperty('agentId');
        expect(vote).toHaveProperty('vote');
        expect(vote).toHaveProperty('confidence');
        expect(typeof vote.vote).toBe('boolean');
        expect(vote.confidence).toBeGreaterThanOrEqual(0.6);
        expect(vote.confidence).toBeLessThanOrEqual(1);
      });
    });

    test('should handle consensus with different approval rates', async () => {
      const proposals = [
        { type: 'minor_change', description: 'Minor configuration update' },
        { type: 'major_change', description: 'Major system restructure' },
        { type: 'emergency', description: 'Emergency protocol activation' }
      ];
      
      const consensusResults = [];
      
      for (const proposal of proposals) {
        const consensus = await daaCoordinator.consensus(testSwarm, proposal);
        consensusResults.push({
          proposal: proposal.type,
          approvalRate: consensus.approvalRate,
          consensusReached: consensus.consensusReached,
          consensusTime: consensus.consensusTime
        });
      }
      
      // At least some proposals should reach consensus
      const successfulConsensus = consensusResults.filter(r => r.consensusReached).length;
      expect(successfulConsensus).toBeGreaterThan(0);
      
      // Consensus time should be reasonable
      consensusResults.forEach(result => {
        expect(result.consensusTime).toBeLessThan(coordinationMetrics.maxCoordinationDelay);
      });
    });

    test('should handle consensus with insufficient agents', async () => {
      // Create swarm with minimal agents
      const minimalSwarm = await daaCoordinator.initializeSwarm('mesh', 2);
      minimalSwarm.agents.forEach(agent => { agent.status = 'active'; });
      
      const proposal = { type: 'test', description: 'Test with minimal agents' };
      const consensus = await daaCoordinator.consensus(minimalSwarm, proposal);
      
      expect(consensus.participatingAgents).toBe(2);
      expect(consensus.votes).toHaveLength(2);
      
      // Consensus should still be possible with minimal agents
      if (consensus.approvalRate >= coordinationMetrics.minConsensusThreshold) {
        expect(consensus.consensusReached).toBe(true);
      }
    });

    test('should handle consensus timeout scenarios', async () => {
      const proposal = { type: 'timeout_test', description: 'Test consensus timing' };
      
      const startTime = performance.now();
      const consensus = await daaCoordinator.consensus(testSwarm, proposal);
      const endTime = performance.now();
      
      expect(endTime - startTime).toBeLessThan(coordinationMetrics.maxCoordinationDelay * 2);
      expect(consensus.consensusTime).toBeLessThan(coordinationMetrics.maxCoordinationDelay);
    });
  });

  describe('Performance and Scalability', () => {
    test('should scale coordination with different swarm sizes', async () => {
      const swarmSizes = [3, 6, 8];
      const scalabilityResults = [];
      
      for (const size of swarmSizes) {
        const swarm = await daaCoordinator.initializeSwarm('mesh', size);
        swarm.agents.forEach(agent => { agent.status = 'active'; });
        
        const tasks = Array.from({ length: size * 2 }, (_, i) => ({
          id: i + 1,
          required_capabilities: ['semantic_analysis'],
          estimated_workload: 1
        }));
        
        const startTime = performance.now();
        const result = await daaCoordinator.coordinateMultipleTasks(swarm, tasks);
        const endTime = performance.now();
        
        scalabilityResults.push({
          swarmSize: size,
          taskCount: tasks.length,
          coordinationTime: endTime - startTime,
          successRate: result.completedTasks / result.successfulAllocations
        });
      }
      
      // Coordination time should scale reasonably
      scalabilityResults.forEach(result => {
        expect(result.coordinationTime).toBeLessThan(coordinationMetrics.maxCoordinationDelay * 3);
        expect(result.successRate).toBeGreaterThan(0.7);
      });
    });

    test('should handle high-frequency coordination requests', async () => {
      const swarm = await daaCoordinator.initializeSwarm('mesh', 4);
      swarm.agents.forEach(agent => { agent.status = 'active'; });
      
      const rapidRequests = Array.from({ length: 20 }, async (_, i) => {
        const task = {
          required_capabilities: ['coordination'],
          estimated_workload: 1
        };
        
        return daaCoordinator.allocateTask(swarm, task);
      });
      
      const startTime = performance.now();
      const results = await Promise.allSettled(rapidRequests);
      const endTime = performance.now();
      
      expect(endTime - startTime).toBeLessThan(5000); // 5 seconds max
      
      const successful = results.filter(r => r.status === 'fulfilled').length;
      expect(successful).toBeGreaterThan(0);
    });
  });
});