/**
 * MemoryManager - Distributed memory management for swarm coordination
 * 
 * Handles persistent storage, caching, and retrieval of swarm state,
 * task progress, and inter-agent communication data.
 */

const fs = require('fs').promises;
const path = require('path');
const { Logger } = require('../utils/Logger');

class MemoryManager {
    constructor(swarmId, options = {}) {
        this.swarmId = swarmId;
        this.options = {
            persistentStorage: options.persistentStorage !== false,
            cacheSize: options.cacheSize || 1000,
            ttl: options.ttl || 3600000, // 1 hour default TTL
            storageDir: options.storageDir || '.swarm/memory',
            ...options
        };
        
        this.cache = new Map();
        this.accessTimes = new Map();
        this.logger = new Logger(`MemoryManager[${swarmId}]`);
        
        // Statistics tracking
        this.stats = {
            hits: 0,
            misses: 0,
            stores: 0,
            retrievals: 0,
            evictions: 0
        };
    }

    /**
     * Initialize memory manager and create storage directory
     */
    async initialize() {
        try {
            if (this.options.persistentStorage) {
                await fs.mkdir(this.options.storageDir, { recursive: true });
                await this._loadPersistentData();
            }
            
            // Start cleanup interval
            this.cleanupInterval = setInterval(() => {
                this._cleanupExpiredEntries();
            }, 60000); // Cleanup every minute
            
            this.logger.info('MemoryManager initialized', {
                persistentStorage: this.options.persistentStorage,
                cacheSize: this.options.cacheSize
            });
            
        } catch (error) {
            this.logger.error('Failed to initialize MemoryManager', { error: error.message });
            throw error;
        }
    }

    /**
     * Store data in memory with optional TTL
     */
    async store(key, value, ttl = null) {
        try {
            const normalizedKey = this._normalizeKey(key);
            const entry = {
                value,
                timestamp: Date.now(),
                ttl: ttl || this.options.ttl,
                accessed: Date.now()
            };
            
            // Store in cache
            this.cache.set(normalizedKey, entry);
            this.accessTimes.set(normalizedKey, Date.now());
            this.stats.stores++;
            
            // Handle cache size limit
            if (this.cache.size > this.options.cacheSize) {
                await this._evictLeastRecentlyUsed();
            }
            
            // Persist to disk if enabled
            if (this.options.persistentStorage) {
                await this._persistToDisk(normalizedKey, entry);
            }
            
            this.logger.debug('Stored data', { key: normalizedKey });
            return true;
            
        } catch (error) {
            this.logger.error('Failed to store data', { key, error: error.message });
            throw error;
        }
    }

    /**
     * Retrieve data from memory
     */
    async retrieve(key) {
        try {
            const normalizedKey = this._normalizeKey(key);
            this.stats.retrievals++;
            
            // Check cache first
            let entry = this.cache.get(normalizedKey);
            
            if (entry) {
                // Check if entry is expired
                if (this._isExpired(entry)) {
                    this.cache.delete(normalizedKey);
                    this.accessTimes.delete(normalizedKey);
                    entry = null;
                }
            }
            
            // Try loading from persistent storage
            if (!entry && this.options.persistentStorage) {
                entry = await this._loadFromDisk(normalizedKey);
                if (entry && !this._isExpired(entry)) {
                    // Cache the loaded entry
                    this.cache.set(normalizedKey, entry);
                    this.accessTimes.set(normalizedKey, Date.now());
                }
            }
            
            if (entry && !this._isExpired(entry)) {
                // Update access time
                entry.accessed = Date.now();
                this.accessTimes.set(normalizedKey, Date.now());
                this.stats.hits++;
                
                this.logger.debug('Retrieved data', { key: normalizedKey });
                return entry.value;
            }
            
            this.stats.misses++;
            this.logger.debug('Data not found or expired', { key: normalizedKey });
            return null;
            
        } catch (error) {
            this.logger.error('Failed to retrieve data', { key, error: error.message });
            return null;
        }
    }

    /**
     * Search for keys matching a pattern
     */
    async search(pattern) {
        try {
            const regex = new RegExp(pattern);
            const results = [];
            
            // Search in cache
            for (const [key, entry] of this.cache.entries()) {
                if (regex.test(key) && !this._isExpired(entry)) {
                    results.push({
                        key,
                        value: entry.value,
                        timestamp: entry.timestamp,
                        accessed: entry.accessed
                    });
                }
            }
            
            // Search in persistent storage if enabled
            if (this.options.persistentStorage) {
                const persistentResults = await this._searchPersistentStorage(pattern);
                
                // Merge results, avoiding duplicates
                for (const result of persistentResults) {
                    if (!results.find(r => r.key === result.key)) {
                        results.push(result);
                    }
                }
            }
            
            this.logger.debug('Search completed', { pattern, resultsCount: results.length });
            return results;
            
        } catch (error) {
            this.logger.error('Search failed', { pattern, error: error.message });
            return [];
        }
    }

    /**
     * Delete data from memory
     */
    async delete(key) {
        try {
            const normalizedKey = this._normalizeKey(key);
            
            // Delete from cache
            const deleted = this.cache.delete(normalizedKey);
            this.accessTimes.delete(normalizedKey);
            
            // Delete from persistent storage
            if (this.options.persistentStorage) {
                await this._deleteFromDisk(normalizedKey);
            }
            
            this.logger.debug('Deleted data', { key: normalizedKey, deleted });
            return deleted;
            
        } catch (error) {
            this.logger.error('Failed to delete data', { key, error: error.message });
            return false;
        }
    }

    /**
     * Clear all data
     */
    async clear() {
        try {
            this.cache.clear();
            this.accessTimes.clear();
            
            if (this.options.persistentStorage) {
                const files = await fs.readdir(this.options.storageDir);
                await Promise.all(
                    files.map(file => 
                        fs.unlink(path.join(this.options.storageDir, file))
                    )
                );
            }
            
            this.logger.info('Memory cleared');
            return true;
            
        } catch (error) {
            this.logger.error('Failed to clear memory', { error: error.message });
            return false;
        }
    }

    /**
     * Get memory statistics
     */
    getStats() {
        const hitRate = this.stats.retrievals > 0 
            ? (this.stats.hits / this.stats.retrievals * 100).toFixed(2)
            : 0;
            
        return {
            cacheSize: this.cache.size,
            maxCacheSize: this.options.cacheSize,
            hitRate: `${hitRate}%`,
            ...this.stats,
            uptime: Date.now() - (this.initTime || Date.now())
        };
    }

    /**
     * Cleanup and shutdown
     */
    async shutdown() {
        try {
            if (this.cleanupInterval) {
                clearInterval(this.cleanupInterval);
            }
            
            // Persist all cached data before shutdown
            if (this.options.persistentStorage) {
                await this._persistAllCachedData();
            }
            
            this.logger.info('MemoryManager shutdown completed');
            
        } catch (error) {
            this.logger.error('Error during shutdown', { error: error.message });
        }
    }

    /**
     * Private helper methods
     */
    _normalizeKey(key) {
        return key.replace(/[^a-zA-Z0-9/_-]/g, '_').toLowerCase();
    }

    _isExpired(entry) {
        return Date.now() - entry.timestamp > entry.ttl;
    }

    async _evictLeastRecentlyUsed() {
        let oldestKey = null;
        let oldestTime = Date.now();
        
        for (const [key, time] of this.accessTimes.entries()) {
            if (time < oldestTime) {
                oldestTime = time;
                oldestKey = key;
            }
        }
        
        if (oldestKey) {
            this.cache.delete(oldestKey);
            this.accessTimes.delete(oldestKey);
            this.stats.evictions++;
            
            this.logger.debug('Evicted LRU entry', { key: oldestKey });
        }
    }

    _cleanupExpiredEntries() {
        const now = Date.now();
        let cleanedCount = 0;
        
        for (const [key, entry] of this.cache.entries()) {
            if (this._isExpired(entry)) {
                this.cache.delete(key);
                this.accessTimes.delete(key);
                cleanedCount++;
            }
        }
        
        if (cleanedCount > 0) {
            this.logger.debug('Cleaned expired entries', { count: cleanedCount });
        }
    }

    async _persistToDisk(key, entry) {
        if (!this.options.persistentStorage) return;
        
        try {
            const filePath = path.join(this.options.storageDir, `${key}.json`);
            await fs.writeFile(filePath, JSON.stringify(entry), 'utf8');
        } catch (error) {
            this.logger.warn('Failed to persist to disk', { key, error: error.message });
        }
    }

    async _loadFromDisk(key) {
        if (!this.options.persistentStorage) return null;
        
        try {
            const filePath = path.join(this.options.storageDir, `${key}.json`);
            const data = await fs.readFile(filePath, 'utf8');
            return JSON.parse(data);
        } catch (error) {
            return null; // File doesn't exist or is corrupted
        }
    }

    async _deleteFromDisk(key) {
        if (!this.options.persistentStorage) return;
        
        try {
            const filePath = path.join(this.options.storageDir, `${key}.json`);
            await fs.unlink(filePath);
        } catch (error) {
            // File might not exist, which is fine
        }
    }

    async _loadPersistentData() {
        try {
            const files = await fs.readdir(this.options.storageDir);
            
            for (const file of files) {
                if (file.endsWith('.json')) {
                    const key = file.replace('.json', '');
                    const entry = await this._loadFromDisk(key);
                    
                    if (entry && !this._isExpired(entry)) {
                        this.cache.set(key, entry);
                        this.accessTimes.set(key, entry.accessed || entry.timestamp);
                    }
                }
            }
            
            this.logger.info('Loaded persistent data', { filesLoaded: files.length });
            
        } catch (error) {
            this.logger.warn('Failed to load persistent data', { error: error.message });
        }
    }

    async _searchPersistentStorage(pattern) {
        const results = [];
        const regex = new RegExp(pattern);
        
        try {
            const files = await fs.readdir(this.options.storageDir);
            
            for (const file of files) {
                if (file.endsWith('.json')) {
                    const key = file.replace('.json', '');
                    
                    if (regex.test(key)) {
                        const entry = await this._loadFromDisk(key);
                        if (entry && !this._isExpired(entry)) {
                            results.push({
                                key,
                                value: entry.value,
                                timestamp: entry.timestamp,
                                accessed: entry.accessed
                            });
                        }
                    }
                }
            }
        } catch (error) {
            this.logger.warn('Failed to search persistent storage', { error: error.message });
        }
        
        return results;
    }

    async _persistAllCachedData() {
        const promises = [];
        
        for (const [key, entry] of this.cache.entries()) {
            promises.push(this._persistToDisk(key, entry));
        }
        
        await Promise.all(promises);
        this.logger.info('All cached data persisted', { count: promises.length });
    }
}

module.exports = { MemoryManager };