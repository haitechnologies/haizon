/**
 * Centralized DataTable Error Handler
 * Version: 1.0.0
 * 
 * Provides consistent error handling and user feedback across all DataTable implementations.
 * Replaces fragmented per-page error handling with unified approach.
 * 
 * Usage:
 *   window.HAIDatatableErrorHandler.showError(tableSelector, message, details);
 *   window.HAIDatatableErrorHandler.showEmpty(tableSelector, message);
 *   window.HAIDatatableErrorHandler.showProcessing(tableSelector);
 *   window.HAIDatatableErrorHandler.hideProcessing(tableSelector);
 */

(function(window, $) {
    'use strict';

    var ERROR_MESSAGES = {
        NETWORK: 'Network connection error. Please check your internet connection.',
        SERVER: 'Server error occurred. Please try again later.',
        TIMEOUT: 'Request timed out. The server took too long to respond.',
        NO_DATA: 'No records found matching your criteria.',
        GENERIC: 'An error occurred while loading data.',
        PERMISSION: 'You do not have permission to view this data.',
        CSRF: 'Security token expired. Please refresh the page.'
    };

    var ERROR_CODES = {
        NETWORK_ERROR: 'NETWORK',
        SERVER_ERROR: 'SERVER',
        TIMEOUT_ERROR: 'TIMEOUT',
        NO_DATA: 'NO_DATA',
        GENERIC_ERROR: 'GENERIC',
        PERMISSION_ERROR: 'PERMISSION',
        CSRF_ERROR: 'CSRF'
    };

    var retryTimers = {};
    var retryAttempts = {};

    /**
     * Get jQuery instance (handle multiple jQuery versions)
     */
    function getJQuery() {
        return window.jQuery || window.$ || $;
    }

    /**
     * Categorize error based on XHR status and response
     */
    function categorizeError(xhr, status, error) {
        if (!xhr || !xhr.status) {
            return ERROR_CODES.NETWORK_ERROR;
        }

        var statusCode = xhr.status;
        var responseText = xhr.responseText || '';

        if (statusCode === 0) {
            return ERROR_CODES.NETWORK_ERROR;
        }

        if (statusCode === 403 || responseText.toLowerCase().indexOf('permission') >= 0) {
            return ERROR_CODES.PERMISSION_ERROR;
        }

        if (statusCode === 419 || responseText.toLowerCase().indexOf('csrf') >= 0) {
            return ERROR_CODES.CSRF_ERROR;
        }

        if (statusCode === 504 || statusCode === 408 || status === 'timeout') {
            return ERROR_CODES.TIMEOUT_ERROR;
        }

        if (statusCode >= 500) {
            return ERROR_CODES.SERVER_ERROR;
        }

        return ERROR_CODES.GENERIC_ERROR;
    }

    /**
     * Get user-friendly message for error code
     */
    function getErrorMessage(errorCode) {
        return ERROR_MESSAGES[errorCode] || ERROR_MESSAGES.GENERIC;
    }

    /**
     * Create error display HTML
     */
    function createErrorHtml(message, details, errorCode, showRetry) {
        var jq = getJQuery();
        var htmlParts = [];

        htmlParts.push('<div class="datatable-error-container">');
        htmlParts.push('<div class="datatable-error-content">');
        
        // Icon
        htmlParts.push('<div class="datatable-error-icon">');
        if (errorCode === ERROR_CODES.NO_DATA) {
            htmlParts.push('<i class="ph-magnifying-glass ph-3x text-muted"></i>');
        } else {
            htmlParts.push('<i class="ph-warning-circle ph-3x text-warning"></i>');
        }
        htmlParts.push('</div>');
        
        // Message
        htmlParts.push('<div class="datatable-error-message">');
        htmlParts.push('<h4>' + htmlspecialchars(message) + '</h4>');
        if (details) {
            htmlParts.push('<p class="text-muted small">' + htmlspecialchars(details) + '</p>');
        }
        htmlParts.push('</div>');
        
        // Actions
        htmlParts.push('<div class="datatable-error-actions">');
        
        if (showRetry) {
            htmlParts.push('<button type="button" class="btn btn-primary btn-sm datatable-retry">');
            htmlParts.push('<i class="ph-arrow-clockwise me-1"></i>Retry');
            htmlParts.push('</button>');
        }
        
        htmlParts.push('<button type="button" class="btn btn-light btn-sm datatable-refresh">');
        htmlParts.push('<i class="ph-arrows-clockwise me-1"></i>Refresh Page');
        htmlParts.push('</button>');
        
        htmlParts.push('</div>');
        
        htmlParts.push('</div>');
        htmlParts.push('</div>');

        return htmlParts.join('');
    }

    /**
     * Simple HTML escaping
     */
    function htmlspecialchars(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    /**
     * Clear retry timer for table
     */
    function clearRetryTimer(tableId) {
        if (retryTimers[tableId]) {
            clearTimeout(retryTimers[tableId]);
            delete retryTimers[tableId];
        }
        delete retryAttempts[tableId];
    }

    /**
     * Show error message in DataTable
     */
    function showError(tableSelector, message, details, showRetry) {
        var jq = getJQuery();
        
        if (!jq || !tableSelector) {
            console.error('[DataTableErrorHandler] Invalid parameters');
            return;
        }

        var $table = jq(tableSelector);
        if (!$table.length) {
            console.error('[DataTableErrorHandler] Table not found:', tableSelector);
            return;
        }

        // Hide processing indicator
        hideProcessing(tableSelector);

        // Clear any existing error displays
        $table.find('tbody').remove();
        $table.closest('.dataTables_wrapper').find('.datatable-error-container').remove();

        var errorCode = ERROR_CODES.GENERIC_ERROR;
        var errorHtml = createErrorHtml(message || ERROR_MESSAGES.GENERIC, details, errorCode, showRetry);

        // Insert error display
        $table.after(errorHtml);

        // Bind retry action
        if (showRetry) {
            var tableId = tableSelector.replace(/[^a-z0-9]/gi, '_');
            
            jq(tableSelector).closest('.dataTables_wrapper').find('.datatable-retry').off('click').on('click', function() {
                jq(tableSelector).closest('.dataTables_wrapper').find('.datatable-error-container').remove();
                
                // Trigger DataTable reload if available
                if (jq.fn.DataTable && jq.fn.DataTable.isDataTable(tableSelector)) {
                    var table = jq(tableSelector).DataTable();
                    showProcessing(tableSelector);
                    table.ajax.reload(function() {
                        hideProcessing(tableSelector);
                    }, false);
                }
            });
        }

        // Bind refresh action
        jq(tableSelector).closest('.dataTables_wrapper').find('.datatable-refresh').off('click').on('click', function() {
            window.location.reload();
        });
    }

    /**
     * Show AJAX error (categorizes and displays appropriate message)
     */
    function showAjaxError(tableSelector, xhr, status, error, moduleName) {
        var errorCode = categorizeError(xhr, status, error);
        var message = getErrorMessage(errorCode);
        var details = '';

        // Add module name to console logging
        if (moduleName) {
            console.error('[DataTableErrorHandler:' + moduleName + '] AJAX Error:', error);
            console.error('[DataTableErrorHandler:' + moduleName + '] Status:', xhr.status, '|', status);
        } else {
            console.error('[DataTableErrorHandler] AJAX Error:', error);
            console.error('[DataTableErrorHandler] Status:', xhr.status, '|', status);
        }

        // Log response excerpt (first 200 chars)
        if (xhr.responseText) {
            console.error('[DataTableErrorHandler] Response:', xhr.responseText.substring(0, 200));
        }

        // Show retry for network/timeout errors
        var showRetry = (errorCode === ERROR_CODES.NETWORK_ERROR || errorCode === ERROR_CODES.TIMEOUT_ERROR);

        showError(tableSelector, message, details, showRetry);
    }

    /**
     * Show empty state (no data)
     */
    function showEmpty(tableSelector, message) {
        var jq = getJQuery();
        
        if (!jq || !tableSelector) {
            return;
        }

        var $table = jq(tableSelector);
        if (!$table.length) {
            return;
        }

        hideProcessing(tableSelector);

        // Let DataTable handle empty state naturally
        // This function is provided for custom empty messages if needed
        console.log('[DataTableErrorHandler] Empty state for:', tableSelector);
    }

    /**
     * Show processing indicator
     */
    function showProcessing(tableSelector) {
        var jq = getJQuery();
        
        if (!jq || !tableSelector) {
            return;
        }

        var $wrapper = jq(tableSelector).closest('.dataTables_wrapper');
        var $processing = $wrapper.find('.dataTables_processing');
        
        if ($processing.length) {
            $processing.show();
        }
    }

    /**
     * Hide processing indicator
     */
    function hideProcessing(tableSelector) {
        var jq = getJQuery();
        
        if (!jq || !tableSelector) {
            return;
        }

        var $wrapper = jq(tableSelector).closest('.dataTables_wrapper');
        var $processing = $wrapper.find('.dataTables_processing');
        
        if ($processing.length) {
            $processing.hide();
        }
    }

    /**
     * Auto-retry with exponential backoff
     */
    function autoRetry(tableSelector, maxAttempts, callback) {
        var jq = getJQuery();
        var tableId = tableSelector.replace(/[^a-z0-9]/gi, '_');
        
        retryAttempts[tableId] = retryAttempts[tableId] || 0;
        
        if (retryAttempts[tableId] >= maxAttempts) {
            console.log('[DataTableErrorHandler] Max retry attempts reached for:', tableSelector);
            return false;
        }

        retryAttempts[tableId]++;
        var delay = Math.pow(2, retryAttempts[tableId] - 1) * 1000; // 1s, 2s, 4s, 8s...
        
        console.log('[DataTableErrorHandler] Retry attempt', retryAttempts[tableId], 'in', delay, 'ms');
        
        retryTimers[tableId] = setTimeout(function() {
            if (typeof callback === 'function') {
                callback();
            }
        }, delay);
        
        return true;
    }

    /**
     * Clear all error displays on page
     */
    function clearAllErrors() {
        var jq = getJQuery();
        if (jq) {
            jq('.datatable-error-container').remove();
        }
    }

    // Public API
    window.HAIDatatableErrorHandler = {
        showError: showError,
        showAjaxError: showAjaxError,
        showEmpty: showEmpty,
        showProcessing: showProcessing,
        hideProcessing: hideProcessing,
        autoRetry: autoRetry,
        clearAllErrors: clearAllErrors,
        ERROR_CODES: ERROR_CODES,
        ERROR_MESSAGES: ERROR_MESSAGES
    };

    // Log initialization
    console.log('[DataTableErrorHandler] Loaded version 1.0.0');

})(window, window.jQuery || window.$);
