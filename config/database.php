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
$autoInitSchemaEnv = strtolower((string)$getEnv('DB_AUTO_INIT_SCHEMA', isRemote() ? 'false' : 'true'));
if ($autoInitSchemaEnv === 'true' || $autoInitSchemaEnv === '1' || $autoInitSchemaEnv === 'yes') {
	\App\Core\DatabaseSchemaInitializer::init($mysqli);
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
/*
|--------------------------------------------------------------------------
| Load the modern DB class which provides IDE autocomplete and type safety
| for all database table names without requiring database queries
*/


/*
|--------------------------------------------------------------------------
| 	LOAD ROLES CONSTANTS CLASS
|--------------------------------------------------------------------------
| Load the Roles class which provides centralized role management
| and eliminates hardcoded role IDs throughout the application
*/


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

// Note: Legacy tbl_ constants have been deprecated and completely removed.
// All code has been migrated to DB:: class constants.


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


