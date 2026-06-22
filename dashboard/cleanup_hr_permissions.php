<?php
/**
 * Phase C: Remove orphan permissions from HR role (role_id=6)
 *
 * The HR role was previously granted permissions to ALL modules
 * (likely via sync_permissions_all_roles.php). This script removes
 * permissions for non-HR modules, keeping only the HR-approved set.
 *
 * Run: php dashboard/cleanup_hr_permissions.php
 * Safe to re-run (idempotent — uses DELETE with WHERE IN).
 */

declare(strict_types=1);

$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';

$hrRoleId = 6;

// Module slugs that HR role should keep access to
$hrModules = [
    'departments',
    'designations',
    'attendance',
    'leave_requests',
    'leave_types',
    'payroll_components',
    'salary_structures',
    'employee_salaries',
    'payroll_runs',
    'payslips',
    'user_documents',
    'users',
    'report_hr',
];

// Get module IDs for HR modules
$placeholders = implode("','", array_map(function ($s) use ($mysqli) {
    return $mysqli->real_escape_string($s);
}, $hrModules));

$result = $mysqli->query("SELECT id FROM erp_modules WHERE slug IN ('$placeholders')");
$hrModuleIds = [];
while ($row = $result->fetch_assoc()) {
    $hrModuleIds[] = (int)$row['id'];
}

if (empty($hrModuleIds)) {
    die("ERROR: No HR module IDs found in erp_modules.\n");
}

// Count current permissions for HR role
$totalResult = $mysqli->query("SELECT COUNT(*) as cnt FROM erp_permissions WHERE role_id = $hrRoleId");
$totalBefore = (int)$totalResult->fetch_assoc()['cnt'];

// Count permissions that will be kept (HR modules)
$keepIds = implode(',', $hrModuleIds);
$keepResult = $mysqli->query("SELECT COUNT(*) as cnt FROM erp_permissions WHERE role_id = $hrRoleId AND module_id IN ($keepIds)");
$keepCount = (int)$keepResult->fetch_assoc()['cnt'];

// Delete permissions for non-HR modules
$deleteResult = $mysqli->query("DELETE FROM erp_permissions WHERE role_id = $hrRoleId AND module_id NOT IN ($keepIds)");
$deletedCount = $mysqli->affected_rows;

// Verify
$afterResult = $mysqli->query("SELECT COUNT(*) as cnt FROM erp_permissions WHERE role_id = $hrRoleId");
$totalAfter = (int)$afterResult->fetch_assoc()['cnt'];

echo "=== HR Role Permission Cleanup ===\n";
echo "Role ID:          $hrRoleId\n";
echo "Modules kept:     " . count($hrModuleIds) . " (IDs: " . implode(',', $hrModuleIds) . ")\n";
echo "Permissions before: $totalBefore\n";
echo "Permissions kept:   $keepCount\n";
echo "Permissions deleted: $deletedCount\n";
echo "Permissions after:   $totalAfter\n";
echo "\n" . ($totalAfter === $keepCount ? "SUCCESS: Cleanup verified.\n" : "WARNING: Count mismatch — expected $keepCount, got $totalAfter.\n");
