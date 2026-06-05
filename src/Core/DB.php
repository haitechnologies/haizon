<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Database Table Registry
 *
 * Central location for all database table names with organized categories.
 * Provides IDE autocomplete, type safety, and eliminates runtime database queries.
 *
 * @package App\Core
 */
class DB
{
    /**
     * Table prefix for dashboard system
     */
    private const PREFIX = 'erp_';

    // ================================
    // USER & AUTHENTICATION TABLES
    // ================================

    /** System users table */
    public const USERS = self::PREFIX . 'users';

    /** User roles table */
    public const ROLES = self::PREFIX . 'roles';

    /** Authentication activity tracking table */
    public const AUTHENTICATION_ACTIVITY = self::PREFIX . 'authentication_activity';

    /** Rate limiting attempts tracking table */
    public const RATE_LIMIT_ATTEMPTS = self::PREFIX . 'rate_limit_attempts';

    /** Rate limiting public attempts tracking table */
    public const RATE_LIMIT_PUBLIC = self::PREFIX . 'rate_limit_public';

    /** System permissions table */
    public const PERMISSIONS = self::PREFIX . 'permissions';

    /** Module-specific permissions table */
    public const MODULE_PERMISSIONS = self::PREFIX . 'module_permissions';

    // ================================
    // HR & PAYROLL TABLES
    // ================================

    /** Department master data */
    public const DEPARTMENTS = self::PREFIX . 'departments';

    /** Department master data (modern singular) */
    public const DEPARTMENT = self::PREFIX . 'department';

    /** Employee designations */
    public const DESIGNATIONS = self::PREFIX . 'designations';

    /** User document records */
    public const USER_DOCUMENTS = self::PREFIX . 'user_documents';

    /** Attendance records */
    public const ATTENDANCE = self::PREFIX . 'attendance';

    /** Leave request transactions */
    public const LEAVE_REQUESTS = self::PREFIX . 'leave_requests';

    /** Leave type definitions */
    public const LEAVE_TYPES = self::PREFIX . 'leave_types';

    /** Payroll component setup */
    public const PAYROLL_COMPONENTS = self::PREFIX . 'payroll_components';

    /** Salary structure setup */
    public const SALARY_STRUCTURES = self::PREFIX . 'salary_structures';

    /** Employee salary assignments */
    public const EMPLOYEE_SALARIES = self::PREFIX . 'employee_salaries';

    /** Payroll run headers */
    public const PAYROLL_RUNS = self::PREFIX . 'payroll_runs';

    /** Generated payslips */
    public const PAYSLIPS = self::PREFIX . 'payslips';

    // ================================
    // SYSTEM & CONFIGURATION TABLES
    // ================================

    /** System settings table */
    public const SYSTEM_SETTINGS = self::PREFIX . 'system_settings';

    /** System modules table */
    public const MODULES = self::PREFIX . 'modules';

    /** Error log read status tracking */
    public const ERROR_LOG_STATUS = self::PREFIX . 'error_log_status';

    /** Canonical backend error events sink */
    public const BACKEND_ERROR_LOGS = self::PREFIX . 'backend_error_logs';

    /** Backend page/module logging coverage manifest */
    public const BACKEND_LOG_COVERAGE = self::PREFIX . 'backend_log_coverage';

    /** Email service providers configuration */
    public const EMAIL_PROVIDERS = self::PREFIX . 'email_providers';

    // ================================
    // EMAIL MARKETING TABLES
    // ================================

    /** Email sending history */
    public const EMAIL_HISTORY = self::PREFIX . 'email_history';

    /** Email send queue */
    public const EMAIL_QUEUE = self::PREFIX . 'email_queue';

    // ================================
    // CRM - CUSTOMER MANAGEMENT TABLES
    // ================================

    /** Customers master table */
    public const CUSTOMERS = self::PREFIX . 'customers';

    /** Customer contact persons */
    public const CUSTOMER_CONTACTS = self::PREFIX . 'customer_contacts';

    /** Customer addresses (delivery, billing, etc.) */
    public const CUSTOMER_ADDRESSES = self::PREFIX . 'customer_addresses';

    /** Unified entity activity logs (leads + customers) */
    public const ENTITY_LOGS = self::PREFIX . 'entity_logs';

    /** Unified entity notes (leads + customers) */
    public const ENTITY_NOTES = self::PREFIX . 'entity_notes';

    // ================================
    // SALES & INVOICING TABLES
    // ================================

    /** Sales invoices table */
    public const INVOICES = self::PREFIX . 'invoices';

    /** Invoice line items */
    public const INVOICE_ITEMS = self::PREFIX . 'invoice_items';

    // ================================
    // PAYMENTS & FINANCIAL TABLES
    // ================================

    /** Payment methods (cash, bank transfer, etc.) */
    public const PAYMENT_METHODS = self::PREFIX . 'payment_methods';

    // ================================
    // SUBSCRIPTION & MONETIZATION TABLES
    // ================================

    /** System audit log for tracking user actions and events */
    public const AUDIT_LOG = self::PREFIX . 'audit_log';

    /** Subscription tier change audit log */
    public const SUBSCRIPTION_LOGS = self::PREFIX . 'subscription_logs';



    /** SaaS subscription plan catalog */
    public const SUBSCRIPTION_PLANS = self::PREFIX . 'subscription_plans';

    /** Main-account SaaS subscriptions */
    public const SUBSCRIPTIONS = self::PREFIX . 'subscriptions';

    /** Per-plan SaaS feature values and system toggles */
    public const SUBSCRIPTION_PLAN_FEATURES = self::PREFIX . 'subscription_plan_features';

    /** Superadmin/account-level SaaS feature overrides */
    public const SUBSCRIPTION_OVERRIDES = self::PREFIX . 'subscription_overrides';

    /** API keys for Pro/Enterprise users */
    public const API_KEYS = self::PREFIX . 'api_keys';

    // ================================
    // BUSINESS LISTING SUBSCRIPTION TABLES
    // ================================

    /** Business listing plan catalog (Free, Silver, Gold, Platinum) */
    public const LISTING_PLANS = self::PREFIX . 'listing_plans';

    /** Per-company listing subscription records */
    public const LISTING_SUBSCRIPTIONS = self::PREFIX . 'listing_subscriptions';

    /** @deprecated Decommissioned table */
    public const PUBLIC_ADS = self::PREFIX . 'public_ads';

    // ================================
    // GEOGRAPHY & LOCATION TABLES
    // ================================

    /** Countries master data */
    public const GEO_COUNTRIES = self::PREFIX . 'geo_countries';

    /** States/Provinces master data */
    public const GEO_STATES = self::PREFIX . 'geo_states';

    /** Cities master data */
    public const GEO_CITIES = self::PREFIX . 'geo_cities';

    // ================================
    // INVENTORY & ORGANIZATION TABLES
    // ================================

    /** Items/Products master table */
    public const ITEMS = self::PREFIX . 'items';

    /** Organizations/Storage locations */
    public const ORGANIZATIONS = self::PREFIX . 'organizations';

    /** Organization membership records */
    public const ORGANIZATION_MEMBERSHIPS = self::PREFIX . 'organization_memberships';

    /** Organization-scoped role catalog */
    public const ORGANIZATION_ROLES = self::PREFIX . 'organization_roles';

    /** Organization membership to role assignments */
    public const ORGANIZATION_MEMBER_ROLES = self::PREFIX . 'organization_member_roles';

    /** Pending organization invite records */
    public const ORGANIZATION_INVITES = self::PREFIX . 'organization_invites';

    /** Effective organization system entitlements */
    public const ORGANIZATION_SYSTEM_ENTITLEMENTS = self::PREFIX . 'organization_system_entitlements';

    // ================================
    // SHIPPING & LOGISTICS TABLES
    // ================================

    /** Shipping customers master data */
    public const SHIPPING_CUSTOMERS = self::PREFIX . 'shipping_customers';

    /** Shipping advice documents */
    public const SHIPPING_ADVICES = self::PREFIX . 'shipping_advices';

    /** Shipping advice line items */
    public const SHIPPING_ADVICE_ITEMS = self::PREFIX . 'shipping_advice_items';

    /** Shipping invoices */
    public const SHIPPING_INVOICES = self::PREFIX . 'shipping_invoices';

    /** Shipping invoice line items */
    public const SHIPPING_INVOICE_ITEMS = self::PREFIX . 'shipping_invoice_items';

    /** Shipping inventory snapshots */
    public const SHIPPING_STOCKS = self::PREFIX . 'shipping_stocks';

    /** Ports master data */
    public const PORTS = self::PREFIX . 'ports';

    /** Carriers master data */
    public const CARRIERS = self::PREFIX . 'carriers';

    /** Consignees master data */
    public const CONSIGNEES = self::PREFIX . 'consignees';

    /** Shippers master data */
    public const SHIPPERS = self::PREFIX . 'shippers';

    // ================================
    // SETUP & MASTER DATA TABLES
    // ================================

    /** Lead/Customer sources (website, referral, etc.) */
    public const SETUP_SOURCES = self::PREFIX . 'setup_sources';

    /** Lead/Customer statuses */
    public const SETUP_STATUSES = self::PREFIX . 'setup_statuses';

    /** Tags for categorization */
    public const SETUP_TAGS = self::PREFIX . 'setup_tags';

    /** Content moderation - banned words */
    public const BANNED_WORDS = self::PREFIX . 'banned_words';

    /** @deprecated Decommissioned table */
    public const BLOG_CATEGORIES = self::PREFIX . 'blog_categories';

    /** @deprecated Decommissioned table */
    public const BLOGS = self::PREFIX . 'blogs';

    /** CMS pages */
    public const PAGES = self::PREFIX . 'pages';

    // ================================
    // HS CODES (HARMONIZED SYSTEM)
    // ================================

    /** @deprecated Decommissioned table */
    public const HS_CODE_SETS = self::PREFIX . 'hs_code_sets';

    /** HS Codes master table */
    public const HS_CODES = self::PREFIX . 'hscodes';

    /** Legacy HS Codes table name (pre-rename) */
    public const HS_CODES_LEGACY = self::PREFIX . 'hs_codes';

    /** @deprecated Decommissioned table */
    public const HS_CODE_TEXTS = self::PREFIX . 'hs_code_texts';

    /** HS Code to Category mappings (junction table) */
    public const CATEGORY_HS_CODES = self::PREFIX . 'category_hs_codes';

    /** HS Code to Subcategory mappings (junction table) */
    public const SUBCATEGORY_HS_CODES = self::PREFIX . 'subcategory_hs_codes';

    // ================================
    // COMPANY DIRECTORY
    // ================================

    /** @deprecated Decommissioned table */
    public const COMPANIES = self::PREFIX . 'companies';

    /** @deprecated Decommissioned table */
    public const COMPANIES_DETAILS = self::PREFIX . 'companies_details';

    /** @deprecated Decommissioned table */
    public const COMPANY_SOURCES = self::PREFIX . 'setup_sources';

    /** @deprecated Decommissioned table */
    public const COMPANY_ENGAGEMENT = self::PREFIX . 'company_engagement';
    /** Referral tracking codes (legacy compatibility) */
    public const REFERRAL_CODES = self::PREFIX . 'referral_codes';



    // ================================
    // NEW HIERARCHICAL CATEGORY SYSTEM
    // ================================

    /** Level 1: Main categories (33 total) */
    public const CATEGORIES = self::PREFIX . 'categories';

    /** Level 2: Subcategories under main categories (111 total) */
    public const SUBCATEGORIES = self::PREFIX . 'subcategories';

    /** Category items junction table */
    public const CATEGORY_ITEMS = self::PREFIX . 'category_items';

    /** @deprecated Decommissioned table */
    public const IP_COUNTRIES = self::PREFIX . 'ip_countries';

    // ================================
    // ALERTS & NOTIFICATIONS
    // ================================

    /** System alerts and notifications */
    public const ALERTS = self::PREFIX . 'alerts';

    // ================================
    // SEARCH & ANALYTICS TABLES
    // ================================

    /** @deprecated Decommissioned table */
    public const SEARCHES = self::PREFIX . 'searches';

    /** Contact form inquiries */
    public const INQUIRIES = self::PREFIX . 'inquiries';

    /** Email thread replies for inquiries */
    public const INQUIRY_REPLIES = self::PREFIX . 'inquiry_replies';

    /** Disposable and temporary email domains */
    public const DISPOSABLE_EMAIL_DOMAINS = self::PREFIX . 'disposable_email_domains';



    // ================================
    // ACCOUNTING - CHART OF ACCOUNTS
    // ================================

    /** Chart of accounts (ledger accounts) */
    public const ACCOUNTS = self::PREFIX . 'accounts';

    /** Account report categories (P&L, Balance Sheet groupings) */
    public const ACCOUNTS_REPORT_CATEGORIES = self::PREFIX . 'accounts_report_categories';

    /** Account report subcategories */
    public const ACCOUNTS_REPORT_SUBCATEGORIES = self::PREFIX . 'accounts_report_subcategories';

    // ================================
    // ACCOUNTING - JOURNALS
    // ================================

    /** Journal entry headers */
    public const JOURNALS = self::PREFIX . 'journals';

    /** Journal entry line items */
    public const JOURNAL_ITEMS = self::PREFIX . 'journal_items';

    // ================================
    // ACCOUNTING - SALES TRANSACTIONS
    // ================================

    /** Sales quotations */
    public const QUOTATIONS = self::PREFIX . 'quotations';

    /** Quotation line items */
    public const QUOTATION_ITEMS = self::PREFIX . 'quotation_items';

    /** Sale orders */
    public const SALE_ORDERS = self::PREFIX . 'sale_orders';

    /** Sale order line items */
    public const SALE_ORDER_ITEMS = self::PREFIX . 'sale_order_items';

    /** Sale type definitions */
    public const SALE_TYPES = self::PREFIX . 'sale_types';

    /** Payments received from customers */
    public const PAYMENTS_RECEIVED = self::PREFIX . 'payments_received';

    /** Credit note headers */
    public const CREDIT_NOTES = self::PREFIX . 'credit_notes';

    /** Credit note line items */
    public const CREDIT_NOTE_ITEMS = self::PREFIX . 'credit_note_items';

    // ================================
    // ACCOUNTING - PURCHASE TRANSACTIONS
    // ================================

    /** Vendors/Suppliers master table */
    public const VENDORS = self::PREFIX . 'vendors';

    /** Vendor contact persons */
    public const VENDOR_CONTACTS = self::PREFIX . 'vendor_contacts';

    /** Purchase transactions */
    public const PURCHASES = self::PREFIX . 'purchases';

    /** Purchase line items */
    public const PURCHASE_ITEMS = self::PREFIX . 'purchase_items';

    /** Purchase orders */
    public const PURCHASE_ORDERS = self::PREFIX . 'purchase_orders';

    /** Purchase order line items */
    public const PURCHASE_ORDER_ITEMS = self::PREFIX . 'purchase_order_items';

    /** Purchase type definitions */
    public const PURCHASE_TYPES = self::PREFIX . 'purchase_types';

    /** Payments made to vendors */
    public const PAYMENTS_MADE = self::PREFIX . 'payments_made';

    /** Debit note headers */
    public const DEBIT_NOTES = self::PREFIX . 'debit_notes';

    /** Debit note line items */
    public const DEBIT_NOTE_ITEMS = self::PREFIX . 'debit_note_items';

    // ================================
    // ACCOUNTING - EXPENSES
    // ================================

    /** Expense transactions */
    public const EXPENSES = self::PREFIX . 'expenses';

    // ================================
    // ACCOUNTING - BANKING & FINANCE SETUP
    // ================================

    /** Bank accounts master data */
    public const BANKS = self::PREFIX . 'banks';

    /** Tax treatment definitions */
    public const TAX_TREATMENTS = self::PREFIX . 'tax_treatments';

    /** Payment terms (Net 30, etc.) */
    public const PAYMENT_TERMS = self::PREFIX . 'payment_terms';

    /** Currency definitions */
    public const CURRENCIES = self::PREFIX . 'currencies';

    // ================================
    // CRM - LEADS
    // ================================

    /** Leads/prospects master table */
    public const LEADS = self::PREFIX . 'leads';

    /** Lead attachments */
    public const LEAD_ATTACHMENTS = self::PREFIX . 'lead_attachments';

    // ================================
    // CRM - PROJECTS & JOBS
    // ================================

    /** Projects master table */
    public const PROJECTS = self::PREFIX . 'projects';

    /** Jobs / service orders */
    public const JOBS = self::PREFIX . 'jobs';

    /** Job status definitions */
    public const JOB_STATUSES = self::PREFIX . 'job_statuses';

    // ================================
    // OPERATIONAL SETUP
    // ================================

    /** Incoterms (EXW, FOB, CIF, etc.) */
    public const INCOTERMS = self::PREFIX . 'incoterms';

    /** Exit points / customs checkpoints */
    public const EXIT_POINTS = self::PREFIX . 'exit_points';

    /** Container type definitions */
    public const CONTAINER_TYPES = self::PREFIX . 'container_types';

    /** Commodity type definitions */
    public const COMMODITY_TYPES = self::PREFIX . 'commodity_types';

    // ================================
    // WAREHOUSE & STORAGE SETUP
    // ================================

    /** Warehouses master data */
    public const WAREHOUSES = self::PREFIX . 'warehouses';

    /** Storage type definitions */
    public const STORAGE_TYPES = self::PREFIX . 'storage_types';

    /** Storage subtype definitions */
    public const STORAGE_SUBTYPES = self::PREFIX . 'storage_subtypes';

    // ================================
    // PRODUCT / SERVICE SETUP
    // ================================

    /** Services master table */
    public const SERVICES = self::PREFIX . 'services';

    /** Units of measure */
    public const UNITS = self::PREFIX . 'units';

    // ================================
    // SETUP GROUPS & DOCUMENT MANAGEMENT
    // ================================

    /** Setup groups (for CRM pipeline stages) */
    public const SETUP_GROUPS = self::PREFIX . 'setup_groups';

    /** Document categories */
    public const DOCUMENT_CATEGORIES = self::PREFIX . 'document_categories';

    // ================================
    // UTILITY METHODS
    // ================================

    /**
     * Get table name with dashboard prefix
     *
     * @param string $tableName Table name without prefix
     * @return string Full table name with erp_ prefix
     */
    public static function table(string $tableName): string
    {
        return self::PREFIX . $tableName;
    }

    /**
     * Get all dashboard table constants as array
     *
     * @return array Array of all erp_ prefixed table names
     */
    public static function getAllTables(): array
    {
        $reflection = new \ReflectionClass(__CLASS__);
        $constants = $reflection->getConstants();

        // Filter only erp_ tables (exclude legacy and prefix constants)
        return array_filter($constants, function ($value) {
            return is_string($value) && strpos($value, self::PREFIX) === 0;
        });
    }

    /**
     * Check if table constant exists
     *
     * @param string $constantName Constant name (e.g., 'USERS')
     * @return bool True if constant exists
     */
    public static function hasTable(string $constantName): bool
    {
        $reflection = new \ReflectionClass(__CLASS__);
        return $reflection->hasConstant($constantName);
    }

    /**
     * Get dashboard table prefix
     *
     * @return string Returns 'erp_'
     */
    public static function getPrefix(): string
    {
        return self::PREFIX;
    }

    /**
     * Get constant name by table name (reverse lookup)
     *
     * @param string $tableName Full table name (e.g., 'erp_users')
     * @return string|null Constant name (e.g., 'USERS') or null if not found
     */
    public static function getConstantName(string $tableName): ?string
    {
        $reflection = new \ReflectionClass(__CLASS__);
        $constants = $reflection->getConstants();

        $key = array_search($tableName, $constants, true);
        return $key !== false ? $key : null;
    }

    /**
     * Retrieve the global MySQLi connection.
     *
     * @return \mysqli
     */
    public static function mysqli(): \mysqli
    {
        global $mysqli;
        if (!$mysqli instanceof \mysqli) {
            $mysqli = $GLOBALS['DB']['MSQLI'] ?? null;
        }
        if (!$mysqli instanceof \mysqli) {
            throw new \RuntimeException("MySQLi connection is not initialized.");
        }
        return $mysqli;
    }

    /**
     * Retrieve the Database PDO wrapper instance.
     *
     * @return Database
     */
    public static function pdo(): Database
    {
        $container = Container::getInstance();
        if (!$container->has(Database::class)) {
            $container->register(Database::class, function () {
                return new Database();
            });
        }
        return $container->get(Database::class);
    }

    /**
     * Retrieve the active PDO connection instance from the Database wrapper.
     *
     * @return \PDO
     */
    public static function conn(): \PDO
    {
        return self::pdo()->getConnection();
    }
}
