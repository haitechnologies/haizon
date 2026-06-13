#!/usr/bin/env php
<?php
/**
 * PHASE 8A: Organization ID Compliance Audit
 *
 * Scans all dashboard pages for SQL queries on org-scoped tables
 * and verifies they include organization_id filtering
 *
 * Run: php audit-org-id-compliance.php
 */

// Tables with organization_id column
$orgScopedTables = [
    'customers' => 'erp_customers',
    'contacts' => 'erp_contacts',
    'addresses' => 'erp_addresses',
    'entity_notes' => 'erp_entity_notes',
    'entity_logs' => 'erp_entity_logs',
    'invoices' => 'erp_invoices',
    'invoice_items' => 'erp_invoice_items',
    'departments' => 'erp_departments',
    'designations' => 'erp_designations',
    'attendance' => 'erp_attendance',
    'leave_requests' => 'erp_leave_requests',
    'leave_types' => 'erp_leave_types',
    'payroll_components' => 'erp_payroll_components',
    'salary_structures' => 'erp_salary_structures',
    'employee_salaries' => 'erp_employee_salaries',
    'payroll_runs' => 'erp_payroll_runs',
    'payslips' => 'erp_payslips',
    'shipping_customers' => 'erp_customers',
    'shipping_advices' => 'erp_shipping_advices',
    'shipping_advice_items' => 'erp_shipping_advice_items',
    'shipping_invoices' => 'erp_shipping_invoices',
    'shipping_invoice_items' => 'erp_shipping_invoice_items',
    'shipping_stocks' => 'erp_shipping_stocks',
    'carriers' => 'erp_carriers',
    'consignees' => 'erp_consignees',
    'shippers' => 'erp_shippers',
    'setup_sources' => 'erp_setup_sources',
    'setup_statuses' => 'erp_setup_statuses',
    'setup_tags' => 'erp_setup_tags',
    'items' => 'erp_items',
    'ports' => 'erp_ports',
];

$dashboardDir = __DIR__;
$results = [
    'compliant' => [],
    'potential_issues' => [],
    'datatable_handlers' => []
];

// Scan all .php files in dashboard
$files = glob($dashboardDir . '/*.php');

foreach ($files as $file) {
    $filename = basename($file);

    // Skip utility files
    if (in_array($filename, ['datatables_dispatcher.php', 'bootstrap.php', 'PHASE-7B-QUERY-TEMPLATE.php'])) {
        continue;
    }

    $content = file_get_contents($file);

    // Skip files without database queries
    if (!preg_match('/mysqli|DB::|query|prepare|execute/i', $content)) {
        continue;
    }

    $hasIssues = false;
    $issues = [];

    // Check for queries on org-scoped tables without org_id filter
    foreach ($orgScopedTables as $shortName => $fullName) {
        // Look for SELECT queries
        if (preg_match('/SELECT\s+.*?\s+FROM\s+.*?' . preg_quote($fullName) . '.*?WHERE/is', $content)) {
            // Check if organization_id is mentioned
            $selectBlock = preg_match('/SELECT\s+.*?\s+FROM\s+.*?' . preg_quote($fullName) . '.*?(?:WHERE|;|$)/is', $content, $m) ? $m[0] : '';

            if (!empty($selectBlock) && !preg_match('/organization_id|org_id|org\.id/i', $selectBlock)) {
                $hasIssues = true;
                $issues[] = "SELECT on $fullName without org_id filter";
            }
        }

        // Look for DELETE queries
        if (preg_match('/DELETE\s+FROM\s+.*?' . preg_quote($fullName) . '/i', $content)) {
            if (!preg_match('/organization_id|org_id/i', $content)) {
                $hasIssues = true;
                $issues[] = "DELETE on $fullName without org_id check";
            }
        }
    }

    // Check if uses DataTable (which auto-filters)
    $usesDataTable = preg_match('/DataTable|datatables_dispatcher/i', $content);

    if ($usesDataTable) {
        $results['datatable_handlers'][] = $filename;
    } elseif ($hasIssues) {
        $results['potential_issues'][$filename] = $issues;
    } else {
        $results['compliant'][] = $filename;
    }
}

// Output report
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║ PHASE 8A: Organization ID Compliance Audit Report               ║\n";
echo "║ Scanned " . count($files) . " dashboard pages                          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "✓ COMPLIANT PAGES (" . count($results['compliant']) . "):\n";
if (empty($results['compliant'])) {
    echo "  (None without queries)\n";
} else {
    foreach ($results['compliant'] as $page) {
        echo "  ✓ $page\n";
    }
}

echo "\n📊 DATATABLES HANDLERS (" . count($results['datatable_handlers']) . " - auto-filtered):\n";
foreach ($results['datatable_handlers'] as $page) {
    echo "  ⚙ $page (auto-org-filtered via BaseDataTable)\n";
}

echo "\n⚠ POTENTIAL ISSUES (" . count($results['potential_issues']) . "):\n";
if (empty($results['potential_issues'])) {
    echo "  ✓ No potential org_id compliance issues detected!\n";
} else {
    foreach ($results['potential_issues'] as $page => $issues) {
        echo "\n  ⚠ $page:\n";
        foreach ($issues as $issue) {
            echo "    - $issue\n";
        }
    }
}

echo "\n" . str_repeat("═", 64) . "\n";
echo "Total Scanned: " . count($files) . " pages\n";
echo "Compliant: " . count($results['compliant']) . "\n";
echo "DataTable (auto-filtered): " . count($results['datatable_handlers']) . "\n";
echo "Issues: " . count($results['potential_issues']) . "\n";
echo "Status: " . (count($results['potential_issues']) === 0 ? "✓ COMPLIANT" : "⚠ REVIEW NEEDED") . "\n";
echo str_repeat("═", 64) . "\n";
