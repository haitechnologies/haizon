<?php
/**
 * Page: Category Landing Page (PROFESSIONAL REDESIGN)
 * Route: /category/{slug}
 * Description: Display all companies in a specific category with advanced filtering
 * Author: Development Team
 * Updated: March 1, 2026
 */

// ============================================
// SECTION 1: DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/IpRateLimiter.php';
require_once __DIR__ . '/../classes/frontend/Category.php';
require_once __DIR__ . '/../classes/frontend/Subcategories.php';

// Anti-scraping throttle for category listing pages.
IpRateLimiter::init($conn);
$rateLimit = IpRateLimiter::check('category_page', 180, 60);
if (empty($rateLimit['allowed'])) {
  http_response_code(429);
  header('Retry-After: 60');
  exit('Too many requests. Please try again in a minute.');
}

// ============================================
// SECTION 2: GET ROUTE PARAMETERS & FILTERS
// ============================================
$categorySlug = $GLOBALS['route_params']['category_slug'] ?? null;
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 18;
$disableRatingFilter = (strtolower((string)$categorySlug) === 'business-services');

// Filter parameters
$filterEmirate = $_GET['emirate'] ?? '';
$filterRating = intval($_GET['rating'] ?? 0);
$filterVerified = isset($_GET['verified']) ? 1 : 0;
$filterFeatured = isset($_GET['featured']) ? 1 : 0;
$sortBy = $_GET['sort'] ?? 'relevance'; // relevance, rating, name, newest

if ($disableRatingFilter) {
  $filterRating = 0;
  if ($sortBy === 'rating') {
    $sortBy = 'relevance';
  }
}

if (!$categorySlug) {
    header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/listings'));
    exit;
}

// Lightweight file cache for repeated category page loads with identical filters.
$categoryPageCacheTtl = 180;
$categoryPageCacheFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'haipulse_category_page_v1_' . md5(json_encode([
  'slug' => strtolower((string)$categorySlug),
  'page' => (int)$page,
  'perPage' => (int)$perPage,
  'emirate' => (string)$filterEmirate,
  'rating' => (int)$filterRating,
  'verified' => (int)$filterVerified,
  'featured' => (int)$filterFeatured,
  'sort' => (string)$sortBy,
])) . '.json';

$cachedCategoryPayload = null;
if (is_file($categoryPageCacheFile) && (time() - (int)@filemtime($categoryPageCacheFile) < $categoryPageCacheTtl)) {
  $cachedJson = @file_get_contents($categoryPageCacheFile);
  if ($cachedJson !== false) {
    $decoded = json_decode($cachedJson, true);
    if (is_array($decoded)) {
      $cachedCategoryPayload = $decoded;
    }
  }
}

// ============================================
// SECTION 3: LOAD CATEGORY DATA
// ============================================
$Category = new Category($conn);
$category = $cachedCategoryPayload['category'] ?? $Category->getCategoryBySlug($categorySlug);

if (!$category) {
    http_response_code(404);
    $pageTitle = 'Category Not Found';
    include __DIR__ . '/../pages/404.php';
    exit;
}

$categoryName = html_entity_decode((string)($category['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

// ============================================
// SECTION 3.5: LOAD SUBCATEGORIES
// ============================================
$subcategories = $cachedCategoryPayload['subcategories'] ?? null;
if (!is_array($subcategories)) {
  $SubcategoriesModel = new Subcategories($conn);
  $subcategories = $SubcategoriesModel->getByCategory($category['id']);
}

// ============================================
// SECTION 4: LOAD COMPANIES WITH FILTERS
// ============================================
$filters = [
    'emirate' => $filterEmirate,
  'min_rating' => $disableRatingFilter ? 0 : $filterRating,
    'verified' => $filterVerified,
    'featured' => $filterFeatured,
    'sort_by' => $sortBy
];

$companies = $cachedCategoryPayload['companies'] ?? null;
$stats = $cachedCategoryPayload['stats'] ?? null;
$totalPages = $cachedCategoryPayload['totalPages'] ?? null;

if (!is_array($companies) || !is_array($stats) || !is_numeric($totalPages)) {
  $companies = $Category->getCompaniesInCategory($category['id'], $page, $perPage, $filters);
  $stats = $Category->getCategoryStats($category['id']);

  $usesCountFilters = !empty($filterEmirate) || !empty($filterVerified);
  if ($usesCountFilters) {
    $totalPages = $Category->getTotalPages($category['id'], $perPage, $filters);
  } else {
    $totalPages = max(1, (int)ceil(((int)($stats['total_companies'] ?? 0)) / $perPage));
  }

  @file_put_contents($categoryPageCacheFile, json_encode([
    'category' => $category,
    'subcategories' => $subcategories,
    'companies' => $companies,
    'stats' => $stats,
    'totalPages' => (int)$totalPages,
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

// Get unique emirates for filter dropdown
$emirates = ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 'Fujairah', 'Umm Al Quwain'];

// ============================================
// SECTION 5: BUILD PAGE METADATA
// ============================================
$pageTitle = htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . ' in UAE - Business Directory';
$pageDescription = htmlspecialchars($category['description'] ?? 'Find trusted ' . $categoryName . ' businesses across UAE. Compare ratings, reviews, and contact information.', ENT_QUOTES, 'UTF-8');

$totalCompanies = $stats['total_companies'] ?? 0;
$avgRating = $stats['avg_rating'] ? round($stats['avg_rating'], 1) : 0;
$verifiedCount = $stats['verified_count'] ?? 0;

// Get current path for pagination (without query string)
$currentPath = strtok($_SERVER['REQUEST_URI'], '?');
$currentPath = str_replace($GLOBALS['basePath'] ?? '', '', $currentPath);
$currentPath = ltrim($currentPath, '/');

// Build query string for filters
function buildQueryString($overrides = []) {
  global $filterEmirate, $filterRating, $filterVerified, $filterFeatured, $sortBy, $disableRatingFilter;
    $params = array_merge([
        'emirate' => $filterEmirate,
        'rating' => $disableRatingFilter ? 0 : $filterRating,
    'sort' => $sortBy
    ], $overrides);
    
    if ($filterVerified) $params['verified'] = '1';
    if ($filterFeatured) $params['featured'] = '1';
    
    // Remove empty values
    $params = array_filter($params, function($v) { return $v !== '' && $v !== '0' && $v !== 0; });
    
    return http_build_query($params);
}

// Result hint message
$resultHint = '';
if ($totalCompanies > 0) {
    $start = (($page - 1) * $perPage) + 1;
    $end = min(($page - 1) * $perPage + count($companies), $totalCompanies);
    $resultHint = "Showing $start-$end of " . number_format($totalCompanies) . " businesses";
} else {
    $resultHint = 'No businesses found';
}

// Generate JSON-LD structured data for rich results
if (!empty($companies)) {
    // ItemList schema for category businesses
    $jsonLdSchema = generateItemListSchema(
        $companies,
      $categoryName . ' Businesses in UAE',
      $category['description'] ?? 'Find trusted ' . $categoryName . ' businesses across UAE'
    );
    
    // Add breadcrumb schema
    $breadcrumbs = [
        ['name' => 'Home', 'url' => getFullUrl('/')],
        ['name' => 'Categories', 'url' => getFullUrl('/listings')],
        ['name' => $categoryName, 'url' => getFullUrl('/category/' . $category['slug'])]
    ];
    $jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);
}

  $bodyClass = 'page-category';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

<style>
/* Category Page Specific Styles */
.category-hero {
    background: linear-gradient(135deg, var(--primary) 0%, #1a5fd9 100%);
    color: white;
    padding: 48px 0;
    margin-bottom: 32px;
    border-radius: 0 0 24px 24px;
}

.category-hero-content {
    display: flex;
    align-items: center;
    gap: 24px;
}

.category-icon-large {
    font-size: 5rem;
    opacity: 0.95;
    filter: drop-shadow(0 4px 12px rgba(0,0,0,0.1));
}

.category-stats-bar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 20px;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid rgba(255,255,255,0.2);
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    display: block;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.filter-toolbar {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 16px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-soft);
}

.toolbar-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: space-between;
}

.filter-group-inline {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.filter-group-inline label {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-soft);
}

.filter-select {
    padding: 8px 32px 8px 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--surface);
    color: var(--text);
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-select:hover, .filter-select:focus {
    border-color: var(--primary);
    outline: none;
}

.filter-checkbox {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    padding: 6px 12px;
    border-radius: var(--radius-sm);
    background: var(--surface-soft);
    border: 1px solid transparent;
    transition: all 0.2s;
}

.filter-checkbox:hover {
    border-color: var(--primary);
}

.filter-checkbox input {
    cursor: pointer;
}

.view-toggle {
    display: flex;
    gap: 4px;
    background: var(--surface-soft);
    border-radius: var(--radius-sm);
    padding: 4px;
}

.view-toggle button {
    padding: 8px 12px;
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 8px;
    color: var(--text-soft);
    transition: all 0.2s;
    font-size: 0.9rem;
}

.view-toggle button.active {
    background: var(--primary);
    color: white;
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.results-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 32px;
}

.company-card-pro {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.company-card-pro:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 32px rgba(12, 26, 75, 0.12);
    border-color: var(--primary);
}

.company-card-header {
    background: linear-gradient(135deg, #f0f4ff 0%, #e1e9ff 100%);
    padding: 20px;
    min-height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.company-badges {
    position: absolute;
    top: 12px;
    right: 12px;
    display: flex;
    gap: 6px;
}

.badge-pro {
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
}

.badge-featured {
    background: rgba(242, 178, 3, 0.9);
    color: #4a3600;
}

.badge-verified {
    background: rgba(5, 150, 105, 0.9);
    color: white;
}

.company-logo-placeholder {
    width: 80px;
    height: 80px;
    background: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.company-card-body {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.company-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: var(--text);
    line-height: 1.3;
}

.company-title a {
    color: inherit;
    text-decoration: none;
    transition: color 0.2s;
}

.company-title a:hover {
    color: var(--primary);
}

.company-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    font-size: 0.85rem;
    color: var(--text-soft);
    flex-wrap: wrap;
}

.company-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #f59e0b;
    font-weight: 600;
}

.company-description {
    font-size: 0.9rem;
    color: var(--text-soft);
    line-height: 1.5;
    margin-bottom: 16px;
    flex: 1;
}

.company-contacts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 8px;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: var(--text-soft);
}

.contact-item a {
    color: var(--primary);
    text-decoration: none;
    transition: opacity 0.2s;
}

.contact-item a:hover {
    opacity: 0.7;
}

.company-card-footer {
    display: flex;
    gap: 8px;
}

/* List View Mode */
.company-card-list {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 20px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 32px;
    font-size: 0.9rem;
}

.breadcrumb a {
    color: var(--text-soft);
    text-decoration: none;
    transition: color 0.2s;
}

.breadcrumb a:hover {
    color: var(--primary);
}

.breadcrumb-separator {
    color: var(--text-soft);
}

@media (max-width: 768px) {
  #main-content {
    padding-left: 0 !important;
    padding-right: 0 !important;
  }

  #main-content > .container-narrow {
    max-width: 100% !important;
    padding-left: 12px !important;
    padding-right: 12px !important;
  }

  .category-hero {
    padding: 24px 0;
    margin-bottom: 20px;
    border-radius: 0 0 18px 18px;
  }

  .category-hero .container-narrow {
    max-width: 100% !important;
    padding-left: 12px !important;
    padding-right: 12px !important;
  }
    
  .category-hero-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
    text-align: left;
  }

  .category-hero-title {
    font-size: 1.6rem;
    margin-bottom: 10px;
  }

  .category-hero-desc {
    font-size: 0.96rem;
    line-height: 1.55;
  }
    
  .category-icon-large {
    font-size: 3rem;
    line-height: 1;
  }

  .category-stats-bar {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-top: 16px;
    padding-top: 16px;
  }

  .stat-item {
    padding: 10px 8px;
    background: rgba(255,255,255,0.12);
    border-radius: 12px;
  }

  .stat-value {
    font-size: 1.35rem;
  }

  .stat-label {
    font-size: 0.78rem;
  }

  .breadcrumb {
    gap: 6px;
    margin-bottom: 18px;
    font-size: 0.8rem;
    flex-wrap: wrap;
    line-height: 1.4;
  }

  .category-subcats-wrap {
    margin-bottom: 18px;
  }

  .category-subcats-title {
    font-size: 1.1rem;
    margin-bottom: 12px;
  }

  .category-subcats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }

  .category-subcat-link {
    min-height: 74px;
    padding: 12px;
    align-items: flex-start;
    gap: 8px;
    flex-direction: column;
    justify-content: center;
  }

  .category-subcat-name {
    font-size: 0.88rem;
    line-height: 1.35;
  }

  .category-subcat-count {
    font-size: 0.78rem;
  }
    
  .results-grid {
    grid-template-columns: 1fr;
    gap: 14px;
    margin-bottom: 20px;
  }

  .filter-toolbar {
    padding: 14px 12px;
    margin-bottom: 16px;
    border-radius: 16px;
  }
    
  .toolbar-row {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }
    
  .filter-group-inline {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }

  .filter-group-inline label:not(.filter-checkbox) {
    font-size: 0.78rem;
    margin: 0 0 -2px;
  }

  .filter-select {
    width: 100%;
    min-height: 44px;
    font-size: 0.92rem;
  }

  .filter-checkbox {
    width: 100%;
    min-height: 42px;
    padding: 10px 12px;
    justify-content: flex-start;
    font-size: 0.86rem;
  }

  .category-result-hint {
    margin-bottom: 14px;
    padding: 0 2px;
    font-size: 0.88rem;
    line-height: 1.45;
  }
    
  .company-card-list {
    grid-template-columns: 1fr;
  }

  .company-card-pro {
    border-radius: 16px;
    box-shadow: 0 10px 24px rgba(12, 26, 75, 0.08);
  }

  .company-card-pro:hover {
    transform: none;
    box-shadow: 0 10px 24px rgba(12, 26, 75, 0.08);
  }

  .company-card-header {
    min-height: 96px;
    padding: 16px;
  }

  .company-badges {
    top: 10px;
    right: 10px;
    gap: 4px;
    flex-wrap: wrap;
    justify-content: flex-end;
    max-width: calc(100% - 20px);
  }

  .badge-pro {
    font-size: 0.69rem;
    padding: 4px 8px;
  }

  .company-logo-placeholder {
    width: 62px;
    height: 62px;
    border-radius: 10px;
    font-size: 1.5rem;
  }

  .company-card-body {
    padding: 16px 14px;
  }

  .company-title {
    font-size: 1rem;
    margin-bottom: 8px;
  }

  .company-meta {
    gap: 8px;
    margin-bottom: 10px;
    font-size: 0.8rem;
  }

  .company-description {
    font-size: 0.86rem;
    line-height: 1.5;
    margin-bottom: 12px;
    line-clamp: 3;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .company-contacts {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    margin-bottom: 14px;
    padding-bottom: 14px;
  }

  .contact-item {
    min-height: 38px;
    padding: 8px 10px;
    border-radius: 10px;
    background: var(--surface-soft);
    font-size: 0.8rem;
  }

  .company-card-footer,
  .company-card-action {
    width: 100%;
  }

  .company-card-action .btn-ui {
    width: 100%;
    min-height: 44px;
  }

  .category-empty-state {
    padding: 18px 12px !important;
  }

  .category-pagination-card {
    padding: 12px !important;
    margin-top: 16px !important;
    border-radius: 16px !important;
  }

  .pagination-row {
    gap: 10px;
    align-items: stretch;
  }

  .pagination-row > div {
    width: 100%;
  }

  .pagination-row > div > .btn-ui,
  .pagination-row > div > a.btn-ui,
  .pagination-row > div > button.btn-ui {
    width: 100%;
    justify-content: center;
  }

  .pagination-nums {
    overflow-x: auto;
    flex-wrap: nowrap;
    padding-bottom: 4px;
    scrollbar-width: none;
  }

  .pagination-nums::-webkit-scrollbar {
    display: none;
  }

  .pagination-btn {
    flex: 0 0 auto;
  }

  .pagination-info {
    text-align: center;
    font-size: 0.82rem;
    margin-top: 10px;
  }

  .category-cta-card {
    margin-top: 20px !important;
    padding: 16px 12px !important;
    text-align: center;
  }

  .category-cta-card h2 {
    font-size: 1.2rem !important;
    margin-bottom: 8px !important;
  }

  .category-cta-card p {
    margin-bottom: 12px !important;
    font-size: 0.92rem !important;
  }

  .category-cta-btn {
    width: 100%;
    min-height: 44px;
  }
}

@media (max-width: 575.98px) {
  .category-stats-bar,
  .company-contacts,
  .category-subcats-grid {
    grid-template-columns: 1fr;
  }

  .category-icon-large {
    font-size: 2.6rem;
  }

  .company-badges {
    left: 10px;
    right: 10px;
    justify-content: flex-start;
  }
}
</style>

<main id="main-content" class="section">
  <div class="container-narrow">
    
    <!-- Breadcrumb -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo ($GLOBALS['basePath'] ?? ''); ?>/">Home</a>
      <span class="breadcrumb-separator">â€º</span>
      <a href="<?php echo ($GLOBALS['basePath'] ?? ''); ?>/listings">Business Directory</a>
      <span class="breadcrumb-separator">â€º</span>
      <span aria-current="page"><?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?></span>
    </nav>

    <!-- Category Hero -->
    <div class="category-hero">
      <div class="container-narrow">
        <div class="category-hero-content">
          <?php if ($category['icon']): ?>
            <div class="category-icon-large">
              <?php echo htmlspecialchars($category['icon'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>
          <div class="category-hero-main">
            <h1 class="category-hero-title">
              <?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <?php if ($category['description']): ?>
              <p class="category-hero-desc">
                <?php echo htmlspecialchars($category['description'], ENT_QUOTES, 'UTF-8'); ?>
              </p>
            <?php endif; ?>
            
            <!-- Category Stats Bar -->
            <div class="category-stats-bar">
              <div class="stat-item">
                <span class="stat-value"><?php echo $totalCompanies; ?></span>
                <span class="stat-label">Businesses</span>
              </div>
              <?php if ($verifiedCount > 0): ?>
                <div class="stat-item">
                  <span class="stat-value"><?php echo $verifiedCount; ?></span>
                  <span class="stat-label">Verified</span>
                </div>
              <?php endif; ?>
              <?php if ($avgRating > 0): ?>
                <div class="stat-item">
                  <span class="stat-value">â˜… <?php echo $avgRating; ?></span>
                  <span class="stat-label">Avg Rating</span>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Subcategories Grid -->
    <?php if (!empty($subcategories)): ?>
      <div class="category-subcats-wrap">
        <h2 class="category-subcats-title">Explore Subcategories</h2>
        <div class="category-subcats-grid">
          <?php foreach ($subcategories as $subcat): ?>
            <a href="<?php echo ($GLOBALS['basePath'] ?? ''); ?>/subcategory/<?php echo urlencode($subcat['slug']); ?>" 
               class="card-ui category-subcat-link">
              <span class="category-subcat-name"><?php echo htmlspecialchars($subcat['name'], ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="category-subcat-count">
                <?php echo isset($subcat['total_companies']) ? $subcat['total_companies'] : ''; ?>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Filter & Sort Toolbar -->
    <div class="category-mobile-tools">
      <button class="btn-ui btn-light-ui mobile-filter-toggle mobile-filter-trigger" data-filter-toggle aria-controls="category-filter-panel" aria-expanded="false" type="button">Filter &amp; sort</button>
    </div>

    <div class="category-filter-panel" id="category-filter-panel" data-filter-panel aria-hidden="true">
      <div class="filter-toolbar">
      <form method="GET" action="" id="filter-form">
        <div class="toolbar-row toolbar-row-mobile-head">
          <h2 class="filter-mobile-title">Filter &amp; sort</h2>
          <button class="btn-ui btn-light-ui mobile-filter-toggle" data-filter-toggle aria-controls="category-filter-panel" aria-expanded="false" type="button">Close</button>
        </div>
        <div class="toolbar-row">
          <div class="filter-group-inline">
            <!-- Location Filter -->
            <label for="emirate-filter">Location:</label>
            <select name="emirate" id="emirate-filter" class="filter-select" onchange="document.getElementById('filter-form').submit()">
              <option value="">All Emirates</option>
              <?php foreach ($emirates as $emirate): ?>
                <option value="<?php echo htmlspecialchars($emirate, ENT_QUOTES, 'UTF-8'); ?>" 
                  <?php echo $filterEmirate === $emirate ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($emirate, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>

            <?php if (!$disableRatingFilter): ?>
            <!-- Rating Filter -->
            <label for="rating-filter">Min Rating:</label>
            <select name="rating" id="rating-filter" class="filter-select" onchange="document.getElementById('filter-form').submit()">
              <option value="0">Any Rating</option>
              <option value="4" <?php echo $filterRating === 4 ? 'selected' : ''; ?>>4+ Stars</option>
              <option value="3" <?php echo $filterRating === 3 ? 'selected' : ''; ?>>3+ Stars</option>
            </select>
            <?php endif; ?>

            <!-- Sort By -->
            <label for="sort-filter">Sort:</label>
            <select name="sort" id="sort-filter" class="filter-select" onchange="document.getElementById('filter-form').submit()">
              <option value="relevance" <?php echo $sortBy === 'relevance' ? 'selected' : ''; ?>>Most Relevant</option>
              <?php if (!$disableRatingFilter): ?>
              <option value="rating" <?php echo $sortBy === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
              <?php endif; ?>
              <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
              <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
            </select>

            <!-- Checkboxes -->
            <label class="filter-checkbox">
              <input type="checkbox" name="verified" value="1" <?php echo $filterVerified ? 'checked' : ''; ?> 
                onchange="document.getElementById('filter-form').submit()">
              âœ“ Verified Only
            </label>

            <label class="filter-checkbox">
              <input type="checkbox" name="featured" value="1" <?php echo $filterFeatured ? 'checked' : ''; ?> 
                onchange="document.getElementById('filter-form').submit()">
              â­ Featured Only
            </label>
          </div>

        </div>
      </form>
    </div>
    </div>

    <!-- Results Info -->
    <div class="category-result-hint">
      <?php echo $resultHint; ?>
    </div>

    <!-- Companies List -->
    <?php if (empty($companies)): ?>
      <div class="card-ui category-empty-state">
        <div class="category-empty-icon">ðŸ¢</div>
        <h3 class="category-empty-title">No Businesses Found</h3>
        <p class="category-empty-text">
          <?php if ($filterEmirate || (!$disableRatingFilter && $filterRating) || $filterVerified || $filterFeatured): ?>
            Try adjusting your filters or
            <a href="<?php echo htmlspecialchars($currentPath, ENT_QUOTES, 'UTF-8'); ?>" class="category-clear-link">clear all filters</a>
          <?php else: ?>
            Be the first to register your business in this category!
          <?php endif; ?>
        </p>
        <a href="<?php echo htmlspecialchars(url('/add-business?category=' . rawurlencode((string)($category['slug'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui">
          Add Your Business
        </a>
      </div>
    <?php else: ?>
      
      <div class="results-grid">
        <?php foreach ($companies as $company): 
          $companyInitial = strtoupper(substr($company['company_name'], 0, 1));
          $companySlugUrl = ($GLOBALS['basePath'] ?? '') . '/company/' . urlencode($company['slug']);
        ?>
          <article class="company-card-pro">
            <!-- Card Header with Logo Placeholder -->
            <div class="company-card-header">
              <div class="company-badges">
                <?php if ($company['featured']): ?>
                  <span class="badge-pro badge-featured">â­ Featured</span>
                <?php endif; ?>
                <?php if ($company['verified']): ?>
                  <span class="badge-pro badge-verified">âœ“ Verified</span>
                <?php endif; ?>
              </div>
              <div class="company-logo-placeholder">
                <?php echo $companyInitial; ?>
              </div>
            </div>

            <!-- Card Body -->
            <div class="company-card-body">
              <h3 class="company-title">
                <a href="<?php echo $companySlugUrl; ?>">
                  <?php echo htmlspecialchars($company['company_name'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
              </h3>

              <div class="company-meta">
                <?php if ($company['emirate']): ?>
                  <span>ðŸ“ <?php echo htmlspecialchars($company['emirate'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
                <?php if ($company['avg_rating']): ?>
                  <span class="company-rating">
                    â˜… <?php echo number_format($company['avg_rating'], 1); ?>
                    <span class="company-rating-count">
                      (<?php echo number_format($company['review_count'] ?? 0); ?>)
                    </span>
                  </span>
                <?php endif; ?>
              </div>

              <?php if (!empty($company['description'])): ?>
                <div class="company-description">
                  <?php 
                  $rawDesc = trim((string)($company['description'] ?? ''));
                  $companyTitle = trim((string)($company['company_name'] ?? ''));
                  $normalizedDesc = preg_replace('/\s+/', ' ', strtolower($rawDesc));
                  $normalizedTitle = preg_replace('/\s+/', ' ', strtolower($companyTitle));
                  $displayDesc = $normalizedDesc !== '' && $normalizedDesc === $normalizedTitle
                    ? 'View verified business information, contact details, and listing highlights for this company.'
                    : (strlen($rawDesc) > 120 ? substr($rawDesc, 0, 117) . '...' : $rawDesc);
                  echo htmlspecialchars($displayDesc, ENT_QUOTES, 'UTF-8');
                  ?>
                </div>
              <?php endif; ?>

              <!-- Contact Icons -->
              <?php if (!empty($company['phone']) || !empty($company['email']) || !empty($company['website'])): ?>
                <div class="company-contacts">
                  <?php if (!empty($company['phone'])): ?>
                    <div class="contact-item">
                      <span>ðŸ“ž</span>
                      <a href="tel:<?php echo htmlspecialchars($company['phone'], ENT_QUOTES, 'UTF-8'); ?>">
                        Call
                      </a>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($company['email'])): ?>
                    <div class="contact-item">
                      <span>âœ‰</span>
                      <a href="mailto:<?php echo htmlspecialchars($company['email'], ENT_QUOTES, 'UTF-8'); ?>">
                        Email
                      </a>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($company['website'])): ?>
                    <div class="contact-item">
                      <span>ðŸŒ</span>
                      <a href="<?php echo htmlspecialchars($company['website'], ENT_QUOTES, 'UTF-8'); ?>" 
                        target="_blank" rel="noopener noreferrer">
                        Visit
                      </a>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <!-- Action Buttons -->
              <div class="company-card-footer company-card-action">
                <a href="<?php echo $companySlugUrl; ?>" class="btn-ui btn-primary-ui">
                  View Details â†’
                </a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <?php endif; ?>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav class="card-ui category-pagination-card" aria-label="Results pagination">
          <div class="pagination-row">
            <!-- Previous Button -->
            <div>
              <?php if ($page > 1): ?>
                <a href="?<?php echo buildQueryString(['page' => $page - 1]); ?>" 
                  class="btn-ui btn-light-ui">
                  â† Previous
                </a>
              <?php else: ?>
                <button class="btn-ui btn-light-ui btn-disabled" disabled>
                  â† Previous
                </button>
              <?php endif; ?>
            </div>

            <!-- Page Numbers -->
            <div class="pagination-nums">
              <?php 
              $startPage = max(1, $page - 2);
              $endPage = min($totalPages, $page + 2);
              
              // First page
              if ($startPage > 1): ?>
                <a href="?<?php echo buildQueryString(['page' => 1]); ?>" 
                  class="btn-ui btn-light-ui pagination-btn">
                  1
                </a>
                <?php if ($startPage > 2): ?>
                  <span class="pagination-ellipsis">...</span>
                <?php endif; ?>
              <?php endif; ?>

              <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i === $page): ?>
                  <button class="btn-ui btn-primary-ui pagination-btn btn-page-current" disabled aria-current="page">
                    <?php echo $i; ?>
                  </button>
                <?php else: ?>
                  <a href="?<?php echo buildQueryString(['page' => $i]); ?>" 
                    class="btn-ui btn-light-ui pagination-btn">
                    <?php echo $i; ?>
                  </a>
                <?php endif; ?>
              <?php endfor; ?>

              <?php 
              // Last page
              if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                  <span class="pagination-ellipsis">...</span>
                <?php endif; ?>
                <a href="?<?php echo buildQueryString(['page' => $totalPages]); ?>" 
                  class="btn-ui btn-light-ui pagination-btn">
                  <?php echo $totalPages; ?>
                </a>
              <?php endif; ?>
            </div>

            <!-- Next Button -->
            <div>
              <?php if ($page < $totalPages): ?>
                <a href="?<?php echo buildQueryString(['page' => $page + 1]); ?>" 
                  class="btn-ui btn-light-ui">
                  Next â†’
                </a>
              <?php else: ?>
                <button class="btn-ui btn-light-ui btn-disabled" disabled>
                  Next â†’
                </button>
              <?php endif; ?>
            </div>
          </div>

          <!-- Page Info -->
          <div class="pagination-info">
            Page <?php echo $page; ?> of <?php echo $totalPages; ?>
          </div>
        </nav>
      <?php endif; ?>

    <!-- Call-to-Action Section -->
    <?php if (!empty($companies)): ?>
      <div class="card-ui category-cta-card">
        <h2 class="category-cta-title">
          Is Your Business in This Category?
        </h2>
        <p class="category-cta-text">
          Join thousands of businesses on UAE's leading directory platform. Get discovered by customers today!
        </p>
        <a href="<?php echo htmlspecialchars(url('/add-business?category=' . rawurlencode((string)($category['slug'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?>" 
          class="btn-ui btn-primary-ui category-cta-btn">
          Add Your Business Now
        </a>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

