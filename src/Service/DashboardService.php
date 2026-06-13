<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\DB;
use App\Core\Database;

/**
 * Dashboard Service
 *
 * Consolidates all database logic and statistics computation for the admin dashboard.
 */
class DashboardService
{
    private \mysqli $mysqli;

    public function __construct()
    {
        $this->mysqli = DB::mysqli();
    }

    /**
     * Compute and fetch all dashboard stats, trends, and recent logs.
     *
     * @param int $unreadErrorLogsCount Unread admin logs count
     * @param int $frontendErrorLogsCount Unread public logs count
     * @param string $dashboardView Compact or detailed view
     * @return array
     */
    public function getDashboardData(int $unreadErrorLogsCount, int $frontendErrorLogsCount, string $dashboardView): array
    {
        // Helper count function
        $countFn = function (string $sql): int {
            $res = $this->mysqli->query($sql);
            if (!$res) {
                return 0;
            }
            $row = $res->fetch_assoc();
            return (int)($row['cnt'] ?? 0);
        };

        // Recent emails
        $recentEmailsLimit = 5;
        $recentDashboardEmails = [];
        $recentWebsiteEmails = [];

        $recentDashboardEmailsResult = $this->mysqli->query(
            "SELECT eh.recipient_email, eh.subject, COALESCE(eh.sent_at, eh.created_at) AS sent_time
             FROM `" . DB::EMAIL_HISTORY . "` eh
             LEFT JOIN `" . DB::USERS . "` du ON du.id = eh.user_id
             WHERE eh.status = 'sent'
               AND (du.id IS NOT NULL OR (eh.campaign_id IS NOT NULL AND eh.campaign_id > 0))
             ORDER BY COALESCE(eh.sent_at, eh.created_at) DESC
             LIMIT " . (int)$recentEmailsLimit
        );
        if ($recentDashboardEmailsResult) {
            while ($emailRow = $recentDashboardEmailsResult->fetch_assoc()) {
                $recentDashboardEmails[] = $emailRow;
            }
        }

        $recentWebsiteEmailsResult = $this->mysqli->query(
            "SELECT eh.recipient_email, eh.subject, COALESCE(eh.sent_at, eh.created_at) AS sent_time
             FROM `" . DB::EMAIL_HISTORY . "` eh
             LEFT JOIN `" . DB::USERS . "` du ON du.id = eh.user_id
             WHERE eh.status = 'sent'
               AND NOT (du.id IS NOT NULL OR (eh.campaign_id IS NOT NULL AND eh.campaign_id > 0))
             ORDER BY COALESCE(eh.sent_at, eh.created_at) DESC
             LIMIT " . (int)$recentEmailsLimit
        );
        if ($recentWebsiteEmailsResult) {
            while ($emailRow = $recentWebsiteEmailsResult->fetch_assoc()) {
                $recentWebsiteEmails[] = $emailRow;
            }
        }

        // Inquiries trend
        $inquiries30d = [];
        $inquiries30dMap = [];
        $inquiries30dResult = $this->mysqli->query(
            "SELECT DATE(created_at) AS day_key, COUNT(*) AS cnt
             FROM `" . DB::INQUIRIES . "`
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
             GROUP BY DATE(created_at)"
        );
        if ($inquiries30dResult) {
            while ($trendRow = $inquiries30dResult->fetch_assoc()) {
                $key = (string)($trendRow['day_key'] ?? '');
                if ($key !== '') {
                    $inquiries30dMap[$key] = (int)($trendRow['cnt'] ?? 0);
                }
            }
        }
        for ($i = 29; $i >= 0; $i--) {
            $dateKey = date('Y-m-d', strtotime('-' . $i . ' day'));
            $inquiries30d[] = [
                'label' => date('d M', strtotime($dateKey)),
                'count' => (int)($inquiries30dMap[$dateKey] ?? 0),
            ];
        }
        $inquiries30dMax = 1;
        foreach ($inquiries30d as $point) {
            if ((int)$point['count'] > $inquiries30dMax) {
                $inquiries30dMax = (int)$point['count'];
            }
        }

        $todayInquiries = $countFn("SELECT COUNT(*) AS cnt FROM `" . DB::INQUIRIES . "` WHERE DATE(created_at) = CURDATE()");
        $weekInquiries = $countFn("SELECT COUNT(*) AS cnt FROM `" . DB::INQUIRIES . "` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $totalInquiries = $countFn("SELECT COUNT(*) AS cnt FROM `" . DB::INQUIRIES . "`");

        $totalHsCodes = $countFn("SELECT COUNT(*) AS cnt FROM `" . DB::HS_CODES . "`");
        $emailsSentTotal = $countFn("SELECT COUNT(*) AS cnt FROM `" . DB::EMAIL_HISTORY . "` WHERE status = 'sent'");
        $emailsSent24h = $countFn("SELECT COUNT(*) AS cnt FROM `" . DB::EMAIL_HISTORY . "` WHERE status = 'sent' AND DATE(COALESCE(sent_at, created_at)) = CURDATE()");
        $emailsSent7d = $countFn("SELECT COUNT(*) AS cnt FROM `" . DB::EMAIL_HISTORY . "` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

        $emailsSentDashboardTotal = $countFn(
            "SELECT COUNT(*) AS cnt
             FROM `" . DB::EMAIL_HISTORY . "` eh
             LEFT JOIN `" . DB::USERS . "` du ON du.id = eh.user_id
             WHERE eh.status = 'sent'
                 AND (du.id IS NOT NULL OR (eh.campaign_id IS NOT NULL AND eh.campaign_id > 0))"
        );
        $emailsSentDashboard24h = $countFn(
            "SELECT COUNT(*) AS cnt
             FROM `" . DB::EMAIL_HISTORY . "` eh
             LEFT JOIN `" . DB::USERS . "` du ON du.id = eh.user_id
             WHERE eh.status = 'sent'
                 AND DATE(COALESCE(eh.sent_at, eh.created_at)) = CURDATE()
                 AND (du.id IS NOT NULL OR (eh.campaign_id IS NOT NULL AND eh.campaign_id > 0))"
        );

        $emailsSentWebsiteTotal = max(0, $emailsSentTotal - $emailsSentDashboardTotal);
        $emailsSentWebsite24h = max(0, $emailsSent24h - $emailsSentDashboard24h);
        $emailsPending = $countFn("SELECT COUNT(*) AS cnt FROM `" . DB::EMAIL_QUEUE . "` WHERE status IN ('pending','queued','retry')");

        $emailDailyLimitTotal = $countFn(
            "SELECT COALESCE(SUM(CASE WHEN daily_limit > 0 THEN daily_limit ELSE 100 END), 0) AS cnt
             FROM `" . DB::EMAIL_PROVIDERS . "`
             WHERE is_active = 1"
        );
        $emailsRemaining24h = max(0, $emailDailyLimitTotal - $emailsSent24h);
        $emailProvidersCount = $countFn("SELECT COUNT(*) AS cnt FROM `" . DB::EMAIL_PROVIDERS . "`");
        $disposableEmailDomainsCount = $countFn("SELECT COUNT(*) AS cnt FROM `" . DB::DISPOSABLE_EMAIL_DOMAINS . "`");
        $bannedWordsCount = $countFn("SELECT COUNT(*) AS cnt FROM `" . DB::BANNED_WORDS . "`");

        $totalCustomers = $countFn("SELECT COUNT(*) AS cnt FROM `" . DB::CUSTOMERS . "`");
        $newCustomers7d = $countFn("SELECT COUNT(*) AS cnt FROM `" . DB::CUSTOMERS . "` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

        $totalDashboardUsers = $countFn("SELECT COUNT(*) AS cnt FROM `" . DB::USERS . "`");

        // Logs path setup
        $adminLogPath = dirname(__DIR__, 2) . '/dashboard/CONSOLIDATED_ERROR_LOG.txt';
        if (!is_file($adminLogPath)) {
            $adminLogPath = dirname(__DIR__, 2) . '/dashboard/error_log.txt';
        }

        $frontendLogPath = function_exists('resolveFrontendErrorLogPath')
            ? resolveFrontendErrorLogPath()
            : dirname(__DIR__, 2) . '/logs/FRONTEND_ERROR_LOG.txt';

        $adminRecentEntries = $this->getRecentErrorEntries($adminLogPath, 5);
        $publicRecentEntries = $this->getRecentErrorEntries($frontendLogPath, 5);

        return [
            'recentDashboardEmails' => $recentDashboardEmails,
            'recentWebsiteEmails' => $recentWebsiteEmails,
            'inquiries30d' => $inquiries30d,
            'inquiries30dMax' => $inquiries30dMax,
            'today_inquiries' => $todayInquiries,
            'week_inquiries' => $weekInquiries,
            'total_inquiries' => $totalInquiries,
            'total_hs_codes' => $totalHsCodes,
            'emails_sent_total' => $emailsSentTotal,
            'emails_sent_24h' => $emailsSent24h,
            'emails_sent_7d' => $emailsSent7d,
            'emails_sent_dashboard_total' => $emailsSentDashboardTotal,
            'emails_sent_dashboard_24h' => $emailsSentDashboard24h,
            'emails_sent_website_total' => $emailsSentWebsiteTotal,
            'emails_sent_website_24h' => $emailsSentWebsite24h,
            'emails_pending' => $emailsPending,
            'email_daily_limit_total' => $emailDailyLimitTotal,
            'emails_remaining_24h' => $emailsRemaining24h,
            'email_providers_count' => $emailProvidersCount,
            'disposable_email_domains_count' => $disposableEmailDomainsCount,
            'banned_words_count' => $bannedWordsCount,
            'total_customers' => $totalCustomers,
            'new_customers_7d' => $newCustomers7d,
            'total_dashboard_users' => $totalDashboardUsers,
            'adminLogCount' => $unreadErrorLogsCount,
            'publicLogCount' => $frontendErrorLogsCount,
            'adminRecentEntries' => $adminRecentEntries,
            'publicRecentEntries' => $publicRecentEntries,
            'isDetailedView' => ($dashboardView === 'detailed'),
        ];
    }

    private function getRecentErrorEntries(string $filePath, int $limit = 3): array
    {
        $tailLines = $this->tailLines($filePath, 80);
        if (empty($tailLines)) {
            return [];
        }

        $errorPattern = '/\b(PHP|Fatal|Error|Exception|Warning|Notice|Deprecated|CRITICAL|FATAL|WARNING)\b/i';
        $filtered = [];
        foreach ($tailLines as $line) {
            $clean = trim((string)$line);
            if ($clean === '') {
                continue;
            }
            if (preg_match($errorPattern, $clean)) {
                $filtered[] = $clean;
            }
        }

        if (empty($filtered)) {
            $filtered = $tailLines;
        }

        return array_slice($filtered, -$limit);
    }

    private function tailLines(string $filePath, int $maxLines = 60): array
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return [];
        }

        $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines) || empty($lines)) {
            return [];
        }

        return array_slice($lines, -$maxLines);
    }
}
