# Migration Status & Remaining Work

> **Updated: 2026-06-15**

## Phase Status

| Phase | Description | Status |
|-------|------------|--------|
| P1-P7 | Core infrastructure | **100%** |
| P8 | Legacy cleanup | **~99%** |
| P14 | Dashboard page migration (~340 files) | **~28%** (~95/~340) |

**Remaining legacy:** `src/Core/DynamicPrefixMysqli.php` (kept for config/database.php)

## P14 — Dashboard Page Migration

| Type | Count | Migrated | Remaining |
|------|:----:|:--------:|:---------:|
| P14a: Existing controllers wired | 14 | 14 | 0 |
| P14b: Simple CRUD | 20 | 20 | 0 |
| P14c: Medium CRUD | 22 | 22 | 0 |
| P14d: Listing pages (DataTable) | ~87 | 20 | ~67 |
| P14e: Multi-line-item CRUD | 12 | 12 | 0 |
| P14e: File-upload CRUD | 3 | 3 | 0 |
| P14e: Child entity pages | 8 | 6 | 2 |
| P14e: Reports, overviews | ~85 | 0 | ~85 |
| **Total** | **~340** | **~95** | **~245** |

### P14a-c — CRUD Pages Complete

56 modules migrated with full Model + Repository + Service + Controller + View + Dashboard stacks. Session class (`App\Core\Session`) replaces verbose `$_SESSION[$GLOBALS['project_pre']]['DASHBOARD']` pattern.

### P14d — Listing Pages (~87 files)

20 of 87 use shared `listing_handler.php` for publish/unpublish/delete actions. Remaining ~67 files already have good architecture (Service layer, DeletionManager, or complex cascade deletes) and don't fit the generic delete pattern.

### P14e — Multi-line-item CRUD Complete

12 modules with full MVC stacks: expenses, credit_notes, debit_notes, purchases, purchase_orders, sale_orders, quotations, lead_quotations, jobs, shipping_advices, shipping_invoices, journals, recurring_invoices.

### P14e — File-Upload CRUD Complete

3 modules: user_documents, lead_attachments (full MVC), global_settings (Service extraction).

### P14e — Child Entity Pages (6/8)

Migrated: customer_contacts, customer_comments, lead_notes, customer_billing_addresses, customer_shipping_addresses. Display-only (not migrated): customer_transactions, lead_logs.

## Infrastructure

| Class | Purpose |
|-------|---------|
| `App\Core\Session` | Static session: `userId()`, `roleId()`, `orgId()`, `get()` |
| `App\Core\Container` | PSR-11 DI with `autowire()`, `register()` |
| `App\Core\Database` | PDO: `fetchOne()`, `fetchAll()`, `execute()`, `insert()` |
| `App\Core\DB` | Table constants, `getPrefix()`, `pdo()` |

## Error Handling & Coverage

100% entry-point coverage. `error_handler_init.php` registers 3 handlers (error, exception, fatal). 30 standalone files (login, pdf, ajax, api, cron) include it. `bootstrap.php` loads at line 97.

## Docs

| Location | Files | Purpose |
|----------|:-----:|---------|
| docs/ | 2 | Active (AGENTS.md + this file) |
| docs/archive/ | 6 | Historical reference |
