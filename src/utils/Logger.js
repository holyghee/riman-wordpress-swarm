/**
 * Logger - Centralized logging system for swarm operations
 * 
 * Provides structured logging with multiple levels, formatting options,
 * and integration with monitoring systems.
 */

const fs = require('fs').promises;
const path = require('path');

class Logger {
    constructor(context = 'SwarmSystem', options = {}) {
        this.context = context;
        this.options = {
            level: options.level || process.env.LOG_LEVEL || 'info',
            format: options.format || 'json',
            output: options.output || 'console',
            logDir: options.logDir || '.swarm/logs',
            maxFileSize: options.maxFileSize || 10485760, // 10MB
            maxFiles: options.maxFiles || 5,
            includeTimestamp: options.includeTimestamp !== false,
            includeContext: options.includeContext !== false,
            ...options
        };
        
        this.levels = {
            debug: 0,
            info: 1,
            warn: 2,
            error: 3,
            fatal: 4
        };
        
        this.currentLevelValue = this.levels[this.options.level] || 1;
        
        // Initialize logging system
        this._initialize();
    }

    /**
     * Initialize logging system
     */
    async _initialize() {
        if (this.options.output === 'file' || this.options.output === 'both') {
            try {
                await fs.mkdir(this.options.logDir, { recursive: true });
            } catch (error) {
                console.error('Failed to create log directory:', error.message);
            }
        }
    }

    /**
     * Log debug message
     */
    debug(message, metadata = {}) {
        this._log('debug', message, metadata);
    }

    /**
     * Log info message
     */
    info(message, metadata = {}) {
        this._log('info', message, metadata);
    }

    /**
     * Log warning message
     */
    warn(message, metadata = {}) {
        this._log('warn', message, metadata);
    }

    /**
     * Log error message
     */
    error(message, metadata = {}) {
        this._log('error', message, metadata);
    }

    /**
     * Log fatal error message
     */
    fatal(message, metadata = {}) {
        this._log('fatal', message, metadata);
    }

    /**
     * Create a child logger with extended context
     */
    child(childContext, options = {}) {
        const fullContext = `${this.context}:${childContext}`;
        return new Logger(fullContext, { ...this.options, ...options });
    }

    /**
     * Log message with specified level
     */
    _log(level, message, metadata) {
        const levelValue = this.levels[level];
        
        // Check if message should be logged based on current level
        if (levelValue < this.currentLevelValue) {
            return;
        }
        
        const logEntry = this._createLogEntry(level, message, metadata);
        
        // Output to console if enabled
        if (this.options.output === 'console' || this.options.output === 'both') {
            this._outputToConsole(level, logEntry);
        }
        
        // Output to file if enabled
        if (this.options.output === 'file' || this.options.output === 'both') {
            this._outputToFile(logEntry);
        }
        
        // Send to external logging services if configured
        this._sendToExternalServices(logEntry);
    }

    /**
     * Create structured log entry
     */
    _createLogEntry(level, message, metadata) {
        const entry = {
            level: level.toUpperCase(),
            message,
            ...metadata
        };
        
        if (this.options.includeTimestamp) {
            entry.timestamp = new Date().toISOString();
        }
        
        if (this.options.includeContext) {
            entry.context = this.context;
        }
        
        // Add process information
        entry.pid = process.pid;
        
        // Add memory usage for performance monitoring
        if (level === 'debug') {
            const memUsage = process.memoryUsage();
            entry.memory = {
                rss: Math.round(memUsage.rss / 1024 / 1024),
                heapTotal: Math.round(memUsage.heapTotal / 1024 / 1024),
                heapUsed: Math.round(memUsage.heapUsed / 1024 / 1024),
                external: Math.round(memUsage.external / 1024 / 1024)
            };
        }
        
        return entry;
    }

    /**
     * Output log entry to console
     */
    _outputToConsole(level, logEntry) {
        let output;
        
        if (this.options.format === 'json') {
            output = JSON.stringify(logEntry);
        } else {
            // Pretty format for console
            const timestamp = logEntry.timestamp || new Date().toISOString();
            const context = logEntry.context || '';
            const levelStr = logEntry.level.padEnd(5);
            
            output = `${timestamp} [${levelStr}] ${context}: ${logEntry.message}`;
            
            // Add metadata if present
            const metadata = { ...logEntry };
            delete metadata.timestamp;
            delete metadata.level;
            delete metadata.message;
            delete metadata.context;
            delete metadata.pid;
            
            if (Object.keys(metadata).length > 0) {
                output += ` ${JSON.stringify(metadata)}`;
            }
        }
        
        // Use appropriate console method based on level
        switch (level) {
            case 'debug':
                console.debug(output);
                break;
            case 'info':
                console.info(output);
                break;
            case 'warn':
                console.warn(output);
                break;
            case 'error':
            case 'fatal':
                console.error(output);
                break;
            default:
                console.log(output);
        }
    }

    /**
     * Output log entry to file
     */
    async _outputToFile(logEntry) {
        try {
            const timestamp = new Date().toISOString().split('T')[0];
            const filename = `swarm-${timestamp}.log`;
            const filepath = path.join(this.options.logDir, filename);
            
            const logLine = JSON.stringify(logEntry) + '\n';
            
            // Append to file
            await fs.appendFile(filepath, logLine, 'utf8');
            
            // Check file size and rotate if necessary
            await this._rotateLogsIfNeeded(filepath);
            
        } catch (error) {
            console.error('Failed to write to log file:', error.message);
        }
    }

    /**
     * Rotate logs if file size exceeds limit
     */
    async _rotateLogsIfNeeded(filepath) {
        try {
            const stats = await fs.stat(filepath);
            
            if (stats.size > this.options.maxFileSize) {
                const timestamp = Date.now();
                const ext = path.extname(filepath);
                const base = path.basename(filepath, ext);
                const dir = path.dirname(filepath);
                
                const rotatedPath = path.join(dir, `${base}.${timestamp}${ext}`);
                
                // Rename current file
                await fs.rename(filepath, rotatedPath);
                
                // Clean up old log files
                await this._cleanupOldLogs(dir);
            }
        } catch (error) {
            // Ignore errors in log rotation
        }
    }

    /**
     * Clean up old log files
     */
    async _cleanupOldLogs(logDir) {
        try {
            const files = await fs.readdir(logDir);
            const logFiles = files
                .filter(file => file.startsWith('swarm-') && file.endsWith('.log'))
                .map(file => ({
                    name: file,
                    path: path.join(logDir, file),
                    time: fs.stat(path.join(logDir, file)).then(stats => stats.mtime)
                }));
            
            // Sort by modification time (newest first)
            const sortedFiles = await Promise.all(
                logFiles.map(async file => ({
                    ...file,
                    time: await file.time
                }))
            );
            
            sortedFiles.sort((a, b) => b.time - a.time);
            
            // Delete files beyond the limit
            const filesToDelete = sortedFiles.slice(this.options.maxFiles);
            
            for (const file of filesToDelete) {
                await fs.unlink(file.path);
            }
            
        } catch (error) {
            // Ignore cleanup errors
        }
    }

    /**
     * Send logs to external services (placeholder for future implementation)
     */
    _sendToExternalServices(logEntry) {
        // This could integrate with services like:
        // - Elasticsearch
        // - CloudWatch
        // - Datadog
        // - Sentry (for errors)
        
        if (this.options.externalLogger && typeof this.options.externalLogger === 'function') {
            try {
                this.options.externalLogger(logEntry);
            } catch (error) {
                // Don't let external logging errors break the main application
            }
        }
    }

    /**
     * Set log level at runtime
     */
    setLevel(level) {
        if (this.levels.hasOwnProperty(level)) {
            this.options.level = level;
            this.currentLevelValue = this.levels[level];
            this.info('Log level changed', { newLevel: level });
        } else {
            this.warn('Invalid log level', { requestedLevel: level, validLevels: Object.keys(this.levels) });
        }
    }

    /**
     * Get current log level
     */
    getLevel() {
        return this.options.level;
    }

    /**
     * Check if a log level is enabled
     */
    isLevelEnabled(level) {
        const levelValue = this.levels[level];
        return levelValue >= this.currentLevelValue;
    }

    /**
     * Create performance timer
     */
    timer(label) {
        const startTime = Date.now();
        
        return {
            end: (metadata = {}) => {
                const duration = Date.now() - startTime;
                this.info(`Timer: ${label}`, {
                    duration,
                    durationMs: duration,
                    ...metadata
                });
                return duration;
            }
        };
    }

    /**
     * Log performance metrics
     */
    performance(operation, duration, metadata = {}) {
        this.info('Performance metric', {
            operation,
            duration,
            durationMs: duration,
            ...metadata
        });
    }

    /**
     * Log system metrics
     */
    metrics(metrics) {
        this.info('System metrics', {
            metrics,
            timestamp: Date.now()
        });
    }

    /**
     * Create audit log entry
     */
    audit(action, actor, target, metadata = {}) {
        this.info('Audit log', {
            action,
            actor,
            target,
            auditTimestamp: Date.now(),
            ...metadata
        });
    }

    /**
     * Log security event
     */
    security(event, severity = 'medium', metadata = {}) {
        const logLevel = severity === 'high' ? 'error' : 'warn';
        this[logLevel]('Security event', {
            securityEvent: event,
            severity,
            timestamp: Date.now(),
            ...metadata
        });
    }

    /**
     * Get logger statistics
     */
    getStats() {
        return {
            context: this.context,
            level: this.options.level,
            format: this.options.format,
            output: this.options.output,
            logDir: this.options.logDir
        };
    }

    /**
     * Shutdown logger and flush any pending writes
     */
    async shutdown() {
        this.info('Logger shutting down', { context: this.context });
        
        // In a real implementation, this might flush buffers, close file handles, etc.
        // For now, just log the shutdown event
    }
}

module.exports = { Logger };