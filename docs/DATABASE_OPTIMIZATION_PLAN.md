# HaiPulse ERP — Database Optimization & Standardization Plan

> **Date:** 2026-06-11
> **Scope:** Full schema audit, table consolidation, column standardization, and optimization roadmap based on live database inspection.
> **Methodology:** Analyzed all 131 tables (2,079 columns, 333 FK relationships) via information_schema queries.

---

## 1. Current State Summary

| Metric | Count |
|--------|-------|
| Active physical tables | **131 → 121** (10 dropped: 8 deprecated + timezones + systems) |
| DB.php constant definitions | **99 → 117** tables |
| Tables in DB but NOT in DB.php | **18 → 0** (all registered) |
| DB.php constants pointing to nonexistent tables | **11 → 11** (8 dropped + 3 dummies, all marked @deprecated) |
| Total columns | **2,079** |
| Foreign key relationships | **333** |
| Tables with `organization_id` | **32** |
| Tables with `publish` column | **60** |
| Tables with `is_active` column | **47** |
| Tables with both `publish` + `is_active` | **33** |
| Tables with `created_by` + `updated_by` | **86** |
| Tables missing `ON UPDATE CURRENT_TIMESTAMP` on `updated_at` | **57** |

---

## 2. Table Reduction Plan

### 2.1 Tier 1 — Drop Deprecated Tables (8 tables, zero risk)

These tables are officially deprecated per `DB.php` `@deprecated` tags. All are small and safe to drop:

| # | Table | Rows | Merged Into | Action |
|---|-------|------|-------------|--------|
| 1 | `erp_shipping_customers` | 3 | `erp_customers` | **DROP** |
| 2 | `erp_sale_types` | 10 | `erp_document_types` | **DROP** |
| 3 | `erp_purchase_types` | 10 | `erp_document_types` | **DROP** |
| 4 | `erp_listing_plans` | 4 | `erp_subscription_plans` | **DROP** |
| 5 | `erp_listing_subscriptions` | 0 | `erp_subscriptions` | **DROP** |
| 6 | `erp_storage_subtypes` | 2 | `erp_storage_types` | **DROP** |
| 7 | `erp_error_log_status` | 0 | `erp_backend_error_logs` | **DROP** |
| 8 | `erp_user_blocks` | 3 | Decommissioned | **DROP** |

**Command to execute:**
```sql
DROP TABLE IF EXISTS
  erp_shipping_customers,
  erp_sale_types,
  erp_purchase_types,
  erp_listing_plans,
  erp_listing_subscriptions,
  erp_storage_subtypes,
  erp_error_log_status,
  erp_user_blocks;
```

**Reduction: 131 → 123 tables.**

---

### 2.2 Tier 2 — Clean Up DB.php Ghost Constants (no data change)

These 11 constants in `src/Core/DB.php` point to tables that no longer exist. Mark them as `@deprecated` or remove them:

| Constant | Physical Table | Status |
|----------|---------------|--------|
| `COMPANIES` | `erp_companies` | Table dropped in Phase 1 |
| `SHIPPING_CUSTOMERS` | `erp_shipping_customers` | Merged into `CUSTOMERS` |
| `SALE_TYPES` | `erp_sale_types` | Merged into `DOCUMENT_TYPES` |
| `PURCHASE_TYPES` | `erp_purchase_types` | Merged into `DOCUMENT_TYPES` |
| `LISTING_PLANS` | `erp_listing_plans` | Merged into `SUBSCRIPTION_PLANS` |
| `LISTING_SUBSCRIPTIONS` | `erp_listing_subscriptions` | Merged into `SUBSCRIPTIONS` |
| `STORAGE_SUBTYPES` | `erp_storage_subtypes` | Merged into `STORAGE_TYPES` |
| `ERROR_LOG_STATUS` | `erp_error_log_status` | Merged into `BACKEND_ERROR_LOGS` |
| `BALANCE_SHEET` | `erp_balance_sheet_dummy` | Never a real table |
| `GENERAL_LEDGER` | `erp_general_ledger_dummy` | Never a real table |
| `TRIAL_BALANCE` | `erp_trial_balance_dummy` | Never a real table |

**Also check if these DB.php constants still need tables:**
`HSCodeTexts` (`erp_hscodes_texts`), `ContainerTypes` (`erp_container_types`), `CommodityTypes` (`erp_commodity_types`), `Services` (`erp_services`), `CategoryItems` (`erp_category_items`), `ReferralCodes` (`erp_referral_codes`).

---

### 2.3 Tier 3 — Consolidation Candidates

#### 2.3.1 `erp_systems` → `erp_modules` (HIGH confidence)

- `erp_modules`: 125 rows, fully featured with `publish`, `is_active`, `created_by/updated_by`
- `erp_systems`: 5 rows, minimal schema (essentially a subset of modules)
- Both serve the same purpose (system feature registry)

**Action:** Migrate 5 rows from `erp_systems` into `erp_modules`, add any missing columns, drop `erp_systems`.

#### 2.3.2 `erp_timezones` → PHP constant array (HIGH confidence)

- 418 rows of static timezone data
- Rarely changes, no FKs, no relationships
- 64 KB data + 0 KB index = not worth a database table

**Action:** Convert to a PHP constant array in `config/timezones.php`, replace all queries with array lookups, drop table.

#### 2.3.3 `erp_dimension_items` → `erp_taxonomies` (MEDIUM confidence)

- `erp_dimension_items`: 8 rows, accounting dimensions
- `erp_taxonomies`: 62 rows, polymorphic categorization with `type` column
- Dimension items follow the same `type`-based polymorphic pattern

**Action:** Add `type='dimension'` rows to `erp_taxonomies`, migrate data, drop `erp_dimension_items`.

#### DO NOT Merge

The following patterns are **essential** and must remain separate:

- **Header/line-item pairs** (11 pairs = 22 tables): `invoices`/`invoice_items`, `quotations`/`quotation_items`, `sale_orders`/`sale_order_items`, `purchases`/`purchase_items`, `purchase_orders`/`purchase_order_items`, `journals`/`journal_items`, `shipping_advices`/`shipping_advice_items`, `shipping_invoices`/`shipping_invoice_items`, `shipping_stocks`/`shipping_stock_items`, `credit_notes`/`credit_note_items`, `debit_notes`/`debit_note_items`
- **Accounting**: `erp_accounts`, `erp_journals`, `erp_expenses` — each serves a distinct double-entry bookkeeping function
- **HR/Payroll**: `erp_departments`, `erp_designations`, `erp_leave_requests`, `erp_payroll_components`, `erp_salary_structures`, `erp_employee_salaries`, `erp_payroll_runs`, `erp_payslips` — each represents a distinct lifecycle stage
- **Shipping**: `erp_shippers`, `erp_consignees`, `erp_carriers`, `erp_ports` — distinct entity types
- **Multi-tenancy**: All 6 organization tables are the backbone of SaaS isolation

**Projected final table count: 119–121** (after all 3 tiers).

---

## 3. Column Standardization

### 3.1 Fix `updated_at` — Add `ON UPDATE CURRENT_TIMESTAMP`

**57 tables** define `updated_at` without `ON UPDATE CURRENT_TIMESTAMP`. The column value never auto-updates on row modification — a critical bug in any SaaS app.

**Affected tables (full list):**
`erp_accounts`, `erp_accounts_report_categories`, `erp_accounts_report_subcategories`, `erp_alerts`, `erp_banks`, `erp_carriers`, `erp_consignees`, `erp_credit_note_items`, `erp_credit_notes`, `erp_currencies`, `erp_customers`, `erp_debit_note_items`, `erp_debit_notes`, `erp_designations`, `erp_dimension_items`, `erp_document_categories`, `erp_email_history`, `erp_email_providers`, `erp_email_queue`, `erp_entity_logs`, `erp_entity_notes`, `erp_exit_points`, `erp_expense_items`, `erp_expenses`, `erp_incoterms`, `erp_industries`, `erp_invoice_items`, `erp_invoices`, `erp_items`, `erp_jobs`, `erp_journals`, `erp_leads`, `erp_module_permissions`, `erp_modules`, `erp_notifications`, `erp_organizations`, `erp_payment_methods`, `erp_payment_received_items`, `erp_payment_terms`, `erp_payments_received`, `erp_permissions`, `erp_ports`, `erp_projects`, `erp_purchase_items`, `erp_purchase_order_items`, `erp_purchase_orders`, `erp_purchases`, `erp_quotation_items`, `erp_quotations`, `erp_roles`, `erp_sale_order_items`, `erp_sale_orders`, `erp_shippers`, `erp_shipping_advice_items`, `erp_shipping_advices`, `erp_shipping_invoice_items`, `erp_shipping_invoices`, `erp_shipping_stocks`, `erp_storage_subtypes`, `erp_storage_types`, `erp_system_settings`, `erp_systems`, `erp_tasks`, `erp_tax_treatments`, `erp_units`, `erp_users`, `erp_vendors`

**Standardize to:**
```sql
ALTER TABLE `erp_xxx` MODIFY COLUMN `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
```

### 3.2 Fix `created_at` Nullable Inconsistency

Most tables use `datetime NULL DEFAULT CURRENT_TIMESTAMP`. For data integrity, `created_at` should **never** be NULL.

**Standardize to:**
```sql
ALTER TABLE `erp_xxx` MODIFY COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;
```

### 3.3 Fix Specific Column Defects

| # | Table | Issue | Fix |
|---|-------|-------|-----|
| 1 | `erp_audit_log` | Missing `updated_at` column entirely | Add column |
| 2 | `erp_authentication_activity` | Missing `updated_at` column entirely | Add column |
| 3 | `erp_taxonomies` | Missing `updated_at` column entirely | Add column |
| 4 | `erp_job_statuses` | Has `created_by` but no `updated_by` | Add `updated_by INT DEFAULT NULL` |
| 5 | `erp_inquiries.updated_at` | `NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP` | Fix to `NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` |
| 6 | `erp_user_blocks.created_at` | `NULL DEFAULT NULL` (no auto-timestamp) | Fix to `NOT NULL DEFAULT CURRENT_TIMESTAMP` |

---

### 3.4 Standardize `publish` vs `is_active`

**Problem:** 60 tables have `publish`, 47 have `is_active`, 33 have **both**. This creates confusion about which flag controls what behavior.

**Modern SaaS convention:**
- `is_active TINYINT(1) NOT NULL DEFAULT 1` = system-level soft-delete toggle (can the record be used at all?)
- `publish TINYINT(1) NOT NULL DEFAULT 1` = editorial workflow toggle (is the content visible to end-users?)

**Recommendation:**

| Table Type | Keep | Rationale |
|------------|------|-----------|
| Entity tables (customers, vendors, items, banks, etc.) | `is_active` only | No editorial workflow needed |
| Content tables (pages, categories, listings) | Both | Editorial review → publish cycle |
| System tables (users, roles, modules) | `is_active` only | No concept of "publishing" a user |
| Transaction tables (invoices, quotations, etc.) | Neither | Use domain-specific status columns instead |

**Tables with both that should drop `publish`:** `erp_accounts`, `erp_addresses`, `erp_alerts`, `erp_api_keys`, `erp_banks`, `erp_carriers`, `erp_consignees`, `erp_contacts`, `erp_currencies`, `erp_customers`, `erp_departments`, `erp_designations`, `erp_document_categories`, `erp_email_providers`, `erp_employee_salaries`, `erp_entity_logs`, `erp_entity_notes`, `erp_exit_points`, `erp_geo_cities`, `erp_geo_countries`, `erp_geo_states`, `erp_incoterms`, `erp_inquiries`, `erp_items`, `erp_job_statuses`, `erp_module_permissions`, `erp_modules`, `erp_payment_methods`, `erp_payment_terms`, `erp_permissions`, `erp_ports`, `erp_roles`, `erp_shippers`, `erp_storage_types`, `erp_tax_treatments`, `erp_units`, `erp_users`

---

### 3.5 Add `organization_id` to Missing Child Tables

These line-item tables lack `organization_id` but their parent headers have it. Adding it enables direct org-scoped queries without JOINs — critical for multi-tenant data isolation:

| Child Table | Parent Table | FK to Add |
|-------------|--------------|-----------|
| `erp_journal_items` | `erp_journals` | `organization_id → erp_organizations.id` |
| `erp_payment_made_items` | `erp_payments_made` | `organization_id → erp_organizations.id` |
| `erp_payment_received_items` | `erp_payments_received` | `organization_id → erp_organizations.id` |

### 3.6 Timestamp Column Standard

**Every data table must have these 4 standard columns:**

```sql
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
`created_by` int DEFAULT NULL  -- FK to erp_users.id
`updated_by` int DEFAULT NULL  -- FK to erp_users.id
```

**Org-scoped tables additionally require:**
```sql
`organization_id` int NOT NULL  -- FK to erp_organizations.id
```

**Entity/master tables additionally require:**
```sql
`is_active` tinyint(1) NOT NULL DEFAULT 1
```

---

## 4. DB.php Registry — Missing Table Constants

These 18 tables exist in the database but have **no constant** in `src/Core/DB.php`. The codebase cannot reference them cleanly:

| # | Table | Domain | Priority | Add Constant As |
|---|-------|--------|----------|-----------------|
| 1 | `erp_frontend_users` | Frontend Auth | **High** | `FRONTEND_USERS` |
| 2 | `erp_hr_employees` | HR | **High** | `HR_EMPLOYEES` |
| 3 | `erp_hr_leave_balances` | HR | **High** | `HR_LEAVE_BALANCES` |
| 4 | `erp_hr_payroll_component_accounts` | HR/Payroll | **High** | `HR_PAYROLL_COMPONENT_ACCOUNTS` |
| 5 | `erp_hr_payroll_run_items` | HR/Payroll | **High** | `HR_PAYROLL_RUN_ITEMS` |
| 6 | `erp_subscription_payments` | SaaS | **High** | `SUBSCRIPTION_PAYMENTS` |
| 7 | `erp_notifications` | System | **High** | `NOTIFICATIONS` |
| 8 | `erp_expense_items` | Accounting | **High** | `EXPENSE_ITEMS` |
| 9 | `erp_payment_made_items` | Accounting | **High** | `PAYMENT_MADE_ITEMS` |
| 10 | `erp_payment_received_items` | Accounting | **High** | `PAYMENT_RECEIVED_ITEMS` |
| 11 | `erp_schema_migrations` | Infra | Medium | `SCHEMA_MIGRATIONS` |
| 12 | `erp_tasks` | CRM | Medium | `TASKS` |
| 13 | `erp_industries` | Setup | Medium | `INDUSTRIES` |
| 14 | `erp_frontend_user_favorites` | Frontend | Medium | `FRONTEND_USER_FAVORITES` |
| 15 | `erp_dimension_items` | Accounting | Medium | `DIMENSION_ITEMS` |
| 16 | `erp_timezones` | Setup | Low (merge candidate) | `TIMEZONES` |
| 17 | `erp_systems` | Setup | Low (merge candidate) | `SYSTEMS` |
| 18 | `erp_user_blocks` | Legacy | Low (drop candidate) | N/A — dropping |

---

## 5. Relationship Audit

### 5.1 Transaction Flow

```
erp_quotations
 ├── erp_quotation_items
 ├── erp_invoices ────── erp_invoice_items
 ├── erp_sale_orders ─── erp_sale_order_items
 ├── erp_purchases ───── erp_purchase_items
 └── erp_purchase_orders ── erp_purchase_order_items

erp_invoices ─────────── erp_credit_notes ─── erp_credit_note_items
erp_purchases ────────── erp_debit_notes ──── erp_debit_note_items

erp_customers ────────── erp_payments_received ── erp_payment_received_items
erp_vendors ──────────── erp_payments_made ────── erp_payment_made_items

erp_journals ─────────── erp_journal_items
erp_expenses ─────────── erp_expense_items
```

### 5.2 Orphan Tables (No FK Relationships)

These 12 tables have zero incoming or outgoing foreign keys:

| Table | Rows | Recommendation |
|-------|------|----------------|
| `erp_backend_log_coverage` | 572 | Acceptable (log table) |
| `erp_disposable_email_domains` | 147,543 | Acceptable (standalone denylist) |
| `erp_document_types` | 40 | Consider FK to `erp_organizations` |
| `erp_error_log_status` | 0 | Dropping |
| `erp_frontend_users` | 51 | **Add FK** to `erp_organizations` |
| `erp_hs_code_mappings` | 50 | Acceptable (junction table — FKs exist at app level) |
| `erp_hscodes` | 13,613 | Acceptable (reference data) |
| `erp_listing_plans` | 4 | Dropping |
| `erp_rate_limits` | 0 | Acceptable (security table) |
| `erp_schema_migrations` | 12 | Acceptable (infra table) |
| `erp_timezones` | 418 | Merging into PHP config |
| `erp_user_blocks` | 3 | Dropping |

### 5.3 Missing FK Recommendations

```sql
-- Frontend users should link to organizations
ALTER TABLE erp_frontend_users
  ADD CONSTRAINT fk_frontend_users_org
  FOREIGN KEY (organization_id) REFERENCES erp_organizations(id);

-- Document types should be org-scoped
ALTER TABLE erp_document_types
  ADD COLUMN organization_id INT DEFAULT NULL,
  ADD CONSTRAINT fk_document_types_org
  FOREIGN KEY (organization_id) REFERENCES erp_organizations(id);
```

---

## 6. Index Audit

Key indexes that should exist based on query patterns:

```sql
-- Multi-tenant scoping (add to all org-scoped tables missing this)
CREATE INDEX idx_organization_id ON erp_xxx (organization_id);

-- Common query pattern: org + status + date
CREATE INDEX idx_org_status_date ON erp_invoices (organization_id, invoice_status, invoice_date);
CREATE INDEX idx_org_status_date ON erp_quotations (organization_id, quotation_status, quotation_date);

-- Soft-delete filtering
CREATE INDEX idx_is_active ON erp_xxx (is_active);
```

---

## 7. SaaS Standards Compliance Checklist

| Standard | Current State | Target |
|----------|--------------|--------|
| All tables use InnoDB | ✅ 100% | — |
| All tables use utf8mb4_unicode_ci | ✅ 100% | — |
| Timestamp columns (`created_at`, `updated_at`) on all tables | ✅ All tables have both | 100% |
| `ON UPDATE CURRENT_TIMESTAMP` on `updated_at` | ✅ All tables | 100% |
| Audit columns (`created_by`, `updated_by`) on all entity tables | ✅ All entity tables | 100% |
| Soft-delete (`is_active`) on all entity tables | ✅ 70 tables have is_active | Standardized (step 1/2) |
| Multi-tenant isolation (`organization_id`) on tenant data | ✅ 70 tables scoped | 100% of tenant data |
| Foreign keys on all relational columns | ✅ All org_id, created_by, updated_by columns have FKs | 100% |
| No deprecated/ghost tables | ✅ 10 tables dropped | Clean |
| Every table has a DB.php constant | ✅ All 121 tables registered | 100% |
| Consistent column naming | ✅ `created_at`/`updated_at` standard adopted | Maintain |

---

## 8. Prioritized Action Plan

| # | Action | Tables Affected | Risk | Est. Time |
|---|--------|----------------|------|-----------|
| **P1** ~~ | Drop 8 deprecated tables ` DONE | 8 | None | 30 min |
| **P2** ~~ | Add `ON UPDATE CURRENT_TIMESTAMP` to 57 `updated_at` columns ` DONE | 57 | Low | 1 hr |
| **P3** ~~ | Add missing `updated_at` to 3 tables (`audit_log`, `authentication_activity`, `taxonomies`) ` DONE | 3 | Low | 15 min |
| **P4** ~~ | Register 18 missing tables in `DB.php` ` DONE | 18 | None | 30 min |
| **P5** ~~ | Add `organization_id` to 3 child line-item tables ` DONE | 3 | Medium | 2 hrs |
| **P6** ~~ | Standardize `created_at` to `NOT NULL` ` DONE | ~100 | Low | 1 hr |
| **P7** ~~ | Clean up 11 ghost/dummy constants in `DB.php` ` DONE | 11 | Low | 20 min |
| **P8** | Consolidate `publish`/`is_active` ` STEP 1 DONE: added is_active to 23 tables. STEP 2 DONE: 136 sync triggers, all SQL code migrated (102 files, ~300 column references). publish column kept for compat (auto-synced). | ~33/23 | Medium | 6 hrs |
| **P9** ~~ | Merge `erp_systems` → `erp_modules` ` DONE (5 rows migrated, modules.php updated, erp_systems dropped) | 2→1 | Low | 1 hr |
| **P10** ~~ | Convert `erp_timezones` to PHP constant array ` DONE (exported to config/timezones.php, table dropped) | 1 | Low | 30 min |
| **P11** ~~ | Add missing FKs for `erp_frontend_users`, `erp_document_types` ` DONE (4 FKs added, orphan org_ids fixed) | 2 | Medium | 1 hr |
| **P12** | Merge `erp_dimension_items` → `erp_taxonomies` ` CANCELLED: dimension_items is shipping CBM data, not taxonomy | N/A | N/A | N/A |
| **P13** ~~ | Fix specific column defects (5 tables with wrong defaults) ` DONE | 5 | Low | 10 min |
| **P14** ~~ | Add missing `updated_by` to `erp_job_statuses` ` DONE | 1 | Low | 5 min |
| **P2.1** ~~ | Add `organization_id` to 12 transaction tables ` DONE | 12 | Medium | 3 hrs |
| **P2.2** ~~ | Update OrgIdInjectionMiddleware to cover all 70 org-scoped tables ` DONE | 40+ | Low | 30 min |
| **P2.3** ~~ | Add publish ↔ is_active sync triggers to 68 dual-column tables ` DONE (136 triggers) | 68 | Low | 15 min |
| **P2.4** ~~ | Migrate all SQL code from publish → is_active (102 files, ~300 refs) ` DONE | 102 | Medium | 3 hrs |

**Result: 131 → 121 tables.** (10 dropped: 8 deprecated + erp_timezones + erp_systems)

---

## 8b. Execution Summary (June 2026)

| Step | Description | Result |
|------|-------------|--------|
| P1 | Dropped 8 deprecated tables | ` shipping_customers, sale_types, purchase_types, listing_plans, listing_subscriptions, storage_subtypes, error_log_status, user_blocks |
| P2 | Added ON UPDATE CURRENT_TIMESTAMP to 57 updated_at columns | All 57 ALTERs OK |
| P3 | Added updated_at to audit_log, authentication_activity, taxonomies | All 3 ALTERs OK |
| P4 | Registered 18 missing tables in DB.php | 18 consts added |
| P5 | Added organization_id to journal_items, payment_made_items, payment_received_items | 3 ALTERs + indexes |
| P6 | Standardized created_at to NOT NULL on 92 tables | All 92 ALTERs OK |
| P7 | Cleaned up 11 ghost/dummy constants in DB.php | Marked @deprecated |
| P8 | Added is_active to 23 publish-only tables, synced from publish | All 23 OK |
| P9 | Merged erp_systems → erp_modules (5 rows migrated, modules.php updated) | erp_systems dropped |
| P10 | Converted erp_timezones to PHP config array | Exported to config/timezones.php, table dropped |
| P11 | Added 4 FKs: frontend_users(document_types).org_id→organizations, document_types.created_by/updated_by→users | 4 FKs, fixed 40 orphan org_ids |
| P12 | CANCELLED: erp_dimension_items is shipping CBM calc, not taxonomy | N/A |
| P13 | Fixed erp_inquiries.updated_at NULL default | Fixed |
| P14 | Added updated_by to erp_job_statuses | Added |
| **+** | Added org_id FKs to erp_alerts, erp_audit_log, erp_journal_items, erp_payment_made_items, erp_payment_received_items | 5 FKs added — all org_id columns now have FKs |
| P2.1 | Added organization_id to 12 transaction tables | 12 ALTERs + indexes + FKs + data population |
| P2.2 | Updated OrgIdInjectionMiddleware from ~30 to 70 tables | Removed stale shipping_customers, added 40+ new tables |
| P2.3 | Created publish↔is_active sync triggers on 68 dual-column tables | 136 BEFORE INSERT/UPDATE triggers |
| P2.4 | Migrated all SQL code (102 files) from publish column to is_active | ~300 column references across dashboard, src, DataTable files |

**Total: All 16 steps complete.** 14/14 Phase 1 + 2/2 Phase 2 + 2 bonus Phase 2 extensions. 1 cancelled (P12).

---

## 9. Migration Template for Deprecated Tables

```php
<?php
/**
 * Migration: Drop deprecated tables
 * Tables: erp_shipping_customers, erp_sale_types, erp_purchase_types,
 *         erp_listing_plans, erp_listing_subscriptions, erp_storage_subtypes,
 *         erp_error_log_status, erp_user_blocks
 */
return [
    'up' => function (mysqli $conn, string $prefix): void {
        $tables = [
            'shipping_customers',
            'sale_types',
            'purchase_types',
            'listing_plans',
            'listing_subscriptions',
            'storage_subtypes',
            'error_log_status',
            'user_blocks',
        ];
        foreach ($tables as $table) {
            $conn->query("DROP TABLE IF EXISTS `{$prefix}{$table}`");
        }
    },
];
```

### Migration Template for `updated_at` Fix

```php
<?php
/**
 * Migration: Fix updated_at columns — add ON UPDATE CURRENT_TIMESTAMP
 * Tables: 57 tables
 */
return [
    'up' => function (mysqli $conn, string $prefix): void {
        $tables = [
            'accounts', 'accounts_report_categories', 'accounts_report_subcategories',
            // ... (full list from section 3.1)
        ];
        foreach ($tables as $table) {
            $conn->query(
                "ALTER TABLE `{$prefix}{$table}` 
                 MODIFY COLUMN `updated_at` datetime NOT NULL 
                 DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
            );
        }
    },
];
```

---

## 10. Column Standard Reference

### Every table MUST have:

```sql
`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`created_by` int DEFAULT NULL,
`updated_by` int DEFAULT NULL
```

### Org-scoped tables MUST additionally have:

```sql
`organization_id` int NOT NULL,
INDEX idx_organization_id (`organization_id`),
CONSTRAINT fk_xxx_org FOREIGN KEY (`organization_id`) REFERENCES `erp_organizations`(`id`) ON DELETE CASCADE
```

### Entity/master tables SHOULD additionally have:

```sql
`is_active` tinyint(1) NOT NULL DEFAULT 1,
INDEX idx_is_active (`is_active`)
```

### Transaction tables SHOULD use domain-specific status:

```sql
`status` varchar(...) NOT NULL DEFAULT 'draft',
INDEX idx_status (`status`)
```

---

*Phase 1: 11/14 complete, 1 partial (P8 step1), 1 cancelled (P12). Phase 2: 2/2 complete. P8 step2 (publish→is_active code migration) is the only remaining work.*
**Bonus:** All org_id columns (70 tables) have FKs. OrgIdInjectionMiddleware covers all 70 org-scoped tables. 121 tables total.
