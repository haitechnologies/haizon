<?php

require_once __DIR__ . '/admin_elements/error_handler_init.php';

use App\Core\DB;
// Sync hai_permissions for System Admin (role_id=1) with current modules and module permissions.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/admin_elements/error_logger.php';

// Register custom error/exception/shutdown handlers for CLI execution
if (function_exists('custom_error_handler')) {
    set_error_handler('custom_error_handler');
}
if (function_exists('custom_exception_handler')) {
    set_exception_handler('custom_exception_handler');
}
if (function_exists('handle_fatal_error')) {
    register_shutdown_function('handle_fatal_error');
}
// Removed legacy require for autoloader compatibility: require_once __DIR__ . '/../classes/DB.php';

$roleId = 1;
$now = date('Y-m-d H:i:s');

$conn->begin_transaction();

try {
    // Collect valid module IDs
    $moduleIds = [];
    $result = $conn->query("SELECT id FROM " . DB::MODULES);
    while ($row = $result->fetch_assoc()) {
        $moduleIds[] = (int)$row['id'];
    }

    if (empty($moduleIds)) {
        throw new Exception('No modules found in ' . DB::MODULES);
    }

    $moduleIdList = implode(',', $moduleIds);

    // Collect valid permission IDs
    $permissionIds = [];
    $result = $conn->query("SELECT id FROM " . DB::MODULE_PERMISSIONS . " WHERE module_id IN ({$moduleIdList})");
    while ($row = $result->fetch_assoc()) {
        $permissionIds[] = (int)$row['id'];
    }

    if (empty($permissionIds)) {
        throw new Exception('No module permissions found in ' . DB::MODULE_PERMISSIONS);
    }

    $permissionIdList = implode(',', $permissionIds);

    // Remove orphaned permissions
    $deleteSql = "DELETE FROM " . DB::PERMISSIONS . "
        WHERE role_id = {$roleId}
          AND (module_id NOT IN ({$moduleIdList})
               OR permission_id NOT IN ({$permissionIdList}))";
    $conn->query($deleteSql);
    $deleted = $conn->affected_rows;

    // Insert missing permissions (full access for role_id=1)
    $missingSql = "
        SELECT mp.id AS permission_id, mp.module_id
        FROM " . DB::MODULE_PERMISSIONS . " mp
        INNER JOIN " . DB::MODULES . " m ON m.id = mp.module_id
        LEFT JOIN " . DB::PERMISSIONS . " p
            ON p.role_id = {$roleId}
            AND p.module_id = mp.module_id
            AND p.permission_id = mp.id
        WHERE p.id IS NULL";

    $missing = $conn->query($missingSql);
    $inserted = 0;

    if ($missing) {
        $stmt = $conn->prepare("INSERT INTO " . DB::PERMISSIONS . " (role_id, module_id, permission_id, is_active, created_by, created_at, updated_at, updated_by)
             VALUES (?, ?, ?, 1, 1, ?, ?, 1)"
        );

        while ($row = $missing->fetch_assoc()) {
            $permissionId = (int)$row['permission_id'];
            $moduleId = (int)$row['module_id'];
            $stmt->bind_param('iiiss', $roleId, $moduleId, $permissionId, $now, $now);
            $stmt->execute();
            $inserted += $stmt->affected_rows;
        }

        $stmt->close();
    }

    $conn->commit();

    echo "Sync completed.\n";
    echo "Deleted orphaned rows: {$deleted}\n";
    echo "Inserted missing rows: {$inserted}\n";
} catch (Exception $e) {
    $conn->rollback();
    echo "Sync failed: " . $e->getMessage() . "\n";
}
