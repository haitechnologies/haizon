<?php
/**
 * Page: Subcategory Landing Page
 * Route: /subcategory/{slug}
 * Description: Display all companies in a specific subcategory with advanced filtering
 * Updated: March 1, 2026
 */

// ============================================
// DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/IpRateLimiter.php';
require_once __DIR__ . '/../classes/frontend/Subcategories.php';
require_once __DIR__ . '/../classes/frontend/Category.php';
require_once __DIR__ . '/../classes/frontend/Companies.php';

// Anti-scraping throttle for subcategory listing pages.
IpRateLimiter::init($conn);
$rateLimit = IpRateLimiter::check('subcategory_page', 180, 60);
if (empty($rateLimit['allowed'])) {
  http_response_code(429);
  header('Retry-After: 60');
  exit('Too many requests. Please try again in a minute.');
}

// ============================================
// GET ROUTE PARAMETERS & FILTERS
// ============================================
$subcategorySlug = $GLOBALS['route_params']['subcategory_slug'] ?? null;
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 18;

// Filter parameters
$filterEmirate = $_GET['emirate'] ?? '';
$filterRating = intval($_GET['rating'] ?? 0);
$filterVerified = isset($_GET['verified']) ? 1 : 0;
$filterFeatured = isset($_GET['featured']) ? 1 : 0;
$sortBy = $_GET['sort'] ?? 'relevance';
$viewMode = $_GET['view'] ?? 'grid';

if (!$subcategorySlug) {
    header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/listings'));
    exit;
}

// ============================================
// LOAD SUBCATEGORY DATA
// ============================================
$SubcategoriesModel = new Subcategories($conn);

// Resolve subcategory by slug with robust backward-compatible fallback.
$normalizedRequestedSlug = strtolower(trim(rawurldecode((string)$subcategorySlug)));
$normalizedRequestedSlug = preg_replace('/[^a-z0-9-]+/', '-', $normalizedRequestedSlug);
$normalizedRequestedSlug = preg_replace('/-+/', '-', $normalizedRequestedSlug);
$normalizedRequestedSlug = trim((string)$normalizedRequestedSlug, '-');

$subcategory = $SubcategoriesModel->getBySlug($normalizedRequestedSlug);

if (!$subcategory) {
  $subcategoriesResult = $SubcategoriesModel->getAll(['limit' => 1000]);
  foreach ($subcategoriesResult as $sub) {
    $candidateSlug = trim((string)($sub['slug'] ?? ''));
    if ($candidateSlug === '') {
      $candidateSlug = (string)($sub['name'] ?? $sub['subcategory'] ?? '');
    }

    $normalizedCandidateSlug = strtolower($candidateSlug);
    $normalizedCandidateSlug = preg_replace('/[^a-z0-9-]+/', '-', $normalizedCandidateSlug);
    $normalizedCandidateSlug = preg_replace('/-+/', '-', $normalizedCandidateSlug);
    $normalizedCandidateSlug = trim((string)$normalizedCandidateSlug, '-');

    if ($normalizedCandidateSlug === $normalizedRequestedSlug) {
      $subcategory = $sub;
      break;
    }
  }
}

if (!$subcategory) {
    http_response_code(404);
    $pageTitle = 'Subcategory Not Found';
    include __DIR__ . '/../pages/404.php';
    exit;
}

// Load parent category for breadcrumb
$CategoryModel = new Category($conn);
$parentCategory = $CategoryModel->getCategoryById($subcategory['category_id']);

// ============================================
// LOAD COMPANIES IN SUBCATEGORY
// ============================================
$CompaniesModel = new Companies($conn);
$companies = $CompaniesModel->getBySubcategory([
    'subcategory_id' => $subcategory['id'],
    'page' => $page,
    'per_page' => $perPage,
    'sort_by' => $sortBy,
    'emirate' => $filterEmirate,
    'min_rating' => $filterRating,
    'verified' => $filterVerified,
    'featured' => $filterFeatured
]);

$stats = $CompaniesModel->getSubcategoryStats($subcategory['id']);
$totalCompanies = $stats['total'] ?? 0;
$totalPages = max(1, ceil($totalCompanies / $perPage));
$emirateList = ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 'Fujairah', 'Umm Al Quwain'];

// ============================================
// PAGE METADATA
// ============================================
$pageTitle = htmlspecialchars($subcategory['name'], ENT_QUOTES, 'UTF-8') . ' in UAE';
if ($parentCategory) {
    $pageTitle .= ' - ' . htmlspecialchars($parentCategory['name'], ENT_QUOTES, 'UTF-8');
}
$pageTitle .= ' Business Directory';

$pageDescription = htmlspecialchars($subcategory['description'] ?? 'Find trusted ' . $subcategory['name'] . ' businesses across UAE.', ENT_QUOTES, 'UTF-8');
$subcategoryBaseUrl = url('/subcategory/' . rawurlencode($subcategory['slug'] ?? $subcategorySlug));

// Helper function for query strings
function buildSubcatQueryString($overrides = []) {
    global $filterEmirate, $filterRating, $filterVerified, $filterFeatured, $sortBy, $viewMode;
    $params = array_merge([
        'emirate' => $filterEmirate,
        'rating' => $filterRating,
        'sort' => $sortBy,
        'view' => $viewMode
    ], $overrides);
    
    if ($filterVerified) $params['verified'] = '1';
    if ($filterFeatured) $params['featured'] = '1';
    
    $params = array_filter($params, function($v) { return $v !== '' && $v !== '0' && $v !== 0; });
    return http_build_query($params);
}

$resultHint = '';
if ($totalCompanies > 0) {
    $start = (($page - 1) * $perPage) + 1;
    $end = min(($page - 1) * $perPage + count($companies), $totalCompanies);
    $resultHint = "Showing $start-$end of " . number_format($totalCompanies) . " businesses";
}

// Generate JSON-LD structured data for rich results
if (!empty($companies)) {
    // ItemList schema for subcategory businesses
    $jsonLdSchema = generateItemListSchema(
        $companies,
        $subcategory['name'] . ' Businesses in UAE',
        $subcategory['description'] ?? 'Find trusted ' . $subcategory['name'] . ' businesses across UAE'
    );
    
    // Add breadcrumb schema
    $breadcrumbs = [
        ['name' => 'Home', 'url' => getFullUrl('/')]
    ];
    if ($parentCategory) {
        $breadcrumbs[] = ['name' => $parentCategory['name'], 'url' => getFullUrl('/category/' . $parentCategory['slug'])];
    }
    $breadcrumbs[] = ['name' => $subcategory['name'], 'url' => getFullUrl('/subcategory/' . $subcategory['slug'])];
    
    $jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);
}
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

<style>
/* Subcategory Page Styles (same as category) */
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

.filter-toolbar {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 16px;
    margin-bottom: 24px;
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

.filter-select {
    padding: 8px 32px 8px 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--surface);
    font-size: 0.9rem;
    cursor: pointer;
}

.filter-select:hover {
    border-color: var(--primary);
}

.results-grid {
  display: flex;
  flex-direction: column;
  gap: 14px;
    margin-bottom: 32px;
}

.company-card-pro {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: all 0.3s ease;
    display: flex;
  flex-direction: row;
  align-items: stretch;
}

.company-card-pro:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 22px rgba(12, 26, 75, 0.1);
    border-color: var(--primary);
}

.subcategory-hero-copy {
  flex: 1;
}

.subcategory-title {
  margin: 0 0 12px 0;
  font-size: 2.5rem;
  font-weight: 700;
}

.subcategory-desc {
  margin: 0;
  font-size: 1.05rem;
  opacity: 0.95;
}

.subcategory-count {
  margin-top: 16px;
  font-size: 1.2rem;
  font-weight: 600;
}

.subcategory-verified-toggle {
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
}

.subcategory-result-hint,
.subcategory-rating-count,
.subcategory-empty-note {
  color: var(--text-soft);
}

.subcategory-empty {
  text-align: center;
  padding: 60px 20px;
}

.subcategory-action {
  flex: 1;
}

.subcategory-pagination {
  padding: 20px;
  margin-top: 32px;
  text-align: center;
}

.subcategory-page-label {
  margin: 0 12px;
}

.company-card-header {
    background: linear-gradient(135deg, #f0f4ff 0%, #e1e9ff 100%);
  padding: 16px;
  min-height: auto;
  width: 140px;
  flex: 0 0 140px;
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
  width: 64px;
  height: 64px;
    background: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
  font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.company-card-body {
  padding: 16px 18px;
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
  justify-content: flex-start;
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
}

.breadcrumb a:hover {
    color: var(--primary);
}

@media (max-width: 768px) {
  .company-card-pro {
    flex-direction: column;
  }

  .company-card-header {
    width: 100%;
    flex: 0 0 auto;
  }
    
    .toolbar-row {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<main id="main-content" class="section">
  <div class="container-narrow">
    
    <!-- Breadcrumb -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo ($GLOBALS['basePath'] ?? ''); ?>/">Home</a>
      <span class="breadcrumb-separator">›</span>
      <a href="<?php echo ($GLOBALS['basePath'] ?? ''); ?>/listings">Directory</a>
      <?php if ($parentCategory): ?>
        <span class="breadcrumb-separator">›</span>
        <a href="<?php echo ($GLOBALS['basePath'] ?? ''); ?>/category/<?php echo urlencode($parentCategory['slug']); ?>">
          <?php echo htmlspecialchars($parentCategory['name'], ENT_QUOTES); ?>
        </a>
      <?php endif; ?>
      <span class="breadcrumb-separator">›</span>
      <span aria-current="page"><?php echo htmlspecialchars($subcategory['name'], ENT_QUOTES); ?></span>
    </nav>

    <!-- Hero Section -->
    <div class="category-hero">
      <div class="container-narrow">
        <div class="category-hero-content">
          <div class="category-icon-large">📂</div>
          <div class="subcategory-hero-copy">
            <h1 class="subcategory-title">
              <?php echo htmlspecialchars($subcategory['name'], ENT_QUOTES); ?>
            </h1>
            <p class="subcategory-desc">
              <?php echo htmlspecialchars($subcategory['description'] ?? '', ENT_QUOTES); ?>
            </p>
            <div class="subcategory-count">
              <?php echo number_format($totalCompanies); ?> Businesses
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="filter-toolbar">
      <form method="GET" action="<?php echo htmlspecialchars($subcategoryBaseUrl, ENT_QUOTES, 'UTF-8'); ?>" id="filter-form">
        <div class="toolbar-row">
          <div class="filter-group-inline">
            <select name="emirate" class="filter-select" onchange="document.getElementById('filter-form').submit()">
              <option value="">All Emirates</option>
              <?php foreach ($emirateList as $emirate): ?>
                <option value="<?php echo htmlspecialchars($emirate); ?>" <?php echo $filterEmirate === $emirate ? 'selected' : ''; ?>>
                  <?php echo $emirate; ?>
                </option>
              <?php endforeach; ?>
            </select>

            <select name="sort" class="filter-select" onchange="document.getElementById('filter-form').submit()">
              <option value="relevance" <?php echo $sortBy === 'relevance' ? 'selected' : ''; ?>>Most Relevant</option>
              <option value="rating" <?php echo $sortBy === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
              <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
            </select>

            <label class="subcategory-verified-toggle">
              <input type="checkbox" name="verified" value="1" <?php echo $filterVerified ? 'checked' : ''; ?> 
                onchange="document.getElementById('filter-form').submit()">
              ✓ Verified
            </label>
          </div>
        </div>
      </form>
    </div>

    <!-- Results -->
    <p class="subcategory-result-hint"><?php echo $resultHint; ?></p>

    <?php if (empty($companies)): ?>
      <div class="card-ui subcategory-empty">
        <h3>No Businesses Found</h3>
        <p class="subcategory-empty-note">No businesses in this subcategory yet.</p>
      </div>
    <?php else: ?>
      <div class="results-grid">
        <?php foreach ($companies as $company): 
          $companyDisplayName = display_text($company['company_name'] ?? '');
          $companyInitial = strtoupper(substr($companyDisplayName, 0, 1));
            $companySlugUrl = ($GLOBALS['basePath'] ?? '') . '/company/' . urlencode($company['slug']);
          ?>
            <article class="company-card-pro">
              <!-- Card Header with Logo Placeholder -->
              <div class="company-card-header">
                <div class="company-badges">
                  <?php if (isset($company['featured']) && $company['featured']): ?>
                    <span class="badge-pro badge-featured">⭐ Featured</span>
                  <?php endif; ?>
                  <?php if (isset($company['verified']) && $company['verified']): ?>
                    <span class="badge-pro badge-verified">✓ Verified</span>
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
                    <?php echo htmlspecialchars($companyDisplayName, ENT_QUOTES, 'UTF-8'); ?>
                  </a>
                </h3>

                <div class="company-meta">
                  <?php if (!empty($company['emirate'])): ?>
                    <span>📍 <?php echo htmlspecialchars($company['emirate'], ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php endif; ?>
                  <?php if (!empty($company['city']) && empty($company['emirate'])): ?>
                    <span>📍 <?php echo htmlspecialchars($company['city'], ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php endif; ?>
                  <?php if (!empty($company['avg_rating'])): ?>
                    <span class="company-rating">
                      ★ <?php echo number_format($company['avg_rating'], 1); ?>
                      <span class="subcategory-rating-count">
                        (<?php echo number_format($company['review_count'] ?? 0); ?>)
                      </span>
                    </span>
                  <?php endif; ?>
                </div>

                <?php if (!empty($company['description'])): ?>
                  <div class="company-description">
                    <?php 
                    $desc = htmlspecialchars($company['description'], ENT_QUOTES, 'UTF-8');
                    echo strlen($desc) > 120 ? substr($desc, 0, 120) . '...' : $desc;
                    ?>
                  </div>
                <?php endif; ?>

                <!-- Contact Icons -->
                <?php if (!empty($company['phone']) || !empty($company['telephone']) || !empty($company['email']) || !empty($company['website'])): ?>
                  <div class="company-contacts">
                    <?php 
                      $phoneStr = !empty($company['phone']) ? $company['phone'] : (!empty($company['telephone']) ? $company['telephone'] : null);
                      if ($phoneStr): 
                    ?>
                      <div class="contact-item">
                        <span>📞</span>
                        <a href="tel:<?php echo htmlspecialchars($phoneStr, ENT_QUOTES, 'UTF-8'); ?>">
                          Call
                        </a>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($company['email'])): ?>
                      <div class="contact-item">
                        <span>✉</span>
                        <a href="mailto:<?php echo htmlspecialchars($company['email'], ENT_QUOTES, 'UTF-8'); ?>">
                          Email
                        </a>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($company['website'])): ?>
                      <div class="contact-item">
                        <span>🌐</span>
                        <a href="<?php echo htmlspecialchars($company['website'], ENT_QUOTES, 'UTF-8'); ?>" 
                          target="_blank" rel="noopener noreferrer">
                          Visit
                        </a>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="company-card-footer">
                  <a href="<?php echo $companySlugUrl; ?>" class="btn-ui btn-primary-ui subcategory-action">
                    View Details →
                  </a>
                </div>
              </div>
            </article>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="card-ui subcategory-pagination">
          <?php if ($page > 1): ?>
            <a href="<?php echo htmlspecialchars($subcategoryBaseUrl, ENT_QUOTES, 'UTF-8'); ?>?<?php echo buildSubcatQueryString(['page' => $page - 1]); ?>" class="btn-ui btn-light-ui">← Previous</a>
          <?php endif; ?>
          
          <span class="subcategory-page-label">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
          
          <?php if ($page < $totalPages): ?>
            <a href="<?php echo htmlspecialchars($subcategoryBaseUrl, ENT_QUOTES, 'UTF-8'); ?>?<?php echo buildSubcatQueryString(['page' => $page + 1]); ?>" class="btn-ui btn-light-ui">Next →</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
