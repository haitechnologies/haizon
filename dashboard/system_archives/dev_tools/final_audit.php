<?php

use App\Core\DB;
// Final corrected audit using correct table mappings
require_once __DIR__ . '/../config/database.php';

$mysqli = $mysqli ?? $conn ?? null;
if (!($mysqli instanceof mysqli)) {
    die("Database connection unavailable\n");
}

function extractFormFields($file_path) {
    $content = file_get_contents($file_path);
    $fields = [];
    
    preg_match_all('/name=["\']([a-zA-Z0-9_\[\]]+)["\']/', $content, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $field) {
            // Skip form control fields, not database fields
            $skip_words = ['csrf_token', 'action', 'id', 'submit', 'save_and_send', 'save_draft', 
                          'cancel', 'delete_photo', 'save', 'update', 'save_draft_text'];
            
            if (strpos($field, '[]') !== false || in_array($field, $skip_words)) {
                continue;
            }
            
            $fields[] = $field;
        }
    }
    
    return array_unique($fields);
}

echo "FINAL CORRECTED CRUD FORM AUDIT\n";
echo str_repeat("=", 70) . "\n\n";

// Manual correct form-to-table mappings based on code inspection
$mappings = [
    'customers.php' => DB::CUSTOMERS,
    'invoices.php' => DB::INVOICES,
    'blogs.php' => DB::BLOGS,
    'categories.php' => DB::CATEGORIES,
    'subcategories.php' => DB::SUBCATEGORIES,
    'items.php' => DB::ITEMS,
    'email_templates.php' => DB::EMAIL_TEMPLATES,
    'email_targets.php' => DB::EMAIL_TARGETS,
];

$issues = [];
$clean_count = 0;

foreach ($mappings as $form_file => $table) {
    $file_path = __DIR__ . '/' . $form_file;
    
    if (!file_exists($file_path)) {
        continue;
    }
    
    if (!$mysqli->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='haipulse' AND TABLE_NAME='{$table}' LIMIT 1")) {
        continue;
    }
    
    $form_fields = extractFormFields($file_path);
    
    if (empty($form_fields)) {
        continue;
    }
    
    // Get DB columns
    $result = $mysqli->query("DESCRIBE {$table}");
    $db_columns = [];
    while ($row = $result->fetch_assoc()) {
        $db_columns[] = $row['Field'];
    }
    
    // Find problems
    $missing = array_diff($form_fields, $db_columns);
    
    if (!empty($missing)) {
        $issues[] = [
            'form' => $form_file,
            'table' => $table,
            'missing' => $missing,
            'field_count' => count($form_fields),
            'db_count' => count($db_columns)
        ];
    } else {
        $clean_count++;
    }
}

echo "AUDIT RESULTS\n";
echo str_repeat("-", 70) . "\n";
echo "Total forms checked: " . (count($mappings)) . "\n";
echo "Clean forms (no issues): {$clean_count}\n";
echo "Forms with issues: " . count($issues) . "\n\n";

if (empty($issues)) {
    echo "âœ… ALL CHECKED FORMS ARE COMPLIANT!\n";
} else {
    echo "âš ï¸  FORMS NEEDING ATTENTION:\n\n";
    foreach ($issues as $issue) {
        echo "ðŸ“‹ " . $issue['form'] . "\n";
        echo "   Table: " . $issue['table'] . "\n";
        echo "   Form fields: " . $issue['field_count'] . " | DB columns: " . $issue['db_count'] . "\n";
        echo "   Missing DB columns:\n";
        foreach ($issue['missing'] as $field) {
            echo "      - {$field}\n";
        }
        echo "\n";
    }
}

echo str_repeat("=", 70) . "\n";

$mysqli->close();
?>

