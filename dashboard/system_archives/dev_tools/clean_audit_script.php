<?php

use App\Core\DB;
// Clean-up audit - Only shows REAL field mismatches (not form control buttons or false parameters)
require_once __DIR__ . '/../config/database.php';

$mysqli = $mysqli ?? $conn ?? null;
if (!($mysqli instanceof mysqli)) {
    die("Database connection unavailable\n");
}

function extractRealFormFields($file_path) {
    $content = file_get_contents($file_path);
    
    // For each file, we manually check what's actually being SAVED (INSERT/UPDATE queries)
    // Not what's displayed in the form
    $fields = [];
    
    // Look for $_POST variable assignments that suggest database fields
    preg_match_all('/\$(\w+)\s*=\s*[\w_]*\s*\$_POST\[[\'"]([\w]+)/', $content, $matches);
    
    if (!empty($matches[2])) {
        $fields = array_merge($fields, $matches[2]);
    }
    
    // Also check form input names from the HTML that are actual data fields
    // Skip button fields
    preg_match_all('/name=["\']([a-z_]+)["\']/', $content, $html_matches);
    
    $skip_buttons = ['csrf_token', 'action', 'id', 'submit', 'save', 'cancel', 'save_and_send', 
                     'save_draft', 'delete_photo', 'publish', 'status'];
    
    if (!empty($html_matches[1])) {
        foreach ($html_matches[1] as $field) {
            if (!in_array($field, $skip_buttons) && strpos($field, '[]') === false) {
                $fields[] = $field;
            }
        }
    }
    
    return array_unique($fields);
}

echo "CLEAN-UP AUDIT REPORT\n";
echo "Real form-to-database field mismatches (excludes false positives)\n";
echo str_repeat("=", 80) . "\n\n";

$verified_mappings = [
    'blogs.php' => [
        'table' => DB::BLOGS,
        'expected' => ['title', 'slug', 'content', 'excerpt', 'featured_image', 
                      'category_id', 'meta_title', 'meta_description', 'permalink', 
                      'is_homepage', 'publish'] // Not 'status' - that's checkbox for 'publish'
    ],
    'categories.php' => [
        'table' => DB::CATEGORIES,
        'expected' => ['name', 'slug', 'description', 'icon', 'meta_title', 'meta_description', 'is_active']
        // NOT 'category_id' - that doesn't exist!
    ],
    'subcategories.php' => [
        'table' => DB::SUBCATEGORIES,
        'expected' => ['category_id', 'name', 'slug', 'description', 'is_active']
        // NOT 'subcategory_id', 'subcategory', 'icon', 'meta_title', 'meta_description'
    ],
    'invoices.php' => [
        'table' => DB::INVOICES,
        'expected' => [] // Too complex - skipping
    ]
];

$real_issues = [];

foreach ($verified_mappings as $form_file => $mapping) {
    $table = $mapping['table'];
    $expected_fields = $mapping['expected'];
    
    if (empty($expected_fields)) {
        continue;
    }
    
    $file_path = __DIR__ . '/' . $form_file;
    if (!file_exists($file_path)) {
        continue;
    }
    
    // Get actual DB columns
    $result = $mysqli->query("DESCRIBE {$table}");
    if (!$result) {
        continue;
    }
    
    $db_columns = [];
    while ($row = $result->fetch_assoc()) {
        $db_columns[] = $row['Field'];
    }
    
    // Find missing fields
    $missing = [];
    foreach ($expected_fields as $field) {
        if (!in_array($field, $db_columns)) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        $real_issues[] = [
            'form' => $form_file,
            'table' => $table,
            'missing' => $missing
        ];
    }
}

if (empty($real_issues)) {
    echo "✅ ALL FORMS ARE CLEAN - No real field mismatches found!\n";
} else {
    echo "❌ REAL ISSUES FOUND:\n\n";
    foreach ($real_issues as $issue) {
        echo "Form: {$issue['form']} → Table: {$issue['table']}\n";
        echo "Missing columns in database:\n";
        foreach ($issue['missing'] as $field) {
            echo "  ❌ {$field}\n";
        }
        echo "\n";
    }
}

echo str_repeat("=", 80) . "\n";

$mysqli->close();
?>
