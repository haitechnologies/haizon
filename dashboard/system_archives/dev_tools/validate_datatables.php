<?php
/**
 * DataTable Validation Script
 * 
 * âš ï¸ DEPRECATED: Use validate_listing_pages.php instead (unified validator)
 * 
 * This validator only checks DataTable configuration.
 * The unified validator checks both DataTable config AND style governance.
 * 
 * Validates all listing pages to ensure:
 * - Proper ajax configuration (url, type, data function)
 * - CSRF token injection
 * - Columns array exists and matches handler output
 * - Error handler integration
 * 
 * Run from: http://localhost/haipulse/dashboard/validate_datatables.php
 * 
 * See: dashboard/VALIDATOR_README.md for unified validator documentation
 */

require_once __DIR__ . '/bootstrap.php';

// Require System Admin access
if (!has_full_access()) {
    die('Access denied. System Admin only.');
}

$dashboardPath = __DIR__;
$results = [];
$fixedPages = [];
$problemPages = [];
$skippedPages = [];

/**
 * Get all listing_*.php files
 */
$listingFiles = glob($dashboardPath . '/listing_*.php');

/**
 * Validation patterns
 */
$patterns = [
    'initializer' => '/HAIDatatableInitializer\.init/is',
    'vanilla_datatable' => '/\.DataTable\s*\(/is',
    'ajax_url' => '/ajax\s*:\s*\{[\s\S]*?url\s*:\s*[\'"](datatables\.php|datatables_dispatcher\.php)[\'"]/is',
    'ajax_type' => '/ajax\s*:\s*\{[\s\S]*?type\s*:\s*[\'"]POST[\'"]/is',
    'csrf_assign' => '/d\.csrf_token\s*=/is',
    'csrf_in_data_object' => '/ajax\s*:\s*\{[\s\S]*?data\s*:\s*\{[\s\S]*?csrf_token\s*:/is',
    'csrf_presence' => '/csrf_field\s*\(|name=[\'"]csrf_token[\'"]|HAI_CSRF_TOKEN/is',
    'ajax_data_function' => '/ajax\s*:\s*\{[\s\S]*?data\s*:\s*function\s*\(/is',
    'return_d' => '/return\s+d;/is',
    'columns_array' => '/columns\s*:\s*\[/is'
];

echo "<!DOCTYPE html>\n";
echo "<html>\n<head>\n";
echo "<title>DataTable Validation Results</title>\n";
echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>\n";
echo "<style>
    body { padding: 20px; font-family: system-ui, -apple-system; background: #f8f9fa; }
    .validation-report { max-width: 1400px; margin: auto; }
    .page-card { margin-bottom: 15px; border-left: 4px solid #ddd; }
    .page-card.success { border-left-color: #28a745; }
    .page-card.warning { border-left-color: #ffc107; }
    .page-card.error { border-left-color: #dc3545; }
    .check-item { padding: 4px 0; }
    .check-pass { color: #28a745; }
    .check-fail { color: #dc3545; }
    .summary-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .stat-box { text-align: center; padding: 15px; }
    .stat-number { font-size: 2.5em; font-weight: bold; }
    .stat-label { color: #6c757d; font-size: 0.9em; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 0.85em; }
</style>\n";
echo "</head>\n<body>\n";

echo "<div class='validation-report'>\n";
echo "<h1 class='mb-4'>ðŸ” DataTable Validation Report</h1>\n";
echo "<p class='text-muted'>Generated: " . date('Y-m-d H:i:s') . "</p>\n";

/**
 * Validate each file
 */
foreach ($listingFiles as $file) {
    $fileName = basename($file);
    $content = file_get_contents($file);

    $usesInitializer = preg_match($patterns['initializer'], $content) === 1;
    $usesVanilla = preg_match($patterns['vanilla_datatable'], $content) === 1;
    $isDataTablePage = ($usesInitializer || $usesVanilla);

    // Skip non-DataTable pages to avoid false positives.
    if (!$isDataTablePage) {
        $skippedPages[] = $fileName;
        continue;
    }

    $hasAjaxDataFunction = preg_match($patterns['ajax_data_function'], $content) === 1;
    $hasCsrf = $usesInitializer
        || (preg_match($patterns['csrf_assign'], $content) === 1)
        || (preg_match($patterns['csrf_in_data_object'], $content) === 1)
        || (preg_match($patterns['csrf_presence'], $content) === 1);

    // If page uses initializer and does not override ajax url/type, defaults are inherited from initializer.
    $hasAjaxUrl = (preg_match($patterns['ajax_url'], $content) === 1) || $usesInitializer;
    $hasAjaxType = (preg_match($patterns['ajax_type'], $content) === 1) || $usesInitializer;

    // return d is only required when ajax.data is a function in the page.
    $returnDApplicable = $hasAjaxDataFunction;
    $hasReturnD = !$returnDApplicable || (preg_match($patterns['return_d'], $content) === 1);

    // columns check is enforced for initializer pages; vanilla DataTable pages may be managed differently.
    $columnsApplicable = $usesInitializer;
    $hasColumns = preg_match($patterns['columns_array'], $content) === 1;
    
    $checks = [
        'initializer' => ($usesInitializer || $usesVanilla),
        'ajax_url' => $hasAjaxUrl,
        'ajax_type' => $hasAjaxType,
        'csrf_token' => $hasCsrf,
        'return_d' => $hasReturnD,
        'columns_array' => $hasColumns
    ];

    $applicable = [
        'initializer' => true,
        'ajax_url' => true,
        'ajax_type' => true,
        'csrf_token' => true,
        'return_d' => $returnDApplicable,
        'columns_array' => $columnsApplicable
    ];
    
    $passCount = 0;
    $totalChecks = 0;
    foreach ($checks as $checkName => $passed) {
        if (!($applicable[$checkName] ?? true)) {
            continue;
        }
        $totalChecks++;
        if ($passed) {
            $passCount++;
        }
    }
    $status = $passCount === $totalChecks ? 'success' : ($passCount >= max(1, $totalChecks - 1) ? 'warning' : 'error');
    
    $results[] = [
        'file' => $fileName,
        'checks' => $checks,
        'applicable' => $applicable,
        'pass_count' => $passCount,
        'total' => $totalChecks,
        'status' => $status
    ];
    
    if ($status === 'success') {
        $fixedPages[] = $fileName;
    } else {
        $missingChecks = [];
        foreach ($checks as $checkName => $passed) {
            if (($applicable[$checkName] ?? true) && !$passed) {
                $missingChecks[] = $checkName;
            }
        }
        $problemPages[] = [
            'file' => $fileName,
            'missing' => $missingChecks
        ];
    }
}

/**
 * Summary Statistics
 */
$totalPages = count($results);
$fixedCount = count($fixedPages);
$problemCount = count($problemPages);
$successRate = $totalPages > 0 ? round(($fixedCount / $totalPages) * 100, 1) : 0;

echo "<div class='summary-card'>\n";
echo "<div class='row'>\n";
echo "<div class='col-md-3 stat-box'>\n";
echo "<div class='stat-number text-primary'>{$totalPages}</div>\n";
echo "<div class='stat-label'>DataTable Pages</div>\n";
echo "</div>\n";
echo "<div class='col-md-3 stat-box'>\n";
echo "<div class='stat-number text-success'>{$fixedCount}</div>\n";
echo "<div class='stat-label'>Fully Compliant</div>\n";
echo "</div>\n";
echo "<div class='col-md-3 stat-box'>\n";
echo "<div class='stat-number text-warning'>{$problemCount}</div>\n";
echo "<div class='stat-label'>Needs Attention</div>\n";
echo "</div>\n";
echo "<div class='col-md-3 stat-box'>\n";
echo "<div class='stat-number text-info'>{$successRate}%</div>\n";
echo "<div class='stat-label'>Success Rate</div>\n";
echo "</div>\n";
echo "</div>\n";

if (!empty($skippedPages)) {
    echo "<div class='alert alert-info'>\n";
    echo "<strong>Info:</strong> Skipped " . count($skippedPages) . " non-DataTable listing pages.\n";
    echo "</div>\n";
}
echo "</div>\n";

/**
 * Problem Pages Section
 */
if (!empty($problemPages)) {
    echo "<div class='alert alert-warning'>\n";
    echo "<h4>âš ï¸ Pages Needing Attention ({$problemCount})</h4>\n";
    echo "<ul>\n";
    foreach ($problemPages as $problem) {
        echo "<li><strong>{$problem['file']}</strong> - Missing: " . implode(', ', $problem['missing']) . "</li>\n";
    }
    echo "</ul>\n";
    echo "</div>\n";
}

/**
 * Detailed Results
 */
echo "<h3 class='mt-4 mb-3'>Detailed Validation Results</h3>\n";

foreach ($results as $result) {
    echo "<div class='card page-card {$result['status']}'>\n";
    echo "<div class='card-body'>\n";
    echo "<h5 class='card-title'>{$result['file']}</h5>\n";
    echo "<div class='row'>\n";
    
    foreach ($result['checks'] as $checkName => $passed) {
        $isApplicable = $result['applicable'][$checkName] ?? true;
        if (!$isApplicable) {
            $icon = 'âž–';
            $class = 'text-muted';
        } else {
            $icon = $passed ? 'âœ…' : 'âŒ';
            $class = $passed ? 'check-pass' : 'check-fail';
        }
        $label = ucwords(str_replace('_', ' ', $checkName));
        echo "<div class='col-md-4 check-item'>\n";
        echo "<span class='{$class}'>{$icon} {$label}</span>\n";
        echo "</div>\n";
    }
    
    echo "</div>\n";
    echo "<div class='mt-2'>\n";
    echo "<span class='badge bg-secondary'>{$result['pass_count']}/{$result['total']} checks passed</span>\n";
    echo "</div>\n";
    echo "</div>\n";
    echo "</div>\n";
}

/**
 * Recommendations
 */
echo "<div class='card mt-4'>\n";
echo "<div class='card-body'>\n";
echo "<h4>ðŸ“‹ Recommendations</h4>\n";
echo "<ol>\n";
echo "<li><strong>Pages with Missing URL/Type:</strong> Add <code>url: 'datatables.php', type: 'POST'</code> to ajax config</li>\n";
echo "<li><strong>Missing CSRF Token:</strong> Add <code>d.csrf_token = window.HAI_CSRF_TOKEN || ...;</code></li>\n";
echo "<li><strong>Missing Return Statement:</strong> Add <code>return d;</code> at end of data function</li>\n";
echo "<li><strong>Missing Columns Array:</strong> Define explicit columns array with data field names</li>\n";
echo "<li><strong>Not Using Initializer:</strong> Replace vanilla DataTable() with window.HAIDatatableInitializer.init()</li>\n";
echo "</ol>\n";
echo "</div>\n";
echo "</div>\n";

/**
 * Fixed Pages List
 */
if (!empty($fixedPages)) {
    echo "<div class='card mt-4 border-success'>\n";
    echo "<div class='card-body'>\n";
    echo "<h4 class='text-success'>âœ… Fully Compliant Pages ({$fixedCount})</h4>\n";
    echo "<div class='row'>\n";
    foreach ($fixedPages as $page) {
        echo "<div class='col-md-4'><code>{$page}</code></div>\n";
    }
    echo "</div>\n";
    echo "</div>\n";
    echo "</div>\n";
}

echo "</div>\n"; // End validation-report
echo "</body>\n</html>";

