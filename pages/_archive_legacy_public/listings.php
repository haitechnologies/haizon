<?php
/**
 * Page: Business Listings (NEW DESIGN)
 * Route: /listings
 * Description: Search and browse businesses with filters
 * Author: Development Team
 * Created: February 28, 2026
 */

// ============================================
// SECTION 1: DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/IpRateLimiter.php';
require_once __DIR__ . '/../classes/SubscriptionTier.php';
require_once __DIR__ . '/../classes/SearchLimiter.php';
require_once __DIR__ . '/../classes/frontend/CompanyCategories.php';
require_once __DIR__ . '/../classes/frontend/PublicAds.php';
require_once __DIR__ . '/../classes/frontend/Searches.php';
require_once __DIR__ . '/../includes/helpers.php';

// Basic anti-scraping throttle for public listings page.
IpRateLimiter::init($conn);
$rateLimit = IpRateLimiter::check('listings_page', 240, 60);
if (empty($rateLimit['allowed'])) {
  http_response_code(429);
  header('Retry-After: 60');
  exit('Too many requests. Please try again in a minute.');
}

// ============================================
// SECTION 2: GET FILTER PARAMETERS & TIER LIMITS
// ============================================
// Get user tier for result limiting
$userId = isset($_SESSION['frontend_user_id']) ? (int)$_SESSION['frontend_user_id'] : 0;
$userTier = SubscriptionTier::getUserTier($userId, $conn);
$resultLimit = SearchLimiter::getResultLimit($userTier);


$keyword = trim((string)($_GET['keyword'] ?? $_GET['q'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$categoryId = (int)($_GET['category_id'] ?? 0);
$emirate = trim((string)($_GET['emirate'] ?? ''));
$city = trim((string)($_GET['city'] ?? ''));
$companyNameStartsWith = strtoupper(trim((string)($_GET['company_name_starts_with'] ?? '')));
$categoryStartsWith = strtoupper(trim((string)($_GET['category_starts_with'] ?? '')));
$sortBy = $_GET['sort_by'] ?? 'recommended';
$page = max(1, intval($_GET['page'] ?? 1));
$manualSearchFlag = (string)($_GET['manual_search'] ?? '') === '1';
$keywordError = '';

// --- SEARCH LOGGING ---
$shouldLogSearch = $manualSearchFlag
  && $page === 1
  && isset($_GET['keyword'])
  && $keyword !== ''
  && isMeaningfulSearchTerm($keyword);

if ($shouldLogSearch) {
  $searchesModel = new Searches($conn);
  $ip = $_SERVER['REMOTE_ADDR'] ?? null;
  $userIdForLog = $userId > 0 ? $userId : null;
  // Compose a search query string for analytics
  $searchQueryParts = [];
  if ($keyword !== '') $searchQueryParts[] = $keyword;
  if ($category !== '') $searchQueryParts[] = 'cat:' . $category;
  if ($categoryId > 0) $searchQueryParts[] = 'catid:' . $categoryId;
  if ($emirate !== '') $searchQueryParts[] = 'city:' . $emirate;
  if ($companyNameStartsWith !== '') $searchQueryParts[] = 'company_starts:' . $companyNameStartsWith;
  if ($categoryStartsWith !== '') $searchQueryParts[] = 'cat_starts:' . $categoryStartsWith;
  $searchQueryString = implode(' | ', $searchQueryParts);

  // Result count will be available after companies are loaded, so log after query below
}

if (!preg_match('/^[A-Z0-9]$/', $companyNameStartsWith)) {
  $companyNameStartsWith = '';
}
if (!preg_match('/^[A-Z0-9]$/', $categoryStartsWith)) {
  $categoryStartsWith = '';
}

// Backward-compatible support for homepage links: /listings?city=Dubai
if ($city !== '' && $emirate === '') {
  $emirate = $city;
}

// Normalize location parameter for filter UI and canonical matching.
if ($emirate !== '') {
  $emirate = strtolower($emirate);
  $emirate = str_replace(['_', ' '], '-', $emirate);
  $emirate = preg_replace('/-+/', '-', $emirate);
  $emirate = trim($emirate, '-');
}

// Use centralized InputValidator instead of local function definition
if ($keyword !== '' && !isMeaningfulSearchTerm($keyword)) {
  $keywordError = 'Please enter at least 2 meaningful characters to search.';
  $keyword = '';
}
// Keep listings pagination consistent at 25 results per page (subject to tier cap).
$tierVisibleLimit = max(1, (int)$resultLimit);
$perPage = min(25, $tierVisibleLimit);
$offset = ($page - 1) * $perPage;

// Micro-timing: checkpoints recorded here and emitted as X-Timing-* response headers.
$_lt = ['start' => microtime(true)];

// Cache repeated listings page filter requests for a short period.
$listingsCacheTtl = 180;
$listingsCacheFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'haipulse_listings_v1_' . md5(json_encode([
  'keyword' => $keyword,
  'category' => $category,
  'category_id' => (int)$categoryId,
  'emirate' => $emirate,
  'company_starts' => $companyNameStartsWith,
  'category_starts' => $categoryStartsWith,
  'sort_by' => $sortBy,
  'page' => (int)$page,
  'per_page' => (int)$perPage,
  'tier' => (string)$userTier,
  'tier_limit' => (int)$tierVisibleLimit,
])) . '.json';

$cachedListingsPayload = null;
if (is_file($listingsCacheFile) && (time() - (int)@filemtime($listingsCacheFile) < $listingsCacheTtl)) {
  $cachedJson = @file_get_contents($listingsCacheFile);
  if ($cachedJson !== false) {
    $decoded = json_decode($cachedJson, true);
    if (is_array($decoded)) {
      $cachedListingsPayload = $decoded;
    }
  }
}
$_lt['cache_check'] = microtime(true);

// ============================================
// SECTION 3: BUILD QUERY
// ============================================
// Get all categories first (for filter dropdown + slug->id map)
$categories = $cachedListingsPayload['categories'] ?? null;
$categoryBySlug = $cachedListingsPayload['categoryBySlug'] ?? null;
$categoryNameById = $cachedListingsPayload['categoryNameById'] ?? null;

if (!is_array($categories) || !is_array($categoryBySlug) || !is_array($categoryNameById)) {
  $categoriesModel = new CompanyCategories($conn);
  $categories = $categoriesModel->getAll(['order_by' => 'name ASC']);
  $categoryBySlug = [];
  $categoryNameById = [];
  foreach ($categories as &$cat) {
    if (!isset($cat['name']) && isset($cat['category'])) {
      $cat['name'] = $cat['category'];
    }
    $cat['count'] = (int)($cat['total_companies'] ?? 0);
    if (!empty($cat['slug'])) {
      $categoryBySlug[$cat['slug']] = (int)$cat['id'];
    }
    $categoryNameById[(int)$cat['id']] = $cat['name'] ?? 'Business';
  }
  unset($cat);
}
$_lt['cats_done'] = microtime(true);

$whereClauses = ['comp.publish = 1', 'comp.is_active = 1'];
$params = [];
$types = '';

if (!empty($keyword)) {
  $whereClauses[] = "(comp.company_name LIKE ? OR comp.location LIKE ? OR comp.city LIKE ? OR comp.services LIKE ? OR comp.company_profile LIKE ?)";
  $searchTerm = '%' . $keyword . '%';
  $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
  $types .= 'sssss';
}

if ($companyNameStartsWith !== '') {
  $whereClauses[] = "comp.company_name LIKE ?";
  $params[] = $companyNameStartsWith . '%';
  $types .= 's';
}

if (!empty($category) && isset($categoryBySlug[$category])) {
  $whereClauses[] = "comp.primary_category_id = ?";
  $params[] = $categoryBySlug[$category];
  $types .= 'i';
} elseif ($categoryId > 0) {
  $whereClauses[] = "comp.primary_category_id = ?";
  $params[] = $categoryId;
  $types .= 'i';
}

if ($categoryStartsWith !== '') {
  $matchingCategoryIds = [];
  foreach ($categories as $cat) {
    $catName = strtoupper(trim((string)($cat['name'] ?? '')));
    if ($catName !== '' && strpos($catName, $categoryStartsWith) === 0) {
      $matchingCategoryIds[] = (int)($cat['id'] ?? 0);
    }
  }
  $matchingCategoryIds = array_values(array_filter(array_unique($matchingCategoryIds)));

  if (!empty($matchingCategoryIds)) {
    $placeholders = implode(',', array_fill(0, count($matchingCategoryIds), '?'));
    $whereClauses[] = "comp.primary_category_id IN ($placeholders)";
    foreach ($matchingCategoryIds as $id) {
      $params[] = $id;
      $types .= 'i';
    }
  } else {
    // No category names matched the requested starting letter.
    $whereClauses[] = "1 = 0";
  }
}

$emirateDisplayMap = [
  'dubai' => 'Dubai',
  'abu-dhabi' => 'Abu Dhabi',
  'sharjah' => 'Sharjah',
  'ajman' => 'Ajman',
  'ras-al-khaimah' => 'Ras Al Khaimah',
  'fujairah' => 'Fujairah',
  'umm-al-quwain' => 'Umm Al Quwain'
];

if (!empty($emirate)) {
  $emirateLabel = $emirateDisplayMap[$emirate] ?? ucwords(str_replace('-', ' ', $emirate));
  // Use indexed equality on city/state only. The location LIKE '%...%' fallback
  // state column has 0 populated rows â€” city-only equality allows idx_city (626 rows vs 360K scan).
  $whereClauses[] = "comp.city = ?";
  $params[] = $emirateLabel;
  $types .= 
's';
}

$whereSQL = implode(' AND ', $whereClauses);

$orderBy = match($sortBy) {
  'newest' => 'comp.id DESC',
  'rating_desc' => 'comp.verified DESC, comp.id DESC',
  'name_asc' => 'comp.company_name ASC',
  default => 'comp.verified DESC, comp.id DESC'
};
$totalCompanies = isset($cachedListingsPayload['totalCompanies']) ? (int)$cachedListingsPayload['totalCompanies'] : null;
$visibleTotalCompanies = isset($cachedListingsPayload['visibleTotalCompanies']) ? (int)$cachedListingsPayload['visibleTotalCompanies'] : null;
$totalPages = isset($cachedListingsPayload['totalPages']) ? (int)$cachedListingsPayload['totalPages'] : null;
$offset = isset($cachedListingsPayload['offset']) ? (int)$cachedListingsPayload['offset'] : $offset;
$companies = $cachedListingsPayload['companies'] ?? null;

if (!is_int($totalCompanies) || !is_int($visibleTotalCompanies) || !is_int($totalPages) || !is_array($companies)) {
  $countQuery = "SELECT COUNT(*) as total FROM `" . DB::COMPANIES . "` comp WHERE $whereSQL";

  $stmt = $conn->prepare($countQuery);
  if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  $totalRow = $result->fetch_assoc();
  $totalCompanies = (int)($totalRow['total'] ?? 0);
  $stmt->close();
  $_lt['count_done'] = microtime(true);

  // --- LOG SEARCH after result count is known ---
  if ($shouldLogSearch) {
    $searchesModel->recordSearch(
      $searchQueryString,
      $ip,
      $userIdForLog,
      $totalCompanies,
      'public-listings'
    );
  }

  // Enforce tier visibility cap on total results and pagination.
  $visibleTotalCompanies = min($totalCompanies, $tierVisibleLimit);
  $totalPages = max(1, (int)ceil($visibleTotalCompanies / $perPage));
  $page = max(1, min($page, $totalPages));
  $offset = ($page - 1) * $perPage;

  $query = "
    SELECT 
      comp.id,
      comp.company_name,
      comp.slug,
      comp.company_profile AS description,
      comp.state AS emirate,
      comp.city,
      comp.address,
      comp.telephone AS phone,
      comp.email,
      comp.website,
      0 AS featured,
      comp.verified,
      0 AS rating,
      0 AS review_count,
      comp.primary_category_id,
      '' AS category_slug
    FROM `" . DB::COMPANIES . "` comp
    WHERE $whereSQL
    ORDER BY $orderBy
    LIMIT ? OFFSET ?
  ";

  $stmt = $conn->prepare($query);
  $queryParams = $params;
  $queryTypes = $types . 'ii';
  $queryParams[] = $perPage;
  $queryParams[] = $offset;
  $stmt->bind_param($queryTypes, ...$queryParams);
  $stmt->execute();
  $result = $stmt->get_result();
  $companies = [];
  while ($row = $result->fetch_assoc()) {
    $categoryId = (int)($row['primary_category_id'] ?? 0);
    $row['category_name'] = $categoryNameById[$categoryId] ?? 'Business';
    $companies[] = $row;
  }
  $stmt->close();
  $_lt['queries_done'] = microtime(true);
}

// Get current path for pagination (without query string)
$currentPath = strtok($_SERVER['REQUEST_URI'], '?');
$currentPath = str_replace($GLOBALS['basePath'] ?? '', '', $currentPath);
$currentPath = ltrim($currentPath, '/');

// Ads use their own short-lived cache (60s) keyed by targeting context so they
// rotate every minute independently of the 180s results cache.
$adsCacheTtl = 60;
$adsCacheFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
  . 'haipulse_ads_listings_' . md5($emirate . '|' . $keyword . '|' . $category) . '.json';

$listingsInlineAds = null;
$listingsFooterAds = null;
if (is_file($adsCacheFile) && (time() - (int)@filemtime($adsCacheFile) < $adsCacheTtl)) {
  $adsCachedJson = @file_get_contents($adsCacheFile);
  if ($adsCachedJson !== false) {
    $adsDecoded = json_decode($adsCachedJson, true);
    if (is_array($adsDecoded)) {
      $listingsInlineAds = $adsDecoded['inline'] ?? null;
      $listingsFooterAds = $adsDecoded['footer'] ?? null;
    }
  }
}

if (!is_array($listingsInlineAds) || !is_array($listingsFooterAds)) {
  $publicAdsModel = new PublicAds($conn);
  $listingsInlineAds = $publicAdsModel->getAdsForSlot('listings_inline', [
    'page_type' => 'listings',
    'keyword' => $keyword,
    'category' => $category,
    'city' => $emirate,
    'tags' => ['software', 'leads', 'support', 'automation']
  ], 1);

  $listingsFooterAds = $publicAdsModel->getAdsForSlot('global_footer', [
    'page_type' => 'listings',
    'keyword' => $keyword,
    'tags' => ['software', 'accounting', 'crm']
  ], 1);
  @file_put_contents($adsCacheFile, json_encode([
    'inline' => $listingsInlineAds,
    'footer' => $listingsFooterAds,
  ], JSON_UNESCAPED_SLASHES));
}
$_lt['ads_done'] = microtime(true);

if ($cachedListingsPayload === null) {
  @file_put_contents($listingsCacheFile, json_encode([
    'categories' => $categories,
    'categoryBySlug' => $categoryBySlug,
    'categoryNameById' => $categoryNameById,
    'totalCompanies' => (int)$totalCompanies,
    'visibleTotalCompanies' => (int)$visibleTotalCompanies,
    'totalPages' => (int)$totalPages,
    'page' => (int)$page,
    'offset' => (int)$offset,
    'companies' => $companies,
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

// ============================================
// SECTION 4: GET FILTER OPTIONS
// ============================================

// Emirates list
$emirates = [
    'dubai' => 'Dubai',
    'abu-dhabi' => 'Abu Dhabi',
    'sharjah' => 'Sharjah',
    'ajman' => 'Ajman',
    'ras-al-khaimah' => 'Ras Al Khaimah',
    'fujairah' => 'Fujairah',
    'umm-al-quwain' => 'Umm Al Quwain'
];

// Build result hint message
$resultHint = 'Showing ';
if ($visibleTotalCompanies > 0) {
    $start = $offset + 1;
  $end = min($offset + $perPage, $visibleTotalCompanies);
  $resultHint .= "$start-$end of " . number_format($visibleTotalCompanies) . " results";
} else {
    $resultHint = 'No results found';
}

if (!empty($keyword)) {
    $resultHint .= ' for "' . htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') . '"';
}
if ($companyNameStartsWith !== '') {
  $resultHint .= ' with company name starting "' . htmlspecialchars($companyNameStartsWith, ENT_QUOTES, 'UTF-8') . '"';
}
if (!empty($category)) {
    $catName = array_filter($categories, fn($c) => $c['slug'] === $category);
    if ($catName) {
        $resultHint .= ' in ' . htmlspecialchars(reset($catName)['name'], ENT_QUOTES, 'UTF-8');
    }
} elseif ($categoryId > 0 && isset($categoryNameById[$categoryId])) {
  $resultHint .= ' in ' . htmlspecialchars($categoryNameById[$categoryId], ENT_QUOTES, 'UTF-8');
}
if ($categoryStartsWith !== '') {
  $resultHint .= ' in categories starting "' . htmlspecialchars($categoryStartsWith, ENT_QUOTES, 'UTF-8') . '"';
}
if (!empty($emirate)) {
    $resultHint .= ' in ' . htmlspecialchars($emirates[$emirate] ?? $emirate, ENT_QUOTES, 'UTF-8');
}

// Page metadata
$pageTitle = 'Business Listings - UAE Business Directory';
$bodyClass = 'page-listings';
$ampHtmlUrl = url('/listings/amp');
if (!empty($keyword)) {
    $pageTitle = htmlspecialchars($keyword) . ' - ' . $pageTitle;
}

// Generate JSON-LD structured data for rich results
if (!empty($companies)) {
    // ItemList schema for business listings
    $jsonLdSchema = generateItemListSchema(
        $companies,
        'UAE Business Listings',
        'Browse verified businesses and services across the UAE'
    );
    
    // Add breadcrumb schema
    $breadcrumbs = [
        ['name' => 'Home', 'url' => getFullUrl('/')],
        ['name' => 'Listings', 'url' => getFullUrl('/listings')]
    ];
    $jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);
}

// ============================================
// TIMING: Emit X-Timing-* headers + log slow requests
// ============================================
$_lt['render_start'] = microtime(true);
$_ltTotal = round(($_lt['render_start'] - $_lt['start']) * 1000, 2);
if (!headers_sent()) {
  header('X-Timing-Total: ' . $_ltTotal . 'ms');
  if (isset($_lt['cache_check'])) {
    header('X-Timing-Cache: ' . round(($_lt['cache_check'] - $_lt['start']) * 1000, 2) . 'ms');
  }
  if (isset($_lt['ads_done'])) {
    $adsBase = isset($_lt['queries_done']) ? $_lt['queries_done'] : (isset($_lt['cache_check']) ? $_lt['cache_check'] : $_lt['start']);
    header('X-Timing-Ads: ' . round(($_lt['ads_done'] - $adsBase) * 1000, 2) . 'ms');
  }
  if (isset($_lt['queries_done'])) {
    header('X-Timing-Queries: ' . round(($_lt['queries_done'] - $_lt['cache_check']) * 1000, 2) . 'ms');
  }
  if (isset($_lt['cats_done'])) {
    header('X-Timing-Cats: ' . round(($_lt['cats_done'] - $_lt['cache_check']) * 1000, 2) . 'ms');
  }
  if (isset($_lt['count_done'], $_lt['cats_done'])) {
    header('X-Timing-Count: ' . round(($_lt['count_done'] - $_lt['cats_done']) * 1000, 2) . 'ms');
  }
  if (isset($_lt['queries_done'], $_lt['count_done'])) {
    header('X-Timing-Data: ' . round(($_lt['queries_done'] - $_lt['count_done']) * 1000, 2) . 'ms');
  }
}
// Log slow cold requests (> 800ms) to help identify DB bottlenecks.
if ($_ltTotal > 800 && !($cachedListingsPayload !== null)) {
  $logDir = __DIR__ . '/../logs';
  $logFile = $logDir . '/listings_slow_requests.log';
  if (is_dir($logDir)) {
    $logLine = date('Y-m-d H:i:s') . ' '
      . 'total=' . $_ltTotal . 'ms '
      . 'cache=' . (isset($_lt['cache_check']) ? round(($_lt['cache_check'] - $_lt['start']) * 1000, 2) : '-') . 'ms '
      . 'queries=' . (isset($_lt['queries_done']) ? round(($_lt['queries_done'] - $_lt['cache_check']) * 1000, 2) : '-') . 'ms '
      . 'ads=' . (isset($_lt['ads_done']) ? round(($_lt['ads_done'] - (isset($_lt['queries_done']) ? $_lt['queries_done'] : $_lt['cache_check'])) * 1000, 2) : '-') . 'ms '
      . 'emirate=' . $emirate . ' kw=' . $keyword . ' cat=' . $category
      . PHP_EOL;
    @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
  }
}
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <style>
    .results-title {
      font-size: clamp(1.15rem, 2vw, 1.35rem);
      font-weight: 700;
      margin: 0;
      color: #111827;
    }

    .results-tools {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    .results-count {
      margin: 0;
      font-size: 0.95rem;
      font-weight: 600;
      color: #475569;
      text-align: right;
    }

    .listing-results {
      display: grid;
      gap: 14px;
      grid-template-columns: 1fr;
    }

    .listing-results.grid-view {
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    }

    .listing-results.grid-view .listing-card {
      height: 100%;
    }

    .listing-card {
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 16px;
      background: #fff;
      transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .listing-card:hover {
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
      transform: translateY(-1px);
    }

    .listing-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 8px;
    }

    .listing-name {
      font-size: clamp(1rem, 1.5vw, 1.15rem);
      font-weight: 700;
      margin: 0;
      line-height: 1.3;
      color: #111827;
    }

    .listing-name a {
      color: inherit;
      text-decoration: none;
    }

    .listing-name a:hover {
      color: #0f4ad8;
    }

    .listing-badges {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-bottom: 8px;
    }

    .listing-badge {
      display: inline-flex;
      align-items: center;
      padding: 4px 9px;
      border-radius: 999px;
      font-size: 0.78rem;
      font-weight: 600;
    }

    .badge-featured {
      background: #fff5e6;
      color: #b45309;
    }

    .badge-verified {
      background: #e8f9ef;
      color: #0f7a3f;
    }

    .badge-rating {
      background: #eef2ff;
      color: #1d4ed8;
    }

    .listing-meta {
      margin: 0 0 10px;
      color: #4b5563;
      font-size: 0.92rem;
      line-height: 1.45;
    }

    .listing-desc {
      margin: 0 0 12px;
      color: #374151;
      font-size: 0.94rem;
      line-height: 1.6;
      display: -webkit-box;
      line-clamp: 2;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .listing-info-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 12px;
    }

    .listing-info-item {
      font-size: 0.84rem;
      color: #374151;
      background: #f8fafc;
      border: 1px solid #e5e7eb;
      border-radius: 999px;
      padding: 4px 10px;
    }

    .listing-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      align-items: center;
    }

    .listing-actions .btn-ui {
      margin: 0;
      font-size: 0.86rem;
      padding: 7px 12px;
    }

    .listings-warning {
      margin-top: 10px;
      border-left-color: #f59e0b;
    }

    .listings-head {
      margin-top: 12px;
    }

    .listings-sort {
      max-width: 220px;
    }

    .filter-head {
      margin-bottom: 8px;
    }

    .filter-title {
      margin: 0;
      font-size: 1rem;
    }

    .filter-btn {
      width: 100%;
    }

    .filter-clear {
      width: 100%;
      margin-top: 8px;
      text-align: center;
      display: block;
    }

    .listings-empty-state {
      padding: 40px;
      text-align: center;
    }

    .listings-empty-cta {
      margin-top: 12px;
    }

    .listings-upgrade-cta {
      background: #f8f9fa;
      border-left: 4px solid #007acc;
      padding: 16px;
      margin: 20px 0;
      border-radius: 4px;
    }

    .listings-upgrade-cta h4 {
      margin-top: 0;
      color: #333;
    }

    .listings-upgrade-copy {
      margin: 8px 0;
      color: #666;
    }

    .listings-upgrade-link {
      color: #007acc;
      text-decoration: none;
    }

    .listings-upgrade-link-strong {
      color: #007acc;
      text-decoration: none;
      font-weight: 600;
    }

    .listings-pagination {
      margin-top: 24px;
    }

    .listings-pagination-wrap {
      display: flex;
      gap: 8px;
      justify-content: center;
      flex-wrap: wrap;
    }

    @media (max-width: 767.98px) {
      #main-content {
        padding-left: 0 !important;
        padding-right: 0 !important;
      }

      #main-content > .container-narrow {
        max-width: 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
      }

      .results-tools {
        justify-content: flex-start;
        gap: 8px;
      }

      .results-count {
        width: 100%;
        text-align: left;
      }

      .listing-results.grid-view {
        grid-template-columns: 1fr;
      }

      .listing-results {
        gap: 10px;
      }

      .listing-card {
        padding: 12px;
        border-radius: 10px;
        border-color: #e2e8f0;
        box-shadow: none;
      }

      .listing-card:hover {
        transform: none;
        box-shadow: none;
      }

      .listing-header {
        margin-bottom: 6px;
      }

      .listing-name {
        font-size: 1rem;
        line-height: 1.3;
      }

      .listing-badges {
        gap: 5px;
        margin-bottom: 7px;
      }

      .listing-badge {
        padding: 3px 8px;
        font-size: 0.74rem;
      }

      .listing-meta {
        margin-bottom: 8px;
        font-size: 0.86rem;
      }

      .listing-desc {
        margin-bottom: 9px;
        font-size: 0.88rem;
        line-height: 1.45;
        -webkit-line-clamp: 2;
        line-clamp: 2;
      }

      .listing-info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 6px;
        margin-bottom: 10px;
      }

      .listing-info-item {
        width: 100%;
        border-radius: 8px;
        padding: 5px 8px;
        font-size: 0.8rem;
      }

      .listing-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
      }

      .listing-actions .btn-ui {
        width: 100%;
        text-align: center;
        justify-content: center;
        font-size: 0.82rem;
        padding: 7px 8px;
      }

      .listing-actions .btn-ui:nth-child(3) {
        grid-column: 1 / -1;
      }

      .listings-empty-state {
        padding: 16px !important;
      }

      .listings-upgrade-cta {
        padding: 12px !important;
        margin: 12px 0 !important;
      }

      .listings-upgrade-cta h4 {
        font-size: 1rem;
        margin-bottom: 6px;
      }

      .listings-pagination {
        margin-top: 14px !important;
      }

      .listings-pagination .btn-ui {
        min-height: 40px;
        padding: 8px 10px;
      }
    }

    @media (max-width: 1199.98px) {
      body.hai-public.responsive-bootstrap-redesign.page-listings #main-content {
        padding-top: 22px;
        padding-bottom: 30px;
      }

      body.hai-public.responsive-bootstrap-redesign.page-listings .listings-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 18px 20px;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
      }

      body.hai-public.responsive-bootstrap-redesign.page-listings .list-layout {
        display: grid;
        grid-template-columns: minmax(260px, 300px) minmax(0, 1fr);
        gap: 18px;
        align-items: start;
      }

      body.hai-public.responsive-bootstrap-redesign.page-listings .sidebar {
        position: sticky;
        top: 92px;
      }

      body.hai-public.responsive-bootstrap-redesign.page-listings .filter-box,
      body.hai-public.responsive-bootstrap-redesign.page-listings .listing-card,
      body.hai-public.responsive-bootstrap-redesign.page-listings .listings-upgrade-cta,
      body.hai-public.responsive-bootstrap-redesign.page-listings .listings-empty-state {
        border-radius: 22px;
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.08);
      }

      body.hai-public.responsive-bootstrap-redesign.page-listings .filter-box {
        padding: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
      }

      body.hai-public.responsive-bootstrap-redesign.page-listings .listing-card {
        padding: 18px;
        border-color: #dbe4f0;
      }

      body.hai-public.responsive-bootstrap-redesign.page-listings .listing-header {
        gap: 14px;
        margin-bottom: 12px;
      }

      body.hai-public.responsive-bootstrap-redesign.page-listings .listing-actions .btn-ui {
        min-height: 42px;
        border-radius: 12px;
      }

      body.hai-public.responsive-bootstrap-redesign.page-listings .listings-pagination-wrap {
        gap: 10px;
      }
    }

    @media (max-width: 991.98px) {
      body.hai-public.responsive-bootstrap-redesign.page-listings .list-layout {
        grid-template-columns: 1fr;
      }

      body.hai-public.responsive-bootstrap-redesign.page-listings .sidebar {
        position: static;
      }
    }

    @media (max-width: 767.98px) {
      body.hai-public.responsive-bootstrap-redesign.page-listings .listings-head {
        flex-direction: column;
        align-items: stretch;
        padding: 14px;
        border-radius: 18px;
      }

      body.hai-public.responsive-bootstrap-redesign.page-listings .results-title {
        font-size: 1.05rem;
      }

      body.hai-public.responsive-bootstrap-redesign.page-listings .filter-box,
      body.hai-public.responsive-bootstrap-redesign.page-listings .listing-card,
      body.hai-public.responsive-bootstrap-redesign.page-listings .listings-upgrade-cta,
      body.hai-public.responsive-bootstrap-redesign.page-listings .listings-empty-state {
        border-radius: 18px;
        box-shadow: none;
      }

      body.hai-public.responsive-bootstrap-redesign.page-listings .filter-box {
        padding: 14px;
      }
    }
  </style>

  <main id="main-content" class="section">
    <?php
      $searchHeroTitle = 'Search UAE business listings';
      $searchHeroDescription = 'Browse verified companies by keyword, category, and city without leaving the directory flow.';
      $searchHeroFormAction = url('/listings');
      $searchHeroFormId = 'listings-hero-search-form';
      $searchHeroKeyword = $keyword;
      $searchHeroSubmitLabel = 'Search listings';
      $searchHeroVariant = 'listings';
      $searchHeroShowCategory = true;
      $searchHeroShowLocation = true;
      $searchHeroCategories = $categories;
      $searchHeroSelectedCategory = $category;
      $searchHeroLocations = $emirates;
      $searchHeroSelectedLocation = $emirate;
      $searchHeroHiddenFields = [
        'manual_search' => '1',
        'sort_by' => $sortBy,
        'company_name_starts_with' => $companyNameStartsWith,
        'category_starts_with' => $categoryStartsWith,
      ];
      include __DIR__ . '/../includes/partials/public-search-hero.php';
    ?>

    <div class="container-narrow">
      <?php if ($keywordError !== ''): ?>
      <div class="notice listings-warning" aria-live="polite">
        <?php echo htmlspecialchars($keywordError, ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <?php endif; ?>

      <div class="section-head listings-head">
        <h2 class="results-title">Search results</h2>
        <div class="results-tools">
          <p class="results-count" data-result-hint aria-live="polite"><?php echo $resultHint; ?></p>
          <button class="btn-ui btn-light-ui mobile-filter-toggle mobile-filter-trigger" data-filter-toggle aria-controls="listing-filter-panel" aria-expanded="false" type="button">Filters</button>
        </div>
      </div>

      <?php
        $publicAds = $listingsInlineAds;
        $publicAdSlot = 'inline';
        $publicAdHeading = 'Smart software picks for businesses getting leads';
        include __DIR__ . '/../includes/partials/public-ad-slot.php';
      ?>

      <form class="list-layout" id="listing-filter-form" method="get" action="">
        <aside class="sidebar" id="listing-filter-panel" data-filter-panel aria-hidden="true">
          <div class="card-ui filter-box">
            <div class="section-head filter-head">
              <h3 class="filter-title">Filter options</h3>
              <button class="btn-ui btn-light-ui mobile-filter-toggle" data-filter-toggle aria-controls="listing-filter-panel" aria-expanded="false" type="button">Close</button>
            </div>

            <div class="filter-group">
              <h4>Search keyword</h4>
              <input class="field" name="keyword" type="text" placeholder="e.g., clinic, cafe" 
                value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
                minlength="2"
                title="Enter at least 2 meaningful characters">
            </div>

            <?php if ($companyNameStartsWith !== ''): ?>
              <input type="hidden" name="company_name_starts_with" value="<?php echo htmlspecialchars($companyNameStartsWith, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
            <?php if ($categoryStartsWith !== ''): ?>
              <input type="hidden" name="category_starts_with" value="<?php echo htmlspecialchars($categoryStartsWith, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>

            <div class="filter-group">
              <h4>Category</h4>
              <select class="select" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?php echo htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8'); ?>"
                          <?php echo $category === $cat['slug'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?> 
                    (<?php echo number_format($cat['count']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="filter-group">
              <h4>City</h4>
              <select class="select" name="emirate">
                <option value="">All UAE cities</option>
                <?php foreach ($emirates as $value => $label): ?>
                  <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"
                          <?php echo $emirate === $value ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <input type="hidden" name="sort_by" id="sort-hidden" value="<?php echo htmlspecialchars($sortBy, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="manual_search" value="1">

            <button class="btn-ui btn-primary-ui filter-btn" type="submit">Apply filters</button>
            
            <?php if (!empty($keyword) || !empty($category) || $categoryId > 0 || !empty($emirate)): ?>
              <a href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui filter-clear">Clear all filters</a>
            <?php endif; ?>
          </div>
        </aside>

        <section>
          <?php if (empty($companies)): ?>
            <div class="card-ui listings-empty-state">
              <h3>No businesses found</h3>
              <p class="muted">Try adjusting your filters or search terms.</p>
              <a href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui listings-empty-cta">View all listings</a>
            </div>
          <?php else: ?>
            <div class="listing-results" id="listing-results">
              <?php foreach ($companies as $company): ?>
                <?php
                  $detailUrl = url('/company/' . rawurlencode((string)$company['slug']));
                ?>
                <?php include __DIR__ . '/../includes/partials/listing-card.php'; ?>
              <?php endforeach; ?>
            </div>

            <!-- Upgrade CTA (if free user and more results available) -->
            <?php 
            $actualMaxPages = [
              'total_pages' => $totalPages,
            ];
            ?>
            <?php if ($visibleTotalCompanies < $totalCompanies): ?>
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
                <h4>See All <?php echo number_format($totalCompanies); ?> Businesses</h4>
                <p class="listings-upgrade-copy">You're viewing limited results (<?php echo number_format($visibleTotalCompanies); ?>/browse). 
                <?php if ($userTier === SubscriptionTier::TIER_FREE): ?>
                  <a href="<?php echo url('/register'); ?>" class="listings-upgrade-link">Create a free account</a> 
                  to see up to <?php echo number_format($nextTierLimit); ?> results, or
                <?php endif; ?>
                <a href="<?php echo url('/pricing'); ?>" class="listings-upgrade-link-strong">upgrade to <?php echo htmlspecialchars($nextTierLabel, ENT_QUOTES, 'UTF-8'); ?></a> 
                for up to <?php echo number_format($nextTierLimit); ?> results.</p>
              </div>
            <?php endif; ?>

            <?php if ($actualMaxPages['total_pages'] > 1): ?>
              <nav class="listings-pagination" aria-label="Pagination">
                <div class="listings-pagination-wrap">
                  <?php if ($page > 1): ?>
                    <a href="<?php echo htmlspecialchars($currentPath, ENT_QUOTES, 'UTF-8'); ?>?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                       class="btn-ui btn-light-ui">â† Previous</a>
                  <?php endif; ?>
                  
                  <?php for ($i = max(1, $page - 2); $i <= min($actualMaxPages['total_pages'], $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                      <span class="btn-ui btn-primary-ui"><?php echo $i; ?></span>
                    <?php else: ?>
                      <a href="<?php echo htmlspecialchars($currentPath, ENT_QUOTES, 'UTF-8'); ?>?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                         class="btn-ui btn-light-ui"><?php echo $i; ?></a>
                    <?php endif; ?>
                  <?php endfor; ?>
                  
                  <?php if ($page < $actualMaxPages['total_pages']): ?>
                    <a href="<?php echo htmlspecialchars($currentPath, ENT_QUOTES, 'UTF-8'); ?>?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                       class="btn-ui btn-light-ui">Next â†’</a>
                  <?php endif; ?>
                </div>
              </nav>
            <?php endif; ?>
          <?php endif; ?>
        </section>
      </form>

      <?php
        $publicAds = $listingsFooterAds;
        $publicAdSlot = 'wide';
        $publicAdHeading = 'Finance and operations software';
        include __DIR__ . '/../includes/partials/public-ad-slot.php';
      ?>
    </div>
  </main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

