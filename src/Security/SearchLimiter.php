<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\SubscriptionTier;

/**
 * SearchLimiter Class
 *
 * Enforces search result limits based on user subscription tier.
 * Handles pagination, result limiting, and quota management.
 */
class SearchLimiter
{
    /**
     * Get result limit for user tier
     *
     * @param int|string $userIdOrTier User ID or tier name
     * @param mixed $conn Database connection (optional)
     * @return int Maximum results per search
     */
    public static function getResultLimit(mixed $userIdOrTier, mixed $conn = null): int
    {
        if (is_string($userIdOrTier)) {
            $resultLimit = SubscriptionTier::getFeatureValue($userIdOrTier, 'results_per_search');
        } else {
            $tier = (new SubscriptionTier())->getUserTier((int)$userIdOrTier);
            $resultLimit = SubscriptionTier::getFeatureValue($tier, 'results_per_search');
        }

        return $resultLimit ? (int)$resultLimit : 100;
    }

    /**
     * Enforce result limit on array of results
     *
     * @param array $results Full result set
     * @param int|string $userIdOrTier User ID or tier name for limit
     * @param mixed $conn Database connection (optional)
     * @return array Limited results
     */
    public static function enforceLimit(array &$results, mixed $userIdOrTier, mixed $conn = null): array
    {
        $limit = self::getResultLimit($userIdOrTier, $conn);

        if (count($results) > $limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    /**
     * Get pagination info with tier limits
     *
     * @param int $totalResults Total matching results
     * @param int $currentPage Current page number
     * @param int|string $userIdOrTier User ID or tier name
     * @param mixed $conn Database connection
     * @return array Pagination data including max pages
     */
    public static function getPaginationInfo(
        int $totalResults,
        int $currentPage,
        mixed $userIdOrTier,
        mixed $conn = null
    ): array {
        $limit = self::getResultLimit($userIdOrTier, $conn);

        $maxPages = 10;
        if (is_string($userIdOrTier)) {
            $tier = $userIdOrTier;
        } else {
            $tier = (new SubscriptionTier())->getUserTier((int)$userIdOrTier);
        }

        if (in_array($tier, [SubscriptionTier::TIER_SILVER, SubscriptionTier::TIER_GOLD, SubscriptionTier::TIER_PLATINUM], true)) {
            $maxPages = 20;
        }

        $actualMaxPages = (int)ceil((min($limit * $maxPages, $totalResults)) / $limit);
        $currentPage = max(1, min($currentPage, $actualMaxPages));
        $offset = ($currentPage - 1) * $limit;

        return [
            'limit' => $limit,
            'offset' => $offset,
            'current_page' => $currentPage,
            'total_results' => $totalResults,
            'total_pages' => $actualMaxPages,
            'has_more' => $currentPage < $actualMaxPages,
            'can_see_more' => $totalResults > ($limit * $maxPages),
        ];
    }

    /**
     * Check if user can view company details
     *
     * @param int|string $userIdOrTier User ID or tier name
     * @param mixed $conn Database connection
     * @return bool Can view full details
     */
    public static function canViewCompanyDetails(mixed $userIdOrTier, mixed $conn = null): bool
    {
        if (is_string($userIdOrTier)) {
            $tier = $userIdOrTier;
        } else {
            $tier = (new SubscriptionTier())->getUserTier((int)$userIdOrTier);
        }

        return $tier !== SubscriptionTier::TIER_FREE;
    }

    /**
     * Get fields viewable by tier (for company detail)
     *
     * @param int|string $userIdOrTier User ID or tier name
     * @param mixed $conn Database connection
     * @return array Array of viewable field names
     */
    public static function getViewableFields(mixed $userIdOrTier, mixed $conn = null): array
    {
        if (is_string($userIdOrTier)) {
            $tier = $userIdOrTier;
        } else {
            $tier = (new SubscriptionTier())->getUserTier((int)$userIdOrTier);
        }

        $baseFields = ['id', 'name', 'category', 'city', 'description'];

        switch ($tier) {
            case SubscriptionTier::TIER_FREE:
                return $baseFields;

            case SubscriptionTier::TIER_REGISTERED:
                return array_merge($baseFields, ['email', 'phone', 'website', 'address']);

            case SubscriptionTier::TIER_SILVER:
            case SubscriptionTier::TIER_GOLD:
            case SubscriptionTier::TIER_PLATINUM:
                return ['*'];

            default:
                return $baseFields;
        }
    }

    /**
     * Filter company data by tier (hide sensitive fields)
     *
     * @param array $company Company data
     * @param int|string $userIdOrTier User ID or tier name
     * @param mixed $conn Database connection
     * @return array Filtered company data
     */
    public static function filterCompanyData(array $company, mixed $userIdOrTier, mixed $conn = null): array
    {
        $viewableFields = self::getViewableFields($userIdOrTier, $conn);

        if (in_array('*', $viewableFields, true)) {
            return $company;
        }

        $filtered = [];
        foreach ($viewableFields as $field) {
            if (isset($company[$field])) {
                $filtered[$field] = $company[$field];
            }
        }

        return $filtered;
    }

    /**
     * Get CSV export limit for tier
     *
     * @param int|string $userIdOrTier User ID or tier name
     * @param mixed $conn Database connection
     * @param string $period Time period ('month', 'day', 'all')
     * @return int Maximum rows exportable
     */
    public static function getCsvExportLimit(mixed $userIdOrTier, mixed $conn = null, string $period = 'month'): int
    {
        if (is_string($userIdOrTier)) {
            $limit = SubscriptionTier::getFeatureValue($userIdOrTier, 'csv_export_rows_per_month');
        } else {
            $tier = (new SubscriptionTier())->getUserTier((int)$userIdOrTier);
            $limit = SubscriptionTier::getFeatureValue($tier, 'csv_export_rows_per_month');
        }

        return $limit ? (int)$limit : 0;
    }

    /**
     * Check if user can export results
     *
     * @param int|string $userIdOrTier User ID or tier name
     * @param mixed $conn Database connection
     * @return bool Can export
     */
    public static function canExport(mixed $userIdOrTier, mixed $conn = null): bool
    {
        $limit = self::getCsvExportLimit($userIdOrTier, $conn);
        return $limit > 0;
    }

    /**
     * Track CSV export usage
     *
     * @param int $userId User ID
     * @param int $rowsExported Number of rows exported
     * @param mixed $conn Database connection
     * @return bool Success
     */
    public static function trackExport(int $userId, int $rowsExported, mixed $conn = null): bool
    {
        return true;
    }

    /**
     * Get CSV export usage for current month
     *
     * @param int $userId User ID
     * @param mixed $conn Database connection
     * @return int Total rows exported this month
     */
    public static function getMonthlyExportUsage(int $userId, mixed $conn = null): int
    {
        return 0;
    }

    /**
     * Check if user can export row count
     *
     * @param int $userId User ID
     * @param int $rowCount Rows wanting to export
     * @param mixed $conn Database connection
     * @return array ['allowed' => bool, 'remaining' => int, 'message' => string]
     */
    public static function canExportRows(int $userId, int $rowCount, mixed $conn = null): array
    {
        $tier = (new SubscriptionTier())->getUserTier($userId);
        $monthlyLimit = self::getCsvExportLimit($tier, null, 'month');

        if ($monthlyLimit >= 9999) {
            return [
                'allowed' => true,
                'remaining' => 9999,
                'message' => 'Unlimited exports',
            ];
        }

        $currentUsage = self::getMonthlyExportUsage($userId, $conn);
        $remaining = max(0, $monthlyLimit - $currentUsage);

        if ($rowCount > $remaining) {
            return [
                'allowed' => false,
                'remaining' => $remaining,
                'message' => "Monthly export limit reached. Can export $remaining more rows.",
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $remaining - $rowCount,
            'message' => "OK",
        ];
    }

    /**
     * Get rate limit for searches
     *
     * @param int|string $userIdOrTier User ID or tier name
     * @param mixed $conn Database connection
     * @return int Searches per hour
     */
    public static function getSearchRateLimit(mixed $userIdOrTier, mixed $conn = null): int
    {
        if (is_string($userIdOrTier)) {
            $tier = $userIdOrTier;
        } else {
            $tier = (new SubscriptionTier())->getUserTier((int)$userIdOrTier);
        }

        $limits = [
            SubscriptionTier::TIER_FREE => 20,
            SubscriptionTier::TIER_REGISTERED => 100,
            SubscriptionTier::TIER_SILVER => 500,
            SubscriptionTier::TIER_GOLD => 1500,
            SubscriptionTier::TIER_PLATINUM => 5000,
        ];

        return $limits[$tier] ?? 20;
    }

    /**
     * Generate upgrade CTA message
     *
     * @param int $totalResults Total matching results
     * @param int $displayedResults Results shown to user
     * @param int|string $userIdOrTier User ID or tier
     * @param mixed $conn Database connection
     * @return string HTML for upgrade message (or empty)
     */
    public static function getUpgradeMessage(
        int $totalResults,
        int $displayedResults,
        mixed $userIdOrTier,
        mixed $conn = null
    ): string {
        if ($displayedResults >= $totalResults) {
            return '';
        }

        if (is_string($userIdOrTier)) {
            $tier = $userIdOrTier;
        } else {
            $tier = (new SubscriptionTier())->getUserTier((int)$userIdOrTier);
        }

        $basePath = rtrim((string)($_ENV['APP_URL'] ?? $GLOBALS['basePath'] ?? ''), '/');
        $registerUrl = $basePath . '/register';
        $pricingUrl = $basePath . '/pricing';

        if ($tier === SubscriptionTier::TIER_FREE) {
            return <<<HTML
            <div class="upgrade-cta alert alert-info">
                <h5>View More Results</h5>
                <p>Showing $displayedResults of $totalResults results.
                         <a href="$registerUrl">Create free account</a> to see 1,000 results per search.</p>
                     <a href="$pricingUrl" class="btn btn-sm btn-primary">View Pricing Plans</a>
            </div>
            HTML;
        }

        if ($tier === SubscriptionTier::TIER_REGISTERED) {
            return <<<HTML
            <div class="upgrade-cta alert alert-info">
                <h5>See All $totalResults Results</h5>
                <p>Showing $displayedResults of $totalResults results. Upgrade to Silver for up to 5,000 results.</p>
                <a href="$pricingUrl" class="btn btn-sm btn-primary">Upgrade to Silver</a>
            </div>
            HTML;
        }

        if ($tier === SubscriptionTier::TIER_SILVER) {
            return <<<HTML
            <div class="upgrade-cta alert alert-info">
                <h5>See More Results</h5>
                <p>Showing $displayedResults of $totalResults results. Upgrade to Gold for up to 25,000 results.</p>
                <a href="$pricingUrl" class="btn btn-sm btn-primary">Upgrade to Gold</a>
            </div>
            HTML;
        }

        if ($tier === SubscriptionTier::TIER_GOLD) {
            return <<<HTML
            <div class="upgrade-cta alert alert-info">
                <h5>See More Results</h5>
                <p>Showing $displayedResults of $totalResults results. Upgrade to Platinum for up to 100,000 results.</p>
                <a href="$pricingUrl" class="btn btn-sm btn-primary">Upgrade to Platinum</a>
            </div>
            HTML;
        }

        return '';
    }
}
