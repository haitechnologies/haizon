<?php
/**
 * Sitemap Page (both HTML and XML)
 * 
 * Provide comprehensive sitemap for users and search engines
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../includes/helpers.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$format = isset($_GET['format']) ? $_GET['format'] : 'html'; // html or xml
if (stripos($requestPath, 'sitemap.xml') !== false) {
    $format = 'xml';
}

// Sitemap settings (from Global Settings / Sitemap Management)
$sitemapEnabled = (int)getSystemSetting('sitemap_enabled', 1);
$sitemapCompanies = (int)getSystemSetting('sitemap_companies', 1);
$sitemapBlogs = (int)getSystemSetting('sitemap_blogs', 1);
$sitemapCategories = (int)getSystemSetting('sitemap_categories', 1);
$sitemapRoot = trim(getSystemSetting('sitemap_root', ''));
$seoCanonicalUrl = trim(getSystemSetting('seo_canonical_url', ''));

/**
 * Execute SQL and return result or null without fataling on schema differences.
 */
function sitemapQuery($conn, $sql) {
    try {
        return $conn->query($sql);
    } catch (Throwable $e) {
        return null;
    }
}

// Determine base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $GLOBALS['basePath'];
$baseUrl = rtrim($baseUrl, '/');
if (!empty($sitemapRoot)) {
    $baseUrl = rtrim($sitemapRoot, '/');
} elseif (!empty($seoCanonicalUrl)) {
    $baseUrl = rtrim($seoCanonicalUrl, '/');
}

// Get all categories
$categories = [];
$subcategories = [];
if ($sitemapCategories === 1) {
    $categoriesQuery = "SELECT id, name, slug FROM " . DB::CATEGORIES . " WHERE status = 1 ORDER BY name ASC";
    $categoriesResult = sitemapQuery($conn, $categoriesQuery);
    if (!$categoriesResult) {
        // Fallback for schemas without a status column.
        $categoriesQuery = "SELECT id, name, slug FROM " . DB::CATEGORIES . " ORDER BY name ASC";
        $categoriesResult = sitemapQuery($conn, $categoriesQuery);
    }
    $categories = $categoriesResult ? $categoriesResult->fetch_all(MYSQLI_ASSOC) : [];

    // Get all subcategories
    $subcategoriesQuery = "SELECT id, slug FROM " . DB::SUBCATEGORIES . " WHERE status = 1 ORDER BY slug ASC";
    $subcategoriesResult = sitemapQuery($conn, $subcategoriesQuery);
    if (!$subcategoriesResult) {
        // Fallback for schemas without a status column.
        $subcategoriesQuery = "SELECT id, slug FROM " . DB::SUBCATEGORIES . " ORDER BY slug ASC";
        $subcategoriesResult = sitemapQuery($conn, $subcategoriesQuery);
    }
    $subcategories = $subcategoriesResult ? $subcategoriesResult->fetch_all(MYSQLI_ASSOC) : [];
}

// Get all published companies
$companies = [];
if ($sitemapCompanies === 1) {
    $companiesQuery = "SELECT slug, updated_at FROM " . DB::COMPANIES . " WHERE publish = 1 ORDER BY updated_at DESC LIMIT 5000";
    $companiesResult = sitemapQuery($conn, $companiesQuery);
    $companies = $companiesResult ? $companiesResult->fetch_all(MYSQLI_ASSOC) : [];
}

// Get all published blogs
$blogs = [];
if ($sitemapBlogs === 1) {
    $blogsQuery = "SELECT slug, updated_at FROM " . DB::BLOGS . " WHERE publish = 1 ORDER BY updated_at DESC LIMIT 1000";
    $blogsResult = sitemapQuery($conn, $blogsQuery);
    if (!$blogsResult) {
        // Fallback for schemas without a publish column.
        $blogsQuery = "SELECT slug, updated_at FROM " . DB::BLOGS . " ORDER BY updated_at DESC LIMIT 1000";
        $blogsResult = sitemapQuery($conn, $blogsQuery);
    }
    $blogs = $blogsResult ? $blogsResult->fetch_all(MYSQLI_ASSOC) : [];
}

// Get all static pages
$staticPages = [
    ['path' => '', 'title' => 'Home', 'priority' => 1.0],
    ['path' => 'listings', 'title' => 'Companies', 'priority' => 0.9],
    ['path' => 'categories', 'title' => 'Categories', 'priority' => 0.9],
    ['path' => 'blog', 'title' => 'Blog', 'priority' => 0.8],
    ['path' => 'partners', 'title' => 'Partners', 'priority' => 0.8],
    ['path' => 'search', 'title' => 'Search', 'priority' => 0.7],
    ['path' => 'about', 'title' => 'About Us', 'priority' => 0.6],
    ['path' => 'contact', 'title' => 'Contact Us', 'priority' => 0.7],
    ['path' => 'add-business', 'title' => 'Submit Company', 'priority' => 0.7],
    ['path' => 'pricing', 'title' => 'Pricing', 'priority' => 0.6],
    ['path' => 'terms-of-use', 'title' => 'Terms of Service', 'priority' => 0.5],
    ['path' => 'privacy-policy', 'title' => 'Privacy Policy', 'priority' => 0.5],
];

// Generate XML sitemap
if ($format === 'xml') {
    if ($sitemapEnabled !== 1) {
        header('HTTP/1.0 404 Not Found');
        exit;
    }
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    // Static pages
    foreach ($staticPages as $page) {
        $url = $baseUrl . ($page['path'] ? '/' . $page['path'] : '/');
        $priority = $page['priority'];
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
        echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>{$priority}</priority>\n";
        echo "  </url>\n";
    }

    // Category pages
    if ($sitemapCategories === 1) {
        foreach ($categories as $category) {
            $url = $baseUrl . '/category/' . $category['slug'];
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.8</priority>\n";
            echo "  </url>\n";
        }

        // Subcategory pages
        foreach ($subcategories as $subcat) {
            $url = $baseUrl . '/subcategory/' . $subcat['slug'];
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.7</priority>\n";
            echo "  </url>\n";
        }
    }

    // Company pages
    if ($sitemapCompanies === 1) {
        foreach ($companies as $company) {
            $url = $baseUrl . '/company/' . $company['slug'];
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            echo "    <lastmod>" . $company['updated_at'] . "</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.6</priority>\n";
            echo "  </url>\n";
        }
    }

    // Blog pages
    if ($sitemapBlogs === 1) {
        foreach ($blogs as $blog) {
            $url = $baseUrl . '/blog/' . $blog['slug'];
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            echo "    <lastmod>" . $blog['updated_at'] . "</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.5</priority>\n";
            echo "  </url>\n";
        }
    }

    echo '</urlset>';
    exit;
}

// HTML Sitemap
$pageTitle = 'Sitemap - UAE Business Directory';
$pageDescription = 'Browse the complete sitemap of UAE Business Directory';

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="content-wrapper">
    <!-- Breadcrumb -->
    <div class="breadcrumb-area">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>">Home</a></li>
                    <li class="breadcrumb-item active">Sitemap</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <h1 class="mb-2">Sitemap</h1>
        <p class="text-muted mb-4">
            Navigation map of UAE Business Directory. 
            <a href="<?php echo htmlspecialchars(url('/sitemap.xml'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><i class="fa fa-file"></i> View XML Sitemap</a>
        </p>

        <?php if ($sitemapEnabled !== 1): ?>
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-circle me-2"></i> Public sitemaps are currently disabled in settings.
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-10">
                <!-- Static Pages Section -->
                <div class="sitemap-section mb-4">
                    <h4 class="mb-3"><i class="fa fa-file text-primary"></i> Main Pages</h4>
                    <ul class="list-unstyled">
                        <?php foreach ($staticPages as $page): ?>
                            <li class="mb-2">
                                <a href="<?php echo htmlspecialchars(url($page['path'] ? '/' . $page['path'] : '/'), ENT_QUOTES, 'UTF-8'); ?>" class="sitemap-link">
                                    <?php echo e($page['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Categories Section -->
                <?php if ($sitemapCategories === 1): ?>
                <div class="sitemap-section mb-4">
                    <h4 class="mb-3"><i class="fa fa-folder text-warning"></i> Categories (<?php echo count($categories); ?>)</h4>
                    <details class="sitemap-details">
                        <summary class="cursor-pointer">Expand to view all categories</summary>
                        <ul class="list-unstyled ps-3 mt-2">
                            <?php foreach ($categories as $category): ?>
                                <li class="mb-2">
                                    <a href="<?php echo htmlspecialchars(url('/category/' . rawurlencode((string)$category['slug'])), ENT_QUOTES, 'UTF-8'); ?>" class="sitemap-link">
                                        <?php echo e($category['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                </div>
                <?php endif; ?>

                <!-- Subcategories Section -->
                <?php if ($sitemapCategories === 1): ?>
                <div class="sitemap-section mb-4">
                    <h4 class="mb-3"><i class="fa fa-sitemap text-info"></i> Subcategories (<?php echo count($subcategories); ?>)</h4>
                    <details class="sitemap-details">
                        <summary class="cursor-pointer">Expand to view all subcategories</summary>
                        <ul class="list-unstyled ps-3 mt-2 multi-column-list">
                            <?php foreach ($subcategories as $subcat): ?>
                                <li class="mb-2">
                                    <a href="<?php echo htmlspecialchars(url('/subcategory/' . rawurlencode((string)$subcat['slug'])), ENT_QUOTES, 'UTF-8'); ?>" class="sitemap-link">
                                        <?php echo e($subcat['slug']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                </div>
                <?php endif; ?>

                <!-- Companies Section -->
                <?php if ($sitemapCompanies === 1): ?>
                <div class="sitemap-section mb-4">
                    <h4 class="mb-3"><i class="fa fa-building text-success"></i> Recent Companies (showing 100 of <?php echo count($companies); ?>)</h4>
                    <details class="sitemap-details">
                        <summary class="cursor-pointer">Expand to view recent companies</summary>
                        <ul class="list-unstyled ps-3 mt-2 multi-column-list">
                            <?php 
                            $companyCount = 0;
                            foreach ($companies as $company): 
                                if ($companyCount >= 100) break;
                                $companyCount++;
                            ?>
                                <li class="mb-2">
                                    <a href="<?php echo htmlspecialchars(url('/company/' . rawurlencode((string)$company['slug'])), ENT_QUOTES, 'UTF-8'); ?>" class="sitemap-link">
                                        <?php echo e(ucfirst(str_replace('-', ' ', $company['slug']))); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                </div>
                <?php endif; ?>

                <!-- Blog Section -->
                <?php if ($sitemapBlogs === 1): ?>
                <div class="sitemap-section mb-4">
                    <h4 class="mb-3"><i class="fa fa-newspaper text-danger"></i> Recent Blog Posts (showing 50 of <?php echo count($blogs); ?>)</h4>
                    <details class="sitemap-details">
                        <summary class="cursor-pointer">Expand to view recent blog posts</summary>
                        <ul class="list-unstyled ps-3 mt-2 multi-column-list">
                            <?php 
                            $blogCount = 0;
                            foreach ($blogs as $blog): 
                                if ($blogCount >= 50) break;
                                $blogCount++;
                            ?>
                                <li class="mb-2">
                                    <a href="<?php echo htmlspecialchars(url('/blog/' . rawurlencode((string)$blog['slug'])), ENT_QUOTES, 'UTF-8'); ?>" class="sitemap-link">
                                        <?php echo e(ucfirst(str_replace('-', ' ', $blog['slug']))); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="alert alert-info mt-4">
                    <h6><i class="fa fa-bar-chart me-2"></i> Sitemap Statistics</h6>
                    <ul class="mb-0">
                        <li>Total Main Pages: <?php echo count($staticPages); ?></li>
                        <li>Total Categories: <?php echo count($categories); ?></li>
                        <li>Total Subcategories: <?php echo count($subcategories); ?></li>
                        <li>Total Companies: <?php echo count($companies); ?></li>
                        <li>Total Blog Posts: <?php echo count($blogs); ?></li>
                        <li><strong>Total URLs: <?php echo count($staticPages) + count($categories) + count($subcategories) + count($companies) + count($blogs); ?></strong></li>
                    </ul>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-2">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Sitemap Resources</h6>
                        <div class="list-group list-group-sm">
                            <a href="<?php echo htmlspecialchars(url('/sitemap.xml'), ENT_QUOTES, 'UTF-8'); ?>" class="list-group-item list-group-item-action" target="_blank">
                                <i class="fa fa-file"></i> XML Sitemap
                            </a>
                            <a href="<?php echo htmlspecialchars(url('/robots.txt'), ENT_QUOTES, 'UTF-8'); ?>" class="list-group-item list-group-item-action" target="_blank">
                                <i class="fa fa-robot"></i> Robots.txt
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.breadcrumb-area {
    background: #f8f9fa;
    padding: 20px 0;
    border-bottom: 1px solid #e0e0e0;
}

.sitemap-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.sitemap-details {
    border: 1px solid #e0e0e0;
    padding: 15px;
    border-radius: 6px;
    background: white;
}

.sitemap-details summary {
    cursor: pointer;
    font-weight: 500;
    color: #667eea;
}

.sitemap-details summary:hover {
    color: #764ba2;
}

.sitemap-link {
    color: inherit;
    text-decoration: none;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s;
}

.sitemap-link:hover {
    background: #e7f1ff;
    color: #667eea;
}

.multi-column-list {
    columns: 2;
    column-gap: 30px;
}

@media (max-width: 768px) {
    .multi-column-list {
        columns: 1;
    }
}

.cursor-pointer {
    cursor: pointer;
}
</style>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
