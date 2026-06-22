# Haipulse ERP — Naming & File Conventions

## Dashboard Page Naming
| Pattern | Purpose | Example |
|---------|---------|---------|
| `{module}.php` | Form/editor | `customers.php`, `invoices.php` |
| `listing_{module}.php` | List/grid with DataTable | `listing_customers.php` |
| `{parent}_{child}.php` | Child entity page | `customer_contacts.php` |
| `listing_{parent}_{child}.php` | Child entity list | `listing_customer_contacts.php` |
| `{module}_overview.php` | Detail/read-only | `customer_overview.php` |
| `dashboard_{domain}.php` | Dashboard landing | `dashboard_accounting.php` |

## DataTable Handler Naming
- Handler: `{ModuleName}DataTable.php` (e.g., `CustomersDataTable.php`)
- Registry key: `listing_{module}` (e.g., `listing_customers`)
- Base: `App\DataTable\BaseDataTable` | Generic fallback: `App\DataTable\GenericDataTable`

## Service/Repository/Model Naming
| Layer | Naming | Example |
|-------|--------|---------|
| Service | `{Entity}Service.php` | `CustomerService.php` |
| Repository | `{Entity}Repository.php` | `CustomerRepository.php` |
| Model | `{Entity}.php` (readonly DTO) | `Customer.php` |
| Controller | `{Entity}Controller.php` | `BanksController.php` |
| Exception | `{Type}Exception.php` | `ValidationException.php` |

## Database Table Naming
- Prefix `erp_` (rewritten at runtime), use `DB::CONSTANT_NAME`
- Header/line-item pairs: `{plural}` + `{singular}_items` (e.g., `erp_invoices` → `erp_invoice_items`)

## Column Naming Conventions
| Pattern | Usage |
|---------|-------|
| `id` | Auto-increment PK, `INT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `{entity}_id` | FK to parent |
| `organization_id` | Tenant scoping, `INT UNSIGNED NOT NULL` |
| `is_active` | Soft-delete, `TINYINT(1) NOT NULL DEFAULT 1` |
| `publish` | Legacy, auto-synced via trigger (do NOT use in new code) |
| `created_at` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| `updated_at` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` |
| `created_by` / `updated_by` | `INT UNSIGNED DEFAULT NULL`, FK → `erp_users` |

## Permission Module Naming
- Module slug matches plural table name: `customers`, `invoices`, `leads`
- Resolved via: `getModuleIdBySlug('customers', $mysqli)`
- Actions: `view`, `create`, `edit`, `delete`

## Standard Page Layout
Use `d-flex align-items-center justify-content-between` on header row. Title + help icon on left, action buttons on right. See `listing_invoices.php` (gold standard).

**Nesting rules:**
1. `.content-wrapper` > `.page-header` + `.content` (or `.datatable-enhanced`)
2. Copyright outside `.content`, inside `.content-wrapper`
3. No extra `</div>` — tag depth must resolve to exactly 0
