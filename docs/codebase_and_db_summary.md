# Haipulse ERP — Compact Codebase & Database Summary

> Last updated: June 2026 (post Phase 1-2 optimization)

## 1. Project Overview

- **Stack:** PHP 8.2+, PSR-4 `App\` → `src/`, custom platform (no Laravel/Symfony), Bootstrap 5.3, jQuery 3.7
- **Database:** MySQL 8.0+, InnoDB, `utf8mb4_unicode_ci`, prefix `erp_` (runtime-rewritable)
- **Tables:** 123 (post-optimization)
- **Frontend:** Bootstrap 5 + DataTables.js, dashboard UI at `/dashboard/`
- **Entry point:** `/index.php` redirects to `/dashboard/login.php`
- **CLI:** `php database/migrate.php`

## 2. Directory Map

| Path | Purpose |
|------|---------|
| `src/Core/` | DB.php (table constants), Database.php (PDO wrapper), OrgIdInjectionMiddleware.php |
| `src/Model/` | Readonly DTOs (User, Customer, Invoice, Department, Designation, etc.) |
| `src/Repository/` | CRUD isolation per entity (7 repos) |
| `src/Service/` | Business logic layer |
| `src/DataTable/` | Server-side DataTables handlers + Registry |
| `src/Frontend/` | Public-facing query classes |
| `src/Security/` | Auth, roles, rate limiting, entitlements |
| `dashboard/` | Monolithic page controllers + admin_elements/ layouts + api/ endpoints |
| `config/` | globals.php (procedural helpers), database.php (connection), session.php |
| `docs/` | AGENTS.md, codebase_and_db_summary.md, DATABASE_*.md plans |

## 3. Database Table Inventory (123 tables, 70 org-scoped)

### Core & Auth
`erp_users`, `erp_roles`, `erp_permissions`, `erp_module_permissions`, `erp_authentication_activity`, `erp_rate_limits`

### Multi-Tenancy & SaaS (all org-scoped via `organization_id`)
`erp_organizations`, `erp_organization_memberships`, `erp_organization_roles`, `erp_organization_member_roles`, `erp_organization_invites`, `erp_organization_system_entitlements`, `erp_subscription_plans`, `erp_subscriptions`, `erp_subscription_plan_features`, `erp_subscription_overrides`, `erp_subscription_payments`, `erp_subscription_logs`, `erp_api_keys`

### CRM (all org-scoped)
`erp_customers`, `erp_leads`, `erp_contacts` (polymorphic: customers+vendors), `erp_addresses` (polymorphic), `erp_entity_logs`, `erp_entity_notes`, `erp_inquiries`, `erp_inquiry_replies`

### HR & Payroll (all org-scoped)
`erp_departments`, `erp_designations`, `erp_attendance`, `erp_leave_types`, `erp_leave_requests`, `erp_payroll_components`, `erp_salary_structures`, `erp_employee_salaries`, `erp_payroll_runs`, `erp_payslips`, `erp_hr_leave_balances`, `erp_hr_payroll_component_accounts`, `erp_hr_payroll_run_items`

### Sales & Invoicing (all org-scoped)
`erp_invoices`, `erp_invoice_items`, `erp_quotations`, `erp_quotation_items`, `erp_sale_orders`, `erp_sale_order_items`, `erp_credit_notes`, `erp_credit_note_items`, `erp_payments_received`, `erp_payment_received_items`

### Purchasing (all org-scoped)
`erp_vendors`, `erp_purchases`, `erp_purchase_items`, `erp_purchase_orders`, `erp_purchase_order_items`, `erp_debit_notes`, `erp_debit_note_items`, `erp_payments_made`, `erp_payment_made_items`, `erp_expenses`, `erp_expense_items`

### Accounting (all org-scoped)
`erp_accounts`, `erp_accounts_report_categories`, `erp_accounts_report_subcategories`, `erp_journals`, `erp_journal_items`, `erp_banks`

### Shipping & Logistics (all org-scoped)
`erp_shipping_advices`, `erp_shipping_advice_items`, `erp_shipping_invoices`, `erp_shipping_invoice_items`, `erp_shipping_stocks`, `erp_shipping_stock_items`, `erp_ports`, `erp_carriers`, `erp_consignees`, `erp_shippers`, `erp_exit_points`, `erp_warehouses`, `erp_storage_types`, `erp_dimension_items`, `erp_commodity_types`

### Geography
`erp_geo_countries`, `erp_geo_states`, `erp_geo_cities`

### Content & Taxonomies (org-scoped where applicable)
`erp_categories`, `erp_subcategories`, `erp_pages`, `erp_items`, `erp_taxonomies`, `erp_document_types`, `erp_document_categories`, `erp_units`, `erp_incoterms`, `erp_banned_words`, `erp_hscodes`, `erp_hs_code_mappings`, `erp_industries`

### Operational Setup (org-scoped where applicable)
`erp_modules`, `erp_payment_methods`, `erp_payment_terms`, `erp_currencies`, `erp_tax_treatments`, `erp_jobs`, `erp_job_items`, `erp_job_statuses`, `erp_projects`, `erp_tasks`, `erp_system_settings`

### Infrastructure (auto-created at runtime)
`erp_schema_migrations`, `erp_email_queue`, `erp_email_history`, `erp_email_providers`, `erp_rate_limits`, `erp_backend_error_logs`, `erp_backend_log_coverage`, `erp_disposable_email_domains`, `erp_alerts`, `erp_audit_log`, `erp_error_log_status`, `erp_notifications`

### Shared/Polymorphic Tables
| Physical Table | Serves | Column |
|---------------|--------|--------|
| `erp_contacts` | Customers + Vendors | `entity_type` + FK |
| `erp_addresses` | Customers + Vendors | `entity_type` + FK |
| `erp_attachments` | Users, Customers, Leads, Vendors | `entity_type` + `entity_id` |
| `erp_entity_logs` | Customers, Leads | `entity_type` |
| `erp_document_types` | Sales, Purchases | `context` (sale/purchase) |

## 4. Column Standards (post-optimization)

Every entity table has:
```sql
`id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`created_by` int unsigned DEFAULT NULL,
`updated_by` int unsigned DEFAULT NULL,
`is_active` tinyint(1) NOT NULL DEFAULT 1,
KEY `idx_is_active` (`is_active`),
KEY `idx_created_by` (`created_by`),
KEY `idx_updated_by` (`updated_by`)
```

Org-scoped tables additionally have:
```sql
`organization_id` int unsigned NOT NULL,
KEY `idx_organization_id` (`organization_id`),
CONSTRAINT `fk_xxx_organization` FOREIGN KEY (`organization_id`) REFERENCES `erp_organizations`(`id`) ON DELETE CASCADE
```

**Key facts:**
- 70 tables have `organization_id` (all have FK → `erp_organizations`)
- All tables with `created_by`/`updated_by` have FKs → `erp_users`
- 68 tables have both `is_active` and `publish` columns (auto-synced via 136 BEFORE INSERT/UPDATE triggers)
- New code MUST use `is_active` only; `publish` exists for backward compatibility
- `OrgIdInjectionMiddleware` covers all 70 org-scoped tables

## 5. Architecture Layers

```
Controller (dashboard/*.php) → Service (src/Service/) → Repository (src/Repository/) → Model (readonly DTO)
```

Pilot modules fully migrated: Departments, Designations. Most other modules use monolithic dashboard pages.

## 6. Database Access

```php
// Preferred (PDO) — always use named params
$db = \App\Core\DB::pdo();
$user = $db->fetchOne("SELECT id, email FROM " . DB::USERS . " WHERE id = :id", ['id' => $userId]);

// Table names — always use DB:: constants
DB::CUSTOMERS        // → erp_customers
DB::CUSTOMER_CONTACTS // → erp_contacts (alias)
DB::INVOICES         // → erp_invoices
DB::DEPARTMENTS      // → erp_departments (DB::DEPARTMENT is an alias)

// Legacy (MySQLi) — existing code only
global $mysqli;
```

## 7. Security & Multi-Tenancy

- **CSRF:** `csrf_field()` + `validate_csrf_token()` on all POST
- **Auth:** Session-based (`$_SESSION['haipulse']['DASHBOARD']`), TOTP MFA
- **Permissions:** `granted('action', $module_id)` or `granted_('action', 'slug')`
- **Tenant isolation:** `OrgIdInjectionMiddleware` auto-injects `WHERE organization_id = :orgId` into queries on 70 org-scoped tables
- **Soft delete:** `is_active = 0` — never hard delete

## 8. Key Files Reference

| File | Purpose |
|------|---------|
| `src/Core/DB.php` | 120+ table name constants |
| `src/Core/Database.php` | PDO wrapper (fetchOne, fetchAll, execute) |
| `src/Core/OrgIdInjectionMiddleware.php` | Multi-tenant query injection (70 tables) |
| `config/globals.php` | Procedural helpers (2800+ lines) |
| `config/database.php` | DB connection + legacy tbl_* constants |
| `config/constants.php` | Project-wide constants |
| `config/timezones.php` | Timezone lookup array (247 countries, ~418 entries) |
| `dashboard/bootstrap.php` | Session, CSP, tenant context |
| `src/DataTable/Registry.php` | Module → DataTable handler mapping |

## 9. CLI Commands

```
php database/migrate.php [--run|--status|--verify]
php tests/run_all_tests.php
php -l <file>                          # Syntax check
vendor/bin/phpstan analyse             # Static analysis
vendor/bin/phpcs --standard=.phpcs.xml # Coding standards
```
