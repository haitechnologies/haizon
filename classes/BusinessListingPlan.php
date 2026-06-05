<?php
/**
 * BusinessListingPlan
 * 
 * Manages business listing subscription plans (Free/Silver/Gold/Platinum)
 * and per-company subscription lookups.
 * 
 * This is separate from SubscriptionTier which controls directory search
 * access. This class controls what features appear on a company's listing page.
 */

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/SubscriptionTier.php';

class BusinessListingPlan
{
    // Plan slug constants
    const FREE      = 'free';
    const SILVER    = 'silver';
    const GOLD      = 'gold';
    const PLATINUM  = 'platinum';

    // Tier ordering (higher = more features)
    const TIER_ORDER = [
        self::FREE     => 0,
        self::SILVER   => 1,
        self::GOLD     => 2,
        self::PLATINUM => 3,
    ];

    /**
     * Map listing plan slug to directory search tier.
     */
    public static function mapPlanSlugToSearchTier(string $planSlug): string
    {
        $planSlug = strtolower(trim($planSlug));

        switch ($planSlug) {
            case self::SILVER:
                return SubscriptionTier::TIER_SILVER;
            case self::GOLD:
                return SubscriptionTier::TIER_GOLD;
            case self::PLATINUM:
                return SubscriptionTier::TIER_PLATINUM;
            case self::FREE:
            default:
                return SubscriptionTier::TIER_REGISTERED;
        }
    }

    /**
     * Sync a customer's search tier from the highest active listing plan
     * across companies they own or created.
     */
    public static function syncUserSearchTier(mysqli $conn, int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $query = "
            SELECT p.plan_slug
            FROM `" . DB::LISTING_SUBSCRIPTIONS . "` s
            INNER JOIN `" . DB::LISTING_PLANS . "` p ON p.id = s.plan_id
            INNER JOIN `" . DB::COMPANIES . "` c ON c.id = s.company_id
            WHERE (c.owner_user_id = ? OR c.created_by = ?)
              AND s.status IN ('active','trial')
            ORDER BY FIELD(p.plan_slug, 'platinum', 'gold', 'silver', 'free')
            LIMIT 1
        ";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log('BusinessListingPlan::syncUserSearchTier() - Prepare error: ' . $conn->error);
            return false;
        }

        $stmt->bind_param('ii', $userId, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $planSlug = (string)($row['plan_slug'] ?? self::FREE);
        $targetTier = self::mapPlanSlugToSearchTier($planSlug);

        return SubscriptionTier::setUserTier($userId, $targetTier, $conn, null);
    }

    /**
     * Sync a customer's search tier using Stripe checkout session id.
     */
    public static function syncUserSearchTierBySessionId(mysqli $conn, string $stripeSessionId): bool
    {
        $stripeSessionId = trim($stripeSessionId);
        if ($stripeSessionId === '') {
            return false;
        }

        $query = "
            SELECT c.owner_user_id, c.created_by
            FROM `" . DB::LISTING_SUBSCRIPTIONS . "` s
            INNER JOIN `" . DB::COMPANIES . "` c ON c.id = s.company_id
            WHERE s.stripe_session_id = ?
            ORDER BY s.id DESC
            LIMIT 1
        ";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log('BusinessListingPlan::syncUserSearchTierBySessionId() - Prepare error: ' . $conn->error);
            return false;
        }

        $stmt->bind_param('s', $stripeSessionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $userId = (int)($row['owner_user_id'] ?? 0);
        if ($userId <= 0) {
            $userId = (int)($row['created_by'] ?? 0);
        }

        if ($userId <= 0) {
            return false;
        }

        return self::syncUserSearchTier($conn, $userId);
    }

    /**
     * Sync a customer's search tier using Stripe subscription id.
     */
    public static function syncUserSearchTierBySubscriptionId(mysqli $conn, string $stripeSubscriptionId): bool
    {
        $stripeSubscriptionId = trim($stripeSubscriptionId);
        if ($stripeSubscriptionId === '') {
            return false;
        }

        $query = "
            SELECT c.owner_user_id, c.created_by
            FROM `" . DB::LISTING_SUBSCRIPTIONS . "` s
            INNER JOIN `" . DB::COMPANIES . "` c ON c.id = s.company_id
            WHERE s.stripe_subscription_id = ?
            ORDER BY s.id DESC
            LIMIT 1
        ";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log('BusinessListingPlan::syncUserSearchTierBySubscriptionId() - Prepare error: ' . $conn->error);
            return false;
        }

        $stmt->bind_param('s', $stripeSubscriptionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $userId = (int)($row['owner_user_id'] ?? 0);
        if ($userId <= 0) {
            $userId = (int)($row['created_by'] ?? 0);
        }

        if ($userId <= 0) {
            return false;
        }

        return self::syncUserSearchTier($conn, $userId);
    }

    // -----------------------------------------------------------------
    // Plan catalog helpers
    // -----------------------------------------------------------------

    /**
     * Return all active plans ordered by sort_order.
     */
    public static function getAllPlans(mysqli $conn): array
    {
        $rows = [];
        $result = $conn->query(
            "SELECT * FROM `" . DB::LISTING_PLANS . "` WHERE is_active = 1 ORDER BY sort_order ASC"
        );
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[$row['plan_slug']] = $row;
            }
        }
        return $rows;
    }

    /**
     * Return a single plan by slug.
     */
    public static function getPlanBySlug(mysqli $conn, string $slug): ?array
    {
        $stmt = $conn->prepare(
            "SELECT * FROM `" . DB::LISTING_PLANS . "` WHERE plan_slug = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Return a plan by its Stripe price ID (monthly or annual).
     */
    public static function getPlanByStripePriceId(mysqli $conn, string $priceId): ?array
    {
        $stmt = $conn->prepare(
            "SELECT * FROM `" . DB::LISTING_PLANS . "`
             WHERE stripe_monthly_price_id = ? OR stripe_annual_price_id = ?
             LIMIT 1"
        );
        $stmt->bind_param('ss', $priceId, $priceId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    // -----------------------------------------------------------------
    // Company subscription helpers
    // -----------------------------------------------------------------

    /**
     * Return the active/trial subscription for a company (or null).
     */
    public static function getCompanySubscription(mysqli $conn, int $companyId): ?array
    {
        $stmt = $conn->prepare(
            "SELECT s.*, p.plan_slug, p.plan_name, p.tagline,
                    p.max_banners, p.max_pages, p.max_categories,
                    p.max_keywords, p.max_brands,
                    p.has_company_profile, p.has_social_media, p.has_whatsapp,
                    p.has_location_map, p.has_company_logo, p.has_website_link,
                    p.has_email, p.has_phone, p.has_years_in_business,
                    p.has_iso_accolades, p.has_green_badge, p.has_verified_mark,
                    p.has_video_banner, p.has_theme_custom, p.has_services_section,
                    p.has_priority_ranking,
                    p.stripe_monthly_price_id, p.stripe_annual_price_id
             FROM `" . DB::LISTING_SUBSCRIPTIONS . "` s
             INNER JOIN `" . DB::LISTING_PLANS . "` p ON p.id = s.plan_id
             WHERE s.company_id = ?
               AND s.status IN ('active','trial')
             ORDER BY s.subscription_end_at DESC
             LIMIT 1"
        );
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Return the effective plan slug for a company,
     * falling back to 'free' if no active subscription.
     */
    public static function getCompanyPlanSlug(mysqli $conn, int $companyId): string
    {
        $sub = self::getCompanySubscription($conn, $companyId);
        return $sub['plan_slug'] ?? self::FREE;
    }

    /**
     * Check whether a company's plan includes a specific boolean feature.
     * Safe column whitelist prevents SQL injection.
     */
    public static function hasFeature(mysqli $conn, int $companyId, string $feature): bool
    {
        static $allowedFeatures = [
            'has_company_profile', 'has_social_media', 'has_whatsapp',
            'has_location_map', 'has_company_logo', 'has_website_link',
            'has_email', 'has_phone', 'has_years_in_business',
            'has_iso_accolades', 'has_green_badge', 'has_verified_mark',
            'has_video_banner', 'has_theme_custom', 'has_services_section',
            'has_priority_ranking',
        ];

        if (!in_array($feature, $allowedFeatures, true)) {
            return false;
        }

        $sub = self::getCompanySubscription($conn, $companyId);
        if (!$sub) {
            // Free plan defaults
            return in_array($feature, ['has_company_profile', 'has_company_logo'], true);
        }
        return !empty($sub[$feature]);
    }

    /**
     * Return a numeric limit (e.g. max_banners) for a company's plan.
     */
    public static function getLimit(mysqli $conn, int $companyId, string $limitKey): int
    {
        static $allowedLimits = [
            'max_banners', 'max_pages', 'max_categories', 'max_keywords', 'max_brands',
        ];
        if (!in_array($limitKey, $allowedLimits, true)) {
            return 0;
        }
        $sub = self::getCompanySubscription($conn, $companyId);
        return $sub ? (int)($sub[$limitKey] ?? 0) : 0;
    }

    /**
     * Return current usage counts for listing-plan constrained resources.
     */
    public static function getCompanyUsage(mysqli $conn, int $companyId): array
    {
        $companyId = (int)$companyId;
        if ($companyId <= 0) {
            return [
                'banners' => 0,
                'brands' => 0,
                'keywords' => 0,
                'pages' => 0,
            ];
        }

        $counts = [
            'banners' => 0,
            'brands' => 0,
            'keywords' => 0,
            'pages' => 0,
        ];

        $countTable = function (string $tableName, string $columnName = 'company_id') use ($conn, $companyId): int {
            $sql = "SELECT COUNT(*) AS total FROM `{$tableName}` WHERE `{$columnName}` = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return 0;
            }

            $stmt->bind_param('i', $companyId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return (int)($row['total'] ?? 0);
        };

        $counts['banners']  = 0; // erp_listing_banners decommissioned
        $counts['brands']   = 0; // erp_listing_brands decommissioned
        $counts['keywords'] = 0; // erp_listing_keywords decommissioned
        $counts['pages'] = $countTable(DB::PAGES, 'company_id');

        return $counts;
    }

    /**
     * Determine whether a company can add another resource under plan limits.
     */
    public static function canAddResource(mysqli $conn, int $companyId, string $resource): array
    {
        $resource = strtolower(trim($resource));

        $resourceMap = [
            'banner' => ['usage_key' => 'banners', 'limit_key' => 'max_banners'],
            'banners' => ['usage_key' => 'banners', 'limit_key' => 'max_banners'],
            'brand' => ['usage_key' => 'brands', 'limit_key' => 'max_brands'],
            'brands' => ['usage_key' => 'brands', 'limit_key' => 'max_brands'],
            'keyword' => ['usage_key' => 'keywords', 'limit_key' => 'max_keywords'],
            'keywords' => ['usage_key' => 'keywords', 'limit_key' => 'max_keywords'],
            'page' => ['usage_key' => 'pages', 'limit_key' => 'max_pages'],
            'pages' => ['usage_key' => 'pages', 'limit_key' => 'max_pages'],
        ];

        if (!isset($resourceMap[$resource])) {
            return [
                'allowed' => false,
                'limit' => 0,
                'used' => 0,
                'remaining' => 0,
                'message' => 'Unknown resource type.',
            ];
        }

        $usage = self::getCompanyUsage($conn, $companyId);
        $usageKey = $resourceMap[$resource]['usage_key'];
        $limitKey = $resourceMap[$resource]['limit_key'];

        $limit = max(0, self::getLimit($conn, $companyId, $limitKey));
        $used = max(0, (int)($usage[$usageKey] ?? 0));
        $remaining = max(0, $limit - $used);
        $allowed = $used < $limit;

        return [
            'allowed' => $allowed,
            'limit' => $limit,
            'used' => $used,
            'remaining' => $remaining,
            'message' => $allowed
                ? 'Allowed.'
                : 'Plan limit reached. Please upgrade your plan to add more resources.',
        ];
    }

    // -----------------------------------------------------------------
    // Subscription write helpers
    // -----------------------------------------------------------------

    /**
     * Create (or replace) a pending subscription record after initiating
     * a Stripe Checkout Session. The status is set to 'pending' until
     * the webhook confirms payment.
     */
    public static function createPendingSubscription(
        mysqli $conn,
        int    $companyId,
        int    $planId,
        string $billingCycle,
        string $stripeSessionId,
        float  $amount
    ): int {
        // Cancel any existing pending record for this company
        $stmt = $conn->prepare(
            "UPDATE `" . DB::LISTING_SUBSCRIPTIONS . "`
             SET status = 'cancelled', updated_at = NOW()
             WHERE company_id = ? AND status = 'pending'"
        );
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "INSERT INTO `" . DB::LISTING_SUBSCRIPTIONS . "`
             (company_id, plan_id, billing_cycle, status, stripe_session_id, amount_paid, currency)
             VALUES (?, ?, ?, 'pending', ?, ?, 'AED')"
        );
        $stmt->bind_param('iissd', $companyId, $planId, $billingCycle, $stripeSessionId, $amount);
        $stmt->execute();
        $id = (int)$conn->insert_id;
        $stmt->close();
        return $id;
    }

    /**
     * Activate a subscription after successful Stripe payment (webhook).
     */
    public static function activateSubscription(
        mysqli $conn,
        string $stripeSessionId,
        string $stripeCustomerId,
        string $stripeSubscriptionId,
        bool   $isAnnual,
        bool   $hasTrial,
        int    $trialDays
    ): bool {
        $now       = date('Y-m-d H:i:s');
        $trialEnd  = $hasTrial ? date('Y-m-d H:i:s', strtotime("+{$trialDays} days")) : null;
        $subEnd    = $isAnnual  ? date('Y-m-d H:i:s', strtotime('+1 year'))  : date('Y-m-d H:i:s', strtotime('+1 month'));
        $nextBill  = $subEnd;
        $status    = $hasTrial ? 'trial' : 'active';

        $stmt = $conn->prepare(
            "UPDATE `" . DB::LISTING_SUBSCRIPTIONS . "`
             SET status               = ?,
                 trial_ends_at        = ?,
                 subscription_start_at = ?,
                 subscription_end_at  = ?,
                 next_billing_at      = ?,
                 payment_status       = 'paid',
                 stripe_customer_id   = ?,
                 stripe_subscription_id = ?,
                 updated_at           = NOW()
             WHERE stripe_session_id = ? AND status = 'pending'
             LIMIT 1"
        );
        $stmt->bind_param(
            'ssssssss',
            $status, $trialEnd, $now, $subEnd, $nextBill,
            $stripeCustomerId, $stripeSubscriptionId, $stripeSessionId
        );
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }

    // -----------------------------------------------------------------
    // Formatting helpers
    // -----------------------------------------------------------------

    /**
     * Format an AED price for display, e.g. "AED 600" or "AED 50/mo".
     */
    public static function formatPrice(float $amount, string $suffix = ''): string
    {
        $formatted = 'AED ' . number_format($amount, 0);
        return $suffix ? $formatted . $suffix : $formatted;
    }

    /**
     * Calculate annual saving vs. paying monthly for 12 months.
     */
    public static function annualSaving(array $plan): float
    {
        return ($plan['monthly_price_orig'] * 12) - (float)$plan['annual_price'];
    }

    /**
     * Return the colour class for a plan badge.
     */
    public static function planBadgeClass(string $slug): string
    {
        return match($slug) {
            'platinum' => 'badge-platinum',
            'gold'     => 'badge-gold',
            'silver'   => 'badge-silver',
            default    => 'badge-free',
        };
    }
}
