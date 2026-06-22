<?php
/**
 * Seed missing HR modules: annual_leave_entitlements, attendance_devices
 *
 * These modules exist in the codebase and sidebar/HR nav but were missing
 * from erp_modules. This script inserts them and grants all permissions
 * to HR role (id=6).
 *
 * Run: php dashboard/seed_missing_hr_modules.php
 * Safe to re-run (skips if slug already exists).
 */

declare(strict_types=1);

$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

$m = $GLOBALS['mysqli'];

$newModules = [
    ['annual_leave_entitlements', 'Annual Leave Entitlements', 'module'],
    ['attendance_devices', 'Attendance Devices', 'module'],
];

$permissionSlugs = ['view', 'create', 'edit', 'delete'];
$hrRoleId = 6;

$existing = [];
$r = $m->query("SELECT slug FROM erp_modules");
while ($row = $r->fetch_assoc()) {
    $existing[] = $row['slug'];
}

foreach ($newModules as [$slug, $name, $type]) {
    if (in_array($slug, $existing)) {
        echo "$slug already exists, skipping.\n";
        continue;
    }

    $m->query("INSERT INTO erp_modules (slug, module_name, module_type, publish, is_active) VALUES ('$slug', '$name', '$type', 1, 1)");
    $moduleId = $m->insert_id;
    echo "Inserted $slug → id=$moduleId\n";

    $permIds = [];
    foreach ($permissionSlugs as $ps) {
        $m->query("INSERT INTO erp_module_permissions (module_id, slug, permission_name, publish, is_active, created_at) VALUES ($moduleId, '$ps', '$ps', 1, 1, NOW())");
        $permIds[$ps] = $m->insert_id;
    }

    foreach ($permIds as $ps => $mpId) {
        $m->query("INSERT INTO erp_permissions (role_id, permission_id, module_id, publish, is_active, created_by) VALUES ($hrRoleId, $mpId, $moduleId, 1, 1, 1)");
    }
    echo "  Granted view/create/edit/delete to HR role\n";
}

echo "\nDone.\n";
