<?php

declare(strict_types=1);

require_once __DIR__ . '/admin_elements/error_handler_init.php';

use App\Core\DB;

/**
 * Migration: Update Arslan email + Grant full HR permissions to HR Role
 *
 * Usage:
 *   php dashboard/migrate_hr_permissions.php
 *
 * Or via web: /dashboard/migrate_hr_permissions.php
 * LOCK THE FILE after running (rename or delete).
 */

// Force local DB detection when running from CLI
if (PHP_SAPI === 'cli' && empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}
require_once __DIR__ . '/../config/database.php';
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

$roleId = 6;
$now = date('Y-m-d H:i:s');

$hrModuleSlugs = [
    'users',
    'departments',
    'designations',
    'attendance',
    'leave_requests',
    'leave_types',
    'payroll_components',
    'employee_salaries',
    'payroll_runs',
    'report_hr',
];

$conn->begin_transaction();

try {
    // 1. Update Arslan's email
    $stmt = $conn->prepare("UPDATE " . DB::USERS . " SET email = ? WHERE id = ? AND email = ?");
    $newEmail = 'Arslansaleem4@gmail.com';
    $userId = 9;
    $oldEmail = 'arslan@flashlogisticsfzco.com';
    $stmt->bind_param('sis', $newEmail, $userId, $oldEmail);
    $stmt->execute();
    $emailUpdated = $stmt->affected_rows;
    echo "Email updated: {$emailUpdated} rows\n";

    // 2. Get module IDs for HR slugs using PDO-style Database wrapper
    $db = \App\Core\Container::getInstance()->get(\App\Core\Database::class);

    $moduleIds = [];
    $moduleSlugToId = [];
    foreach ($hrModuleSlugs as $slug) {
        $row = $db->fetchOne(
            "SELECT id FROM " . DB::MODULES . " WHERE slug = :slug LIMIT 1",
            ['slug' => $slug]
        );
        if ($row) {
            $id = (int)$row['id'];
            $moduleIds[] = $id;
            $moduleSlugToId[$slug] = $id;
        } else {
            echo "WARNING: Module slug '{$slug}' not found in DB\n";
        }
    }

    if (empty($moduleIds)) {
        throw new \Exception('No HR modules found');
    }

    // 3. Find all missing permissions for HR role on these modules
    $moduleIdList = implode(',', $moduleIds);
    $missingSql = "
        SELECT mp.id AS permission_id, mp.module_id, m.slug AS module_slug, mp.slug AS perm_slug
        FROM " . DB::MODULE_PERMISSIONS . " mp
        INNER JOIN " . DB::MODULES . " m ON m.id = mp.module_id
        LEFT JOIN " . DB::PERMISSIONS . " p
            ON p.role_id = {$roleId}
            AND p.module_id = mp.module_id
            AND p.permission_id = mp.id
        WHERE m.id IN ({$moduleIdList})
          AND p.id IS NULL
        ORDER BY m.id, mp.id";

    $missingRows = $db->fetchAll($missingSql);

    $inserted = 0;
    $insertStmt = $conn->prepare("INSERT INTO " . DB::PERMISSIONS . "
        (role_id, module_id, permission_id, is_active, created_by, created_at, updated_at, updated_by)
        VALUES (?, ?, ?, 1, 1, ?, ?, 1)");

    echo "\nMissing permissions to grant:\n";
    foreach ($missingRows as $row) {
        echo "  + module {$row['module_id']} ({$row['module_slug']}): perm_id={$row['permission_id']} ({$row['perm_slug']})\n";
        $permModuleId = (int)$row['module_id'];
        $permId = (int)$row['permission_id'];
        $insertStmt->bind_param('iiiss',
            $roleId,
            $permModuleId,
            $permId,
            $now,
            $now
        );
        $insertStmt->execute();
        $inserted += $insertStmt->affected_rows;
    }

    $conn->commit();

    echo "\n=== MIGRATION COMPLETE ===\n";
    echo "Email updated: {$emailUpdated}\n";
    echo "Permissions granted: {$inserted}\n";

} catch (Throwable $e) {
    $conn->rollback();
    echo "MIGRATION FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
