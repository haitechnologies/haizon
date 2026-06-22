<?php
require_once dirname(dirname(__DIR__)) . '/admin_elements/error_handler_init.php';

use App\Core\DB;
use App\Service\EmailQueue;

require_once __DIR__ . '/../../../config/globals.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../CronJobBase.php';

class DocumentExpiryCron extends CronJobBase {

    private int $notifyDays = 30;
    private int $criticalDays = 7;

    protected function getJobName() {
        return 'document_expiry';
    }

    public function execute() {
        $this->log("Checking document expiry dates...", 'INFO');

        $notifyCount = $this->processExpiringDocuments($this->notifyDays, 'NOTIFY');
        $criticalCount = $this->processExpiringDocuments($this->criticalDays, 'CRITICAL');

        $total = $notifyCount + $criticalCount;
        if ($total > 0) {
            $this->log("Found $total expiring document(s) - $notifyCount near expiry, $criticalCount critical", 'SUCCESS');
            $this->incrementProcessed($total);
        } else {
            $this->log("No documents expiring within {$this->notifyDays} days.", 'INFO');
        }
    }

    private function processExpiringDocuments(int $days, string $severity): int {
        $prefix = $GLOBALS['TBL']['PREFIX'];
        $count = 0;

        // Employee documents
        $sql = "SELECT ud.id, ud.attachable_id AS user_id, ud.expiry_date,
                       dc.document_category,
                       u.full_name, u.email,
                       'employee' AS doc_type
                FROM `{$prefix}attachments` ud
                JOIN `{$prefix}document_categories` dc ON ud.document_category = dc.id
                JOIN `{$prefix}users` u ON ud.attachable_id = u.id
                WHERE ud.attachable_type = 'UserDoc'
                  AND ud.expiry_date IS NOT NULL
                  AND ud.expiry_date != '1970-01-01'
                  AND ud.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $days DAY)
                  AND u.is_active = 1
                UNION ALL
                -- Organization documents
                SELECT ud.id, ud.attachable_id AS org_id, ud.expiry_date,
                       dc.document_category,
                       o.warehouse_name AS full_name, o.email,
                       'organization' AS doc_type
                FROM `{$prefix}attachments` ud
                JOIN `{$prefix}document_categories` dc ON ud.document_category = dc.id
                JOIN `{$prefix}organizations` o ON ud.attachable_id = o.id
                WHERE ud.attachable_type = 'OrganizationDoc'
                  AND ud.expiry_date IS NOT NULL
                  AND ud.expiry_date != '1970-01-01'
                  AND ud.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $days DAY)
                  AND o.is_active = 1
                ORDER BY expiry_date ASC";

        $result = $this->safeQuery($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->sendExpiryNotification($row, $severity);
                $count++;
            }
        }

        return $count;
    }

    private function sendExpiryNotification(array $row, string $severity): void {
        $recipientName = $row['full_name'];
        $category = $row['document_category'];
        $expiryDate = $row['expiry_date'];
        $email = $row['email'];
        $docType = $row['doc_type'] ?? 'employee';
        $daysLeft = max(0, (int)ceil((strtotime($expiryDate) - time()) / 86400));

        $recipientLabel = $docType === 'organization' ? 'Organization' : 'Employee';
        $subject = "[$severity] Document Expiry: $category expiring in $daysLeft day(s)";

        $body = "Hello $recipientName,\n\n"
              . "This is a notification regarding your $docType document: $category\n\n"
              . "Expiry Date: $expiryDate\n"
              . "Days Remaining: $daysLeft\n\n"
              . "Please renew this document at your earliest convenience.\n\n"
              . "Regards,\nAdmin Department";

        $emailQueue = new EmailQueue();
        $enqueued = $emailQueue->enqueue($email, $subject, $body, [
            'X-Priority' => $severity === 'CRITICAL' ? '1' : '3',
            'X-Cron-Job' => 'document_expiry',
            'X-Document-Id' => (string)$row['id'],
        ]);

        if ($enqueued) {
            $this->log("Notification sent to $email for '$category' ($recipientLabel, expires: $expiryDate, $daysLeft days)", $severity === 'CRITICAL' ? 'WARNING' : 'INFO');
        } else {
            $this->log("Failed to enqueue email for $email - '$category'", 'ERROR');
            $this->incrementErrors();
        }
    }
}

if (php_sapi_name() === 'cli') {
    $mysqli = $GLOBALS['DB']['MSQLI'];
    $cron = new DocumentExpiryCron($mysqli);
    $cron->run();
} else {
    http_response_code(403);
    die('CLI only');
}