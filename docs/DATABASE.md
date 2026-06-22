# Haizon ERP — Database Reference

> **Source of truth:** All table constants are defined in `src/Core/DB.php`.

## Overview

- **Engine:** MySQL 8.0+ / InnoDB
- **Charset:** `utf8mb4_unicode_ci`
- **Prefix:** `erp_` (rewritten at runtime via `DynamicPrefixPdo`)
- **Tables:** ~100 physical tables
- **Multi-tenancy:** Row-level isolation via `organization_id` FK → `erp_organizations` on ~70 tables

---

## Table Catalog

### User & Auth (8 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `USERS` | `erp_users` | No |
| `ROLES` | `erp_roles` | No |
| `PERMISSIONS` | `erp_permissions` | No |
| `MODULE_PERMISSIONS` | `erp_module_permissions` | No |
| `AUTHENTICATION_ACTIVITY` | `erp_authentication_activity` | No |
| `RATE_LIMIT_ATTEMPTS` | `erp_rate_limits` | No |
| `RATE_LIMIT_PUBLIC` | `erp_rate_limits` (alias) | No |

### HR & Payroll (14 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `DEPARTMENTS` | `erp_departments` | Yes |
| `DESIGNATIONS` | `erp_designations` | Yes |
| `ATTENDANCE` | `erp_attendance` | Yes |
| `LEAVE_TYPES` | `erp_leave_types` | Yes |
| `LEAVE_REQUESTS` | `erp_leave_requests` | Yes |
| `HR_LEAVE_BALANCES` | `erp_hr_leave_balances` | Yes |
| `PAYROLL_COMPONENTS` | `erp_payroll_components` | Yes |
| `SALARY_STRUCTURES` | `erp_salary_structures` | Yes |
| `EMPLOYEE_SALARIES` | `erp_employee_salaries` | Yes |
| `PAYROLL_RUNS` | `erp_payroll_runs` | Yes |
| `PAYSLIPS` | `erp_payslips` | Yes |
| `HR_PAYROLL_RUN_ITEMS` | `erp_hr_payroll_run_items` | Yes |
| `HR_PAYROLL_COMPONENT_ACCOUNTS` | `erp_hr_payroll_component_accounts` | Yes |

### CRM — Customers (6 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `CUSTOMERS` | `erp_customers` | Yes |
| `CUSTOMER_CONTACTS` | `erp_contacts` (poly) | Yes |
| `CUSTOMER_ADDRESSES` | `erp_addresses` (poly) | Yes |
| `CUSTOMER_TRANSACTIONS` | `erp_customer_transactions` | Yes |
| `ENTITY_LOGS` | `erp_entity_logs` | Yes |
| `ENTITY_NOTES` | `erp_entity_notes` | Yes |

### CRM — Leads (1 table)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `LEADS` | `erp_leads` | Yes |
| `LEAD_ATTACHMENTS` | `erp_attachments` (poly) | Yes |

### Sales & Invoicing (8 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `QUOTATIONS` | `erp_quotations` | Yes |
| `QUOTATION_ITEMS` | `erp_quotation_items` | Yes |
| `SALE_ORDERS` | `erp_sale_orders` | Yes |
| `SALE_ORDER_ITEMS` | `erp_sale_order_items` | Yes |
| `INVOICES` | `erp_invoices` | Yes |
| `INVOICE_ITEMS` | `erp_invoice_items` | Yes |
| `CREDIT_NOTES` | `erp_credit_notes` | Yes |
| `CREDIT_NOTE_ITEMS` | `erp_credit_note_items` | Yes |

### Purchasing (8 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `VENDORS` | `erp_vendors` | Yes |
| `VENDOR_CONTACTS` | `erp_contacts` (poly) | Yes |
| `VENDOR_ADDRESSES` | `erp_addresses` (poly) | Yes |
| `PURCHASES` | `erp_purchases` | Yes |
| `PURCHASE_ITEMS` | `erp_purchase_items` | Yes |
| `PURCHASE_ORDERS` | `erp_purchase_orders` | Yes |
| `PURCHASE_ORDER_ITEMS` | `erp_purchase_order_items` | Yes |
| `DEBIT_NOTES` | `erp_debit_notes` | Yes |
| `DEBIT_NOTE_ITEMS` | `erp_debit_note_items` | Yes |

### Accounting — Journals & Expenses (8 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `ACCOUNTS` | `erp_accounts` | Yes |
| `ACCOUNTS_REPORT_CATEGORIES` | `erp_accounts_report_categories` | Yes |
| `ACCOUNTS_REPORT_SUBCATEGORIES` | `erp_accounts_report_subcategories` | Yes |
| `DIMENSION_ITEMS` | `erp_dimension_items` | Yes |
| `JOURNALS` | `erp_journals` | Yes |
| `JOURNAL_ITEMS` | `erp_journal_items` | Yes |
| `EXPENSES` | `erp_expenses` | Yes |
| `EXPENSE_ITEMS` | `erp_expense_items` | Yes |

### Payments & Banking (6 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `PAYMENT_METHODS` | `erp_payment_methods` | Yes |
| `PAYMENTS_RECEIVED` | `erp_payments_received` | Yes |
| `PAYMENT_RECEIVED_ITEMS` | `erp_payment_received_items` | Yes |
| `PAYMENTS_MADE` | `erp_payments_made` | Yes |
| `PAYMENT_MADE_ITEMS` | `erp_payment_made_items` | Yes |
| `BANKS` | `erp_banks` | Yes |

### Shipping & Logistics (12 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `SHIPPING_ADVICES` | `erp_shipping_advices` | Yes |
| `SHIPPING_ADVICE_ITEMS` | `erp_shipping_advice_items` | Yes |
| `SHIPPING_INVOICES` | `erp_shipping_invoices` | Yes |
| `SHIPPING_INVOICE_ITEMS` | `erp_shipping_invoice_items` | Yes |
| `SHIPPING_STOCKS` | `erp_shipping_stocks` | Yes |
| `SHIPPING_STOCK_ITEMS` | `erp_shipping_stock_items` | Yes |
| `PORTS` | `erp_ports` | Yes |
| `CARRIERS` | `erp_carriers` | Yes |
| `CONSIGNEES` | `erp_consignees` | Yes |
| `SHIPPERS` | `erp_shippers` | Yes |

### Subscriptions / SaaS (8 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `SUBSCRIPTION_PLANS` | `erp_subscription_plans` | No |
| `SUBSCRIPTIONS` | `erp_subscriptions` | Yes |
| `SUBSCRIPTION_PLAN_FEATURES` | `erp_subscription_plan_features` | No |
| `SUBSCRIPTION_OVERRIDES` | `erp_subscription_overrides` | Yes |
| `SUBSCRIPTION_PAYMENTS` | `erp_subscription_payments` | Yes |
| `API_KEYS` | `erp_api_keys` | Yes |
| `SUBSCRIPTION_LOGS` | `erp_subscription_logs` | Yes |

### System & Config (8 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `SYSTEM_SETTINGS` | `erp_system_settings` | No |
| `MODULES` | `erp_modules` | No |
| `SCHEMA_MIGRATIONS` | `erp_schema_migrations` | No |
| `BACKEND_ERROR_LOGS` | `erp_backend_error_logs` | No |
| `BACKEND_LOG_COVERAGE` | `erp_backend_log_coverage` | No |
| `EMAIL_PROVIDERS` | `erp_email_providers` | No |
| `EMAIL_HISTORY` | `erp_email_history` | Yes |
| `EMAIL_QUEUE` | `erp_email_queue` | Yes |

### Geography (3 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `GEO_COUNTRIES` | `erp_geo_countries` | No |
| `GEO_STATES` | `erp_geo_states` | No |
| `GEO_CITIES` | `erp_geo_cities` | No |

### Inventory & Organization (8 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `ITEMS` | `erp_items` | Yes |
| `ORGANIZATIONS` | `erp_organizations` | No |
| `ORGANIZATION_MEMBERSHIPS` | `erp_organization_memberships` | Yes |
| `ORGANIZATION_ROLES` | `erp_organization_roles` | Yes |
| `ORGANIZATION_MEMBER_ROLES` | `erp_organization_member_roles` | Yes |
| `ORGANIZATION_INVITES` | `erp_organization_invites` | Yes |
| `ORGANIZATION_SYSTEM_ENTITLEMENTS` | `erp_organization_system_entitlements` | Yes |
| `WAREHOUSES` | `erp_organizations` (alias) | No |

### Setup & Master Data (10 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `TAXONOMIES` | `erp_taxonomies` | Yes |
| `DOCUMENT_TYPES` | `erp_document_types` | Yes |
| `TAX_TREATMENTS` | `erp_tax_treatments` | Yes |
| `PAYMENT_TERMS` | `erp_payment_terms` | Yes |
| `CURRENCIES` | `erp_currencies` | Yes |
| `UNITS` | `erp_units` | Yes |
| `HS_CODES` | `erp_hscodes` | No |
| `CATEGORIES` | `erp_categories` | Yes |
| `SUBCATEGORIES` | `erp_subcategories` | Yes |
| `INCOTERMS` | `erp_incoterms` | Yes |

### Projects & Jobs (6 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `PROJECTS` | `erp_projects` | Yes |
| `JOBS` | `erp_jobs` | Yes |
| `JOB_ITEMS` | `erp_job_items` | Yes |
| `JOB_STATUSES` | `erp_job_statuses` | Yes |
| `TASKS` | `erp_tasks` | Yes |

### Other (10 tables)

| Constant | Physical Table | Org-Scoped |
|----------|---------------|:----------:|
| `ALERTS` | `erp_alerts` | Yes |
| `NOTIFICATIONS` | `erp_notifications` | Yes |
| `AUDIT_LOG` | `erp_audit_log` | Yes |
| `ATTACHMENTS` | `erp_attachments` | Yes |
| `BANNED_WORDS` | `erp_banned_words` | No |
| `INQUIRIES` | `erp_inquiries` | No |
| `INQUIRY_REPLIES` | `erp_inquiry_replies` | No |
| `DISPOSABLE_EMAIL_DOMAINS` | `erp_disposable_email_domains` | No |
| `INDUSTRIES` | `erp_industries` | No |

---

## Polymorphic / Shared Tables

These physical tables serve multiple entity types via an `entity_type` column:

| Physical Table | Serves | Distinguisher |
|---------------|--------|---------------|
| `erp_contacts` | Customers, Vendors | `entity_type` = 'customer' / 'vendor' |
| `erp_addresses` | Customers, Vendors | `entity_type` = 'customer' / 'vendor' |
| `erp_attachments` | Users, Customers, Leads, Vendors | `entity_type` + `entity_id` |
| `erp_entity_logs` | Customers, Leads | `entity_type` = 'customer' / 'lead' |
| `erp_entity_notes` | Customers, Leads | `entity_type` = 'customer' / 'lead' |
| `erp_hs_code_mappings` | Categories, Subcategories | `entity_type` |
| `erp_document_types` | Sales, Purchases | `context` = 'sale' / 'purchase' |
| `erp_taxonomies` | Setup Groups, Sources, Statuses, Tags | `type` column |

---

## Org-Scoped vs Non-Scoped Tables

**Not org-scoped** (system-level, geography, auth):
`users`, `roles`, `permissions`, `module_permissions`, `authentication_activity`, `rate_limits`, `system_settings`, `modules`, `schema_migrations`, `backend_error_logs`, `backend_log_coverage`, `email_providers`, `geo_countries`, `geo_states`, `geo_cities`, `organizations`, `hscodes`, `subscription_plans`, `subscription_plan_features`, `banned_words`, `pages`, `inquiries`, `inquiry_replies`, `disposable_email_domains`, `industries`

All other tables are org-scoped via `organization_id` FK.

---

## Deprecated / Ghost Constants

| Constant | Status | Replacement |
|----------|--------|-------------|
| `HR_EMPLOYEES` | Merged | `USERS` with `employee_code`, `designation_id` |
| `FRONTEND_USERS` | Dropped | `USERS` |
| `FRONTEND_USER_FAVORITES` | Dropped | — |
| `SHIPPING_CUSTOMERS` | Dropped | `CUSTOMERS` with `entity_type='shipping'` |
| `SALE_TYPES` | Dropped | `DOCUMENT_TYPES` with `context='sale'` |
| `PURCHASE_TYPES` | Dropped | `DOCUMENT_TYPES` with `context='purchase'` |
| `LISTING_PLANS` | Dropped | `SUBSCRIPTION_PLANS` with `plan_type='listing'` |
| `LISTING_SUBSCRIPTIONS` | Dropped | `SUBSCRIPTIONS` |
| `SYSTEMS` | Merged | `MODULES` with `module_type='system'` |
| `TIMEZONES` | Config | `config/timezones.php` |
| `STORAGE_SUBTYPES` | Dropped | `STORAGE_TYPES` with `parent_id` |
| `COMPANIES` | Ghost | Never existed |
| `REFERRAL_CODES` | Ghost | Never existed |
| `SERVICES` | Ghost | Use `ITEMS` with `type='service'` (not yet) |
| `CONTAINER_TYPES` | Ghost | Not yet created |
| `COMMODITY_TYPES` | Ghost | Not yet created |
| `BALANCE_SHEET` | Ghost | Dynamic report, no table |
| `GENERAL_LEDGER` | Ghost | Dynamic report, no table |
| `TRIAL_BALANCE` | Ghost | Dynamic report, no table |

---

## Migration System

- **CLI runner:** `php database/migrate.php`
- **Migration files:** `database/migrations/` (versioned PHP files)
- **Schema auto-init:** `DatabaseSchemaInitializer` creates missing tables at runtime
- **Tracking table:** `erp_schema_migrations`
- **Helpers:** `src/Database/MigrationHelpers.php`

## DB Access Patterns

| Pattern | When to Use |
|---------|-------------|
| `DB::pdo()` — PDO wrapper (preferred) | All new code |
| `$mysqli` — MySQLi (legacy) | Existing dashboard pages only |
| `DB::mysqli()` — global MySQLi accessor | Legacy compat only |

**Always:** use `DB::*` constants, named parameters, `organization_id` scoping, and no `SELECT *`.
