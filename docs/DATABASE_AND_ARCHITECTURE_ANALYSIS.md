# Haipulse ERP — Database & Architecture Analysis

> **Purpose:** Comprehensive audit of database table count, consolidation opportunities, and codebase improvements for LLM/AI agent interaction.
> **Date:** 2026-06-11
> **Scope:** Analysis only — no implementation changes proposed here.

---

## 1. Executive Summary

The Haipulse ERP database contains **~115 active physical tables** (plus ~7 deprecated backward-compat constants). For a full-featured ERP covering accounting, shipping, HR/payroll, CRM, multi-tenant SaaS, and subscription management, this count is **appropriate and lean**. Comparable open-source ERPs (Odoo: 500+, ERPNext: 400+) have significantly more tables for similar feature sets.

**Bottom line: Do NOT reduce the table count for the sake of reducing it.** Only 3–4 minor consolidation candidates exist, and they carry migration risk for marginal benefit.

---

## 2. Complete Table Inventory

### Legend
- ✅ Active & well-structured
- ⚠️ Has issues or inconsistencies noted
- 🔄 Merged/consolidated (deprecated constant still in DB.php)
- 📦 Org-scoped (has `organization_id`)

### 2.1 User & Authentication (6 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `USERS` | `erp_users` | ✅ | No |
| `ROLES` | `erp_roles` | ✅ | No |
| `PERMISSIONS` | `erp_permissions` | ✅ | No |
| `MODULE_PERMISSIONS` | `erp_module_permissions` | ✅ | No |
| `AUTHENTICATION_ACTIVITY` | `erp_authentication_activity` | ✅ | No |
| `RATE_LIMIT_ATTEMPTS` / `RATE_LIMIT_PUBLIC` | `erp_rate_limits` | ⚠️ Two constants → one table | No |

### 2.2 Multi-Tenancy & Organizations (6 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `ORGANIZATIONS` | `erp_organizations` | ✅ | Root |
| `ORGANIZATION_MEMBERSHIPS` | `erp_organization_memberships` | ✅ | Yes |
| `ORGANIZATION_ROLES` | `erp_organization_roles` | ✅ | Yes |
| `ORGANIZATION_MEMBER_ROLES` | `erp_organization_member_roles` | ✅ | Yes |
| `ORGANIZATION_INVITES` | `erp_organization_invites` | ✅ | Yes |
| `ORGANIZATION_SYSTEM_ENTITLEMENTS` | `erp_organization_system_entitlements` | ✅ | Yes |

### 2.3 HR & Payroll (10 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `DEPARTMENTS` | `erp_departments` | ✅ 📦 | Yes |
| `DESIGNATIONS` | `erp_designations` | ✅ 📦 | Yes |
| `ATTENDANCE` | `erp_attendance` | ✅ 📦 | Yes |
| `LEAVE_REQUESTS` | `erp_leave_requests` | ✅ 📦 | Yes |
| `LEAVE_TYPES` | `erp_leave_types` | ✅ 📦 | Yes |
| `PAYROLL_COMPONENTS` | `erp_payroll_components` | ✅ 📦 | Yes |
| `SALARY_STRUCTURES` | `erp_salary_structures` | ✅ 📦 | Yes |
| `EMPLOYEE_SALARIES` | `erp_employee_salaries` | ✅ 📦 | Yes |
| `PAYROLL_RUNS` | `erp_payroll_runs` | ✅ 📦 | Yes |
| `PAYSLIPS` | `erp_payslips` | ✅ 📦 | Yes |

### 2.4 CRM — Customers & Leads (7 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `CUSTOMERS` | `erp_customers` | ✅ 📦 | Yes |
| `CUSTOMER_CONTACTS` | `erp_contacts` | ✅ 📦 | Yes (shared with vendors) |
| `CUSTOMER_ADDRESSES` | `erp_addresses` | ✅ 📦 | Yes (shared with vendors) |
| `ENTITY_LOGS` | `erp_entity_logs` | ✅ 📦 | Yes |
| `ENTITY_NOTES` | `erp_entity_notes` | ✅ 📦 | Yes |
| `LEADS` | `erp_leads` | ✅ 📦 | Yes |
| `ATTACHMENTS` | `erp_attachments` | ✅ 📦 | Yes (shared: user docs, customer docs, lead docs) |

### 2.5 Sales & Invoicing (2 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `INVOICES` | `erp_invoices` | ✅ 📦 | Yes |
| `INVOICE_ITEMS` | `erp_invoice_items` | ✅ 📦 | Yes |

### 2.6 Accounting — Chart of Accounts (3 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `ACCOUNTS` | `erp_accounts` | ✅ 📦 | Yes |
| `ACCOUNTS_REPORT_CATEGORIES` | `erp_accounts_report_categories` | ✅ | No |
| `ACCOUNTS_REPORT_SUBCATEGORIES` | `erp_accounts_report_subcategories` | ✅ | No |

### 2.7 Accounting — Journals (2 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `JOURNALS` | `erp_journals` | ✅ 📦 | Yes |
| `JOURNAL_ITEMS` | `erp_journal_items` | ✅ 📦 | Yes |

### 2.8 Accounting — Sales Transactions (8 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `QUOTATIONS` | `erp_quotations` | ✅ 📦 | Yes |
| `QUOTATION_ITEMS` | `erp_quotation_items` | ✅ 📦 | Yes |
| `SALE_ORDERS` | `erp_sale_orders` | ✅ 📦 | Yes |
| `SALE_ORDER_ITEMS` | `erp_sale_order_items` | ✅ 📦 | Yes |
| `DOCUMENT_TYPES` | `erp_document_types` | ✅ 📦 | Yes (unified sale+purchase) |
| `PAYMENTS_RECEIVED` | `erp_payments_received` | ✅ 📦 | Yes |
| `CREDIT_NOTES` | `erp_credit_notes` | ✅ 📦 | Yes |
| `CREDIT_NOTE_ITEMS` | `erp_credit_note_items` | ✅ 📦 | Yes |

### 2.9 Accounting — Purchase Transactions (10 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `VENDORS` | `erp_vendors` | ✅ 📦 | Yes |
| `VENDOR_CONTACTS` | `erp_contacts` | ⚠️ Shared physical table with customers | Yes |
| `VENDOR_ADDRESSES` | `erp_addresses` | ⚠️ Shared physical table with customers | Yes |
| `PURCHASES` | `erp_purchases` | ✅ 📦 | Yes |
| `PURCHASE_ITEMS` | `erp_purchase_items` | ✅ 📦 | Yes |
| `PURCHASE_ORDERS` | `erp_purchase_orders` | ✅ 📦 | Yes |
| `PURCHASE_ORDER_ITEMS` | `erp_purchase_order_items` | ✅ 📦 | Yes |
| `PAYMENTS_MADE` | `erp_payments_made` | ✅ 📦 | Yes |
| `DEBIT_NOTES` | `erp_debit_notes` | ✅ 📦 | Yes |
| `DEBIT_NOTE_ITEMS` | `erp_debit_note_items` | ✅ 📦 | Yes |

### 2.10 Accounting — Expenses & Banking (6 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `EXPENSES` | `erp_expenses` | ✅ 📦 | Yes |
| `BANKS` | `erp_banks` | ✅ 📦 | Yes |
| `TAX_TREATMENTS` | `erp_tax_treatments` | ✅ | No |
| `PAYMENT_TERMS` | `erp_payment_terms` | ✅ | No |
| `CURRENCIES` | `erp_currencies` | ✅ | No |
| `PAYMENT_METHODS` | `erp_payment_methods` | ✅ | No |

### 2.11 Shipping & Logistics (11 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `SHIPPING_ADVICES` | `erp_shipping_advices` | ✅ 📦 | Yes |
| `SHIPPING_ADVICE_ITEMS` | `erp_shipping_advice_items` | ✅ 📦 | Yes |
| `SHIPPING_INVOICES` | `erp_shipping_invoices` | ✅ 📦 | Yes |
| `SHIPPING_INVOICE_ITEMS` | `erp_shipping_invoice_items` | ✅ 📦 | Yes |
| `SHIPPING_STOCKS` | `erp_shipping_stocks` | ✅ 📦 | Yes |
| `SHIPPING_STOCK_ITEMS` | `erp_shipping_stock_items` | ✅ 📦 | Yes |
| `PORTS` | `erp_ports` | ✅ 📦 | Yes |
| `CARRIERS` | `erp_carriers` | ✅ 📦 | Yes |
| `CONSIGNEES` | `erp_consignees` | ✅ 📦 | Yes |
| `SHIPPERS` | `erp_shippers` | ✅ 📦 | Yes |

### 2.12 Geography (3 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `GEO_COUNTRIES` | `erp_geo_countries` | ✅ | No |
| `GEO_STATES` | `erp_geo_states` | ✅ | No |
| `GEO_CITIES` | `erp_geo_cities` | ✅ | No |

### 2.13 Content, Categories & Taxonomies (9 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `TAXONOMIES` | `erp_taxonomies` | ✅ 📦 | Yes (polymorphic) |
| `CATEGORIES` | `erp_categories` | ✅ 📦 | Yes |
| `SUBCATEGORIES` | `erp_subcategories` | ✅ 📦 | Yes |
| `CATEGORY_ITEMS` | `erp_category_items` | ✅ 📦 | Yes |
| `HS_CODES` | `erp_hscodes` | ✅ | No |
| `HS_CODE_TEXTS` | `erp_hscodes_texts` | ✅ | No |
| `CATEGORY_HS_CODES` / `SUBCATEGORY_HS_CODES` | `erp_hs_code_mappings` | ⚠️ Two constants → one table | No |
| `PAGES` | `erp_pages` | ✅ | No |
| `BANNED_WORDS` | `erp_banned_words` | ✅ | No |

### 2.14 Email System (3 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `EMAIL_PROVIDERS` | `erp_email_providers` | ✅ | No |
| `EMAIL_HISTORY` | `erp_email_history` | ✅ | No |
| `EMAIL_QUEUE` | `erp_email_queue` | ✅ | No |

### 2.15 SaaS Subscriptions (6 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `SUBSCRIPTION_PLANS` | `erp_subscription_plans` | ✅ | No (global catalog) |
| `SUBSCRIPTIONS` | `erp_subscriptions` | ✅ | Yes |
| `SUBSCRIPTION_PLAN_FEATURES` | `erp_subscription_plan_features` | ✅ | No |
| `SUBSCRIPTION_OVERRIDES` | `erp_subscription_overrides` | ✅ | No |
| `SUBSCRIPTION_LOGS` | `erp_subscription_logs` | ✅ | No |
| `API_KEYS` | `erp_api_keys` | ✅ | No |

### 2.16 Operational Setup (10 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `INCOTERMS` | `erp_incoterms` | ✅ | No |
| `EXIT_POINTS` | `erp_exit_points` | ✅ | No |
| `CONTAINER_TYPES` | `erp_container_types` | ✅ | No |
| `COMMODITY_TYPES` | `erp_commodity_types` | ✅ | No |
| `WAREHOUSES` | `erp_organizations` | ⚠️ Alias to ORGANIZATIONS | Yes |
| `STORAGE_TYPES` | `erp_storage_types` | ✅ | Yes |
| `SERVICES` | `erp_services` | ✅ | No |
| `UNITS` | `erp_units` | ✅ | No |
| `ITEMS` | `erp_items` | ✅ 📦 | Yes |
| `DOCUMENT_CATEGORIES` | `erp_document_categories` | ✅ | No |

### 2.17 System & Monitoring (4 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `SYSTEM_SETTINGS` | `erp_system_settings` | ✅ | No |
| `MODULES` | `erp_modules` | ✅ | No |
| `BACKEND_ERROR_LOGS` | `erp_backend_error_logs` | ✅ | No |
| `BACKEND_LOG_COVERAGE` | `erp_backend_log_coverage` | ✅ | No |

### 2.18 CRM — Projects & Jobs (3 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `PROJECTS` | `erp_projects` | ✅ | Yes |
| `JOBS` | `erp_jobs` | ✅ | Yes |
| `JOB_STATUSES` | `erp_job_statuses` | ✅ 📦 | Yes |

### 2.19 Other (3 tables)

| Constant | Physical Table | Status | Org-Scoped |
|----------|---------------|--------|------------|
| `INQUIRIES` | `erp_inquiries` | ✅ | No |
| `INQUIRY_REPLIES` | `erp_inquiry_replies` | ✅ | No |
| `DISPOSABLE_EMAIL_DOMAINS` | `erp_disposable_email_domains` | ✅ | No |
| `AUDIT_LOG` | `erp_audit_log` | ✅ | Yes |
| `COMPANIES` | `erp_companies` | 🔄 Dropped in Phase 1 | — |
| `REFERRAL_CODES` | `erp_referral_codes` | ✅ | No |
| `ALERTS` | `erp_alerts` | ✅ 📦 | Yes |

### 2.20 Auto-Created / Schema-Init Tables

| Physical Table | Created By | Status |
|---------------|-----------|--------|
| `erp_schema_migrations` | MigrationRunner | ✅ (infrastructure) |
| `erp_rate_limits` | DatabaseSchemaInitializer | ✅ |
| `erp_email_queue` | DatabaseSchemaInitializer | ✅ |
| `erp_inquiries` | DatabaseSchemaInitializer | ✅ |
| `erp_inquiry_replies` | DatabaseSchemaInitializer | ✅ |
| `erp_disposable_email_domains` | DatabaseSchemaInitializer | ✅ |
| `erp_backend_error_logs` | DatabaseSchemaInitializer | ✅ |
| `erp_backend_log_coverage` | DatabaseSchemaInitializer | ✅ |

### 2.21 Deprecated Constants (kept in DB.php for backward compat)

| Constant | Physical Table | Notes |
|----------|---------------|-------|
| `ERROR_LOG_STATUS` | `erp_error_log_status` | Merged into `BACKEND_ERROR_LOGS` |
| `LISTING_PLANS` | `erp_listing_plans` | Merged into `SUBSCRIPTION_PLANS` |
| `LISTING_SUBSCRIPTIONS` | `erp_listing_subscriptions` | Merged into `SUBSCRIPTIONS` |
| `SHIPPING_CUSTOMERS` | `erp_shipping_customers` | Merged into `CUSTOMERS` |
| `SALE_TYPES` | `erp_sale_types` | Merged into `DOCUMENT_TYPES` |
| `PURCHASE_TYPES` | `erp_purchase_types` | Merged into `DOCUMENT_TYPES` |
| `STORAGE_SUBTYPES` | `erp_storage_subtypes` | Merged into `STORAGE_TYPES` |
| `COMPANIES` | `erp_companies` | Dropped entirely |

**Total unique physical tables: ~115 active + 8 deprecated/shadow**

---

## 3. Consolidation Analysis — What CAN Be Merged

### 3.1 `erp_setup_statuses` / `erp_setup_sources` / `erp_setup_tags` → `erp_taxonomies`

**Verdict: CONFLICT — Resolve immediately.**

- `docs/AGENTS.md` line 38 says: *"DO NOT create or use legacy setup tables (`erp_setup_groups`, `erp_setup_priorities`, `erp_setup_statuses`, `erp_setup_sources`, `erp_setup_tags`). These are decommissioned and consolidated into the polymorphic `erp_taxonomies` table."*
- However, recent migrations (`2026_06_11_001` through `2026_06_11_004`) are **recreating** `erp_setup_statuses`, `erp_setup_tags`, and `erp_job_statuses` as standalone tables with triggers.
- The `DataTable/Registry.php` still registers handlers for `listing_setup_sources`, `listing_setup_statuses`, `listing_setup_tags`.
- Dashboard pages (`setup_statuses.php`, `setup_sources.php`, `setup_tags.php`) still exist and are fully functional.

**Recommendation:** Choose ONE approach and commit:
- **Option A (Consolidate into taxonomies):** Remove the 3 recreated tables, migrate data to `erp_taxonomies` with `type` column, update all dashboard pages and DataTables to query `erp_taxonomies`. Delete the migration files.
- **Option B (Keep standalone tables):** Update `docs/AGENTS.md` to remove the prohibition. These tables serve a specific UI purpose (manageable via CRUD pages with triggers for `publish`/`is_active` sync) and are simpler for non-technical users than a polymorphic taxonomy table.

**My recommendation: Option B** — Keep the standalone tables. The taxonomies table adds complexity without clear benefit for a small number of status/source/tag types. The triggers ensure `publish` ↔ `is_active` sync, which is a clean pattern.

### 3.2 `erp_setup_groups` → `erp_taxonomies`

The `setup_groups` migration doesn't exist (no `2026_06_11_00X` file for it), but the DataTable handler `SetupGroupsDataTable` and page `setup_groups.php` still exist. Either recreate it like the others or remove the handler and page.

### 3.3 `erp_contacts` / `erp_addresses` — Shared Tables (Customers + Vendors)

**Verdict: Keep as-is.** These tables use a polymorphic pattern where the same physical table (`erp_contacts`, `erp_addresses`) serves both customers and vendors. The `DB.php` class defines separate constants (`CUSTOMER_CONTACTS` → `erp_contacts`, `VENDOR_CONTACTS` → `erp_contacts`) that point to the same physical table. This is a valid and space-efficient pattern.

**Risk for LLMs:** An AI agent might assume these are separate tables. Document this explicitly.

### 3.4 `erp_geo_countries` + `erp_geo_states` — Could Use Config Constants

**Verdict: Keep as-is.** The `config/uae_geo_constants.php` already hardcodes UAE geography. The database tables serve global geography needs (shipping origins/destinations). No consolidation needed.

### 3.5 `erp_tax_treatments` + `erp_payment_terms` + `erp_payment_methods` — Small Lookup Tables

**Verdict: Keep as-is.** These are small, independent lookup tables. Merging them into a single "finance_config" table would add complexity without benefit. Each has distinct UI pages and distinct FK relationships.

### 3.6 `erp_accounts_report_categories` + `erp_accounts_report_subcategories`

**Verdict: Could merge into single table with `parent_id`.** However, these are accounting-specific groupings used by the chart of accounts, and the two-level hierarchy is standard accounting practice. Keep as-is.

---

## 4. Tables That SHOULD Stay As-Is (Justification)

### 4.1 Header/Line-Item Pairs (14 tables → 7 pairs)

These are the backbone of the ERP and **cannot** be merged:

| Header | Line Items | Why Separate |
|--------|-----------|--------------|
| `erp_invoices` | `erp_invoice_items` | Standard accounting pattern |
| `erp_quotations` | `erp_quotation_items` | Same |
| `erp_sale_orders` | `erp_sale_order_items` | Same |
| `erp_purchases` | `erp_purchase_items` | Same |
| `erp_purchase_orders` | `erp_purchase_order_items` | Same |
| `erp_journals` | `erp_journal_items` | Same |
| `erp_shipping_advices` | `erp_shipping_advice_items` | Same |
| `erp_shipping_invoices` | `erp_shipping_invoice_items` | Same |
| `erp_shipping_stocks` | `erp_shipping_stock_items` | Same |
| `erp_credit_notes` | `erp_credit_note_items` | Same |
| `erp_debit_notes` | `erp_debit_note_items` | Same |

**Verdict: Essential.** Every ERP uses this pattern. Flattening line items into JSON columns would destroy queryability.

### 4.2 Organization/Tenancy Tables (6 tables)

These are the multi-tenancy backbone. Cannot be reduced.

### 4.3 HR & Payroll (10 tables)

Each serves a distinct HR function. Payroll alone requires components → structures → assignments → runs → payslips (5 tables minimum). Keep all.

### 4.4 Shipping & Logistics (11 tables)

Shipping is a distinct domain with its own document types. The 6 line-item tables (advice, invoice, stock) follow the same header/item pattern. Carriers, consignees, shippers, ports are distinct entity types. Keep all.

---

## 5. Inconsistencies & Issues Found

### 5.1 Critical: `AGENTS.md` vs Reality Contradiction

**File:** `docs/AGENTS.md` line 38
```
DO NOT create or use legacy setup tables (erp_setup_groups, erp_setup_priorities,
erp_setup_statuses, erp_setup_sources, erp_setup_tags). These are decommissioned
and consolidated into the polymorphic erp_taxonomies table.
```

**Reality:** Migrations `2026_06_11_001` through `2026_06_11_004` recreate these tables. DataTable handlers and dashboard pages still reference them.

**Impact on LLMs:** An AI agent following AGENTS.md will refuse to work with setup tables, breaking existing functionality.

**Fix:** Update `docs/AGENTS.md` to reflect the actual state.

### 5.2 Stale Deprecated Constants in `DB.php`

The following constants exist in `src/Core/DB.php` but their physical tables were dropped or merged:

| Constant | Issue |
|----------|-------|
| `COMPANIES` | Table dropped in Phase 1 migration |
| `SHIPPING_CUSTOMERS` | Merged into `CUSTOMERS` |
| `SALE_TYPES` | Merged into `DOCUMENT_TYPES` |
| `PURCHASE_TYPES` | Merged into `DOCUMENT_TYPES` |
| `LISTING_PLANS` | Merged into `SUBSCRIPTION_PLANS` |
| `LISTING_SUBSCRIPTIONS` | Merged into `SUBSCRIPTIONS` |
| `STORAGE_SUBTYPES` | Merged into `STORAGE_TYPES` |
| `ERROR_LOG_STATUS` | Merged into `BACKEND_ERROR_LOGS` |

**Impact:** LLMs see these constants and assume the tables exist, leading to failed queries or unnecessary code.

**Fix:** Add `@deprecated` PHPDoc to each, with migration target noted. Or remove them if no code references remain.

### 5.3 Aliased Constants Pointing to Same Table

| Constants | Physical Table |
|-----------|---------------|
| `RATE_LIMIT_ATTEMPTS`, `RATE_LIMIT_PUBLIC` | `erp_rate_limits` |
| `CUSTOMER_CONTACTS`, `VENDOR_CONTACTS` | `erp_contacts` |
| `CUSTOMER_ADDRESSES`, `VENDOR_ADDRESSES` | `erp_addresses` |
| `ATTACHMENTS`, `USER_DOCUMENTS`, `LEAD_ATTACHMENTS`, `VENDOR_ATTACHMENTS` | `erp_attachments` |
| `CATEGORY_HS_CODES`, `SUBCATEGORY_HS_CODES` | `erp_hs_code_mappings` |
| `DEPARTMENTS`, `DEPARTMENT` | `erp_departments` |

**Impact:** LLMs may create separate table references or assume data isolation where none exists.

**Fix:** Document all aliases in a single table within `DB.php` or in this analysis doc.

### 5.4 `config/database.php` — Still Defines 96 `tbl_*` Constants

Lines 285–395 of `config/database.php` define backward-compatibility `tbl_*` constants. These are legacy aliases.

**Impact:** LLMs see both `DB::CUSTOMERS` and `tbl_customers` and may use either inconsistently.

**Fix:** Deprecation notice + eventual removal.

### 5.5 Mixed Database APIs

The codebase uses both **MySQLi** (via `App\Core\DynamicPrefixMysqli`) and **PDO** (via `App\Core\Database`). The `OrgIdInjectionMiddleware` handles both. The migration system uses MySQLi only.

**Impact:** LLMs may generate code for the wrong API. A new developer or AI agent needs to know which to use when.

**Fix:** Document clearly: "New code must use PDO (`DB::pdo()`). MySQLi is legacy."

### 5.6 Monolithic Dashboard Files

Files like `quotations.php` (~194KB), `sale_orders.php` (~196KB) are too large for LLM context windows. An AI agent reading these files will hit context limits.

**Impact:** LLMs cannot analyze or modify these files in a single pass.

**Fix:** Long-term: Extract business logic into Service classes. Short-term: Add a `dashboard/README.md` explaining the file structure pattern (header guard → action handler → form → view).

---

## 6. LLM/AI Codebase Optimization Recommendations

### 6.1 Create a Root-Level `ARCHITECTURE.md`

The existing docs are scattered:
- `docs/AGENTS.md` — Agent rules (39 lines, outdated)
- `docs/codebase_and_db_summary.md` — DB summary (46 lines, partially outdated)
- `docs/MULTI_TENANT_ARCHITECTURE.md` — Multi-tenant docs (156 lines, good)

**Recommendation:** Create a single `ARCHITECTURE.md` at project root that consolidates:
1. Project structure map
2. Database table registry (all tables, domains, org-scoping)
3. Layer responsibilities (Controller → Service → Repository → Model)
4. Which API to use (PDO vs MySQLi)
5. Permission model
6. Common patterns with code snippets

### 6.2 Add `DB.php` Table Groups for Quick Reference

Add static methods to `DB.php`:

```php
public static function getOrgScopedTables(): array { ... }
public static function getSharedTables(): array { ... }
public static function getDeprecatedConstants(): array { ... }
```

This gives LLMs programmatic access to table classification.

### 6.3 Standardize File Naming for Dashboard Pages

Current pattern is inconsistent:
- Form editors: `customers.php`, `invoices.php`
- List views: `listing_customers.php`, `listing_invoices.php`
- But some have: `customer_contacts.php`, `customer_billing_addresses.php` (no listing_ prefix)

**Recommendation:** Document the naming convention explicitly:
- `{module}.php` = form/editor page
- `listing_{module}.php` = list/grid page
- `{parent}_{child}.php` = child entity page within parent context

### 6.4 Add Inline `@db-table` Annotations

For LLM-friendly code, add PHPDoc annotations to dashboard files:

```php
/**
 * @db-table erp_quotations, erp_quotation_items
 * @org-scoped true
 * @permissions quotations
 * @see src/Service/QuotationService.php
 */
```

This allows LLMs to understand table relationships without reading the full file.

### 6.5 Create `database/SCHEMA.md` — Auto-Generated Schema Reference

A simple PHP script could query `information_schema` and generate a markdown file with:
- All tables, columns, types, indexes
- Foreign key relationships
- Org-scoped flag per table

Run it periodically (or as a migration post-hook) to keep the doc in sync.

### 6.6 Consolidate Config Files for LLM Context

When an LLM needs to understand the project, it currently needs to read:
1. `.cursorrules` / `.windsurfrules` — Coding rules
2. `docs/AGENTS.md` — Agent context
3. `docs/codebase_and_db_summary.md` — DB summary
4. `docs/MULTI_TENANT_ARCHITECTURE.md` — Multi-tenant docs
5. `config/database.php` — Table constants
6. `src/Core/DB.php` — Table registry

**Recommendation:** Create a single `.context/` directory:
```
.context/
  rules.md          ← Merged .cursorrules + .windsurfrules + AGENTS.md
  database.md       ← Complete table inventory with relationships
  architecture.md   ← Layers, patterns, APIs
  conventions.md    ← Naming, file structure, coding patterns
```

This gives LLMs a **single place** to load project context.

### 6.7 DataTable Registry — Document All Handlers

The `Registry.php` has ~70 registered handlers. Add a comment block at the top listing all registered modules and their handler classes, so LLMs can find the right handler without reading the entire file.

### 6.8 Migration Files — Add Relationship Metadata

Each migration file should have a header comment:

```php
/**
 * Tables affected: erp_setup_statuses
 * Depends on: erp_organizations
 * Org-scoped: Yes
 * Rollback: DROP TABLE erp_setup_statuses
 */
```

---

## 7. Summary — What To Do

| Priority | Action | Effort | Impact |
|----------|--------|--------|--------|
| 🔴 High | Fix AGENTS.md contradiction about setup tables | 10 min | Prevents LLM confusion |
| 🔴 High | Document all constant aliases in DB.php | 30 min | Prevents wrong-table bugs |
| 🟡 Medium | Create root-level ARCHITECTURE.md | 2 hours | Major LLM productivity boost |
| 🟡 Medium | Add @db-table annotations to dashboard files | 3 hours | Helps LLMs navigate code |
| 🟡 Medium | Mark deprecated DB.php constants clearly | 20 min | Prevents stale references |
| 🟢 Low | Create database/SCHEMA.md auto-generator | 1 hour | Keeps docs in sync |
| 🟢 Low | Consolidate .context/ directory for LLMs | 2 hours | Single-source-of-truth |
| 🟢 Low | Document file naming conventions | 30 min | Reduces ambiguity |

**Do NOT:**
- Merge header/line-item table pairs (destroys queryability)
- Merge domain-specific tables (shipping, HR, accounting) — they serve distinct business functions
- Reduce geography, setup, or lookup tables — they're already minimal
- Add a framework or ORM — the custom approach is working and is well-understood by the team

---

*This document is analysis-only. No implementation changes are proposed. Review and decide on priorities before proceeding with any changes.*
