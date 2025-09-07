/**
 * Performance Monitoring and Analytics
 * Real-time system performance tracking and analytics
 */

const winston = require('winston');
const { EventEmitter } = require('events');
const { v4: uuidv4 } = require('uuid');

class PerformanceMonitor extends EventEmitter {
  constructor(config = {}) {
    super();
    
    this.config = {
      metricsInterval: 5000,
      performanceInterval: 10000,
      alertThresholds: {
        latency: 1000,
        errorRate: 0.05,
        memoryUsage: 0.9,
        cpuUsage: 0.8
      },
      historySize: 1000,
      enableAlerts: true,
      enableAnalytics: true,
      ...config
    };

    this.logger = winston.createLogger({
      level: 'info',
      format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.json()
      ),
      defaultMeta: { service: 'performance-monitor' },
      transports: [
        new winston.transports.Console(),
        new winston.transports.File({ filename: 'performance.log' })
      ]
    });

    this.metrics = {
      system: {
        uptime: 0,
        memory: {},
        cpu: {},
        timestamp: null
      },
      neural: {
        totalInferences: 0,
        averageLatency: 0,
        errorRate: 0,
        cacheHitRate: 0,
        modelLoads: 0,
        trainingJobs: 0
      },
      wordpress: {
        totalRequests: 0,
        averageResponseTime: 0,
        contentAnalyzed: 0,
        imagesProcessed: 0
      }
    };

    this.history = {
      system: [],
      neural: [],
      wordpress: [],
      alerts: []
    };

    this.components = new Map();
    this.alerts = [];
    this.initialized = false;
  }

  async initialize() {
    try {
      this.logger.info('Initializing Performance Monitor...');
      
      this.setupMetricsCollection();
      this.setupPerformanceTracking();
      this.setupAlertSystem();
      
      this.initialized = true;
      this.logger.info('Performance Monitor initialized successfully');
    } catch (error) {
      this.logger.error('Failed to initialize Performance Monitor:', error);
      throw error;
    }
  }

  setupMetricsCollection() {
    // System metrics collection
    this.systemMetricsInterval = setInterval(() => {
      this.collectSystemMetrics();
    }, this.config.metricsInterval);

    // Neural system metrics collection
    this.neuralMetricsInterval = setInterval(() => {
      this.collectNeuralMetrics();
    }, this.config.performanceInterval);

    // WordPress integration metrics
    this.wordpressMetricsInterval = setInterval(() => {
      this.collectWordPressMetrics();
    }, this.config.performanceInterval);

    this.logger.info('Metrics collection setup complete');
  }

  setupPerformanceTracking() {
    // Performance analytics
    this.analyticsInterval = setInterval(() => {
      this.generatePerformanceAnalytics();
    }, this.config.performanceInterval * 2);

    this.logger.info('Performance tracking setup complete');
  }

  setupAlertSystem() {
    if (!this.config.enableAlerts) return;

    // Alert evaluation
    this.alertInterval = setInterval(() => {
      this.evaluateAlerts();
    }, this.config.metricsInterval);

    this.logger.info('Alert system setup complete');
  }

  registerComponent(componentId, component) {
    this.components.set(componentId, {
      id: componentId,
      instance: component,
      metrics: {},
      lastUpdate: new Date(),
      status: 'active'
    });

    this.logger.info(`Component registered: ${componentId}`);
  }

  unregisterComponent(componentId) {
    this.components.delete(componentId);
    this.logger.info(`Component unregistered: ${componentId}`);
  }

  collectSystemMetrics() {
    try {
      const memUsage = process.memoryUsage();
      const cpuUsage = process.cpuUsage();
      const uptime = process.uptime();

      this.metrics.system = {
        uptime: uptime,
        memory: {
          rss: memUsage.rss,
          heapTotal: memUsage.heapTotal,
          heapUsed: memUsage.heapUsed,
          external: memUsage.external,
          usage: memUsage.heapUsed / memUsage.heapTotal
        },
        cpu: {
          user: cpuUsage.user,
          system: cpuUsage.system,
          usage: this.calculateCpuUsage(cpuUsage)
        },
        timestamp: new Date().toISOString()
      };

      // Add to history
      this.addToHistory('system', this.metrics.system);

      this.emit('systemMetrics', this.metrics.system);

    } catch (error) {
      this.logger.error('Failed to collect system metrics:', error);
    }
  }

  collectNeuralMetrics() {
    try {
      // Collect metrics from neural system components
      const neuralMetrics = {
        totalInferences: 0,
        averageLatency: 0,
        errorRate: 0,
        cacheHitRate: 0,
        modelLoads: 0,
        trainingJobs: 0,
        activeModels: 0,
        memoryUsage: 0,
        timestamp: new Date().toISOString()
      };

      // Aggregate metrics from components
      for (const [componentId, component] of this.components) {
        if (component.instance && typeof component.instance.getMetrics === 'function') {
          try {
            const componentMetrics = component.instance.getMetrics();
            this.aggregateNeuralMetrics(neuralMetrics, componentMetrics);
          } catch (error) {
            this.logger.warn(`Failed to get metrics from component ${componentId}:`, error);
          }
        }
      }

      this.metrics.neural = neuralMetrics;
      this.addToHistory('neural', neuralMetrics);

      this.emit('neuralMetrics', neuralMetrics);

    } catch (error) {
      this.logger.error('Failed to collect neural metrics:', error);
    }
  }

  collectWordPressMetrics() {
    try {
      // Collect WordPress integration metrics
      const wpComponent = this.components.get('wordpress-integration');
      
      if (wpComponent && wpComponent.instance) {
        const wpMetrics = {
          totalRequests: 0,
          averageResponseTime: 0,
          contentAnalyzed: 0,
          imagesProcessed: 0,
          cacheHitRate: 0,
          errorRate: 0,
          timestamp: new Date().toISOString()
        };

        // Get metrics from WordPress integration if available
        if (typeof wpComponent.instance.getMetrics === 'function') {
          const componentMetrics = wpComponent.instance.getMetrics();
          Object.assign(wpMetrics, componentMetrics);
        }

        this.metrics.wordpress = wpMetrics;
        this.addToHistory('wordpress', wpMetrics);

        this.emit('wordpressMetrics', wpMetrics);
      }

    } catch (error) {
      this.logger.error('Failed to collect WordPress metrics:', error);
    }
  }

  aggregateNeuralMetrics(target, source) {
    if (source.totalRequests) target.totalInferences += source.totalRequests;
    if (source.averageLatency) {
      target.averageLatency = (target.averageLatency + source.averageLatency) / 2;
    }
    if (source.errorRate) {
      target.errorRate = (target.errorRate + source.errorRate) / 2;
    }
    if (source.cacheHitRate) {
      target.cacheHitRate = (target.cacheHitRate + source.cacheHitRate) / 2;
    }
    if (source.loadedModels) target.activeModels += source.loadedModels;
    if (source.memoryUsage) target.memoryUsage += source.memoryUsage.heapUsed || 0;
  }

  calculateCpuUsage(cpuUsage) {
    // Simplified CPU usage calculation
    const totalUsage = cpuUsage.user + cpuUsage.system;
    return totalUsage / 1000000; // Convert from microseconds
  }

  addToHistory(category, metrics) {
    if (!this.history[category]) {
      this.history[category] = [];
    }

    this.history[category].push({
      ...metrics,
      timestamp: new Date().toISOString()
    });

    // Limit history size
    if (this.history[category].length > this.config.historySize) {
      this.history[category] = this.history[category].slice(-this.config.historySize / 2);
    }
  }

  generatePerformanceAnalytics() {
    if (!this.config.enableAnalytics) return;

    try {
      const analytics = {
        id: uuidv4(),
        timestamp: new Date().toISOString(),
        system: this.analyzeSystemPerformance(),
        neural: this.analyzeNeuralPerformance(),
        wordpress: this.analyzeWordPressPerformance(),
        trends: this.analyzeTrends(),
        recommendations: this.generateRecommendations()
      };

      this.emit('performanceAnalytics', analytics);
      this.logger.info('Performance analytics generated');

    } catch (error) {
      this.logger.error('Failed to generate performance analytics:', error);
    }
  }

  analyzeSystemPerformance() {
    const systemHistory = this.history.system.slice(-10); // Last 10 measurements
    
    if (systemHistory.length === 0) return null;

    const avgMemoryUsage = systemHistory.reduce((sum, m) => sum + m.memory.usage, 0) / systemHistory.length;
    const avgCpuUsage = systemHistory.reduce((sum, m) => sum + m.cpu.usage, 0) / systemHistory.length;

    return {
      averageMemoryUsage: avgMemoryUsage,
      averageCpuUsage: avgCpuUsage,
      currentUptime: systemHistory[systemHistory.length - 1]?.uptime || 0,
      memoryTrend: this.calculateTrend(systemHistory, 'memory.usage'),
      cpuTrend: this.calculateTrend(systemHistory, 'cpu.usage'),
      status: this.determineSystemStatus(avgMemoryUsage, avgCpuUsage)
    };
  }

  analyzeNeuralPerformance() {
    const neuralHistory = this.history.neural.slice(-10);
    
    if (neuralHistory.length === 0) return null;

    const avgLatency = neuralHistory.reduce((sum, m) => sum + m.averageLatency, 0) / neuralHistory.length;
    const avgErrorRate = neuralHistory.reduce((sum, m) => sum + m.errorRate, 0) / neuralHistory.length;
    const avgCacheHitRate = neuralHistory.reduce((sum, m) => sum + m.cacheHitRate, 0) / neuralHistory.length;

    return {
      averageLatency: avgLatency,
      averageErrorRate: avgErrorRate,
      averageCacheHitRate: avgCacheHitRate,
      totalInferences: neuralHistory[neuralHistory.length - 1]?.totalInferences || 0,
      activeModels: neuralHistory[neuralHistory.length - 1]?.activeModels || 0,
      latencyTrend: this.calculateTrend(neuralHistory, 'averageLatency'),
      errorTrend: this.calculateTrend(neuralHistory, 'errorRate'),
      status: this.determineNeuralStatus(avgLatency, avgErrorRate)
    };
  }

  analyzeWordPressPerformance() {
    const wpHistory = this.history.wordpress.slice(-10);
    
    if (wpHistory.length === 0) return null;

    const avgResponseTime = wpHistory.reduce((sum, m) => sum + m.averageResponseTime, 0) / wpHistory.length;
    const totalContent = wpHistory[wpHistory.length - 1]?.contentAnalyzed || 0;
    const totalImages = wpHistory[wpHistory.length - 1]?.imagesProcessed || 0;

    return {
      averageResponseTime: avgResponseTime,
      totalContentAnalyzed: totalContent,
      totalImagesProcessed: totalImages,
      responseTrend: this.calculateTrend(wpHistory, 'averageResponseTime'),
      status: this.determineWordPressStatus(avgResponseTime)
    };
  }

  analyzeTrends() {
    return {
      systemMemory: this.calculateTrend(this.history.system.slice(-20), 'memory.usage'),
      neuralLatency: this.calculateTrend(this.history.neural.slice(-20), 'averageLatency'),
      wordpressResponse: this.calculateTrend(this.history.wordpress.slice(-20), 'averageResponseTime')
    };
  }

  calculateTrend(data, field) {
    if (data.length < 2) return 'stable';

    const values = data.map(item => this.getNestedValue(item, field)).filter(v => v !== null);
    
    if (values.length < 2) return 'stable';

    const firstHalf = values.slice(0, Math.floor(values.length / 2));
    const secondHalf = values.slice(Math.floor(values.length / 2));

    const firstAvg = firstHalf.reduce((sum, v) => sum + v, 0) / firstHalf.length;
    const secondAvg = secondHalf.reduce((sum, v) => sum + v, 0) / secondHalf.length;

    const change = (secondAvg - firstAvg) / firstAvg;

    if (change > 0.1) return 'increasing';
    if (change < -0.1) return 'decreasing';
    return 'stable';
  }

  getNestedValue(obj, path) {
    return path.split('.').reduce((current, key) => current && current[key], obj);
  }

  determineSystemStatus(memoryUsage, cpuUsage) {
    if (memoryUsage > 0.9 || cpuUsage > 0.9) return 'critical';
    if (memoryUsage > 0.7 || cpuUsage > 0.7) return 'warning';
    return 'healthy';
  }

  determineNeuralStatus(latency, errorRate) {
    if (latency > this.config.alertThresholds.latency || errorRate > 0.1) return 'critical';
    if (latency > this.config.alertThresholds.latency * 0.7 || errorRate > 0.05) return 'warning';
    return 'healthy';
  }

  determineWordPressStatus(responseTime) {
    if (responseTime > 2000) return 'critical';
    if (responseTime > 1000) return 'warning';
    return 'healthy';
  }

  generateRecommendations() {
    const recommendations = [];
    const systemAnalysis = this.analyzeSystemPerformance();
    const neuralAnalysis = this.analyzeNeuralPerformance();

    if (systemAnalysis?.averageMemoryUsage > 0.8) {
      recommendations.push({
        category: 'system',
        type: 'memory',
        priority: 'high',
        message: 'High memory usage detected. Consider optimizing memory usage or increasing available memory.',
        actions: ['memory_cleanup', 'garbage_collection', 'model_unloading']
      });
    }

    if (neuralAnalysis?.averageLatency > this.config.alertThresholds.latency) {
      recommendations.push({
        category: 'neural',
        type: 'performance',
        priority: 'medium',
        message: 'High inference latency detected. Consider model optimization or caching improvements.',
        actions: ['model_optimization', 'caching_enhancement', 'batch_processing']
      });
    }

    if (neuralAnalysis?.averageErrorRate > 0.05) {
      recommendations.push({
        category: 'neural',
        type: 'reliability',
        priority: 'high',
        message: 'High error rate detected. Check model quality and input validation.',
        actions: ['model_retraining', 'input_validation', 'error_handling']
      });
    }

    return recommendations;
  }

  evaluateAlerts() {
    const currentMetrics = {
      system: this.metrics.system,
      neural: this.metrics.neural,
      wordpress: this.metrics.wordpress
    };

    // System alerts
    if (currentMetrics.system.memory?.usage > this.config.alertThresholds.memoryUsage) {
      this.createAlert('system', 'critical', 'High memory usage', {
        current: currentMetrics.system.memory.usage,
        threshold: this.config.alertThresholds.memoryUsage
      });
    }

    if (currentMetrics.system.cpu?.usage > this.config.alertThresholds.cpuUsage) {
      this.createAlert('system', 'critical', 'High CPU usage', {
        current: currentMetrics.system.cpu.usage,
        threshold: this.config.alertThresholds.cpuUsage
      });
    }

    // Neural system alerts
    if (currentMetrics.neural.averageLatency > this.config.alertThresholds.latency) {
      this.createAlert('neural', 'warning', 'High inference latency', {
        current: currentMetrics.neural.averageLatency,
        threshold: this.config.alertThresholds.latency
      });
    }

    if (currentMetrics.neural.errorRate > this.config.alertThresholds.errorRate) {
      this.createAlert('neural', 'critical', 'High error rate', {
        current: currentMetrics.neural.errorRate,
        threshold: this.config.alertThresholds.errorRate
      });
    }
  }

  createAlert(category, severity, message, data) {
    const alert = {
      id: uuidv4(),
      category,
      severity,
      message,
      data,
      timestamp: new Date().toISOString(),
      acknowledged: false
    };

    this.alerts.push(alert);
    this.addToHistory('alerts', alert);

    this.emit('alert', alert);
    this.logger.warn(`Alert created [${severity}]: ${message}`, data);

    // Limit alerts array size
    if (this.alerts.length > 100) {
      this.alerts = this.alerts.slice(-50);
    }
  }

  acknowledgeAlert(alertId) {
    const alert = this.alerts.find(a => a.id === alertId);
    if (alert) {
      alert.acknowledged = true;
      alert.acknowledgedAt = new Date().toISOString();
      this.logger.info(`Alert acknowledged: ${alertId}`);
    }
  }

  getMetrics(category = null) {
    if (category) {
      return this.metrics[category] || null;
    }
    return this.metrics;
  }

  getHistory(category = null, limit = 100) {
    if (category) {
      return this.history[category]?.slice(-limit) || [];
    }
    
    const history = {};
    for (const [cat, data] of Object.entries(this.history)) {
      history[cat] = data.slice(-limit);
    }
    return history;
  }

  getAlerts(acknowledged = false) {
    return this.alerts.filter(alert => alert.acknowledged === acknowledged);
  }

  getComponentStatus() {
    const status = {};
    
    for (const [componentId, component] of this.components) {
      status[componentId] = {
        id: component.id,
        status: component.status,
        lastUpdate: component.lastUpdate,
        hasMetrics: typeof component.instance?.getMetrics === 'function'
      };
    }
    
    return status;
  }

  generateReport(timeframe = '1h') {
    const now = new Date();
    const timeframeMs = this.parseTimeframe(timeframe);
    const startTime = new Date(now.getTime() - timeframeMs);

    const report = {
      id: uuidv4(),
      timeframe,
      startTime: startTime.toISOString(),
      endTime: now.toISOString(),
      summary: {
        totalAlerts: this.alerts.filter(a => new Date(a.timestamp) >= startTime).length,
        criticalAlerts: this.alerts.filter(a => 
          new Date(a.timestamp) >= startTime && a.severity === 'critical'
        ).length,
        systemStatus: this.determineOverallStatus(),
        recommendations: this.generateRecommendations()
      },
      metrics: {
        system: this.metrics.system,
        neural: this.metrics.neural,
        wordpress: this.metrics.wordpress
      },
      analytics: {
        system: this.analyzeSystemPerformance(),
        neural: this.analyzeNeuralPerformance(),
        wordpress: this.analyzeWordPressPerformance()
      }
    };

    return report;
  }

  parseTimeframe(timeframe) {
    const units = {
      m: 60 * 1000,
      h: 60 * 60 * 1000,
      d: 24 * 60 * 60 * 1000
    };

    const match = timeframe.match(/^(\d+)([mhd])$/);
    if (!match) return 60 * 60 * 1000; // Default 1 hour

    const value = parseInt(match[1]);
    const unit = match[2];
    
    return value * units[unit];
  }

  determineOverallStatus() {
    const systemAnalysis = this.analyzeSystemPerformance();
    const neuralAnalysis = this.analyzeNeuralPerformance();
    
    const statuses = [
      systemAnalysis?.status,
      neuralAnalysis?.status
    ].filter(Boolean);

    if (statuses.includes('critical')) return 'critical';
    if (statuses.includes('warning')) return 'warning';
    return 'healthy';
  }

  async cleanup() {
    // Clear all intervals
    if (this.systemMetricsInterval) clearInterval(this.systemMetricsInterval);
    if (this.neuralMetricsInterval) clearInterval(this.neuralMetricsInterval);
    if (this.wordpressMetricsInterval) clearInterval(this.wordpressMetricsInterval);
    if (this.analyticsInterval) clearInterval(this.analyticsInterval);
    if (this.alertInterval) clearInterval(this.alertInterval);

    // Clear data structures
    this.components.clear();
    this.alerts.length = 0;
    
    for (const category of Object.keys(this.history)) {
      this.history[category].length = 0;
    }

    this.logger.info('Performance Monitor cleanup completed');
  }
}

module.exports = { PerformanceMonitor };