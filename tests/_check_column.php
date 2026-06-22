<?php
require_once __DIR__ . '/../config/database.php';
$r = $mysqli->query("SHOW COLUMNS FROM erp_taxonomies LIKE 'updated_by'");
echo $r->num_rows > 0 ? "EXISTS\n" : "MISSING\n";
