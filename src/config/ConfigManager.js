/**
 * ConfigManager - Centralized configuration management for swarm operations
 * 
 * Handles loading, validation, and management of configuration parameters
 * for all swarm components with environment-specific overrides.
 */

const fs = require('fs').promises;
const path = require('path');
const { Logger } = require('../utils/Logger');

class ConfigManager {
    constructor(initialConfig = {}) {
        this.config = new Map();
        this.watchers = new Map();
        this.validators = new Map();
        this.logger = new Logger('ConfigManager');
        
        // Set default configuration
        this._setDefaults();
        
        // Apply initial configuration
        if (initialConfig && typeof initialConfig === 'object') {
            Object.entries(initialConfig).forEach(([key, value]) => {
                this.config.set(key, value);
            });
        }
        
        // Setup configuration validation
        this._setupValidators();
    }

    /**
     * Load configuration from file or environment
     */
    async load(configPath = null) {
        try {
            // Load from file if specified
            if (configPath) {
                await this._loadFromFile(configPath);
            }
            
            // Load from default locations
            await this._loadFromDefaultLocations();
            
            // Apply environment variable overrides
            this._loadFromEnvironment();
            
            // Validate all configuration
            this._validateConfiguration();
            
            this.logger.info('Configuration loaded successfully', {
                keys: Array.from(this.config.keys()),
                sources: this._getLoadedSources()
            });
            
            return true;
            
        } catch (error) {
            this.logger.error('Failed to load configuration', { error: error.message });
            throw error;
        }
    }

    /**
     * Get configuration value with optional default
     */
    get(key, defaultValue = undefined) {
        const value = this.config.get(key);
        
        if (value === undefined && defaultValue !== undefined) {
            this.logger.debug('Using default value for config key', { key, defaultValue });
            return defaultValue;
        }
        
        return value;
    }

    /**
     * Set configuration value with validation
     */
    set(key, value) {
        try {
            // Validate the value
            this._validateValue(key, value);
            
            const oldValue = this.config.get(key);
            this.config.set(key, value);
            
            // Notify watchers
            this._notifyWatchers(key, value, oldValue);
            
            this.logger.debug('Configuration updated', { key, value });
            return true;
            
        } catch (error) {
            this.logger.error('Failed to set configuration', { key, value, error: error.message });
            throw error;
        }
    }

    /**
     * Get all configuration as object
     */
    getAll() {
        const config = {};
        this.config.forEach((value, key) => {
            config[key] = value;
        });
        return config;
    }

    /**
     * Watch for configuration changes
     */
    watch(key, callback) {
        if (!this.watchers.has(key)) {
            this.watchers.set(key, []);
        }
        
        this.watchers.get(key).push(callback);
        
        this.logger.debug('Watcher registered', { key });
        
        // Return unwatch function
        return () => {
            const watchers = this.watchers.get(key);
            if (watchers) {
                const index = watchers.indexOf(callback);
                if (index > -1) {
                    watchers.splice(index, 1);
                }
            }
        };
    }

    /**
     * Merge configuration from another source
     */
    merge(newConfig) {
        if (!newConfig || typeof newConfig !== 'object') {
            throw new Error('Configuration must be an object');
        }
        
        const updatedKeys = [];
        
        Object.entries(newConfig).forEach(([key, value]) => {
            try {
                this.set(key, value);
                updatedKeys.push(key);
            } catch (error) {
                this.logger.warn('Failed to merge config key', { key, error: error.message });
            }
        });
        
        this.logger.info('Configuration merged', { updatedKeys });
        return updatedKeys;
    }

    /**
     * Export configuration to file
     */
    async export(filePath, format = 'json') {
        try {
            const config = this.getAll();
            let content;
            
            switch (format.toLowerCase()) {
                case 'json':
                    content = JSON.stringify(config, null, 2);
                    break;
                case 'yaml':
                    // Simple YAML export (for basic structures)
                    content = this._toYaml(config);
                    break;
                default:
                    throw new Error(`Unsupported export format: ${format}`);
            }
            
            await fs.writeFile(filePath, content, 'utf8');
            this.logger.info('Configuration exported', { filePath, format });
            
            return true;
            
        } catch (error) {
            this.logger.error('Failed to export configuration', { filePath, error: error.message });
            throw error;
        }
    }

    /**
     * Validate specific configuration value
     */
    _validateValue(key, value) {
        const validator = this.validators.get(key);
        
        if (validator && !validator(value)) {
            throw new Error(`Invalid value for configuration key '${key}': ${value}`);
        }
        
        return true;
    }

    /**
     * Validate entire configuration
     */
    _validateConfiguration() {
        const errors = [];
        
        this.config.forEach((value, key) => {
            try {
                this._validateValue(key, value);
            } catch (error) {
                errors.push({ key, error: error.message });
            }
        });
        
        if (errors.length > 0) {
            const errorMessages = errors.map(e => `${e.key}: ${e.error}`).join(', ');
            throw new Error(`Configuration validation failed: ${errorMessages}`);
        }
        
        return true;
    }

    /**
     * Set default configuration values
     */
    _setDefaults() {
        const defaults = {
            // Swarm configuration
            maxAgents: 10,
            defaultTopology: 'hierarchical',
            executionStrategy: 'parallel',
            maxConcurrency: 5,
            
            // Task configuration
            defaultTimeout: 300000, // 5 minutes
            maxRetries: 3,
            retryDelay: 1000,
            
            // Memory configuration
            cacheSize: 1000,
            cacheTtl: 3600000, // 1 hour
            persistentStorage: true,
            storageDir: '.swarm/memory',
            
            // Agent configuration
            defaultAgents: ['generalist'],
            agentTimeout: 60000, // 1 minute
            maxAgentRetries: 2,
            
            // Performance configuration
            tokenOptimization: true,
            parallelExecution: true,
            neuralTraining: false,
            telemetryLevel: 'basic',
            
            // Security configuration
            validateTasks: true,
            sanitizeInputs: true,
            maxTaskSize: 10485760, // 10MB
            
            // Logging configuration
            logLevel: 'info',
            logFormat: 'json',
            logRotation: true,
            maxLogFiles: 5,
            
            // Network configuration
            timeout: 30000,
            retries: 3,
            keepAlive: true
        };
        
        Object.entries(defaults).forEach(([key, value]) => {
            this.config.set(key, value);
        });
        
        this.logger.debug('Default configuration set', { keys: Object.keys(defaults) });
    }

    /**
     * Setup configuration validators
     */
    _setupValidators() {
        // Numeric validators
        this.validators.set('maxAgents', (value) => 
            typeof value === 'number' && value > 0 && value <= 100
        );
        
        this.validators.set('maxConcurrency', (value) => 
            typeof value === 'number' && value > 0 && value <= 20
        );
        
        this.validators.set('defaultTimeout', (value) => 
            typeof value === 'number' && value > 0 && value <= 3600000
        );
        
        this.validators.set('cacheSize', (value) => 
            typeof value === 'number' && value > 0 && value <= 100000
        );
        
        // String validators
        this.validators.set('defaultTopology', (value) => 
            typeof value === 'string' && ['hierarchical', 'mesh', 'ring', 'star'].includes(value)
        );
        
        this.validators.set('executionStrategy', (value) => 
            typeof value === 'string' && ['parallel', 'sequential', 'adaptive'].includes(value)
        );
        
        this.validators.set('logLevel', (value) => 
            typeof value === 'string' && ['debug', 'info', 'warn', 'error'].includes(value)
        );
        
        // Array validators
        this.validators.set('defaultAgents', (value) => 
            Array.isArray(value) && value.length > 0
        );
        
        // Boolean validators
        this.validators.set('tokenOptimization', (value) => typeof value === 'boolean');
        this.validators.set('parallelExecution', (value) => typeof value === 'boolean');
        this.validators.set('validateTasks', (value) => typeof value === 'boolean');
        this.validators.set('persistentStorage', (value) => typeof value === 'boolean');
        
        this.logger.debug('Validators configured', { count: this.validators.size });
    }

    /**
     * Load configuration from file
     */
    async _loadFromFile(configPath) {
        try {
            const absolutePath = path.resolve(configPath);
            const content = await fs.readFile(absolutePath, 'utf8');
            
            let config;
            if (configPath.endsWith('.json')) {
                config = JSON.parse(content);
            } else if (configPath.endsWith('.js')) {
                // Dynamic require for JS config files
                delete require.cache[absolutePath];
                config = require(absolutePath);
            } else {
                throw new Error(`Unsupported config file format: ${path.extname(configPath)}`);
            }
            
            this.merge(config);
            this.logger.info('Configuration loaded from file', { configPath });
            
        } catch (error) {
            if (error.code !== 'ENOENT') {
                throw error;
            }
            this.logger.debug('Configuration file not found', { configPath });
        }
    }

    /**
     * Load from default configuration locations
     */
    async _loadFromDefaultLocations() {
        const defaultPaths = [
            './claude-flow.config.json',
            './config/swarm.json',
            './config/swarm.config.js',
            './.swarmrc'
        ];
        
        for (const configPath of defaultPaths) {
            try {
                await this._loadFromFile(configPath);
                break; // Load only the first found config
            } catch (error) {
                // Continue to next path
            }
        }
    }

    /**
     * Load configuration from environment variables
     */
    _loadFromEnvironment() {
        const envPrefix = 'SWARM_';
        const envOverrides = {};
        
        Object.keys(process.env).forEach(key => {
            if (key.startsWith(envPrefix)) {
                const configKey = key.substring(envPrefix.length).toLowerCase();
                let value = process.env[key];
                
                // Try to parse as JSON first
                try {
                    value = JSON.parse(value);
                } catch {
                    // Keep as string if not valid JSON
                }
                
                envOverrides[configKey] = value;
            }
        });
        
        if (Object.keys(envOverrides).length > 0) {
            this.merge(envOverrides);
            this.logger.info('Environment overrides applied', { 
                keys: Object.keys(envOverrides) 
            });
        }
    }

    /**
     * Notify configuration watchers
     */
    _notifyWatchers(key, newValue, oldValue) {
        const watchers = this.watchers.get(key);
        
        if (watchers) {
            watchers.forEach(callback => {
                try {
                    callback(newValue, oldValue, key);
                } catch (error) {
                    this.logger.warn('Watcher callback failed', { key, error: error.message });
                }
            });
        }
    }

    /**
     * Get loaded configuration sources
     */
    _getLoadedSources() {
        return ['defaults', 'environment']; // Can be extended to track actual file sources
    }

    /**
     * Simple YAML export (for basic structures)
     */
    _toYaml(obj, indent = 0) {
        const spaces = '  '.repeat(indent);
        let yaml = '';
        
        Object.entries(obj).forEach(([key, value]) => {
            if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
                yaml += `${spaces}${key}:\n${this._toYaml(value, indent + 1)}`;
            } else if (Array.isArray(value)) {
                yaml += `${spaces}${key}:\n`;
                value.forEach(item => {
                    yaml += `${spaces}  - ${item}\n`;
                });
            } else {
                yaml += `${spaces}${key}: ${value}\n`;
            }
        });
        
        return yaml;
    }

    /**
     * Reset configuration to defaults
     */
    reset() {
        this.config.clear();
        this.watchers.clear();
        this._setDefaults();
        this.logger.info('Configuration reset to defaults');
    }

    /**
     * Get configuration schema/structure
     */
    getSchema() {
        const schema = {};
        
        this.config.forEach((value, key) => {
            schema[key] = {
                type: typeof value,
                value,
                hasValidator: this.validators.has(key),
                hasWatchers: this.watchers.has(key) && this.watchers.get(key).length > 0
            };
        });
        
        return schema;
    }
}

module.exports = { ConfigManager };