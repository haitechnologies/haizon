<?php
/**
 * Backward-compatible wrapper for the email queue worker.
 *
 * Preferred entrypoint: dashboard/cron/email/EmailQueueWorker.php
 */

require_once __DIR__ . '/cron/email/EmailQueueWorker.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'This worker can only be executed from CLI.';
    exit;
}

$mysqli = $GLOBALS['DB']['MSQLI'] ?? null;
if (!$mysqli instanceof mysqli) {
    fwrite(STDERR, "Database connection is not available.\n");
    exit(1);
}

$worker = new EmailQueueWorker($mysqli);
$worker->run();
