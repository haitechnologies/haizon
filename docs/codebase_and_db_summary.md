<!-- ⚠️ DO NOT DELETE THIS FILE — EVER. ⚠️ -->
<!-- This is the canonical codebase & database summary for the Haipulse project. -->
<!-- It must be kept up-to-date and must never be removed from the repository.   -->

# Haipulse Codebase & Database Summary

> [!CAUTION]
> **This file must NEVER be deleted.** It is the single source of truth for the codebase architecture, database schema, and system context. Update it when changes are made — do not remove it.

This document provides a high-level architectural, directory, routing, and schema summary of the Haipulse platform. It is designed to act as a system context guide for reviewing PSR compliance, modern HTML/CSS standards, security posture, and scalability.

---

## 1. Project Overview

*   **Primary Language & Backend Stack:** PHP 8.2+ utilizing raw SQL/PDO, strict typing (`declare(strict_types=1);`), and Composer-driven autoloading.
*   **Architecture Pattern:** Dual layered model. Transitioning from legacy spaghetti code to a **Strict Layered Architecture** (Controllers -> Services -> Repositories -> DTO Models) wrapped in Composer PSR-4 namespaces.
*   **Autoloading Rules:** Namespace `App\` maps to the `src/` directory. Legacy utilities are loaded via class files in `classes/` and configuration boot-strappers.
*   **Frontend Stack:** Modern Semantic HTML5, CSS3, custom-compiled Bootstrap v5.1.0/v5.3+ (Limitless Admin Theme), and modern jQuery v3.7+ (using central delegated event listeners).
*   **Deployment Mode:** Backend-only (ERP dashboard). The public-facing frontend website has been **completely removed**. `robots.txt` disallows all crawlers (`Disallow: /`). The root `index.php` immediately redirects to `dashboard/login.php`.
*   **Entry Points:**
    *   `/index.php` — Root redirector to `dashboard/login.php`.
    *   `/dashboard/login.php` — User login gate.
    *   `/dashboard/bootstrap.php` — Central session, security headers, config, and organizational context bootstrapper included by all dashboard pages.
    *   Individual scripts inside `/dashboard` (e.g., `invoices.php`, `listing_departments.php`) serve as Page Controllers.

---

## 2. High-Level Directory Structure

```
haipulse/
├── assets/                      # Shared public assets
│   ├── css/                     # Global stylesheets
│   ├── fonts/                   # Web fonts
│   ├── images/                  # Shared images & branding
│   ├── js/                      # Global scripts (DataTable initializer, theme, etc.)
│   ├── plugins/                 # Third-party jQuery/Bootstrap plugins
│   └── vendor/                  # Local vendor libraries (no CDNs)
│       ├── bootstrap/           # Bootstrap 5.3.3 minified CSS/JS bundles
│       ├── datatables/          # DataTables.js core
│       ├── datatables-responsive/ # DataTables responsive extension
│       └── jquery/              # jQuery 3.7.1 local minified scripts
├── classes/                     # Legacy helper classes and core validators
│   ├── DataTable/               # Server-side DataTables handler classes
│   │   ├── BaseDataTable.php    # Abstract base class (pagination, search, CSRF)
│   │   ├── Registry.php         # Central handler-to-table mapping
│   │   └── *DataTable.php       # ~105 concrete DataTable handlers
│   ├── frontend/                # Legacy frontend helper classes (retained for reference)
│   ├── DB.php                   # Database table catalog registry (~100 constants)
│   ├── InputValidator.php       # Type and range checking inputs
│   ├── TOTPAuthenticator.php    # Two-factor authentication (MFA/TOTP)
│   ├── OrgIdInjectionMiddleware.php  # Multi-tenant query interceptor
│   ├── OrganizationMembershipManager.php  # Invite/membership lifecycle
│   ├── SystemEntitlements.php   # SaaS feature gating and entitlement resolution
│   ├── SubscriptionTier.php     # Subscription tier catalog
│   ├── Roles.php                # RBAC role & permission resolution
│   ├── DeletionManager.php      # Centralized soft/hard delete handling
│   ├── RateLimiter.php          # Token-bucket rate limiter
│   ├── IpRateLimiter.php        # IP-based rate limiter
│   ├── CSVExporter.php          # CSV export utility
│   ├── FileUploadValidator.php  # Upload validation
│   ├── ImageUploadHandler.php   # Image processing & upload
│   ├── EmailQueue.php           # Transactional email queue
│   ├── SMTPMailer.php           # SMTP mail sender
│   ├── EmailProviderManager.php # Email provider management
│   ├── EmailTriggers.php        # Event-driven email dispatch
│   ├── DisposableEmailValidator.php  # Disposable email domain blocker
│   ├── AccountingJournalManager.php  # Double-entry journal posting
│   ├── ActionButtonHelper.php   # UI action button generator
│   ├── BadgeHelper.php          # Status badge renderer
│   ├── AuditHelper.php          # Audit trail helper
│   ├── JSONLDSchema.php         # Structured data generator
│   ├── BusinessListingPlan.php  # Listing plan logic
│   ├── DSNConfigurator.php      # DSN connection helper
│   ├── DatabaseSchemaInitializer.php  # Schema bootstrapping
│   ├── SearchLimiter.php        # Search rate limiting
│   ├── SimpleCaptcha.php        # CAPTCHA generation
│   ├── StripePayment.php        # Stripe API integration
│   └── OAuth.php / OAuthTokenProvider.php  # OAuth helpers
├── config/                      # Core bootstrapping and configurations
│   ├── database.php             # Database configuration & settings
│   ├── globals.php              # URL paths, global helpers, and utility functions
│   ├── session.php              # Scoped PHP session handlers (dashboard vs frontend)
│   ├── logging.php              # Logging configuration
│   ├── error_alerting.php       # Error alerting thresholds
│   ├── images.php               # Image path constants
│   ├── seo_helpers.php          # SEO utility functions
│   ├── saas_catalog.php         # SaaS tier catalog configuration
│   ├── uae_geo_constants.php    # UAE geography constants
│   ├── disposable_email_*.conf  # Blocklist/allowlist configuration files
│   └── company_slug_*.php       # Legacy company slug redirects
├── cron/                        # Scheduled cron job scripts
│   ├── email_alerts.php         # Alert email dispatch
│   ├── email_digest.php         # Digest compilation and dispatch
│   ├── sync_subscription_tiers.php  # Subscription tier synchronization
│   └── update_category_counts.php   # Category statistics refresh
├── dashboard/                   # Page Controllers & Layout elements
│   ├── admin_elements/          # Core layouts (headers, footers, sidebar, security)
│   │   ├── admin_header.php     # Master header (fonts, CSS, CSRF, navbar)
│   │   ├── admin_footer.php     # Script bundles and layout corrections
│   │   ├── sidebar.php          # Main sidebar navigation (~40KB)
│   │   ├── security.php         # MFA enforcement and session validation
│   │   ├── permissions.php      # Permission checking middleware
│   │   ├── error_logger.php     # Dashboard error handler registration
│   │   └── event-bindings.js    # Central jQuery event delegation
│   ├── ajax/                    # AJAX endpoint handlers
│   ├── api/                     # REST API dispatcher and handlers
│   │   ├── dispatcher.php       # APIDispatcher router class
│   │   ├── BaseAPI.php          # Abstract API base class
│   │   ├── CustomerAPI.php      # Customer endpoints
│   │   └── ItemAPI.php          # Item/service endpoints
│   ├── assets/                  # Theme-specific CSS, fonts, and scripts (Limitless)
│   ├── assets_custom/js/        # Custom JavaScript extensions
│   ├── bootstrap.php            # Dashboard bootstrap (security headers, session, org context)
│   ├── classes/                 # Dashboard-scoped classes (PHPMailer, EmailTemplateHelper)
│   ├── cron/                    # Dashboard-scoped cron scripts
│   ├── dashboard_widgets/       # Dashboard widget components
│   ├── datatables/              # DataTables handler directory
│   │   └── handlers/            # DataTable handler mounting point
│   ├── datatables_dispatcher.php  # DataTables AJAX POST router
│   ├── helpers/                 # Helper includes (e.g., shipping_customer_helper)
│   ├── login.php                # Security login entrypoint
│   ├── index.php                # Main dashboard (90KB+ dashboard page)
│   ├── listing_*.php            # ~80+ listing (read-only grid) page controllers
│   └── *.php                    # ~120+ form/write page controllers
├── database/                    # Database utilities
│   └── migrations/              # Migration scripts subdirectory
├── docs/                        # Technical specification & guidelines
│   ├── technical_specifications.md  # Architecture, schema, UI, and security spec
│   ├── MULTI_TENANT_ARCHITECTURE.md # Multi-tenant design documentation
│   ├── Semantic.md              # Semantic HTML guidelines
│   └── ai/                      # AI-specific context and prompt documentation
│       ├── QUICK_START.md       # AI agent onboarding guide
│       ├── architecture.md      # Detailed architecture reference
│       ├── domain-rules.md      # Business domain rules
│       └── project-context.md   # Project context summary
├── migrations/                  # Database migration SQL and scripts
│   ├── 20260605_*_create_erp_department_dual_run.sql  # Dual-run department migration
│   ├── 06-*.sql                 # Organization ID migration phases
│   └── 2026-04-*.php            # PHP-based migrations (subscriptions, etc.)
├── scripts/                     # CLI utilities and one-off scripts
│   ├── security_validator.php   # Security audit tool
│   ├── export_db_metadata.php   # Database metadata exporter
│   ├── audit_pages_csv_*.php    # Page coverage audit scripts
│   ├── build_decision_ledger.php  # Architectural decision ledger generator
│   └── setup-user-mfa.php       # MFA setup helper
├── src/                         # Modern PSR-4 source code (App\ namespace)
│   ├── Core/
│   │   └── Database.php         # PDO wrapper (App\Core\Database)
│   ├── Exception/
│   │   ├── DomainException.php  # Business rule exception
│   │   ├── NotFoundException.php  # Entity not found exception
│   │   └── ValidationException.php  # Input validation exception
│   ├── Model/
│   │   └── Department.php       # Readonly DTO (App\Model\Department)
│   ├── Repository/
│   │   ├── DepartmentRepository.php  # Department data access
│   │   └── UserRepository.php   # User data access
│   └── Service/
│       └── DepartmentService.php  # Department business logic
├── tests/                       # Integration and Unit tests
│   ├── test_department_psr.php  # PSR-4 Department pilot test
│   └── integration-multi-org.php  # Multi-tenant integration test
├── composer.json                # PSR-4 mapping & composer dependencies
├── PSR.md                       # AI/Backend coding standards reference
├── .htaccess                    # Apache security, compression, CSP, and routing rules
└── index.php                    # Root redirector to dashboard/login.php
```

---

## 3. Database Schema & Relationships

All primary tables use the `erp_` prefix. During the migration phase, plural tables (legacy) coexist with singular tables (modernized) synced via bidirectional MySQL triggers.

### Table Registry (`classes/DB.php`)

The centralized `DB` class provides ~100 typed constants for all table names, organized into categories:

| Category | Table Constant(s) | Table Name | Purpose |
| :--- | :--- | :--- | :--- |
| **User & Auth** | `USERS` | `erp_users` | Backend dashboard users |
| | `ROLES` | `erp_roles` | RBAC access roles |
| | `PERMISSIONS` | `erp_permissions` | System access privileges |
| | `MODULE_PERMISSIONS` | `erp_module_permissions` | Per-module permission rules |
| | `AUTHENTICATION_ACTIVITY` | `erp_authentication_activity` | Login/MFA audit trail |
| | `RATE_LIMIT_ATTEMPTS` | `erp_rate_limit_attempts` | Rate limiting tracking |
| **Organization & Multi-Tenancy** | `ORGANIZATIONS` | `erp_organizations` | Multi-tenant root entities |
| | `ORGANIZATION_MEMBERSHIPS` | `erp_organization_memberships` | User-to-org assignments |
| | `ORGANIZATION_ROLES` | `erp_organization_roles` | Per-org role catalog |
| | `ORGANIZATION_MEMBER_ROLES` | `erp_organization_member_roles` | Member role assignments |
| | `ORGANIZATION_INVITES` | `erp_organization_invites` | Pending org invitations |
| | `ORGANIZATION_SYSTEM_ENTITLEMENTS` | `erp_organization_system_entitlements` | Org-level feature toggles |
| **Subscription & SaaS** | `SUBSCRIPTION_PLANS` | `erp_subscription_plans` | SaaS plan catalog |
| | `SUBSCRIPTIONS` | `erp_subscriptions` | Account subscriptions |
| | `SUBSCRIPTION_PLAN_FEATURES` | `erp_subscription_plan_features` | Per-plan feature values |
| | `SUBSCRIPTION_OVERRIDES` | `erp_subscription_overrides` | Admin feature overrides |
| | `SUBSCRIPTION_PAYMENTS` | `erp_subscription_payments` | Payment records |
| | `SUBSCRIPTION_LOGS` | `erp_subscription_logs` | Tier change audit log |
| | `API_KEYS` | `erp_api_keys` | Pro/Enterprise API keys |
| **HR & Payroll** | `DEPARTMENTS` / `DEPARTMENT` | `erp_departments` / `erp_department` | Legacy plural + modern singular (dual-run triggers) |
| | `DESIGNATIONS` | `erp_designations` | Job positions |
| | `ATTENDANCE` | `erp_attendance` | Employee check-in/out |
| | `LEAVE_REQUESTS` / `LEAVE_TYPES` | `erp_leave_requests` / `erp_leave_types` | PTO management |
| | `PAYROLL_COMPONENTS` | `erp_payroll_components` | Earnings/deduction types |
| | `SALARY_STRUCTURES` | `erp_salary_structures` | Salary composition templates |
| | `EMPLOYEE_SALARIES` | `erp_employee_salaries` | Per-employee salary setup |
| | `PAYROLL_RUNS` / `PAYSLIPS` | `erp_payroll_runs` / `erp_payslips` | Monthly payroll |
| | `USER_DOCUMENTS` | `erp_user_documents` | Employee document records |
| **CRM & Leads** | `CUSTOMERS` | `erp_customers` | Customer directory |
| | `CUSTOMER_CONTACTS` | `erp_customer_contacts` | Contact persons |
| | `CUSTOMER_ADDRESSES` | `erp_customer_addresses` | Shipping/billing addresses |
| | `ENTITY_LOGS` | `erp_entity_logs` | Unified activity logs (leads + customers) |
| | `ENTITY_NOTES` | `erp_entity_notes` | Unified notes (leads + customers) |
| | `LEADS` | `erp_leads` | Sales prospects |
| | `LEAD_ATTACHMENTS` | `erp_lead_attachments` | Lead file attachments |
| | `PROJECTS` | `erp_projects` | Client projects |
| | `JOBS` / `JOB_STATUSES` | `erp_jobs` / `erp_job_statuses` | Service orders |
| **Accounting** | `ACCOUNTS` | `erp_accounts` | Chart of Accounts |
| | `ACCOUNTS_REPORT_CATEGORIES` / `..._SUBCATEGORIES` | `erp_accounts_report_*` | P&L/Balance Sheet grouping |
| | `JOURNALS` / `JOURNAL_ITEMS` | `erp_journals` / `erp_journal_items` | Double-entry journals |
| | `INVOICES` / `INVOICE_ITEMS` | `erp_invoices` / `erp_invoice_items` | Sales invoices |
| | `QUOTATIONS` / `QUOTATION_ITEMS` | `erp_quotations` / `erp_quotation_items` | Sales quotes |
| | `SALE_ORDERS` / `SALE_ORDER_ITEMS` | `erp_sale_orders` / `erp_sale_order_items` | Sales orders |
| | `CREDIT_NOTES` / `CREDIT_NOTE_ITEMS` | `erp_credit_notes` / `erp_credit_note_items` | Credit memos |
| | `PURCHASES` / `PURCHASE_ITEMS` | `erp_purchases` / `erp_purchase_items` | Purchase transactions |
| | `PURCHASE_ORDERS` / `PURCHASE_ORDER_ITEMS` | `erp_purchase_orders` / `erp_purchase_order_items` | Purchase orders |
| | `DEBIT_NOTES` / `DEBIT_NOTE_ITEMS` | `erp_debit_notes` / `erp_debit_note_items` | Debit memos |
| | `PAYMENTS_RECEIVED` / `PAYMENTS_MADE` | `erp_payments_received` / `erp_payments_made` | Payment tracking |
| | `EXPENSES` | `erp_expenses` | Expense transactions |
| | `VENDORS` / `VENDOR_CONTACTS` | `erp_vendors` / `erp_vendor_contacts` | Supplier directory |
| | `SALE_TYPES` / `PURCHASE_TYPES` | `erp_sale_types` / `erp_purchase_types` | Transaction type setup |
| | `BANKS` | `erp_banks` | Bank accounts |
| | `TAX_TREATMENTS` | `erp_tax_treatments` | Tax treatment setup |
| | `PAYMENT_TERMS` | `erp_payment_terms` | Payment terms (Net 30, etc.) |
| | `CURRENCIES` | `erp_currencies` | Currency definitions |
| | `PAYMENT_METHODS` | `erp_payment_methods` | Cash, bank transfer, etc. |
| **Logistics & Shipping** | `SHIPPING_CUSTOMERS` | `erp_shipping_customers` | Shipping-specific customers |
| | `SHIPPING_ADVICES` / `SHIPPING_ADVICE_ITEMS` | `erp_shipping_advices` / `erp_shipping_advice_items` | Dispatch notices |
| | `SHIPPING_INVOICES` / `SHIPPING_INVOICE_ITEMS` | `erp_shipping_invoices` / `erp_shipping_invoice_items` | Freight invoicing |
| | `SHIPPING_STOCKS` | `erp_shipping_stocks` | Inventory balance |
| | `PORTS` / `CARRIERS` / `CONSIGNEES` / `SHIPPERS` | `erp_ports` / `erp_carriers` / `erp_consignees` / `erp_shippers` | Logistics master data |
| | `INCOTERMS` / `EXIT_POINTS` | `erp_incoterms` / `erp_exit_points` | Trade terms / customs |
| | `CONTAINER_TYPES` / `COMMODITY_TYPES` | `erp_container_types` / `erp_commodity_types` | Logistics type setup |
| | `WAREHOUSES` / `STORAGE_TYPES` / `STORAGE_SUBTYPES` | `erp_warehouses` / `erp_storage_types` / `erp_storage_subtypes` | Warehouse management |
| **Email & Marketing** | `EMAIL_PROVIDERS` | `erp_email_providers` | SMTP provider config |
| | `EMAIL_CAMPAIGNS` / `EMAIL_TARGETS` | `erp_email_campaigns` / `erp_email_targets` | Campaign management |
| | `EMAIL_TEMPLATES` | `erp_email_templates` | Email template library |
| | `EMAIL_QUEUE` | `erp_email_queue` | Transactional email queue |
| | `EMAIL_HISTORY` / `EMAIL_SENDS` | `erp_email_history` / `erp_email_sends` | Send history & aggregates |
| | `EMAIL_BOUNCES` / `EMAIL_EVENTS` / `EMAIL_UNSUBSCRIBES` | `erp_email_*` | Deliverability tracking |
| | `EMAIL_AUTOMATION_RULES` / `EMAIL_AUTOMATION_QUEUE` | `erp_email_automation_*` | Automated email rules |
| **Content & CMS** | `BLOGS` / `BLOG_CATEGORIES` | `erp_blogs` / `erp_blog_categories` | Blog management |
| | `PAGES` | `erp_pages` | CMS pages |
| | `CATEGORIES` / `SUBCATEGORIES` | `erp_categories` / `erp_subcategories` | Hierarchical categories |
| | `HS_CODE_SETS` / `HS_CODES` / `HS_CODE_TEXTS` | `erp_hs_code_sets` / `erp_hscodes` / `erp_hs_code_texts` | Harmonized System codes |
| | `CATEGORY_HS_CODES` | `erp_category_hs_codes` | Category-to-HS code mapping |
| **Business Directory** | `COMPANIES` | `erp_companies` | Company listings |
| | `LISTING_PLANS` / `LISTING_SUBSCRIPTIONS` | `erp_listing_plans` / `erp_listing_subscriptions` | Listing subscriptions |
| | `PUBLIC_ADS` | `erp_public_ads` | Sponsored ad campaigns |
| **Geography** | `GEO_COUNTRIES` / `GEO_STATES` / `GEO_CITIES` | `erp_geo_*` | Location master data |
| | `IP_COUNTRIES` | `erp_ip_countries` | IP-to-country geolocation |
| **Search & Analytics** | `SEARCHES` | `erp_searches` | Unified search log |
| | `INQUIRIES` / `INQUIRY_REPLIES` | `erp_inquiries` / `erp_inquiry_replies` | Contact form submissions |
| **Frontend Users** | `FRONTEND_USERS` | `erp_frontend_users` | Public user accounts |
| | `FRONTEND_USER_FAVORITES` | `erp_frontend_user_favorites` | Saved companies |
| **Security & Utilities** | `DISPOSABLE_EMAIL_DOMAINS` | `erp_disposable_email_domains` | Spam email blocklist |
| | `BANNED_WORDS` | `erp_banned_words` | Content moderation dictionary |
| | `AUDIT_LOG` | `erp_audit_log` | System audit trail |
| **Setup** | `SETUP_SOURCES` / `SETUP_STATUSES` / `SETUP_TAGS` / `SETUP_GROUPS` | `erp_setup_*` | CRM pipeline setup |
| | `ITEMS` / `UNITS` | `erp_items` / `erp_units` | Products and units |
| | `DOCUMENT_CATEGORIES` | `erp_document_categories` | Document categorization |
| **System** | `SYSTEM_SETTINGS` | `erp_system_settings` | Global configuration |
| | `MODULES` | `erp_modules` | Module registry |
| | `ALERTS` | `erp_alerts` | System notifications |
| | `BACKEND_ERROR_LOGS` / `BACKEND_LOG_COVERAGE` | `erp_backend_error_logs` / `erp_backend_log_coverage` | Error tracking |
| | `ERROR_LOG_STATUS` | `erp_error_log_status` | Error read-status tracking |

### Core Table Field Details

#### A. erp_users (Dashboard User Registry)
*   **Columns:**
    *   `id`: INT (Primary Key, Auto Increment)
    *   `can_access_system`: TINYINT(1) (Default 0)
    *   `is_active`: TINYINT(1) (Indexed)
    *   `role_id`: INT (Foreign Key to `erp_roles`)
    *   `email`: VARCHAR(254) (Unique Key)
    *   `password`: VARCHAR(255) (Hashed)
    *   `mfa_totp_enabled`: TINYINT(1)
    *   `mfa_totp_secret`: VARCHAR(255)
    *   `full_name`: VARCHAR(100) (Indexed)
    *   `department_id`: INT
    *   `created_at`: DATETIME (Default CURRENT_TIMESTAMP)
    *   `updated_at`: DATETIME (Default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

#### B. erp_roles (Access Control Groups)
*   **Columns:**
    *   `id`: INT (Primary Key, Auto Increment)
    *   `role_name`: VARCHAR(40)
    *   `publish`: TINYINT(1)
    *   `is_active`: TINYINT(1)

#### C. erp_department (Modernized Singlet Division Table)
*   **Columns:**
    *   `id`: BIGINT UNSIGNED (Primary Key, Auto Increment)
    *   `organization_id`: INT UNSIGNED (Multi-tenant Key, Indexed)
    *   `department`: VARCHAR(100) (Unique Key)
    *   `publish`: TINYINT(1) (Default 1)
    *   `created_at`: TIMESTAMP (Default CURRENT_TIMESTAMP)
    *   `updated_at`: TIMESTAMP (Default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

#### D. erp_organizations (Multi-Tenant Segregation Nodes)
*   **Columns:**
    *   `id`: INT (Primary Key, Auto Increment)
    *   `owner_user_id`: INT UNSIGNED (Indexed)
    *   `status`: VARCHAR(40) (Default 'active')
    *   `warehouse_name`: VARCHAR(100) (Unique Key)
    *   `slug`: VARCHAR(191) (Unique Key)
    *   `trn`: VARCHAR(20) (Tax Registration Number)
    *   `is_active`: TINYINT(1) (Default 1)

#### E. erp_customers (CRM Customer Directory)
*   **Columns:**
    *   `id`: INT (Primary Key, Auto Increment)
    *   `organization_id`: INT (Multi-tenant Key, Indexed)
    *   `display_name`: VARCHAR(100)
    *   `company_name`: VARCHAR(200)
    *   `email`: VARCHAR(50)
    *   `phone`: VARCHAR(50)
    *   `is_active`: TINYINT(1) (Default 1)

#### F. erp_invoices (Sales Invoice Headers)
*   **Columns:**
    *   `id`: INT (Primary Key, Auto Increment)
    *   `organization_id`: INT (Multi-tenant Key)
    *   `invoice_no`: VARCHAR(100) (Unique Key)
    *   `customer_id`: INT (Foreign Key to `erp_customers`)
    *   `invoice_status`: VARCHAR(15) (Draft, Sent, Paid, etc.)
    *   `grand_subtotal`: DECIMAL(15,2)
    *   `grand_tax`: DECIMAL(12,2)
    *   `grand_total`: DECIMAL(15,2)
    *   `created_by`: INT (Foreign Key to `erp_users`)

---

### Entity Relationships
```
[erp_organizations] ── hasMany ──> [erp_users]
[erp_organizations] ── hasMany ──> [erp_customers]
[erp_organizations] ── hasMany ──> [erp_invoices]
[erp_organizations] ── hasMany ──> [erp_department]
[erp_organizations] ── hasMany ──> [erp_organization_memberships]
[erp_organizations] ── hasMany ──> [erp_organization_invites]
[erp_organizations] ── hasMany ──> [erp_organization_roles]

[erp_organization_memberships] ── belongsTo ──> [erp_users]
[erp_organization_memberships] ── hasMany ──> [erp_organization_member_roles]
[erp_organization_member_roles] ── belongsTo ──> [erp_organization_roles]

[erp_subscriptions] ── belongsTo ──> [erp_subscription_plans]
[erp_subscription_plans] ── hasMany ──> [erp_subscription_plan_features]

[erp_customers]     ── hasMany ──> [erp_invoices]
[erp_customers]     ── hasMany ──> [erp_customer_contacts]
[erp_customers]     ── hasMany ──> [erp_customer_addresses]
[erp_invoices]      ── hasMany ──> [erp_invoice_items]
[erp_roles]         ── hasMany ──> [erp_users]

[erp_leads]         ── hasMany ──> [erp_lead_attachments]
[erp_leads]         ── hasMany ──> [erp_entity_logs] (polymorphic)
[erp_leads]         ── hasMany ──> [erp_entity_notes] (polymorphic)

[erp_accounts]      ── hasMany ──> [erp_journal_items]
[erp_journals]      ── hasMany ──> [erp_journal_items]

[erp_vendors]       ── hasMany ──> [erp_purchases]
[erp_vendors]       ── hasMany ──> [erp_vendor_contacts]
```

---

## 4. PSR-4 Architecture (src/)

The modern layered architecture under `src/` follows the Strict Layered Pattern:

### Layers

| Layer | Namespace | Responsibility |
| :--- | :--- | :--- |
| **Core** | `App\Core\` | PDO database wrapper, connection management |
| **Exception** | `App\Exception\` | Typed domain exceptions (`DomainException`, `NotFoundException`, `ValidationException`) |
| **Model** | `App\Model\` | Readonly DTOs representing database records |
| **Repository** | `App\Repository\` | Data access layer (one repository per table) |
| **Service** | `App\Service\` | Business logic coordination |

### Pilot Module: Departments

The Departments module is the first fully migrated PSR-4 module:
*   `App\Model\Department` — Readonly DTO
*   `App\Repository\DepartmentRepository` — CRUD operations via PDO
*   `App\Service\DepartmentService` — Business rules and validation
*   `App\Repository\UserRepository` — User data access

### Dual-Run Migration Strategy

Legacy plural tables (`erp_departments`) coexist with modern singular tables (`erp_department`) via bidirectional MySQL triggers:
*   **Trigger naming**: `trg_erp_tablename_after_action`
*   **Recursion guard**: Triggers check `@sync_in_progress` session variable to prevent infinite loops
*   **Migration file**: `migrations/20260605_093000_create_erp_department_dual_run.sql`

---

## 5. Routing & Key Components

### Routing Model
- **Page Controller Pattern:** Direct endpoint scripts inside `/dashboard` intercept requests (e.g. `listing_departments.php` for viewing lists, `departments.php` for writing). 
- **API Dispatcher:** `dashboard/api/dispatcher.php` routes JSON API requests via an `APIDispatcher` class with action-to-handler mapping (currently: `getServices`, `getItemRate`, `populateCustomers`).
- **Middlewares / Interceptors:** Page files load a header bootstrapper `admin_elements/admin_header.php` which executes the following checks:
    - Session lifetime validity check.
    - CSRF token presence validation.
    - Two-factor authentication verification.
    - Organization entitlement verification.

### Dashboard Bootstrap (`dashboard/bootstrap.php`)
The central bootstrapper loaded by all dashboard pages handles:
1. **Security headers**: HSTS, CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy.
2. **HTTPS enforcement**: Automatic redirect in production environments.
3. **Scoped session management**: Dashboard uses `HAIPULSE_DASHBOARD_SESSID` cookie, separate from frontend.
4. **Configuration loading**: `globals.php`, `database.php`, `images.php`.
5. **Service initialization**: `OrganizationMembershipManager`, `SystemEntitlements`, `EmailQueue`, `SMTPMailer`, `DeletionManager`.
6. **Error handling**: Custom error/exception handlers and shutdown functions via `error_logger.php`.
7. **Organization context resolution**: Auto-resolves active organization from session with fallback to first accessible org.
8. **Entitlement caching**: Subscription features and system entitlements cached in session with 5-minute TTL.

### Key Components
1.  **PDO Wrapper (`App\Core\Database`):** Standardizes queries, enforcing strict parameter binding via PDO (no raw parameter injection).
2.  **Organization ID Interceptor (`OrgIdInjectionMiddleware`):** Programmatically forces `organization_id` criteria on select queries executing on multi-tenant tables.
3.  **Dynamic Initializer (`dashboard-datatable-initializer.js`):** Script that automatically boots grid lists and handles CSRF validation for server-side DataTables request endpoints.
4.  **DataTables Dispatcher (`datatables_dispatcher.php`):** Central AJAX POST router that routes DataTable requests to the correct handler class via `Registry.php`.
5.  **SystemEntitlements:** Resolves SaaS feature gates from subscription plans, plan features, and admin overrides, cached in session.
6.  **OrganizationMembershipManager:** Full lifecycle management for invites, membership activation, role assignment, and member counting.

---

## 6. Frontend Asset Overview

- **Vendors Location:** Assets are loaded locally under `assets/vendor/` to allow offline XAMPP operations (Bootstrap 5.3.3 CSS/JS, jQuery 3.7.1, DataTables.js).
- **Custom Theme Styles:** Loaded from `dashboard/assets/` (Limitless Bootstrap theme: `bootstrap.min.css`, `bootstrap_limitless.min.css`, `components.min.css`, `layout.min.css`).
- **Landmark tags:** Custom layout header wrappers use `<header>`, main navigation elements use `<nav>`, sidebar elements use `<aside class="sidebar-main">` and `<aside class="sidebar-secondary">`, page containers use `<main>`, and footers use `<footer>`.
- **Forms & Inputs:** Standardised with Bootstrap form-control and float patterns, incorporating explicit HTML5 autocomplete attributes and labels.
- **Key Scripts:**
    - `assets/js/dashboard-datatable-initializer.js` — Auto-configures DataTables with CSRF injection.
    - `assets/js/dashboard-datatable-standard.js` — Standard DataTable grid utilities.
    - `assets/js/datatable-error-handler.js` — Global DataTable error handling.
    - `assets/js/themeColors.js` — Theme color configuration (~27KB).
    - `admin_elements/event-bindings.js` — Centralized jQuery event delegation.

---

## 7. Security Architecture

### CSRF Token Protection
All forms and AJAX request payloads must include a valid CSRF token.
*   Tokens are generated and validated via the `csrf_token()` and `validate_csrf_token()` functions.
*   AJAX POST queries (including DataTables search/filter queries) append the token dynamically.

### Tenant Isolation (Multi-Tenancy)
*   Queries executed on organization-scoped tables (listed in `OrgIdInjectionMiddleware.php`) must filter by `organization_id`.
*   The `OrgIdInjectionMiddleware` intercepts raw `SELECT` queries and automatically injects the corresponding `WHERE organization_id = {activeOrgId}` condition.

### Session & MFA Hardening
*   **Scoped Sessions:** Dashboard and frontend sessions are isolated with separate cookie names (`HAIPULSE_DASHBOARD_SESSID` / `HAIPULSE_FRONTEND_SESSID`) and separate cookie paths.
*   **MFA (2FA):** TOTP verification enforced on production via `admin_elements/security.php`.
*   **Session Hijacking:** Client User-Agent and IP signature checks with automatic logouts.
*   **Cookie Security:** `cookie_httponly=1`, `cookie_samesite='Strict'`, `use_strict_mode=1`, `use_only_cookies=1`, `cookie_secure=1` (production).

### Apache Hardening (`.htaccess`)
*   Directory listing disabled (`Options -Indexes`).
*   Hidden files blocked (`.env`, `.git`, etc.).
*   Sensitive file extensions blocked (`.bak`, `.sql`, `.log`, `.env`, `.key`, `.pem`).
*   Developer documentation blocked (`.md`, `.csv`, `.ps1`).
*   Sensitive directories blocked via `mod_rewrite` (`config/`, `classes/`, `vendor/`, `migrations/`, `cron/`, `database/`).
*   `mod_deflate` compression for text-based responses.
*   Full security header suite via `mod_headers` (HSTS, CSP, X-Frame-Options, etc.).

### Rate Limiting
*   `RateLimiter.php` — Token-bucket implementation for API and form submissions.
*   `IpRateLimiter.php` — IP-based brute-force protection.
*   `SearchLimiter.php` — Search query rate limiting.

---

## 8. Composer Dependencies

| Package | Version | Purpose |
| :--- | :--- | :--- |
| `phpoffice/phpspreadsheet` | ^2.1 | Excel/CSV import/export |
| `endroid/qr-code` | ^3.2 | QR code generation (MFA setup) |
| `smalot/pdfparser` | ^2.12 | PDF document parsing |
| `vlucas/phpdotenv` | 5.6 | Environment variable loading |
| `stripe/stripe-php` | ^13.0 | Stripe payment integration |

---

## 9. Technical Debt & Known Issues

1.  **Dual-Run Schema Synchronization:** Syncing `erp_departments` and `erp_department` via database triggers prevents visual regressions but introduces concurrency and synchronization overhead. Other tables pending migration.
2.  **No Front Controller Router:** Lack of a centralized router means security policies and layouts must be included manually on every endpoint via file-level `include` statements.
3.  **Hybrid Autoloading Namespace split:** Source code is divided between class autoloading in `classes/` (no namespace, manual requires) and `src/` (PSR-4 App namespace). Only the Department module has been fully migrated.
4.  **Legacy Frontend Classes:** The `classes/frontend/` directory still contains 21 frontend helper classes (Blogs, Categories, Companies, HSCodes, etc.) that were used by the now-removed public website. These are retained for reference but are orphaned.
5.  **Large Monolithic Files:** Several page controllers exceed 50KB (e.g., `quotations.php` at 194KB, `sale_orders.php` at 196KB, `jobs.php` at 154KB), indicating opportunities for extraction into services.
6.  **Mixed Database Access:** Dashboard pages still use `$mysqli` (MySQLi) directly alongside the new PDO-based `App\Core\Database` wrapper. Full migration to PDO is pending.
7.  **Global State:** Bootstrap relies on global variables (`$mysqli`, `$project_pre`, `$session_user_id`) and `function_exists()` guards rather than dependency injection.
