<?php
/**
 * EmailSendLimiter
 * Enforces a system-wide email send limit (e.g., 500 emails per 24h)
 * Usage: if (!EmailSendLimiter::canSend($conn)) { ... block send ... }
 */
class EmailSendLimiter {
    const LIMIT = 500;
    const WINDOW_HOURS = 24;

    /**
     * Returns true if sending another email is allowed under the limit
     * @param mysqli $conn
     * @return bool
     */
    public static function canSend($conn) {
        $windowStart = date('Y-m-d H:i:s', strtotime('-' . self::WINDOW_HOURS . ' hours'));
        $historyTable = class_exists('DB') ? DB::EMAIL_HISTORY : 'erp_email_history';
        $queueTable = class_exists('DB') ? DB::EMAIL_QUEUE : 'erp_email_queue';
        // Count sent emails in email history and queue (sent or pending)
        $sql = "SELECT (
            (SELECT COUNT(*) FROM `" . $historyTable . "` WHERE sent_at >= ?) +
            (SELECT COUNT(*) FROM `" . $queueTable . "` WHERE status IN ('pending','sent') AND created_at >= ?)
        ) AS sent_count";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return false; // Fail safe: block send
        $stmt->bind_param('ss', $windowStart, $windowStart);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ((int)($row['sent_count'] ?? 0) < self::LIMIT);
    }
}
