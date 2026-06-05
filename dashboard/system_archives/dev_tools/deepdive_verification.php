<?php
// Deep-dive verification of form fields vs actual DB columns
require_once __DIR__ . '/../config/database.php';

$mysqli = $mysqli ?? $conn ?? null;
if (!($mysqli instanceof mysqli)) {
    die("Database connection unavailable\n");
}

$forms = [
    'invoices.php' => DB::INVOICES,
    'blogs.php' => DB::BLOGS,
    'categories.php' => DB::CATEGORIES,
    'subcategories.php' => DB::SUBCATEGORIES,
];

echo "DEEP-DIVE VERIFICATION - Form Fields vs Database Columns\n";
echo str_repeat("=", 80) . "\n\n";

foreach ($forms as $form_file => $table) {
    echo "FORM: {$form_file} → TABLE: {$table}\n";
    echo str_repeat("-", 80) . "\n";
    
    // Get all DB columns
    $result = $mysqli->query("DESCRIBE {$table}");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    echo "Available columns in database ({$table}):\n";
    sort($columns);
    foreach ($columns as $col) {
        echo "  ✓ {$col}\n";
    }
    
    echo "\n";
}

echo str_repeat("=", 80) . "\n";

$mysqli->close();
?>
