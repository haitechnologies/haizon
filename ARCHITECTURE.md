# Haizon ERP — Architecture Reference

> **Purpose:** Single-source-of-truth for developers and AI agents. Read this file first.

---

## 1. Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.2+ (`declare(strict_types=1)`) |
| Framework | None — custom platform, PSR-4 `App\` → `src/` |
| Frontend | Bootstrap 5.3, jQuery 3.7, DataTables.js |
| Database | MySQL 8.0+ / InnoDB / utf8mb4_unicode_ci |
| DB Prefix | `erp_` (rewritten at runtime via `DynamicPrefixPdo`) |
| Mail | PHPMailer 6.9 |
| PDF | TCPDF |
| Auth | Session-based + TOTP MFA |

---

## 2. Directory Map

```
haizon/
├── ARCHITECTURE.md          ← You are here
├── docs/
│   ├── DATABASE_AND_ARCHITECTURE_ANALYSIS.md  ← Full DB table audit
│   └── DATABASE_OPTIMIZATION_PLAN.md          ← Executed optimization plan
├── .cursorrules             ← AI coding rules (LSP)
├── .windsurfrules           ← AI coding rules (Windsurf)
├── composer.json            ← PSR-4 autoload: App\ → src/
├── index.php                ← Redirects to /dashboard/login.php
│
├── config/
│   ├── database.php         ← DB connection, tbl_* compat constants
│   ├── constants.php        ← PROJECT_PREFIX, session keys
│   ├── globals.php          ← 2800+ lines of procedural helpers
│   ├── session.php          ← Session/CSRF initialization
│   └── uae_geo_constants.php
│
├── src/                     ← PSR-4 namespace App\
│   ├── Controller/          ← Simple CRUD controllers (pilot)
│   ├── Core/                ← DB, Database, Container, MigrationRunner
│   ├── Database/            ← MigrationRunner, MigrationHelpers
│   ├── DataTable/           ← 70+ server-side DataTable handlers
│   │   ├── BaseDataTable.php    ← Abstract base with org-scoping
│   │   ├── GenericDataTable.php ← Config-driven fallback handler
│   │   ├── Registry.php         ← Module→Handler mapping
│   │   └── config.php           ← Generic handler configs
│   ├── Exception/           ← DomainException, NotFoundException, ValidationException
│   ├── Frontend/            ← Public-facing page logic
│   ├── Helper/              ← AuditHelper, BadgeHelper, JSONLDSchema
│   ├── Model/               ← Readonly DTOs (constructor promotion)
│   ├── Repository/          ← CRUD database operations
│   ├── Security/            ← Roles, RateLimiter, InputValidator, TOTP
│   └── Service/             ← Business logic (InvoiceService, etc.)
│
├── dashboard/               ← Admin backend (views + controllers)
│   ├── bootstrap.php        ← Session, CSP headers, tenant context
│   ├── admin_elements/      ← Shared layouts (header, sidebar, footer)
│   ├── api/                 ← AJAX API endpoints
│   ├── ajax/                ← Legacy AJAX handlers
│   ├── cron/                ← Cron job scripts
│   ├── dashboard_widgets/   ← Dashboard widget components
│   ├── datatables/          ← DataTable dispatcher
│   ├── helpers/             ← Page-specific helpers
│   │
│   │  Page naming convention:
│   │  ├── {module}.php              ← Form/editor page
│   │  ├── listing_{module}.php      ← List/grid page
│   │  ├── {parent}_{child}.php      ← Child entity within parent
│   │  └── listing_{parent}_{child}.php ← Child entity list
│
├── database/
│   ├── migrate.php          ← CLI migration runner
│   └── migrations/          ← Versioned schema migrations
│
├── tests/                   ← Test scripts
├── uploads/                 ← User uploads (protected)
├── assets/                  ← Public static assets
└── vendor/                  ← Composer dependencies
```

---

## 3. Architecture Layers

```
Controller (dashboard/*.php)  →  Service (src/Service/)  →  Repository (src/Repository/)  →  Model (src/Model/)
     ↓                              ↓                           ↓                              ↓
  HTTP/POST handling          Business validation         Database CRUD               Readonly DTOs
  Permission checks           Cross-entity logic          Prepared statements         Constructor promotion
  CSRF validation             Email/audit triggers        Org-scoping                 Type-safe fields
```

**Current status:** Pilot module (Departments) is fully migrated to this pattern. Other modules use monolithic dashboard pages.

---

## 4. Database Access

### Use PDO (preferred for new code)
```php
$db = \App\Core\DB::pdo();
$user = $db->fetchOne("SELECT id, email FROM " . DB::USERS . " WHERE id = :id", ['id' => $userId]);
```

### MySQLi is legacy (existing code only)
```php
global $mysqli;
$stmt = $mysqli->prepare("SELECT id FROM " . DB::USERS . " WHERE id = ?");
$stmt->bind_param('i', $userId);
```

### Rules
- **Always** use `DB::*` constants for table names
- **Always** use named parameters (`:id`) or positional placeholders (`?`)
- **Never** interpolate variables into SQL strings
- **Never** use `SELECT *` — specify columns
- **Always** scope by `organization_id` on tenant tables

---

## 5. Multi-Tenancy

- **Model:** Shared database, shared schema, row-level isolation
- **Scope:** `organization_id` column on 70 tables (all with FK → `erp_organizations`)
- **Enforcement:** `OrgIdInjectionMiddleware` auto-injects WHERE clauses
- **Session:** `$_SESSION['haizon']['DASHBOARD']['active_organization_id']`
- **Tables NOT org-scoped:** Users, roles, permissions, geography, system settings, SaaS plans

---

## 6. Permission System

```php
// Check by module_id (integer)
granted('view', $module_id);
granted('create', $module_id);
granted('edit', $module_id);
granted('delete', $module_id);

// Check by module slug (string)
granted_('view', 'customers');
granted_('edit', 'invoices');
```

- `module_id` resolved via `getModuleIdBySlug('customers', $mysqli)`
- Role-based: `Roles::SYSTEM_ADMIN`, `Roles::hasFullAccess($role_id)`

---

## 7. Key Files Quick Reference

| What | File |
|------|------|
| Table constants | `src/Core/DB.php` |
| PDO wrapper | `src/Core/Database.php` |
| MySQLi wrapper | `src/Core/DynamicPrefixMysqli.php` |
| Org middleware | `src/Core/OrgIdInjectionMiddleware.php` |
| Deletion (soft) | `src/Core/DeletionManager.php` |
| Roles | `src/Security/Roles.php` |
| Input validation | `src/Security/InputValidator.php` |
| CSRF helpers | `config/globals.php` → `validate_csrf_token()`, `csrf_field()` |
| Session init | `config/session.php` |
| DB connection | `config/database.php` |
| DataTable registry | `src/DataTable/Registry.php` |
| DataTable base | `src/DataTable/BaseDataTable.php` |
| Migration runner | `src/Database/MigrationRunner.php` |
| Migration helpers | `src/Database/MigrationHelpers.php` |

---

## 8. CLI Commands

```bash
php database/migrate.php              # Run pending migrations
php database/migrate.php --status     # Show migration status
php tests/run_all_tests.php           # Run test suite
vendor/bin/phpstan analyse            # Static analysis
vendor/bin/phpcs --standard=.phpcs.xml # Code style check
php -l <file>                         # Syntax check
```

---

## 9. Dashboard Page Template

Every dashboard page follows this pattern:

```php
<?php
// 1. Bootstrap & auth
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/admin_elements/admin_header.php';

// 2. Module setup
$module = 'customers';
$module_id = getModuleIdBySlug($module, $mysqli);

// 3. Permission check
if (!granted('view', $module_id)) { /* redirect or 403 */ }

// 4. CSRF validation on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token($_POST['csrf_token'] ?? '');
    // Handle action...
}

// 5. Page content (HTML with PHP)
require_once __DIR__ . '/admin_elements/footer.php';
?>
```

---

## 10. Database Table Categories

For the complete table inventory with org-scoping flags and alias mappings, see `docs/DATABASE_AND_ARCHITECTURE_ANALYSIS.md`.

**Current:** 123 tables, 70 org-scoped, all with FKs on `organization_id`, `created_by`, `updated_by`.
See `docs/DATABASE_OPTIMIZATION_PLAN.md` for full execution summary.

---

## 11. Shared/Polymorphic Tables

These physical tables serve multiple entity types via `entity_type` or context columns:

| Physical Table | Serves | Distinguishing Column |
|---------------|--------|----------------------|
| `erp_contacts` | Customers, Vendors | `entity_type` (customer/vendor) + FK |
| `erp_addresses` | Customers, Vendors | `entity_type` (customer/vendor) + FK |
| `erp_attachments` | Users, Customers, Leads, Vendors | `entity_type` + `entity_id` |
| `erp_entity_logs` | Customers, Leads | `entity_type` (customer/lead) |
| `erp_entity_notes` | Customers, Leads | `entity_type` (customer/lead) |
| `erp_hs_code_mappings` | Categories, Subcategories | `entity_type` |
| `erp_document_types` | Sales, Purchases | `context` (sale/purchase) |

---

## 12. Deprecated / Ghost Constants

These `DB.php` constants reference tables that were dropped (P1) or converted to PHP config (P10) or merged (P9). Do not use in new code:

| Constant | Status | Note |
|----------|--------|------|
| `SHIPPING_CUSTOMERS` | Dropped | Table dropped |
| `SALE_TYPES` | Dropped | Use `DB::DOCUMENT_TYPES` with `context='sale'` |
| `PURCHASE_TYPES` | Dropped | Use `DB::DOCUMENT_TYPES` with `context='purchase'` |
| `LISTING_PLANS` | Dropped | Table dropped |
| `LISTING_SUBSCRIPTIONS` | Dropped | Table dropped |
| `STORAGE_SUBTYPES` | Dropped | Table dropped |
| `USER_BLOCKS` | Dropped | Table dropped |
| `SYSTEMS` | Merged into MODULES | `DB::MODULES` with `module_type='system'` |
| `TIMEZONES` | PHP config | `config/timezones.php` |
| `COMPANIES` | Ghost | Never existed |
| `REFERRAL_CODES` | Ghost | Never existed |
| `HS_CODE_TEXTS` | Ghost | Never existed |
| `CONTAINER_TYPES` | Ghost | Never existed |
| `COMMODITY_TYPES` | Ghost | Never existed |
| `SERVICES` | Ghost | Never existed |
| `CATEGORY_ITEMS` | Ghost | Never existed |
| `BALANCE_SHEET` | Ghost | View/report, not a table |
| `GENERAL_LEDGER` | Ghost | View/report, not a table |
| `TRIAL_BALANCE` | Ghost | View/report, not a table |

---

*Keep this file updated when adding new modules, tables, or architectural patterns.*
