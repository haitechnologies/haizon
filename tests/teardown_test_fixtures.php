<?php
/**
 * Teardown test fixtures for Haizon ERP integration tests
 */
require_once __DIR__ . '/../config/database.php';

echo "Cleaning up integration test fixtures...\n";

// Disable foreign key checks temporarily to ensure we can delete everything cleanly
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");

$orgs = [999, 9999, 881, 882];
foreach ($orgs as $id) {
    $mysqli->query("DELETE FROM erp_organizations WHERE id = $id");
}

$users = [101, 12345];
foreach ($users as $id) {
    $mysqli->query("DELETE FROM erp_users WHERE id = $id");
}

$mysqli->query("SET FOREIGN_KEY_CHECKS = 1");

echo "Fixtures cleaned up successfully!\n";
