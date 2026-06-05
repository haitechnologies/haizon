# Haipulse Technical Specifications & Architecture Guide

This document provides a comprehensive technical overview of the Haipulse platform, detailing its core architecture, database schema, layout components, styling framework, and security implementation.

---

## 1. Core Architecture & Stack
Haipulse is a secure, multi-tenant enterprise resource planning (ERP) system designed around a **Strict Layered Architecture** using a backend-only PHP/MySQL stack.

### Stack Specifications
*   **Language**: PHP 8.2+ (Enforcing strict typing, native property hints, readonly DTOs, and typed Exceptions).
*   **Database**: MySQL 8.0+ (InnoDB engine, `utf8mb4_unicode_ci` default charset).
*   **Autoloading**: Composer PSR-4. The namespace `App\` is mapped directly to the `src/` directory.
*   **Routing**: Self-contained routing endpoints under the `/dashboard` segment. Root request paths (`/`) redirect immediately to [index.php](file:///g:/xampp/htdocs/haipulse/index.php) which performs a relative redirect to the backend login page.

### Strict Layered Pattern
The system separates responsibilities across four distinct layers under `src/`:
1.  **Controllers** (Validation & Routing): Responds to HTTP/AJAX requests, sanitizes inputs, handles view rendering, and captures exceptions.
2.  **Services** (Business Logic): Implements business constraints, handles organizational entitlements, and coordinates repositories.
3.  **Repositories** (Data Access): Performs isolated database queries (one repository per table). Communicates strictly via PDO prepared statements.
4.  **Models** (Readonly DTOs): Immutable structures representing database records.

---

## 2. Database Layer & Conventions

### Naming Rules
*   **Tables**: All tables utilize the `erp_` prefix and are named in singular lowercase format (e.g., `erp_user`, `erp_department`).
*   **Columns**: Named in `snake_case` (e.g., `organization_id`, `created_by`).
*   **Indexes**: Multi-column and foreign key indexes use the prefix `idx_` or `fk_` (e.g., `idx_department_organization_id`).

### Audit & System Columns
Every primary business table contains the following columns:
*   `id`: `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` (or `CHAR(36)` UUID).
*   `organization_id`: `INT UNSIGNED` (Multi-tenant context identifier).
*   `created_at`: `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`.
*   `updated_at`: `TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`.
*   `created_by`: `INT` (User ID who created the record).

### Co-Existence & Triggers (Dual-Run Migration)
To transition from legacy plural tables to modern singular tables without breaking legacy modules, bidirectional database triggers are implemented.
*   **Trigger Naming**: `trg_erp_tablename_after_action`.
*   **Recursion Guard**: Triggers inspect the session variable `@sync_in_progress`. If `@sync_in_progress` is equal to `1`, the trigger terminates immediately to prevent infinite update/insert loops.

---

## 3. Database Table Registry (Categorized Mapping)
The following tables are registered under [DB.php](file:///g:/xampp/htdocs/haipulse/classes/DB.php):

| Category | Table Name | Purpose / Business Scope |
| :--- | :--- | :--- |
| **User & Authentication** | `erp_users` | Backend dashboard users and login credentials |
| | `erp_roles` | User access control roles (Admin, Manager, etc.) |
| | `erp_permissions` | Individual system access privilege rules |
| | `erp_authentication_activity` | Audit trail for logins, MFA validations, and logouts |
| **HR & Payroll** | `erp_department` | Department divisions (PSR-4 singular table) |
| | `erp_designations` | Employee positions and job roles |
| | `erp_attendance` | Daily employee check-in and check-out logs |
| | `erp_leave_requests` | Paid time off requests and approval states |
| | `erp_payroll_components` | Earnings and deduction categories for salaries |
| | `erp_payroll_runs` | Monthly payroll calculation headers |
| | `erp_payslips` | Individual employee generated payslips |
| **Sales & Accounting** | `erp_invoices` | Sales invoice headers |
| | `erp_invoice_items` | Individual line items on sales invoices |
| | `erp_payments_received` | Recorded payments against client invoices |
| | `erp_accounts` | Chart of Accounts ledger nodes |
| | `erp_journals` | General ledger double-entry journal headers |
| | `erp_journal_items` | Journal debits and credits details |
| **Logistics & Shipping** | `erp_shipping_advices` | Logistics dispatch notices |
| | `erp_shipping_invoices` | Freight invoicing details |
| | `erp_shipping_stocks` | Inventory balance tracking |
| | `erp_carriers` | Shipping and transport logistics providers |
| | `erp_consignees` | Cargo receiving entities |
| | `erp_shippers` | Freight dispatching companies |
| | `erp_ports` | Master data of international shipping ports |
| **CRM & Leads** | `erp_leads` | Prospective clients and sales opportunities |
| | `erp_projects` | Ongoing client assignments and contract tasks |
| | `erp_jobs` | Scheduled operations and service actions |
| **Security & Utilities** | `erp_disposable_email` | Allowed/blocked spam email domains blocklist |
| | `erp_banned_words` | Content filtering dictionary |
| | `erp_ip_countries` | INET_ATON-based IP to country geolocation database |

---

## 4. UI Architecture & Design System

### Layout Structure
The dashboard HTML interface resides under `/dashboard` and uses a modular layout system:
*   **Header** (`admin_elements/admin_header.php`): Embeds Google Fonts (`IBM Plex Sans`, `Space Grotesk`), CSS stylesheet arrays, CSRF parameters, and top navigation bar elements.
*   **Sidebar** (`admin_elements/sidebar.php`): Left sidebar navigation containing collapsible menu trees for ERP sections (CRM, Accounting, HR, Shipping, System & Setup, Content).
*   **Footer** (`admin_elements/admin_footer.php`): Script bundles and browser layout corrections.

### CSS Styling & Framework
The visual interface is built upon the **Limitless Responsive Bootstrap** design framework. It loads stylesheets from [assets/assets_custom/css/](file:///g:/xampp/htdocs/haipulse/dashboard/assets/assets_custom/css):
*   `bootstrap.min.css`: Core responsive grid framework.
*   `bootstrap_limitless.min.css`: Custom utility classes and card panels designed for the Limitless theme.
*   `components.min.css`: Design tokens for forms, alerts, navigation bars, dropdowns, and widgets.
*   `layout.min.css`: Left-sidebar flexbox wrappers, content-wrapper containers, and scroll controls.
*   `haipulse-dashboard-compat.css`: Legacy theme override values and spacing fixes.
*   `all.min.css`: FontAwesome and icon fonts.

### Server-Side DataTables
Interactive grid tables (e.g. `listing_departments.php`) are rendered dynamically:
*   **Initializer**: [dashboard-datatable-initializer.js](file:///g:/xampp/htdocs/haipulse/assets/js/dashboard-datatable-initializer.js) configures columns, sorting, pagination, and CSRF token injection.
*   **Ajax Router**: [datatables_dispatcher.php](file:///g:/xampp/htdocs/haipulse/dashboard/datatables_dispatcher.php) captures AJAX POST requests.
*   **Handlers**: Subclasses extending `BaseDataTable` (under `classes/DataTable/`) execute queries and return structured JSON records.

---

## 5. Security & Isolation Controls

### CSRF Token Protection
All forms and AJAX request payloads must include a valid CSRF token.
*   Tokens are generated and validated via the `csrf_token()` and `validate_csrf_token()` functions.
*   AJAX POST queries (including DataTables search/filter queries) append the token dynamically.

### Tenant Isolation (Multi-Tenancy)
*   Queries executed on organization-scoped tables (listed in [OrgIdInjectionMiddleware.php](file:///g:/xampp/htdocs/haipulse/classes/OrgIdInjectionMiddleware.php)) must filter by `organization_id`.
*   As an additional safety net, the `OrgIdInjectionMiddleware` intercepts raw `SELECT` queries on org-scoped tables and automatically injects the corresponding `WHERE organization_id = {activeOrgId}` condition to prevent multi-tenant data leaks.

### Session & MFA Hardening
*   **MFA (2FA)**: Time-based One-Time Password (TOTP) verification is enforced on production environments via [security.php](file:///g:/xampp/htdocs/haipulse/dashboard/admin_elements/security.php).
*   **Session Hijacking**: Session validation checks the client User-Agent and IP address signature and triggers automatic logouts on mismatch.
*   **Cookie Security**: Configures `session.cookie_httponly = 1`, `session.cookie_samesite = 'Strict'`, and `session.use_strict_mode = 1` to prevent CSRF and session fixation attacks.
