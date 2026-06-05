<?php
require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../../config/database.php';

class EmailPreferences {
    private $mysqli;
    private $usersTable = DB::FRONTEND_USERS;
    private $unsubscribesTable = DB::EMAIL_UNSUBSCRIBES;
    private $historyTable = DB::EMAIL_HISTORY;

    public function __construct($mysqli = null) {
        global $conn;
        $this->mysqli = $mysqli ?? $conn;
    }

    public function getUserEmail($userId) {
        $sql = "SELECT email FROM `{$this->usersTable}` WHERE id = ? LIMIT 1";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row['email'] ?? null;
    }

    public function isUnsubscribed($email) {
        $sql = "SELECT id FROM `{$this->unsubscribesTable}` WHERE email = ? LIMIT 1";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return !empty($row['id']);
    }

    public function subscribeEmail($email) {
        $sql = "DELETE FROM `{$this->unsubscribesTable}` WHERE email = ?";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $email);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function unsubscribeEmail($email, $reason = 'User unsubscribed', $source = 'public', $companyId = null) {
        $existing = $this->isUnsubscribed($email);

        if ($existing) {
            return true;
        }

        $sql = "INSERT INTO `{$this->unsubscribesTable}` (email, company_id, reason, source, unsubscribed_at)
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('siss', $email, $companyId, $reason, $source);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function getPreferenceByUser($userId) {
        $email = $this->getUserEmail($userId);
        if (!$email) {
            return null;
        }

        return [
            'email' => $email,
            'is_subscribed' => !$this->isUnsubscribed($email)
        ];
    }

    public function updateUserPreference($userId, $isSubscribed) {
        $email = $this->getUserEmail($userId);
        if (!$email) {
            return false;
        }

        if ($isSubscribed) {
            return $this->subscribeEmail($email);
        }

        return $this->unsubscribeEmail($email, 'Updated from preferences center', 'preferences_center', null);
    }

    public function getEmailHistoryStats($email) {
        $stats = [
            'total' => 0,
            'sent' => 0,
            'opened' => 0,
            'clicked' => 0
        ];

        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent,
                    SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) AS opened,
                    SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) AS clicked
                FROM `{$this->historyTable}`
                WHERE recipient_email = ?";

        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return $stats;
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $stats['total'] = (int)($row['total'] ?? 0);
            $stats['sent'] = (int)($row['sent'] ?? 0);
            $stats['opened'] = (int)($row['opened'] ?? 0);
            $stats['clicked'] = (int)($row['clicked'] ?? 0);
        }

        return $stats;
    }
}
