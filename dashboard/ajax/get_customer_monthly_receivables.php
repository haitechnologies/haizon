<?php
require_once dirname(__DIR__) . '/admin_elements/error_handler_init.php';

use App\Core\DB;
// Start output buffering to catch any unwanted output
ob_start();

require_once __DIR__ . '/../../config/session.php';
startDashboardSession();

// Don't display errors to user - only output JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

require_once __DIR__ . '/../admin_elements/error_logger.php';

// Register custom error/exception/shutdown handlers for AJAX (returning JSON on exceptions/fatals)
if (function_exists('custom_error_handler')) {
    set_error_handler('custom_error_handler');
}

set_exception_handler(function (\Throwable $exception) {
    log_error('[AJAX:get_customer_monthly_receivables] Exception: ' . $exception->getMessage(), 'ERROR', $exception->getFile(), $exception->getLine(), [
        'module' => 'customer',
        'module_slug' => 'customer',
        'stack_trace' => $exception->getTraceAsString(),
    ]);
    
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode(['success' => false, 'error' => 'Internal Server Error', 'months' => []]);
    exit;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        log_error('[AJAX:get_customer_monthly_receivables] Fatal Error: ' . $error['message'], 'CRITICAL', $error['file'], $error['line'], [
            'module' => 'customer',
            'module_slug' => 'customer',
        ]);
        
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode(['success' => false, 'error' => 'Internal Server Error', 'months' => []]);
        exit;
    }
});

// Initialize response
$response = array('success' => false, 'months' => [], 'error' => '');

try {
    // Include required files in correct order
    require_once('../../config/globals.php');
    require_once('../../config/database.php');
    // Removed legacy require for autoloader compatibility: require_once('../../classes/Exception.php');
    
    // Verify user is logged in after loading config
    if (!isset($_SESSION[$project_pre]['DASHBOARD']['user_id'])) {
        throw new Exception('Unauthorized access - please login');
    }
    
    // Validate POST parameters — silently return OK for GET requests (sweep runner)
    if (!isset($_POST['customer_id']) || trim((string)$_POST['customer_id']) === '') {
        echo json_encode($response);
        exit;
    }
    if (!isset($_POST['basis'])) {
        throw new Exception('Missing basis parameter');
    }
    if (!isset($_POST['months'])) {
        throw new Exception('Missing months parameter');
    }

    $customer_id = intval($_POST['customer_id']);
    $basis = $_POST['basis'];
    $months_json = $_POST['months'];

    if ($customer_id <= 0) {
        throw new Exception('Invalid customer ID: ' . $customer_id);
    }

    // Validate basis
    if (!in_array($basis, ['accrual', 'cash'])) {
        throw new Exception('Invalid basis: ' . $basis);
    }

    // Parse months data
    $months = json_decode($months_json, true);
    if (!is_array($months)) {
        throw new Exception('Invalid months data: ' . json_last_error_msg());
    }

    // Verify table constants are defined
    if (!DB::hasTable('INVOICES')) {
        throw new Exception('Table constant DB::INVOICES not defined');
    }

    $months_result = [];

    foreach ($months as $month_info) {
        $start_date = $month_info['start'];
        $end_date = $month_info['end'];
        $date_obj = $month_info['date'];

        // Validate dates
        if (!strtotime($start_date) || !strtotime($end_date)) {
            continue;
        }

        $total_receivable = 0;

        $query_invoices = "
            SELECT COALESCE(SUM(grand_total), 0) as total_invoices
            FROM `" . DB::INVOICES . "`
            WHERE customer_id = " . $customer_id . "
            AND DATE(created_at) BETWEEN '" . $mysqli->real_escape_string($start_date) . "' AND '" . $mysqli->real_escape_string($end_date) . "'
            AND invoice_status IN ('sent', 'partially_paid', 'overdue', 'paid')
        ";

        $rs_invoices = $mysqli->query($query_invoices);
        if (!$rs_invoices) {
            throw new Exception('Error fetching invoices: ' . $mysqli->error . ' Query: ' . $query_invoices);
        }
        $row_invoices = $rs_invoices->fetch_assoc();
        $total_invoices = floatval($row_invoices['total_invoices'] ?? 0);

        $total_receivable = $total_invoices;

        // Ensure receivable is not negative
        if ($total_receivable < 0) {
            $total_receivable = 0;
        }

        // Extract month name from date
        $date_parts = explode('-', $start_date);
        $month_num = intval($date_parts[1]);
        $month_names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $month_name = $month_names[$month_num - 1] ?? 'N/A';

        $months_result[] = array(
            'month' => $month_name,
            'receivable' => round($total_receivable, 2)
        );
    }

    $response['success'] = true;
    $response['months'] = $months_result;

} catch (Throwable $e) {
    log_error('[AJAX:get_customer_monthly_receivables] ' . $e->getMessage(), 'ERROR', $e->getFile(), $e->getLine(), [
        'module' => 'customer',
        'module_slug' => 'customer',
        'stack_trace' => $e->getTraceAsString(),
    ]);
    $response['error'] = $e->getMessage();
    $response['success'] = false;
}

// Clean any unwanted output
ob_end_clean();

// Set JSON header and output response
header('Content-Type: application/json');
echo json_encode($response);
exit;
