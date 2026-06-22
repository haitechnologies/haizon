# Haipulse ERP — AI Agent Rules

## Language & Stack
- PHP 8.2+, `declare(strict_types=1)` everywhere
- No framework — PSR-4 `App\` → `src/`
- Bootstrap 5, jQuery 3.7, MySQL 8.0+, InnoDB, `utf8mb4_unicode_ci`

## Database
- Use `App\Core\Database` PDO wrapper with named params
- Table names: `DB::CUSTOMERS` constants — never hardcode `erp_`
- Always `organization_id` scoping on tenant tables
- No `SELECT *`, no `$mysqli->query()` interpolation

## Architecture
- Controller → Service → Repository → Model (readonly DTO)
- New classes: `src/{Layer}/` under `App\{Layer}`
- Throw `App\Exception\*`, never `die()`/`exit()` in `src/`

## Security
- CSRF via `validate_csrf_token()` on every POST
- Permissions: `granted('action', $module_id)` or `granted_('action', 'slug')`
- Session: `$_SESSION['haipulse']['DASHBOARD']`, use `App\Core\Session` class
- Use `App\Security\InputValidator` for user input

## Status Columns
- Soft delete: `is_active = 0` — never hard delete
- `publish` is legacy auto-synced — new code writes to `is_active` only
- `erp_organizations` has no `is_active`

## Do NOT
- `package.json`, Node.js, PHP frameworks
- `SELECT *`, `@` error suppression, comments unless asked, markdown docs unless asked
- Hard deletes, use `publish` in new code
- Reference dropped tables: `erp_shipping_customers`, `erp_sale_types`, `erp_purchase_types`, `erp_listing_plans`, `erp_listing_subscriptions`, `erp_storage_subtypes`, `erp_user_blocks`, `erp_timezones`, `erp_systems`

## CLI
- Tests: `php tests/run_all_tests.php` | Migration: `php database/migrate.php`
- PHPStan: `vendor/bin/phpstan analyse` | PHPCS: `vendor/bin/phpcs --standard=.phpcs.xml`
