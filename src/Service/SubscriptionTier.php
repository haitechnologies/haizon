<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Container;
use App\Core\Database;
use App\Core\DB;
use Exception;
use Throwable;

class SubscriptionTier
{
    // Tier constants
    public const TIER_FREE = 'free';
    public const TIER_REGISTERED = 'registered';
    public const TIER_SILVER = 'silver';
    public const TIER_GOLD = 'gold';
    public const TIER_PLATINUM = 'platinum';

    // Backward-compatible aliases
    public const TIER_PRO = self::TIER_GOLD;
    public const TIER_ENTERPRISE = self::TIER_PLATINUM;

    // Feature flags
    private static array $tierFeatures = [
        'free' => [
            'results_per_search' => 100,
            'saved_searches' => 5,
            'company_views_per_day' => 5,
            'csv_export_rows_per_month' => 0,
            'api_access' => false,
            'email_leads' => false,
            'phone_numbers' => false,
            'contact_emails' => false,
            'advanced_filters' => false,
            'direct_messaging' => false,
            'priority_support' => false,
            'bulk_operations' => false,
        ],
        'registered' => [
            'results_per_search' => 1000,
            'saved_searches' => 20,
            'company_views_per_day' => 20,
            'csv_export_rows_per_month' => 100,
            'api_access' => false,
            'email_leads' => false,
            'phone_numbers' => false,
            'contact_emails' => true,
            'advanced_filters' => true,
            'direct_messaging' => true,
            'priority_support' => false,
            'bulk_operations' => false,
        ],
        'silver' => [
            'results_per_search' => 5000,
            'saved_searches' => 9999,
            'company_views_per_day' => 9999,
            'csv_export_rows_per_month' => 9999,
            'api_access' => true,
            'email_leads' => true,
            'phone_numbers' => true,
            'contact_emails' => true,
            'advanced_filters' => true,
            'direct_messaging' => true,
            'priority_support' => false,
            'bulk_operations' => true,
        ],
        'gold' => [
            'results_per_search' => 25000,
            'saved_searches' => 9999,
            'company_views_per_day' => 9999,
            'csv_export_rows_per_month' => 9999,
            'api_access' => true,
            'email_leads' => true,
            'phone_numbers' => true,
            'contact_emails' => true,
            'advanced_filters' => true,
            'direct_messaging' => true,
            'priority_support' => true,
            'bulk_operations' => true,
        ],
        'platinum' => [
            'results_per_search' => 100000,
            'saved_searches' => 9999,
            'company_views_per_day' => 9999,
            'csv_export_rows_per_month' => 9999,
            'api_access' => true,
            'email_leads' => true,
            'phone_numbers' => true,
            'contact_emails' => true,
            'advanced_filters' => true,
            'direct_messaging' => true,
            'priority_support' => true,
            'bulk_operations' => true,
        ],
    ];

    private static function normalizeTier(?string $tier): string
    {
        $tier = strtolower(trim((string)$tier));

        if ($tier === 'pro') {
            return self::TIER_GOLD;
        }
        if ($tier === 'enterprise') {
            return self::TIER_PLATINUM;
        }

        if (in_array($tier, [self::TIER_FREE, self::TIER_REGISTERED, self::TIER_SILVER, self::TIER_GOLD, self::TIER_PLATINUM], true)) {
            return $tier;
        }

        return self::TIER_REGISTERED;
    }

    /**
     * Get user subscription tier
     *
     * @param int|null $userId User ID
     * @param mixed $conn Optional database connection
     * @return string
     */
    public static function getUserTier(?int $userId = null, mixed $conn = null): string
    {
        if (!$userId) {
            return self::TIER_FREE;
        }

        // Handle custom connection wrapper or legacy mysqli connection
        if ($conn !== null) {
            if ($conn instanceof Database) {
                return self::getUserTierWithDb($userId, $conn);
            } elseif ($conn instanceof \mysqli) {
                return self::getUserTierLegacyMysqli($userId, $conn);
            }
        }

        // Fallback to container-resolved database
        $db = self::getDatabaseFromContainer();
        if ($db !== null) {
            return self::getUserTierWithDb($userId, $db);
        }

        return self::TIER_REGISTERED;
    }

    private static function getUserTierWithDb(int $userId, Database $db): string
    {
        try {
            $query = "SELECT subscription_tier, subscription_expires_at 
                      FROM " . DB::CUSTOMERS . " 
                      WHERE id = :id";

            $row = $db->fetchOne($query, ['id' => $userId]);
        } catch (Throwable $e) {
            error_log("SubscriptionTier::getUserTier() - Fallback to registered tier: " . $e->getMessage());
            return self::TIER_REGISTERED;
        }

        if (!$row) {
            return self::TIER_REGISTERED;
        }

        $tier = self::normalizeTier($row['subscription_tier'] ?? self::TIER_REGISTERED);

        if (!in_array($tier, [self::TIER_FREE, self::TIER_REGISTERED], true) && !empty($row['subscription_expires_at'])) {
            if (strtotime($row['subscription_expires_at']) < time()) {
                self::setUserTierWithDb($userId, self::TIER_REGISTERED, $db);
                return self::TIER_REGISTERED;
            }
        }

        return $tier;
    }

    private static function getUserTierLegacyMysqli(int $userId, \mysqli $conn): string
    {
        try {
            $query = "SELECT subscription_tier, subscription_expires_at 
                      FROM " . DB::CUSTOMERS . " 
                      WHERE id = ?";

            $stmt = $conn->prepare($query);
            if (!$stmt) {
                error_log("SubscriptionTier::getUserTier() - Prepare error: " . $conn->error);
                return self::TIER_REGISTERED;
            }

            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
        } catch (Throwable $e) {
            error_log("SubscriptionTier::getUserTier() - Fallback to registered tier: " . $e->getMessage());
            return self::TIER_REGISTERED;
        }

        if (!$row) {
            return self::TIER_REGISTERED;
        }

        $tier = self::normalizeTier($row['subscription_tier'] ?? self::TIER_REGISTERED);

        if (!in_array($tier, [self::TIER_FREE, self::TIER_REGISTERED], true) && !empty($row['subscription_expires_at'])) {
            if (strtotime($row['subscription_expires_at']) < time()) {
                self::setUserTierLegacyMysqli($userId, self::TIER_REGISTERED, $conn);
                return self::TIER_REGISTERED;
            }
        }

        return $tier;
    }

    /**
     * Set user subscription tier
     */
    public static function setUserTier(int $userId, string $newTier, mixed $conn = null, ?string $expiresAt = null): bool
    {
        $normalizedTier = self::normalizeTier($newTier);
        if ($normalizedTier === self::TIER_REGISTERED && !in_array($newTier, [self::TIER_REGISTERED, 'registered'], true)) {
            error_log("SubscriptionTier::setUserTier() - Invalid tier: $newTier");
            return false;
        }

        if ($conn !== null) {
            if ($conn instanceof Database) {
                return self::setUserTierWithDb($userId, $normalizedTier, $conn, $expiresAt);
            } elseif ($conn instanceof \mysqli) {
                return self::setUserTierLegacyMysqli($userId, $normalizedTier, $conn, $expiresAt);
            }
        }

        $db = self::getDatabaseFromContainer();
        if ($db !== null) {
            return self::setUserTierWithDb($userId, $normalizedTier, $db, $expiresAt);
        }

        return false;
    }

    private static function setUserTierWithDb(int $userId, string $normalizedTier, Database $db, ?string $expiresAt = null): bool
    {
        $oldTier = self::getUserTierWithDb($userId, $db);

        try {
            $query = "UPDATE " . DB::CUSTOMERS . " 
                      SET subscription_tier = :tier, subscription_expires_at = :expires 
                      WHERE id = :id";

            $db->execute($query, [
                'tier' => $normalizedTier,
                'expires' => $expiresAt,
                'id' => $userId
            ]);

            if ($oldTier !== $normalizedTier) {
                self::logTierChangeWithDb($userId, $oldTier, $normalizedTier, $db);
            }

            return true;
        } catch (Throwable $e) {
            error_log("SubscriptionTier::setUserTier() - Update error: " . $e->getMessage());
            return false;
        }
    }

    private static function setUserTierLegacyMysqli(int $userId, string $normalizedTier, \mysqli $conn, ?string $expiresAt = null): bool
    {
        $oldTier = self::getUserTierLegacyMysqli($userId, $conn);

        $query = "UPDATE " . DB::CUSTOMERS . " 
                  SET subscription_tier = ?, subscription_expires_at = ? 
                  WHERE id = ?";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("SubscriptionTier::setUserTier() - Prepare error: " . $conn->error);
            return false;
        }

        $stmt->bind_param("ssi", $normalizedTier, $expiresAt, $userId);
        $success = $stmt->execute();
        $stmt->close();

        if ($success && $oldTier !== $normalizedTier) {
            self::logTierChangeLegacyMysqli($userId, $oldTier, $normalizedTier, $conn);
        }

        return $success;
    }

    public static function getFeatureValue(string $tier, string $feature): mixed
    {
        $tier = self::normalizeTier($tier);
        return self::$tierFeatures[$tier][$feature] ?? null;
    }

    public static function hasFeature(int|string $userIdOrTier, string $feature, mixed $conn = null): bool
    {
        if (is_string($userIdOrTier)) {
            $tier = self::normalizeTier($userIdOrTier);
        } else {
            $tier = self::getUserTier((int)$userIdOrTier, $conn);
        }

        $value = self::getFeatureValue($tier, $feature);

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return $value > 0;
        }

        return false;
    }

    public static function getTierFeatures(string $tier): array
    {
        $tier = self::normalizeTier($tier);
        return self::$tierFeatures[$tier] ?? self::$tierFeatures[self::TIER_FREE];
    }

    public static function getPricing(): array
    {
        return [
            self::TIER_FREE => [
                'name' => 'Free',
                'price' => 0,
                'billing_period' => null,
                'description' => '100 results per search',
                'recommended' => false,
            ],
            self::TIER_REGISTERED => [
                'name' => 'Free Account',
                'price' => 0,
                'billing_period' => null,
                'description' => '1,000 results per search (logged in)',
                'recommended' => false,
            ],
            self::TIER_SILVER => [
                'name' => 'Silver',
                'price' => 50,
                'billing_period' => 'month',
                'description' => '5,000 results per search',
                'recommended' => false,
            ],
            self::TIER_GOLD => [
                'name' => 'Gold',
                'price' => 150,
                'billing_period' => 'month',
                'description' => '25,000 results per search',
                'recommended' => true,
            ],
            self::TIER_PLATINUM => [
                'name' => 'Platinum',
                'price' => 250,
                'billing_period' => 'month',
                'description' => '100,000 results per search',
                'recommended' => false,
            ],
        ];
    }

    public static function getTrialDays(): int
    {
        return 14;
    }

    public static function getTrialExpiration(): string
    {
        $expirationTime = time() + (self::getTrialDays() * 86400);
        return date('Y-m-d H:i:s', $expirationTime);
    }

    private static function logTierChangeWithDb(int $userId, string $tierFrom, string $tierTo, Database $db): void
    {
        try {
            $logQuery = "INSERT INTO " . DB::SUBSCRIPTION_LOGS . " 
                        (customer_id, tier_from, tier_to, changed_at) 
                        VALUES (:userId, :tierFrom, :tierTo, NOW())";

            $db->execute($logQuery, [
                'userId' => $userId,
                'tierFrom' => $tierFrom,
                'tierTo' => $tierTo
            ]);
        } catch (Throwable $e) {
            error_log("SubscriptionTier::logTierChange() - Error logging tier change: " . $e->getMessage());
        }
    }

    private static function logTierChangeLegacyMysqli(int $userId, string $tierFrom, string $tierTo, \mysqli $conn): void
    {
        $logQuery = "INSERT INTO " . DB::SUBSCRIPTION_LOGS . " 
                    (customer_id, tier_from, tier_to, changed_at) 
                    VALUES (?, ?, ?, NOW())";

        $stmt = $conn->prepare($logQuery);
        if ($stmt) {
            $stmt->bind_param("iss", $userId, $tierFrom, $tierTo);
            $stmt->execute();
            $stmt->close();
        }
    }

    public static function getUpgradeOptions(string $currentTier): array
    {
        $upgrades = [];
        $currentTier = self::normalizeTier($currentTier);

        switch ($currentTier) {
            case self::TIER_FREE:
                $upgrades[] = self::TIER_REGISTERED;
                $upgrades[] = self::TIER_SILVER;
                $upgrades[] = self::TIER_GOLD;
                $upgrades[] = self::TIER_PLATINUM;
                break;
            case self::TIER_REGISTERED:
                $upgrades[] = self::TIER_SILVER;
                $upgrades[] = self::TIER_GOLD;
                $upgrades[] = self::TIER_PLATINUM;
                break;
            case self::TIER_SILVER:
                $upgrades[] = self::TIER_GOLD;
                $upgrades[] = self::TIER_PLATINUM;
                break;
            case self::TIER_GOLD:
                $upgrades[] = self::TIER_PLATINUM;
                break;
            case self::TIER_PLATINUM:
                break;
        }

        $pricing = self::getPricing();
        $result = [];

        foreach ($upgrades as $tier) {
            $result[$tier] = $pricing[$tier];
        }

        return $result;
    }

    public static function isOnTrial(int $userId, mixed $conn = null): bool
    {
        $tier = self::getUserTier($userId, $conn);

        if (!in_array($tier, [self::TIER_SILVER, self::TIER_GOLD, self::TIER_PLATINUM], true)) {
            return false;
        }

        $row = null;
        if ($conn !== null) {
            if ($conn instanceof Database) {
                $row = $conn->fetchOne("SELECT created_at FROM " . DB::CUSTOMERS . " WHERE id = :id", ['id' => $userId]);
            } elseif ($conn instanceof \mysqli) {
                $stmt = $conn->prepare("SELECT created_at FROM " . DB::CUSTOMERS . " WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $row = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }
            }
        } else {
            $db = self::getDatabaseFromContainer();
            if ($db !== null) {
                $row = $db->fetchOne("SELECT created_at FROM " . DB::CUSTOMERS . " WHERE id = :id", ['id' => $userId]);
            }
        }

        if (!$row) {
            return false;
        }

        $trialDays = self::getTrialDays();
        $trialEndTime = strtotime($row['created_at']) + ($trialDays * 86400);

        return time() < $trialEndTime;
    }

    private static function getDatabaseFromContainer(): ?Database
    {
        try {
            $container = Container::getInstance();
            if ($container->has(Database::class)) {
                return $container->get(Database::class);
            }
        } catch (Throwable $e) {
            // Ignore container errors
        }
        return null;
    }
}
