<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

$stmt = $mysqli->query("SHOW TABLES LIKE 'erp_gratuity_settlements'");
if ($stmt && $stmt->num_rows > 0) {
    echo "Table erp_gratuity_settlements already exists.\n";
    exit;
}

$sql = "CREATE TABLE erp_gratuity_settlements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    employee_id INT NOT NULL,
    total_tenure_years DECIMAL(5,2) NOT NULL DEFAULT 0,
    total_tenure_days INT NOT NULL DEFAULT 0,
    last_basic_salary DECIMAL(10,2) NOT NULL DEFAULT 0,
    gratuity_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('calculated','approved','paid','cancelled') NOT NULL DEFAULT 'calculated',
    settlement_date DATE DEFAULT NULL,
    payment_date DATE DEFAULT NULL,
    payment_reference VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    approved_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_organization_id (organization_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($mysqli->query($sql)) {
    echo "Table erp_gratuity_settlements created successfully.\n";
} else {
    echo "Error: " . $mysqli->error . "\n";
}
