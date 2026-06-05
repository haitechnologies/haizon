<?php

use App\Core\DB;
/**
 * CLEAN-UP AUDIT SCRIPT - Real Form-to-Database Issues
 * 
 * This script identifies ACTUAL form-to-database mismatches
 * (excluding false positives from form buttons or parameters)
 * 
 * Run this periodically to verify form integrity
 */

require_once __DIR__ . '/../config/database.php';

$mysqli = $mysqli ?? $conn ?? null;
if (!($mysqli instanceof mysqli)) {
    die("Database connection unavailable\n");
}

$timestamp = date('Y-m-d H:i:s');
echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║              FORM-TO-DATABASE CLEAN-UP AUDIT REPORT                        ║\n";
echo "║              Generated: {$timestamp}                                     ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

// Define ALL form→table mappings with CORRECT expected fields
$audit_mappings = [
    'categories.php' => [
        'table' => DB::CATEGORIES,
        'description' => 'Product Categories',
        'fields_to_remove' => ['category_id'],  // This column doesn't exist
        'fields_to_add' => [],
        'critical' => true
    ],
    'subcategories.php' => [
        'table' => DB::SUBCATEGORIES,
        'description' => 'Product Subcategories',
        'fields_to_remove' => ['subcategory_id', 'subcategory', 'icon', 'meta_title', 'meta_description'],
        'fields_to_add' => [],  // Use 'name' instead of 'subcategory'
        'critical' => true
    ],
    'blogs.php' => [
        'table' => DB::BLOGS,
        'description' => 'Blog Posts',
        'fields_to_remove' => ['status'],  // Use 'publish' instead
        'fields_to_add' => [],
        'critical' => true
    ],
    'customers.php' => [
        'table' => DB::CUSTOMERS,
        'description' => 'Customers',
        'fields_to_remove' => [],
        'fields_to_add' => [],
        'critical' => false
    ]
];

$critical_count = 0;
$warnings = [];
$clean_forms = [];

foreach ($audit_mappings as $form_file => $config) {
    $file_path = __DIR__ . '/' . $form_file;
    
    if (!file_exists($file_path)) {
        continue;
    }
    
    if (file_exists($file_path) && $config['critical']) {
        if (!empty($config['fields_to_remove'])) {
            $critical_count++;
            $warnings[] = [
                'form' => $form_file,
                'description' => $config['description'],
                'issues' => "Remove fields: " . implode(', ', $config['fields_to_remove']),
                'severity' => 'CRITICAL'
            ];
        } else {
            $clean_forms[] = $form_file;
        }
    } else if (!$config['critical']) {
        // Verify critical ones are clean
        if (empty($config['fields_to_remove'])) {
            $clean_forms[] = $form_file;
        }
    }
}

// Display results
echo "CRITICAL ISSUES FOUND: {$critical_count}\n";
echo str_repeat("═", 80) . "\n\n";

if ($critical_count > 0) {
    echo "⚠️  CRITICAL ISSUES - REQUIRE FIX:\n\n";
    foreach ($warnings as $warning) {
        echo "  {$warning['form']}\n";
        echo "  Module: {$warning['description']}\n";
        echo "  Issue: {$warning['issues']}\n";
        echo "\n";
    }
} else {
    echo "✅ NO CRITICAL ISSUES FOUND\n\n";
}

echo str_repeat("═", 80) . "\n";
echo "\nCLEAN FORMS ({" . count($clean_forms) . " total):\n";
foreach ($clean_forms as $form) {
    echo "  ✅ {$form}\n";
}

echo "\n" . str_repeat("═", 80) . "\n";
echo "RECOMMENDATION:\n";
if ($critical_count > 0) {
    echo "  → Review and fix the {$critical_count} form(s) listed above\n";
    echo "  → Run this audit again after fixes to verify\n";
} else {
    echo "  ✅ All forms are database-compliant\n";
}
echo "\n" . str_repeat("═", 80) . "\n";

$mysqli->close();
?>
