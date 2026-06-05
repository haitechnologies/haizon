<?php
/**
 * Page: All Services/Products Browser
 * Route: /services
 * Description: Browse all 3,344+ services/products available in UAE businesses database
 * Updated: March 2, 2026
 */

// ============================================
// DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/Categories.php';
require_once __DIR__ . '/../classes/frontend/CategoryItems.php';

// ============================================
// GET FILTER PARAMETERS
// ============================================
$page = max(1, intval($_GET['page'] ?? 1));
$categoryFilter = $_GET['category'] ?? '';
$searchTerm = trim($_GET['q'] ?? '');
$sortBy = $_GET['sort'] ?? 'popular';
$perPage = 24;

$allowedSorts = ['popular', 'alphabetical', 'newest'];
if (!in_array($sortBy, $allowedSorts, true)) {
  $sortBy = 'popular';
}

$baseServicesUrl = url('/services');
$makeServicesUrl = static function(array $params = []) use ($baseServicesUrl): string {
  $query = http_build_query($params);
  return $baseServicesUrl . ($query !== '' ? ('?' . $query) : '');
};

// ============================================
// LOAD DATA
// ============================================
$CategoryItemsModel = new CategoryItems($conn);
$CategoriesModel = new Categories($conn);

// Build filter options
$filterOptions = [
    'published_only' => true,
    'limit' => $perPage,
    'offset' => ($page - 1) * $perPage,
];

if ($sortBy === 'alphabetical') {
  $filterOptions['order_by'] = 'name ASC';
} elseif ($sortBy === 'newest') {
  $filterOptions['order_by'] = 'id DESC';
} else {
  $filterOptions['order_by'] = 'total_companies DESC, sort_order ASC';
}

if (!empty($categoryFilter)) {
    $filterOptions['category_id'] = $categoryFilter;
}

// Get services
if (!empty($searchTerm)) {
  $matchedServices = $CategoryItemsModel->search($searchTerm, 10000);

  if (!empty($categoryFilter)) {
    $matchedServices = array_values(array_filter($matchedServices, static function($item) use ($categoryFilter) {
      return (string)($item['category_id'] ?? '') === (string)$categoryFilter;
    }));
  }

  if ($sortBy === 'alphabetical') {
    usort($matchedServices, static function($a, $b) {
      return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
    });
  } elseif ($sortBy === 'newest') {
    usort($matchedServices, static function($a, $b) {
      return ((int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0));
    });
  } else {
    usort($matchedServices, static function($a, $b) {
      return ((int)($b['total_companies'] ?? 0) <=> (int)($a['total_companies'] ?? 0));
    });
  }

  $totalServices = count($matchedServices);
  $services = array_slice($matchedServices, max(0, ($page - 1) * $perPage), $perPage);
} else {
    $services = $CategoryItemsModel->getAll($filterOptions);
  $countOptions = [
    'published_only' => true,
    'limit' => 10000,
  ];
  if (!empty($categoryFilter)) {
    $countOptions['category_id'] = $categoryFilter;
  }
  $allServices = $CategoryItemsModel->getAll($countOptions);
    $totalServices = count($allServices);
}

$totalPages = max(1, ceil($totalServices / $perPage));

// Get all categories for filter dropdown
$categories = $CategoriesModel->getAll(['limit' => 100]);

// ============================================
// PAGE METADATA
// ============================================
$pageTitle = 'Services & Products Directory - Find 3,344+ Services in UAE';
$pageDescription = 'Browse all services and products available from businesses across UAE. Find construction services, IT solutions, consulting, transportation, and 3,300+ more services.';
$pageKeywords = 'services, products, business services, UAE services directory, consulting, construction, IT services';
$bodyClass = 'page-services';

// Generate JSON-LD structured data for rich results
if (!empty($services)) {
    // Create service items for ItemList schema
    $serviceItems = array_map(function($service) {
        return [
            'name' => $service['name'] ?? $service['title'] ?? '',
            'description' => $service['description'] ?? '',
            'url' => getFullUrl('/service/' . ($service['slug'] ?? $service['id']))
        ];
    }, $services);
    
    // ItemList schema for services
    $itemListElement = [];
    foreach ($serviceItems as $index => $item) {
        $itemListElement[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'item' => [
                '@type' => 'Service',
                'name' => $item['name'],
                'url' => $item['url']
            ]
        ];
        if (!empty($item['description'])) {
            $itemListElement[$index]['item']['description'] = substr(strip_tags($item['description']), 0, 200);
        }
    }
    
    $itemListData = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'UAE Services & Products Directory',
        'description' => $pageDescription,
        'itemListElement' => $itemListElement
    ];
    $jsonLdSchema = '<script type="application/ld+json">' . json_encode($itemListData, JSON_UNESCAPED_SLASHES) . '</script>';
    
    // Add breadcrumb schema
    $breadcrumbs = [
        ['name' => 'Home', 'url' => getFullUrl('/')],
        ['name' => 'Services', 'url' => getFullUrl('/services')]
    ];
    $jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);
}

include __DIR__ . '/../includes/layout/header.php';
?>

<style>
  /* Services Page Professional Styles */
  .services-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 52px 20px;
    color: white;
    text-align: center;
    margin: 10px auto 30px;
    border-radius: 20px;
    max-width: 1200px;
    width: calc(100% - 24px);
  }

  .services-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 12px;
    font-weight: 700;
  }

  .services-hero p {
    font-size: 1.1rem;
    margin-bottom: 24px;
    opacity: 0.95;
  }

  .services-search-bar {
    max-width: 500px;
    margin: 0 auto;
    position: relative;
  }

  .services-search-bar input {
    width: 100%;
    padding: 12px 16px;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
  }

  .services-search-bar button {
    position: absolute;
    right: 4px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #667eea;
    cursor: pointer;
    padding: 8px;
    font-size: 1.2rem;
  }

  .services-controls {
    display: flex;
    gap: 16px;
    margin-bottom: 32px;
    flex-wrap: wrap;
    align-items: center;
  }

  .services-filter, .services-sort {
    flex: 1;
    min-width: 200px;
  }

  .services-actions {
    display: flex;
    align-items: flex-end;
    gap: 10px;
  }

  .services-apply-btn {
    padding: 10px 16px;
    border: 1px solid #667eea;
    border-radius: 4px;
    background: #667eea;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
  }

  .services-apply-btn:hover {
    background: #5b6fd8;
    border-color: #5b6fd8;
  }

  .services-filter select, .services-sort select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.95rem;
  }

  .services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
  }

  .service-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
  }

  .service-card:hover {
    border-color: #667eea;
    box-shadow: 0 8px 16px rgba(102, 126, 234, 0.15);
    transform: translateY(-4px);
  }

  .service-icon {
    font-size: 2.5rem;
    margin-bottom: 12px;
    color: #667eea;
  }

  .service-name {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
  }

  .service-category {
    font-size: 0.85rem;
    color: #999;
    margin-bottom: 12px;
  }

  .service-count {
    font-size: 0.9rem;
    font-weight: 600;
    color: #667eea;
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
  }

  .services-pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin: 40px 0;
    flex-wrap: wrap;
  }

  .pagination-btn {
    padding: 8px 12px;
    border: 1px solid #ddd;
    background: white;
    color: #333;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
  }

  .pagination-btn:hover, .pagination-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
  }

  .no-services {
    text-align: center;
    padding: 60px 20px;
    color: #999;
  }

  .services-hero-inner {
    max-width: 900px;
    margin: 0 auto;
  }

  .services-content-wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 12px;
  }

  .services-ellipsis {
    padding: 8px 4px;
  }

  .services-clear-link {
    color: #667eea;
    text-decoration: none;
  }

  .services-info {
    margin-top: 60px;
    padding: 40px;
    background: #f9f9f9;
    border-radius: 8px;
    margin-bottom: 40px;
  }

  .services-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
    margin-top: 20px;
  }

  @media (max-width: 768px) {
    .services-hero h1 {
      font-size: 1.8rem;
    }

    .services-grid {
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 12px;
    }

    .services-controls {
      flex-direction: column;
    }

    .services-filter, .services-sort {
      min-width: auto;
    }

    .services-actions {
      width: 100%;
    }

    .services-apply-btn {
      width: 100%;
    }
  }
</style>

<main class="site-main">
  <!-- Hero Section -->
  <div class="services-hero">
    <div class="services-hero-inner">
      <h1>Services & Products Directory</h1>
      <p>Explore 3,344+ services and products offered by businesses across UAE</p>
      
      <!-- Search Bar -->
      <div class="services-search-bar">
        <form method="GET" action="<?php echo htmlspecialchars($baseServicesUrl, ENT_QUOTES); ?>">
          <input type="text" name="q" placeholder="Search services..." value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES); ?>">
          <?php if (!empty($categoryFilter)): ?>
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryFilter, ENT_QUOTES); ?>">
          <?php endif; ?>
          <?php if (!empty($sortBy)): ?>
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortBy, ENT_QUOTES); ?>">
          <?php endif; ?>
          <button type="submit" title="Search">🔍</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Content Section -->
  <div class="services-content-wrap">
    
    <!-- Controls -->
    <div class="services-mobile-tools">
      <button class="btn-ui btn-light-ui mobile-filter-toggle mobile-filter-trigger" data-filter-toggle aria-controls="services-filter-panel" aria-expanded="false" type="button">Filter &amp; sort</button>
    </div>

    <div class="services-filter-panel" id="services-filter-panel" data-filter-panel aria-hidden="true">
    <form class="services-controls" method="GET" action="<?php echo htmlspecialchars($baseServicesUrl, ENT_QUOTES); ?>">
      <div class="services-controls-head">
        <h2 class="services-controls-title">Filter &amp; sort</h2>
        <button class="btn-ui btn-light-ui mobile-filter-toggle" data-filter-toggle aria-controls="services-filter-panel" aria-expanded="false" type="button">Close</button>
      </div>
      <?php if (!empty($searchTerm)): ?>
        <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES); ?>">
      <?php endif; ?>

      <div class="services-filter">
        <label for="category-filter">Filter by Category:</label>
        <select id="category-filter" name="category">
          <option value="">All Categories (<?php echo count($categories); ?>)</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat['id'], ENT_QUOTES); ?>" <?php echo ($categoryFilter == $cat['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat['name'], ENT_QUOTES); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="services-sort">
        <label for="sort-by">Sort by:</label>
        <select id="sort-by" name="sort">
          <option value="popular" <?php echo ($sortBy == 'popular') ? 'selected' : ''; ?>>Most Popular</option>
          <option value="alphabetical" <?php echo ($sortBy == 'alphabetical') ? 'selected' : ''; ?>>A-Z</option>
          <option value="newest" <?php echo ($sortBy == 'newest') ? 'selected' : ''; ?>>Newest</option>
        </select>
      </div>

      <div class="services-actions">
        <button type="submit" class="services-apply-btn">Apply Filters</button>
      </div>
    </form>
    </div>

    <!-- Services Grid -->
    <?php if (!empty($services)): ?>
      <div class="services-grid">
        <?php foreach ($services as $service): ?>
          <?php $serviceUrl = url('/service/' . rawurlencode((string)($service['slug'] ?? $service['id']))); ?>
          <a href="<?php echo htmlspecialchars($serviceUrl, ENT_QUOTES); ?>" class="service-card">
            <div class="service-icon">📦</div>
            <div class="service-name"><?php echo htmlspecialchars(substr($service['name'], 0, 50), ENT_QUOTES); ?></div>
            <div class="service-category">
              <?php 
              $cat = current(array_filter($categories, fn($c) => $c['id'] == $service['category_id']));
              echo htmlspecialchars($cat['name'] ?? 'General', ENT_QUOTES);
              ?>
            </div>
            <div class="service-count">
              <?php 
              $count = $CategoryItemsModel->getCompanyCount($service['id']);
              echo $count . ' ' . ($count == 1 ? 'Company' : 'Companies');
              ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="services-pagination">
          <?php
          $baseQueryParams = [];
          if ($categoryFilter !== '') {
            $baseQueryParams['category'] = $categoryFilter;
          }
          if ($searchTerm !== '') {
            $baseQueryParams['q'] = $searchTerm;
          }
          if ($sortBy !== '') {
            $baseQueryParams['sort'] = $sortBy;
          }
          ?>
          <?php if ($page > 1): ?>
            <a href="<?php echo htmlspecialchars($makeServicesUrl(array_merge($baseQueryParams, ['page' => 1])), ENT_QUOTES); ?>" class="pagination-btn">← First</a>
            <a href="<?php echo htmlspecialchars($makeServicesUrl(array_merge($baseQueryParams, ['page' => $page - 1])), ENT_QUOTES); ?>" class="pagination-btn">← Previous</a>
          <?php endif; ?>

          <!-- Page Numbers -->
          <?php 
          $start = max(1, $page - 2);
          $end = min($totalPages, $page + 2);
          
          if ($start > 1): ?>
            <span class="services-ellipsis">...</span>
          <?php endif;
          
          for ($i = $start; $i <= $end; $i++):
            $isActive = ($i == $page) ? 'active' : '';
          ?>
            <a href="<?php echo htmlspecialchars($makeServicesUrl(array_merge($baseQueryParams, ['page' => $i])), ENT_QUOTES); ?>" class="pagination-btn <?php echo $isActive; ?>">
              <?php echo $i; ?>
            </a>
          <?php 
          endfor;
          
          if ($end < $totalPages): ?>
            <span class="services-ellipsis">...</span>
          <?php endif;
          
          if ($page < $totalPages): ?>
            <a href="<?php echo htmlspecialchars($makeServicesUrl(array_merge($baseQueryParams, ['page' => $page + 1])), ENT_QUOTES); ?>" class="pagination-btn">Next →</a>
            <a href="<?php echo htmlspecialchars($makeServicesUrl(array_merge($baseQueryParams, ['page' => $totalPages])), ENT_QUOTES); ?>" class="pagination-btn">Last →</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <!-- No Services Found -->
      <div class="no-services">
        <h3>No Services Found</h3>
        <p>Try adjusting your search or filters.</p>
        <a href="<?php echo htmlspecialchars(url('/services'), ENT_QUOTES); ?>" class="services-clear-link">Clear Filters</a>
      </div>
    <?php endif; ?>

    <!-- Info Section -->
    <div class="services-info">
      <h3>Browse by Service Type</h3>
      <p>Our comprehensive directory features services across multiple industries:</p>
      <div class="services-info-grid">
        <div>
          <h4>Construction & Real Estate</h4>
          <p>Project management, design, inspection, renovation</p>
        </div>
        <div>
          <h4>Business Services</h4>
          <p>Consulting, accounting, legal, human resources</p>
        </div>
        <div>
          <h4>Technology & IT</h4>
          <p>Software development, web design, system integration</p>
        </div>
        <div>
          <h4>Transportation & Logistics</h4>
          <p>Shipping, delivery, warehouse, freight forwarding</p>
        </div>
      </div>
    </div>

  </div>
</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
