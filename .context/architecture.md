# Haipulse ERP — Architecture Quick Reference

## Stack
PHP 8.2+ | No framework | PSR-4 `App\` → `src/` | MySQL 8.0+ | Bootstrap 5 | jQuery 3.7

## Table Count
123 tables (121 entity + 2 infrastructure). 70 tables org-scoped with `organization_id` FK → `erp_organizations`.

## Layer Pattern
```
Controller → Service → Repository → Model (readonly DTO)
```
Pilot modules (Departments, Designations) fully migrated. Others use monolithic dashboard pages.

## Database Access
```php
$db = \App\Core\DB::pdo();
$user = $db->fetchOne("SELECT id, email FROM " . DB::USERS . " WHERE id = :id", ['id' => $userId]);
// Legacy (MySQLi): global $mysqli;
```

## Multi-Tenancy
- 70 tables have `organization_id` FK → `erp_organizations(id) ON DELETE CASCADE`
- Auto-scoped by `OrgIdInjectionMiddleware` (hardcoded list)
- Session: `$_SESSION['haipulse']['DASHBOARD']['active_organization_id']`

## Column Standards
- `id`, `created_at`, `updated_at` (ON UPDATE CURRENT_TIMESTAMP), `created_by`, `updated_by`, `is_active`
- `is_active` is standard status; `publish` exists on 68 tables for backward compat (auto-synced via triggers)
- All FKs: `created_by`/`updated_by` → `erp_users`; `organization_id` → `erp_organizations`

## Permissions
```php
granted('view', $module_id);     // by module_id
granted_('edit', 'customers');   // by slug
```

## Key Files
| File | Purpose |
|------|---------|
| `src/Core/DB.php` | 120+ table name constants |
| `src/Core/Database.php` | PDO wrapper |
| `src/Core/OrgIdInjectionMiddleware.php` | Tenant query injection |
| `src/DataTable/Registry.php` | Module → Handler mapping |
| `config/globals.php` | Procedural helpers (~2800 lines) |
| `dashboard/bootstrap.php` | Session, CSP, tenant context |

## Shared/Polymorphic Tables
| Table | Serves | Column |
|-------|--------|--------|
| `erp_contacts` | Customers, Vendors | entity_type + FK |
| `erp_addresses` | Customers, Vendors | entity_type + FK |
| `erp_attachments` | Users, Customers, Leads, Vendors | entity_type + entity_id |
| `erp_entity_logs` | Customers, Leads | entity_type |
| `erp_document_types` | Sales, Purchases | context (sale/purchase) |
