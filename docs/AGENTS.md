# Haipulse ERP Agent Rules & Context

## Project Identity
- **Stack:** PHP 8.2+ (no framework), Bootstrap 5, jQuery 3.7, MySQL 8.0+ (PDO, no ORM, Repository pattern). PSR-4 `App\` -> `src/`.
- **Database:** InnoDB, `utf8mb4_unicode_ci`, dynamic prefix `erp_` (rewrite at runtime).
- **Multi-Tenancy:** Row isolation via `organization_id` on scoped tables, auto-injected by `OrgIdInjectionMiddleware`.
- **Session Keys:** `$_SESSION['haipulse']['DASHBOARD']`.

## Directory Map
- `src/`: Namespace `App\` (OOP layer: Core, DataTable, Exception, Frontend, Helper, Model, Repository, Security, Service).
- `dashboard/`: Admin backend (views/ views templates, api/ endpoints, pages/ controllers).
- `config/`: globals.php (procedural helpers), database.php (DB connection), session.php (sessions, CSRF).

## Coding Conventions
- Start all files with `declare(strict_types=1);`.
- Use table constants from `App\Core\DB` (e.g. `DB::CUSTOMERS`) instead of hardcoding `'erp_customers'`.
- Database access: Use `App\Core\Database` PDO wrapper with named parameters `['id' => $id]`. No raw queries with interpolation.
- CSRF validation: Run `validate_csrf_token($_POST['csrf_token'] ?? '')` for all POST requests.
- Authorization: Check via `granted('action', $module_id)` or `granted_('action', 'module_slug')`.
- Models: Readonly PHP 8.2+ classes with constructor promotion.
- Exceptions: Throw typed exceptions under `App\Exception\`. No `die()` / `exit()` in `src/`.
- Deletion: Soft delete (`is_active = 0`), never hard delete.

## CLI Commands
- Run tests: `php tests/run_all_tests.php`
- Run migration: `php database/migrate.php`
- PHP Syntax check: `php -l <file>`
- PHPStan: `vendor/bin/phpstan analyse`
- PHPCS: `vendor/bin/phpcs --standard=.phpcs.xml`

## Strict Avoidance List
- DO NOT add `package.json`, npm, or Node.js toolchains.
- DO NOT add any PHP frameworks (Laravel, Symfony).
- DO NOT use raw `$mysqli->query()` with string interpolation.
- DO NOT use `SELECT *` in production queries.
- DO NOT use `@` error suppression.
- DO NOT add comments unless explicitly requested.
- **Setup tables (`erp_setup_statuses`, `erp_setup_tags`, `erp_job_statuses`)** are standalone tables with `publish`/`is_active` sync triggers. Use them for status/tag management. Do NOT use `erp_taxonomies` for these — that table is reserved for polymorphic categorization only.
- **Shared tables:** `erp_contacts`, `erp_addresses`, and `erp_attachments` serve multiple entities (customers, vendors, leads, users). Query with appropriate context filters.
- Use `DB::*` constants (e.g., `DB::CUSTOMERS`) — never hardcode `erp_customers`. Note: Some constants are aliases pointing to the same physical table (see `src/Core/DB.php` alias documentation).

