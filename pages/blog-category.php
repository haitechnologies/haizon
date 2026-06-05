<?php
/**
 * Page: Blog Category/Archive
 * Route: /blog/category/{slug}
 * Description: Display blog posts filtered by category
 * Updated: March 2, 2026
 */

// ============================================
// DEPENDENCIES
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
// GET ROUTE PARAMETERS
// ============================================
$categorySlug = $GLOBALS['route_params']['blog_category_slug'] ?? null;
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;

if (!$categorySlug) {
    header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/blog'));
    exit;
}

// ============================================
// LOAD DATA
// ============================================
$BlogsModel = new Blogs($conn);

// Find blog category by slug from blog categories table
$category = null;
$stmt = $conn->prepare("SELECT id, name, slug, status FROM `" . DB::BLOG_CATEGORIES . "` WHERE slug = ? LIMIT 1");
if ($stmt) {
  $stmt->bind_param('s', $categorySlug);
  $stmt->execute();
  $result = $stmt->get_result();
  $category = $result ? $result->fetch_assoc() : null;
  $stmt->close();
}

if (!$category) {
  // Legacy slug support: keep old public URLs working after taxonomy updates.
  $legacyCategoryRedirects = [
    'news' => 'legal-government'
  ];
  $requestedSlug = strtolower((string)$categorySlug);

  if (isset($legacyCategoryRedirects[$requestedSlug])) {
    $targetSlug = $legacyCategoryRedirects[$requestedSlug];
    $targetStmt = $conn->prepare("SELECT slug FROM `" . DB::BLOG_CATEGORIES . "` WHERE slug = ? AND status = 1 LIMIT 1");
    if ($targetStmt) {
      $targetStmt->bind_param('s', $targetSlug);
      $targetStmt->execute();
      $targetResult = $targetStmt->get_result();
      $targetCategory = $targetResult ? $targetResult->fetch_assoc() : null;
      $targetStmt->close();

      if (!empty($targetCategory['slug'])) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/blog/category/' . urlencode($targetCategory['slug'])));
        exit;
      }
    }

    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/blog'));
    exit;
  }

  http_response_code(404);
  $pageTitle = 'Blog Category Not Found';
  include __DIR__ . '/../pages/404.php';
  exit;
}

// Get all blogs in this category
$allBlogs = $BlogsModel->getAll([
    'limit' => 10000,
    'category_id' => $category['id']
]);

$categoryName = $category['name'];
$categoryBlogs = $allBlogs;

// Requested route-level simplification: remove article thumb/icon block.
$thumbHiddenCategorySlugs = ['employment-hr-management', 'ecommerce-retail'];
$hideCategoryThumbs = in_array(strtolower((string)$categorySlug), $thumbHiddenCategorySlugs, true);

// Paginate results
$totalBlogs = count($categoryBlogs);
$totalPages = max(1, ceil($totalBlogs / $perPage));
$offset = ($page - 1) * $perPage;
$paginatedBlogs = array_slice($categoryBlogs, $offset, $perPage);

// ============================================
// PAGE METADATA
// ============================================
$pageTitle = $categoryName . ' - UAE Business Blog';
$pageDescription = 'Read articles about ' . $categoryName . ' from HAIPULSE business directory. ' . $totalBlogs . ' articles available.';
$bodyClass = 'page-blog-category';

// Generate JSON-LD structured data for rich results
if (!empty($paginatedBlogs)) {
    // ItemList schema for blog category
    $jsonLdSchema = generateItemListSchema(
        $paginatedBlogs,
        $categoryName . ' Articles',
        'Latest articles about ' . $categoryName
    );
    
    // Add breadcrumb schema
    $breadcrumbs = [
        ['name' => 'Home', 'url' => getFullUrl('/')],
        ['name' => 'Blog', 'url' => getFullUrl('/blog')],
        ['name' => $categoryName, 'url' => getFullUrl('/blog/category/' . urlencode($category['slug']))]
    ];
    $jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);
}

include __DIR__ . '/../includes/layout/header.php';
?>

<main class="site-main">
  <!-- Hero Section -->
  <div class="blog-category-hero">
    <h1><?php echo htmlspecialchars($categoryName, ENT_QUOTES); ?></h1>
    <p>Articles and insights about <?php echo htmlspecialchars($categoryName, ENT_QUOTES); ?></p>
    <div class="blog-category-count"><?php echo $totalBlogs; ?> <?php echo ($totalBlogs == 1 ? 'Article' : 'Articles'); ?></div>
  </div>

  <!-- Blog Articles -->
  <div class="blog-container">
    <!-- Breadcrumb -->
    <div class="blog-breadcrumb">
      <a href="<?php echo url('/'); ?>">Home</a> / <a href="<?php echo url('/blog'); ?>">Blog</a> / <?php echo htmlspecialchars($categoryName, ENT_QUOTES); ?>
    </div>

    <?php if (!empty($paginatedBlogs)): ?>
      <div class="blog-articles">
        <?php foreach ($paginatedBlogs as $blog): ?>
          <a href="<?php echo url('/blog/' . urlencode($blog['slug'])); ?>" class="blog-article-card">
            <?php if (!$hideCategoryThumbs): ?>
              <div class="blog-image-wrapper">ðŸ“°</div>
            <?php endif; ?>
            <div class="blog-article-content">
              <h3 class="blog-article-title"><?php echo htmlspecialchars($blog['title'], ENT_QUOTES); ?></h3>
              
              <div class="blog-article-meta">
                <div class="blog-article-meta-item">
                  ðŸ“… <?php echo dd_($blog['created_at'], 'd M Y'); ?>
                </div>
                <div class="blog-article-meta-item">
                  ðŸ‘¤ <?php echo htmlspecialchars($blog['author_name'] ?? 'Admin', ENT_QUOTES); ?>
                </div>
                <?php if (!empty($blog['category'])): ?>
                  <div class="blog-article-meta-item">
                    ðŸ“‚ <?php echo htmlspecialchars($blog['category'], ENT_QUOTES); ?>
                  </div>
                <?php endif; ?>
              </div>

              <?php 
              // Extract excerpt from content
              $excerpt = strip_tags($blog['content']);
              $excerpt = substr($excerpt, 0, 150);
              if (strlen($blog['content']) > 150) $excerpt .= '...';
              ?>
              <div class="blog-article-excerpt">
                <?php echo htmlspecialchars($excerpt, ENT_QUOTES); ?>
              </div>

              <div class="blog-article-footer">
                <span class="blog-read-more">Read Article â†’</span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="blog-pagination">
          <?php if ($page > 1): ?>
            <a href="?page=1" class="pagination-btn">â† First</a>
            <a href="?page=<?php echo $page - 1; ?>" class="pagination-btn">â† Previous</a>
          <?php endif; ?>

          <?php 
          $start = max(1, $page - 2);
          $end = min($totalPages, $page + 2);
          
          for ($i = $start; $i <= $end; $i++):
            $isActive = ($i == $page) ? 'active' : '';
          ?>
            <a href="?page=<?php echo $i; ?>" class="pagination-btn <?php echo $isActive; ?>">
              <?php echo $i; ?>
            </a>
          <?php endfor;
          
          if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>" class="pagination-btn">Next â†’</a>
            <a href="?page=<?php echo $totalPages; ?>" class="pagination-btn">Last â†’</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <!-- No Articles Found -->
      <div class="no-articles">
        <h3>No Articles Found</h3>
        <p>Check back soon for articles about <?php echo htmlspecialchars($categoryName, ENT_QUOTES); ?>.</p>
        <a href="<?php echo htmlspecialchars(url('/blog'), ENT_QUOTES, 'UTF-8'); ?>" class="blogcat-link">View All Articles</a>
      </div>
    <?php endif; ?>

  </div>

</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

