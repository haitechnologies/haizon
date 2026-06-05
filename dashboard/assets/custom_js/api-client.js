/**
 * APIClient.js - Modern AJAX Client with Request Deduplication & Error Handling
 * 
 * Features:
 * - Promise-based (Fetch API)
 * - Automatic CSRF token handling
 * - Request deduplication
 * - Timeout management
 * - Loading state management
 * - Comprehensive error handling
 * 
 * @version 2.0.0
 * @author Professional API Implementation
 */

class APIClient {
    
    /**
     * @param {string} endpoint - API endpoint URL
     * @param {number} timeout - Request timeout in milliseconds
     */
    constructor(endpoint = 'api/dispatcher.php', timeout = 30000) {
        this.endpoint = endpoint;
        this.timeout = timeout;
        this.pendingRequests = new Map(); // Deduplication
        this.requestCallbacks = new Map(); // Progress callbacks
        this.cache = new Map(); // Optional caching
        this.cacheTime = 5 * 60 * 1000; // 5 minutes default
    }
    
    /**
     * Make API request with deduplication
     * 
     * @param {string} action - Action name
     * @param {object} data - Request data
     * @param {object} options - Additional options
     * @returns {Promise}
     */
    async request(action, data = {}, options = {}) {
        const requestKey = this.generateRequestKey(action, data);
        
        // Return cached result if available
        if (options.useCache && this.cache.has(requestKey)) {
            const cached = this.cache.get(requestKey);
            if (Date.now() - cached.timestamp < this.cacheTime) {
                console.log(`[APIClient] Using cached result for ${action}`);
                return cached.data;
            }
        }
        
        // Prevent duplicate requests
        if (this.pendingRequests.has(requestKey)) {
            console.log(`[APIClient] Deduplicating request: ${action}`);
            return this.pendingRequests.get(requestKey);
        }
        
        // Execute request
        const promise = this._executeRequest(action, data, options);
        this.pendingRequests.set(requestKey, promise);
        
        // Clean up after request completes
        promise
            .then(result => {
                // Cache successful result
                if (options.useCache) {
                    this.cache.set(requestKey, {
                        data: result,
                        timestamp: Date.now()
                    });
                }
                return result;
            })
            .finally(() => {
                this.pendingRequests.delete(requestKey);
                this._triggerCallback(requestKey, 'complete');
            });
        
        return promise;
    }
    
    /**
     * Execute HTTP request
     * 
     * @private
     */
    async _executeRequest(action, data, options) {
        const startTime = performance.now();
        
        try {
            // Prepare form data
            const formData = new FormData();
            formData.append('action', action);
            
            // Add CSRF token
            const csrfToken = this._getCSRFToken();
            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }
            formData.append('csrf_token', csrfToken);
            
            // Add request data
            Object.entries(data).forEach(([key, value]) => {
                if (value instanceof File) {
                    formData.append(key, value);
                } else if (value === null || value === undefined) {
                    // Skip null/undefined values
                } else {
                    formData.append(key, value);
                }
            });
            
            // Create abort controller for timeout
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), this.timeout);
            
            // Emit loading event
            this._triggerCallback(action, 'loading', { action });
            
            // Make request
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData,
                signal: controller.signal,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            clearTimeout(timeoutId);
            
            // Parse response
            const result = await response.json();
            const duration = performance.now() - startTime;
            
            // Check HTTP status
            if (!response.ok) {
                throw new APIError(
                    result.message || `HTTP ${response.status}`,
                    response.status,
                    result.data
                );
            }
            
            // Check API response status
            if (result.status === 'error') {
                throw new APIError(result.message, 400, result.data);
            }
            
            // Log successful request
            console.log(`[APIClient] ${action} completed in ${duration.toFixed(0)}ms`, result);
            this._triggerCallback(action, 'success', result);
            
            return result;
            
        } catch (error) {
            const duration = performance.now() - startTime;
            
            // Handle abort/timeout
            if (error.name === 'AbortError') {
                const timeoutError = new APIError(
                    `Request timeout after ${this.timeout}ms`,
                    408,
                    null
                );
                console.error(`[APIClient] ${action} timed out after ${duration.toFixed(0)}ms`);
                this._triggerCallback(action, 'error', timeoutError);
                throw timeoutError;
            }
            
            // Handle network errors
            if (error instanceof TypeError) {
                const networkError = new APIError(
                    'Network error: Unable to reach server',
                    0,
                    null
                );
                console.error(`[APIClient] ${action} network error: ${error.message}`);
                this._triggerCallback(action, 'error', networkError);
                throw networkError;
            }
            
            // Handle API errors
            if (error instanceof APIError) {
                console.error(`[APIClient] ${action} API error: ${error.message}`);
                this._triggerCallback(action, 'error', error);
                throw error;
            }
            
            // Handle unknown errors
            const unknownError = new APIError(error.message, 500, null);
            console.error(`[APIClient] ${action} unknown error: ${error.message}`);
            this._triggerCallback(action, 'error', unknownError);
            throw unknownError;
        }
    }
    
    /**
     * GET CSRF token from DOM
     * 
     * @private
     */
    _getCSRFToken() {
        // Try to find CSRF token in various common locations
        const token = 
            document.querySelector('input[name="csrf_token"]')?.value ||
            document.querySelector('[data-csrf-token]')?.getAttribute('data-csrf-token') ||
            window._csrfToken;
        
        return token || null;
    }
    
    /**
     * Generate unique request key for deduplication
     * 
     * @private
     */
    generateRequestKey(action, data) {
        // Sort data keys for consistent hashing
        const sortedData = Object.keys(data)
            .sort()
            .reduce((result, key) => {
                result[key] = data[key];
                return result;
            }, {});
        
        return `${action}:${JSON.stringify(sortedData)}`;
    }
    
    /**
     * Register callback for request events
     * 
     * @param {string} action - Action name
     * @param {function} callback - Callback function
     */
    onLoading(action, callback) {
        this._registerCallback(action, 'loading', callback);
    }
    
    onSuccess(action, callback) {
        this._registerCallback(action, 'success', callback);
    }
    
    onError(action, callback) {
        this._registerCallback(action, 'error', callback);
    }
    
    onComplete(action, callback) {
        this._registerCallback(action, 'complete', callback);
    }
    
    /**
     * Register callback
     * 
     * @private
     */
    _registerCallback(action, event, callback) {
        const key = `${action}:${event}`;
        if (!this.requestCallbacks.has(key)) {
            this.requestCallbacks.set(key, []);
        }
        this.requestCallbacks.get(key).push(callback);
    }
    
    /**
     * Trigger callbacks
     * 
     * @private
     */
    _triggerCallback(action, event, data = null) {
        const key = `${action}:${event}`;
        const callbacks = this.requestCallbacks.get(key) || [];
        
        callbacks.forEach(callback => {
            try {
                callback(data);
            } catch (error) {
                console.error(`[APIClient] Callback error:`, error);
            }
        });
    }
    
    /**
     * Clear cache
     */
    clearCache(action = null) {
        if (action) {
            for (let [key, _] of this.cache) {
                if (key.startsWith(`${action}:`)) {
                    this.cache.delete(key);
                }
            }
        } else {
            this.cache.clear();
        }
    }
    
    /**
     * Cancel pending request
     */
    cancelRequest(action, data = {}) {
        const key = this.generateRequestKey(action, data);
        if (this.pendingRequests.has(key)) {
            this.pendingRequests.delete(key);
            console.log(`[APIClient] Cancelled request: ${action}`);
        }
    }
}

/**
 * Custom API Error Class
 */
class APIError extends Error {
    constructor(message, statusCode, data) {
        super(message);
        this.name = 'APIError';
        this.statusCode = statusCode;
        this.data = data;
    }
}

// Export for use
window.APIClient = APIClient;
window.APIError = APIError;

// Create global instance
const api = new APIClient('api/dispatcher.php');
