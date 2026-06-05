<?php
/**
 * Blog Detail Page
 * 
 * Displays a single blog post with full content
 * Shows related posts and handles view tracking
 */

// Load dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/IpRateLimiter.php';
require_once __DIR__ . '/../classes/frontend/Blogs.php';
require_once __DIR__ . '/../includes/helpers.php';

// Ensure basePath is set for URL generation (should be set by index.php router)
if (!isset($GLOBALS['basePath'])) {
    $GLOBALS['basePath'] = dirname($_SERVER['SCRIPT_NAME']);
    $GLOBALS['basePath'] = str_replace('\\', '/', $GLOBALS['basePath']);
    $GLOBALS['basePath'] = $GLOBALS['basePath'] === '/' ? '' : rtrim($GLOBALS['basePath'], '/');
}

// Anti-scraping throttle for blog detail pages.
IpRateLimiter::init($conn);
$rateLimit = IpRateLimiter::check('blog_detail_page', 240, 60);
if (empty($rateLimit['allowed'])) {
    http_response_code(429);
    header('Retry-After: 60');
    exit('Too many requests. Please try again in a minute.');
}

// Get blog slug from URL - supports both clean URL routing and query string format
$slug = '';
if (!empty($GLOBALS['route_params']['blog_slug'])) {
    // From router: /blog/{slug}
    $slug = trim($GLOBALS['route_params']['blog_slug']);
} elseif (isset($_GET['slug'])) {
    // Backward compatible: ?slug={slug}
    $slug = trim($_GET['slug']);
}

if (empty($slug)) {
    header('Location: ' . url('/blog'));
    exit;
}

// Initialize models
$blogsModel = new Blogs($conn);

// Check for bot user agents to determine if we should increment views
$incrementViews = true;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    if (preg_match('/bot|crawl|slurp|spider|mediapartners/i', $userAgent)) {
        $incrementViews = false;
    }
}

// Get blog by slug
$blog = $blogsModel->getBySlug($slug, $incrementViews);

// If blog not found, redirect to 404
if (!$blog) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

// Get blog category
$category = null;
if (!empty($blog['category_id'])) {
    $categoryQuery = "SELECT id, name, slug FROM " . DB::BLOG_CATEGORIES . " WHERE id = ? AND status = 1 LIMIT 1";
    $stmt = $conn->prepare($categoryQuery);
    $stmt->bind_param("i", $blog['category_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    $stmt->close();
}

// Get related blogs (same category, excluding current)
$relatedBlogs = [];
if (!empty($blog['category_id'])) {
    $relatedBlogs = $blogsModel->getRelated($blog['category_id'], $blog['id'], 3);
}

// Get recent blogs for sidebar
$recentBlogs = $blogsModel->getRecent(5);

// Get popular blogs for sidebar
$popularBlogs = $blogsModel->getMostViewed(5);

// Get all categories for sidebar
$categoriesQuery = "SELECT id, name, slug FROM " . DB::BLOG_CATEGORIES . " WHERE status = 1 ORDER BY name ASC";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

// Page title and SEO metadata with database fields
$pageTitle = !empty($blog['meta_title'])
    ? $blog['meta_title']
    : ($blog['title'] ?? '') . ' - Blog - UAE Business Directory';

$pageDescription = !empty($blog['meta_description'])
    ? $blog['meta_description']
    : (isset($blog['excerpt']) && !empty($blog['excerpt'])
        ? truncateText(strip_tags($blog['excerpt']), 160)
        : truncateText(strip_tags($blog['content'] ?? ''), 160));

$pageKeywords = !empty($blog['meta_keywords'])
    ? $blog['meta_keywords']
    : ($blog['title'] ?? '') . ', UAE business news, business articles';

$canonicalUrl = !empty($blog['permalink'])
    ? $blog['permalink']
    : getFullUrl('/blog/' . ($blog['slug'] ?? ''));

$metaRobots = 'index,follow'; // Blogs are always publicly indexed
$bodyClass = 'page-blog-detail';

// Open Graph metadata for social sharing
$ogTitle = ($blog['title'] ?? '') . ' - UAE Business Directory';
$ogDescription = $pageDescription;
$ogImage = (isset($blog['featured_image']) && !empty($blog['featured_image']))
    ? getFullUrl('/' . $blog['featured_image'])
    : '';

$pageUrl = getFullUrl('/blog/' . ($blog['slug'] ?? ''));
$ampHtmlUrl = url('/blog/' . ($blog['slug'] ?? '') . '/amp');

// Hide selected sidebar widgets for this specific article slug.
$hideSearchAndCategories = (($blog['slug'] ?? '') === 'list-of-gcc-countries-gulf-countries');

// Hide recent posts block for requested article route.
$hideRecentPostsWidget = (($blog['slug'] ?? '') === 'uae-corporate-tax-law-guide');

// Route-specific media simplification on requested article.
$hideRelatedAndRecentThumbs = (($blog['slug'] ?? '') === 'list-of-gcc-countries-gulf-countries');

// Global blog detail UI rules for public-facing pages.
$hideFeaturedThumbs = true;
$hidePopularPostThumbs = true;

// Keep a single page-level H1 by demoting content-embedded H1 tags.
$blogContentHtml = (string) ($blog['content'] ?? '');
$blogContentHtml = preg_replace('/<h1\b([^>]*)>/i', '<h2$1>', $blogContentHtml);
$blogContentHtml = preg_replace('/<\/h1>/i', '</h2>', $blogContentHtml);

// Generate structured data for search engines
$jsonLdSchema = generateBlogPostSchema($blog);

// Generate breadcrumb structured data
$breadcrumbs = [
    ['name' => 'Home', 'url' => getFullUrl('/')],
    ['name' => 'Blog', 'url' => getFullUrl('/blog')],
];
if (!empty($category)) {
    $breadcrumbs[] = ['name' => e($category['name']), 'url' => getFullUrl('/blog/category/' . urlencode($category['slug']))];
}
$breadcrumbs[] = ['name' => e($blog['title']), 'url' => $pageUrl];
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);

include __DIR__ . '/../includes/layout/header.php';
?>

<main id="main-content">

<!--Breadcrumb-->
<section>
    <div class="bannerimg cover-image bg-background3" data-bs-image-src="assets/images/banners/banner2.jpg">
        <div class="header-text mb-0">
            <div class="container">
                <div class="text-center text-white">
                    <h2>Blog Details</h2>
                    <ol class="breadcrumb text-center justify-content-center">
                        <li class="breadcrumb-item"><a href="<?php echo url('/'); ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo url('/blog'); ?>">Blog</a></li>
                        <?php if ($category): ?>
                        <li class="breadcrumb-item"><a href="<?php echo url('/blog/category/' . urlencode($category['slug'])); ?>"><?= e($category['name']) ?></a></li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active text-white" aria-current="page">Post Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</section>
<!--/Breadcrumb-->

<!--Blog Detail-->
<section class="sptb">
    <div class="container">
        <div class="row">
            <div class="col-xl-8 col-lg-8 col-md-12">
                
                <!-- Main Blog Post -->
                <div class="card">
                    <div class="card-body">
                        <!-- Featured Image -->
                        <?php if (!$hideFeaturedThumbs && !empty($blog['featured_image'])): ?>
                        <div class="mb-4">
                            <img src="<?= e($blog['featured_image']) ?>" alt="<?= e($blog['title']) ?>" class="w-100 br-7 blogdet-featured-img">
                        </div>
                        <?php endif; ?>

                        <!-- Title -->
                        <h1 class="font-weight-semibold mb-3"><?= e($blog['title']) ?></h1>

                        <!-- Meta Information -->
                        <div class="item7-card-desc d-flex mb-4 pb-3 border-bottom">
                            <div class="d-flex align-items-center me-4">
                                <i class="fa fa-calendar-o text-muted me-2"></i>
                                <span><?= dd_($blog['created_at'], 'd M Y') ?></span>
                            </div>
                            <?php if (!empty($blog['author_name'])): ?>
                            <div class="d-flex align-items-center me-4">
                                <i class="fa fa-user text-muted me-2"></i>
                                <span><?= e($blog['author_name']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="d-flex align-items-center me-4">
                                <i class="fa fa-eye text-muted me-2"></i>
                                <span><?= number_format($blog['views']) ?> views</span>
                            </div>
                            <?php if ($category): ?>
                            <div class="ms-auto">
                                <a href="<?= url('/blog/category/' . urlencode($category['slug'])) ?>" class="badge badge-primary">
                                    <?= e($category['name']) ?>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Excerpt -->
                        <?php if (!empty($blog['excerpt'])): ?>
                        <div class="alert alert-info mb-4">
                            <p class="mb-0"><strong><?= e($blog['excerpt']) ?></strong></p>
                        </div>
                        <?php endif; ?>

                        <!-- Content -->
                        <div class="blog-content">
                            <?= $blogContentHtml ?>
                        </div>

                        <!-- Tags -->
                        <?php if (!empty($blog['tags'])): ?>
                        <div class="mt-5 pt-4 border-top">
                            <h5 class="mb-3">Tags:</h5>
                            <div class="product-tags">
                                <?php 
                                $tags = explode(',', $blog['tags']);
                                foreach ($tags as $tag): 
                                    $tag = trim($tag);
                                    if (!empty($tag)):
                                ?>
                                <a href="<?php echo htmlspecialchars(url('/blog') . '?tag=' . urlencode($tag), ENT_QUOTES, 'UTF-8'); ?>" class="badge badge-light me-2 mb-2 blogdet-tag">
                                    <?= e($tag) ?>
                                </a>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Share Buttons -->
                        <div class="mt-5 pt-4 border-top">
                            <h5 class="mb-3">Share this post:</h5>
                            <div class="d-flex gap-2">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($_SERVER['REQUEST_URI']) ?>" target="_blank" class="btn btn-icon btn-facebook" data-bs-toggle="tooltip" title="Share on Facebook">
                                    <i class="fa fa-facebook"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?= urlencode($_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($blog['title']) ?>" target="_blank" class="btn btn-icon btn-twitter" data-bs-toggle="tooltip" title="Share on Twitter">
                                    <i class="fa fa-twitter"></i>
                                </a>
                                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode($_SERVER['REQUEST_URI']) ?>&title=<?= urlencode($blog['title']) ?>" target="_blank" class="btn btn-icon btn-linkedin" data-bs-toggle="tooltip" title="Share on LinkedIn">
                                    <i class="fa fa-linkedin"></i>
                                </a>
                                <a href="https://wa.me/?text=<?= urlencode($blog['title'] . ' - ' . $_SERVER['REQUEST_URI']) ?>" target="_blank" class="btn btn-icon btn-success" data-bs-toggle="tooltip" title="Share on WhatsApp">
                                    <i class="fa fa-whatsapp"></i>
                                </a>
                                <button onclick="window.print()" class="btn btn-icon btn-secondary" data-bs-toggle="tooltip" title="Print">
                                    <i class="fa fa-print"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Author Bio (if available) -->
                <?php if (!empty($blog['author_name']) || !empty($blog['author_bio'])): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">About the Author</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="me-3">
                                        <img src="assets/images/faces/male/1.jpg" alt="<?= e($blog['author_name'] ?? 'Author') ?>" class="avatar avatar-xl brround">
                            </div>
                            <div>
                                <h5 class="mb-2"><?= e($blog['author_name'] ?? 'Author') ?></h5>
                                <?php if (!empty($blog['author_bio'])): ?>
                                <p class="text-muted mb-0"><?= e($blog['author_bio']) ?></p>
                                <?php else: ?>
                                <p class="text-muted mb-0">Content writer and contributor to HaiPulse UAE Business Directory.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Related Posts -->
                <?php if (!empty($relatedBlogs)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Related Posts</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($relatedBlogs as $relatedBlog): ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="card mb-0">
                                    <?php if (!$hideRelatedAndRecentThumbs && !empty($relatedBlog['featured_image'])): ?>
                                    <a href="<?= blogUrl($relatedBlog['slug']) ?>">
                                        <img src="<?= e($relatedBlog['featured_image']) ?>" alt="<?= e($relatedBlog['title']) ?>" class="card-img-top blogdet-related-img">
                                    </a>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <a href="<?= blogUrl($relatedBlog['slug']) ?>" class="text-dark">
                                            <h5 class="font-weight-semibold mb-2"><?= e(truncateText($relatedBlog['title'], 60)) ?></h5>
                                        </a>
                                        <small class="text-muted">
                                            <?= dd_($relatedBlog['created_at'], 'd M Y') ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Sidebar -->
            <div class="col-xl-4 col-lg-4 col-md-12">

                <!-- Categories Widget -->
                <?php if (!$hideSearchAndCategories && !empty($categories)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Categories</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($categories as $cat): ?>
                            <?php
                            // Get count for this category
                            $catCount = $blogsModel->getCount(['category_id' => $cat['id']]);
                            if ($catCount > 0):
                            ?>
                            <li class="list-group-item">
                                <a href="<?= url('/blog/category/' . urlencode($cat['slug'])) ?>" class="text-dark <?= !empty($category) && $category['id'] == $cat['id'] ? 'font-weight-bold' : '' ?>">
                                    <i class="fa fa-folder-o me-2"></i> <?= e($cat['name']) ?>
                                    <span class="badge badge-light float-end"><?= $catCount ?></span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Popular Posts Widget -->
                <?php if (!empty($popularBlogs)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Popular Posts</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($popularBlogs as $index => $popularBlog): ?>
                        <?php if ($popularBlog['id'] != $blog['id']): // Don't show current post ?>
                        <div class="d-flex <?= $index < count($popularBlogs) - 1 ? 'mb-3 pb-3 border-bottom' : '' ?>">
                            <?php if (!$hidePopularPostThumbs && !$hideFeaturedThumbs && !empty($popularBlog['featured_image'])): ?>
                            <a href="<?= blogUrl($popularBlog['slug']) ?>">
                                <img src="<?= e($popularBlog['featured_image']) ?>" alt="<?= e($popularBlog['title']) ?>" class="avatar avatar-lg brround me-3 blogdet-thumb-img">
                            </a>
                            <?php endif; ?>
                            <div class="flex-fill">
                                <a href="<?= blogUrl($popularBlog['slug']) ?>" class="text-dark">
                                    <h6 class="mb-1 font-weight-semibold"><?= e(truncateText($popularBlog['title'], 60)) ?></h6>
                                </a>
                                <small class="text-muted d-block">
                                    <i class="fa fa-eye me-1"></i><?= number_format($popularBlog['views']) ?> views
                                </small>
                                <small class="text-muted">
                                    <?= timeAgo($popularBlog['created_at']) ?>
                                </small>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Posts Widget -->
                <?php if (!$hideRecentPostsWidget && !empty($recentBlogs)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Posts</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recentBlogs as $index => $recentBlog): ?>
                        <?php if ($recentBlog['id'] != $blog['id']): // Don't show current post ?>
                        <div class="d-flex <?= $index < count($recentBlogs) - 1 ? 'mb-3 pb-3 border-bottom' : '' ?>">
                            <?php if (!$hideRelatedAndRecentThumbs && !empty($recentBlog['featured_image'])): ?>
                            <a href="<?= blogUrl($recentBlog['slug']) ?>">
                                <img src="<?= e($recentBlog['featured_image']) ?>" alt="<?= e($recentBlog['title']) ?>" class="avatar avatar-lg brround me-3 blogdet-thumb-img">
                            </a>
                            <?php endif; ?>
                            <div class="flex-fill">
                                <a href="<?= blogUrl($recentBlog['slug']) ?>" class="text-dark">
                                    <h6 class="mb-1 font-weight-semibold"><?= e(truncateText($recentBlog['title'], 60)) ?></h6>
                                </a>
                                <small class="text-muted">
                                    <?= dd_($recentBlog['created_at'], 'd M Y') ?>
                                </small>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>
<!--/Blog Detail-->

</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

