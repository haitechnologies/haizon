<?php
/**
 * Page: Trending Companies
 * Route: /trending or /pages/trending.php
 * Description: Display most viewed and engaged companies
 * Author: Development Team
 * Created: February 28, 2026
 */

// ============================================
// SECTION 1: DEPENDENCIES & INITIALIZATION
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../includes/helpers.php';

// ============================================
// SECTION 2: GET REQUEST PARAMETERS
// ============================================
$sortBy = trim($_GET['sort'] ?? 'views');
$categoryId = intval($_GET['category'] ?? 0);
$timeRange = trim($_GET['time'] ?? 'all');
$currentPage = intval($_GET['page'] ?? 1);
$itemsPerPage = 12;
$offset = ($currentPage - 1) * $itemsPerPage;

// Validate sort parameter
$validSorts = ['views', 'engagement', 'rating', 'inquiries'];
if (!in_array($sortBy, $validSorts)) {
    $sortBy = 'views';
}

// ============================================
// SECTION 3: FETCH TRENDING COMPANIES
// ============================================
$where = ['c.is_active = 1', 'c.publish = 1'];
$params = [];
$types = '';

// Filter by category if selected
if ($categoryId > 0) {
    $where[] = 'c.primary_category_id = ?';
    $params[] = $categoryId;
    $types .= 'i';
}

// Build WHERE clause
$whereClause = implode(' AND ', $where);

// Build ORDER BY based on sort selection
switch ($sortBy) {
    case 'engagement':
        $orderBy = 'c.inquiries_month DESC, c.inquiries_total DESC';
        break;
    case 'rating':
        $orderBy = 'c.id DESC'; // Rating not available in current schema
        break;
    case 'inquiries':
        $orderBy = 'c.inquiries_month DESC, c.inquiries_total DESC';
        break;
    case 'views':
    default:
        $orderBy = 'c.profile_views_month DESC';
}

// Get total count
$countQuery = "
    SELECT COUNT(*) as total
    FROM `" . DB::COMPANIES . "` c
    WHERE {$whereClause}
";
$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$countResult = $stmt->get_result();
$countRow = $countResult->fetch_assoc();
$totalCompanies = $countRow['total'];
$totalPages = ceil($totalCompanies / $itemsPerPage);
$stmt->close();

// Clamp current page
if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
}
if ($currentPage < 1) {
    $currentPage = 1;
}
$offset = ($currentPage - 1) * $itemsPerPage;

// Fetch trending companies
$query = "
    SELECT 
        c.id,
        c.company_name,
        c.slug,
        c.email,
        c.telephone,
        c.city,
        c.country AS emirate,
        c.verified,
        c.primary_category_id,
        IFNULL(cat.name, 'Business') AS category_name,
        COALESCE(c.profile_views_month, 0) AS profile_views,
        0 AS listing_clicks,
        COALESCE(c.inquiries_month, 0) AS inquiries_sent,
        0 AS avg_rating,
        0 AS review_count
    FROM `" . DB::COMPANIES . "` c
    LEFT JOIN `" . DB::CATEGORIES . "` cat ON c.primary_category_id = cat.id
    WHERE {$whereClause}
    ORDER BY {$orderBy}
    LIMIT ? OFFSET ?
";

$params[] = $itemsPerPage;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$companies = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ============================================
// SECTION 4: GET CATEGORIES
// ============================================
$categoriesQuery = "SELECT id, name FROM " . DB::CATEGORIES . " WHERE is_active = 1 ORDER BY name ASC LIMIT 20";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

// ============================================
// SECTION 5: PAGE METADATA
// ============================================
$sortLabel = match($sortBy) {
    'engagement' => 'Most Engaged',
    'rating' => 'Highest Rated',
    'inquiries' => 'Most Inquiries',
    default => 'Most Viewed'
};
$pageTitle = 'Trending Companies - ' . $sortLabel . ' - UAE Business Directory';
$pageDescription = 'Discover the most popular and trending businesses in the UAE. View trending companies by views, engagement, ratings, and inquiries.';
$trendingBaseUrl = url('/trending');
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main id="main-content" class="section">
    <div class="container-narrow">
      <!-- Page Header -->
      <div class="trending-head">
        <h1 class="trending-title">🔥 Trending Companies</h1>
        <p class="muted trending-subtitle">Discover the most popular businesses in the UAE</p>
      </div>

      <!-- Sort & Filter Bar -->
      <div class="trending-filter-wrap">
        <div class="trending-filter-row">
          <div>
            <strong>Sort by:</strong>
            <div class="trending-chip-row">
              <a href="<?php echo htmlspecialchars($trendingBaseUrl . '?sort=views' . ($categoryId > 0 ? '&category=' . $categoryId : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui trending-chip <?php echo $sortBy === 'views' ? 'btn-primary-ui' : 'btn-light-ui'; ?>">👁 Most Viewed</a>
              <a href="<?php echo htmlspecialchars($trendingBaseUrl . '?sort=engagement' . ($categoryId > 0 ? '&category=' . $categoryId : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui trending-chip <?php echo $sortBy === 'engagement' ? 'btn-primary-ui' : 'btn-light-ui'; ?>">📊 Most Engaged</a>
              <a href="<?php echo htmlspecialchars($trendingBaseUrl . '?sort=rating' . ($categoryId > 0 ? '&category=' . $categoryId : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui trending-chip <?php echo $sortBy === 'rating' ? 'btn-primary-ui' : 'btn-light-ui'; ?>">⭐ Highest Rated</a>
              <a href="<?php echo htmlspecialchars($trendingBaseUrl . '?sort=inquiries' . ($categoryId > 0 ? '&category=' . $categoryId : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui trending-chip <?php echo $sortBy === 'inquiries' ? 'btn-primary-ui' : 'btn-light-ui'; ?>">💬 Most Inquiries</a>
            </div>
          </div>
          
          <?php if (!empty($categories)): ?>
          <div>
            <strong>Category:</strong>
            <div class="trending-chip-row">
              <a href="<?php echo htmlspecialchars($trendingBaseUrl . '?sort=' . urlencode($sortBy), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui trending-chip <?php echo $categoryId === 0 ? 'btn-primary-ui' : 'btn-light-ui'; ?>">All Categories</a>
              <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
                <a href="<?php echo htmlspecialchars($trendingBaseUrl . '?sort=' . urlencode($sortBy) . '&category=' . (int)$cat['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui trending-chip <?php echo $categoryId === $cat['id'] ? 'btn-primary-ui' : 'btn-light-ui'; ?>">
                  <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Companies Grid -->
      <div class="list-layout trending-layout">
        <!-- Main Content -->
        <section class="trending-main">
          <?php if (empty($companies)): ?>
            <div class="card-ui detail-box trending-empty">
              <h3>No companies found</h3>
              <p class="muted">Try adjusting your filters or browse from a different category</p>
              <a href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui trending-empty-cta">Browse All Companies</a>
            </div>
          <?php else: ?>
            <div class="trending-grid">
              <?php foreach ($companies as $i => $company): ?>
                <article class="card-ui trending-card">
                  <!-- Rank Badge -->
                  <div class="trending-rank">
                    #<?php echo $offset + $i + 1; ?>
                  </div>
                  
                  <div class="trending-card-body">
                    <!-- Header -->
                    <div class="trending-company-head">
                      <a href="<?php echo htmlspecialchars(url('/company/' . rawurlencode((string)$company['slug'])), ENT_QUOTES, 'UTF-8'); ?>" class="trending-company-link">
                        <h3 class="trending-company-title">
                          <?php echo htmlspecialchars(display_text($company['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </h3>
                      </a>
                      <div class="trending-company-meta">
                        <?php if (!empty($company['category_name'])): ?>
                          <span class="trending-category-pill">
                            <?php echo htmlspecialchars($company['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                          </span>
                        <?php endif; ?>
                        <?php if ($company['verified']): ?>
                          <span class="trending-verified">✓ Verified</span>
                        <?php endif; ?>
                      </div>
                    </div>

                    <!-- Location & Contact -->
                    <div class="trending-contact">
                      <?php if (!empty($company['city']) || !empty($company['emirate'])): ?>
                        <p class="trending-contact-line">📍 <?php echo htmlspecialchars(trim(($company['city'] ?? '') . (($company['city'] && $company['emirate']) ? ', ' : '') . ($company['emirate'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></p>
                      <?php endif; ?>
                      <?php if (!empty($company['telephone'])): ?>
                        <p class="trending-contact-line"><a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', (string)$company['telephone']), ENT_QUOTES, 'UTF-8'); ?>" class="trending-phone-link">📞 <?php echo htmlspecialchars($company['telephone'], ENT_QUOTES, 'UTF-8'); ?></a></p>
                      <?php endif; ?>
                    </div>

                    <!-- Ratings -->
                    <?php if ($company['avg_rating'] > 0): ?>
                      <div class="trending-rating-box">
                        <div class="trending-rating-row">
                          <div class="trending-stars">
                            <?php echo str_repeat('⭐', (int)$company['avg_rating']); ?>
                          </div>
                          <div>
                            <strong><?php echo number_format($company['avg_rating'], 1); ?>/5</strong>
                            <small class="muted">(<?php echo number_format($company['review_count']); ?> reviews)</small>
                          </div>
                        </div>
                      </div>
                    <?php endif; ?>

                    <!-- Engagement Stats -->
                    <div class="trending-stats-wrap">
                      <div class="trending-stats">
                        <span>👁 <?php echo number_format($company['profile_views']); ?> views</span>
                        <span>💬 <?php echo number_format($company['inquiries_sent']); ?> inquiries</span>
                      </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="trending-actions">
                      <a href="<?php echo htmlspecialchars(url('/company/' . rawurlencode((string)$company['slug'])), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui">View Profile</a>
                      <a href="<?php echo htmlspecialchars(url('/contact') . '?ref=' . rawurlencode((string)$company['slug']), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui">Inquire</a>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
              <div class="trending-pagination">
                <?php if ($currentPage > 1): ?>
                  <a href="<?php echo htmlspecialchars($trendingBaseUrl . '?sort=' . urlencode($sortBy) . '&page=1' . ($categoryId > 0 ? '&category=' . $categoryId : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui">First</a>
                  <a href="<?php echo htmlspecialchars($trendingBaseUrl . '?sort=' . urlencode($sortBy) . '&page=' . ($currentPage - 1) . ($categoryId > 0 ? '&category=' . $categoryId : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui">Previous</a>
                <?php endif; ?>
                
                <span class="trending-pagination-info">
                  Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>
                </span>
                
                <?php if ($currentPage < $totalPages): ?>
                  <a href="<?php echo htmlspecialchars($trendingBaseUrl . '?sort=' . urlencode($sortBy) . '&page=' . ($currentPage + 1) . ($categoryId > 0 ? '&category=' . $categoryId : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui">Next</a>
                  <a href="<?php echo htmlspecialchars($trendingBaseUrl . '?sort=' . urlencode($sortBy) . '&page=' . $totalPages . ($categoryId > 0 ? '&category=' . $categoryId : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui">Last</a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </section>

        <!-- Sidebar -->
        <aside class="trending-side">
          <div class="card-ui detail-box">
            <h3 class="trending-side-title">What's Trending?</h3>
            <p class="muted trending-side-copy">
              Explore the most active and sought-after businesses in the UAE. Companies are ranked by:
            </p>
            <ul class="trending-side-list">
              <li>Profile Views</li>
              <li>Customer Inquiries</li>
              <li>User Engagement</li>
              <li>Customer Ratings</li>
            </ul>
          </div>

          <div class="card-ui detail-box trending-side-card-spaced">
            <h3 class="trending-side-title">Top Categories</h3>
            <div class="trending-cats">
              <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
                <a href="<?php echo htmlspecialchars($trendingBaseUrl . '?sort=' . urlencode($sortBy) . '&category=' . (int)$cat['id'], ENT_QUOTES, 'UTF-8'); ?>" class="trending-cat-link">
                  → <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>
