# Haipulse ERP Platform — GitHub Copilot Instructions

## Core Rules
- PHP 8.2+ with `declare(strict_types=1)` at top of every file
- Custom platform — NO Laravel/Symfony/wireframe. NO package.json. NO npm.
- Use `App\Core\Database` for DB access: `fetchOne(sql, ['id' => $id])`
- Table constants only: `DB::CUSTOMERS` not `erp_customers`
- Models are `readonly class` with constructor promotion
- All queries must include `organization_id`
- Throw `App\Exception\*`, never die()/exit()
- CSRF on POST: `validate_csrf_token()`
- Permissions: `granted('action', $module_id)` or `granted_('action', 'slug')`
- Soft delete: `is_active = 0`
- NO SELECT *, NO @ error suppression, NO markdown docs unless asked
