<?php
/**
 * Email Marketing Queue Worker
 * 
 * Processes email sending queue with:
 * - Provider rotation and rate limiting
 * - Automatic retry with exponential backoff
 * - Error handling and logging
 * 
 * Run via cron: every 5 minutes
 */

require_once __DIR__ . '/../../../config/globals.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../classes/EmailProviderManager.php';
require_once __DIR__ . '/../../../classes/SMTPMailer.php';
require_once __DIR__ . '/../CronJobBase.php';

class EmailQueueWorker extends CronJobBase {
    private $batchSize = 50;
    private $maxRetries = 3;

    /**
     * Get job name for logging
     * 
     * @return string
     */
    protected function getJobName() {
        return 'email_queue_worker';
    }

    /**
     * Execute the email queue processing
     */
    public function execute() {
        // Get active providers with rate limits
        $providers = $this->getActiveProviders();
        if (empty($providers)) {
            $this->log('No active email providers configured', 'ERROR');
            return;
        }

        $this->log('Found ' . count($providers) . ' active provider(s)', 'INFO');

        // Process queue in batches
        $queued = $this->safeQuery(
            "SELECT * FROM `" . tbl_email_queue . "` 
            WHERE status IN ('pending', 'queued')
               OR (status = 'retry' AND (next_retry_at IS NULL OR next_retry_at <= NOW()))
            ORDER BY priority ASC, created_at ASC 
            LIMIT " . $this->batchSize
        );

        if (!$queued) {
            return;
        }

        $processed = 0;
        $failed = 0;
        $retried = 0;

        while ($queueItem = $queued->fetch_array(MYSQLI_ASSOC)) {
            $queueId = $queueItem['id'];
            $campaignId = $queueItem['campaign_id'];
            $recipientEmail = $queueItem['recipient_email'] ?? $queueItem['recipient'] ?? '';
            $providerId = $queueItem['provider_id'];
            $attempts = (int)($queueItem['attempts'] ?? $queueItem['retries'] ?? 0);
            $maxRetriesForItem = (int)($queueItem['max_retries'] ?? $this->maxRetries);
            if ($maxRetriesForItem <= 0) {
                $maxRetriesForItem = $this->maxRetries;
            }

            $payload = [];
            if (!empty($queueItem['payload_json'])) {
                $payload = json_decode($queueItem['payload_json'], true) ?? [];
            }

            $headers = [];
            if (!empty($queueItem['headers'])) {
                $headers = json_decode($queueItem['headers'], true) ?? [];
            }

            if (empty($providerId) && !empty($headers['provider_id'])) {
                $providerId = (int)$headers['provider_id'];
            }

            if (empty($payload['subject']) && !empty($queueItem['subject'])) {
                $payload['subject'] = $queueItem['subject'];
            }
            if (empty($payload['html_body']) && !empty($queueItem['body'])) {
                $payload['html_body'] = $queueItem['body'];
            }
            if (empty($payload['text_body']) && !empty($payload['html_body'])) {
                $payload['text_body'] = trim(strip_tags($payload['html_body']));
            }
            if (empty($payload['from_name']) && !empty($headers['from_name'])) {
                $payload['from_name'] = $headers['from_name'];
            }
            if (empty($payload['from_email']) && !empty($headers['from'])) {
                $payload['from_email'] = $headers['from'];
            }
            if (empty($payload['tracking_id']) && !empty($queueItem['tracking_id'])) {
                $payload['tracking_id'] = $queueItem['tracking_id'];
            }

            try {
                // Select provider (rotate if none specified)
                if (empty($providerId) || !isset($providers[$providerId])) {
                    $providerId = $this->selectProvider($providers);
                }

                $provider = $providers[$providerId] ?? null;
                if (!$provider) {
                    throw new Exception('No suitable provider available');
                }

                // Send email
                $result = $this->sendEmail($provider, $recipientEmail, $payload);

                if ($result['success']) {
                    // Update queue item as sent
                    $this->safeQuery(
                        "UPDATE `" . tbl_email_queue . "` SET 
                        status = 'sent',
                        provider_id = " . (int)$providerId . ",
                        attempts = " . (int)$attempts . ",
                        retries = " . (int)$attempts . ",
                        failed_reason = NULL,
                        sent_at = NOW(),
                        updated_at = NOW()
                        WHERE id = " . (int)$queueId
                    );

                    // Update history
                    $this->safeQuery(
                        "INSERT INTO `" . tbl_email_history . "` 
                        (campaign_id, recipient_email, company_id, provider_id, status, sent_at, message_id, tracking_id, subject, from_name, from_email)
                        VALUES (
                            " . ($campaignId ? $campaignId : 'NULL') . ",
                            '" . $this->mysqli->real_escape_string($recipientEmail) . "',
                            NULL,
                            $providerId,
                            'sent',
                            NOW(),
                            '" . ($result['messageId'] ?? '') . "',
                            '" . ($payload['tracking_id'] ?? '') . "',
                            '" . $this->mysqli->real_escape_string($payload['subject'] ?? '') . "',
                            '" . $this->mysqli->real_escape_string($payload['from_name'] ?? '') . "',
                            '" . $this->mysqli->real_escape_string($payload['from_email'] ?? '') . "'
                        )"
                    );

                    // Update campaign stats
                    if (!empty($campaignId)) {
                        $this->safeQuery(
                            "UPDATE `" . tbl_email_campaigns . "` SET 
                            sent_count = sent_count + 1
                            WHERE id = " . (int)$campaignId
                        );
                    }

                    $this->log("Sent to $recipientEmail [Campaign: $campaignId, Provider: $providerId]", 'SUCCESS');
                    $processed++;
                    $this->incrementProcessed();
                } else {
                    throw new Exception($result['error'] ?? 'Unknown error');
                }
            } catch (Exception $e) {
                $attempts++;
                $errorMsg = $e->getMessage();

                $this->log("Failed to send to $recipientEmail (Attempt $attempts): $errorMsg", 'WARNING');
                $this->incrementErrors();

                if ($attempts < $maxRetriesForItem) {
                    // Schedule retry with exponential backoff
                    $retryDelay = min(300, (2 ** ($attempts - 1)) * 60); // 1min, 2min, 4min
                    $nextRetry = date('Y-m-d H:i:s', strtotime("+$retryDelay seconds"));

                    $this->safeQuery(
                        "UPDATE `" . tbl_email_queue . "` SET 
                        status = 'retry',
                        attempts = " . (int)$attempts . ",
                        retries = " . (int)$attempts . ",
                        failed_reason = '" . $this->mysqli->real_escape_string($errorMsg) . "',
                        next_retry_at = '" . $this->mysqli->real_escape_string($nextRetry) . "',
                        updated_at = NOW()
                        WHERE id = " . (int)$queueId
                    );

                    $retried++;
                } else {
                    // Max retries reached, mark as failed
                    $this->safeQuery(
                        "UPDATE `" . tbl_email_queue . "` SET 
                        status = 'failed',
                        attempts = " . (int)$attempts . ",
                        retries = " . (int)$attempts . ",
                        failed_reason = '" . $this->mysqli->real_escape_string($errorMsg) . "',
                        updated_at = NOW()
                        WHERE id = " . (int)$queueId
                    );

                    // Update campaign failed count
                    if (!empty($campaignId)) {
                        $this->safeQuery(
                            "UPDATE `" . tbl_email_campaigns . "` SET 
                            failed_count = failed_count + 1
                            WHERE id = " . (int)$campaignId
                        );
                    }

                    // Log history as failed
                    $this->safeQuery(
                        "INSERT INTO `" . tbl_email_history . "` 
                        (campaign_id, recipient_email, status, error_message, created_at)
                        VALUES (
                            " . ($campaignId ? $campaignId : 'NULL') . ",
                            '" . $this->mysqli->real_escape_string($recipientEmail) . "',
                            'failed',
                            '" . $this->mysqli->real_escape_string($errorMsg) . "',
                            NOW()
                        )"
                    );

                    $failed++;
                }
            }
        }

        $this->log("Queue processing: $processed sent, $failed failed, $retried scheduled for retry", 'INFO');
    }

    /**
     * Get active email providers
     * 
     * @return array Array of active providers indexed by ID
     */
    private function getActiveProviders() {
        global $mysqli;
        $manager = new EmailProviderManager($mysqli);
        
        $result = $this->safeQuery(
            "SELECT * FROM `" . tbl_email_providers . "` 
            WHERE is_active = 1 
            ORDER BY weight DESC, last_used_at ASC"
        );

        if (!$result) {
            return [];
        }

        $providers = [];
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            // Load decrypted credentials via EmailProviderManager.
            $resolved = $manager->getById((int)$row['id']);
            if (is_array($resolved)) {
                $row = array_merge($row, $resolved);
                if (!empty($resolved['smtp_password_decrypted'])) {
                    $row['smtp_password'] = $resolved['smtp_password_decrypted'];
                }
            }
            
            $providers[$row['id']] = $row;
        }

        return $providers;
    }

    /**
     * Select best available provider based on weight and limits
     * 
     * @param array $providers Array of active providers
     * @return int Provider ID
     */
    private function selectProvider($providers) {
        // Select provider based on weight and last used time
        // Weighted round-robin: prefer providers with higher weight and less recent usage
        foreach ($providers as $id => $provider) {
            if (!$this->isProviderLimited($provider)) {
                return $id;
            }
        }

        // All providers at limit, return least recently used
        return array_key_first($providers);
    }

    /**
     * Check if provider has reached rate limits
     * 
     * @param array $provider Provider configuration
     * @return bool True if provider is at limit
     */
    private function isProviderLimited($provider) {
        if (empty($provider['daily_limit']) && empty($provider['per_minute_limit'])) {
            return false;
        }

        // Check daily limit
        if (!empty($provider['daily_limit'])) {
            $dailyResult = $this->safeQuery(
                "SELECT COUNT(*) as cnt FROM `" . tbl_email_history . "` 
                WHERE provider_id = " . $provider['id'] . "
                AND DATE(sent_at) = CURDATE()"
            );

            if ($dailyResult) {
                $daily_count = $dailyResult->fetch_array()['cnt'];
                if ($daily_count >= $provider['daily_limit']) {
                    $this->log("Provider {$provider['id']} at daily limit ($daily_count/{$provider['daily_limit']})", 'WARNING');
                    return true;
                }
            }
        }

        // Check per-minute limit
        if (!empty($provider['per_minute_limit'])) {
            $minuteResult = $this->safeQuery(
                "SELECT COUNT(*) as cnt FROM `" . tbl_email_history . "` 
                WHERE provider_id = " . $provider['id'] . "
                AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)"
            );

            if ($minuteResult) {
                $minute_count = $minuteResult->fetch_array()['cnt'];
                if ($minute_count >= $provider['per_minute_limit']) {
                    $this->log("Provider {$provider['id']} at per-minute limit ($minute_count/{$provider['per_minute_limit']})", 'WARNING');
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Send email using centralized SMTPMailer with provider context.
     *
     * @param array $provider Provider configuration
     * @param string $recipient Recipient email address
     * @param array $payload Email content and settings
     * @return array Result with success flag and data
     */
    private function sendEmail($provider, $recipient, $payload) {
        try {
            // Add tracking pixel
            $trackingId = $payload['tracking_id'] ?? bin2hex(random_bytes(16));
            $htmlBody = $payload['html_body'] ?? '';
            if (!empty($trackingId)) {
                $baseUrl = '';
                if (!empty($GLOBALS['SETTINGS']) && is_array($GLOBALS['SETTINGS']) && !empty($GLOBALS['SETTINGS']['BASE_URL'])) {
                    $baseUrl = rtrim((string)$GLOBALS['SETTINGS']['BASE_URL'], '/');
                } elseif (!empty($GLOBALS['base_url'])) {
                    $baseUrl = rtrim((string)$GLOBALS['base_url'], '/');
                }

                if ($baseUrl === '') {
                    $baseUrl = 'https://haipulse.com';
                }

                $trackingUrl = $baseUrl . "/dashboard/email_tracker.php?id=$trackingId";
                $htmlBody .= "\n<img src=\"$trackingUrl\" width=\"1\" height=\"1\" alt=\"\" />";
            }

            $mailer = new SMTPMailer();
            $headers = [
                'provider_id' => (int)($provider['id'] ?? 0),
                'from' => (string)($payload['from_email'] ?? $provider['email'] ?? ''),
                'from_name' => (string)($payload['from_name'] ?? 'HAI'),
                'Reply-To' => (string)($payload['reply_to'] ?? ''),
                'skip_history_log' => 1,
            ];

            $sendSuccess = $mailer->send(
                $recipient,
                (string)($payload['subject'] ?? 'Email'),
                $htmlBody,
                $headers
            );

            if ($sendSuccess) {
                return [
                    'success' => true,
                    'messageId' => '',
                    'trackingId' => $trackingId
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $mailer->getLastError()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// CLI execution when run directly (not when included by scheduler)
if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $mysqli = $GLOBALS['DB']['MSQLI'];
    $worker = new EmailQueueWorker($mysqli);
    $worker->run();
}

