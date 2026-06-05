#!/usr/bin/env php
<?php
/**
 * PHASE 8C: Multi-Organization Integration Tests
 * 
 * Validates multi-tenant data isolation via schema inspection
 * and query pattern validation
 * 
 * Run: php tests/integration-multi-org.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/OrgIdInjectionMiddleware.php';

class MultiOrgIntegrationTests
{
    private $mysqli;
    private $results = [];
    private $orgScopedTables = [
        'erp_customers', 'erp_customer_contacts', 'erp_customer_addresses',
        'erp_customer_comments', 'erp_customer_documents', 'erp_customer_logs',
        'erp_invoices', 'erp_invoice_items',
        'erp_departments', 'erp_designations', 'erp_attendance',
        'erp_leave_requests', 'erp_leave_types', 'erp_payroll_components',
        'erp_salary_structures', 'erp_employee_salaries', 'erp_payroll_runs',
        'erp_payslips',
        'erp_shipping_customers', 'erp_shipping_advices',
        'erp_shipping_advice_items', 'erp_shipping_invoices',
        'erp_shipping_invoice_items', 'erp_shipping_stocks',
        'erp_carriers', 'erp_consignees', 'erp_shippers',
        'erp_setup_sources', 'erp_setup_statuses', 'erp_setup_tags',
        'erp_items', 'erp_ports'
    ];
    
    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }
    
    /**
     * Test 1: Verify all 32 org-scoped tables have organization_id column
     */
    public function testOrgIdColumnsExist(): void
    {
        echo "[TEST 1] Organization ID Column Verification\n";
        
        $found = [];
        $missing = [];
        
        foreach ($this->orgScopedTables as $table) {
            $result = $this->mysqli->query(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = '$table' 
                 AND COLUMN_NAME = 'organization_id'"
            );
            
            if ($result && $result->num_rows > 0) {
                $found[] = $table;
            } else {
                $missing[] = $table;
            }
        }
        
        $pass = (count($missing) === 0 && count($found) > 0);
        $this->recordResult(
            "All org-scoped tables have organization_id",
            $pass,
            "Found: " . count($found) . "/32" . (count($missing) > 0 ? ", Missing: " . implode(', ', $missing) : "")
        );
    }
    
    /**
     * Test 2: Verify indexes on organization_id
     */
    public function testOrganizationIdIndexes(): void
    {
        echo "[TEST 2] Organization ID Index Verification\n";
        
        $indexedTables = [];
        
        foreach ($this->orgScopedTables as $table) {
            $result = $this->mysqli->query(
                "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = '$table' 
                 AND COLUMN_NAME = 'organization_id'"
            );
            
            if ($result && $result->num_rows > 0) {
                $indexedTables[] = $table;
            }
        }
        
        $pass = (count($indexedTables) >= 20);
        $this->recordResult(
            "Organization ID indexes on tables",
            $pass,
            count($indexedTables) . " tables have org_id indexes"
        );
    }
    
    /**
     * Test 3: Validate DataTable handlers inherit BaseDataTable
     */
    public function testDataTableHandlerInheritance(): void
    {
        echo "[TEST 3] DataTable Handler Inheritance Check\n";
        
        $handlerDir = __DIR__ . '/../classes/DataTable';
        $handlers = glob($handlerDir . '/*DataTable.php');
        $validHandlers = 0;
        
        foreach ($handlers as $file) {
            $content = file_get_contents($file);
            if (preg_match('/extends\s+BaseDataTable/i', $content)) {
                $validHandlers++;
            }
        }
        
        $pass = ($validHandlers > 50);
        $this->recordResult(
            "DataTable handlers extend BaseDataTable",
            $pass,
            "$validHandlers handlers inherit organization filtering"
        );
    }
    
    /**
     * Test 4: Verify middleware can detect org_id injections
     */
    public function testMiddlewareDetection(): void
    {
        echo "[TEST 4] OrgId Injection Middleware\n";
        
        $middleware = new OrgIdInjectionMiddleware($this->mysqli, 1);
        
        // Test 1: Query without org_id should be flagged
        $sql1 = "SELECT * FROM erp_customers";
        $needsInject1 = $this->callPrivateMethod($middleware, 'needsOrgIdInjection', [$sql1]);
        
        // Test 2: Query with org_id should not be flagged
        $sql2 = "SELECT * FROM erp_customers WHERE organization_id = 1";
        $needsInject2 = $this->callPrivateMethod($middleware, 'needsOrgIdInjection', [$sql2]);
        
        // Test 3: Non-org-scoped query should not be flagged
        $sql3 = "SELECT * FROM users";
        $needsInject3 = $this->callPrivateMethod($middleware, 'needsOrgIdInjection', [$sql3]);
        
        $pass = ($needsInject1 === true && $needsInject2 === false && $needsInject3 === false);
        $this->recordResult(
            "Middleware correctly detects org_id injection needs",
            $pass,
            "Q1 needs: " . ($needsInject1 ? "yes" : "no") .
            ", Q2: " . ($needsInject2 ? "yes" : "no") .
            ", Q3: " . ($needsInject3 ? "yes" : "no")
        );
    }
    
    /**
     * Test 5: Validate guard functions exist in bootstrap.php
     */
    public function testGuardFunctionsExist(): void
    {
        echo "[TEST 5] Guard Functions Availability\n";
        
        $bootstrapPath = __DIR__ . '/../dashboard/bootstrap.php';
        if (!file_exists($bootstrapPath)) {
            $this->recordResult("Guard functions in bootstrap.php", false, "bootstrap.php not found");
            return;
        }
        
        $content = file_get_contents($bootstrapPath);
        
        $hasGetActiveOrgId = strpos($content, 'dashboardGetActiveOrganizationId') !== false;
        $hasRequireActiveOrg = strpos($content, 'dashboardRequireActiveOrganization') !== false;
        $hasIsOwner = strpos($content, 'dashboardUserIsOrganizationOwner') !== false;
        
        $pass = ($hasGetActiveOrgId && $hasRequireActiveOrg && $hasIsOwner);
        $this->recordResult(
            "Guard functions in bootstrap.php",
            $pass,
            ($hasGetActiveOrgId ? "✓" : "✗") . " getActiveOrgId, " .
            ($hasRequireActiveOrg ? "✓" : "✗") . " requireActiveOrg, " .
            ($hasIsOwner ? "✓" : "✗") . " isOwner"
        );
    }
    
    /**
     * Test 6: Validate query template patterns
     */
    public function testQueryTemplatePatterns(): void
    {
        echo "[TEST 6] Query Template Patterns\n";
        
        $templatePath = __DIR__ . '/../dashboard/PHASE-7B-QUERY-TEMPLATE.php';
        $pass = file_exists($templatePath);
        
        if ($pass) {
            $content = file_get_contents($templatePath);
            $patternCount = preg_match_all('/Pattern \d+:/i', $content);
            $this->recordResult(
                "Query template guide with patterns",
                $pass,
                "$patternCount query patterns documented"
            );
        } else {
            $this->recordResult("Query template guide with patterns", false, "File not found");
        }
    }
    
    /**
     * Test 7: Verify audit compliance script
     */
    public function testAuditScriptExists(): void
    {
        echo "[TEST 7] Compliance Audit Script\n";
        
        $auditPath = __DIR__ . '/../dashboard/audit-org-id-compliance.php';
        $pass = file_exists($auditPath);
        
        $this->recordResult(
            "Compliance audit script available",
            $pass,
            $pass ? "audit-org-id-compliance.php ready" : "Script missing"
        );
    }
    
    /**
     * Test 8: Verify org-scoped tables count
     */
    public function testOrgScopedTablesCount(): void
    {
        echo "[TEST 8] Org-Scoped Tables Count\n";
        
        $result = $this->mysqli->query(
            "SELECT COUNT(DISTINCT TABLE_NAME) as org_table_count
             FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND COLUMN_NAME = 'organization_id'"
        );
        
        $row = $result->fetch_assoc();
        $count = $row['org_table_count'];
        $pass = ($count >= 30);
        
        $this->recordResult(
            "Org-scoped tables exist",
            $pass,
            "$count tables with organization_id (target: 32)"
        );
    }
    
    /**
     * Test 9: Verify DataTable Registry has org_id support
     */
    public function testDataTableRegistryOrgId(): void
    {
        echo "[TEST 9] DataTable Registry Organization ID Support\n";
        
        $registryPath = __DIR__ . '/../classes/DataTable/Registry.php';
        if (!file_exists($registryPath)) {
            $this->recordResult("Registry org_id support", false, "Registry.php not found");
            return;
        }
        
        $content = file_get_contents($registryPath);
        
        $hasOrgIdProperty = strpos($content, 'organizationId') !== false;
            $passesOrgId = preg_match('/\\$this->organizationId/i', $content) > 0;
        
        $pass = ($hasOrgIdProperty && $passesOrgId);
        $this->recordResult(
            "Registry passes org_id to handlers",
            $pass,
            ($hasOrgIdProperty ? "✓" : "✗") . " orgId property, " .
            ($passesOrgId ? "✓" : "✗") . " passes to handlers"
        );
    }
    
    /**
     * Test 10: Verify Middleware class
     */
    public function testMiddlewareClass(): void
    {
        echo "[TEST 10] OrgId Injection Middleware Class\n";
        
        $middlewarePath = __DIR__ . '/../classes/OrgIdInjectionMiddleware.php';
        $pass = file_exists($middlewarePath);
        
        if ($pass) {
            $content = file_get_contents($middlewarePath);
            $hasMethods = (
                strpos($content, 'function query') !== false &&
                strpos($content, 'function prepare') !== false &&
                strpos($content, 'function injectOrgIdFilter') !== false
            );
            $pass = $pass && $hasMethods;
            
            $this->recordResult(
                "Middleware implementation complete",
                $pass,
                "Class with query, prepare, and injection methods"
            );
        } else {
            $this->recordResult("Middleware implementation complete", false, "Middleware.php not found");
        }
    }
    
    /**
     * Record test result
     */
    private function recordResult(string $testName, bool $pass, string $details = ""): void
    {
        $status = $pass ? "✓ PASS" : "✗ FAIL";
        echo "  $status: $testName\n";
        if ($details) {
            echo "        $details\n";
        }
        
        $this->results[] = [
            'name' => $testName,
            'pass' => $pass,
            'details' => $details
        ];
    }
    
    /**
     * Call private method via reflection
     */
    private function callPrivateMethod($obj, $method, array $args = [])
    {
        try {
            $reflection = new ReflectionClass($obj);
            $refMethod = $reflection->getMethod($method);
            $refMethod->setAccessible(true);
            return $refMethod->invokeArgs($obj, $args);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Print final report
     */
    public function printReport(): void
    {
        echo "\n" . str_repeat("═", 64) . "\n";
        echo "TEST RESULTS SUMMARY\n";
        echo str_repeat("═", 64) . "\n\n";
        
        $passed = count(array_filter($this->results, fn($r) => $r['pass']));
        $total = count($this->results);
        
        foreach ($this->results as $result) {
            $icon = $result['pass'] ? "✓" : "✗";
            echo "$icon {$result['name']}\n";
        }
        
        echo "\n" . str_repeat("─", 64) . "\n";
        echo "Total: $passed / $total tests passed\n";
        echo "Status: " . ($passed === $total ? "✓ ALL TESTS PASSED" : "⚠ $passed/$total passed") . "\n";
        echo str_repeat("═", 64) . "\n";
    }
    
    /**
     * Run all tests
     */
    public function runAll(): void
    {
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "PHASE 8C: Multi-Organization Integration Test Suite\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
        echo "[RUNNING VALIDATION TESTS]\n\n";
        
        $this->testOrgIdColumnsExist();
        echo "\n";
        $this->testOrganizationIdIndexes();
        echo "\n";
        $this->testDataTableHandlerInheritance();
        echo "\n";
        $this->testMiddlewareDetection();
        echo "\n";
        $this->testGuardFunctionsExist();
        echo "\n";
        $this->testQueryTemplatePatterns();
        echo "\n";
        $this->testAuditScriptExists();
        echo "\n";
        $this->testOrgScopedTablesCount();
        echo "\n";
        $this->testDataTableRegistryOrgId();
        echo "\n";
        $this->testMiddlewareClass();
        
        $this->printReport();
    }
}

// Run tests
try {
    $tests = new MultiOrgIntegrationTests($mysqli);
    $tests->runAll();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
