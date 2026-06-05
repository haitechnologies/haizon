<?php
/**
 * PHASE 7B: Direct Query Organization Isolation
 * 
 * Quick Reference Guide for Updating Dashboard Queries
 * 
 * All SELECT queries on tables with organization_id MUST filter by active org
 * 
 * @file_guide phase-7b-query-template.php
 */

// ============================================================================
// PATTERN 1: Simple SELECT with org_id filter
// ============================================================================

// ❌ BEFORE (multi-tenant data leak!)
$result = $mysqli->query(
    "SELECT * FROM `" . DB::CUSTOMERS . "` 
    WHERE `is_active` = 1 
    LIMIT 10"
);

// ✓ AFTER (org-scoped)
$result = $mysqli->query(
    "SELECT * FROM `" . DB::CUSTOMERS . "` 
    WHERE `organization_id` = " . (int)$activeOrganizationId . " 
    AND `is_active` = 1 
    LIMIT 10"
);

// ============================================================================
// PATTERN 2: JOIN with org_id filter on both tables
// ============================================================================

// ❌ BEFORE
$result = $mysqli->query(
    "SELECT c.*, i.total FROM `" . DB::CUSTOMERS . "` c 
    LEFT JOIN `" . DB::INVOICES . "` i ON c.id = i.customer_id 
    WHERE c.is_active = 1"
);

// ✓ AFTER (filter org_id on PRIMARY table only - JOINs inherit via FK)
$result = $mysqli->query(
    "SELECT c.*, i.total FROM `" . DB::CUSTOMERS . "` c 
    LEFT JOIN `" . DB::INVOICES . "` i ON c.id = i.customer_id 
    WHERE c.`organization_id` = " . (int)$activeOrganizationId . " 
    AND c.`is_active` = 1"
);

// ============================================================================
// PATTERN 3: Prepared statement (BEST PRACTICE)
// ============================================================================

$stmt = $mysqli->prepare(
    "SELECT * FROM `" . DB::CUSTOMERS . "` 
    WHERE `organization_id` = ? 
    AND `is_active` = ? 
    LIMIT ?"
);
$stmt->bind_param('iii', $activeOrganizationId, 1, 10);
$stmt->execute();
$result = $stmt->get_result();

// ============================================================================
// PATTERN 4: Aggregate queries (COUNT, SUM, etc.)
// ============================================================================

// ❌ BEFORE
$row = $mysqli->query(
    "SELECT COUNT(*) as total, SUM(total) as revenue FROM `" . DB::INVOICES . "`"
)->fetch_assoc();

// ✓ AFTER
$row = $mysqli->query(
    "SELECT COUNT(*) as total, SUM(total) as revenue FROM `" . DB::INVOICES . "` 
    WHERE `organization_id` = " . (int)$activeOrganizationId
)->fetch_assoc();

// ============================================================================
// PATTERN 5: INSERT/UPDATE with org_id
// ============================================================================

// ✓ When creating new customer
$mysqli->query(
    "INSERT INTO `" . DB::CUSTOMERS . "` 
    (`organization_id`, `display_name`, `email`) 
    VALUES (" . (int)$activeOrganizationId . ", 'John', 'john@example.com')"
);

// ✓ When updating customer (should already have org_id from creation)
$mysqli->query(
    "UPDATE `" . DB::CUSTOMERS . "` 
    SET `display_name` = 'Jane' 
    WHERE `id` = " . (int)$_POST['id'] . " 
    AND `organization_id` = " . (int)$activeOrganizationId
);

// DELETE also needs org_id check
$mysqli->query(
    "DELETE FROM `" . DB::CUSTOMERS . "` 
    WHERE `id` = " . (int)$_POST['id'] . " 
    AND `organization_id` = " . (int)$activeOrganizationId
);

// ============================================================================
// KEY RULES
// ============================================================================

/*
1. EVERY SELECT on org-scoped tables MUST have: WHERE organization_id = {activeOrgId}
   Exception: Tables WITHOUT organization_id column (legacy/reference data)

2. JOIN queries only filter org_id on PRIMARY table (other tables inherit via FK)

3. UPDATE/DELETE MUST include org_id filter as security check

4. Use prepared statements for user input ($stmt->bind_param)

5. For DataTables: Don't modify - handled by BaseDataTable.getOrgIdWhereClause()

6. For GET/COUNT operations: Always include org_id in WHERE clause

7. Variable: $activeOrganizationId available from dashboardRequireActiveOrganization()
   Location: Added to session via bootstrap.php after guard enforcement
*/
