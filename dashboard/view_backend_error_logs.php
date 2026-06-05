<?php

/*
|==================================================================================
| ERROR LOG VIEWER - Phase 4 Implementation
|==================================================================================
| Admin-only page to view, filter, and analyze CONSOLIDATED_ERROR_LOG.txt
| Access: System and Super Admins only
| Features: Pagination, Filtering, Search, Statistics, Export
|==================================================================================
*/


// Include required files (admin_header.php loads globals.php and database.php)
include('admin_elements/admin_header.php');

// SECURITY CHECK - System and Super Admins only
if (!Roles::hasFullAccess($session_role_id)) {
    echo "<div class='alert alert-danger text-center mt-5'><h3>Access Denied</h3><p>This page is restricted to System and Super Administrators only.</p></div>";
    include('admin_elements/admin_footer.php');
    exit;
}

// Define log file paths
$log_file = __DIR__ . '/CONSOLIDATED_ERROR_LOG.txt';
$error_log_file = __DIR__ . '/error_log.txt';

function backend_log_table_exists($mysqli, $tableName) {
    $sql = "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $tableName);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

// Handle clear logs action (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_logs') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    if (backend_log_table_exists($mysqli, DB::BACKEND_ERROR_LOGS)) {
        $mysqli->query("DELETE FROM `" . DB::BACKEND_ERROR_LOGS . "`");
    }
    if (backend_log_table_exists($mysqli, DB::BACKEND_LOG_COVERAGE)) {
        $mysqli->query("UPDATE `" . DB::BACKEND_LOG_COVERAGE . "` SET last_seen_error_at = NULL");
    }
    if (file_exists($log_file)) {
        file_put_contents($log_file, '');
    }
    header('Location: view_backend_error_logs.php?cleared=1');
    exit;
}

// Handle clear PHP native error_log.txt (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_php_log') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    if (file_exists($error_log_file)) {
        file_put_contents($error_log_file, '');
        header('Location: view_backend_error_logs.php?cleared=php_log');
        exit;
    }
}

$cleared_message = '';
if (isset($_GET['cleared'])) {
    $cleared_message = '<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="ph-check-circle"></i> <strong>Success!</strong> Error logs have been cleared. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

$items_per_page = 25;

// Initialize variables
$filters = [
    'severity' => $_GET['severity'] ?? '',
    'file' => $_GET['file'] ?? '',
    'module' => $_GET['module'] ?? '',
    'source_channel' => $_GET['source_channel'] ?? '',
    'search' => $_GET['search'] ?? '',
    'error_type' => $_GET['error_type'] ?? '', // New: Filter by error type (404, 403, 500, etc.)
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'hide_info' => $_GET['hide_info'] ?? '1'
];

$page = max(1, intval($_GET['page'] ?? 1));
$export = $_GET['export'] ?? '';

// Function to parse log entries
function parse_log_entries($log_file) {
    $entries = [];
    
    if (!file_exists($log_file)) {
        return $entries;
    }
    
    $content = file_get_contents($log_file);
    $entries_raw = explode("---", $content);
    
    foreach ($entries_raw as $entry_text) {
        $entry_text = trim($entry_text);
        if (empty($entry_text)) continue;
        
        $entry = [];
        
        // Parse timestamp and severity: [2026-02-17 22:29:42] [ERROR] [file.php:71]
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \[([A-Z]+)\] \[([^:]+):(\d+)\]/', $entry_text, $matches)) {
            $entry['timestamp'] = $matches[1];
            $entry['severity'] = $matches[2];
            $entry['file'] = $matches[3];
            $entry['line'] = $matches[4];
            
            // Parse message and context
            $lines = explode("\n", $entry_text);
            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, 'Message:') === 0) {
                    $entry['message'] = substr($line, 8);
                } elseif (strpos($line, 'Context:') === 0) {
                    $entry['context'] = substr($line, 8);
                }
            }
            
            $entries[] = $entry;
        }
    }
    
    return array_reverse($entries); // Latest first
}

function normalize_backend_timestamp($rawTs) {
    $rawTs = trim((string)$rawTs);
    if ($rawTs === '') {
        return '';
    }

    $formats = [
        'Y-m-d H:i:s',
        'd-M-Y H:i:s T',
        'd-M-Y H:i:s',
        'd-M-Y H:i:s e',
    ];

    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $rawTs);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d H:i:s');
        }
    }

    $unix = strtotime($rawTs);
    if ($unix !== false) {
        return date('Y-m-d H:i:s', $unix);
    }

    return '';
}

function detect_backend_severity($message, $fallback = 'INFO') {
    $msg = strtoupper((string)$message);

    if (strpos($msg, 'CRITICAL') !== false || strpos($msg, 'FATAL') !== false) {
        return 'CRITICAL';
    }
    if (strpos($msg, 'ERROR') !== false || strpos($msg, 'EXCEPTION') !== false) {
        return 'ERROR';
    }
    if (strpos($msg, 'WARNING') !== false || strpos($msg, 'WARN') !== false) {
        return 'WARNING';
    }
    if (strpos($msg, 'NOTICE') !== false || strpos($msg, 'DEPRECATED') !== false) {
        return 'NOTICE';
    }
    if (strpos($msg, 'DEBUG') !== false) {
        return 'DEBUG';
    }

    return strtoupper((string)$fallback ?: 'INFO');
}

function parse_php_error_entries($filePath, $limit = 0) {
    $entries = [];

    if (!file_exists($filePath) || filesize($filePath) === 0) {
        return $entries;
    }

    $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        return $entries;
    }

    $count = 0;
    foreach (array_reverse($lines) as $line) {
        $line = trim((string)$line);
        if ($line === '') {
            continue;
        }

        $ts = '';
        $msg = $line;

        if (preg_match('/^\[([^\]]+)\]\s*(.+)$/', $line, $m)) {
            $ts = normalize_backend_timestamp((string)$m[1]);
            $msg = (string)$m[2];
        }

        $srcFile = 'error_log.txt';
        $srcLine = 0;
        if (preg_match('/ in ([^ ]+\.php) on line (\d+)/i', $msg, $fm)) {
            $srcFile = basename((string)$fm[1]);
            $srcLine = (int)$fm[2];
        }

        $entries[] = [
            'timestamp' => $ts !== '' ? $ts : date('Y-m-d H:i:s'),
            'severity' => detect_backend_severity($msg),
            'file' => $srcFile,
            'line' => $srcLine,
            'message' => $msg,
            'context' => 'source=php_native_error_log',
        ];

        $count++;
        if ($limit > 0 && $count >= $limit) {
            break;
        }
    }

    return $entries;
}

function parse_bracket_log_entries($filePath, $sourceLabel) {
    $entries = [];

    if (!file_exists($filePath) || filesize($filePath) === 0) {
        return $entries;
    }

    $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        return $entries;
    }

    foreach (array_reverse($lines) as $line) {
        $line = trim((string)$line);
        if ($line === '') {
            continue;
        }

        $ts = '';
        $body = $line;

        if (preg_match('/^\[([^\]]+)\]\s*(.+)$/', $line, $m)) {
            $ts = normalize_backend_timestamp((string)$m[1]);
            $body = (string)$m[2];
        }

        $sev = 'INFO';
        if (preg_match('/^\[([A-Z]+)\]\s*(.+)$/', $body, $lm)) {
            $sev = strtoupper((string)$lm[1]);
            $body = (string)$lm[2];
        } else {
            $sev = detect_backend_severity($body, 'INFO');
        }

        $entries[] = [
            'timestamp' => $ts !== '' ? $ts : date('Y-m-d H:i:s'),
            'severity' => $sev,
            'file' => basename((string)$sourceLabel),
            'line' => 0,
            'message' => $body,
            'context' => 'source=' . $sourceLabel,
        ];
    }

    return $entries;
}

function parse_backend_all_entries($sources) {
    $all = [];

    foreach ($sources as $src) {
        $type = (string)$src['type'];
        $path = (string)$src['path'];

        if ($type === 'consolidated') {
            $all = array_merge($all, parse_log_entries($path));
        } elseif ($type === 'php_native') {
            $all = array_merge($all, parse_php_error_entries($path));
        } elseif ($type === 'api' || $type === 'cron') {
            $all = array_merge($all, parse_bracket_log_entries($path, (string)$src['label']));
        }
    }

    usort($all, function($a, $b) {
        return strtotime((string)($b['timestamp'] ?? '')) <=> strtotime((string)($a['timestamp'] ?? ''));
    });

    return $all;
}

function build_backend_db_context(array $row) {
    $contextParts = [];

    if (!empty($row['module_slug'])) {
        $contextParts[] = 'module=' . $row['module_slug'];
    }
    if (!empty($row['page_name'])) {
        $contextParts[] = 'page=' . $row['page_name'];
    }
    if (!empty($row['page_path'])) {
        $contextParts[] = 'path=' . $row['page_path'];
    }
    if (!empty($row['source_channel'])) {
        $contextParts[] = 'channel=' . $row['source_channel'];
    }
    if (!empty($row['request_method']) || !empty($row['request_uri'])) {
        $contextParts[] = 'request=' . trim(($row['request_method'] ?? '') . ' ' . ($row['request_uri'] ?? ''));
    }
    if (!empty($row['request_id'])) {
        $contextParts[] = 'request_id=' . $row['request_id'];
    }
    if (!empty($row['error_code'])) {
        $contextParts[] = 'error_code=' . $row['error_code'];
    }

    $contextJson = trim((string)($row['context_json'] ?? ''));
    if ($contextJson !== '') {
        $decoded = json_decode($contextJson, true);
        if (is_array($decoded) && !empty($decoded)) {
            $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($pretty !== false) {
                $contextParts[] = "context_json=\n" . $pretty;
            }
        } else {
            $contextParts[] = 'context_json=' . $contextJson;
        }
    }

    $stackTrace = trim((string)($row['stack_trace'] ?? ''));
    if ($stackTrace !== '') {
        $contextParts[] = "stack_trace=\n" . $stackTrace;
    }

    return implode("\n", array_filter($contextParts, function($value) {
        return trim((string)$value) !== '';
    }));
}

function fetch_backend_db_entries($mysqli) {
    $entries = [];

    if (!backend_log_table_exists($mysqli, DB::BACKEND_ERROR_LOGS)) {
        return $entries;
    }

    $sql = "SELECT id, created_at, severity, message, source_file, source_line, module_slug, page_name, page_path,
                   source_channel, request_uri, request_method, request_id, error_code, context_json, stack_trace
            FROM `" . DB::BACKEND_ERROR_LOGS . "`
            ORDER BY created_at DESC, id DESC";

    $result = $mysqli->query($sql);
    if (!$result) {
        return $entries;
    }

    while ($row = $result->fetch_assoc()) {
        $sourceFile = trim((string)($row['source_file'] ?? ''));
        $pageName = trim((string)($row['page_name'] ?? ''));

        $entries[] = [
            'timestamp' => !empty($row['created_at']) ? (string)$row['created_at'] : date('Y-m-d H:i:s'),
            'severity' => strtoupper((string)($row['severity'] ?? 'ERROR')),
            'file' => $sourceFile !== '' ? basename($sourceFile) : ($pageName !== '' ? $pageName : 'unknown'),
            'line' => (int)($row['source_line'] ?? 0),
            'message' => (string)($row['message'] ?? ''),
            'context' => build_backend_db_context($row),
            'module_slug' => (string)($row['module_slug'] ?? ''),
            'page_name' => (string)($row['page_name'] ?? ''),
            'page_path' => (string)($row['page_path'] ?? ''),
            'source_channel' => (string)($row['source_channel'] ?? ''),
            'request_uri' => (string)($row['request_uri'] ?? ''),
            'source_kind' => 'database',
        ];
    }

    $result->free();
    return $entries;
}

function fetch_backend_coverage_summary($mysqli) {
    $summary = [
        'inventory_pages' => 0,
        'observed_pages' => 0,
        'unobserved_pages' => 0,
        'pages_with_errors' => 0,
    ];

    if (!backend_log_table_exists($mysqli, DB::BACKEND_LOG_COVERAGE)) {
        return $summary;
    }

    $sql = "SELECT COUNT(*) AS inventory_pages,
                   SUM(CASE WHEN seen_count > 0 THEN 1 ELSE 0 END) AS observed_pages,
                   SUM(CASE WHEN seen_count = 0 THEN 1 ELSE 0 END) AS unobserved_pages,
                   SUM(CASE WHEN last_seen_error_at IS NOT NULL THEN 1 ELSE 0 END) AS pages_with_errors
            FROM `" . DB::BACKEND_LOG_COVERAGE . "`";
    $result = $mysqli->query($sql);
    if (!$result) {
        return $summary;
    }

    $row = $result->fetch_assoc();
    $result->free();

    if (is_array($row)) {
        $summary['inventory_pages'] = (int)($row['inventory_pages'] ?? 0);
        $summary['observed_pages'] = (int)($row['observed_pages'] ?? 0);
        $summary['unobserved_pages'] = (int)($row['unobserved_pages'] ?? 0);
        $summary['pages_with_errors'] = (int)($row['pages_with_errors'] ?? 0);
    }

    return $summary;
}

function fetch_backend_unobserved_inventory($mysqli, $limit = 20) {
    $rows = [];

    if (!backend_log_table_exists($mysqli, DB::BACKEND_LOG_COVERAGE)) {
        return $rows;
    }

    $limit = max(1, (int)$limit);
    $sql = "SELECT module_slug, page_name, page_path, entrypoint_type, source_channel, seen_count, last_seen_at
            FROM `" . DB::BACKEND_LOG_COVERAGE . "`
            WHERE seen_count = 0
            ORDER BY page_path ASC
            LIMIT {$limit}";

    $result = $mysqli->query($sql);
    if (!$result) {
        return $rows;
    }

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();

    return $rows;
}

function fetch_backend_coverage_gaps($mysqli, $limit = 20) {
    $rows = [];

    if (!backend_log_table_exists($mysqli, DB::BACKEND_LOG_COVERAGE)) {
        return $rows;
    }

    $limit = max(1, (int)$limit);
            $sql = "SELECT module_slug, page_name, page_path, entrypoint_type, source_channel, bootstrap_included, seen_count, last_seen_at, last_seen_error_at
            FROM `" . DB::BACKEND_LOG_COVERAGE . "`
                WHERE module_slug = 'unknown'
               OR page_name = 'unknown'
               OR page_path = ''
               OR page_path IS NULL
            ORDER BY last_seen_at DESC
            LIMIT {$limit}";

    $result = $mysqli->query($sql);
    if (!$result) {
        return $rows;
    }

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();

    return $rows;
}

function fetch_backend_pages_error_summary($mysqli, $limit = 50) {
    $rows = [];

    if (!backend_log_table_exists($mysqli, DB::BACKEND_ERROR_LOGS)) {
        return $rows;
    }

    $limit = max(1, (int)$limit);
    $sql = "SELECT page_name,
                   module_slug,
                   COUNT(*) AS total_count,
                   SUM(CASE WHEN severity IN ('ERROR', 'CRITICAL') THEN 1 ELSE 0 END) AS error_count,
                   SUM(CASE WHEN severity = 'WARNING' THEN 1 ELSE 0 END) AS warning_count,
                   MAX(created_at) AS last_error_at
            FROM `" . DB::BACKEND_ERROR_LOGS . "`
            WHERE page_name IS NOT NULL
              AND page_name <> ''
            GROUP BY page_name, module_slug
            ORDER BY last_error_at DESC
            LIMIT {$limit}";

    $result = $mysqli->query($sql);
    if (!$result) {
        return $rows;
    }

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();

    return $rows;
}

// Function to apply filters
function apply_filters($entries, $filters) {
    $filtered = $entries;

    // Hide INFO-level entries by default to keep backend logs focused on actionable events.
    if (($filters['hide_info'] ?? '1') === '1') {
        $filtered = array_filter($filtered, function($entry) {
            $severity = strtoupper((string)($entry['severity'] ?? ''));
            return !in_array($severity, ['INFO', 'DEBUG'], true);
        });
    }
    
    // Severity filter
    if (!empty($filters['severity'])) {
        $filtered = array_filter($filtered, function($entry) use ($filters) {
            return $entry['severity'] === $filters['severity'];
        });
    }
    
    // File filter
    if (!empty($filters['file'])) {
        $filtered = array_filter($filtered, function($entry) use ($filters) {
            return stripos($entry['file'], $filters['file']) !== false;
        });
    }

    if (!empty($filters['module'])) {
        $filtered = array_filter($filtered, function($entry) use ($filters) {
            return stripos((string)($entry['module_slug'] ?? ''), $filters['module']) !== false;
        });
    }

    if (!empty($filters['source_channel'])) {
        $filtered = array_filter($filtered, function($entry) use ($filters) {
            return strcasecmp((string)($entry['source_channel'] ?? ''), (string)$filters['source_channel']) === 0;
        });
    }
    
    // Error type filter (404, 403, 500, etc.) - searches in message for HTTP error codes
    if (!empty($filters['error_type'])) {
        $filtered = array_filter($filtered, function($entry) use ($filters) {
            return stripos($entry['message'] ?? '', $filters['error_type']) !== false;
        });
    }
    
    // Search filter
    if (!empty($filters['search'])) {
        $filtered = array_filter($filtered, function($entry) use ($filters) {
            return (stripos($entry['message'] ?? '', $filters['search']) !== false ||
                    stripos($entry['context'] ?? '', $filters['search']) !== false);
        });
    }
    
    // Date range filter
    if (!empty($filters['date_from'])) {
        $date_from = strtotime($filters['date_from']);
        $filtered = array_filter($filtered, function($entry) use ($date_from) {
            return strtotime($entry['timestamp']) >= $date_from;
        });
    }
    
    if (!empty($filters['date_to'])) {
        $date_to = strtotime($filters['date_to'] . ' 23:59:59');
        $filtered = array_filter($filtered, function($entry) use ($date_to) {
            return strtotime($entry['timestamp']) <= $date_to;
        });
    }
    
    return array_values($filtered);
}

// Function to calculate statistics
function calculate_statistics($entries) {
    $stats = [
        'total' => count($entries),
        'by_severity' => [],
        'by_file' => [],
        'by_error_type' => [],
        'latest_error' => null,
        'errors_404' => 0,
        'errors_403' => 0,
        'errors_500' => 0
    ];
    
    foreach ($entries as $entry) {
        // Count by severity
        $severity = $entry['severity'];
        $stats['by_severity'][$severity] = ($stats['by_severity'][$severity] ?? 0) + 1;
        
        // Count by file
        $file = $entry['file'];
        $stats['by_file'][$file] = ($stats['by_file'][$file] ?? 0) + 1;
        
        // Count specific HTTP errors
        if (strpos($entry['message'] ?? '', '404') !== false) {
            $stats['errors_404']++;
            $stats['by_error_type']['404'] = ($stats['by_error_type']['404'] ?? 0) + 1;
        }
        if (strpos($entry['message'] ?? '', '403') !== false) {
            $stats['errors_403']++;
            $stats['by_error_type']['403'] = ($stats['by_error_type']['403'] ?? 0) + 1;
        }
        if (strpos($entry['message'] ?? '', '500') !== false) {
            $stats['errors_500']++;
            $stats['by_error_type']['500'] = ($stats['by_error_type']['500'] ?? 0) + 1;
        }
        
        // Get latest error
        if ($severity === 'ERROR' && $stats['latest_error'] === null) {
            $stats['latest_error'] = $entry;
        }
    }
    
    arsort($stats['by_file']);
    arsort($stats['by_severity']);
    arsort($stats['by_error_type']);
    
    return $stats;
}

// Load and process logs from all backend sources
$api_requests_log_file = dirname(__DIR__) . '/api-requests.log';
$cron_log_files = glob(__DIR__ . '/logs/cron/*.log') ?: [];

$backend_sources = [
    ['type' => 'consolidated', 'path' => $log_file, 'label' => 'CONSOLIDATED_ERROR_LOG.txt'],
    ['type' => 'php_native', 'path' => $error_log_file, 'label' => 'error_log.txt'],
];

if (file_exists($api_requests_log_file)) {
    $backend_sources[] = ['type' => 'api', 'path' => $api_requests_log_file, 'label' => 'api-requests.log'];
}

foreach ($cron_log_files as $cronFile) {
    $backend_sources[] = ['type' => 'cron', 'path' => $cronFile, 'label' => 'cron/' . basename($cronFile)];
}

$db_entries = fetch_backend_db_entries($mysqli);
$coverage_summary = fetch_backend_coverage_summary($mysqli);
$unobserved_inventory = fetch_backend_unobserved_inventory($mysqli, 20);
$coverage_gaps = fetch_backend_coverage_gaps($mysqli, 15);
$pages_error_summary = fetch_backend_pages_error_summary($mysqli, 50);

$unobserved_by_module = [];
$unobserved_by_source = [];
foreach ($unobserved_inventory as $invRow) {
    $moduleKey = trim((string)($invRow['module_slug'] ?? 'unknown'));
    if ($moduleKey === '') {
        $moduleKey = 'unknown';
    }
    $sourceKey = trim((string)($invRow['source_channel'] ?? 'unknown'));
    if ($sourceKey === '') {
        $sourceKey = 'unknown';
    }

    $unobserved_by_module[$moduleKey] = (int)($unobserved_by_module[$moduleKey] ?? 0) + 1;
    $unobserved_by_source[$sourceKey] = (int)($unobserved_by_source[$sourceKey] ?? 0) + 1;
}
arsort($unobserved_by_module);
arsort($unobserved_by_source);

$unobserved_top_module = !empty($unobserved_by_module) ? array_key_first($unobserved_by_module) : '';
$unobserved_top_module_count = ($unobserved_top_module !== '') ? (int)$unobserved_by_module[$unobserved_top_module] : 0;
$unobserved_top_source = !empty($unobserved_by_source) ? array_key_first($unobserved_by_source) : '';
$unobserved_top_source_count = ($unobserved_top_source !== '') ? (int)$unobserved_by_source[$unobserved_top_source] : 0;

$all_entries = !empty($db_entries) ? $db_entries : parse_backend_all_entries($backend_sources);
$viewer_source_mode = !empty($db_entries) ? 'database' : 'files';
$filtered_entries = apply_filters($all_entries, $filters);

if ($export === 'json') {
    if (ob_get_length() !== false) {
        ob_clean();
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="backend_error_logs_' . date('Y-m-d_H-i-s') . '.json"');

    echo json_encode([
        'exported_at' => date('c'),
        'source_mode' => $viewer_source_mode,
        'filters' => $filters,
        'total_entries' => count($filtered_entries),
        'entries' => array_values($filtered_entries),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$statistics = calculate_statistics($all_entries);

// Update last read timestamp and unified entry count (guarded)
$total_error_count = count($all_entries);
if (backend_log_table_exists($mysqli, DB::ERROR_LOG_STATUS)) {
    $stmt_status = $mysqli->prepare("UPDATE `" . DB::ERROR_LOG_STATUS . "` SET last_read_timestamp = NOW(), last_error_count = ? WHERE id = 1");
    if ($stmt_status) {
        $stmt_status->bind_param('i', $total_error_count);
        $stmt_status->execute();
        $stmt_status->close();
    }
}



// Calculate pagination
$total_filtered = count($filtered_entries);
$total_pages = ceil($total_filtered / $items_per_page);
$page = min($page, max(1, $total_pages));
$offset = ($page - 1) * $items_per_page;
$paginated_entries = array_slice($filtered_entries, $offset, $items_per_page);

// Get unique files from all entries
$unique_files = array_unique(array_map(function($entry) { return $entry['file']; }, $all_entries));
sort($unique_files);

$unique_modules = array_values(array_unique(array_filter(array_map(function($entry) {
    return trim((string)($entry['module_slug'] ?? ''));
}, $all_entries))));
sort($unique_modules);

$unique_channels = array_values(array_unique(array_filter(array_map(function($entry) {
    return trim((string)($entry['source_channel'] ?? ''));
}, $all_entries))));
sort($unique_channels);

function build_query_params($filters, $overrides = []) {
    $params = [];

    foreach ($filters as $key => $value) {
        if ($value !== '' && $value !== null) {
            $params[$key] = $value;
        }
    }

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }

    return http_build_query($params);
}

$totalBytes = 0;
foreach ($backend_sources as $src) {
    if (file_exists($src['path'])) {
        $totalBytes += (int)filesize($src['path']);
    }
}
$log_file_size = $totalBytes > 0 ? round($totalBytes / 1024, 2) . ' KB' : 'No log file';
$latest_ts = !empty($all_entries) ? ($all_entries[0]['timestamp'] ?? '-') : '-';

// Parse PHP native error_log.txt section (dashboard/error_log.txt)
$php_error_lines = parse_php_error_entries($error_log_file, 200);
$php_error_log_size = file_exists($error_log_file) ? round(filesize($error_log_file) / 1024, 2) . ' KB' : 'No file';
?>

<style>
    .log-shell {
        display: grid;
        gap: 10px;
    }

    .compact-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .stat-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 8px;
    }

    .stat-chip {
        border: 1px solid #dfe6ef;
        border-radius: 8px;
        background: #fff;
        padding: 8px 10px;
        line-height: 1.2;
    }

    .stat-chip strong {
        display: block;
        font-size: 1rem;
    }

    .filters-sticky {
        position: sticky;
        top: 8px;
        z-index: 12;
        border: 1px solid #dbe3ee;
        border-radius: 8px;
        background: #f7fbff;
        padding: 10px;
    }

    .filters-sticky .form-label {
        margin-bottom: 4px;
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #5d6b7b;
        letter-spacing: 0.04em;
    }

    .severity-badge {
        font-weight: 600;
        padding: 0.2rem 0.45rem;
        border-radius: 0.25rem;
        font-size: 0.7rem;
        min-width: 70px;
        display: inline-block;
        text-align: center;
    }

    .severity-critical { background-color: #721c24; color: #fff; }
    .severity-error { background-color: #dc3545; color: #fff; }
    .severity-warning { background-color: #ffc107; color: #333; }
    .severity-notice { background-color: #17a2b8; color: #fff; }
    .severity-info { background-color: #0c5460; color: #fff; }
    .severity-debug { background-color: #6c757d; color: #fff; }

    .log-table thead th {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #5d6b7b;
        white-space: nowrap;
    }

    .log-scroll-area {
        max-height: calc(100vh - 320px);
        overflow: auto;
    }

    .log-scroll-area .log-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #fff;
    }

    .log-table tbody td {
        vertical-align: top;
        font-size: 0.83rem;
        padding-top: 0.4rem;
        padding-bottom: 0.4rem;
    }

    .log-msg {
        max-width: 680px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .log-source code {
        font-size: 0.74rem;
    }

    .log-details summary {
        cursor: pointer;
        color: #3b4f68;
        font-size: 0.75rem;
    }

    .log-details pre {
        margin-top: 6px;
        max-height: 160px;
        overflow: auto;
        background: #f6f8fb;
        border: 1px solid #e5ebf2;
        border-radius: 6px;
        padding: 8px;
        font-size: 0.74rem;
    }

    .empty-state {
        text-align: center;
        padding: 2rem;
        background: #f8f9fa;
        border-radius: 0.5rem;
    }

    @media (max-width: 991.98px) {
        .log-scroll-area {
            max-height: none;
        }
    }
</style>

<!-- Main content -->
<div class="content-wrapper">

    <!-- Page header -->
    <!-- /page header -->

    <!-- Inner content -->
    <div class="content-inner">

        <!-- Content area -->
        <div class="content">
            <div class="log-shell">

                <div class="compact-header">
                    <div>
                        <h3 class="mb-0"><i class="ph-warning-circle text-danger"></i> Backend Error Logs</h3>
                        <small class="text-muted">Dense view for fast triage with reduced scrolling.</small>
                    </div>
                        <div class="d-flex gap-2 align-items-center">
                            <a href="?<?php echo build_query_params($filters, ['export' => 'json']); ?>" class="btn btn-sm btn-success"><i class="ph-download"></i> JSON</a>
                            <form method="post" action="view_backend_error_logs.php" class="d-inline" onsubmit="return confirm('Clear all error logs? This cannot be undone.');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="clear_logs">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="ph-trash"></i> Clear</button>
                            </form>
                        </div>
                </div>

                <?php echo $cleared_message; ?>

                <div class="stat-strip">
                    <div class="stat-chip"><small class="text-muted">Total</small><strong><?php echo number_format($statistics['total']); ?></strong></div>
                    <a href="?severity=ERROR" class="stat-chip text-decoration-none">
                        <small class="text-muted">Errors</small><strong class="text-danger"><?php echo number_format($statistics['by_severity']['ERROR'] ?? 0); ?></strong>
                    </a>
                    <a href="?severity=WARNING" class="stat-chip text-decoration-none">
                        <small class="text-muted">Warnings</small><strong class="text-warning"><?php echo number_format($statistics['by_severity']['WARNING'] ?? 0); ?></strong>
                    </a>
                    <a href="?error_type=404" class="stat-chip text-decoration-none">
                        <small class="text-muted">404</small><strong class="text-warning"><?php echo number_format($statistics['errors_404']); ?></strong>
                    </a>
                    <a href="?error_type=500" class="stat-chip text-decoration-none">
                        <small class="text-muted">500</small><strong class="text-danger"><?php echo number_format($statistics['errors_500']); ?></strong>
                    </a>
                    <a href="?error_type=403" class="stat-chip text-decoration-none">
                        <small class="text-muted">403</small><strong class="text-secondary"><?php echo number_format($statistics['errors_403']); ?></strong>
                    </a>
                    <div class="stat-chip"><small class="text-muted">Inventory</small><strong><?php echo number_format($coverage_summary['inventory_pages'] ?? 0); ?></strong></div>
                    <div class="stat-chip"><small class="text-muted">Observed Pages</small><strong><?php echo number_format($coverage_summary['observed_pages'] ?? 0); ?></strong></div>
                    <div class="stat-chip"><small class="text-muted">Unobserved</small><strong><?php echo number_format($coverage_summary['unobserved_pages'] ?? 0); ?></strong></div>
                    <div class="stat-chip"><small class="text-muted">Pages With Errors</small><strong><?php echo number_format($coverage_summary['pages_with_errors'] ?? 0); ?></strong></div>
                    <div class="stat-chip"><small class="text-muted">Coverage Gaps</small><strong><?php echo number_format(count($coverage_gaps)); ?></strong></div>
                    <div class="stat-chip"><small class="text-muted">Files</small><strong><?php echo number_format(count($statistics['by_file'])); ?></strong></div>
                    <div class="stat-chip"><small class="text-muted">Log Size</small><strong><?php echo htmlspecialchars($log_file_size); ?></strong></div>
                </div>

                <div class="alert alert-secondary py-2 mb-0">
                    <small>
                        <i class="ph-database"></i>
                        Primary source: <strong><?php echo $viewer_source_mode === 'database' ? 'erp_backend_error_logs' : 'text log fallback'; ?></strong>
                        <?php if ($viewer_source_mode === 'files'): ?>
                            <span class="text-muted">DB log table is empty or unavailable, so the viewer is reading compatibility files.</span>
                        <?php endif; ?>
                    </small>
                </div>

                <?php if ($viewer_source_mode === 'database' && !empty($pages_error_summary)): ?>
                    <div class="card border-info-subtle">
                        <div class="card-header bg-info-subtle py-2">
                            <strong>Pages With Errors</strong>
                            <small class="text-muted">Grouped summary of backend pages with logged issues.</small>
                        </div>
                        <div class="table-responsive log-scroll-area" style="max-height: 360px;">
                            <table class="table table-sm mb-0 log-table">
                                <thead>
                                    <tr>
                                        <th>Page</th>
                                        <th>Module</th>
                                        <th>Errors</th>
                                        <th>Warnings</th>
                                        <th>Total</th>
                                        <th>Last Error</th>
                                        <th>Open</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pages_error_summary as $pageSummary): ?>
                                        <?php
                                            $pageName = trim((string)($pageSummary['page_name'] ?? ''));
                                            $moduleSlug = trim((string)($pageSummary['module_slug'] ?? ''));
                                            $fileFilter = build_query_params($filters, ['file' => $pageName, 'page' => 1]);
                                        ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($pageName !== '' ? $pageName : 'unknown'); ?></code></td>
                                            <td><?php echo htmlspecialchars($moduleSlug !== '' ? $moduleSlug : 'unknown'); ?></td>
                                            <td><span class="text-danger fw-semibold"><?php echo (int)($pageSummary['error_count'] ?? 0); ?></span></td>
                                            <td><span class="text-warning fw-semibold"><?php echo (int)($pageSummary['warning_count'] ?? 0); ?></span></td>
                                            <td><?php echo (int)($pageSummary['total_count'] ?? 0); ?></td>
                                            <td><?php echo htmlspecialchars((string)($pageSummary['last_error_at'] ?? '-')); ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary" href="view_backend_error_logs.php?<?php echo htmlspecialchars($fileFilter); ?>">
                                                    Filter
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="get" class="filters-sticky">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label" for="severity">Severity</label>
                            <select name="severity" id="severity" class="form-select form-select-sm">
                                <option value="">All</option>
                                <?php foreach (['CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'] as $severityOption): ?>
                                    <option value="<?php echo htmlspecialchars($severityOption); ?>" <?php echo $filters['severity'] === $severityOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($severityOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="module">Module</label>
                            <select name="module" id="module" class="form-select form-select-sm">
                                <option value="">All</option>
                                <?php foreach ($unique_modules as $moduleOption): ?>
                                    <option value="<?php echo htmlspecialchars($moduleOption); ?>" <?php echo $filters['module'] === $moduleOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($moduleOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="source_channel">Source</label>
                            <select name="source_channel" id="source_channel" class="form-select form-select-sm">
                                <option value="">All</option>
                                <?php foreach ($unique_channels as $channelOption): ?>
                                    <option value="<?php echo htmlspecialchars($channelOption); ?>" <?php echo $filters['source_channel'] === $channelOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($channelOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="file">File</label>
                            <input type="text" name="file" id="file" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filters['file']); ?>" placeholder="file.php">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="error_type">HTTP/Error Type</label>
                            <input type="text" name="error_type" id="error_type" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filters['error_type']); ?>" placeholder="404, 500, timeout">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="search">Search</label>
                            <input type="text" name="search" id="search" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="message or context">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="date_from">Date From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="date_to">Date To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="hide_info">Info Rows</label>
                            <select name="hide_info" id="hide_info" class="form-select form-select-sm">
                                <option value="1" <?php echo ($filters['hide_info'] ?? '1') === '1' ? 'selected' : ''; ?>>Hide INFO/DEBUG</option>
                                <option value="0" <?php echo ($filters['hide_info'] ?? '1') === '0' ? 'selected' : ''; ?>>Show INFO/DEBUG</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="ph-funnel"></i> Apply</button>
                            <a href="view_backend_error_logs.php" class="btn btn-sm btn-light"><i class="ph-x"></i> Reset</a>
                            <a href="view_backend_error_logs.php?<?php echo htmlspecialchars(build_query_params($filters, ['export' => 'json', 'page' => null])); ?>" class="btn btn-sm btn-outline-dark"><i class="ph-download-simple"></i> Export JSON</a>
                            <?php if (!empty($unobserved_inventory)): ?>
                                <a href="run_coverage_sweep.php?source=dashboard_runtime" class="btn btn-sm btn-success"><i class="ph-rocket-launch"></i> Run Coverage Sweep</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <?php if (!empty($unobserved_inventory)): ?>
                    <div class="card border-secondary-subtle">
                        <div class="card-header bg-light py-2">
                            <strong>Unobserved Inventory</strong>
                            <small class="text-muted">Entrypoints discovered by the scanner but not yet seen in runtime logging.</small>
                            <div class="small mt-2">
                                <strong>Details to proceed:</strong>
                                Visit each listed backend page at least once while authenticated as admin so runtime logging can register coverage.
                                <?php if ($unobserved_top_module !== ''): ?>
                                    Prioritize module <code><?php echo htmlspecialchars($unobserved_top_module); ?></code>
                                    (<?php echo (int)$unobserved_top_module_count; ?> pending).
                                <?php endif; ?>
                                <?php if ($unobserved_top_source !== ''): ?>
                                    Most pending source: <code><?php echo htmlspecialchars($unobserved_top_source); ?></code>
                                    (<?php echo (int)$unobserved_top_source_count; ?>).
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="table-responsive log-scroll-area">
                            <table class="table table-sm mb-0 log-table">
                                <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th>Page</th>
                                        <th>Entrypoint</th>
                                        <th>Source</th>
                                        <th>Seen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unobserved_inventory as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars((string)($row['module_slug'] ?? 'unknown')); ?></td>
                                            <td><code><?php echo htmlspecialchars((string)($row['page_path'] ?? ($row['page_name'] ?? 'unknown'))); ?></code></td>
                                            <td><?php echo htmlspecialchars((string)($row['entrypoint_type'] ?? 'unknown')); ?></td>
                                            <td><?php echo htmlspecialchars((string)($row['source_channel'] ?? 'unknown')); ?></td>
                                            <td><?php echo (int)($row['seen_count'] ?? 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($coverage_gaps)): ?>
                    <div class="card border-warning-subtle">
                        <div class="card-header bg-warning-subtle py-2">
                            <strong>Coverage Gaps</strong>
                            <small class="text-muted">Observed backend pages with unresolved categorization metadata.</small>
                        </div>
                        <div class="table-responsive log-scroll-area">
                            <table class="table table-sm mb-0 log-table">
                                <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th>Page</th>
                                        <th>Entrypoint</th>
                                        <th>Source</th>
                                        <th>Seen</th>
                                        <th>Last Seen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($coverage_gaps as $gap): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars((string)($gap['module_slug'] ?? 'unknown')); ?></td>
                                            <td><code><?php echo htmlspecialchars((string)($gap['page_path'] ?? ($gap['page_name'] ?? 'unknown'))); ?></code></td>
                                            <td><?php echo htmlspecialchars((string)($gap['entrypoint_type'] ?? 'unknown')); ?></td>
                                            <td><?php echo htmlspecialchars((string)($gap['source_channel'] ?? 'unknown')); ?></td>
                                            <td><?php echo (int)($gap['seen_count'] ?? 0); ?></td>
                                            <td><?php echo htmlspecialchars((string)($gap['last_seen_at'] ?? '')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center small text-muted">
                    <div>
                        Showing <strong><?php echo number_format($total_filtered); ?></strong>
                        <?php if ($total_filtered != $statistics['total']): ?>
                            of <strong><?php echo number_format($statistics['total']); ?></strong>
                        <?php endif; ?>
                    </div>
                    <div>Page <?php echo $page; ?> / <?php echo max(1, $total_pages); ?></div>
                </div>

                <?php if (empty($paginated_entries)): ?>
                    <div class="empty-state">
                        <i class="ph-file-search" style="font-size: 3rem; color: #ccc;"></i>
                        <h5 class="mt-2">No log entries found</h5>
                        <p class="text-muted mb-0">
                            <?php if (empty($all_entries)): ?>
                                The log file is empty or missing.
                            <?php else: ?>
                                Adjust filters to widen results.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="table-responsive log-scroll-area">
                            <table class="table table-sm table-hover mb-0 log-table">
                                <thead>
                                    <tr>
                                        <th style="width: 92px;">Level</th>
                                        <th style="width: 185px; white-space:nowrap;">Time</th>
                                        <th style="width: 320px;">Source</th>
                                        <th style="width: 150px;">Module</th>
                                        <th colspan="2">Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paginated_entries as $entry): ?>
                                        <tr class="log-row">
                                            <td>
                                                <span class="severity-badge severity-<?php echo strtolower($entry['severity']); ?>">
                                                    <?php echo htmlspecialchars($entry['severity']); ?>
                                                </span>
                                            </td>
                                            <td style="white-space:nowrap;"><small>
                                                <?php
                                                    $ts = $entry['timestamp'] ?? '-';
                                                    if ($ts && $ts !== '-' && strtotime($ts) !== false) {
                                                        echo dd_($ts, 'd M Y g:ia');
                                                    } else {
                                                        echo htmlspecialchars((string)$ts);
                                                    }
                                                ?>
                                            </small></td>
                                            <td class="log-source"><code><?php echo htmlspecialchars($entry['file']); ?>:<?php echo (int)$entry['line']; ?></code></td>
                                            <td><small><?php echo htmlspecialchars($entry['module_slug'] ?? '-'); ?></small></td>
                                            <td class="log-msg-cell" style="cursor:pointer;" colspan="2">
                                                <div class="log-msg d-flex align-items-center" title="<?php echo htmlspecialchars($entry['message'] ?? 'No message'); ?>">
                                                    <span class="arrow-icon" style="display:inline-block;transition:transform 0.2s; margin-right:6px;">
                                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.5 6L8 9.5L11.5 6" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </span>
                                                    <span><?php echo htmlspecialchars($entry['message'] ?? 'No message'); ?></span>
                                                </div>
                                                <div class="log-details-content" style="display:none;">
                                                    <pre style="margin:8px 0 0 22px; white-space:pre-wrap; overflow-x:hidden;">
                                                        <?php echo ($entry['context'] !== '' ? htmlspecialchars($entry['context']) : 'No additional details'); ?>
                                                    </pre>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Expand/collapse details on message cell click
                    document.querySelectorAll('td.log-msg-cell').forEach(function(cell) {
                        cell.addEventListener('click', function(e) {
                            // Only toggle if not clicking a link or input
                            if (e.target.closest('a, input, button, textarea, select')) return;
                            const details = cell.querySelector('.log-details-content');
                            const arrow = cell.querySelector('.arrow-icon');
                            if (details) {
                                const isOpen = details.style.display === 'block';
                                details.style.display = isOpen ? 'none' : 'block';
                                if (arrow) arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(90deg)';
                            }
                        });
                    });
                });
                </script>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <!-- Previous Page -->
                        <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                            <a class="page-link" href="?<?php echo build_query_params($filters, ['page' => max(1, $page - 1)]); ?>">Previous</a>
                        </li>
                        
                        <!-- First Page -->
                        <?php if ($page > 3): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo build_query_params($filters, ['page' => 1]); ?>">1</a>
                            </li>
                            <?php if ($page > 4): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?<?php echo build_query_params($filters, ['page' => $i]); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Last Page -->
                        <?php if ($page < $total_pages - 2): ?>
                            <?php if ($page < $total_pages - 3): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo build_query_params($filters, ['page' => $total_pages]); ?>"><?php echo $total_pages; ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Next Page -->
                        <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                            <a class="page-link" href="?<?php echo build_query_params($filters, ['page' => min($total_pages, $page + 1)]); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            
            <!-- Footer Info -->
            <div class="alert alert-info mt-3 py-2">
                <small>
                    <i class="ph-info"></i> 
                    Source mode: <code><?php echo htmlspecialchars($viewer_source_mode); ?></code> |
                    Latest entry: <code><?php echo htmlspecialchars((string)$latest_ts); ?></code> |
                    Last updated: <code><?php echo date('Y-m-d H:i:s'); ?></code> | 
                    File size: <code><?php echo htmlspecialchars($log_file_size); ?></code>
                </small>
            </div>

            <!-- PHP Native Error Log (dashboard/error_log.txt) -->
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h5 class="mb-0"><i class="ph-file-text text-secondary"></i> PHP Error Log <small class="text-muted" style="font-size:0.85rem;">(dashboard/error_log.txt)</small></h5>
                        <small class="text-muted">Native PHP errors &mdash; showing last <?php echo count($php_error_lines); ?> entries &mdash; <?php echo htmlspecialchars($php_error_log_size); ?></small>
                    </div>
                    <form method="post" action="view_backend_error_logs.php" class="d-inline" onsubmit="return confirm('Clear dashboard/error_log.txt? This cannot be undone.');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="clear_php_log">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ph-trash"></i> Clear</button>
                    </form>
                </div>
                <?php if (empty($php_error_lines)): ?>
                    <div class="empty-state">
                        <i class="ph-file-search" style="font-size: 2rem; color:#ccc;"></i>
                        <p class="text-muted mb-0 mt-1">error_log.txt is empty or not found.</p>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="table-responsive log-scroll-area">
                            <table class="table table-sm table-hover mb-0 log-table">
                                <thead>
                                    <tr>
                                        <th style="width: 92px;">Level</th>
                                        <th style="width: 220px; white-space:nowrap;">Time</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($php_error_lines as $php_entry): ?>
                                        <tr>
                                            <td><span class="severity-badge severity-<?php echo strtolower($php_entry['severity']); ?>"><?php echo htmlspecialchars($php_entry['severity']); ?></span></td>
                                            <td style="white-space:nowrap;"><small><?php echo htmlspecialchars($php_entry['timestamp']); ?></small></td>
                                            <td style="font-size:0.83rem; word-break:break-word;"><?php echo htmlspecialchars($php_entry['message']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        <!-- /content area -->

        <?php include('admin_elements/copyright.php'); ?>

    </div>
    <!-- /inner content -->

</div>
<!-- /main content -->

<?php include('admin_elements/admin_footer.php'); ?>
