<?php
/**
 * Unified Listing Page Validator
 * 
 * Comprehensive validation for all listing pages covering:
 * 1. DataTable Configuration (AJAX, CSRF, columns, error handlers)
 * 2. Style Governance (no inline styles, no forbidden CSS overrides)
 * 
 * Run from CLI:
 *   php dashboard/validate_listing_pages.php
 * 
 * Or from browser:
 *   http://localhost/haizon/dashboard/validate_listing_pages.php
 */

// Check if CLI mode
$isCLI = (php_sapi_name() === 'cli');

// Load requirements based on mode
if ($isCLI) {
    // CLI mode: minimal bootstrapping
    require_once __DIR__ . '/../../../config/database.php';
    require_once __DIR__ . '/../../../config/globals.php';
} else {
    // Web mode: full bootstrap with security
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Require System Admin access
    if (!has_full_access()) {
        die('Access denied. System Admin only.');
    }
}

$dashboardPath = dirname(__DIR__, 2);
$listingFiles = glob($dashboardPath . '/listing_*.php');

if (!$isCLI) {
    echo "<!DOCTYPE html>\n";
    echo "<html>\n<head>\n";
    echo "<title>Listing Page Validation Report</title>\n";
    echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>\n";
    echo "<style>
        body { padding: 20px; font-family: system-ui, -apple-system; background: #f8f9fa; }
        .validation-report { max-width: 1400px; margin: auto; }
        .page-card { margin-bottom: 15px; border-left: 4px solid #ddd; background: white; }
        .page-card.success { border-left-color: #28a745; }
        .page-card.warning { border-left-color: #ffc107; }
        .page-card.error { border-left-color: #dc3545; }
        .check-item { padding: 4px 0; font-size: 0.9em; }
        .check-pass { color: #28a745; }
        .check-fail { color: #dc3545; }
        .check-skip { color: #6c757d; }
        .summary-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-box { text-align: center; padding: 15px; }
        .stat-number { font-size: 2.5em; font-weight: bold; }
        .stat-label { color: #6c757d; font-size: 0.9em; }
        .validation-section { margin-top: 30px; padding-top: 30px; border-top: 2px solid #dee2e6; }
        .section-header { margin-bottom: 20px; }
        .badge { font-size: 0.85em; }
    </style>\n";
    echo "</head>\n<body>\n";
    echo "<div class='validation-report'>\n";
    echo "<h1 class='mb-4'>ðŸ” Unified Listing Page Validation</h1>\n";
    echo "<p class='text-muted'>Generated: " . date('Y-m-d H:i:s') . " | Total Files: " . count($listingFiles) . "</p>\n";
}

// ========================================
// 1. DataTable Configuration Validator
// ========================================

$datatableResults = [];
$datatablePatterns = [
    'initializer' => '/HAIDatatableInitializer\.init/is',
    'vanilla_datatable' => '/\.DataTable\s*\(/is',
    'ajax_url' => '/ajax\s*:\s*\{[\s\S]*?url\s*:\s*[\'"](datatables\.php|datatables_dispatcher\.php)[\'"]/is',
    'ajax_type' => '/ajax\s*:\s*\{[\s\S]*?type\s*:\s*[\'"]POST[\'"]/is',
    'csrf_assign' => '/d\.csrf_token\s*=/is',
    'csrf_in_data_object' => '/ajax\s*:\s*\{[\s\S]*?data\s*:\s*\{[\s\S]*?csrf_token\s*:/is',
    'csrf_presence' => '/csrf_field\s*\(|name=[\'"]csrf_token[\'"]|HAI_CSRF_TOKEN/is',
    'ajax_data_function' => '/ajax\s*:\s*\{[\s\S]*?data\s*:\s*function\s*\(/is',
    'columns_array' => '/columns\s*:\s*\[/is',
    'column_defs' => '/columnDefs\s*:\s*\[/is',
    'has_dom' => '/\bdom\s*:/is',
    'dom_standard' => '/<\'dt-head-left\'fl><\'dt-head-right\'>/is',
    'dom_legacy' => '/<\'dt-head-left\'l><\'dt-head-right\'f>/is',
    'page_length_10' => '/pageLength\s*:\s*10\b/is'
];

$datatableSkipped = 0;
$datatablePassed = 0;
$datatableFailed = 0;

foreach ($listingFiles as $file) {
    $fileName = basename($file);
    
    // Skip examples and backups
    if (stripos($fileName, 'SECURE_EXAMPLE') !== false || stripos($fileName, '.bak') !== false) {
        $datatableSkipped++;
        $datatableResults[$fileName] = ['type' => 'skipped', 'checks' => []];
        continue;
    }
    
    $content = file_get_contents($file);

    $usesInitializer = preg_match($datatablePatterns['initializer'], $content) === 1;
    $usesVanilla = preg_match($datatablePatterns['vanilla_datatable'], $content) === 1;
    $isDataTablePage = ($usesInitializer || $usesVanilla);

    if (!$isDataTablePage) {
        $datatableSkipped++;
        $datatableResults[$fileName] = ['type' => 'skipped', 'checks' => []];
        continue;
    }

    $checks = [];
    $hasIssues = false;

    // Check AJAX configuration
    $hasAjaxUrl = (preg_match($datatablePatterns['ajax_url'], $content) === 1) || $usesInitializer;
    $hasAjaxType = (preg_match($datatablePatterns['ajax_type'], $content) === 1) || $usesInitializer;
    $hasAjaxDataFunction = preg_match($datatablePatterns['ajax_data_function'], $content) === 1;
    
    $checks['ajax_url'] = [
        'pass' => $hasAjaxUrl,
        'label' => 'AJAX URL configured'
    ];
    $checks['ajax_type'] = [
        'pass' => $hasAjaxType,
        'label' => 'AJAX type: POST'
    ];
    $checks['ajax_data_function'] = [
        'pass' => $hasAjaxDataFunction || $usesInitializer,
        'label' => 'AJAX data function'
    ];

    // Check CSRF token
    $hasCsrf = $usesInitializer
        || (preg_match($datatablePatterns['csrf_assign'], $content) === 1)
        || (preg_match($datatablePatterns['csrf_in_data_object'], $content) === 1)
        || (preg_match($datatablePatterns['csrf_presence'], $content) === 1);
    
    $checks['csrf'] = [
        'pass' => $hasCsrf,
        'label' => 'CSRF token present'
    ];

    // Check columns array or columnDefs
    $hasColumns = preg_match($datatablePatterns['columns_array'], $content) === 1;
    $hasColumnDefs = preg_match($datatablePatterns['column_defs'], $content) === 1;
    $checks['columns'] = [
        'pass' => $hasColumns || $hasColumnDefs,
        'label' => 'Columns array or columnDefs defined'
    ];

    // Check standard DataTable header pattern contract
    $hasDom = preg_match($datatablePatterns['has_dom'], $content) === 1;
    $usesStandardDom = preg_match($datatablePatterns['dom_standard'], $content) === 1;
    $usesLegacyDom = preg_match($datatablePatterns['dom_legacy'], $content) === 1;
    $domCompliant = $isReferenceCompaniesPage || !$hasDom || ($usesStandardDom && !$usesLegacyDom);

    $checks['dom_standard'] = [
        'pass' => $domCompliant,
        'label' => 'Standard header DOM (search + entries left, stats right)'
    ];

    $checks['dom_legacy_absent'] = [
        'pass' => $isReferenceCompaniesPage || !$usesLegacyDom,
        'label' => 'Legacy header DOM ordering not used'
    ];

    // Global standard requires 10 rows by default. Initializer already enforces this.
    $hasPageLength10 = $usesInitializer || (preg_match($datatablePatterns['page_length_10'], $content) === 1);
    $checks['page_length_10'] = [
        'pass' => $hasPageLength10,
        'label' => 'Default rows per page set to 10'
    ];

    // Determine overall status
    foreach ($checks as $check) {
        if (!$check['pass']) {
            $hasIssues = true;
            break;
        }
    }

    if ($hasIssues) {
        $datatableFailed++;
        $datatableResults[$fileName] = ['type' => 'failed', 'checks' => $checks];
    } else {
        $datatablePassed++;
        $datatableResults[$fileName] = ['type' => 'passed', 'checks' => $checks];
    }
}

// ========================================
// 2. Style Governance Validator
// ========================================

$styleResults = [];
$forbiddenCssPatterns = [
    '/\.dataTables_paginate\b/i',
    '/\.dt-footer\b/i',
    '/\.dt-foot-left\b/i',
    '/\.dt-foot-right\b/i',
    '/\.paginate_button\b/i',
];

$styleViolations = 0;
$stylePassed = 0;
$styleSkipped = 0;

// ========================================
// 3. Layout Structure Validator
// ========================================

$layoutResults = [];
$layoutPassed = 0;
$layoutViolations = 0;
$layoutSkipped = 0;

foreach ($listingFiles as $file) {
    $fileName = basename($file);

    if (stripos($fileName, 'SECURE_EXAMPLE') !== false || stripos($fileName, '.bak') !== false) {
        $layoutSkipped++;
        continue;
    }

    $content = file_get_contents($file);
    $issues = [];

    $hasContentWrapper = preg_match('/<div\s+class=["\']content-wrapper["\']\s*>/i', $content) === 1;
    $hasCopyrightInclude = preg_match('/include\(["\']admin_elements\/copyright\.php["\']\)\s*;/i', $content) === 1;
    $hasAdminFooterInclude = preg_match('/include\(["\']admin_elements\/admin_footer\.php["\']\)\s*;/i', $content) === 1;

    if ($hasContentWrapper && $hasCopyrightInclude && $hasAdminFooterInclude) {
        // Enforce footer contract: content-wrapper must close immediately after copyright include.
        $hasWrapperCloseAfterCopyright = preg_match(
            '/include\(["\']admin_elements\/copyright\.php["\']\)\s*;\s*\?>\s*<\/div>/is',
            $content
        ) === 1;

        if (!$hasWrapperCloseAfterCopyright) {
            $issues[] = 'Missing `</div>` for `.content-wrapper` after copyright include (can push footer/copyright into sidebar layout)';
        }
    } else {
        $layoutSkipped++;
        $layoutResults[$fileName] = ['type' => 'skipped', 'issues' => []];
        continue;
    }

    if (!empty($issues)) {
        $layoutViolations++;
        $layoutResults[$fileName] = ['type' => 'violation', 'issues' => $issues];
    } else {
        $layoutPassed++;
        $layoutResults[$fileName] = ['type' => 'passed', 'issues' => []];
    }
}

foreach ($listingFiles as $file) {
    $fileName = basename($file);
    
    // Skip examples/backups
    if (stripos($fileName, 'SECURE_EXAMPLE') !== false || stripos($fileName, '.bak') !== false) {
        $styleSkipped++;
        continue;
    }

    $content = file_get_contents($file);
    $issues = [];

    // Check for inline <style> blocks
    if (preg_match('/<style\b[^>]*>/i', $content)) {
        $issues[] = 'Inline &lt;style&gt; block found (move to dashboard-listing-pages.css)';
    }

    // Check for forbidden CSS selectors in inline styles
    if (preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $content, $matches)) {
        foreach ($matches[1] as $cssBlock) {
            foreach ($forbiddenCssPatterns as $cssPattern) {
                if (preg_match($cssPattern, $cssBlock)) {
                    $issues[] = 'Forbidden DataTables pagination/footer CSS override (must use datatables-unified.css)';
                    break 2;
                }
            }
        }
    }

    if (!empty($issues)) {
        $styleViolations++;
        $styleResults[$fileName] = ['type' => 'violation', 'issues' => $issues];
    } else {
        $stylePassed++;
        $styleResults[$fileName] = ['type' => 'passed', 'issues' => []];
    }
}

$totalChecked = count($listingFiles);
$totalIssues = $datatableFailed + $styleViolations + $layoutViolations;
$overallPass = ($totalIssues === 0);

// ========================================
// Output Report
// ========================================

if (!$isCLI) {
    // HTML Output
    echo "<div class='summary-card'>\n";
    echo "<div class='row'>\n";
    echo "<div class='col-md-3 stat-box'>\n";
    echo "<div class='stat-number text-primary'>" . $totalChecked . "</div>\n";
    echo "<div class='stat-label'>Total Files</div>\n";
    echo "</div>\n";
    echo "<div class='col-md-3 stat-box'>\n";
    echo "<div class='stat-number " . ($overallPass ? 'text-success' : 'text-danger') . "'>" . $totalIssues . "</div>\n";
    echo "<div class='stat-label'>Issues Found</div>\n";
    echo "</div>\n";
    echo "<div class='col-md-3 stat-box'>\n";
    echo "<div class='stat-number text-info'>" . ($datatablePassed + $stylePassed) . "</div>\n";
    echo "<div class='stat-label'>Passed Checks</div>\n";
    echo "</div>\n";
    echo "<div class='col-md-3 stat-box'>\n";
    echo "<div class='stat-number " . ($overallPass ? 'text-success' : 'text-warning') . "'>" . ($overallPass ? 'PASS' : 'FAIL') . "</div>\n";
    echo "<div class='stat-label'>Overall Status</div>\n";
    echo "</div>\n";
    echo "</div>\n";
    echo "</div>\n";

    // DataTable Configuration Section
    echo "<div class='validation-section'>\n";
    echo "<div class='section-header'>\n";
    echo "<h3>ðŸ“Š DataTable Configuration</h3>\n";
    echo "<p class='text-muted'>Validates AJAX setup, CSRF tokens, and column definitions</p>\n";
    echo "<div class='mb-3'>\n";
    echo "<span class='badge bg-success'>Passed: $datatablePassed</span> ";
    echo "<span class='badge bg-danger'>Failed: $datatableFailed</span> ";
    echo "<span class='badge bg-secondary'>Skipped: $datatableSkipped</span>\n";
    echo "</div>\n";
    echo "</div>\n";

    if ($datatableFailed > 0) {
        foreach ($datatableResults as $fileName => $result) {
            if ($result['type'] === 'failed') {
                echo "<div class='card page-card error'>\n";
                echo "<div class='card-body'>\n";
                echo "<h5 class='card-title'>$fileName</h5>\n";
                foreach ($result['checks'] as $check) {
                    $class = $check['pass'] ? 'check-pass' : 'check-fail';
                    $icon = $check['pass'] ? 'âœ“' : 'âœ—';
                    echo "<div class='check-item $class'>$icon {$check['label']}</div>\n";
                }
                echo "</div>\n";
                echo "</div>\n";
            }
        }
    } else {
        echo "<div class='alert alert-success'>âœ“ All DataTable pages have proper configuration</div>\n";
    }
    echo "</div>\n";

    // Style Governance Section
    echo "<div class='validation-section'>\n";
    echo "<div class='section-header'>\n";
    echo "<h3>ðŸŽ¨ Style Governance</h3>\n";
    echo "<p class='text-muted'>Validates centralized CSS architecture and prevents style drift</p>\n";
    echo "<div class='mb-3'>\n";
    echo "<span class='badge bg-success'>Passed: $stylePassed</span> ";
    echo "<span class='badge bg-danger'>Violations: $styleViolations</span> ";
    echo "<span class='badge bg-secondary'>Skipped: $styleSkipped</span>\n";
    echo "</div>\n";
    echo "</div>\n";

    if ($styleViolations > 0) {
        foreach ($styleResults as $fileName => $result) {
            if ($result['type'] === 'violation') {
                echo "<div class='card page-card error'>\n";
                echo "<div class='card-body'>\n";
                echo "<h5 class='card-title'>$fileName</h5>\n";
                foreach ($result['issues'] as $issue) {
                    echo "<div class='check-item check-fail'>âœ— $issue</div>\n";
                }
                echo "</div>\n";
                echo "</div>\n";
            }
        }
    } else {
        echo "<div class='alert alert-success'>âœ“ All listing pages follow centralized CSS architecture</div>\n";
    }
    echo "</div>\n";

    // Layout Structure Section
    echo "<div class='validation-section'>\n";
    echo "<div class='section-header'>\n";
    echo "<h3>ðŸ§± Layout Structure</h3>\n";
    echo "<p class='text-muted'>Validates listing footer contract to prevent sidebar/footer layout drift</p>\n";
    echo "<div class='mb-3'>\n";
    echo "<span class='badge bg-success'>Passed: $layoutPassed</span> ";
    echo "<span class='badge bg-danger'>Violations: $layoutViolations</span> ";
    echo "<span class='badge bg-secondary'>Skipped: $layoutSkipped</span>\n";
    echo "</div>\n";
    echo "</div>\n";

    if ($layoutViolations > 0) {
        foreach ($layoutResults as $fileName => $result) {
            if ($result['type'] === 'violation') {
                echo "<div class='card page-card error'>\n";
                echo "<div class='card-body'>\n";
                echo "<h5 class='card-title'>$fileName</h5>\n";
                foreach ($result['issues'] as $issue) {
                    echo "<div class='check-item check-fail'>âœ— $issue</div>\n";
                }
                echo "</div>\n";
                echo "</div>\n";
            }
        }
    } else {
        echo "<div class='alert alert-success'>âœ“ All listing pages follow footer/layout closure contract</div>\n";
    }
    echo "</div>\n";

    echo "</div>\n</body>\n</html>\n";

} else {
    // CLI Output
    echo "\n";
    echo "â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n";
    echo "  UNIFIED LISTING PAGE VALIDATION REPORT\n";
    echo "â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n";
    echo "\n";
    echo "Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "Total Files: $totalChecked\n";
    echo "\n";

    echo "ðŸ“Š DataTable Configuration:\n";
    echo "   Passed:  $datatablePassed\n";
    echo "   Failed:  $datatableFailed\n";
    echo "   Skipped: $datatableSkipped\n";
    echo "\n";

    echo "ðŸŽ¨ Style Governance:\n";
    echo "   Passed:     $stylePassed\n";
    echo "   Violations: $styleViolations\n";
    echo "   Skipped:    $styleSkipped\n";
    echo "\n";

    echo "ðŸ§± Layout Structure:\n";
    echo "   Passed:     $layoutPassed\n";
    echo "   Violations: $layoutViolations\n";
    echo "   Skipped:    $layoutSkipped\n";
    echo "\n";

    if ($totalIssues > 0) {
        echo "âŒ OVERALL STATUS: FAIL ($totalIssues issue(s) found)\n\n";

        if ($datatableFailed > 0) {
            echo "DataTable Configuration Issues:\n";
            echo "â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\n";
            foreach ($datatableResults as $fileName => $result) {
                if ($result['type'] === 'failed') {
                    echo "â€¢ $fileName\n";
                    foreach ($result['checks'] as $check) {
                        if (!$check['pass']) {
                            echo "  âœ— {$check['label']}\n";
                        }
                    }
                }
            }
            echo "\n";
        }

        if ($styleViolations > 0) {
            echo "Style Governance Violations:\n";
            echo "â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\n";
            foreach ($styleResults as $fileName => $result) {
                if ($result['type'] === 'violation') {
                    echo "â€¢ $fileName\n";
                    foreach ($result['issues'] as $issue) {
                        echo "  âœ— " . strip_tags($issue) . "\n";
                    }
                }
            }
            echo "\n";
        }

        if ($layoutViolations > 0) {
            echo "Layout Structure Violations:\n";
            echo "â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\n";
            foreach ($layoutResults as $fileName => $result) {
                if ($result['type'] === 'violation') {
                    echo "â€¢ $fileName\n";
                    foreach ($result['issues'] as $issue) {
                        echo "  âœ— " . strip_tags($issue) . "\n";
                    }
                }
            }
            echo "\n";
        }

        exit(1);
    } else {
        echo "âœ… OVERALL STATUS: PASS\n";
        echo "\nAll listing pages validated successfully:\n";
        echo "â€¢ DataTable configurations are correct\n";
        echo "â€¢ Style governance rules followed\n";
        echo "â€¢ Footer/layout closure contract enforced\n";
        echo "â€¢ CSS architecture centralized\n";
        echo "\n";
        exit(0);
    }
}

