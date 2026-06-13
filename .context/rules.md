# Haipulse ERP — AI Agent Rules

> **Source of truth:** This file consolidates rules from `.cursorrules`, `.windsurfrules`, and `docs/AGENTS.md`.

## Language & Stack
- PHP 8.2+, `declare(strict_types=1)` in every file
- No framework — custom platform with PSR-4 autoloading (`App\` → `src/`)
- Bootstrap 5, jQuery 3.7 frontend
- MySQL 8.0+ with InnoDB, `utf8mb4_unicode_ci`

## Database
- Use `App\Core\Database` PDO wrapper: `fetchOne(sql, params)`, `fetchAll(sql, params)`, `execute(sql, params)`
- Never raw `$mysqli->query()` with string interpolation
- Always named params: `['id' => $id, 'org_id' => $orgId]`
- Table names: Use `DB::CUSTOMERS`, `DB::INVOICES` — never hardcode `erp_customers`
- Every query must include `organization_id` scoping on tenant tables

## Architecture
- Controller → Service → Repository → Model (readonly DTO)
- New classes go in `src/{Layer}/` under `App\{Layer}` namespace
- Models: `readonly class Name(...)` with constructor promotion
- Throw `App\Exception\*` exceptions, never `die()` or `exit()` in `src/`

## Security
- CSRF via `validate_csrf_token()` on every POST
- Permissions via `granted('action', $module_id)` or `granted_('action', 'slug')`
- Use `App\Security\InputValidator` for user input
- Session: `$_SESSION['haipulse']['DASHBOARD']`

## Status Columns
- Use `is_active` as the standard active/inactive column. The `publish` column is auto-synced via triggers on 68 tables (backward compat only).
- New code writes to `is_active`, never to `publish`.
- Soft delete: set `is_active = 0` — never hard delete.

## Setup Tables
- `erp_setup_statuses`, `erp_setup_tags`, `erp_job_statuses` are standalone tables.
- Do NOT use `erp_taxonomies` for these — that table is reserved for polymorphic categorization only.

## Shared Tables
- `erp_contacts`, `erp_addresses`, and `erp_attachments` serve multiple entities (customers, vendors, leads, users).
- Query with appropriate context filters.

## Timezone Data
- PHP array at `config/timezones.php` (247 countries, ~418 entries). No database table for timezones.

## Do NOT
- Add `package.json` or Node.js tooling
- Add a PHP framework
- Use `SELECT *`
- Use `@` error suppression
- Write markdown docs unless asked
- Add comments unless requested
- Hard-delete records (use `is_active = 0`)
- Use the `publish` column in new code (use `is_active` instead)
- Reference dropped tables: `erp_shipping_customers`, `erp_sale_types`, `erp_purchase_types`, `erp_listing_plans`, `erp_listing_subscriptions`, `erp_storage_subtypes`, `erp_user_blocks`, `erp_timezones`, `erp_systems`

## CLI Commands
- Run tests: `php tests/run_all_tests.php`
- Run migration: `php database/migrate.php`
- Generate schema doc: `php scripts/generate_schema_md.php`
- PHP Syntax check: `php -l <file>`
- PHPStan: `vendor/bin/phpstan analyse`
- PHPCS: `vendor/bin/phpcs --standard=.phpcs.xml`
