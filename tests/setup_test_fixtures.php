<?php
/**
 * Setup test fixtures for Haizon ERP integration tests
 */
require_once __DIR__ . '/../config/database.php';

echo "Setting up integration test fixtures...\n";

// Disable foreign key checks temporarily to ensure we can setup everything cleanly
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");

// 1. Setup Test Organizations
$orgs = [
    [999, 'Test Org 999', 'test-org-999'],
    [9999, 'Test Org 9999', 'test-org-9999'],
    [881, 'Test Org 881', 'test-org-881'],
    [882, 'Test Org 882', 'test-org-882'],
];

foreach ($orgs as $org) {
    list($id, $name, $slug) = $org;
    $mysqli->query("DELETE FROM erp_organizations WHERE id = $id");
    $stmt = $mysqli->prepare("INSERT INTO erp_organizations (id, owner_user_id, status, warehouse_no, warehouse_name, slug, trn, is_active) VALUES (?, 1, 'active', ?, ?, ?, '123456789012345', 1)");
    $wh_no = 'WH-' . $id;
    $stmt->bind_param("isss", $id, $wh_no, $name, $slug);
    if (!$stmt->execute()) {
        echo "Error inserting org $id: " . $stmt->error . "\n";
    }
}

// 2. Setup Test Users
$users = [
    [101, 'test.user.101@haizon.com', 'Test User 101'],
    [12345, 'test.user.12345@haizon.com', 'Test User 12345'],
];

foreach ($users as $user) {
    list($id, $email, $name) = $user;
    $mysqli->query("DELETE FROM erp_users WHERE id = $id");
    $stmt = $mysqli->prepare("INSERT INTO erp_users (id, email, password, role_id, full_name, can_access_system, is_active, created_by) VALUES (?, ?, 'dummy_password_hash', 3, ?, 1, 1, 1)");
    $stmt->bind_param("iss", $id, $email, $name);
    if (!$stmt->execute()) {
        echo "Error inserting user $id: " . $stmt->error . "\n";
    }
}

$mysqli->query("SET FOREIGN_KEY_CHECKS = 1");

echo "Fixtures loaded successfully!\n";
