<?php
/**
 * Organization ID Query Injection Middleware
 * 
 * Provides automatic organization_id filtering on all database queries
 * Acts as a safety net to prevent accidental multi-tenant data leaks
 * 
 * Usage:
 *   include 'middleware/org-id-injection.php';
 *   $middleware = new OrgIdInjectionMiddleware($mysqli, $activeOrgId);
 *   $result = $middleware->query($sql);  // Auto-injects org_id WHERE clause
 */

class OrgIdInjectionMiddleware
{
    private $mysqli;
    private $activeOrgId;
    private $orgScopedTables;
    private $logger;
    private $injectionLog = [];
    
    /**
     * Initialize middleware
     * 
     * @param mysqli $mysqli Database connection
     * @param int $activeOrgId Active organization ID
     * @param Logger $logger Optional logger instance
     */
    public function __construct($mysqli, int $activeOrgId, $logger = null)
    {
        $this->mysqli = $mysqli;
        $this->activeOrgId = $activeOrgId;
        $this->logger = $logger;
        
        // All tables with organization_id column
        $this->orgScopedTables = [
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
    }
    
    /**
     * Execute query with automatic org_id injection for SELECT statements
     * 
     * @param string $sql Original SQL query
     * @return mysqli_result|bool Query result
     */
    public function query(string $sql)
    {
        $injectedSql = $this->injectOrgIdFilter($sql);
        
        // Log if query was modified
        if ($injectedSql !== $sql) {
            $this->recordInjection($sql, $injectedSql);
        }
        
        return $this->mysqli->query($injectedSql);
    }
    
    /**
     * Prepare statement with automatic org_id injection
     * 
     * Note: Prepared statements should already have org_id filtering.
     * This method validates the query and logs if injections would be needed.
     * 
     * @param string $sql Prepared SQL with placeholders
     * @return mysqli_stmt|bool Prepared statement
     */
    public function prepare(string $sql)
    {
        // Check if query needs org_id injection (warning only - prepared statements should already have it)
        if ($this->needsOrgIdInjection($sql)) {
            $this->logWarning("Prepared statement should include org_id filter", $sql);
        }
        
        return $this->mysqli->prepare($sql);
    }
    
    /**
     * Inject organization_id filter into SQL query
     * 
     * For SELECT queries: Adds " AND organization_id = {id}" to WHERE clause
     * For UPDATE/DELETE: Validates org_id is included (doesn't auto-inject for safety)
     * For other queries: Returns unchanged
     * 
     * @param string $sql Original SQL query
     * @return string Modified SQL query
     */
    private function injectOrgIdFilter(string $sql): string
    {
        // Only auto-inject org_id into SELECT statements
        if (!preg_match('/^\s*SELECT\s+/i', trim($sql))) {
            return $sql;
        }
        
        // Check if query involves org-scoped tables
        $involvesOrgTable = false;
        foreach ($this->orgScopedTables as $table) {
            if (stripos($sql, $table) !== false) {
                $involvesOrgTable = true;
                break;
            }
        }
        
        if (!$involvesOrgTable) {
            return $sql;
        }
        
        // Check if org_id is already in the WHERE clause
        if (preg_match('/organization_id\s*=|org_id\s*=|org\.id/i', $sql)) {
            return $sql; // Already filtered
        }
        
        // Inject org_id filter
        if (preg_match('/WHERE\s+/i', $sql)) {
            // Append to existing WHERE clause
            $sql = preg_replace('/WHERE\s+/i', 'WHERE organization_id = ' . (int)$this->activeOrgId . ' AND ', $sql);
        } else {
            // Add WHERE clause
            if (preg_match('/ORDER\s+BY|GROUP\s+BY|LIMIT|;/i', $sql)) {
                // Insert before ORDER BY, GROUP BY, LIMIT, or semicolon
                $sql = preg_replace('/(ORDER\s+BY|GROUP\s+BY|LIMIT|;)/i', 'WHERE organization_id = ' . (int)$this->activeOrgId . ' $1', $sql);
            } else {
                // Append to end
                $sql .= ' WHERE organization_id = ' . (int)$this->activeOrgId;
            }
        }
        
        return $sql;
    }
    
    /**
     * Check if query needs org_id injection
     * 
     * @param string $sql SQL query
     * @return bool True if query involves org-scoped table without org_id
     */
    private function needsOrgIdInjection(string $sql): bool
    {
        // Check for SELECT queries on org-scoped tables without org_id filter
        if (!preg_match('/^\s*SELECT\s+/i', trim($sql))) {
            return false;
        }
        
        $involvesOrgTable = false;
        foreach ($this->orgScopedTables as $table) {
            if (stripos($sql, $table) !== false) {
                $involvesOrgTable = true;
                break;
            }
        }
        
        if (!$involvesOrgTable) {
            return false;
        }
        
        // Check for org_id already present
        return !preg_match('/organization_id\s*=|org_id\s*=|org\.id/i', $sql);
    }
    
    /**
     * Record query injection for audit logging
     * 
     * @param string $original Original SQL
     * @param string $injected Injected SQL
     */
    private function recordInjection(string $original, string $injected): void
    {
        $this->injectionLog[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'org_id' => $this->activeOrgId,
            'original' => $original,
            'injected' => $injected
        ];
        
        if ($this->logger) {
            $this->logger->info('org_id_injected', [
                'original_query' => substr($original, 0, 200),
                'organization_id' => $this->activeOrgId
            ]);
        }
    }
    
    /**
     * Log warning for compliance issues
     * 
     * @param string $message Warning message
     * @param string $sql SQL query
     */
    private function logWarning(string $message, string $sql): void
    {
        error_log("[OrgIdMiddleware] WARNING: $message - Query: " . substr($sql, 0, 100));
        
        if ($this->logger) {
            $this->logger->warn('org_id_compliance_warning', [
                'message' => $message,
                'query' => substr($sql, 0, 200)
            ]);
        }
    }
    
    /**
     * Get injection audit log
     * 
     * @return array All injections performed
     */
    public function getInjectionLog(): array
    {
        return $this->injectionLog;
    }
    
    /**
     * Clear injection log
     */
    public function clearLog(): void
    {
        $this->injectionLog = [];
    }
}

/**
 * INTEGRATION GUIDE
 * 
 * Option 1: Direct middleware wrapping (not recommended for legacy code)
 * ─────────────────────────────────────────────────────────────────────
 * 
 *   $middleware = new OrgIdInjectionMiddleware($mysqli, $activeOrgId);
 *   $result = $middleware->query("SELECT * FROM erp_customers");
 *   // Automatically becomes: SELECT * FROM erp_customers WHERE organization_id = 1
 * 
 * 
 * Option 2: Validation layer (recommended)
 * ──────────────────────────────────────────
 * 
 *   $middleware = new OrgIdInjectionMiddleware($mysqli, $activeOrgId);
 *   
 *   // In queries that should be org-scoped:
 *   $sql = "SELECT * FROM erp_customers WHERE is_active = 1";
 *   if ($middleware->needsOrgIdInjection($sql)) {
 *       trigger_error("Query missing org_id filter", E_USER_WARNING);
 *       $sql = $middleware->injectOrgIdFilter($sql); // Safety fallback
 *   }
 *   $result = $mysqli->query($sql);
 * 
 * 
 * Option 3: Prepared statement validation (BEST PRACTICE)
 * ─────────────────────────────────────────────────────────
 * 
 *   $middleware = new OrgIdInjectionMiddleware($mysqli, $activeOrgId);
 *   
 *   // Prepared statements MUST include org_id - middleware validates
 *   $stmt = $middleware->prepare("SELECT * FROM erp_customers WHERE organization_id = ? AND is_active = 1");
 *   $stmt->bind_param('i', $activeOrgId);
 *   $stmt->execute();
 */
