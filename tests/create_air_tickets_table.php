<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

$stmt = $mysqli->query("SHOW TABLES LIKE 'erp_air_tickets'");
if ($stmt && $stmt->num_rows > 0) {
    echo "Table erp_air_tickets already exists.\n";
    $desc = $mysqli->query("DESCRIBE erp_air_tickets");
    while ($row = $desc->fetch_assoc()) {
        echo $row['Field'] . ' ' . $row['Type'] . "\n";
    }
    exit;
}

$sql = "CREATE TABLE erp_air_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    employee_id INT NOT NULL,
    entitlement_amount DECIMAL(10,2) NOT NULL DEFAULT 1250.00,
    status ENUM('pending','payable','paid','cancelled') NOT NULL DEFAULT 'pending',
    eligibility_date DATE DEFAULT NULL,
    paid_date DATE DEFAULT NULL,
    payment_reference VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_organization_id (organization_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($mysqli->query($sql)) {
    echo "Table erp_air_tickets created successfully.\n";
} else {
    echo "Error: " . $mysqli->error . "\n";
}
