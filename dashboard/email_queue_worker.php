<?php
/**
 * Backward-compatible wrapper for the email queue worker.
 *
 * Preferred entrypoint: dashboard/cron/email/EmailQueueWorker.php
 */

require_once __DIR__ . '/admin_elements/error_handler_init.php';

// ── Error Logging Bootstrap ──────────────────────────────────────────
require_once __DIR__ . '/admin_elements/error_logger.php';
if (function_exists('custom_error_handler')) {
    set_error_handler('custom_error_handler');
}
if (function_exists('custom_exception_handler')) {
    set_exception_handler('custom_exception_handler');
}
if (function_exists('handle_fatal_error')) {
    register_shutdown_function('handle_fatal_error');
}
// ─────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/cron/email/EmailQueueWorker.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'This worker can only be executed from CLI.';
    exit;
}

try {
    $mysqli = $GLOBALS['DB']['MSQLI'] ?? null;
    if (!$mysqli instanceof mysqli) {
        fwrite(STDERR, "Database connection is not available.\n");
        exit(1);
    }

    $worker = new EmailQueueWorker($mysqli);
    $worker->run();
} catch (Throwable $e) {
    if (function_exists('log_error')) {
        log_error('[email_queue_worker.php] ' . $e->getMessage(), 'ERROR', __FILE__, __LINE__);
    }
    fwrite(STDERR, "Fatal error: " . $e->getMessage() . "\n");
    exit(1);
}

