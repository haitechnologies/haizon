<?php
require_once __DIR__ . '/config/database.php';
$pdo = App\Core\DB::conn();
$prefix = App\Core\DB::getPrefix();

echo "=== module_permissions columns ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM {$prefix}module_permissions")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo json_encode($c) . "\n";

echo "\n=== Find permission types for module 161 (salary_structures) ===\n";
$rows = $pdo->query("SELECT * FROM {$prefix}module_permissions WHERE module_id=161")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo json_encode($r) . "\n";

echo "\n=== Find permission types for module 163 (payroll_runs) ===\n";
$rows = $pdo->query("SELECT * FROM {$prefix}module_permissions WHERE module_id=163")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo json_encode($r) . "\n";

echo "\n=== Check HR role (6) permissions for module 161 in erp_permissions ===\n";
$rows = $pdo->query("SELECT * FROM {$prefix}permissions WHERE role_id=6 AND module_id=161")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo json_encode($r) . "\n";

echo "\n=== Check HR role (6) permissions for module 163 in erp_permissions ===\n";
$rows = $pdo->query("SELECT * FROM {$prefix}permissions WHERE role_id=6 AND module_id=163")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo json_encode($r) . "\n";

echo "\n=== What permission_ids 565-576 map to ===\n";
$rows = $pdo->query("SELECT * FROM {$prefix}module_permissions WHERE id BETWEEN 565 AND 576")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo json_encode($r) . "\n";
