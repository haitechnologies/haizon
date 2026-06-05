<?php
/**
 * APIDispatcher.php - Central Router for All API Requests
 * 
 * Routes all API requests to appropriate handlers based on action parameter.
 * Handles:
 * - Request validation
 * - Route mapping
 * - Error handling
 * - Request logging
 * 
 * @package API
 * @version 1.0.0
 */

require_once __DIR__ . '/../../config/session.php';

// Start session if not already started
startDashboardSession();

require_once('../../config/globals.php');
require_once('../../config/database.php');
require_once('../admin_elements/error_logger.php');

// Register custom error/exception/shutdown handlers for API (returning JSON on exceptions/fatals)
if (function_exists('custom_error_handler')) {
    set_error_handler('custom_error_handler');
}

// Custom JSON exception handler for API dispatcher
set_exception_handler(function (\Throwable $exception) {
    // Log the exception first
    $context = backend_runtime_log_context([
        'exception_class' => get_class($exception),
        'trace_depth' => count($exception->getTrace())
    ]);
    
    log_error(
        'Exception: ' . $exception->getMessage(),
        'ERROR',
        $exception->getFile(),
        $exception->getLine(),
        $context
    );
    
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal Server Error',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
});

// Custom JSON fatal error shutdown handler for API dispatcher
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $context = backend_runtime_log_context(['error_type' => $error['type']]);
        
        log_error(
            'Fatal Error: ' . $error['message'],
            'CRITICAL',
            $error['file'],
            $error['line'],
            $context
        );
        
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Internal Server Error',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
});

require_once('BaseAPI.php');
require_once('ItemAPI.php');
require_once('CustomerAPI.php');

if (function_exists('backend_log_coverage_heartbeat')) {
    backend_log_coverage_heartbeat([
        'module' => 'api_dispatcher',
        'module_slug' => 'api',
        'entrypoint_type' => 'api',
    ]);
}

class APIDispatcher {
    
    /**
     * @var string Requested action
     */
    private $action;
    
    /**
     * @var array Route mapping [action => [ClassName, methodName]]
     */
    private $routes = [
        'getServices' => ['ItemAPI', 'getServices'],
        'getItemRate' => ['ItemAPI', 'getItemRate'],
        'populateCustomers' => ['CustomerAPI', 'populateCustomers'],
    ];
    
    /**
     * Constructor - Initialize dispatcher and route request
     */
    public function __construct() {
        $this->action = $_POST['action'] ?? null;
        $this->dispatch();
    }
    
    /**
     * Dispatch request to appropriate handler
     */
    private function dispatch() {
        // Get action from request
        if (!$this->action) {
            $this->sendErrorResponse('No action specified', 400);
        }
        
        // Check if route exists
        if (!isset($this->routes[$this->action])) {
            $this->sendErrorResponse("Unknown action: {$this->action}", 404);
        }
        
        try {
            [$className, $methodName] = $this->routes[$this->action];

            if (function_exists('backend_log_coverage_heartbeat')) {
                backend_log_coverage_heartbeat([
                    'module' => $this->action,
                    'module_slug' => 'api',
                    'entrypoint_type' => 'api',
                ]);
            }
            
            // Instantiate handler
            $handler = new $className();
            
            // Call method
            if (!method_exists($handler, $methodName)) {
                throw new Exception("Method $methodName not found in $className");
            }
            
            $handler->$methodName();
            
        } catch (Throwable $e) {
            log_error('[API Dispatch Error] ' . $e->getMessage(), 'ERROR', $e->getFile(), $e->getLine(), [
                'module' => 'api_dispatcher',
                'module_slug' => 'api',
                'action' => $this->action,
                'stack_trace' => $e->getTraceAsString(),
            ]);
            $this->sendErrorResponse($e->getMessage(), 500);
        }
    }
    
    /**
     * Send error response
     */
    private function sendErrorResponse($message, $code = 400) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    /**
     * Register new route
     * 
     * @param string $action Action name
     * @param string $className Handler class name
     * @param string $methodName Handler method name
     */
    public function registerRoute($action, $className, $methodName) {
        $this->routes[$action] = [$className, $methodName];
    }
}

// Initialize dispatcher
new APIDispatcher();
