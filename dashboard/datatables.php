<?php
// Route all DataTable requests through the new modular dispatcher.
require_once __DIR__ . '/datatables_dispatcher.php';
exit;

/*
|--------------------------------------------------------------------------|
| DataTables AJAX Dispatcher (Refactored - Phase 1)
|--------------------------------------------------------------------------|
| Source: dashboard/datatables.php
| Purpose: Lightweight router for server-side DataTable requests
| Version: 2.0 (Modular Handler Pattern)
|--------------------------------------------------------------------------|
*/

// Load dependencies
require('../config/globals.php');
require('../config/database.php');
require('../classes/DataTable/BaseDataTable.php');
require('../classes/DataTable/Registry.php');


// Ensure JSON response
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

try {
    // Get module name from request
    $module = !empty($_POST['ajax_action']) ? sanitize_module_name($_POST['ajax_action']) : '';
    
    if (empty($module)) {
        throw new Exception('Invalid or missing module parameter');
    }
    
    // Get handler from registry
    $handler = DataTable_Registry::getHandler($module, $conn);
    
    if ($handler === null) {
        throw new Exception('Unknown module: ' . htmlspecialchars($module));
    }
    
    // Process the request
    $response = $handler->process($_POST);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log error
    error_log("[DataTables] " . $e->getMessage());
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'draw' => intval($_POST['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'An error occurred processing your request'
    ]);
}

/**
 * Sanitize module name (whitelist approach)
 */
function sanitize_module_name($name) {
    $name = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    return strtolower($name);
}
?>