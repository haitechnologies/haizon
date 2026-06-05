<?php
/**
 * AMP Page: Business Listings
 * Route: /listings/amp
 * 
 * Mobile-optimized business listings with search/filter
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/frontend/CompanyCategories.php';

// Get filter parameters
$keyword = $_GET['keyword'] ?? '';
$category = $_GET['category'] ?? '';
$emirate = $_GET['emirate'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Get all categories
$categoriesModel = new CompanyCategories($conn);
$categories = $categoriesModel->getAll(['order_by' => 'name ASC']);
$categoryBySlug = [];
foreach ($categories as &$cat) {
    if (!isset($cat['name']) && isset($cat['category'])) {
        $cat['name'] = $cat['category'];
    }
    if (!empty($cat['slug'])) {
        $categoryBySlug[$cat['slug']] = (int)$cat['id'];
    }
}
unset($cat);

// Build query
$whereClauses = ['comp.publish = 1', 'comp.is_active = 1'];
$params = [];
$types = '';

if (!empty($keyword)) {
    $whereClauses[] = "(comp.company_name LIKE ? OR comp.location LIKE ? OR comp.city LIKE ?)";
    $searchTerm = '%' . $keyword . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= 'sss';
}

if (!empty($category) && isset($categoryBySlug[$category])) {
    $whereClauses[] = "comp.primary_category_id = ?";
    $params[] = $categoryBySlug[$category];
    $types .= 'i';
}

if (!empty($emirate)) {
    $emirateLabel = str_replace('-', ' ', strtolower($emirate));
    $whereClauses[] = "(LOWER(comp.state) = ? OR LOWER(comp.city) LIKE ?)";
    $params[] = $emirateLabel;
    $params[] = '%' . $emirateLabel . '%';
    $types .= 'ss';
}

$whereSQL = implode(' AND ', $whereClauses);

// Get total count
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

// Get companies
$query = "
    SELECT 
        comp.id,
        comp.company_name,
        comp.slug,
        comp.company_profile AS description,
        comp.city,
        comp.telephone AS phone,
      comp.email AS email,
        comp.verified,
        cat.name AS category_name
    FROM `" . DB::COMPANIES . "` comp
    LEFT JOIN `" . DB::CATEGORIES . "` cat ON cat.id = comp.primary_category_id
    WHERE $whereSQL
    ORDER BY comp.verified DESC, comp.id DESC
    LIMIT ? OFFSET ?
";

$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$companies = [];
while ($row = $result->fetch_assoc()) {
    $companies[] = $row;
}
$stmt->close();

$totalPages = max(1, ceil($totalCompanies / $perPage));

// UAE Emirates list
$emirates = [
    'abu-dhabi' => 'Abu Dhabi',
    'dubai' => 'Dubai',
    'sharjah' => 'Sharjah',
    'ajman' => 'Ajman',
    'umm-al-quwain' => 'Umm Al Quwain',
    'ras-al-khaimah' => 'Ras Al Khaimah',
    'fujairah' => 'Fujairah'
];

// Page meta
$pageTitle = "Business Directory - Find Companies in UAE | HAIPULSE";
$pageDescription = "Search and discover businesses across the United Arab Emirates. Browse categories or search by keyword.";
$pageKeywords = "UAE business directory, companies in UAE, business listings, UAE services, find businesses";
$canonicalUrl = url('/listings');
$pageUrl = url('/listings/amp');

// Open Graph
$ogTitle = $pageTitle;
$ogDescription = $pageDescription;
$ogImage = getFullUrl('/assets/images/brand/logo.png');
$ogType = 'website';

// Twitter Card
$twitterCard = 'summary';
$twitterTitle = $pageTitle;
$twitterDescription = $pageDescription;
$twitterImage = $ogImage;

// Schema.org ItemList
$schemaItems = [];
$position = 1;
foreach ($companies as $comp) {
    $schemaItems[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'item' => [
            '@type' => 'LocalBusiness',
            'name' => $comp['company_name'],
            'url' => url('/company/' . urlencode($comp['slug']))
        ]
    ];
}

$schemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'itemListElement' => $schemaItems
  ];

  // Add breadcrumb schema
  $breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Home',
        'item' => getFullUrl('/')
      ],
      [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => 'Business Directory',
        'item' => $pageUrl
      ]
    ]
  ];

  // Combine schemas
  $schemaData = [
    $schemaData,
    $breadcrumbSchema
  ];

// AMP components
$ampComponents = [
  ['name' => 'amp-form', 'src' => 'https://cdn.ampproject.org/v0/amp-form-0.1.js'],
  ['name' => 'amp-bind', 'src' => 'https://cdn.ampproject.org/v0/amp-bind-0.1.js']
];

$pageCustomCss = <<<'CSS'
  /* Listings Styles */
  .page-header {
    background: linear-gradient(135deg, #0f4ad8 0%, #1e5fd8 100%);
    color: white;
    padding: 32px 16px;
    text-align: center;
    margin-bottom: 24px;
  }
  
  .page-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 8px;
  }
  
  .page-subtitle {
    font-size: 1rem;
    opacity: 0.9;
  }
  
  .search-form {
    background: white;
    padding: 16px;
    margin-bottom: 16px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }
  
  .search-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    margin-bottom: 12px;
  }
  
  .filter-label {
    display: block;
    font-weight: 600;
    margin: 12px 0 6px;
    color: #333;
    font-size: 0.9rem;
  }
  
  .filter-select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.95rem;
  }
  
  .search-button {
    width: 100%;
    padding: 12px;
    background: #1e5fd8;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    margin-top: 12px;
    cursor: pointer;
  }
  
  .results-info {
    padding: 12px 16px;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 16px;
    font-size: 0.9rem;
    color: #666;
  }
  
  .company-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    padding: 0 16px;
  }
  
  .company-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 16px;
  }
  
  .company-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
  }
  
  .company-name {
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
    margin: 0;
  }
  
  .company-name a {
    color: inherit;
    text-decoration: none;
  }
  
  .verified-badge {
    background: #4caf50;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
  }
  
  .company-category {
    display: inline-block;
    background: #e3f2fd;
    color: #1e5fd8;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 10px;
  }
  
  .company-description {
    font-size: 0.9rem;
    color: #666;
    line-height: 1.6;
    margin-bottom: 12px;
  }
  
  .company-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 16px;
    font-size: 0.85rem;
    color: #888;
    border-top: 1px solid #eee;
    padding-top: 12px;
  }
  
  .company-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .contact-meta-item {
    width: 100%;
    align-items: flex-start;
    gap: 8px;
  }

  .contact-meta-label {
    font-weight: 600;
    color: #6e7f95;
    min-width: 56px;
    line-height: 1.8;
  }

  .contact-reveal-group {
    display: block;
    width: 100%;
  }

  .contact-reveal-btn {
    width: 100%;
    margin: 0;
    border: 1px solid #d0d7e2;
    background: #f8fafc;
    color: #1e5fd8;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 6px 10px;
    border-radius: 6px;
    line-height: 1.2;
    cursor: pointer;
    text-align: left;
  }

  .contact-reveal-panel {
    padding-top: 8px;
  }

  .contact-reveal-link {
    display: inline-block;
    font-size: 0.82rem;
    font-weight: 600;
    color: #0f4ad8;
    text-decoration: none;
    padding: 6px 10px;
    border-radius: 6px;
    background: #eef4ff;
    border: 1px solid #d7e4ff;
  }
  
  .pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin: 32px 16px;
    flex-wrap: wrap;
  }
  
  .pagination a,
  .pagination span {
    display: inline-block;
    padding: 8px 14px;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    font-weight: 500;
  }
  
  .pagination a:hover {
    background: #f8f9fa;
  }
  
  .pagination .current {
    background: #1e5fd8;
    color: white;
    border-color: #1e5fd8;
  }
  
  .pagination .disabled {
    opacity: 0.4;
    pointer-events: none;
  }
  
  .no-results {
    text-align: center;
    padding: 48px 16px;
    color: #666;
  }
  
  .view-regular {
    text-align: center;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 6px;
    margin: 16px;
  }
  
  .view-regular a {
    color: #1e5fd8;
    text-decoration: none;
    font-weight: 500;
  }
CSS;

include __DIR__ . '/amp-header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <h1 class="page-title">Business Directory</h1>
  <div class="page-subtitle">Discover trusted companies across all UAE emirates</div>
</div>

<!-- Search & Filters -->
<div class="search-form">
  <form method="GET" action="<?= url('/listings/amp') ?>">
    <input 
      type="text" 
      name="keyword" 
      class="search-input" 
      placeholder="Search companies..." 
      value="<?= htmlspecialchars($keyword, ENT_QUOTES) ?>">
    
    <label class="filter-label">Category</label>
    <select name="category" class="filter-select">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
      <option value="<?= htmlspecialchars($cat['slug'], ENT_QUOTES) ?>" 
              <?= $category === $cat['slug'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>
      </option>
      <?php endforeach; ?>
    </select>
    
    <label class="filter-label">Emirate</label>
    <select name="emirate" class="filter-select">
      <option value="">All Emirates</option>
      <?php foreach ($emirates as $slug => $name): ?>
      <option value="<?= $slug ?>" <?= $emirate === $slug ? 'selected' : '' ?>>
        <?= $name ?>
      </option>
      <?php endforeach; ?>
    </select>
    
    <button type="submit" class="search-button">Search Directory</button>
  </form>
</div>

<!-- Results Info -->
<?php if ($keyword || $category || $emirate): ?>
<div class="results-info">
  Found <?= number_format($totalCompanies) ?> result<?= $totalCompanies !== 1 ? 's' : '' ?>
  <?php if ($keyword): ?>
    for "<?= htmlspecialchars($keyword, ENT_QUOTES) ?>"
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Company Listings -->
<?php if (empty($companies)): ?>
<div class="no-results">
  <h3>No companies found</h3>
  <p>Try adjusting your search criteria or browse all categories.</p>
</div>
<?php else: ?>
<div class="company-grid">
  <?php foreach ($companies as $company): ?>
  <?php $ampCompanyName = display_text($company['company_name'] ?? ''); ?>
  <div class="company-card">
    <div class="company-header-row">
      <h2 class="company-name">
        <a href="<?= url('/company/' . urlencode($company['slug']) . '/amp') ?>">
          <?= htmlspecialchars($ampCompanyName, ENT_QUOTES) ?>
        </a>
      </h2>
      <?php if ($company['verified']): ?>
      <span class="verified-badge">Verified</span>
      <?php endif; ?>
    </div>
    
    <?php if (!empty($company['category_name'])): ?>
    <div class="company-category">
      <?= htmlspecialchars($company['category_name'], ENT_QUOTES) ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($company['description'])): ?>
    <div class="company-description">
      <?= htmlspecialchars(truncateText(strip_tags($company['description']), 150), ENT_QUOTES) ?>
    </div>
    <?php endif; ?>
    
    <div class="company-meta">
      <?php if (!empty($company['city'])): ?>
      <div class="company-meta-item">
        <span>Location:</span>
        <span><?= htmlspecialchars($company['city'], ENT_QUOTES) ?></span>
      </div>
      <?php endif; ?>
      
      <?php if (!empty($company['phone'])): ?>
      <div class="company-meta-item contact-meta-item">
        <amp-state id="contactState<?php echo (int)$company['id']; ?>">
          <script type="application/json">{"phone": false, "email": false}</script>
        </amp-state>
        <div class="contact-reveal-group">
          <button
            type="button"
            class="contact-reveal-btn"
            on="tap:AMP.setState({contactState<?php echo (int)$company['id']; ?>: {phone: !contactState<?php echo (int)$company['id']; ?>.phone}})">
            Click to view phone
          </button>
          <div class="contact-reveal-panel" [hidden]="!contactState<?php echo (int)$company['id']; ?>.phone" hidden>
            <a class="contact-reveal-link" href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', (string)$company['phone']), ENT_QUOTES) ?>"><?= htmlspecialchars($company['phone'], ENT_QUOTES) ?></a>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($company['email'])): ?>
      <div class="company-meta-item contact-meta-item">
        <?php if (empty($company['phone'])): ?>
        <amp-state id="contactState<?php echo (int)$company['id']; ?>">
          <script type="application/json">{"phone": false, "email": false}</script>
        </amp-state>
        <?php endif; ?>
        <div class="contact-reveal-group">
          <button
            type="button"
            class="contact-reveal-btn"
            on="tap:AMP.setState({contactState<?php echo (int)$company['id']; ?>: {email: !contactState<?php echo (int)$company['id']; ?>.email}})">
            Click to view email
          </button>
          <div class="contact-reveal-panel" [hidden]="!contactState<?php echo (int)$company['id']; ?>.email" hidden>
            <a class="contact-reveal-link" href="mailto:<?= htmlspecialchars((string)$company['email'], ENT_QUOTES) ?>"><?= htmlspecialchars((string)$company['email'], ENT_QUOTES) ?></a>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
  <?php if ($page > 1): ?>
  <a href="<?= url('/listings/amp?page=' . ($page - 1) . ($keyword ? '&keyword=' . urlencode($keyword) : '') . ($category ? '&category=' . urlencode($category) : '') . ($emirate ? '&emirate=' . urlencode($emirate) : '')) ?>">
    â† Previous
  </a>
  <?php else: ?>
  <span class="disabled">â† Previous</span>
  <?php endif; ?>
  
  <?php
  $startPage = max(1, $page - 2);
  $endPage = min($totalPages, $page + 2);
  
  for ($i = $startPage; $i <= $endPage; $i++):
    if ($i == $page):
  ?>
  <span class="current"><?= $i ?></span>
  <?php else: ?>
  <a href="<?= url('/listings/amp?page=' . $i . ($keyword ? '&keyword=' . urlencode($keyword) : '') . ($category ? '&category=' . urlencode($category) : '') . ($emirate ? '&emirate=' . urlencode($emirate) : '')) ?>">
    <?= $i ?>
  </a>
  <?php 
    endif;
  endfor;
  ?>
  
  <?php if ($page < $totalPages): ?>
  <a href="<?= url('/listings/amp?page=' . ($page + 1) . ($keyword ? '&keyword=' . urlencode($keyword) : '') . ($category ? '&category=' . urlencode($category) : '') . ($emirate ? '&emirate=' . urlencode($emirate) : '')) ?>">
    Next â†’
  </a>
  <?php else: ?>
  <span class="disabled">Next â†’</span>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="context-nav">
  <div class="context-nav-title">Explore more on AMP</div>
  <div class="context-nav-links">
    <a href="<?= url('/trade/hs-codes/amp') ?>" class="context-nav-link">Browse HS Codes</a>
    <a href="<?= url('/blog/amp') ?>" class="context-nav-link">Read Business Blog</a>
    <a href="<?= url('/about/amp') ?>" class="context-nav-link">About HAIPULSE</a>
    <a href="<?= url('/contact/amp') ?>" class="context-nav-link">Contact Team</a>
  </div>
</div>

<!-- View Full Version -->
<div class="view-regular">
  <a href="<?= $canonicalUrl ?>">View Full Website Version</a>
</div>

<?php include __DIR__ . '/amp-footer.php'; ?>

