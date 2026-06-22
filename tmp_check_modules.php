<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'config/session.php';
require 'config/database.php';

$result = $mysqli->query("SELECT id, slug, module_name FROM erp_modules ORDER BY id");
echo "=== ALL MODULES ===\n";
while ($m = $result->fetch_assoc()) {
    printf("  %d  %-30s  %s\n", $m['id'], $m['slug'], $m['module_name']);
}
echo "Total: " . $result->num_rows . " modules\n";

echo "\n=== Search for 'statistics' ===\n";
$r2 = $mysqli->query("SELECT id, slug, module_name FROM erp_modules WHERE slug = 'statistics'");
echo "Found: " . $r2->num_rows . "\n";
