<?php
/**
 * Cron Jobs Management
 * 
 * View and manage all scheduled cron jobs
 * - Job status and scheduling
 * - Manual job execution
 * - Job configuration
 */

$current_page = basename(__FILE__);
$module = 'cron';
$module_caption = 'Cron Jobs';

include('admin_elements/admin_header.php');

if (!has_full_access()) {
    include('admin_elements/403_forbidden.php');
    include('admin_elements/admin_footer.php');
    exit;
}

$hide_add_button = true;

/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        // Log the failed validation
        log_error('CSRF token validation failed in listing_cron_jobs.php', 'WARNING', __FILE__, __LINE__);
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid security token']);
        exit;
    }
}

// Get database connection
$mysqli = $GLOBALS['DB']['MSQLI'];

// Page title
$page_title = "Cron Jobs Management";

// Handle manual job execution
if (isset($_POST['run_job']) && isset($_POST['job_name'])) {
    $jobName = $_POST['job_name'];
    $cronDir = __DIR__ . '/cron';
    
    // Validate job name
    $allowedJobs = [
        'email:queue' => 'email/EmailQueueWorker.php',
        'email:bounce' => 'email/EmailBounceProcessor.php',
        'email:cleanup' => 'email/EmailQueueCleanup.php',
        'email:stats' => 'email/EmailStatsAggregator.php',
        'db:backup' => 'database/DatabaseBackup.php',
        'db:cleanup' => 'database/DatabaseCleanup.php',
    ];
    
    if (isset($allowedJobs[$jobName])) {
        $jobFile = $cronDir . '/' . $allowedJobs[$jobName];
        
        if (file_exists($jobFile)) {
            // Execute job via scheduler
            $schedulerPath = $cronDir . '/CronJobScheduler.php';
            $phpPath = PHP_BINARY;
            
            $command = escapeshellarg($phpPath) . ' ' . escapeshellarg($schedulerPath) . ' --job=' . escapeshellarg($jobName) . ' 2>&1';
            $output = shell_exec($command);
            
            $_SESSION['success_message'] = "Job '$jobName' executed successfully. Check logs for details.";
        } else {
            $_SESSION['error_message'] = "Job file not found: $jobFile";
        }
    } else {
        $_SESSION['error_message'] = "Invalid job name: $jobName";
    }
    
    header('Location: listing_cron_jobs.php');
    exit;
}

// Define all cron jobs
$cronJobs = [
    [
        'name' => 'email:queue',
        'title' => 'Email Queue Worker',
        'description' => 'Process email marketing queue with provider rotation and rate limiting',
        'schedule' => '*/5 * * * *',
        'schedule_text' => 'Every 5 minutes',
        'category' => 'Email Marketing',
        'file' => 'email/EmailQueueWorker.php',
        'log_name' => 'email_queue_worker',
        'priority' => 'high',
        'icon' => 'ph-envelope-open',
        'color' => 'primary'
    ],
    [
        'name' => 'email:bounce',
        'title' => 'Email Bounce Processor',
        'description' => 'Process email bounces and unsubscribes to maintain deliverability',
        'schedule' => '0 * * * *',
        'schedule_text' => 'Every hour',
        'category' => 'Email Marketing',
        'file' => 'email/EmailBounceProcessor.php',
        'log_name' => 'email_bounce_processor',
        'priority' => 'medium',
        'icon' => 'ph-warning-circle',
        'color' => 'warning'
    ],
    [
        'name' => 'email:cleanup',
        'title' => 'Email Queue Cleanup',
        'description' => 'Remove old processed queue items to prevent database bloat',
        'schedule' => '0 2 * * *',
        'schedule_text' => 'Daily at 2:00 AM',
        'category' => 'Email Marketing',
        'file' => 'email/EmailQueueCleanup.php',
        'log_name' => 'email_queue_cleanup',
        'priority' => 'low',
        'icon' => 'ph-broom',
        'color' => 'info'
    ],
    [
        'name' => 'email:stats',
        'title' => 'Email Stats Aggregator',
        'description' => 'Aggregate email campaign statistics for reporting',
        'schedule' => '0 3 * * *',
        'schedule_text' => 'Daily at 3:00 AM',
        'category' => 'Email Marketing',
        'file' => 'email/EmailStatsAggregator.php',
        'log_name' => 'email_stats_aggregator',
        'priority' => 'low',
        'icon' => 'ph-chart-bar',
        'color' => 'success'
    ],
    [
        'name' => 'db:cleanup',
        'title' => 'Database Cleanup',
        'description' => 'Clean up old logs, sessions, and temporary data',
        'schedule' => '0 4 * * 0',
        'schedule_text' => 'Weekly on Sunday at 4:00 AM',
        'category' => 'Database Maintenance',
        'file' => 'database/DatabaseCleanup.php',
        'log_name' => 'database_cleanup',
        'priority' => 'medium',
        'icon' => 'ph-trash',
        'color' => 'secondary'
    ],
];

// Get log directory
$logDir = __DIR__ . '/logs/cron';

// Check last run time from log files
foreach ($cronJobs as &$job) {
    $logBasename = isset($job['log_name']) ? (string)$job['log_name'] : str_replace(':', '_', $job['name']);
    $logPattern = $logDir . '/' . $logBasename . '_*.log';
    $logFiles = glob($logPattern);
    
    if (!empty($logFiles)) {
        // Get most recent log file
        usort($logFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $lastLogFile = $logFiles[0];
        $lastRun = filemtime($lastLogFile);
        $job['last_run'] = date('Y-m-d H:i:s', $lastRun);
        $job['last_run_ago'] = time_elapsed_string($lastRun);
        
        // Check for errors in last log
        $logContent = file_get_contents($lastLogFile);
        $errorCount = substr_count($logContent, '[ERROR]');
        $job['has_errors'] = $errorCount > 0;
        $job['error_count'] = $errorCount;
        
        // Get status from log (last END message)
        if (preg_match('/\[END\] (.+)/', $logContent, $matches)) {
            $job['status'] = 'completed';
            $job['status_message'] = $matches[1];
        } else if ($job['has_errors']) {
            $job['status'] = 'failed';
            $job['status_message'] = "Completed with $errorCount error(s)";
        } else {
            $job['status'] = 'running';
            $job['status_message'] = 'Running...';
        }
    } else {
        $job['last_run'] = null;
        $job['last_run_ago'] = 'Never';
        $job['status'] = 'pending';
        $job['status_message'] = 'Not yet executed';
        $job['has_errors'] = false;
        $job['error_count'] = 0;
    }
}
unset($job);

// Time elapsed function
function time_elapsed_string($timestamp) {
    $elapsed = time() - $timestamp;
    
    if ($elapsed < 60) {
        return $elapsed . ' second' . ($elapsed != 1 ? 's' : '') . ' ago';
    } else if ($elapsed < 3600) {
        $minutes = floor($elapsed / 60);
        return $minutes . ' minute' . ($minutes != 1 ? 's' : '') . ' ago';
    } else if ($elapsed < 86400) {
        $hours = floor($elapsed / 3600);
        return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($elapsed / 86400);
        return $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
    }
}

?>

<div class="content-wrapper">

<!-- Page header -->
<div class="page-header page-header-light shadow">
    <div class="page-header-content d-lg-flex">
        <div class="d-flex">
            <h4 class="page-title mb-0">
                <i class="ph-clock-clockwise me-2"></i>
                <span class="fw-normal">System</span> - Cron Jobs
            </h4>
            <a href="#page_header" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
            </a>
        </div>
        <div class="collapse d-lg-block my-lg-auto ms-lg-auto" id="page_header">
            <div class="hstack gap-3 mb-3 mb-lg-0">
                <a href="cron_logs.php" class="btn btn-outline-primary">
                    <i class="ph-file-text me-2"></i>
                    View Logs
                </a>
            </div>
        </div>
    </div>
</div>
<!-- /page header -->

<!-- Content area -->
<div class="content datatable-enhanced">

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <!-- Cron Jobs by Category -->
    <?php
    $categories = array_unique(array_column($cronJobs, 'category'));
    foreach ($categories as $category):
        $categoryJobs = array_filter($cronJobs, function($job) use ($category) {
            return $job['category'] === $category;
        });
    ?>
    
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0"><?php echo $category; ?></h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover datatable-professional mb-0">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Job Name</th>
                        <th>Schedule</th>
                        <th>Last Run</th>
                        <th>Status</th>
                        <th style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categoryJobs as $job): ?>
                    <tr>
                        <td class="text-center">
                            <i class="<?php echo $job['icon']; ?> text-<?php echo $job['color']; ?> ph-xl"></i>
                        </td>
                        <td>
                            <div class="fw-semibold"><?php echo $job['title']; ?></div>
                            <div class="text-muted fs-sm"><?php echo $job['description']; ?></div>
                            <div class="mt-1">
                                <span class="badge bg-<?php echo $job['priority'] === 'high' ? 'danger' : ($job['priority'] === 'medium' ? 'warning' : 'secondary'); ?> bg-opacity-10 text-<?php echo $job['priority'] === 'high' ? 'danger' : ($job['priority'] === 'medium' ? 'warning' : 'secondary'); ?>">
                                    <?php echo ucfirst($job['priority']); ?> Priority
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold"><?php echo $job['schedule_text']; ?></div>
                            <code class="fs-sm"><?php echo $job['schedule']; ?></code>
                        </td>
                        <td>
                            <?php if ($job['last_run']): ?>
                                <div><?php echo dd_($job['last_run'], 'd M Y'); ?></div>
                                <div class="text-muted fs-sm"><?php echo dd_($job['last_run'], 'g:ia'); ?> (<?php echo $job['last_run_ago']; ?>)</div>
                            <?php else: ?>
                                <span class="text-muted">Never run</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($job['status'] === 'completed'): ?>
                                <span class="badge bg-success bg-opacity-10 text-success">
                                    <i class="ph-check-circle me-1"></i>
                                    Completed
                                </span>
                                <?php if ($job['has_errors']): ?>
                                    <div class="text-danger fs-sm mt-1">
                                        <i class="ph-warning me-1"></i>
                                        <?php echo $job['error_count']; ?> error(s)
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($job['status'] === 'failed'): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger">
                                    <i class="ph-x-circle me-1"></i>
                                    Failed
                                </span>
                            <?php elseif ($job['status'] === 'running'): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary">
                                    <i class="ph-clock me-1"></i>
                                    Running
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                    <i class="ph-minus-circle me-1"></i>
                                    Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" class="d-inline confirm-form-submit" data-message="Execute this cron job now?">
                                <input type="hidden" name="job_name" value="<?php echo $job['name']; ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" name="run_job" class="btn btn-sm btn-primary" title="Run Now">
                                    <i class="ph-play"></i>
                                </button>
                            </form>
                            <a href="cron_logs.php?job=<?php echo urlencode($job['name']); ?>" class="btn btn-sm btn-outline-secondary" title="View Logs">
                                <i class="ph-file-text"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php endforeach; ?>

    <!-- Setup Instructions Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="ph-info me-2"></i>
                Cron Setup Instructions
            </h5>
        </div>
        <div class="card-body">
            <h6>Quick Start:</h6>
            <ol>
                <li>All cron jobs can be run via the centralized scheduler: <code>CronJobScheduler.php</code></li>
                <li>View complete documentation in <code>dashboard/cron/README.md</code></li>
                <li>Use the crontab template in <code>dashboard/cron/CRONTAB_TEMPLATE.txt</code></li>
            </ol>
            
            <h6 class="mt-3">Example Crontab Entry:</h6>
            <pre class="bg-light p-3 rounded"><code><?php
$phpPath = PHP_BINARY;
$cronDir = realpath(__DIR__ . '/cron');
echo "# Email queue worker (every 5 minutes)\n";
echo "*/5 * * * * cd $cronDir && $phpPath CronJobScheduler.php --job=email:queue >> /var/log/cron.log 2>&1\n\n";
echo "# Database cleanup (weekly on Sunday at 4 AM)\n";
echo "0 4 * * 0 cd $cronDir && $phpPath CronJobScheduler.php --job=db:cleanup >> /var/log/cron.log 2>&1";
            ?></code></pre>
            
            <div class="alert alert-info mb-0 mt-3">
                <i class="ph-lightbulb me-2"></i>
                <strong>Note:</strong> Cron jobs require CLI access and proper file permissions. Test jobs manually before adding to crontab.
            </div>
        </div>
    </div>

</div>
<!-- /content area -->

<?php include('admin_elements/copyright.php'); ?>

</div>

<?php include('admin_elements/admin_footer.php'); ?>



