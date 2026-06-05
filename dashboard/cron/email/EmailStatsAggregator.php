<?php
/**
 * Email Statistics Aggregator
 * 
 * Aggregates email campaign statistics for reporting and analytics:
 * - Daily aggregation of sent/opened/clicked emails
 * - Campaign performance metrics
 * - Provider performance tracking
 * - Generates daily summary reports
 * 
 * Run via cron: 0 3 * * * /usr/bin/php /path/to/cron/email/EmailStatsAggregator.php
 */

require_once __DIR__ . '/../../../config/globals.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../CronJobBase.php';

class EmailStatsAggregator extends CronJobBase {
    
    /**
     * Date to aggregate (default: yesterday)
     */
    private $aggregateDate;
    
    /**
     * Constructor
     * 
     * @param mysqli $mysqli Database connection
     * @param string $date Optional date to aggregate (YYYY-MM-DD)
     */
    public function __construct($mysqli, $date = null) {
        parent::__construct($mysqli);
        $this->aggregateDate = $date ?: date('Y-m-d', strtotime('-1 day'));
    }
    
    /**
     * Get job name for logging
     * 
     * @return string
     */
    protected function getJobName() {
        return 'email_stats_aggregator';
    }
    
    /**
     * Execute stats aggregation
     */
    public function execute() {
        $this->log("Aggregating stats for date: {$this->aggregateDate}", 'INFO');
        
        // Aggregate campaign statistics
        $this->aggregateCampaignStats();
        
        // Aggregate provider statistics
        $this->aggregateProviderStats();
        
        // Generate daily summary
        $this->generateDailySummary();
        
        $this->log('Stats aggregation complete', 'INFO');
    }
    
    /**
     * Aggregate campaign statistics
     */
    private function aggregateCampaignStats() {
        $this->log('Aggregating campaign statistics...', 'INFO');
        
        // Get stats for campaigns that had activity on aggregate date
        $stats = $this->safeQuery(
            "SELECT 
                campaign_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'opened' THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as clicked,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced,
                SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed
            FROM `" . tbl_email_history . "`
            WHERE DATE(sent_at) = '{$this->aggregateDate}'
            AND campaign_id IS NOT NULL
            GROUP BY campaign_id"
        );
        
        if (!$stats) {
            return;
        }
        
        $updated = 0;
        while ($row = $stats->fetch_array(MYSQLI_ASSOC)) {
            $campaignId = $row['campaign_id'];
            
            // Calculate rates
            $openRate = $row['sent'] > 0 ? round(($row['opened'] / $row['sent']) * 100, 2) : 0;
            $clickRate = $row['sent'] > 0 ? round(($row['clicked'] / $row['sent']) * 100, 2) : 0;
            $bounceRate = $row['sent'] > 0 ? round(($row['bounced'] / $row['sent']) * 100, 2) : 0;
            
            // Update campaign statistics
            $updateResult = $this->safeQuery(
                "UPDATE `" . tbl_email_campaigns . "` SET
                sent_count = sent_count + " . $row['sent'] . ",
                open_count = open_count + " . $row['opened'] . ",
                click_count = click_count + " . $row['clicked'] . ",
                failed_count = failed_count + " . $row['failed'] . ",
                bounce_count = bounce_count + " . $row['bounced'] . ",
                unsubscribe_count = unsubscribe_count + " . $row['unsubscribed'] . ",
                open_rate = $openRate,
                click_rate = $clickRate,
                bounce_rate = $bounceRate,
                last_sent_at = NOW()
                WHERE id = $campaignId"
            );
            
            if ($updateResult) {
                $updated++;
                $this->incrementProcessed();
                $this->log(
                    "Campaign $campaignId: {$row['sent']} sent, {$row['opened']} opened ($openRate%), {$row['clicked']} clicked ($clickRate%)", 
                    'INFO'
                );
            }
        }
        
        $this->log("Updated stats for $updated campaign(s)", 'SUCCESS');
    }
    
    /**
     * Aggregate provider statistics
     */
    private function aggregateProviderStats() {
        $this->log('Aggregating provider statistics...', 'INFO');
        
        // Get stats for providers that sent emails on aggregate date
        $stats = $this->safeQuery(
            "SELECT 
                provider_id,
                COUNT(*) as total_sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced,
                AVG(TIMESTAMPDIFF(SECOND, created_at, sent_at)) as avg_send_time
            FROM `" . tbl_email_history . "`
            WHERE DATE(sent_at) = '{$this->aggregateDate}'
            AND provider_id IS NOT NULL
            AND status IN ('sent', 'failed', 'bounced', 'opened', 'clicked')
            GROUP BY provider_id"
        );
        
        if (!$stats) {
            return;
        }
        
        $updated = 0;
        while ($row = $stats->fetch_array(MYSQLI_ASSOC)) {
            $providerId = $row['provider_id'];
            $successRate = $row['total_sent'] > 0 
                ? round((($row['total_sent'] - $row['failed']) / $row['total_sent']) * 100, 2) 
                : 0;
            
            // Update provider stats (if table exists)
            // Store in daily stats table for historical tracking
            $this->safeQuery(
                "INSERT INTO `" . tbl_email_provider_stats . "` 
                (provider_id, date, sent_count, failed_count, bounce_count, success_rate, avg_send_time, created_at)
                VALUES (
                    $providerId,
                    '{$this->aggregateDate}',
                    {$row['total_sent']},
                    {$row['failed']},
                    {$row['bounced']},
                    $successRate,
                    " . round($row['avg_send_time'], 2) . ",
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                sent_count = {$row['total_sent']},
                failed_count = {$row['failed']},
                bounce_count = {$row['bounced']},
                success_rate = $successRate,
                avg_send_time = " . round($row['avg_send_time'], 2)
            );
            
            $updated++;
            $this->incrementProcessed();
            $this->log(
                "Provider $providerId: {$row['total_sent']} sent, success rate: $successRate%", 
                'INFO'
            );
        }
        
        $this->log("Updated stats for $updated provider(s)", 'SUCCESS');
    }
    
    /**
     * Generate daily summary report
     */
    private function generateDailySummary() {
        $this->log('Generating daily summary...', 'INFO');
        
        // Overall summary for the date
        $summary = $this->safeQuery(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'opened' THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as clicked,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced,
                SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed
            FROM `" . tbl_email_history . "`
            WHERE DATE(sent_at) = '{$this->aggregateDate}'"
        );
        
        if (!$summary) {
            return;
        }
        
        $row = $summary->fetch_array(MYSQLI_ASSOC);
        
        if ($row['total'] == 0) {
            $this->log('No email activity for this date', 'INFO');
            return;
        }
        
        // Calculate rates
        $openRate = $row['sent'] > 0 ? round(($row['opened'] / $row['sent']) * 100, 2) : 0;
        $clickRate = $row['sent'] > 0 ? round(($row['clicked'] / $row['sent']) * 100, 2) : 0;
        $bounceRate = $row['sent'] > 0 ? round(($row['bounced'] / $row['sent']) * 100, 2) : 0;
        $failureRate = $row['sent'] > 0 ? round(($row['failed'] / $row['sent']) * 100, 2) : 0;
        
        // Log summary
        $this->log('═══════════════════════════════════════════', 'INFO');
        $this->log("DAILY EMAIL SUMMARY - {$this->aggregateDate}", 'INFO');
        $this->log('═══════════════════════════════════════════', 'INFO');
        $this->log("Total Processed: {$row['total']}", 'INFO');
        $this->log("  ├─ Sent:         {$row['sent']}", 'SUCCESS');
        $this->log("  ├─ Opened:       {$row['opened']} ($openRate%)", 'INFO');
        $this->log("  ├─ Clicked:      {$row['clicked']} ($clickRate%)", 'INFO');
        $this->log("  ├─ Failed:       {$row['failed']} ($failureRate%)", 'WARNING');
        $this->log("  ├─ Bounced:      {$row['bounced']} ($bounceRate%)", 'WARNING');
        $this->log("  └─ Unsubscribed: {$row['unsubscribed']}", 'INFO');
        $this->log('═══════════════════════════════════════════', 'INFO');
        
        // Store summary in database
        $this->safeQuery(
            "INSERT INTO `" . tbl_email_daily_stats . "` 
            (date, total_sent, opened, clicked, failed, bounced, unsubscribed, 
             open_rate, click_rate, bounce_rate, failure_rate, created_at)
            VALUES (
                '{$this->aggregateDate}',
                {$row['sent']},
                {$row['opened']},
                {$row['clicked']},
                {$row['failed']},
                {$row['bounced']},
                {$row['unsubscribed']},
                $openRate,
                $clickRate,
                $bounceRate,
                $failureRate,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
            total_sent = {$row['sent']},
            opened = {$row['opened']},
            clicked = {$row['clicked']},
            failed = {$row['failed']},
            bounced = {$row['bounced']},
            unsubscribed = {$row['unsubscribed']},
            open_rate = $openRate,
            click_rate = $clickRate,
            bounce_rate = $bounceRate,
            failure_rate = $failureRate"
        );
        
        $this->log('Daily summary saved to database', 'SUCCESS');
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $mysqli = $GLOBALS['DB']['MSQLI'];
    
    // Check if custom date provided as argument
    $customDate = null;
    if ($argc > 1 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $argv[1])) {
        $customDate = $argv[1];
    }
    
    $aggregator = new EmailStatsAggregator($mysqli, $customDate);
    $aggregator->run();
} else {
    http_response_code(403);
    die('CLI only');
}
