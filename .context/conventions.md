# Haipulse ERP — Naming & File Conventions

## Dashboard Page Naming

| Pattern | Purpose | Example |
|---------|---------|---------|
| `{module}.php` | Form/editor page | `customers.php`, `invoices.php` |
| `listing_{module}.php` | List/grid page with DataTable | `listing_customers.php` |
| `{parent}_{child}.php` | Child entity within parent context | `customer_contacts.php` |
| `listing_{parent}_{child}.php` | Child entity list | `listing_customer_contacts.php` |
| `{module}_overview.php` | Detail/read-only overview | `customer_overview.php` |
| `dashboard_{domain}.php` | Dashboard landing page | `dashboard_accounting.php` |

## DataTable Handler Naming

- Handler class: `{ModuleName}DataTable.php` (e.g., `CustomersDataTable.php`)
- Registry key: `listing_{module}` (e.g., `listing_customers`)
- Base class: `App\DataTable\BaseDataTable`
- Generic fallback: `App\DataTable\GenericDataTable` (config-driven)

## Service/Repository/Model Naming

| Layer | Naming | Example |
|-------|--------|---------|
| Service | `{Entity}Service.php` | `CustomerService.php` |
| Repository | `{Entity}Repository.php` | `CustomerRepository.php` |
| Model | `{Entity}.php` (readonly DTO) | `Customer.php` |
| Controller | `{Entity}Controller.php` | `BanksController.php` |
| Exception | `{Type}Exception.php` | `ValidationException.php` |

## Database Table Naming

- All tables prefixed with `erp_` (rewritten at runtime)
- Use `DB::CONSTANT_NAME` — never hardcode table names
- Header/line-item pairs: `{plural}` + `{singular}_items`
  - `erp_invoices` → `erp_invoice_items`
  - `erp_quotations` → `erp_quotation_items`

## Column Naming Conventions

| Pattern | Usage | Example |
|---------|-------|---------|
| `id` | Auto-increment PK | `INT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `{entity}_id` | FK to parent | `customer_id`, `organization_id` |
| `organization_id` | Tenant scoping column | `INT UNSIGNED NOT NULL` |
| `is_active` | Soft-delete flag | `TINYINT(1) NOT NULL DEFAULT 1` |
| `publish` | Legacy soft-delete (synced with `is_active` via trigger) | `TINYINT(1) NOT NULL DEFAULT 1` |
| `created_at` | Creation timestamp | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| `updated_at` | Last modification | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` |
| `created_by` | User who created | `INT UNSIGNED DEFAULT NULL` |
| `updated_by` | User who last updated | `INT UNSIGNED DEFAULT NULL` |
| `entity_type` | Polymorphic discriminator | `ENUM('business','shipping')` |

## Standard Table Template

```sql
CREATE TABLE `erp_{name}` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    -- ... business columns ...
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `updated_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_org` (`organization_id`),
    KEY `idx_org_active` (`organization_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Permission Module Naming

- Module slug matches the plural table name: `customers`, `invoices`, `leads`
- Resolved via: `getModuleIdBySlug('customers', $mysqli)`
- Permission actions: `view`, `create`, `edit`, `delete`

## File Path Quick Reference

```
src/Core/DB.php                    → Table constants
src/Core/Database.php              → PDO wrapper
src/Service/{Entity}Service.php    → Business logic
src/Repository/{Entity}Repository.php → Database CRUD
src/Model/{Entity}.php             → Readonly DTO
src/DataTable/{Entity}DataTable.php → Grid handler
dashboard/{module}.php             → Form page
dashboard/listing_{module}.php     → List page
dashboard/admin_elements/          → Shared layouts
config/globals.php                 → Procedural helpers (~2800 lines)
config/database.php                → DB connection + tbl_* legacy constants

## Page Layout & Header Standards

All listing pages and CRUD forms should use the standardized carriers header style.

### Standard Layout Structure
```html
<div class="content-wrapper">
    <!-- Page Header (carriers style) -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h1 class="h5 mb-0 d-inline-flex align-items-center gap-2">
                    <a href="listing_<?php echo $module; ?>.php" class="text-dark">All Name</a>
                </h1>
            </div>
            <div class="my-1">
                <!-- optional action buttons -->
            </div>
        </div>
    </div>
    
    <!-- Main Content Container -->
    <div class="content datatable-enhanced">
        <?php include('admin_elements/breadcrumb.php'); ?>
        <!-- Card / Form / Table Content -->
    </div>
    
    <?php include('admin_elements/copyright.php'); ?>
</div>
```

### Important Nesting Rules
1. Never leave extra closing `</div>` tags at the end of the file. Ensure tag depth resolves to exactly `0`.
2. Always place `copyright.php` at the very bottom of the page wrapper (outside `.content` / `.content-inner` / `.datatable-enhanced` but inside `.content-wrapper`).
```
