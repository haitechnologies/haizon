<?php
/**
 * SubscriptionTier Class
 * 
 * Manages user subscription tiers and feature access control.
 * Implements freemium model with tier-based result limiting.
 * 
 * Tiers:
 * - 'free': Anonymous user, 100 results/search
 * - 'registered': Logged-in user, 1,000 results/search
 * - 'silver': Paid subscriber, 5,000 results/search
 * - 'gold': Paid subscriber, 25,000 results/search
 * - 'platinum': Paid subscriber, 100,000 results/search
 */

class SubscriptionTier
{
    // Tier constants
    const TIER_FREE = 'free';
    const TIER_REGISTERED = 'registered';
    const TIER_SILVER = 'silver';
    const TIER_GOLD = 'gold';
    const TIER_PLATINUM = 'platinum';

    // Backward-compatible aliases used in older code paths.
    const TIER_PRO = self::TIER_GOLD;
    const TIER_ENTERPRISE = self::TIER_PLATINUM;

    // Feature flags
    private static $tierFeatures = [
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

    /**
     * Normalize legacy tier values from older deployments.
     */
    private static function normalizeTier($tier)
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
     * Get current user's subscription tier
     * 
     * @param int $userId Customer ID (null = anonymous)
     * @param mysqli $conn Database connection
    * @return string Tier: 'free', 'registered', 'silver', 'gold', 'platinum'
     */
    public static function getUserTier($userId = null, $conn = null)
    {
        // No user = anonymous free tier
        if (!$userId) {
            return self::TIER_FREE;
        }

        // Get database connection if not provided
        if (!$conn) {
            global $mysqli;
            $conn = $mysqli;
        }

        // Check if user subscription is expired.
        // Some deployments may not have subscription columns yet.
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

        // Check if subscription expired
        if (!in_array($tier, [self::TIER_FREE, self::TIER_REGISTERED], true) && $row['subscription_expires_at']) {
            if (strtotime($row['subscription_expires_at']) < time()) {
                // Subscription expired, downgrade to registered
                self::setUserTier($userId, self::TIER_REGISTERED, $conn);
                return self::TIER_REGISTERED;
            }
        }

        return $tier;
    }

    /**
     * Set/upgrade user subscription tier
     * 
     * @param int $userId Customer ID
    * @param string $newTier New tier ('silver', 'gold', 'platinum', 'registered')
     * @param mysqli $conn Database connection
     * @param string $expiresAt Optional expiration date (Y-m-d H:i:s)
     * @return bool Success
     */
    public static function setUserTier($userId, $newTier, $conn = null, $expiresAt = null)
    {
        $normalizedTier = self::normalizeTier($newTier);
        if ($normalizedTier === self::TIER_REGISTERED && !in_array($newTier, [self::TIER_REGISTERED, 'registered'], true)) {
            error_log("SubscriptionTier::setUserTier() - Invalid tier: $newTier");
            return false;
        }

        if (!$conn) {
            global $mysqli;
            $conn = $mysqli;
        }

        // Get old tier for audit log
        $oldTier = self::getUserTier($userId, $conn);

        // Update user tier
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
            // Log tier change
            self::logTierChange($userId, $oldTier, $normalizedTier, $conn);
        }

        return $success;
    }

    /**
     * Get feature value for tier
     * 
     * @param string $tier Subscription tier
     * @param string $feature Feature name
     * @return mixed Feature value or null if not found
     */
    public static function getFeatureValue($tier, $feature)
    {
        $tier = self::normalizeTier($tier);

        if (!isset(self::$tierFeatures[$tier])) {
            return null;
        }

        return self::$tierFeatures[$tier][$feature] ?? null;
    }

    /**
     * Check if tier has feature enabled
     * 
     * @param int|string $userIdOrTier User ID or tier name
     * @param string $feature Feature to check
     * @param mysqli $conn Database connection
     * @return bool Feature enabled
     */
    public static function hasFeature($userIdOrTier, $feature, $conn = null)
    {
        // Handle tier passed directly
        if (is_string($userIdOrTier)) {
            $tier = self::normalizeTier($userIdOrTier);
        } else {
            // Get tier from user ID
            $tier = self::getUserTier($userIdOrTier, $conn);
        }

        $value = self::getFeatureValue($tier, $feature);
        
        // Boolean features
        if (is_bool($value)) {
            return $value;
        }
        
        // Numeric features
        if (is_numeric($value)) {
            return $value > 0;
        }

        return false;
    }

    /**
     * Get all features for tier
     * 
     * @param string $tier Subscription tier
     * @return array Feature flags and limits
     */
    public static function getTierFeatures($tier)
    {
        $tier = self::normalizeTier($tier);
        return self::$tierFeatures[$tier] ?? self::$tierFeatures[self::TIER_FREE];
    }

    /**
     * Get pricing for tiers
     * 
     * @return array Pricing data
     */
    public static function getPricing()
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

    /**
     * Get trial period (days)
     * 
     * @return int Trial period in days
     */
    public static function getTrialDays()
    {
        return 14;
    }

    /**
     * Calculate trial expiration date
     * 
     * @return string Trial expiration date (Y-m-d H:i:s)
     */
    public static function getTrialExpiration()
    {
        $expirationTime = time() + (self::getTrialDays() * 86400);
        return date('Y-m-d H:i:s', $expirationTime);
    }

    /**
     * Audit log for tier changes
     * 
     * @param int $userId Customer ID
     * @param string $tierFrom Old tier
     * @param string $tierTo New tier
     * @param mysqli $conn Database connection
     */
    private static function logTierChange($userId, $tierFrom, $tierTo, $conn)
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

    /**
     * Get tier upgrade information
     * 
     * @param string $currentTier Current subscription tier
     * @return array Array of upgradeable tiers with info
     */
    public static function getUpgradeOptions($currentTier)
    {
        $upgrades = [];
        
        $currentTier = self::normalizeTier($currentTier);

        // Upgrade path: free -> registered -> silver -> gold -> platinum
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
                // No upgrades from platinum
                break;
        }

        $pricing = self::getPricing();
        $result = [];
        
        foreach ($upgrades as $tier) {
            $result[$tier] = $pricing[$tier];
        }

        return $result;
    }

    /**
     * Check if user is on trial
     * 
     * @param int $userId Customer ID
     * @param mysqli $conn Database connection
     * @return bool Is on trial period
     */
    public static function isOnTrial($userId, $conn = null)
    {
        if (!$conn) {
            global $mysqli;
            $conn = $mysqli;
        }

        $tier = self::getUserTier($userId, $conn);
        
        // Paid users might be on trial
        if (!in_array($tier, [self::TIER_SILVER, self::TIER_GOLD, self::TIER_PLATINUM], true)) {
            return false;
        }

        // Check if subscription date is within trial period
        $query = "SELECT created_at FROM " . DB::CUSTOMERS . " WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return false;
        }

        // If created less than trial days ago, on trial
        $trialDays = self::getTrialDays();
        $trialEndTime = strtotime($row['created_at']) + ($trialDays * 86400);
        
        return time() < $trialEndTime;
    }
}
