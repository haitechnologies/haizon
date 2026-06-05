<?php
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';
$db = new \App\Core\Database();
$fields = array_map(fn($c) => $c['Field'], $db->fetchAll('DESCRIBE erp_invoices'));
print_r($fields);
