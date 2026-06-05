<?php
/**
 * Email Bounce Processor
 * 
 * Processes email bounces and unsubscribes to maintain deliverability:
 * - Checks bounce webhooks and notifications from providers
 * - Marks emails as bounced in history
 * - Updates recipient status (soft/hard bounce)
 * - Automatically unsubscribes hard bounces
 * 
 * Run via cron: 0 * * * * /usr/bin/php /path/to/cron/email/EmailBounceProcessor.php
 */

require_once __DIR__ . '/../../../config/globals.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../CronJobBase.php';

class EmailBounceProcessor extends CronJobBase {
    
    /**
     * Bounce types
     */
    const SOFT_BOUNCE = 'soft';
    const HARD_BOUNCE = 'hard';
    
    /**
     * Max soft bounces before marking as hard
     */
    const MAX_SOFT_BOUNCES = 3;
    
    /**
     * Get job name for logging
     * 
     * @return string
     */
    protected function getJobName() {
        return 'email_bounce_processor';
    }
    
    /**
     * Execute bounce processing
     */
    public function execute() {
        $this->log('Processing email bounces and unsubscribes', 'INFO');
        
        // Process bounces from email history
        $this->processBounces();
        
        // Process unsubscribe requests
        $this->processUnsubscribes();
        
        // Clean up old bounce records
        $this->cleanupOldBounces();
        
        $this->log('Bounce processing complete', 'INFO');
    }
    
    /**
     * Process email bounces
     */
    private function processBounces() {
        // Check for bounced emails in history
        // NOTE: This assumes bounce status is updated via webhook or email parsing
        $bounced = $this->safeQuery(
            "SELECT eh.*, 
                COALESCE(eb.bounce_count, 0) as previous_bounces,
                eb.bounce_type as previous_bounce_type
            FROM `" . tbl_email_history . "` eh
            LEFT JOIN `" . tbl_email_bounces . "` eb ON eb.email = eh.recipient_email
            WHERE eh.status = 'bounced' 
            AND eh.processed_bounce = 0
            LIMIT 100"
        );
        
        if (!$bounced || $bounced->num_rows === 0) {
            $this->log('No new bounces to process', 'INFO');
            return;
        }
        
        $softBounces = 0;
        $hardBounces = 0;
        
        while ($row = $bounced->fetch_array(MYSQLI_ASSOC)) {
            $email = $row['recipient_email'];
            $bounceType = $this->determineBounceType($row);
            $bounceCount = $row['previous_bounces'] + 1;
            
            // Determine if this should be treated as hard bounce
            if ($bounceType === self::HARD_BOUNCE || $bounceCount >= self::MAX_SOFT_BOUNCES) {
                $bounceType = self::HARD_BOUNCE;
                $this->handleHardBounce($email, $row);
                $hardBounces++;
            } else {
                $this->handleSoftBounce($email, $row);
                $softBounces++;
            }
            
            // Update bounce record
            $this->updateBounceRecord($email, $bounceType, $bounceCount, $row['error_message']);
            
            // Mark as processed
            $this->safeQuery(
                "UPDATE `" . tbl_email_history . "` 
                SET processed_bounce = 1 
                WHERE id = " . $row['id']
            );
            
            $this->incrementProcessed();
        }
        
        $this->log("Processed $softBounces soft bounce(s), $hardBounces hard bounce(s)", 'INFO');
    }
    
    /**
     * Determine bounce type from error message
     * 
     * @param array $row Email history row
     * @return string Bounce type (soft or hard)
     */
    private function determineBounceType($row) {
        $errorMsg = strtolower($row['error_message'] ?? '');
        
        // Hard bounce indicators
        $hardBouncePatterns = [
            'user.*not.*found',
            'mailbox.*unavailable',
            'address.*rejected',
            'does.*not.*exist',
            'unknown.*user',
            'invalid.*recipient',
            'permanent.*failure',
            '550',
            '551',
            '553'
        ];
        
        foreach ($hardBouncePatterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $errorMsg)) {
                return self::HARD_BOUNCE;
            }
        }
        
        // Default to soft bounce
        return self::SOFT_BOUNCE;
    }
    
    /**
     * Handle hard bounce - unsubscribe recipient
     * 
     * @param string $email Email address
     * @param array $row Email history row
     */
    private function handleHardBounce($email, $row) {
        $this->log("Hard bounce: $email", 'WARNING');
        
        // Add to unsubscribe list if not already there
        $existing = $this->safeQuery(
            "SELECT id FROM `" . tbl_email_unsubscribes . "` 
            WHERE email = '" . $this->mysqli->real_escape_string($email) . "'"
        );
        
        if ($existing && $existing->num_rows === 0) {
            $this->safeQuery(
                "INSERT INTO `" . tbl_email_unsubscribes . "` 
                (email, reason, unsubscribed_at) 
                VALUES (
                    '" . $this->mysqli->real_escape_string($email) . "',
                    'Hard bounce - " . $this->mysqli->real_escape_string(substr($row['error_message'] ?? '', 0, 200)) . "',
                    NOW()
                )"
            );
            
            $this->log("Unsubscribed: $email (hard bounce)", 'INFO');
        }
    }
    
    /**
     * Handle soft bounce - log but don't unsubscribe
     * 
     * @param string $email Email address
     * @param array $row Email history row
     */
    private function handleSoftBounce($email, $row) {
        $this->log("Soft bounce: $email", 'INFO');
        // Soft bounces are logged but recipient remains subscribed
    }
    
    /**
     * Update bounce record for email address
     * 
     * @param string $email Email address
     * @param string $bounceType Bounce type
     * @param int $bounceCount Total bounce count
     * @param string $lastError Last error message
     */
    private function updateBounceRecord($email, $bounceType, $bounceCount, $lastError) {
        // Check if bounce record exists
        $existing = $this->safeQuery(
            "SELECT id FROM `" . tbl_email_bounces . "` 
            WHERE email = '" . $this->mysqli->real_escape_string($email) . "'"
        );
        
        if ($existing && $existing->num_rows > 0) {
            // Update existing record
            $this->safeQuery(
                "UPDATE `" . tbl_email_bounces . "` SET
                bounce_type = '$bounceType',
                bounce_count = $bounceCount,
                last_bounce_at = NOW(),
                last_error = '" . $this->mysqli->real_escape_string(substr($lastError ?? '', 0, 500)) . "'
                WHERE email = '" . $this->mysqli->real_escape_string($email) . "'"
            );
        } else {
            // Insert new record
            $this->safeQuery(
                "INSERT INTO `" . tbl_email_bounces . "` 
                (email, bounce_type, bounce_count, first_bounce_at, last_bounce_at, last_error)
                VALUES (
                    '" . $this->mysqli->real_escape_string($email) . "',
                    '$bounceType',
                    $bounceCount,
                    NOW(),
                    NOW(),
                    '" . $this->mysqli->real_escape_string(substr($lastError ?? '', 0, 500)) . "'
                )"
            );
        }
    }
    
    /**
     * Process unsubscribe requests
     */
    private function processUnsubscribes() {
        // Check for unsubscribe clicks in email tracking
        // NOTE: This assumes unsubscribe links update a tracking table
        $unsubscribes = $this->safeQuery(
            "SELECT DISTINCT recipient_email 
            FROM `" . tbl_email_history . "` 
            WHERE status = 'unsubscribed' 
            AND processed_unsubscribe = 0
            LIMIT 100"
        );
        
        if (!$unsubscribes || $unsubscribes->num_rows === 0) {
            $this->log('No new unsubscribes to process', 'INFO');
            return;
        }
        
        $count = 0;
        while ($row = $unsubscribes->fetch_array(MYSQLI_ASSOC)) {
            $email = $row['recipient_email'];
            
            // Add to unsubscribe list
            $existing = $this->safeQuery(
                "SELECT id FROM `" . tbl_email_unsubscribes . "` 
                WHERE email = '" . $this->mysqli->real_escape_string($email) . "'"
            );
            
            if ($existing && $existing->num_rows === 0) {
                $this->safeQuery(
                    "INSERT INTO `" . tbl_email_unsubscribes . "` 
                    (email, reason, unsubscribed_at) 
                    VALUES (
                        '" . $this->mysqli->real_escape_string($email) . "',
                        'User unsubscribed',
                        NOW()
                    )"
                );
            }
            
            // Mark all unsubscribe records as processed
            $this->safeQuery(
                "UPDATE `" . tbl_email_history . "` 
                SET processed_unsubscribe = 1 
                WHERE recipient_email = '" . $this->mysqli->real_escape_string($email) . "'
                AND status = 'unsubscribed'"
            );
            
            $count++;
            $this->incrementProcessed();
        }
        
        $this->log("Processed $count unsubscribe request(s)", 'INFO');
    }
    
    /**
     * Clean up old bounce records (> 180 days)
     */
    private function cleanupOldBounces() {
        $result = $this->safeQuery(
            "DELETE FROM `" . tbl_email_bounces . "` 
            WHERE bounce_type = '" . self::SOFT_BOUNCE . "' 
            AND last_bounce_at < DATE_SUB(NOW(), INTERVAL 180 DAY)"
        );
        
        if ($result) {
            $deleted = $this->mysqli->affected_rows;
            if ($deleted > 0) {
                $this->log("Cleaned up $deleted old soft bounce record(s)", 'INFO');
            }
        }
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $mysqli = $GLOBALS['DB']['MSQLI'];
    $processor = new EmailBounceProcessor($mysqli);
    $processor->run();
} else {
    http_response_code(403);
    die('CLI only');
}
