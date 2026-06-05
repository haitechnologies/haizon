<?php
/**
 * Page: Blog Listing (PROFESSIONAL REDESIGN)
 * Route: /blog or /pages/blog.php
 * Description: Display all published blog posts with advanced features
 * Author: Development Team
 * Updated: March 1, 2026
 */

// ============================================
// SECTION 1: DEPENDENCIES & INITIALIZATION
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/Blogs.php';

// Ensure basePath is set for URL generation (should be set by index.php router)
if (!isset($GLOBALS['basePath'])) {
    $GLOBALS['basePath'] = dirname($_SERVER['SCRIPT_NAME']);
    $GLOBALS['basePath'] = str_replace('\\', '/', $GLOBALS['basePath']);
    $GLOBALS['basePath'] = $GLOBALS['basePath'] === '/' ? '' : rtrim($GLOBALS['basePath'], '/');
}

// ============================================
// SECTION 2: HANDLE BACKWARD-COMPATIBLE REDIRECTS
// ============================================
// Redirect old query-param URLs to clean URLs for SEO
if (isset($_GET['category']) && !isset($_GET['page']) && !isset($_GET['sort']) && !isset($_GET['tag'])) {
    $categoryId = intval($_GET['category']);
    if ($categoryId > 0) {
        // Get category slug from ID
        $catQuery = "SELECT slug FROM " . DB::BLOG_CATEGORIES . " WHERE id = ? LIMIT 1";
        $catStmt = $conn->prepare($catQuery);
        $catStmt->bind_param("i", $categoryId);
        $catStmt->execute();
        $catResult = $catStmt->get_result();
        $catRow = $catResult->fetch_assoc();
        $catStmt->close();
        
        if ($catRow) {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/blog/category/' . urlencode($catRow['slug'])));
            exit;
        }
    }
}

// ============================================
// SECTION 3: GET REQUEST PARAMETERS & FILTERS
// ============================================
$currentPage = intval($_GET['page'] ?? 1);
$categoryId = intval($_GET['category'] ?? 0);
$searchTerm = ''; // Search removed - no search functionality
$sortBy = $_GET['sort'] ?? 'latest'; // latest, popular, oldest
$viewMode = 'grid'; // Force grid view - list view removed from UI
$itemsPerPage = 12;
$offset = ($currentPage - 1) * $itemsPerPage;

// ============================================
// SECTION 4: INITIALIZE MODEL & BUILD FILTERS
// ============================================
$Blogs = new Blogs($conn);

// Map sort options to SQL
$orderByMap = [
    'latest' => 'created_at DESC',
    'oldest' => 'created_at ASC',
    'popular' => 'views DESC',
    'title' => 'title ASC'
];
$orderBy = $orderByMap[$sortBy] ?? 'created_at DESC';

// Build filter options
$filterOptions = [
    'category_id' => $categoryId > 0 ? $categoryId : null,
    'search' => !empty($searchTerm) ? $searchTerm : null,
    'limit' => $itemsPerPage,
    'offset' => $offset,
    'order_by' => $orderBy
];

// Get blogs for current page
$blogs = $Blogs->getAll($filterOptions);

// Get total count for pagination
$totalBlogs = $Blogs->getCount([
    'category_id' => $categoryId > 0 ? $categoryId : null,
    'search' => !empty($searchTerm) ? $searchTerm : null
]);
$totalPages = max(1, ceil($totalBlogs / $itemsPerPage));

// Clamp current page
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
if ($currentPage < 1) {
    $currentPage = 1;
}

// Get current path for pagination
$currentPath = strtok($_SERVER['REQUEST_URI'], '?');
$currentPath = str_replace($GLOBALS['basePath'] ?? '', '', $currentPath);
$currentPath = ltrim($currentPath, '/');

// Build query string helper
function buildBlogQueryString($overrides = []) {
    global $categoryId, $searchTerm, $sortBy, $viewMode;
    $params = array_merge([
        'category' => $categoryId,
        'q' => $searchTerm,
        'sort' => $sortBy,
        'view' => $viewMode
    ], $overrides);
    
    // Remove empty values (including null, empty string, 0, '0')
    $params = array_filter($params, function($v, $k) { 
        // Filter out empty values
        if ($v === '' || $v === '0' || $v === 0 || $v === null) {
            return false;
        }
        // Filter out page=1 (default page, no need in URL)
        if ($k === 'page' && ($v === 1 || $v === '1')) {
            return false;
        }
        return true;
    }, ARRAY_FILTER_USE_BOTH);
    
    return http_build_query($params);
}

// Build blog URL with query string
function buildBlogUrl($overrides = []) {
    $queryString = buildBlogQueryString($overrides);
    $baseUrl = url('/blog');
    return $queryString ? ($baseUrl . '?' . $queryString) : $baseUrl;
}

// ============================================
// SECTION 5: GET CATEGORIES & FEATURED CONTENT
// ============================================
$categoriesQuery = "SELECT id, name, slug, 
    (SELECT COUNT(*) FROM " . DB::BLOGS . " WHERE category_id = " . DB::BLOG_CATEGORIES . ".id AND publish = 1) as post_count 
    FROM " . DB::BLOG_CATEGORIES . " 
    WHERE status = 1 
    ORDER BY name ASC";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

// Get featured blog (most recent or most viewed)
$featuredBlog = $Blogs->getAll([
    'limit' => 1,
    'offset' => 0,
    'order_by' => 'views DESC'
]);
$featuredBlog = !empty($featuredBlog) ? $featuredBlog[0] : null;

// Get recent blogs for sidebar
$recentBlogs = $Blogs->getAll([
    'limit' => 5,
    'offset' => 0,
    'order_by' => 'created_at DESC'
]);

// ============================================
// SECTION 5: PAGE METADATA
// ============================================
$pageTitle = 'Blog & Articles - UAE Business Insights | HAIPULSE';
if (!empty($searchTerm)) {
    $pageTitle = 'Search Results: ' . htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') . ' | Blog';
} elseif ($categoryId > 0) {
    foreach ($categories as $cat) {
        if ($cat['id'] == $categoryId) {
            $pageTitle = htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') . ' Articles | Blog';
            break;
        }
    }
}
$pageDescription = 'Read articles about business, trade, and the UAE market. Industry insights, guides, and news for entrepreneurs and business owners.';
$ampHtmlUrl = url('/blog/amp');
$bodyClass = 'page-blog';

// Result hint
$resultHint = '';
if ($totalBlogs > 0) {
    $start = ($currentPage - 1) * $itemsPerPage + 1;
    $end = min($currentPage * $itemsPerPage, $totalBlogs);
    $resultHint = "Showing $start-$end of " . number_format($totalBlogs) . " articles";
} else {
    $resultHint = 'No articles found';
}

// Generate JSON-LD structured data for rich results
if (!empty($blogs)) {
    // ItemList schema for blog listing
    $jsonLdSchema = generateItemListSchema(
        $blogs,
        'Blog Articles - UAE Business Insights',
        'Latest articles, guides, and insights about doing business in UAE'
    );
    
    // Add breadcrumb schema
    $breadcrumbs = [
        ['name' => 'Home', 'url' => getFullUrl('/')],
        ['name' => 'Blog', 'url' => getFullUrl('/blog')]
    ];
    if ($categoryId > 0) {
        foreach ($categories as $cat) {
            if ($cat['id'] == $categoryId) {
                $breadcrumbs[] = [
                    'name' => $cat['name'],
                    'url' => getFullUrl('/blog?category=' . $cat['id'])
                ];
                break;
            }
        }
    }
    $jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);
}
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

<style>
/* Professional Blog Page Redesign */

/* Hero Section */
.blog-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1a2749 100%);
    color: white;
    padding: 72px 0 48px;
    margin-bottom: 48px;
    position: relative;
    overflow: hidden;
}

.blog-hero::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.blog-hero::after {
    content: '';
    position: absolute;
    bottom: -100px;
    left: 50%;
    transform: translateX(-50%);
    width: 600px;
    height: 300px;
    background: radial-gradient(circle, rgba(102, 126, 234, 0.05) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.blog-hero-content {
    text-align: center;
    max-width: 700px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

.blog-hero h1 {
    margin: 0 0 16px 0;
    font-size: clamp(2.5rem, 5vw, 3.2rem);
    font-weight: 700;
    letter-spacing: -0.5px;
}

.blog-hero p {
    margin: 0;
    font-size: 1.1rem;
    opacity: 0.95;
  padding: 56px 0 38px;
  margin: 10px auto 30px;
  max-width: 1200px;
  width: calc(100% - 24px);
  border-radius: 20px;
    font-weight: 400;
}

/* Remove Search Box Styles - No longer needed */
.blog-search-box {
    display: none;
}

/* Featured Section */
.blog-featured-section {
    margin-bottom: 56px;
}

.blog-featured-section h2 {
    margin: 0 0 24px 0;
    font-size: 1.4rem;
    font-weight: 600;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.95rem;
    color: #667eea;
    font-weight: 700;
}

.blog-featured-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
    display: grid;
    grid-template-columns: 1.3fr 1fr;
    gap: 0;
    transition: all 0.4s cubic-bezier(0.23, 1, 0.320, 1);
    border: 1px solid #e2e8f0;
}

.blog-featured-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
    border-color: #cbd5e1;
}

.blog-featured-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    min-height: 340px;
}

.blog-featured-content {
    padding: 44px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.blog-featured-content h3 {
    margin: 0 0 16px 0;
    font-size: 1.9rem;
    font-weight: 700;
    line-height: 1.3;
    color: #0f172a;
}

.blog-featured-content p {
    margin: 0 0 24px 0;
    color: #475569;
    line-height: 1.7;
    font-size: 1rem;
}

/* Toolbar */
.blog-toolbar {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 32px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.blog-toolbar-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.blog-filters {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.blog-filters label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #334155;
    white-space: nowrap;
}

.blog-filter-select {
    padding: 8px 32px 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: white url('data:image/svg+xml;charset=utf8,<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>') no-repeat right 8px center;
    background-size: 18px;
    padding-right: 32px;
    color: #334155;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}

.blog-filter-select:hover, .blog-filter-select:focus {
    border-color: #cbd5e1;
    background-color: #f8fafc;
    outline: none;
}

.blog-filter-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* View Toggle - Hidden since we use grid only */
.view-toggle {
    display: none;
}

/* Grid Layout */
.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 28px;
    margin-bottom: 40px;
}

/* Blog Card */
.blog-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
    display: flex;
    flex-direction: column;
    height: 100%;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.blog-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
    border-color: #cbd5e1;
}

.blog-card-image {
    width: 100%;
    height: 220px;
    object-fit: cover;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    transition: transform 0.4s ease;
}

.blog-card:hover .blog-card-image {
    transform: scale(1.05);
}

.blog-card-image-placeholder {
    width: 100%;
    height: 220px;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.5rem;
    opacity: 0.4;
}

.blog-card-body {
    padding: 24px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.blog-category-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    background: #f0f4ff;
    color: #667eea;
    margin-bottom: 12px;
    width: fit-content;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.blog-card-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 12px 0;
    line-height: 1.4;
    color: #0f172a;
}

.blog-card-title a {
    color: inherit;
    text-decoration: none;
    transition: color 0.2s;
}

.blog-card-title a:hover {
    color: #667eea;
}

.blog-card-excerpt {
    font-size: 0.95rem;
    color: #64748b;
    line-height: 1.6;
    margin-bottom: 16px;
    flex: 1;
}

.blog-card-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
    font-size: 0.85rem;
    color: #94a3b8;
}

.blog-card-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* List View - Hidden */
.blog-list {
    display: none;
}

.blog-card-list {
    display: none;
}

/* Breadcrumb */
.breadcrumb-blog {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 32px;
    font-size: 0.9rem;
}

.breadcrumb-blog a {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.breadcrumb-blog a:hover {
    color: #5568d3;
}

.breadcrumb-blog span {
    color: #cbd5e1;
}

/* Responsive */
@media (max-width: 992px) {
    .blog-featured-card {
        grid-template-columns: 1fr;
    }
    
    .blog-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .blog-featured-content {
        padding: 32px;
    }
}

@media (max-width: 768px) {
    .blog-hero {
    padding: 44px 0 30px;
    margin-bottom: 24px;
    width: calc(100% - 12px);
    border-radius: 14px;
    }
    
    .blog-hero h1 {
        font-size: 2rem;
    }
    
    .blog-hero p {
        font-size: 1rem;
    }
    
    .blog-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .blog-toolbar-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .blog-toolbar {
        padding: 16px;
    }
    
    .blog-filters {
        flex-direction: column;
    }
    
    .blog-featured-content {
        padding: 24px;
    }

    .blog-empty-state {
      padding: 20px 12px !important;
    }

    .blog-pagination-card {
      padding: 12px !important;
      margin-top: 16px !important;
    }

    .blog-cta-card {
      margin-top: 20px !important;
      padding: 18px 12px !important;
    }

    .blog-cta-card h2 {
      font-size: 1.25rem !important;
      margin-bottom: 10px !important;
    }

    .blog-cta-card p {
      margin-bottom: 12px !important;
      font-size: 0.92rem !important;
    }
}
</style>

<main id="main-content" class="section">
  <!-- Blog Hero -->
  <div class="blog-hero">
    <div class="container-narrow">
      <div class="blog-hero-content">
        <h1>Blog & Insights</h1>
        <p>Industry insights, business guides, and expert tips for UAE entrepreneurs and business professionals</p>
      </div>
    </div>
  </div>

  <div class="container-narrow">
    <!-- Breadcrumb -->
    <nav class="breadcrumb-blog" aria-label="Breadcrumb">
      <a href="<?php echo url('/'); ?>">Home</a>
      <span>â€º</span>
      <span aria-current="page">Blog</span>
    </nav>

    <?php if ($featuredBlog && empty($searchTerm) && empty($categoryId) && $currentPage == 1): ?>
      <div class="blog-featured-section">
        <h2>Featured Article</h2>
        <article class="blog-featured-card">
          <div class="blog-featured-content">
            <span class="blog-category-badge">Featured</span>
            <h3>
              <a href="<?php echo url('/blog/' . urlencode($featuredBlog['slug'])); ?>" 
                class="blog-title-link">
                <?php echo htmlspecialchars($featuredBlog['title'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
            </h3>
            <p>
              <?php 
              $excerpt = $featuredBlog['excerpt'] ?? $featuredBlog['content'];
              if (!empty($excerpt)) {
                  $text = strip_tags($excerpt);
                  echo htmlspecialchars(strlen($text) > 200 ? substr($text, 0, 200) . '...' : $text, ENT_QUOTES, 'UTF-8');
              }
              ?>
            </p>
            <div class="blog-featured-meta">
              <span>ðŸ“… <?php echo dd_($featuredBlog['created_at'], 'd M Y'); ?></span>
              <span>ðŸ‘ <?php echo number_format($featuredBlog['views'] ?? 0); ?> views</span>
            </div>
            <a href="<?php echo url('/blog/' . urlencode($featuredBlog['slug'])); ?>" 
              class="btn-ui btn-primary-ui blog-featured-cta">
              Read Full Article â†’
            </a>
          </div>
        </article>
      </div>
    <?php endif; ?>

    <div class="main-content-grid blog-main-grid">
      <div>
        <!-- Toolbar -->
        <div class="blog-toolbar">
          <form method="GET" action="" id="blog-filter-form">
            <div class="blog-toolbar-row">
              <div class="blog-filters">
                <!-- Category Filter -->
                <label>Category</label>
                <select name="category" class="blog-filter-select" onchange="document.getElementById('blog-filter-form').submit()">
                  <option value="0">All Categories</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo $cat['post_count']; ?>)
                    </option>
                  <?php endforeach; ?>
                </select>

                <!-- Sort Filter -->
                <label>Sort By</label>
                <select name="sort" class="blog-filter-select" onchange="document.getElementById('blog-filter-form').submit()">
                  <option value="latest" <?php echo $sortBy === 'latest' ? 'selected' : ''; ?>>Latest First</option>
                  <option value="popular" <?php echo $sortBy === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                  <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                  <option value="title" <?php echo $sortBy === 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                </select>
              </div>
            </div>
          </form>
        </div>

        <!-- Results Info -->
        <div class="blog-result-hint">
          <?php echo $resultHint; ?>
        </div>

        <!-- Blog Articles -->
        <?php if (empty($blogs)): ?>
          <div class="card-ui blog-empty-state">
            <div class="blog-empty-icon">ðŸ“</div>
            <h3 class="blog-empty-title">No Articles Found</h3>
            <p class="blog-empty-text">
              <?php if ($searchTerm || $categoryId): ?>
                Try adjusting your filters or
                <a href="<?php echo url('/blog'); ?>" class="blog-empty-link">browse all articles</a>
              <?php else: ?>
                No articles have been published yet. Check back soon!
              <?php endif; ?>
            </p>
          </div>
        <?php else: ?>
          
          <!-- Grid View -->
          <div class="blog-grid">
            <?php foreach ($blogs as $blog): 
              $blogUrl = url('/blog/' . urlencode($blog['slug']));
              $catName = '';
              foreach ($categories as $cat) {
                  if ($cat['id'] == $blog['category_id']) {
                      $catName = htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8');
                      break;
                  }
              }
            ?>
              <article class="blog-card">
                <div class="blog-card-body">
                  <?php if ($catName): ?>
                    <span class="blog-category-badge"><?php echo $catName; ?></span>
                  <?php endif; ?>
                  
                  <h3 class="blog-card-title">
                    <a href="<?php echo $blogUrl; ?>">
                      <?php echo htmlspecialchars($blog['title'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                  </h3>
                  
                  <div class="blog-card-excerpt">
                    <?php 
                    $excerpt = $blog['excerpt'] ?? $blog['content'];
                    if (!empty($excerpt)) {
                        $text = strip_tags($excerpt);
                        echo htmlspecialchars(strlen($text) > 140 ? substr($text, 0, 140) . '...' : $text, ENT_QUOTES, 'UTF-8');
                    }
                    ?>
                  </div>
                  
                  <div class="blog-card-meta">
                    <div class="blog-card-meta-item">
                      <span>ðŸ“…</span>
                      <span><?php echo dd_($blog['created_at'], 'd M Y'); ?></span>
                    </div>
                    <div class="blog-card-meta-item">
                      <span>ðŸ‘</span>
                      <span><?php echo number_format($blog['views'] ?? 0); ?></span>
                    </div>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>

          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <nav class="card-ui blog-pagination-card" aria-label="Blog pagination">
              <div class="pagination-row">
                <div>
                  <?php if ($currentPage > 1): ?>
                    <a href="<?php echo buildBlogUrl(['page' => $currentPage - 1]); ?>" class="btn-ui btn-light-ui">â† Previous</a>
                  <?php else: ?>
                    <button class="btn-ui btn-light-ui btn-disabled" disabled>â† Previous</button>
                  <?php endif; ?>
                </div>

                <div class="pagination-nums">
                  <?php 
                  $startPage = max(1, $currentPage - 2);
                  $endPage = min($totalPages, $currentPage + 2);
                  
                  if ($startPage > 1): ?>
                    <a href="<?php echo buildBlogUrl(['page' => 1]); ?>" class="btn-ui btn-light-ui pagination-btn">1</a>
                    <?php if ($startPage > 2): ?>
                      <span class="pagination-ellipsis">...</span>
                    <?php endif; ?>
                  <?php endif; ?>

                  <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i === $currentPage): ?>
                      <button class="btn-ui btn-primary-ui pagination-btn btn-page-current" disabled aria-current="page">
                        <?php echo $i; ?>
                      </button>
                    <?php else: ?>
                      <a href="<?php echo buildBlogUrl(['page' => $i]); ?>" class="btn-ui btn-light-ui pagination-btn">
                        <?php echo $i; ?>
                      </a>
                    <?php endif; ?>
                  <?php endfor; ?>

                  <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                      <span class="pagination-ellipsis">...</span>
                    <?php endif; ?>
                    <a href="<?php echo buildBlogUrl(['page' => $totalPages]); ?>" class="btn-ui btn-light-ui pagination-btn">
                      <?php echo $totalPages; ?>
                    </a>
                  <?php endif; ?>
               </div>

                <div>
                  <?php if ($currentPage < $totalPages): ?>
                    <a href="<?php echo buildBlogUrl(['page' => $currentPage + 1]); ?>" class="btn-ui btn-light-ui">Next â†’</a>
                  <?php else: ?>
                    <button class="btn-ui btn-light-ui btn-disabled" disabled>Next â†’</button>
                  <?php endif; ?>
                </div>
              </div>

              <div class="pagination-info">
                Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>
              </div>
            </nav>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- CTA Section -->
    <?php if (!empty($blogs)): ?>
      <div class="card-ui blog-cta-card">
        <h2 class="blog-cta-title">
          Want to Contribute?
        </h2>
        <p class="blog-cta-text">
          Share your expertise with the UAE business community. We're always looking for quality content and industry insights.
        </p>
        <a href="<?php echo url('/contact'); ?>" class="btn-ui btn-primary-ui blog-cta-btn">
          Get in Touch
        </a>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

