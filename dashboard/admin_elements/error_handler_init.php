<?php

/**
 * Minimal error handler bootstrap.
 *
 * Include this file at the top of any PHP entry point to ensure
 * all PHP errors, exceptions, and fatal errors are captured.
 *
 * This file:
 *  1. Loads the ErrorLogger infrastructure
 *  2. Registers custom error/exception/shutdown handlers
 *  3. Fires a coverage heartbeat for backend_log_coverage tracking
 */

require_once __DIR__ . '/error_logger.php';

// Register custom error handlers
if (function_exists('custom_error_handler')) {
    set_error_handler('custom_error_handler');
}
if (function_exists('custom_exception_handler')) {
    set_exception_handler('custom_exception_handler');
}
if (function_exists('handle_fatal_error')) {
    register_shutdown_function('handle_fatal_error');
}

// Fire coverage heartbeat
if (function_exists('backend_log_coverage_heartbeat')) {
    backend_log_coverage_heartbeat(['entrypoint' => 'page']);
}
