<?php

// Load environment variables from .env file
// Check multiple locations for .env file (in order of priority):
// 1. Parent directory: G:\xampp\.env (recommended for security)
// 2. Project root: G:\xampp\htdocs\haipulse\.env (current location, less secure)

require_once __DIR__ . '/../vendor/autoload.php';

$envCandidates = [
	dirname(__DIR__, 1), // e.g., G:\xampp\htdocs\haipulse (project root â€” checked first)
	dirname(__DIR__, 2), // e.g., G:\xampp\htdocs
	dirname(__DIR__, 3), // e.g., G:\xampp (last resort)
];

$envLoaded = false;
foreach ($envCandidates as $candidateDir) {
	if (is_file($candidateDir . '/.env')) {
		$dotenv = Dotenv\Dotenv::createImmutable($candidateDir);
		$dotenv->safeLoad();
		$envLoaded = true;
		break;
	}
}

// Backward compatibility fallback: don't hard-fail if .env is missing.
if (!$envLoaded) {
	$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1));
	$dotenv->safeLoad();
}

// ============================================
// DATABASE CONFIGURATION
// ============================================
if (!function_exists('isRemote')) {
    function isRemote() {
		if (preg_match('/haipulse.local/', $_SERVER['HTTP_HOST'] ?? '') || 
            preg_match('/localhost/', $_SERVER['HTTP_HOST'] ?? '') || 
            preg_match('/127.0.0.1/', $_SERVER['HTTP_HOST'] ?? '')) {
            return false;
        }
        return true;
    }
}

// Load database configuration based on environment
$getEnv = static function ($key, $default = '') {
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return $value;
};

$app_env = strtolower((string)$getEnv('APP_ENV', ''));

$localDb = [
    'hostname' => $getEnv('DB_HOSTNAME', 'localhost'),
    'username' => $getEnv('DB_USERNAME', ''),
    'password' => $getEnv('DB_PASSWORD', ''),
    'database' => $getEnv('DB_DATABASE', ''),
];

$remoteDb = [
    'hostname' => $getEnv('REMOTE_DB_HOSTNAME', $localDb['hostname']),
    'username' => $getEnv('REMOTE_DB_USERNAME', $localDb['username']),
    'password' => $getEnv('REMOTE_DB_PASSWORD', $localDb['password']),
    'database' => $getEnv('REMOTE_DB_DATABASE', $localDb['database']),
];

if ($app_env === 'development') {
    $dbConfig = $localDb;
} else {
    $dbConfig = isRemote() ? $remoteDb : $localDb;
}

// Set global database config
$GLOBALS['DB'] = [
	'HOSTNAME' => $dbConfig['hostname'],
	'DATABASE' => $dbConfig['database'],
	'USERNAME' => $dbConfig['username'],
	'PASSWORD' => $dbConfig['password'],
];

// Establish database connection with short retry for transient saturation.
$maxConnectRetries = max(1, (int)$getEnv('DB_CONNECT_RETRIES', 2));
$connectRetryDelayMs = max(50, (int)$getEnv('DB_CONNECT_RETRY_DELAY_MS', 250));
$mysqli = null;
$lastConnectError = '';
$lastConnectErrno = 0;

for ($attempt = 1; $attempt <= $maxConnectRetries; $attempt++) {
	try {
		$mysqli = new mysqli(
			$dbConfig['hostname'],
			$dbConfig['username'],
			$dbConfig['password'],
			$dbConfig['database']
		);

		if (!$mysqli->connect_error) {
			break;
		}

		$lastConnectErrno = (int)$mysqli->connect_errno;
		$lastConnectError = (string)$mysqli->connect_error;
	} catch (mysqli_sql_exception $e) {
		$lastConnectErrno = (int)$e->getCode();
		$lastConnectError = (string)$e->getMessage();
	}

	if ($attempt < $maxConnectRetries) {
		usleep($connectRetryDelayMs * 1000);
	}
}

if (!($mysqli instanceof mysqli) || $mysqli->connect_error) {
	if (isRemote()) {
		http_response_code(503);
		header('Retry-After: 5');
		error_log('Database Connection Error (' . $lastConnectErrno . '): ' . $lastConnectError);
		exit('Service temporarily unavailable. Please retry shortly.');
	}

	die('Database Connection Error (' . $lastConnectErrno . '): ' . $lastConnectError);
}

// Note: Error logging is handled separately for frontend and dashboard:
// - Frontend: Uses FrontendErrorLogger (initialized in config/logging.php)
// - Dashboard: Uses dashboard ErrorLogger (required in admin_elements/admin_header.php)
// Do NOT require dashboard error logger here as it would override frontend error handling!

// --------------------------
// --------------------------
// SET TO utf8mb4 Encoding (Full Unicode + Emoji Support)
// --------------------------
$mysqli->set_charset("utf8mb4");
// --------------------------
// --------------------------


$GLOBALS['DB']['MSQLI']      = $mysqli;
$tbl_prefix                  = 'erp_';
$GLOBALS['TBL']['PREFIX']    = $tbl_prefix;
$project_pre                 = 'haipulse'; 			// Unique Session_id Name
$GLOBALS['project_pre']      = $project_pre;    	// fp__($tbl_name, $id) globals.php

// Create $conn alias for backward compatibility with frontend pages
$conn = $mysqli;

// Load global helper functions after DB is ready
require_once __DIR__ . '/globals.php';

// Initialize database schema (create missing tables if needed)
require_once __DIR__ . '/../classes/DatabaseSchemaInitializer.php';
$autoInitSchemaEnv = strtolower((string)$getEnv('DB_AUTO_INIT_SCHEMA', isRemote() ? 'false' : 'true'));
if ($autoInitSchemaEnv === 'true' || $autoInitSchemaEnv === '1' || $autoInitSchemaEnv === 'yes') {
	DatabaseSchemaInitializer::init($mysqli);
}

// Ensure connections are explicitly released even on abrupt request endings.
register_shutdown_function(static function () use ($mysqli) {
	if ($mysqli instanceof mysqli) {
		try {
			$mysqli->close();
		} catch (Throwable $e) {
			// Connection may already be closed by request flow; ignore safely.
		}
	}
});


/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/


/*
|--------------------------------------------------------------------------
| 	Display All Errors
|--------------------------------------------------------------------------
|
*/

error_reporting(E_ALL);
ini_set('display_errors', $app_env === 'development' ? '1' : '0');


/*
|--------------------------------------------------------------------------
| 	SET SERVER TIMEZONE
|--------------------------------------------------------------------------
|
*/

$appTimezone = (string)$getEnv('APP_TIMEZONE', 'Asia/Dubai');
if (!@date_default_timezone_set($appTimezone)) {
    date_default_timezone_set('Asia/Dubai');
}

$dbTimezone = (string)$getEnv('DB_TIMEZONE', '+00:00');
$mysqli->query("SET time_zone = '" . $mysqli->real_escape_string($dbTimezone) . "'");

/*
|--------------------------------------------------------------------------
| 	add WWW. TO http:// -> https
|--------------------------------------------------------------------------
|
*/
// if ( isRemote() ){
// 	if ( !preg_match ('/www./', $_SERVER['HTTP_HOST']) || empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off" ) {
// 		header('Location:https://www.' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . $_SERVER['REQUEST_URI']);
// 		exit();
// 	}
// // local move to ssl
// } else {
// if ( empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off" ) {
// 	header('Location:https://'.  $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
// 	exit();
// }
// }

/*
|--------------------------------------------------------------------------
| 	FORCE SSL -> https
|--------------------------------------------------------------------------
|
*/

if (!empty($_ENV['FORCE_HTTPS']) && $_ENV['FORCE_HTTPS'] === 'true' && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
	header('Location:https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	exit();
}


/*
|--------------------------------------------------------------------------
| 	GLOBAL SITE URL
|--------------------------------------------------------------------------
|
*/
// Note: $base_url is defined in config/globals.php, not loaded here yet
// $GLOBALS['SETTINGS']['SITE_URL']	= $base_url;


/*
|--------------------------------------------------------------------------
| 	LOAD DATABASE TABLE REGISTRY CLASS
|--------------------------------------------------------------------------
| Load the modern DB class which provides IDE autocomplete and type safety
| for all database table names without requiring database queries
*/

require_once __DIR__ . '/../classes/DB.php';

/*
|--------------------------------------------------------------------------
| 	LOAD ROLES CONSTANTS CLASS
|--------------------------------------------------------------------------
| Load the Roles class which provides centralized role management
| and eliminates hardcoded role IDs throughout the application
*/

require_once __DIR__ . '/../classes/Roles.php';

/*
|--------------------------------------------------------------------------
| 	LOAD UAE GEOGRAPHIC DATA CONSTANTS
|--------------------------------------------------------------------------
| Load UAE country and states data as constants instead of database tables
| This replaces geo_countries and geo_states tables with hardcoded data
*/

require_once __DIR__ . '/uae_geo_constants.php';

/*
|--------------------------------------------------------------------------
| 	BACKWARD COMPATIBILITY CONSTANTS
|--------------------------------------------------------------------------
| Define old tbl_ constants for backward compatibility with existing code
| These will be gradually phased out in favor of DB:: class constants
| 
| Migration: Replace tbl_users with DB::USERS throughout the codebase
*/

// User & Authentication Tables
define('tbl_users', DB::USERS);
define('tbl_roles', DB::ROLES);
define('tbl_permissions', DB::PERMISSIONS);
define('tbl_module_permissions', DB::MODULE_PERMISSIONS);

// System & Configuration Tables
define('tbl_system_settings', DB::SYSTEM_SETTINGS);
define('tbl_modules', DB::MODULES);
define('tbl_error_log_status', DB::ERROR_LOG_STATUS);
define('tbl_backend_error_logs', DB::BACKEND_ERROR_LOGS);
define('tbl_backend_log_coverage', DB::BACKEND_LOG_COVERAGE);
define('tbl_email_providers', DB::EMAIL_PROVIDERS);
define('tbl_email_campaigns', DB::EMAIL_CAMPAIGNS);
define('tbl_email_templates', DB::EMAIL_TEMPLATES);
define('tbl_email_targets', DB::EMAIL_TARGETS);
define('tbl_email_history', DB::EMAIL_HISTORY);
define('tbl_email_queue', DB::EMAIL_QUEUE);
define('tbl_email_unsubscribes', DB::EMAIL_UNSUBSCRIBES);
define('tbl_email_bounces', DB::EMAIL_BOUNCES);
define('tbl_email_events', DB::EMAIL_EVENTS);
define('tbl_email_sends', DB::EMAIL_SENDS);

// CRM - Customer Management Tables
define('tbl_customers', DB::CUSTOMERS);
define('tbl_customer_contacts', DB::CUSTOMER_CONTACTS);
define('tbl_customer_addresses', DB::CUSTOMER_ADDRESSES);
// tbl_customer_comments, tbl_customer_attachments, tbl_customer_documents, tbl_customer_logs removed (tables merged/dropped)
define('tbl_entity_logs', DB::ENTITY_LOGS);
define('tbl_entity_notes', DB::ENTITY_NOTES);

// Sales & Invoicing Tables
define('tbl_invoices', DB::INVOICES);
define('tbl_invoice_items', DB::INVOICE_ITEMS);

// Payments & Financial Tables
define('tbl_payment_methods', DB::PAYMENT_METHODS);

// Geography & Location Tables
define('tbl_geo_countries', DB::GEO_COUNTRIES);
define('tbl_geo_states', DB::GEO_STATES);
define('tbl_geo_cities', DB::GEO_CITIES);

// Inventory & Organization Tables
define('tbl_items', DB::ITEMS);
define('tbl_organizations', DB::ORGANIZATIONS);

// Shipping & Logistics Tables
define('tbl_shipping_customers', DB::SHIPPING_CUSTOMERS);
define('tbl_shipping_advices', DB::SHIPPING_ADVICES);
define('tbl_shipping_advice_items', DB::SHIPPING_ADVICE_ITEMS);
define('tbl_shipping_invoices', DB::SHIPPING_INVOICES);
define('tbl_shipping_invoice_items', DB::SHIPPING_INVOICE_ITEMS);
define('tbl_shipping_stocks', DB::SHIPPING_STOCKS);
define('tbl_ports', DB::PORTS);
define('tbl_carriers', DB::CARRIERS);
define('tbl_consignees', DB::CONSIGNEES);
define('tbl_shippers', DB::SHIPPERS);

// HR & Payroll Tables
define('tbl_departments', DB::DEPARTMENTS);
define('tbl_designations', DB::DESIGNATIONS);
define('tbl_user_documents', DB::USER_DOCUMENTS);
define('tbl_attendance', DB::ATTENDANCE);
define('tbl_leave_requests', DB::LEAVE_REQUESTS);
define('tbl_leave_types', DB::LEAVE_TYPES);
define('tbl_payroll_components', DB::PAYROLL_COMPONENTS);
define('tbl_salary_structures', DB::SALARY_STRUCTURES);
define('tbl_employee_salaries', DB::EMPLOYEE_SALARIES);
define('tbl_payroll_runs', DB::PAYROLL_RUNS);
define('tbl_payslips', DB::PAYSLIPS);

// Accounting - Chart of Accounts
define('tbl_accounts', DB::ACCOUNTS);
define('tbl_accounts_report_categories', DB::ACCOUNTS_REPORT_CATEGORIES);
define('tbl_accounts_report_subcategories', DB::ACCOUNTS_REPORT_SUBCATEGORIES);

// Accounting - Journals
define('tbl_journals', DB::JOURNALS);
define('tbl_journal_items', DB::JOURNAL_ITEMS);

// Accounting - Sales Transactions
define('tbl_quotations', DB::QUOTATIONS);
define('tbl_quotation_items', DB::QUOTATION_ITEMS);
define('tbl_sale_orders', DB::SALE_ORDERS);
define('tbl_sale_order_items', DB::SALE_ORDER_ITEMS);
define('tbl_sale_types', DB::SALE_TYPES);
define('tbl_payments_received', DB::PAYMENTS_RECEIVED);
define('tbl_payment_received_items', DB::table('payment_received_items'));
define('tbl_credit_notes', DB::CREDIT_NOTES);
define('tbl_credit_note_items', DB::CREDIT_NOTE_ITEMS);

// Accounting - Purchase Transactions
define('tbl_vendors', DB::VENDORS);
define('tbl_vendor_contacts', DB::VENDOR_CONTACTS);
define('tbl_purchases', DB::PURCHASES);
define('tbl_purchase_items', DB::PURCHASE_ITEMS);
define('tbl_purchase_orders', DB::PURCHASE_ORDERS);
define('tbl_purchase_order_items', DB::PURCHASE_ORDER_ITEMS);
define('tbl_purchase_types', DB::PURCHASE_TYPES);
define('tbl_payments_made', DB::PAYMENTS_MADE);
define('tbl_payment_made_items', DB::table('payment_made_items'));
define('tbl_debit_notes', DB::DEBIT_NOTES);
define('tbl_debit_note_items', DB::DEBIT_NOTE_ITEMS);

// Accounting - Expenses
define('tbl_expenses', DB::EXPENSES);

// Accounting - Banking & Finance Setup
define('tbl_banks', DB::BANKS);
define('tbl_tax_treatments', DB::TAX_TREATMENTS);
define('tbl_payment_terms', DB::PAYMENT_TERMS);
define('tbl_currencies', DB::CURRENCIES);

// CRM - Leads
define('tbl_leads', DB::LEADS);
// tbl_lead_notes and tbl_lead_logs merged into tbl_entity_notes / tbl_entity_logs
define('tbl_lead_attachments', DB::LEAD_ATTACHMENTS);
// tbl_lead_quotations and tbl_lead_quotation_items removed (tables decommissioned)

// CRM - Projects & Jobs
define('tbl_projects', DB::PROJECTS);
define('tbl_jobs', DB::JOBS);
define('tbl_job_statuses', DB::JOB_STATUSES);

// Operational Setup
define('tbl_incoterms', DB::INCOTERMS);
define('tbl_exit_points', DB::EXIT_POINTS);
define('tbl_container_types', DB::CONTAINER_TYPES);
define('tbl_commodity_types', DB::COMMODITY_TYPES);

// Warehouse & Storage Setup
define('tbl_warehouses', DB::WAREHOUSES);
define('tbl_storage_types', DB::STORAGE_TYPES);
define('tbl_storage_subtypes', DB::STORAGE_SUBTYPES);

// Product / Service Setup
define('tbl_units', DB::UNITS);
// tbl_services, tbl_service_types removed (tables decommissioned)

// Setup Groups & Document Management
define('tbl_setup_groups', DB::SETUP_GROUPS);
define('tbl_document_categories', DB::DOCUMENT_CATEGORIES);
// tbl_documents removed (table decommissioned)

// Setup & Master Data Tables
define('tbl_setup_sources', DB::SETUP_SOURCES);
define('tbl_setup_statuses', DB::SETUP_STATUSES);
define('tbl_setup_tags', DB::SETUP_TAGS);
define('tbl_banned_words', DB::BANNED_WORDS);
define('tbl_blog_categories', DB::BLOG_CATEGORIES);
define('tbl_blogs', DB::BLOGS);
define('tbl_pages', DB::PAGES);
define('tbl_ip_countries', DB::IP_COUNTRIES);
// tbl_company_sources removed (table decommissioned)

// Alerts & Notifications
define('tbl_alerts', DB::ALERTS);

// Search & Analytics
define('tbl_searches', DB::SEARCHES);
define('tbl_inquiries', DB::INQUIRIES);

// Legacy Frontend Tables (haipulse_ prefix) — all removed; tables were dropped.


// config.php
define('BASE_CURRENCY', [
	'code' => 'AED',
	'symbol' => 'D',
	'precision' => 2,
	'full_name' => 'United Arab Emriates'
]);


/* 
	| ---------------------------------------------------------------------------------------------------
	| SETTINGS TBL
	| --------------------------------------------------------------------------------------------------- 
	*/

// $result_settings = $mysqli->query("SELECT * FROM `".$GLOBALS['TBL']['PREFIX']."settings` WHERE id=1");
// $row_settings		 = $result_settings->fetch_array();

// $GLOBALS['SETTINGS']['LOGO']						      			= s__($row_settings['logo']);
// $GLOBALS['SETTINGS']['LOGO_FOOTER']						     		= s__($row_settings['logo_footer']);

// $GLOBALS['SETTINGS']['SITEMAP_ROOT']								= s__($row_settings['sitemap_root']);
// $GLOBALS['SETTINGS']['SITEMAP_COMPANIES']							= s__($row_settings['sitemap_companies']);

// $GLOBALS['SETTINGS']['MINIFY_CSS']									= s__($row_settings['minify_css']);
// $GLOBALS['SETTINGS']['MINIFY_JS']									= s__($row_settings['minify_js']);
// $GLOBALS['SETTINGS']['FACEBOOK']									= s__($row_settings['facebook']);
// $GLOBALS['SETTINGS']['TWITTER']										= s__($row_settings['twitter']);
// $GLOBALS['SETTINGS']['PINTEREST']									= s__($row_settings['pinterest']);


