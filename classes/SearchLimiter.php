<?php
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
     * @param mysqli $conn Database connection (optional)
     * @return int Maximum results per search
     */
    public static function getResultLimit($userIdOrTier, $conn = null)
    {
        // Determine tier
        if (is_string($userIdOrTier)) {
            $resultLimit = SubscriptionTier::getFeatureValue($userIdOrTier, 'results_per_search');
        } else {
            $tier = SubscriptionTier::getUserTier($userIdOrTier, $conn);
            $resultLimit = SubscriptionTier::getFeatureValue($tier, 'results_per_search');
        }

        return $resultLimit ? (int)$resultLimit : 100;
    }

    /**
     * Enforce result limit on array of results
     * 
     * @param array $results Full result set
     * @param int|string $userIdOrTier User ID or tier name for limit
     * @param mysqli $conn Database connection (optional)
     * @return array Limited results
     */
    public static function enforceLimit(&$results, $userIdOrTier, $conn = null)
    {
        if (!is_array($results)) {
            return $results;
        }

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
     * @param mysqli $conn Database connection
     * @return array Pagination data including max pages
     */
    public static function getPaginationInfo($totalResults, $currentPage, $userIdOrTier, $conn = null)
    {
        $limit = self::getResultLimit($userIdOrTier, $conn);
        
        // Pro users can see all results in pages, others max 10 pages
        $maxPages = 10;
        if (is_string($userIdOrTier)) {
            $tier = $userIdOrTier;
        } else {
            $tier = SubscriptionTier::getUserTier($userIdOrTier, $conn);
        }

        // Paid tiers (Silver/Gold/Platinum): up to 20 pages
        // Others: max 10 pages
        if (in_array($tier, [SubscriptionTier::TIER_SILVER, SubscriptionTier::TIER_GOLD, SubscriptionTier::TIER_PLATINUM], true)) {
            $maxPages = 20;
        }

        // Calculate actual max pages
        $actualMaxPages = (int)ceil((min($limit * $maxPages, $totalResults)) / $limit);

        // Ensure current page is valid
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
     * @param mysqli $conn Database connection
     * @return bool Can view full details
     */
    public static function canViewCompanyDetails($userIdOrTier, $conn = null)
    {
        // Only registered+ can see full details
        if (is_string($userIdOrTier)) {
            $tier = $userIdOrTier;
        } else {
            $tier = SubscriptionTier::getUserTier($userIdOrTier, $conn);
        }

        return $tier !== SubscriptionTier::TIER_FREE;
    }

    /**
     * Get fields viewable by tier (for company detail)
     * 
     * @param int|string $userIdOrTier User ID or tier name
     * @param mysqli $conn Database connection
     * @return array Array of viewable field names
     */
    public static function getViewableFields($userIdOrTier, $conn = null)
    {
        // Determine tier
        if (is_string($userIdOrTier)) {
            $tier = $userIdOrTier;
        } else {
            $tier = SubscriptionTier::getUserTier($userIdOrTier, $conn);
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
                return ['*']; // All fields
            
            default:
                return $baseFields;
        }
    }

    /**
     * Filter company data by tier (hide sensitive fields)
     * 
     * @param array $company Company data
     * @param int|string $userIdOrTier User ID or tier name
     * @param mysqli $conn Database connection
     * @return array Filtered company data
     */
    public static function filterCompanyData($company, $userIdOrTier, $conn = null)
    {
        if (!is_array($company)) {
            return $company;
        }

        $viewableFields = self::getViewableFields($userIdOrTier, $conn);

        // If can see all fields, return as-is
        if (in_array('*', $viewableFields)) {
            return $company;
        }

        // Filter to viewable fields only
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
     * @param mysqli $conn Database connection
     * @param string $period Time period ('month', 'day', 'all')
     * @return int Maximum rows exportable
     */
    public static function getCsvExportLimit($userIdOrTier, $conn = null, $period = 'month')
    {
        if (is_string($userIdOrTier)) {
            $limit = SubscriptionTier::getFeatureValue($userIdOrTier, 'csv_export_rows_per_month');
        } else {
            $tier = SubscriptionTier::getUserTier($userIdOrTier, $conn);
            $limit = SubscriptionTier::getFeatureValue($tier, 'csv_export_rows_per_month');
        }

        return $limit ? (int)$limit : 0;
    }

    /**
     * Check if user can export results
     * 
     * @param int|string $userIdOrTier User ID or tier name
     * @param mysqli $conn Database connection
     * @return bool Can export
     */
    public static function canExport($userIdOrTier, $conn = null)
    {
        $limit = self::getCsvExportLimit($userIdOrTier, $conn);
        return $limit > 0;
    }

    /**
     * Track CSV export usage
     * 
     * @param int $userId User ID
     * @param int $rowsExported Number of rows exported
     * @param mysqli $conn Database connection
     * @return bool Success
     */
    public static function trackExport($userId, $rowsExported, $conn = null)
    {
        if (!$conn) {
            global $mysqli;
            $conn = $mysqli;
        }

        // erp_csv_exports table decommissioned; CSV export tracking disabled
        return true;
    }

    /**
     * Get CSV export usage for current month
     * 
     * @param int $userId User ID
     * @param mysqli $conn Database connection
     * @return int Total rows exported this month
     */
    public static function getMonthlyExportUsage($userId, $conn = null)
    {
        if (!$conn) {
            global $mysqli;
            $conn = $mysqli;
        }

        // erp_csv_exports table decommissioned; return 0 usage
        return 0;
    }

    /**
     * Check if user can export row count
     * 
     * @param int $userId User ID
     * @param int $rowCount Rows wanting to export
     * @param mysqli $conn Database connection
     * @return array ['allowed' => bool, 'remaining' => int, 'message' => string]
     */
    public static function canExportRows($userId, $rowCount, $conn = null)
    {
        $tier = SubscriptionTier::getUserTier($userId, $conn);
        $monthlyLimit = self::getCsvExportLimit($tier, null, 'month');
        
        // No export limit = unlimited
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
     * @param mysqli $conn Database connection
     * @return int Searches per hour
     */
    public static function getSearchRateLimit($userIdOrTier, $conn = null)
    {
        if (is_string($userIdOrTier)) {
            $tier = $userIdOrTier;
        } else {
            $tier = SubscriptionTier::getUserTier($userIdOrTier, $conn);
        }

        $limits = [
            SubscriptionTier::TIER_FREE => 20,        // searches per hour
            SubscriptionTier::TIER_REGISTERED => 100, // searches per hour
            SubscriptionTier::TIER_SILVER => 500,     // searches per hour
            SubscriptionTier::TIER_GOLD => 1500,      // searches per hour
            SubscriptionTier::TIER_PLATINUM => 5000,  // searches per hour
        ];

        return $limits[$tier] ?? 20;
    }

    /**
     * Generate upgrade CTA message
     * 
     * @param int $totalResults Total matching results
     * @param int $displayedResults Results shown to user
     * @param int|string $userIdOrTier User ID or tier
     * @param mysqli $conn Database connection
     * @return string HTML for upgrade message (or empty)
     */
    public static function getUpgradeMessage($totalResults, $displayedResults, $userIdOrTier, $conn = null)
    {
        if ($displayedResults >= $totalResults) {
            return ''; // All results shown
        }

        if (is_string($userIdOrTier)) {
            $tier = $userIdOrTier;
            $isLoggedIn = $tier !== SubscriptionTier::TIER_FREE;
        } else {
            $tier = SubscriptionTier::getUserTier($userIdOrTier, $conn);
            $isLoggedIn = isset($userIdOrTier) && $userIdOrTier > 0;
        }

        $hiddenCount = $totalResults - $displayedResults;
        $basePath = rtrim((string)($GLOBALS['basePath'] ?? ''), '/');
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
        } elseif ($tier === SubscriptionTier::TIER_REGISTERED) {
            return <<<HTML
            <div class="upgrade-cta alert alert-info">
                <h5>See All $totalResults Results</h5>
                <p>Showing $displayedResults of $totalResults results. Upgrade to Silver for up to 5,000 results.</p>
                <a href="$pricingUrl" class="btn btn-sm btn-primary">Upgrade to Silver</a>
            </div>
            HTML;
        } elseif ($tier === SubscriptionTier::TIER_SILVER) {
            return <<<HTML
            <div class="upgrade-cta alert alert-info">
                <h5>See More Results</h5>
                <p>Showing $displayedResults of $totalResults results. Upgrade to Gold for up to 25,000 results.</p>
                <a href="$pricingUrl" class="btn btn-sm btn-primary">Upgrade to Gold</a>
            </div>
            HTML;
        } elseif ($tier === SubscriptionTier::TIER_GOLD) {
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
