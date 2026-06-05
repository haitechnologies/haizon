<?php
/**
 * Cron: Sync subscription tiers
 *
 * Purpose:
 * 1) Mark ended listing subscriptions as expired
 * 2) Re-sync directory search tiers for affected users
 *
 * Run via scheduler (example):
 * php /path/to/cron/sync_subscription_tiers.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/BusinessListingPlan.php';

function tableExists(mysqli $conn, string $table): bool {
    $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    fwrite(STDERR, "[subscription-sync] Database connection unavailable\n");
    exit(1);
}

$now = date('Y-m-d H:i:s');
$expiredCount = 0;
$syncedUsers = 0;

// 1) Expire finished subscriptions.
$expireSql = "
    UPDATE `" . DB::LISTING_SUBSCRIPTIONS . "`
    SET status = 'expired', updated_at = NOW()
    WHERE status IN ('active', 'trial')
      AND subscription_end_at IS NOT NULL
      AND subscription_end_at < ?
";

$expireStmt = $conn->prepare($expireSql);
if ($expireStmt) {
    $expireStmt->bind_param('s', $now);
    $expireStmt->execute();
    $expiredCount = (int)$expireStmt->affected_rows;
    $expireStmt->close();
}

// 2) Find users tied to any listing subscription (owner or creator) and sync tiers.
if (tableExists($conn, DB::COMPANIES)) {
    $userSql = "
        SELECT DISTINCT COALESCE(NULLIF(c.owner_user_id, 0), c.created_by) AS user_id
        FROM `" . DB::LISTING_SUBSCRIPTIONS . "` s
        INNER JOIN `" . DB::COMPANIES . "` c ON c.id = s.company_id
        WHERE COALESCE(NULLIF(c.owner_user_id, 0), c.created_by) > 0
    ";

    $userResult = $conn->query($userSql);
    if ($userResult) {
        while ($row = $userResult->fetch_assoc()) {
            $userId = (int)($row['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            if (BusinessListingPlan::syncUserSearchTier($conn, $userId)) {
                $syncedUsers++;
            }
        }
    }
} else {
    echo "[subscription-sync] skip user tier sync: " . DB::COMPANIES . " table not found\n";
}

echo "[subscription-sync] completed: expired={$expiredCount}, synced_users={$syncedUsers}\n";
exit(0);
