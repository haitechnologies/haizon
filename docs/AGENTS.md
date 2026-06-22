# Haizon ERP — AI Agent Context

> **Single source of truth for AI coding agents.**

## Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.2+ (`declare(strict_types=1)`) |
| Framework | None — custom platform, PSR-4 `App\` → `src/` |
| Frontend | Bootstrap 5.3, jQuery 3.7, DataTables.js |
| Database | MySQL 8.0+ / InnoDB / utf8mb4_unicode_ci / PDO |
| DB Prefix | `erp_` (rewritten at runtime via `DynamicPrefixPdo`) |
| Multi-Tenancy | Row-level isolation via `organization_id` on ~70 tables |
| Session | `App\Core\Session` — `Session::userId()`, `Session::roleId()`, `Session::orgId()` |
| DI | Custom PSR-11 container with reflection auto-wiring |

## Architecture

```
Controller (__invoke(Request): Response) → Service → Repository → Model (readonly DTO)
  Permission/CSRF checks           Business logic      SQL+PDO       Type-safe DTOs
  View::render()                   Validation          Org-scoping
```

## Directory Map

- `src/` — PSR-4 `App\` namespace (Http/, Core/, DataTable/, Exception/, Helper/, Model/, Repository/, Security/, Service/)
- `resources/views/` — PHP templates
- `dashboard/` — Admin backend (page controllers, admin_elements/, api/, ajax/, cron/)
- `config/` — globals.php (procedural helpers), database.php (DB connection), session.php
- `tests/`, `docs/`, `docs/archive/` — Tests, active refs, historical refs

## Key Classes

| Class | Location | Purpose |
|-------|----------|---------|
| `App\Core\Database` | src/Core/ | PDO wrapper: `fetchOne()`, `fetchAll()`, `execute()`, `insert()` |
| `App\Core\DB` | src/Core/ | Table constants (`DB::CUSTOMERS`), `getPrefix()`, `pdo()` |
| `App\Core\Container` | src/Core/ | PSR-11 DI container, `autowire()`, `register()`, `get()` |
| `App\Core\Session` | src/Core/ | Static: `userId()`, `roleId()`, `orgId()`, `get(key, default)` |
| `App\Http\Request` | src/Http/ | Wraps `$_GET/POST/SERVER/FILES`: `get()`, `post()`, `getInt()`, `has()` |
| `App\Http\Response` | src/Http/ | Immutable: `html()`, `json()`, `redirect()` |
| `App\Http\Controller\BaseController` | src/Http/Controller/ | `requiresModule()`, `canView/Edit/Create/Delete()`, `validateCsrf()` |
| `App\Core\View` | src/Core/ | Renderer: `render(template, data)`, `share(key, value)` |
| `App\DataTable\BaseDataTable` | src/DataTable/ | Abstract base for server-side DataTables |
| `App\DataTable\Registry` | src/DataTable/ | Module→Handler mapping for datatables_dispatcher.php |

## Database Rules

- Use `App\Core\Database` PDO wrapper with named params: `['id' => $id]`
- Use `DB::*` constants for table names — never hardcode
- Always scope by `organization_id` on tenant tables
- No `SELECT *` — specify columns
- No raw `$mysqli->query()` with interpolation

## Permission System

```php
granted('view', $module_id);      // By module_id (integer)
granted_('edit', 'customers');    // By module slug (string)
```

- Role-based: `Roles::SYSTEM_ADMIN`, `Roles::hasFullAccess($role_id)`
- In controllers: `$this->canView()`, `$this->canEdit()`, etc.

## Creating a New CRUD Page

1. **Model** (`src/Model/{Entity}.php`): `readonly class` with constructor promotion
2. **Repository** (`src/Repository/{Entity}Repository.php`): PDO CRUD, `DB::*` constants
3. **Service** (`src/Service/{Entity}Service.php`): Validation, business logic, throws `ValidationException`
4. **Controller** (`src/Http/Controller/{Entity}Controller.php`): Extends `BaseController`, `__invoke(Request): Response`
5. **View** (`resources/views/{module}/form.php`): Bootstrap 5 form, CSRF token, `include admin_header.php`
6. **Dashboard** (`dashboard/{module}.php`): 13-line dispatcher
7. **Register** in `dashboard/bootstrap.php`: autowire Repository + Service, register Controller with factory closure

## CLI Commands

- Tests: `php tests/run_all_tests.php` | Syntax: `php -l <file>`
- PHPStan: `vendor/bin/phpstan analyse` | PHPCS: `vendor/bin/phpcs --standard=.phpcs.xml`

## Strict Avoidance

- No `package.json`, npm, Node.js
- No PHP frameworks (Laravel, Symfony)
- No raw `$mysqli->query()` with interpolation, no `SELECT *`, no `@` error suppression
- No comments unless requested | No hard deletes (use `is_active = 0`)
- Global functions in `src/` are `function_exists()` guarded
- Use `SlugHelper::slugify()` not global `slugify()`

## Listing Template

`listing_template.php` accepts `$listingConfig` with keys: `module`, `module_caption`, `tbl_name`, `hide_add_button`, `error_message`, `success_message`, `dt_columns` (JSON), `dt_options`, `custom_dt_init`, `extra_js`, `after_card`. Handler: `listing_handler.php` provides AJAX delete.

## Page Header Standard

Title on left with help icon; action buttons on right. Use `d-flex align-items-center justify-content-between`. See `listing_invoices.php` (gold standard), `listing_template.php` (template version).

## Form Partials (`form_*.php`)

| Partial | Usage |
|---------|-------|
| `form_field_text.php` | Text/email/number inputs |
| `form_field_select.php` | Select dropdowns (supports `options_html`) |
| `form_field_textarea.php` | Textarea inputs |
| `form_field_date.php` | Date picker with calendar icon |
| `form_card_section.php` | Bootstrap card wrapper |
| `form_line_items_table.php` | Dynamic add/remove row table |

## Migration Status

See `docs/MIGRATION-AUDIT-REMAINING.md` for details.

- **P1-P7 (Core infra):** 100% | **P8 (Legacy cleanup):** ~99%
- **P14 (Dashboard migration):** ~28% (~95/~340)
- **P14a-c (CRUD):** 56 modules done | **P14d (Listing handler):** 20 files
- **P14e (Complex CRUD):** 20 modules migrated, ~95 new src/ files
- **Remaining:** `src/Core/DynamicPrefixMysqli.php` (kept for config/database.php)
- **Error coverage:** 100% — all 335+ entry points include `error_handler_init.php`
