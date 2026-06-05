<?php
/**
 * Page: Dynamic CMS Pages
 * Route: /page/{slug}
 * Description: Load any page from hai_pages table by slug
 * Updated: March 2, 2026
 */

// ============================================
// DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';

// ============================================
// GET ROUTE PARAMETERS
// ============================================
$pageSlug = $GLOBALS['route_params']['page_slug'] ?? null;

if (!$pageSlug) {
    http_response_code(404);
    $pageTitle = 'Page Not Found';
    include __DIR__ . '/../pages/404.php';
    exit;
}

// ============================================
// LOAD PAGE FROM DATABASE
// ============================================
$query = "SELECT * FROM `" . DB::PAGES . "` 
          WHERE slug = '" . $conn->real_escape_string($pageSlug) . "' 
          AND status = 1 
          LIMIT 1";

$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    http_response_code(404);
    $pageTitle = 'Page Not Found';
    include __DIR__ . '/../pages/404.php';
    exit;
}

$page = $result->fetch_assoc();

// ============================================
// PREPARE PAGE VARIABLES
// ============================================
$pageTitle = htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8');
$pageDescription = htmlspecialchars($page['excerpt'], ENT_QUOTES, 'UTF-8');

// Override SEO metadata if available
if (!empty($page['meta_title'])) {
    $pageTitle = htmlspecialchars($page['meta_title'], ENT_QUOTES, 'UTF-8');
}
if (!empty($page['meta_description'])) {
    $pageDescription = htmlspecialchars($page['meta_description'], ENT_QUOTES, 'UTF-8');
}

// Update page view count
$conn->query("UPDATE `" . DB::PAGES . "` SET views = views + 1 WHERE id = " . intval($page['id']));

// ============================================
// INCLUDE HEADER & RENDER PAGE
// ============================================
include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container py-5">
    <!-- Page Header -->
    <div class="row mb-5">
        <div class="col-lg-10 mx-auto">
            <h1 class="display-5 font-weight-bold mb-3">
                <?php echo htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            
            <?php if (!empty($page['excerpt'])): ?>
                <p class="lead text-muted">
                    <?php echo htmlspecialchars($page['excerpt'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>
            
            <!-- Published Date -->
            <small class="text-muted d-block mt-3">
                <i class="fa fa-calendar-o"></i> 
                Published: <?php echo dd_($page['created_at'], 'd M Y'); ?>
                <?php if (!empty($page['views'])): ?>
                    | <i class="fa fa-eye"></i> <?php echo number_format($page['views']); ?> views
                <?php endif; ?>
            </small>
        </div>
    </div>

    <!-- Page Content -->
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <article class="page-content">
                <?php echo $page['content']; ?>
            </article>
        </div>
    </div>

    <!-- Last Modified -->
    <?php if (!empty($page['updated_at']) && $page['updated_at'] !== $page['created_at']): ?>
        <div class="row mt-5">
            <div class="col-lg-10 mx-auto">
                <hr>
                <small class="text-muted">
                    Last updated: <?php echo dd_($page['updated_at'], 'd M Y g:ia'); ?>
                </small>
            </div>
        </div>
    <?php endif; ?>

    <!-- Child Pages (If this page has children) -->
    <?php
    $childQuery = "SELECT id, title, slug, excerpt FROM `" . DB::PAGES . "` 
                   WHERE parent_id = " . intval($page['id']) . " 
                   AND status = 1 
                   ORDER BY display_order ASC";
    $childResult = $conn->query($childQuery);
    
    if ($childResult && $childResult->num_rows > 0): 
    ?>
        <div class="row mt-5">
            <div class="col-lg-10 mx-auto">
                <hr class="my-5">
                <h3 class="mb-4">Related Pages</h3>
                <div class="row">
                    <?php while ($child = $childResult->fetch_assoc()): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="<?php echo ($GLOBALS['basePath'] ?? '') . '/page/' . htmlspecialchars($child['slug']); ?>">
                                            <?php echo htmlspecialchars($child['title'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </h5>
                                    <?php if (!empty($child['excerpt'])): ?>
                                        <p class="card-text text-muted">
                                            <?php echo htmlspecialchars(substr($child['excerpt'], 0, 150), ENT_QUOTES, 'UTF-8'); ?>...
                                        </p>
                                    <?php endif; ?>
                                    <a href="<?php echo ($GLOBALS['basePath'] ?? '') . '/page/' . htmlspecialchars($child['slug']); ?>" class="btn btn-sm btn-outline-primary">
                                        Read More <i class="fa fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .page-content {
        font-size: 1.1rem;
        line-height: 1.8;
        color: #333;
    }

    .page-content h2 {
        margin-top: 2rem;
        margin-bottom: 1rem;
        font-weight: 700;
        color: #1a1a1a;
    }

    .page-content h3 {
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        font-weight: 600;
        color: #2a2a2a;
    }

    .page-content p {
        margin-bottom: 1.2rem;
    }

    .page-content ul,
    .page-content ol {
        margin-bottom: 1.2rem;
        padding-left: 2rem;
    }

    .page-content li {
        margin-bottom: 0.5rem;
    }

    .page-content a {
        color: #0066cc;
        text-decoration: underline;
    }

    .page-content a:hover {
        color: #0052a3;
    }

    .page-content blockquote {
        border-left: 4px solid #0066cc;
        padding-left: 1.5rem;
        margin: 1.5rem 0;
        font-style: italic;
        color: #666;
    }

    .page-content code {
        background-color: #f4f4f4;
        padding: 0.2rem 0.5rem;
        border-radius: 3px;
        font-family: 'Courier New', monospace;
    }

    .page-content pre {
        background-color: #f4f4f4;
        padding: 1rem;
        border-radius: 5px;
        overflow-x: auto;
        margin-bottom: 1.2rem;
    }

    .page-content table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1.2rem;
    }

    .page-content table th,
    .page-content table td {
        border: 1px solid #ddd;
        padding: 0.75rem;
        text-align: left;
    }

    .page-content table th {
        background-color: #f9f9f9;
        font-weight: 600;
    }
</style>

<?php
// Include footer
include __DIR__ . '/../includes/layout/footer.php';
?>
