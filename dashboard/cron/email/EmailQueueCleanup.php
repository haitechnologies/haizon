<?php
require_once dirname(dirname(__DIR__)) . '/admin_elements/error_handler_init.php';

use App\Core\DB;
/**
 * Email Queue Cleanup
 * 
 * Removes old processed queue items to prevent database bloat:
 * - Deletes sent/failed items older than retention period
 * - Optimizes table after cleanup
 * - Maintains queue performance
 * 
 * Run via cron: 0 2 * * * /usr/bin/php /path/to/cron/email/EmailQueueCleanup.php
 */

require_once __DIR__ . '/../../../config/globals.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../CronJobBase.php';

class EmailQueueCleanup extends CronJobBase {
    
    /**
     * Retention period in days
     */
    private $retentionDays = 30;
    
    /**
     * Old history retention in days (for email_history table)
     */
    private $historyRetentionDays = 90;
    
    /**
     * Get job name for logging
     * 
     * @return string
     */
    protected function getJobName() {
        return 'email_queue_cleanup';
    }
    
    /**
     * Execute queue cleanup
     */
    public function execute() {
        $this->log("Starting queue cleanup (retention: {$this->retentionDays} days)", 'INFO');
        
        // Clean email queue
        $this->cleanEmailQueue();
        
        // Clean old email history
        $this->cleanEmailHistory();
        
        // Optimize tables
        $this->optimizeTables();
        
        $this->log('Queue cleanup complete', 'INFO');
    }
    
    /**
     * Clean old sent/failed items from email queue
     */
    private function cleanEmailQueue() {
        $this->log('Cleaning email queue...', 'INFO');
        
        // Delete old sent items
        $sentResult = $this->safeQuery(
            "DELETE FROM `" . DB::EMAIL_QUEUE . "` 
            WHERE status = 'sent' 
            AND updated_at < DATE_SUB(NOW(), INTERVAL {$this->retentionDays} DAY)"
        );
        
        $sentDeleted = $sentResult ? $this->mysqli->affected_rows : 0;
        
        // Delete old failed items
        $failedResult = $this->safeQuery(
            "DELETE FROM `" . DB::EMAIL_QUEUE . "` 
            WHERE status = 'failed' 
            AND updated_at < DATE_SUB(NOW(), INTERVAL {$this->retentionDays} DAY)"
        );
        
        $failedDeleted = $failedResult ? $this->mysqli->affected_rows : 0;
        
        $totalDeleted = $sentDeleted + $failedDeleted;
        
        if ($totalDeleted > 0) {
            $this->log("Deleted $totalDeleted old queue item(s) (sent: $sentDeleted, failed: $failedDeleted)", 'SUCCESS');
            $this->incrementProcessed($totalDeleted);
        } else {
            $this->log('No old queue items to delete', 'INFO');
        }
        
        // Get current queue statistics
        $this->logQueueStats();
    }
    
    /**
     * Clean old records from email history
     */
    private function cleanEmailHistory() {
        $this->log("Cleaning email history (retention: {$this->historyRetentionDays} days)...", 'INFO');
        
        // Delete very old history records (keep opened/clicked longer for analytics)
        $result = $this->safeQuery(
            "DELETE FROM `" . DB::EMAIL_HISTORY . "` 
            WHERE sent_at < DATE_SUB(NOW(), INTERVAL {$this->historyRetentionDays} DAY)
            AND status IN ('sent', 'failed')"
        );
        
        $deleted = $result ? $this->mysqli->affected_rows : 0;
        
        if ($deleted > 0) {
            $this->log("Deleted $deleted old email history record(s)", 'SUCCESS');
            $this->incrementProcessed($deleted);
        } else {
            $this->log('No old history records to delete', 'INFO');
        }
    }
    
    /**
     * Log current queue statistics
     */
    private function logQueueStats() {
        $stats = $this->safeQuery(
            "SELECT 
                status,
                COUNT(*) as count,
                MIN(created_at) as oldest,
                MAX(created_at) as newest
            FROM `" . DB::EMAIL_QUEUE . "`
            GROUP BY status"
        );
        
        if (!$stats) {
            return;
        }
        
        $this->log('Current queue status:', 'INFO');
        while ($row = $stats->fetch_array(MYSQLI_ASSOC)) {
            $status = $row['status'];
            $count = $row['count'];
            $oldest = $row['oldest'];
            $this->log("  - $status: $count items (oldest: $oldest)", 'INFO');
        }
        
        // Get total table size
        $sizeResult = $this->safeQuery(
            "SELECT 
                table_name AS 'table',
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb'
            FROM information_schema.TABLES
            WHERE table_schema = DATABASE()
            AND table_name = '" . DB::EMAIL_QUEUE . "'"
        );
        
        if ($sizeResult && $row = $sizeResult->fetch_array(MYSQLI_ASSOC)) {
            $this->log("  Table size: {$row['size_mb']} MB", 'INFO');
        }
    }
    
    /**
     * Optimize database tables
     */
    private function optimizeTables() {
        $this->log('Optimizing tables...', 'INFO');
        
        $tables = [
            DB::EMAIL_QUEUE,
            DB::EMAIL_HISTORY
        ];
        
        foreach ($tables as $table) {
            $result = $this->safeQuery("OPTIMIZE TABLE `$table`");
            
            if ($result) {
                $this->log("Optimized table: $table", 'SUCCESS');
            } else {
                $this->log("Failed to optimize table: $table", 'WARNING');
                $this->incrementErrors();
            }
        }
    }
    
    /**
     * Get cleanup summary
     * 
     * @return array Summary statistics
     */
    private function getCleanupSummary() {
        $summary = [];
        
        // Count items by status in queue
        $queueStats = $this->safeQuery(
            "SELECT status, COUNT(*) as count 
            FROM `" . DB::EMAIL_QUEUE . "` 
            GROUP BY status"
        );
        
        if ($queueStats) {
            while ($row = $queueStats->fetch_array(MYSQLI_ASSOC)) {
                $summary['queue_' . $row['status']] = $row['count'];
            }
        }
        
        // Count history items
        $historyCount = $this->safeQuery(
            "SELECT COUNT(*) as count FROM `" . DB::EMAIL_HISTORY . "`"
        );
        
        if ($historyCount) {
            $row = $historyCount->fetch_array(MYSQLI_ASSOC);
            $summary['history_total'] = $row['count'];
        }
        
        return $summary;
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $mysqli = $GLOBALS['DB']['MSQLI'];
    $cleanup = new EmailQueueCleanup($mysqli);
    $cleanup->run();
} else {
    http_response_code(403);
    die('CLI only');
}
