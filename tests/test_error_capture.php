<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dashboard/admin_elements/error_logger.php';

echo "==================================================\n";
echo "Backend Error Capture Integration Tests\n";
echo "==================================================\n\n";

// Ensure we have a valid database connection
if (!isset($mysqli) || !$mysqli instanceof mysqli) {
    echo "ERROR: Database connection is not available.\n";
    exit(1);
}

// Ensure the table exists
$table = defined('App\Core\DB::BACKEND_ERROR_LOGS') ? \App\Core\DB::BACKEND_ERROR_LOGS : 'erp_backend_error_logs';
$tableCheck = $mysqli->query("SHOW TABLES LIKE '{$table}'");
if (!$tableCheck || $tableCheck->num_rows === 0) {
    echo "ERROR: Table '{$table}' does not exist. Run migrations first.\n";
    exit(1);
}

try {
    // Save current error handlers
    $oldErrorHandler = set_error_handler('custom_error_handler');
    $oldExceptionHandler = set_exception_handler('custom_exception_handler');

    // Create a unique test correlation ID to identify our test logs
    $testCorrelationId = 'test_corr_' . bin2hex(random_bytes(8));
    $_SERVER['HTTP_X_CORRELATION_ID'] = $testCorrelationId;
    $_SERVER['SCRIPT_NAME'] = '/tests/test_error_capture.php';
    $_SERVER['REQUEST_METHOD'] = 'CLI';

    // Test 1: Capture PHP Warnings
    echo "[TEST 1] Triggering PHP user warning... ";
    trigger_error("Mock test warning: division by zero simulation", E_USER_WARNING);

    // Assert Warning was written to DB
    $stmt = $mysqli->prepare("SELECT id, severity, message, source_file FROM `{$table}` WHERE correlation_id = ? AND severity = 'WARNING' LIMIT 1");
    $stmt->bind_param('s', $testCorrelationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $warningLog = $result->fetch_assoc();
    $stmt->close();

    if ($warningLog && strpos($warningLog['message'], "Mock test warning") !== false) {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL (Warning not logged to DB)\n";
        exit(1);
    }

    // Test 2: Capture Exceptions (simulated)
    echo "[TEST 2] Simulating uncaught exception... ";
    ob_start();
    $mockException = new \RuntimeException("Mock test exception message");
    custom_exception_handler($mockException);
    $output = ob_get_clean();

    // Assert Exception was written to DB
    $stmt = $mysqli->prepare("SELECT id, severity, message FROM `{$table}` WHERE correlation_id = ? AND severity = 'ERROR' LIMIT 1");
    $stmt->bind_param('s', $testCorrelationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exceptionLog = $result->fetch_assoc();
    $stmt->close();

    if ($exceptionLog && strpos($exceptionLog['message'], "Mock test exception message") !== false) {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL (Exception not logged to DB)\n";
        exit(1);
    }

    // Clean up test entries from DB to avoid cluttering logs
    $mysqli->query("DELETE FROM `{$table}` WHERE correlation_id = '{$testCorrelationId}'");
    echo "\nCleaned up test logs from database.\n";

    // Restore old handlers
    if ($oldErrorHandler) {
        set_error_handler($oldErrorHandler);
    }
    if ($oldExceptionHandler) {
        set_exception_handler($oldExceptionHandler);
    }

    echo "\nAll Backend Error Capture tests passed successfully!\n";

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
