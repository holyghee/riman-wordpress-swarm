/**
 * SecurityValidator - Security validation and sanitization for swarm operations
 * 
 * Provides input validation, sanitization, access control, and security
 * monitoring for all swarm components and operations.
 */

const crypto = require('crypto');
const { Logger } = require('../utils/Logger');

class SecurityValidator {
    constructor(options = {}) {
        this.options = {
            maxTaskSize: options.maxTaskSize || 10485760, // 10MB
            maxStringLength: options.maxStringLength || 100000,
            allowedFileTypes: options.allowedFileTypes || ['.js', '.json', '.md', '.txt', '.yaml', '.yml'],
            sanitizeInputs: options.sanitizeInputs !== false,
            validateTasks: options.validateTasks !== false,
            maxDepth: options.maxDepth || 10,
            rateLimit: options.rateLimit || 1000, // requests per minute
            ...options
        };
        
        this.logger = new Logger('SecurityValidator');
        this.rateLimitTracker = new Map();
        this.blockedPatterns = this._initializeBlockedPatterns();
        this.trustedSources = new Set(options.trustedSources || []);
        
        // Security metrics
        this.metrics = {
            totalValidations: 0,
            blockedRequests: 0,
            sanitizedInputs: 0,
            securityViolations: 0,
            lastViolation: null
        };
        
        // Start cleanup interval
        this.cleanupInterval = setInterval(() => {
            this._cleanupRateLimitTracker();
        }, 60000); // Cleanup every minute
    }

    /**
     * Validate a task before execution
     */
    async validateTask(task) {
        try {
            this.metrics.totalValidations++;
            
            if (!this.options.validateTasks) {
                return { valid: true, sanitized: task };
            }
            
            this.logger.debug('Validating task', { taskId: task.id });
            
            // Basic structure validation
            this._validateTaskStructure(task);
            
            // Size validation
            this._validateTaskSize(task);
            
            // Content validation
            this._validateTaskContent(task);
            
            // Rate limiting check
            this._checkRateLimit(task.source || 'unknown');
            
            // Sanitize task data
            const sanitizedTask = this._sanitizeTask(task);
            
            this.logger.debug('Task validation successful', { taskId: task.id });
            
            return {
                valid: true,
                sanitized: sanitizedTask,
                warnings: []
            };
            
        } catch (error) {
            this.metrics.securityViolations++;
            this.metrics.lastViolation = Date.now();
            this.metrics.blockedRequests++;
            
            this.logger.security('Task validation failed', 'high', {
                taskId: task.id,
                error: error.message,
                task: this._safeTaskLog(task)
            });
            
            throw new SecurityValidationError(error.message, 'TASK_VALIDATION_FAILED');
        }
    }

    /**
     * Validate input data
     */
    validateInput(input, type = 'general') {
        try {
            this.metrics.totalValidations++;
            
            if (input === null || input === undefined) {
                return { valid: true, sanitized: input };
            }
            
            // Type-specific validation
            switch (type) {
                case 'string':
                    this._validateString(input);
                    break;
                case 'object':
                    this._validateObject(input);
                    break;
                case 'array':
                    this._validateArray(input);
                    break;
                case 'code':
                    this._validateCode(input);
                    break;
                default:
                    this._validateGeneral(input);
            }
            
            // Sanitize input
            const sanitized = this._sanitizeInput(input, type);
            
            if (sanitized !== input) {
                this.metrics.sanitizedInputs++;
            }
            
            return {
                valid: true,
                sanitized,
                original: input
            };
            
        } catch (error) {
            this.metrics.securityViolations++;
            this.metrics.blockedRequests++;
            
            this.logger.security('Input validation failed', 'medium', {
                type,
                error: error.message,
                inputPreview: this._getInputPreview(input)
            });
            
            throw new SecurityValidationError(error.message, 'INPUT_VALIDATION_FAILED');
        }
    }

    /**
     * Validate file operations
     */
    validateFileOperation(operation, filePath, content = null) {
        try {
            this.logger.debug('Validating file operation', { operation, filePath });
            
            // Path traversal protection
            this._validateFilePath(filePath);
            
            // File type validation
            this._validateFileType(filePath);
            
            // Content validation if provided
            if (content !== null) {
                this._validateFileContent(content);
            }
            
            // Operation-specific validation
            this._validateOperationType(operation, filePath);
            
            return {
                valid: true,
                sanitizedPath: this._sanitizeFilePath(filePath),
                sanitizedContent: content ? this._sanitizeInput(content, 'code') : null
            };
            
        } catch (error) {
            this.metrics.securityViolations++;
            this.metrics.blockedRequests++;
            
            this.logger.security('File operation validation failed', 'high', {
                operation,
                filePath,
                error: error.message
            });
            
            throw new SecurityValidationError(error.message, 'FILE_OPERATION_BLOCKED');
        }
    }

    /**
     * Generate security hash for data integrity
     */
    generateSecurityHash(data) {
        const hash = crypto.createHash('sha256');
        hash.update(JSON.stringify(data));
        return hash.digest('hex');
    }

    /**
     * Verify security hash
     */
    verifySecurityHash(data, expectedHash) {
        const actualHash = this.generateSecurityHash(data);
        return actualHash === expectedHash;
    }

    /**
     * Check if source is trusted
     */
    isTrustedSource(source) {
        return this.trustedSources.has(source);
    }

    /**
     * Add trusted source
     */
    addTrustedSource(source) {
        this.trustedSources.add(source);
        this.logger.info('Added trusted source', { source });
    }

    /**
     * Remove trusted source
     */
    removeTrustedSource(source) {
        this.trustedSources.delete(source);
        this.logger.info('Removed trusted source', { source });
    }

    /**
     * Private validation methods
     */
    _validateTaskStructure(task) {
        if (!task || typeof task !== 'object') {
            throw new Error('Task must be an object');
        }
        
        if (!task.id || typeof task.id !== 'string') {
            throw new Error('Task must have a valid ID');
        }
        
        if (task.id.length > 100) {
            throw new Error('Task ID too long');
        }
    }

    _validateTaskSize(task) {
        const taskSize = JSON.stringify(task).length;
        
        if (taskSize > this.options.maxTaskSize) {
            throw new Error(`Task size (${taskSize} bytes) exceeds maximum (${this.options.maxTaskSize} bytes)`);
        }
    }

    _validateTaskContent(task) {
        // Check for blocked patterns in task content
        const taskString = JSON.stringify(task).toLowerCase();
        
        for (const pattern of this.blockedPatterns) {
            if (pattern.test(taskString)) {
                throw new Error(`Task contains blocked pattern: ${pattern.source}`);
            }
        }
    }

    _validateString(input) {
        if (typeof input !== 'string') {
            throw new Error('Input must be a string');
        }
        
        if (input.length > this.options.maxStringLength) {
            throw new Error(`String too long: ${input.length} > ${this.options.maxStringLength}`);
        }
    }

    _validateObject(input) {
        if (typeof input !== 'object' || Array.isArray(input)) {
            throw new Error('Input must be an object');
        }
        
        this._validateDepth(input, 0);
    }

    _validateArray(input) {
        if (!Array.isArray(input)) {
            throw new Error('Input must be an array');
        }
        
        if (input.length > 10000) {
            throw new Error('Array too large');
        }
    }

    _validateCode(input) {
        if (typeof input !== 'string') {
            throw new Error('Code input must be a string');
        }
        
        // Check for dangerous code patterns
        const dangerousPatterns = [
            /eval\s*\(/i,
            /Function\s*\(/i,
            /process\.exit/i,
            /require\s*\(\s*['"][^'"]*child_process/i,
            /require\s*\(\s*['"][^'"]*fs/i,
            /\.\.\/\.\.\//,
            /document\.cookie/i,
            /window\.location/i
        ];
        
        for (const pattern of dangerousPatterns) {
            if (pattern.test(input)) {
                throw new Error(`Code contains potentially dangerous pattern: ${pattern.source}`);
            }
        }
    }

    _validateGeneral(input) {
        // General validation for any input type
        if (typeof input === 'string') {
            this._validateString(input);
        } else if (typeof input === 'object' && input !== null) {
            if (Array.isArray(input)) {
                this._validateArray(input);
            } else {
                this._validateObject(input);
            }
        }
    }

    _validateDepth(obj, currentDepth) {
        if (currentDepth > this.options.maxDepth) {
            throw new Error(`Object depth exceeds maximum: ${this.options.maxDepth}`);
        }
        
        for (const value of Object.values(obj)) {
            if (typeof value === 'object' && value !== null) {
                this._validateDepth(value, currentDepth + 1);
            }
        }
    }

    _validateFilePath(filePath) {
        if (typeof filePath !== 'string') {
            throw new Error('File path must be a string');
        }
        
        // Path traversal protection
        if (filePath.includes('..') || filePath.includes('~')) {
            throw new Error('Path traversal detected');
        }
        
        // Absolute path outside project
        if (filePath.startsWith('/') && !filePath.startsWith(process.cwd())) {
            throw new Error('Absolute path outside project directory');
        }
    }

    _validateFileType(filePath) {
        const extension = filePath.toLowerCase().substring(filePath.lastIndexOf('.'));
        
        if (!this.options.allowedFileTypes.includes(extension)) {
            throw new Error(`File type not allowed: ${extension}`);
        }
    }

    _validateFileContent(content) {
        if (typeof content !== 'string') {
            throw new Error('File content must be a string');
        }
        
        if (content.length > this.options.maxTaskSize) {
            throw new Error('File content too large');
        }
    }

    _validateOperationType(operation, filePath) {
        const allowedOperations = ['read', 'write', 'create', 'delete', 'append'];
        
        if (!allowedOperations.includes(operation)) {
            throw new Error(`File operation not allowed: ${operation}`);
        }
        
        // Additional restrictions for sensitive operations
        if (operation === 'delete' && !this.isTrustedSource('system')) {
            throw new Error('Delete operation requires trusted source');
        }
    }

    _checkRateLimit(source) {
        const now = Date.now();
        const windowStart = now - 60000; // 1 minute window
        
        if (!this.rateLimitTracker.has(source)) {
            this.rateLimitTracker.set(source, []);
        }
        
        const requests = this.rateLimitTracker.get(source);
        
        // Remove old requests
        const recentRequests = requests.filter(timestamp => timestamp > windowStart);
        
        if (recentRequests.length >= this.options.rateLimit) {
            throw new Error(`Rate limit exceeded for source: ${source}`);
        }
        
        recentRequests.push(now);
        this.rateLimitTracker.set(source, recentRequests);
    }

    /**
     * Sanitization methods
     */
    _sanitizeTask(task) {
        if (!this.options.sanitizeInputs) {
            return task;
        }
        
        const sanitized = JSON.parse(JSON.stringify(task));
        
        // Sanitize string fields
        if (sanitized.description) {
            sanitized.description = this._sanitizeString(sanitized.description);
        }
        
        if (sanitized.input && typeof sanitized.input === 'string') {
            sanitized.input = this._sanitizeString(sanitized.input);
        }
        
        return sanitized;
    }

    _sanitizeInput(input, type) {
        if (!this.options.sanitizeInputs) {
            return input;
        }
        
        if (typeof input === 'string') {
            return this._sanitizeString(input);
        } else if (typeof input === 'object' && input !== null) {
            return this._sanitizeObject(input);
        }
        
        return input;
    }

    _sanitizeString(str) {
        return str
            .replace(/[<>]/g, '') // Remove HTML-like brackets
            .replace(/javascript:/gi, '') // Remove javascript: protocol
            .replace(/data:/gi, '') // Remove data: protocol
            .trim();
    }

    _sanitizeObject(obj) {
        if (Array.isArray(obj)) {
            return obj.map(item => this._sanitizeInput(item));
        }
        
        const sanitized = {};
        for (const [key, value] of Object.entries(obj)) {
            const sanitizedKey = this._sanitizeString(key);
            sanitized[sanitizedKey] = this._sanitizeInput(value);
        }
        
        return sanitized;
    }

    _sanitizeFilePath(filePath) {
        return filePath.replace(/[<>"|?*]/g, '').trim();
    }

    /**
     * Helper methods
     */
    _initializeBlockedPatterns() {
        return [
            /script\s*>/i,
            /javascript:/i,
            /vbscript:/i,
            /onload\s*=/i,
            /onerror\s*=/i,
            /eval\s*\(/i,
            /document\.write/i,
            /innerHTML/i,
            /__proto__/i,
            /constructor/i
        ];
    }

    _cleanupRateLimitTracker() {
        const now = Date.now();
        const windowStart = now - 60000;
        
        for (const [source, requests] of this.rateLimitTracker.entries()) {
            const recentRequests = requests.filter(timestamp => timestamp > windowStart);
            
            if (recentRequests.length === 0) {
                this.rateLimitTracker.delete(source);
            } else {
                this.rateLimitTracker.set(source, recentRequests);
            }
        }
    }

    _safeTaskLog(task) {
        // Return safe subset of task for logging
        return {
            id: task.id,
            type: task.type,
            hasInput: !!task.input,
            hasDescription: !!task.description,
            size: JSON.stringify(task).length
        };
    }

    _getInputPreview(input) {
        if (typeof input === 'string') {
            return input.substring(0, 100) + (input.length > 100 ? '...' : '');
        }
        
        return typeof input;
    }

    /**
     * Get security metrics
     */
    getMetrics() {
        return {
            ...this.metrics,
            rateLimitSources: this.rateLimitTracker.size,
            trustedSources: this.trustedSources.size,
            uptime: Date.now() - (this.startTime || Date.now())
        };
    }

    /**
     * Reset security metrics
     */
    resetMetrics() {
        this.metrics = {
            totalValidations: 0,
            blockedRequests: 0,
            sanitizedInputs: 0,
            securityViolations: 0,
            lastViolation: null
        };
        
        this.logger.info('Security metrics reset');
    }

    /**
     * Shutdown security validator
     */
    shutdown() {
        if (this.cleanupInterval) {
            clearInterval(this.cleanupInterval);
        }
        
        this.logger.info('SecurityValidator shutdown completed');
    }
}

/**
 * Custom security validation error
 */
class SecurityValidationError extends Error {
    constructor(message, code = 'SECURITY_VALIDATION_ERROR') {
        super(message);
        this.name = 'SecurityValidationError';
        this.code = code;
        this.timestamp = Date.now();
    }
}

module.exports = { SecurityValidator, SecurityValidationError };