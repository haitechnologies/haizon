<?php
/**
 * Frontend Logging Configuration
 * 
 * Logs to logs/FRONTEND_ERROR_LOG.txt in same format as dashboard
 * Integrates with ErrorLogger class
 */

// Determine environment
$isProduction = isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production';
$isDevelopment = !$isProduction;

require_once __DIR__ . '/../vendor/autoload.php';

// Create logger instance (global)
if (!isset($GLOBALS['frontendLogger'])) {
    $GLOBALS['frontendLogger'] = new \App\Frontend\FrontendErrorLogger(
        __DIR__ . '/../logs/FRONTEND_ERROR_LOG.txt',
        $isProduction ? 'production' : 'development'
    );
    
    // Register global error handlers
    $GLOBALS['frontendLogger']->registerErrorHandler();
    $GLOBALS['frontendLogger']->registerExceptionHandler();
}

// Logging configuration
$logging_config = [
    'enabled' => true,
    'environment' => $isProduction ? 'production' : 'development',
    
    'levels' => [
        'error' => true,           // Always log errors
        'warning' => true,         // Always log warnings
        'notice' => true,          // Always log notices
        'debug' => $isDevelopment, // Only in development
        'info' => true,            // Log info messages
    ],
    
    'features' => [
        'request_tracking' => true,         // Log all requests
        'performance_monitoring' => true,   // Log slow requests > 500ms
        'database_queries' => $isDevelopment, // Log DB queries in dev
    ],
    
    'retention' => [
        'days' => 30,              // Keep 30 days by retention (optional)
    ],
    
    'log_file' => __DIR__ . '/../logs/FRONTEND_ERROR_LOG.txt',
];

// Return configuration
return $logging_config;
?>
