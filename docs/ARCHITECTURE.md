# Haizon ERP — Architecture Reference

> **Single-source-of-truth for developers and AI agents.**

## 1. Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.2+ (`declare(strict_types=1)`) |
| Framework | None — custom platform, PSR-4 `App\` → `src/` |
| Frontend | Bootstrap 5.3, jQuery 3.7, DataTables.js |
| Database | MySQL 8.0+ / InnoDB / utf8mb4_unicode_ci |
| DB Prefix | `erp_` (rewritten at runtime via `DynamicPrefixPdo`) |
| Mail | PHPMailer 6.9 | PDF | TCPDF | Auth | Session-based + TOTP MFA |

## 2. Architecture Layers

```
Controller (dashboard/*.php) → Service (src/Service/) → Repository (src/Repository/) → Model (src/Model/)
  HTTP/POST handling          Business validation       Database CRUD               Readonly DTOs
  Permission checks           Cross-entity logic        Prepared statements         Constructor promotion
  CSRF validation             Email/audit triggers      Org-scoping                 Type-safe fields
```

## 3. Database Access

**PDO (preferred):** `\App\Core\DB::pdo()->fetchOne("SELECT id FROM " . DB::USERS . " WHERE id = :id", ['id' => $id])`

**MySQLi (legacy only):** `global $mysqli; $stmt = $mysqli->prepare("SELECT id FROM " . DB::USERS . " WHERE id = ?");`

**Rules:** Use `DB::*` constants, named params, org-scoping on tenant tables, specify columns, never interpolate.

## 4. Multi-Tenancy

Row-level isolation via `organization_id` on ~70 tables (FK → `erp_organizations`). Auto-scoped by `OrgIdInjectionMiddleware`. Session: `$_SESSION['haizon']['DASHBOARD']['active_organization_id']`.

## 5. Permission System

```php
granted('view', $module_id);      // By module_id (integer)
granted_('edit', 'customers');    // By module slug (string)
```

- Role-based: `Roles::SYSTEM_ADMIN`, `Roles::hasFullAccess($role_id)`

## 6. Key Files

| What | File |
|------|------|
| Table constants | `src/Core/DB.php` |
| PDO wrapper | `src/Core/Database.php` |
| Org middleware | `src/Core/OrgIdInjectionMiddleware.php` |
| Roles | `src/Security/Roles.php` |
| Input validation | `src/Security/InputValidator.php` |
| CSRF helpers | `config/globals.php` → `validate_csrf_token()`, `csrf_field()` |
| DataTable registry | `src/DataTable/Registry.php` |
| Migration runner | `src/Database/MigrationRunner.php` |

## 7. CLI Commands

```bash
php database/migrate.php                    # Run pending migrations
php database/migrate.php --status           # Show migration status
php tests/run_all_tests.php                 # Run test suite
vendor/bin/phpstan analyse                  # Static analysis
vendor/bin/phpcs --standard=.phpcs.xml       # Code style check
php -l <file>                               # Syntax check
```

## 8. Dashboard Page Template

```php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/admin_elements/admin_header.php';
$module = 'customers';
$module_id = getModuleIdBySlug($module, $mysqli);
if (!granted('view', $module_id)) { /* redirect or 403 */ }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token($_POST['csrf_token'] ?? '');
}
// HTML content
require_once __DIR__ . '/admin_elements/footer.php';
```

## 9. Database Table Categories

Complete catalog at `docs/DATABASE.md`. 123 tables, 70 org-scoped.

## 10. Shared/Polymorphic Tables

| Physical Table | Serves | Distinguishing Column |
|---------------|--------|----------------------|
| `erp_contacts` | Customers, Vendors | `entity_type` + FK |
| `erp_addresses` | Customers, Vendors | `entity_type` + FK |
| `erp_attachments` | Users, Customers, Leads, Vendors | `entity_type` + `entity_id` |
| `erp_entity_logs` | Customers, Leads | `entity_type` |
| `erp_entity_notes` | Customers, Leads | `entity_type` |
| `erp_hs_code_mappings` | Categories, Subcategories | `entity_type` |
| `erp_document_types` | Sales, Purchases | `context` (sale/purchase) |

## 11. Deprecated Constants

Do not use: `SHIPPING_CUSTOMERS`, `SALE_TYPES`, `PURCHASE_TYPES`, `LISTING_PLANS`, `LISTING_SUBSCRIPTIONS`, `STORAGE_SUBTYPES`, `USER_BLOCKS`, `SYSTEMS` (use `MODULES`), `TIMEZONES` (use `config/timezones.php`). `COMPANIES`, `REFERRAL_CODES`, `HS_CODE_TEXTS`, `CONTAINER_TYPES`, `COMMODITY_TYPES`, `SERVICES`, `CATEGORY_ITEMS`, `BALANCE_SHEET`, `GENERAL_LEDGER`, `TRIAL_BALANCE` are ghost constants (never existed or are views).
