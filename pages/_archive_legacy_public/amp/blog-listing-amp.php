<?php
/**
 * AMP Page: Blog Listing
 * Route: /blog/amp
 * 
 * Mobile-optimized blog listing page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/frontend/Blogs.php';

$blogsModel = new Blogs($conn);

// Get parameters
$currentPage = intval($_GET['page'] ?? 1);
$categoryId = intval($_GET['category'] ?? 0);
$sortBy = $_GET['sort'] ?? 'latest';
$itemsPerPage = 12;
$offset = ($currentPage - 1) * $itemsPerPage;

// Map sort options
$orderByMap = [
    'latest' => 'created_at DESC',
    'oldest' => 'created_at ASC',
    'popular' => 'views DESC'
];
$orderBy = $orderByMap[$sortBy] ?? 'created_at DESC';

// Build filter options
$filterOptions = [
    'category_id' => $categoryId > 0 ? $categoryId : null,
    'limit' => $itemsPerPage,
    'offset' => $offset,
    'order_by' => $orderBy
];

// Get blogs
$blogs = $blogsModel->getAll($filterOptions);

// Get total count for pagination
$totalBlogs = $blogsModel->getCount([
    'category_id' => $categoryId > 0 ? $categoryId : null
]);
$totalPages = max(1, ceil($totalBlogs / $itemsPerPage));

// Get categories for filter
$categoriesQuery = "SELECT id, name, slug FROM " . DB::BLOG_CATEGORIES . " WHERE status = 1 ORDER BY name ASC";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

// Get current category name if filtering
$categoryName = '';
if ($categoryId > 0) {
    $catQuery = "SELECT name FROM " . DB::BLOG_CATEGORIES . " WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($catQuery);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $catRow = $result->fetch_assoc();
    $stmt->close();
    if ($catRow) {
        $categoryName = $catRow['name'];
    }
}

// Page meta
$pageTitle = $categoryName 
  ? "Blog - {$categoryName} - HAIPULSE"
  : "Blog - Latest Business News & Articles - HAIPULSE";
    
$pageDescription = "Read the latest business news, articles, and insights from UAE. Stay updated with industry trends, tips, and business opportunities.";
$pageKeywords = "UAE business news, business articles, industry insights, business tips, UAE trends";
$canonicalUrl = url('/blog');
$pageUrl = url('/blog/amp');

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
foreach ($blogs as $blog) {
    $schemaItems[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'item' => [
            '@type' => 'BlogPosting',
            'headline' => $blog['title'],
            'url' => url('/blog/' . urlencode($blog['slug'])),
            'datePublished' => date('c', strtotime($blog['created_at'])),
            'author' => [
                '@type' => 'Person',
                'name' => $blog['author_name'] ?? 'UAE Business Directory'
            ]
        ]
    ];
}

$schemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'itemListElement' => $schemaItems
  ];

  // Add breadcrumb schema
  $breadcrumbItems = [
    [
      '@type' => 'ListItem',
      'position' => 1,
      'name' => 'Home',
      'item' => getFullUrl('/')
    ],
    [
      '@type' => 'ListItem',
      'position' => 2,
      'name' => 'Blog',
      'item' => getFullUrl('/blog')
    ]
  ];

  if ($categoryName) {
    $breadcrumbItems[] = [
      '@type' => 'ListItem',
      'position' => 3,
      'name' => $categoryName,
      'item' => $pageUrl
    ];
  }

  $breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $breadcrumbItems
  ];

  // Combine schemas
  $schemaData = [
    $schemaData,
    $breadcrumbSchema
  ];

// AMP components
$ampComponents = [
    ['name' => 'amp-img', 'src' => 'https://cdn.ampproject.org/v0/amp-img-0.1.js'],
    ['name' => 'amp-form', 'src' => 'https://cdn.ampproject.org/v0/amp-form-0.1.js']
];

$pageCustomCss = <<<'CSS'
  /* Blog Listing Styles */
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
  
  .filters-bar {
    background: white;
    padding: 16px;
    margin-bottom: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }
  
  .filter-label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
  }
  
  .filter-select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.95rem;
  }
  
  .blog-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    padding: 0 16px;
  }
  
  @media (min-width: 600px) {
    .blog-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  
  .blog-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: box-shadow 0.3s;
  }
  
  .blog-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
  
  .blog-card-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
  }
  
  .blog-card-body {
    padding: 16px;
  }
  
  .blog-card-category {
    display: inline-block;
    background: #e3f2fd;
    color: #1e5fd8;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 10px;
    text-transform: uppercase;
  }
  
  .blog-card-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
    line-height: 1.4;
  }
  
  .blog-card-title a {
    color: inherit;
    text-decoration: none;
  }
  
  .blog-card-excerpt {
    font-size: 0.9rem;
    color: #666;
    line-height: 1.6;
    margin-bottom: 12px;
  }
  
  .blog-card-meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    color: #888;
    border-top: 1px solid #eee;
    padding-top: 12px;
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
  <h1 class="page-title"><?= $categoryName ? htmlspecialchars($categoryName) : 'Blog' ?></h1>
  <div class="page-subtitle">Insights, trends, and practical updates for UAE businesses</div>
</div>

<!-- Filters -->
<div class="filters-bar">
  <form method="GET" action="<?= url('/blog/amp') ?>">
    <label class="filter-label">Category</label>
      <select name="category" class="filter-select">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
      <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>
      </option>
      <?php endforeach; ?>
    </select>
    
    <label class="filter-label" style="margin-top: 12px;">Sort By</label>
      <select name="sort" class="filter-select">
      <option value="latest" <?= $sortBy === 'latest' ? 'selected' : '' ?>>Latest First</option>
      <option value="popular" <?= $sortBy === 'popular' ? 'selected' : '' ?>>Most Popular</option>
      <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
    </select>
      <button type="submit" class="btn btn-primary" style="width:100%;margin-top:12px;">Apply Filters</button>
  </form>
</div>

<!-- Blog Grid -->
<?php if (empty($blogs)): ?>
<div class="no-results">
  <h3>No articles found</h3>
  <p>Try adjusting your filters or check back later for new content.</p>
</div>
<?php else: ?>
<div class="blog-grid">
  <?php foreach ($blogs as $blog): ?>
  <div class="blog-card">
    <?php if (!empty($blog['featured_image'])): ?>
    <amp-img
      src="<?= htmlspecialchars($blog['featured_image'], ENT_QUOTES) ?>"
      alt="<?= htmlspecialchars($blog['title'], ENT_QUOTES) ?>"
      width="400"
      height="200"
      layout="responsive"
      class="blog-card-image">
    </amp-img>
    <?php endif; ?>
    
    <div class="blog-card-body">
      <?php if (!empty($blog['category_name'])): ?>
      <div class="blog-card-category"><?= htmlspecialchars($blog['category_name'], ENT_QUOTES) ?></div>
      <?php endif; ?>
      
      <h2 class="blog-card-title">
        <a href="<?= url('/blog/' . urlencode($blog['slug']) . '/amp') ?>">
          <?= htmlspecialchars($blog['title'], ENT_QUOTES) ?>
        </a>
      </h2>
      
      <?php if (!empty($blog['excerpt'])): ?>
      <div class="blog-card-excerpt">
        <?= htmlspecialchars(truncateText(strip_tags($blog['excerpt']), 120), ENT_QUOTES) ?>
      </div>
      <?php endif; ?>
      
      <div class="blog-card-meta">
        <span>Date: <?= dd_($blog['created_at'], 'd M Y') ?></span>
        <?php if (!empty($blog['views'])): ?>
        <span>Views: <?= number_format($blog['views']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
  <?php if ($currentPage > 1): ?>
  <a href="<?= url('/blog/amp?page=' . ($currentPage - 1) . ($categoryId ? '&category=' . $categoryId : '') . ($sortBy !== 'latest' ? '&sort=' . $sortBy : '')) ?>">
    â† Previous
  </a>
  <?php else: ?>
  <span class="disabled">â† Previous</span>
  <?php endif; ?>
  
  <?php
  $startPage = max(1, $currentPage - 2);
  $endPage = min($totalPages, $currentPage + 2);
  
  for ($i = $startPage; $i <= $endPage; $i++):
    if ($i == $currentPage):
  ?>
  <span class="current"><?= $i ?></span>
  <?php else: ?>
  <a href="<?= url('/blog/amp?page=' . $i . ($categoryId ? '&category=' . $categoryId : '') . ($sortBy !== 'latest' ? '&sort=' . $sortBy : '')) ?>">
    <?= $i ?>
  </a>
  <?php 
    endif;
  endfor;
  ?>
  
  <?php if ($currentPage < $totalPages): ?>
  <a href="<?= url('/blog/amp?page=' . ($currentPage + 1) . ($categoryId ? '&category=' . $categoryId : '') . ($sortBy !== 'latest' ? '&sort=' . $sortBy : '')) ?>">
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
    <a href="<?= url('/listings/amp') ?>" class="context-nav-link">Browse Companies</a>
    <a href="<?= url('/trade/hs-codes/amp') ?>" class="context-nav-link">Find HS Codes</a>
    <a href="<?= url('/about/amp') ?>" class="context-nav-link">About HAIPULSE</a>
    <a href="<?= url('/contact/amp') ?>" class="context-nav-link">Contact Team</a>
  </div>
</div>

<!-- View Full Version -->
<div class="view-regular">
  <a href="<?= $canonicalUrl ?>">View Full Website Version</a>
</div>

<?php include __DIR__ . '/amp-footer.php'; ?>

