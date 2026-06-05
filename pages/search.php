<?php
/**
 * Search Results Page
 * Displays search results for public users without trending-search fallback
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/IpRateLimiter.php';
require_once __DIR__ . '/../classes/SubscriptionTier.php';
require_once __DIR__ . '/../classes/SearchLimiter.php';
require_once __DIR__ . '/../classes/frontend/Companies.php';
require_once __DIR__ . '/../classes/frontend/Searches.php';
require_once __DIR__ . '/../includes/helpers.php';

// Basic anti-scraping throttle for public search endpoint.
IpRateLimiter::init($conn);
$rateLimit = IpRateLimiter::check('search_page', 120, 60);
if (empty($rateLimit['allowed'])) {
    http_response_code(429);
    header('Retry-After: 60');
    exit('Too many search requests. Please try again in a minute.');
}

$basePath = $GLOBALS['basePath'] ?? '';

// Get user tier for result limiting
$userId = isset($_SESSION['frontend_user_id']) ? (int)$_SESSION['frontend_user_id'] : 0;
$userTier = SubscriptionTier::getUserTier($userId, $conn);
$resultLimit = SearchLimiter::getResultLimit($userTier);

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$source = isset($_GET['source']) ? strtolower(trim((string)$_GET['source'])) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$manualSearchFlag = (string)($_GET['manual_search'] ?? '') === '1';
$limit = max(1, (int)$resultLimit);  // Tier-based max visible results
$offset = ($page - 1) * $limit;
$searchError = '';

$results = [];
$totalResults = 0;
$pageTitle = 'Search Results - UAE Business Directory';
$pageDescription = 'Search results for businesses in UAE';

if (!empty($searchQuery)) {
    if (!isMeaningfulSearchTerm($searchQuery)) {
        $searchError = 'Please enter at least 2 meaningful characters to search.';
    } else {
        // Search companies first to get result count
        $companiesModel = new Companies($conn);
        $totalResults = $companiesModel->searchCount($searchQuery);

        // Log only explicit manual submissions on page 1.
        if ($manualSearchFlag && $page === 1 && isset($_GET['q'])) {
            $searches = new Searches($conn);
            $searches->recordSearch(
                $searchQuery,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $userId > 0 ? $userId : null,
                $totalResults,
                $userId > 0 ? 'manual' : 'guest'
            );
        }

        // Enforce membership visibility cap on result set and pagination.
        $visibleTotalResults = min($totalResults, $limit);
        $totalPages = max(1, (int)ceil($visibleTotalResults / $limit));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $limit;

        $results = $companiesModel->search($searchQuery, ['limit' => $limit, 'offset' => $offset]);

        $pageTitle = htmlspecialchars("Results for '{$searchQuery}' - UAE Business Directory");
        $pageDescription = "Found " . number_format($visibleTotalResults) . " businesses matching '{$searchQuery}'";
    }
}

if (!isset($visibleTotalResults)) {
    $visibleTotalResults = min($totalResults, $limit);
}

// Generate JSON-LD structured data for rich results
if (!empty($results) && !empty($searchQuery)) {
    // ItemList schema for search results
    $jsonLdSchema = generateItemListSchema(
        $results,
        'Search Results for "' . htmlspecialchars($searchQuery, ENT_QUOTES) . '"',
        'Found ' . number_format($totalResults) . ' businesses matching your search'
    );
    
    // Add breadcrumb schema
    $breadcrumbs = [
        ['name' => 'Home', 'url' => getFullUrl('/')],
        ['name' => 'Search', 'url' => getFullUrl('/search')],
        ['name' => 'Results', 'url' => getFullUrl('/search?q=' . urlencode($searchQuery))]
    ];
    $jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);
}
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

<main id="main-content">
    <section class="section">
        <div class="container-narrow">
            <!-- Search Panel -->
            <div class="search-panel search-page-panel">
                <form method="get" action="<?php echo htmlspecialchars(url('/search'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="manual_search" value="1">
                    <div class="search-grid">
                        <input 
                            class="field" 
                            name="q" 
                            type="text" 
                            placeholder="Search businesses, services, or locations..." 
                            value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>"
                            minlength="2"
                            title="Enter at least 2 meaningful characters"
                            aria-label="Search"
                            autofocus>
                        <button class="btn-ui btn-primary-ui" type="submit">Search</button>
                    </div>
                </form>
            </div>

            <?php if (!empty($searchQuery)): ?>
                <?php if ($searchError !== ''): ?>
                    <div class="notice search-warning" aria-live="polite">
                        <?php echo htmlspecialchars($searchError, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                <!-- Results Section -->
                <div class="section-head">
                    <h1>Search results for "<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>"</h1>
                    <p class="muted">Found <?php echo number_format($visibleTotalResults); ?> result<?php echo $visibleTotalResults !== 1 ? 's' : ''; ?></p>
                </div>

                <?php if (!empty($results)): ?>
                    <div class="grid-3 search-results-grid">
                        <?php foreach ($results as $company): ?>
                            <article class="card-ui business-card">
                                <div class="business-top">
                                    <?php if (!empty($company['verified'])): ?>
                                        <span class="pill">Verified</span>
                                    <?php endif; ?>
                                </div>
                                <?php $searchCompanyName = display_text($company['display_name'] ?? $company['company_name'] ?? 'Business'); ?>
                                <h3>
                                    <a href="<?php echo htmlspecialchars(url('/company/' . ($company['slug'] ?? $company['id'])), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($searchCompanyName, ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </h3>
                                <p class="muted">
                                    <?php 
                                    $details = [];
                                    if (!empty($company['category_name'])) $details[] = htmlspecialchars($company['category_name'], ENT_QUOTES, 'UTF-8');
                                    if (!empty($company['emirate'])) $details[] = htmlspecialchars($company['emirate'], ENT_QUOTES, 'UTF-8');
                                    echo implode(' • ', $details);
                                    ?>
                                </p>
                                <?php if (!empty($company['short_description'])): ?>
                                    <p><?php echo htmlspecialchars(truncateText($company['short_description'], 100), ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars(url('/company/' . ($company['slug'] ?? $company['id'])), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui">
                                    View Profile
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Upgrade CTA (if free user and more results available) -->
                    <?php if ($visibleTotalResults < $totalResults): ?>
                        <?php
                        $nextTierLabel = 'Platinum';
                        $nextTierLimit = 100000;
                        if ($userTier === SubscriptionTier::TIER_FREE) {
                            $nextTierLabel = 'a registered account';
                            $nextTierLimit = 1000;
                        } elseif ($userTier === SubscriptionTier::TIER_REGISTERED) {
                            $nextTierLabel = 'Silver';
                            $nextTierLimit = 5000;
                        } elseif ($userTier === SubscriptionTier::TIER_SILVER) {
                            $nextTierLabel = 'Gold';
                            $nextTierLimit = 25000;
                        } elseif ($userTier === SubscriptionTier::TIER_GOLD) {
                            $nextTierLabel = 'Platinum';
                            $nextTierLimit = 100000;
                        }
                        ?>
                        <div class="listings-upgrade-cta">
                            <h4>See All <?php echo number_format($totalResults); ?> Results</h4>
                            <p class="listings-upgrade-copy">You're viewing limited results (<?php echo number_format($visibleTotalResults); ?>/search). 
                            <?php if ($userTier === SubscriptionTier::TIER_FREE): ?>
                                <a href="<?php echo url('/register'); ?>" class="listings-upgrade-link">Create a free account</a> 
                                to see up to <?php echo number_format($nextTierLimit); ?> results, or
                            <?php endif; ?>
                            <a href="<?php echo url('/pricing'); ?>" class="listings-upgrade-link-strong">upgrade to <?php echo htmlspecialchars($nextTierLabel, ENT_QUOTES, 'UTF-8'); ?></a> 
                            for up to <?php echo number_format($nextTierLimit); ?> results.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Pagination -->
                    <?php 
                    // Calculate actual max pages based on tier limits
                    $actualMaxPages = $totalPages;
                    ?>
                    <?php if ($actualMaxPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo htmlspecialchars(url('/search?q=' . urlencode($searchQuery) . '&page=' . ($page - 1)), ENT_QUOTES, 'UTF-8'); ?>" class="btn-pagination">← Prev</a>
                            <?php endif; ?>
                            
                            <span class="pagination-info">Page <?php echo $page; ?> of <?php echo $actualMaxPages; ?></span>
                            
                            <?php if ($page < $actualMaxPages): ?>
                                <a href="<?php echo htmlspecialchars(url('/search?q=' . urlencode($searchQuery) . '&page=' . ($page + 1)), ENT_QUOTES, 'UTF-8'); ?>" class="btn-pagination">Next →</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state search-empty">
                        <i class="fas fa-search search-empty-icon"></i>
                        <h2>No results found</h2>
                        <p class="muted">Try searching with different keywords or browse by category.</p>
                        <a href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui search-empty-cta">Browse All Businesses</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- No search query fallback -->
                <h2 class="search-prompt-title">What are you looking for?</h2>
                <p class="muted search-prompt-subtitle">Enter a search term to find matching businesses.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
