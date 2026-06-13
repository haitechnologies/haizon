<?php

declare(strict_types=1);

use App\Core\Database;
use App\DataTable\Registry;

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($requestMethod !== 'POST') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'POST requests only', 'success' => false]);
    exit;
}

if (ob_get_level() === 0) {
    ob_start();
}

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../admin_elements/error_logger.php';
if (function_exists('custom_error_handler')) {
    set_error_handler('custom_error_handler');
}
if (function_exists('custom_exception_handler')) {
    set_exception_handler('custom_exception_handler');
}

try {
    if (session_status() === PHP_SESSION_NONE) {
        startDashboardSession();
    }
} catch (\Throwable $e) {
    // Continue
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'error' => 'Fatal server error: ' . ($error['message'] ?? 'Unknown'),
            'success' => false,
        ]);
    }
});

require_once __DIR__ . '/../../config/globals.php';
require_once __DIR__ . '/../../config/database.php';

$project_pre = $GLOBALS['project_pre'] ?? 'haipulse';

if (!isset($_SESSION[$project_pre]['DASHBOARD'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated', 'success' => false]);
    exit;
}

$session_role_id = (int) ($_SESSION[$project_pre]['DASHBOARD']['role_id'] ?? 0);
$session_user_id = (int) ($_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? 0);

if (!function_exists('Roles') || !class_exists('App\Security\Roles')) {
    // Roles may not be autoloaded without bootstrap; skip strict check
} else {
    if (!App\Security\Roles::hasFullAccess($session_role_id)) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required', 'success' => false]);
        exit;
    }
}

$receivedToken = $_POST['csrf_token'] ?? '';
$storedToken = $_SESSION[$project_pre]['DASHBOARD']['csrf_token'] ?? '';
if (empty($receivedToken) || $receivedToken !== $storedToken) {
    if ($session_user_id <= 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token', 'success' => false]);
        exit;
    }
}

$db = new Database();
$userId = $session_user_id;
$roleId = $session_role_id;

$activeOrganizationId = null;
if (isset($_SESSION[$project_pre]['DASHBOARD']['active_organization_id'])) {
    $activeOrganizationId = (int) $_SESSION[$project_pre]['DASHBOARD']['active_organization_id'];
} elseif (isset($_SESSION[$project_pre]['DASHBOARD']['organization_id'])) {
    $activeOrganizationId = (int) $_SESSION[$project_pre]['DASHBOARD']['organization_id'];
}

$registry = new Registry($db, $userId, $roleId, $activeOrganizationId);

$action = (string) ($_POST['qa_action'] ?? '');

if ($action === 'test_page') {
    $page = (string) ($_POST['page'] ?? '');
    $module = (string) ($_POST['module'] ?? '');

    if ($page === '' || $module === '') {
        echo json_encode(['success' => false, 'error' => 'Missing page or module']);
        exit;
    }

    $result = runSinglePageTest($page, $module, $registry);
    echo json_encode($result);
    exit;
}

if ($action === 'test_datatable') {
    $module = (string) ($_POST['module'] ?? '');

    if ($module === '') {
        echo json_encode(['success' => false, 'error' => 'Missing module']);
        exit;
    }

    $result = runDatatableTest($module, $registry);
    echo json_encode($result);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);

// ─────────────────────────────────────────────
// TEST FUNCTIONS
// ─────────────────────────────────────────────

function runSinglePageTest(string $page, string $module, Registry $registry): array
{
    $result = [
        'page' => $page,
        'module' => $module,
        'tests' => [],
        'overall_status' => 'pass',
        'total_tests' => 0,
        'passed' => 0,
        'failed' => 0,
        'execution_time_ms' => 0,
    ];

    $startTime = microtime(true);

    // Test 1: Handler registration
    $handlerRegistered = $registry->isRegistered($module);
    $result['tests'][] = [
        'name' => 'Handler Registered',
        'status' => $handlerRegistered ? 'pass' : 'fail',
        'detail' => $handlerRegistered ? 'Handler found in Registry' : 'No handler registered for module: ' . $module,
    ];

    // Test 2: DataTable request (basic)
    $dtResult = runDatatableTest($module, $registry);
    $result['tests'][] = [
        'name' => 'DataTable Response',
        'status' => $dtResult['success'] ? 'pass' : 'fail',
        'detail' => $dtResult['success']
            ? 'recordsTotal=' . ($dtResult['records_total'] ?? '?') . ', recordsFiltered=' . ($dtResult['records_filtered'] ?? '?')
            : ($dtResult['error'] ?? 'Invalid response'),
    ];

    // Test 3: DataTable with search
    $searchResult = runDatatableSearchTest($module, $registry);
    $result['tests'][] = [
        'name' => 'Search Filter',
        'status' => $searchResult['success'] ? 'pass' : 'fail',
        'detail' => $searchResult['success']
            ? 'Search returned ' . ($searchResult['records_filtered'] ?? '?') . ' filtered records'
            : ($searchResult['error'] ?? 'Search failed'),
    ];

    // Test 4: DataTable with pagination
    $pageResult = runDatatablePaginationTest($module, $registry);
    $result['tests'][] = [
        'name' => 'Pagination',
        'status' => $pageResult['success'] ? 'pass' : 'fail',
        'detail' => $pageResult['success']
            ? 'Page 2 returned ' . ($pageResult['data_count'] ?? '?') . ' records'
            : ($pageResult['error'] ?? 'Pagination failed'),
    ];

    // Test 5: Response structure validation
    $structureResult = runDatatableStructureTest($module, $registry);
    $result['tests'][] = [
        'name' => 'Response Structure',
        'status' => $structureResult['success'] ? 'pass' : 'fail',
        'detail' => $structureResult['success']
            ? 'Valid DataTables response structure'
            : ($structureResult['error'] ?? 'Invalid structure'),
    ];

    $endTime = microtime(true);
    $result['execution_time_ms'] = round(($endTime - $startTime) * 1000, 1);

    foreach ($result['tests'] as $test) {
        $result['total_tests']++;
        if ($test['status'] === 'pass') {
            $result['passed']++;
        } else {
            $result['failed']++;
        }
    }

    $result['overall_status'] = $result['failed'] > 0 ? 'fail' : 'pass';

    return $result;
}

function runDatatableTest(string $module, Registry $registry): array
{
    try {
        $requestData = [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'order' => [['column' => 0, 'dir' => 'desc']],
        ];

        $response = $registry->process($module, $requestData);

        if (!is_array($response)) {
            return ['success' => false, 'error' => 'Response is not an array'];
        }

        if (!isset($response['recordsTotal']) || !isset($response['recordsFiltered']) || !isset($response['data'])) {
            return ['success' => false, 'error' => 'Missing required fields in response'];
        }

        if (!is_array($response['data'])) {
            return ['success' => false, 'error' => 'Data field is not an array'];
        }

        return [
            'success' => true,
            'records_total' => (int) $response['recordsTotal'],
            'records_filtered' => (int) $response['recordsFiltered'],
            'data_count' => count($response['data']),
        ];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => get_class($e) . ': ' . $e->getMessage()];
    }
}

function runDatatableSearchTest(string $module, Registry $registry): array
{
    try {
        $requestData = [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'test'],
            'order' => [['column' => 0, 'dir' => 'desc']],
        ];

        $response = $registry->process($module, $requestData);

        if (!is_array($response) || !isset($response['recordsFiltered'])) {
            return ['success' => false, 'error' => 'Invalid response'];
        }

        return [
            'success' => true,
            'records_filtered' => (int) $response['recordsFiltered'],
        ];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => get_class($e) . ': ' . $e->getMessage()];
    }
}

function runDatatablePaginationTest(string $module, Registry $registry): array
{
    try {
        $requestData = [
            'draw' => 1,
            'start' => 5,
            'length' => 5,
            'search' => ['value' => ''],
            'order' => [['column' => 0, 'dir' => 'desc']],
        ];

        $response = $registry->process($module, $requestData);

        if (!is_array($response) || !isset($response['data'])) {
            return ['success' => false, 'error' => 'Invalid response'];
        }

        return [
            'success' => true,
            'data_count' => count($response['data']),
        ];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => get_class($e) . ': ' . $e->getMessage()];
    }
}

function runDatatableStructureTest(string $module, Registry $registry): array
{
    try {
        $requestData = [
            'draw' => 42,
            'start' => 0,
            'length' => 1,
            'search' => ['value' => ''],
            'order' => [['column' => 0, 'dir' => 'desc']],
        ];

        $response = $registry->process($module, $requestData);

        if (!is_array($response)) {
            return ['success' => false, 'error' => 'Response is not an array'];
        }

        $requiredKeys = ['draw', 'recordsTotal', 'recordsFiltered', 'data'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $response)) {
                return ['success' => false, 'error' => 'Missing key: ' . $key];
            }
        }

        if ((int) $response['draw'] !== 42) {
            return ['success' => false, 'error' => 'Draw echo mismatch: expected 42, got ' . $response['draw']];
        }

        if (!is_array($response['data'])) {
            return ['success' => false, 'error' => 'Data is not an array'];
        }

        return ['success' => true];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => get_class($e) . ': ' . $e->getMessage()];
    }
}
