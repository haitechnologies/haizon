<?php
$db = new PDO('mysql:host=localhost;dbname=haizon', 'root', 'hai@30');
$stmt = $db->query('DESCRIBE erp_leave_requests');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $db->query('DESCRIBE erp_leave_types');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
