<?php
/**
 * Cron Job Logs Viewer
 * 
 * View and analyze cron job execution logs
 * - Filter by job type
 * - Search log content
 * - Download log files
 */

$current_page = basename(__FILE__);
$module = 'cron';
$module_caption = 'Cron Logs';

include('admin_elements/admin_header.php');

if (!has_full_access()) {
    include('admin_elements/403_forbidden.php');
    include('admin_elements/admin_footer.php');
    exit;
}

// Page title
$page_title = "Cron Job Logs";

// Get log directory
$logDir = __DIR__ . '/logs/cron';

// Ensure log directory exists
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Get filter parameters
$selectedJob = $_GET['job'] ?? 'all';
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$searchTerm = $_GET['search'] ?? '';

// Define available jobs
$availableJobs = [
    'all' => 'All Jobs',
    'email_queue_worker' => 'Email Queue Worker',
    'email_bounce_processor' => 'Email Bounce Processor',
    'email_queue_cleanup' => 'Email Queue Cleanup',
    'email_stats_aggregator' => 'Email Stats Aggregator',
    'database_backup' => 'Database Backup',
    'database_cleanup' => 'Database Cleanup',
];

// Get list of all log files
$allLogFiles = glob($logDir . '/*.log');
usort($allLogFiles, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

// Filter log files based on selection
$logFiles = [];
foreach ($allLogFiles as $file) {
    $filename = basename($file);
    
    // Filter by job type
    if ($selectedJob !== 'all') {
        if (strpos($filename, $selectedJob) === false) {
            continue;
        }
    }
    
    // Filter by date
    if ($selectedDate && $selectedDate !== 'all') {
        if (strpos($filename, $selectedDate) === false) {
            continue;
        }
    }
    
    $logFiles[] = $file;
}

// Get unique dates from all logs
$availableDates = [];
foreach ($allLogFiles as $file) {
    if (preg_match('/_(\d{4}-\d{2}-\d{2})\.log$/', basename($file), $matches)) {
        $availableDates[$matches[1]] = true;
    }
}
$availableDates = array_keys($availableDates);
rsort($availableDates);

// Download log file if requested
if (isset($_GET['download']) && $_GET['download']) {
    $downloadFile = $logDir . '/' . basename($_GET['download']);
    
    if (file_exists($downloadFile) && strpos(realpath($downloadFile), realpath($logDir)) === 0) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . basename($downloadFile) . '"');
        header('Content-Length: ' . filesize($downloadFile));
        readfile($downloadFile);
        exit;
    }
}

?>

<div class="content-wrapper">

<!-- Page header -->
<div class="page-header page-header-light shadow">
    <div class="page-header-content d-lg-flex">
        <div class="d-flex">
            <h4 class="page-title mb-0">
                <i class="ph-file-text me-2"></i>
                <span class="fw-normal">System</span> - Cron Job Logs
            </h4>
            <a href="#page_header" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
            </a>
        </div>
        <div class="collapse d-lg-block my-lg-auto ms-lg-auto" id="page_header">
            <div class="hstack gap-3 mb-3 mb-lg-0">
                <a href="listing_cron_jobs.php" class="btn btn-outline-primary">
                    <i class="ph-clock-clockwise me-2"></i>
                    Back to Jobs
                </a>
                <a href="cron/README.md" class="btn btn-outline-secondary" target="_blank">
                    <i class="ph-book-open me-2"></i>
                    Documentation
                </a>
            </div>
        </div>
    </div>
</div>
<!-- /page header -->

<!-- Content area -->
<div class="content">

    <!-- Filters Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="ph-funnel me-2"></i>
                Log Filters
            </h5>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Cron Job</label>
                    <select name="job" class="form-select auto-submit-form">
                        <?php foreach ($availableJobs as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $selectedJob === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <select name="date" class="form-select auto-submit-form">
                        <option value="all" <?php echo $selectedDate === 'all' ? 'selected' : ''; ?>>All Dates</option>
                        <?php foreach ($availableDates as $date): ?>
                            <option value="<?php echo $date; ?>" <?php echo $selectedDate === $date ? 'selected' : ''; ?>>
                                <?php echo dd_($date, 'd M Y'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search Logs</label>
                    <input type="text" name="search" class="form-control" placeholder="Search log content..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ph-magnifying-glass me-2"></i>
                        Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Log Files -->
    <?php if (empty($logFiles)): ?>
        <div class="alert alert-info">
            <i class="ph-info me-2"></i>
            No log files found matching the selected filters.
        </div>
    <?php else: ?>
        <?php foreach ($logFiles as $logFile): 
            $filename = basename($logFile);
            $filesize = filesize($logFile);
            $modified = filemtime($logFile);
            
            // Read log content
            $content = file_get_contents($logFile);
            
            // Apply search filter
            if ($searchTerm) {
                if (stripos($content, $searchTerm) === false) {
                    continue;
                }
            }
            
            // Parse log statistics
            $lines = explode("\n", trim($content));
            $totalLines = count($lines);
            $errorCount = substr_count($content, '[ERROR]');
            $warningCount = substr_count($content, '[WARNING]');
            $successCount = substr_count($content, '[SUCCESS]');
            
            // Get job name from filename
            if (preg_match('/^(.+)_\d{4}-\d{2}-\d{2}\.log$/', $filename, $matches)) {
                $jobName = str_replace('_', ' ', ucwords($matches[1], '_'));
            } else {
                $jobName = $filename;
            }
            
            // Determine status
            if ($errorCount > 0) {
                $statusBadge = '<span class="badge bg-danger bg-opacity-10 text-danger"><i class="ph-warning me-1"></i>Errors: ' . $errorCount . '</span>';
            } else if ($warningCount > 0) {
                $statusBadge = '<span class="badge bg-warning bg-opacity-10 text-warning"><i class="ph-warning-circle me-1"></i>Warnings: ' . $warningCount . '</span>';
            } else {
                $statusBadge = '<span class="badge bg-success bg-opacity-10 text-success"><i class="ph-check-circle me-1"></i>Success</span>';
            }
        ?>
        <div class="card mb-3">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?php echo $jobName; ?></h6>
                        <div class="text-muted fs-sm">
                            <i class="ph-calendar-blank me-1"></i>
                            <?php echo date('d M Y g:ia', $modified); ?>
                            <span class="mx-2">•</span>
                            <i class="ph-file me-1"></i>
                            <?php echo number_format($filesize / 1024, 2); ?> KB
                            <span class="mx-2">•</span>
                            <?php echo $totalLines; ?> lines
                        </div>
                    </div>
                    <div class="hstack gap-2">
                        <?php echo $statusBadge; ?>
                        <a href="?download=<?php echo urlencode($filename); ?>" class="btn btn-sm btn-outline-primary">
                            <i class="ph-download"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#log_<?php echo md5($filename); ?>">
                            <i class="ph-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div id="log_<?php echo md5($filename); ?>" class="collapse">
                <div class="card-body p-0">
                    <pre class="mb-0 p-3" style="max-height: 400px; overflow-y: auto; background-color: #1e1e1e; color: #d4d4d4; font-size: 12px; line-height: 1.5;"><?php
                    // Highlight search term
                    if ($searchTerm) {
                        $content = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<mark style="background-color: yellow; color: black;">$1</mark>', htmlspecialchars($content));
                        echo $content;
                    } else {
                        // Color code log levels
                        $content = htmlspecialchars($content);
                        $content = preg_replace('/\[ERROR\]/', '<span style="color: #f44336; font-weight: bold;">[ERROR]</span>', $content);
                        $content = preg_replace('/\[WARNING\]/', '<span style="color: #ff9800; font-weight: bold;">[WARNING]</span>', $content);
                        $content = preg_replace('/\[SUCCESS\]/', '<span style="color: #4caf50; font-weight: bold;">[SUCCESS]</span>', $content);
                        $content = preg_replace('/\[INFO\]/', '<span style="color: #2196f3;">[INFO]</span>', $content);
                        $content = preg_replace('/\[START\]/', '<span style="color: #00bcd4; font-weight: bold;">[START]</span>', $content);
                        $content = preg_replace('/\[END\]/', '<span style="color: #9c27b0; font-weight: bold;">[END]</span>', $content);
                        echo $content;
                    }
                    ?></pre>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Statistics Summary -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="ph-chart-bar me-2"></i>
                Log Summary
            </h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="mb-3">
                        <h2 class="mb-0 text-primary"><?php echo count($logFiles); ?></h2>
                        <div class="text-muted">Log Files</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <h2 class="mb-0 text-info"><?php echo count($availableDates); ?></h2>
                        <div class="text-muted">Days with Logs</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <h2 class="mb-0 text-success"><?php echo number_format(array_sum(array_map('filesize', $allLogFiles)) / 1024 / 1024, 2); ?> MB</h2>
                        <div class="text-muted">Total Size</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <?php
                        $oldestLog = !empty($allLogFiles) ? min(array_map('filemtime', $allLogFiles)) : time();
                        $daysOld = floor((time() - $oldestLog) / 86400);
                        ?>
                        <h2 class="mb-0 text-secondary"><?php echo $daysOld; ?></h2>
                        <div class="text-muted">Days of History</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /content area -->

<?php include('admin_elements/copyright.php'); ?>

</div>

<?php include('admin_elements/admin_footer.php'); ?>
