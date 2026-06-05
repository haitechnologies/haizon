<?php
/**
 * Page: Individual Service/Product Detail
 * Route: /service/{slug}
 * Description: Display detail for a specific service with companies offering it
 * Updated: March 2, 2026
 */

// ============================================
// DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/CategoryItems.php';
require_once __DIR__ . '/../classes/frontend/Categories.php';
require_once __DIR__ . '/../classes/frontend/Companies.php';

// ============================================
// GET ROUTE PARAMETERS
// ============================================
$serviceSlug = $GLOBALS['route_params']['service_slug'] ?? null;
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;

if (!$serviceSlug) {
  header('Location: ' . url('/services'));
    exit;
}

// ============================================
// LOAD SERVICE DATA
// ============================================
$CategoryItemsModel = new CategoryItems($conn);
$CategoriesModel = new Categories($conn);
$CompaniesModel = new Companies($conn);

// Get service by slug
$service = $CategoryItemsModel->getBySlug($serviceSlug);

if (!$service) {
    http_response_code(404);
    $pageTitle = 'Service Not Found';
    include __DIR__ . '/../pages/404.php';
    exit;
}

// Get category information for breadcrumb
$category = $CategoriesModel->getById($service['category_id']);

// Get companies offering this service
$companies = $CategoryItemsModel->getCompanies($service['id'], [
    'page' => $page,
    'per_page' => $perPage,
    'verified' => false,
    'published' => true
]);

$companyCount = $CategoryItemsModel->getCompanyCount($service['id']);
$totalPages = max(1, ceil($companyCount / $perPage));

// Get breadcrumb path
$breadcrumb = $CategoryItemsModel->getBreadcrumb($service['id']);

// ============================================
// PAGE METADATA
// ============================================
$pageTitle = htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') . ' - UAE Services Directory';
$pageDescription = 'Find companies offering ' . htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') . ' in UAE. ' . $companyCount . ' businesses listed.';

include __DIR__ . '/../includes/layout/header.php';
?>

<style>
  :root {
    --service-accent: #0f766e;
    --service-accent-strong: #115e59;
    --service-ink: #0f172a;
    --service-muted: #64748b;
    --service-surface: #f8fafc;
    --service-line: #dbe4ef;
  }

  .service-detail-hero {
    background:
      radial-gradient(circle at 10% 0%, rgba(20, 184, 166, 0.35) 0%, rgba(20, 184, 166, 0) 45%),
      radial-gradient(circle at 90% 100%, rgba(14, 165, 233, 0.25) 0%, rgba(14, 165, 233, 0) 45%),
      linear-gradient(130deg, #0f172a 0%, #1e293b 100%);
    color: #eef6ff;
    padding: 44px 20px;
    margin-bottom: 34px;
  }

  .service-hero-inner {
    max-width: 1180px;
    margin: 0 auto;
  }

  .service-hero-panel {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 18px;
    backdrop-filter: blur(2px);
    padding: 22px;
  }

  .breadcrumb-nav {
    display: flex;
    gap: 8px;
    margin-bottom: 18px;
    font-size: 0.88rem;
    flex-wrap: wrap;
    align-items: center;
  }

  .breadcrumb-nav a,
  .breadcrumb-nav span {
    display: inline-flex;
    align-items: center;
    border-radius: 99px;
    padding: 4px 10px;
    line-height: 1;
  }

  .breadcrumb-nav a {
    color: #dcfce7;
    background: rgba(20, 184, 166, 0.2);
    text-decoration: none;
  }

  .breadcrumb-nav a:hover {
    background: rgba(20, 184, 166, 0.34);
    color: #fff;
  }

  .breadcrumb-nav span {
    color: rgba(238, 246, 255, 0.9);
    background: rgba(255, 255, 255, 0.1);
  }

  .service-detail-hero h1 {
    font-size: clamp(1.7rem, 2.8vw, 2.4rem);
    margin: 0 0 8px;
    font-weight: 700;
    letter-spacing: -0.02em;
  }

  .service-subtitle {
    margin: 0;
    color: rgba(238, 246, 255, 0.92);
    font-size: 1rem;
  }

  .service-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 10px;
    margin-top: 18px;
  }

  .service-stat-item {
    border-radius: 12px;
    padding: 12px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.25);
  }

  .service-stat-value {
    display: block;
    font-size: 1.35rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 2px;
  }

  .service-stat-label {
    font-size: 0.85rem;
    color: rgba(238, 246, 255, 0.9);
  }

  .service-list-container {
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 20px 36px;
  }

  .service-list-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    flex-wrap: wrap;
  }

  .service-list-title {
    font-size: 1.08rem;
    color: var(--service-ink);
    margin: 0;
    font-weight: 700;
  }

  .service-list-note {
    margin: 0;
    font-size: 0.86rem;
    color: var(--service-muted);
  }

  .company-card {
    background: #fff;
    border: 1px solid var(--service-line);
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 12px;
    text-decoration: none;
    color: inherit;
    display: block;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  }

  .company-card:hover {
    transform: translateY(-2px);
    border-color: #a7f3d0;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
  }

  .company-top {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
    margin-bottom: 10px;
  }

  .company-name {
    font-size: 1.06rem;
    font-weight: 700;
    color: var(--service-ink);
    margin: 0;
  }

  .company-badges {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 6px;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 9px;
    border-radius: 99px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.01em;
  }

  .badge-verified {
    background: #dcfce7;
    color: #166534;
  }

  .badge-featured {
    background: #fff7ed;
    color: #c2410c;
  }

  .company-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 10px;
    color: var(--service-muted);
    font-size: 0.87rem;
  }

  .company-description {
    margin: 0 0 12px;
    color: #334155;
    line-height: 1.5;
    font-size: 0.92rem;
  }

  .company-contact {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .contact-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 11px;
    border: 1px solid var(--service-line);
    border-radius: 10px;
    text-decoration: none;
    color: #0f3340;
    font-size: 0.84rem;
    font-weight: 600;
    background: #fff;
    transition: all 0.2s ease;
  }

  .contact-link:hover {
    border-color: #99f6e4;
    background: #f0fdfa;
    color: var(--service-accent-strong);
  }

  .contact-link-profile {
    background: var(--service-accent);
    border-color: var(--service-accent);
    color: #fff;
  }

  .contact-link-profile:hover {
    background: var(--service-accent-strong);
    border-color: var(--service-accent-strong);
    color: #fff;
  }

  .pagination-controls {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin: 28px 0 8px;
    flex-wrap: wrap;
  }

  .pagination-link {
    padding: 8px 12px;
    border: 1px solid var(--service-line);
    background: #fff;
    color: #334155;
    text-decoration: none;
    border-radius: 10px;
    font-size: 0.86rem;
    font-weight: 600;
    transition: all 0.2s ease;
  }

  .pagination-link:hover,
  .pagination-link.active {
    background: #ecfeff;
    border-color: #99f6e4;
    color: #0f766e;
  }

  .no-companies {
    text-align: center;
    padding: 54px 18px;
    color: #64748b;
    background: var(--service-surface);
    border: 1px solid var(--service-line);
    border-radius: 14px;
  }

  .service-browse-link {
    color: var(--service-accent-strong);
    text-decoration: none;
    font-weight: 700;
  }

  @media (max-width: 768px) {
    .service-detail-hero {
      padding: 30px 16px;
      margin-bottom: 24px;
    }

    .service-hero-panel {
      padding: 16px;
    }

    .service-list-container {
      padding: 0 14px 28px;
    }

    .company-top {
      flex-direction: column;
      gap: 8px;
    }
  }
</style>

<main class="site-main">
  <!-- Hero Section -->
  <div class="service-detail-hero">
    <div class="service-hero-inner">
      <div class="service-hero-panel">
        <!-- Breadcrumb -->
        <div class="breadcrumb-nav">
          <a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES); ?>">Home</a>
          <span>Services</span>
          <?php if ($category): ?>
            <a href="<?php echo htmlspecialchars(url('/services') . '?category=' . urlencode((string)$category['id']), ENT_QUOTES); ?>">
              <?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>
            </a>
          <?php endif; ?>
          <span><?php echo htmlspecialchars($service['name'], ENT_QUOTES); ?></span>
        </div>

        <h1><?php echo htmlspecialchars($service['name'], ENT_QUOTES); ?></h1>
        <p class="service-subtitle">Verified business directory results tailored for this service in UAE.</p>

        <!-- Stats -->
        <div class="service-stats">
          <div class="service-stat-item">
            <span class="service-stat-value"><?php echo (int)$companyCount; ?></span>
            <span class="service-stat-label">Companies Found</span>
          </div>
          <div class="service-stat-item">
            <span class="service-stat-value"><?php echo (int)$page; ?>/<?php echo (int)$totalPages; ?></span>
            <span class="service-stat-label">Current Page</span>
          </div>
          <div class="service-stat-item">
            <span class="service-stat-value"><?php echo (int)$perPage; ?></span>
            <span class="service-stat-label">Results Per Page</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Companies List -->
  <div class="service-list-container">
    <div class="service-list-head">
      <h2 class="service-list-title">Available Companies</h2>
      <p class="service-list-note">Showing <?php echo count($companies); ?> of <?php echo (int)$companyCount; ?> companies</p>
    </div>

    <?php if (!empty($companies)): ?>
      <?php foreach ($companies as $company): ?>
        <?php $companyDisplayName = display_text($company['display_name'] ?? $company['company_name'] ?? $company['name'] ?? 'Business'); ?>
        <?php $companyUrl = url('/company/' . rawurlencode((string)($company['slug'] ?? $company['id']))); ?>
        <a href="<?php echo htmlspecialchars($companyUrl, ENT_QUOTES); ?>" class="company-card">
          <div class="company-top">
            <div>
              <h3 class="company-name"><?php echo htmlspecialchars($companyDisplayName, ENT_QUOTES); ?></h3>
            </div>
            <div class="company-badges">
              <?php if ($company['verified'] ?? false): ?>
                <span class="badge badge-verified">✓ Verified</span>
              <?php endif; ?>
              <?php if ($company['featured'] ?? false): ?>
                <span class="badge badge-featured">⭐ Featured</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="company-meta">
            <?php if (!empty($company['emirate'])): ?>
              <div>📍 <?php echo htmlspecialchars($company['emirate'], ENT_QUOTES); ?></div>
            <?php endif; ?>
            <?php if ($company['avg_rating'] ?? 0 > 0): ?>
              <div>⭐ <?php echo round($company['avg_rating'], 1); ?>/5.0</div>
            <?php endif; ?>
          </div>

          <?php if (!empty($company['description'])): ?>
            <div class="company-description">
              <?php echo htmlspecialchars(substr($company['description'], 0, 170), ENT_QUOTES); ?>...
            </div>
          <?php endif; ?>

          <div class="company-contact">
            <?php if (!empty($company['phone'])): ?>
              <a href="tel:<?php echo htmlspecialchars($company['phone'], ENT_QUOTES); ?>" class="contact-link">📞 Call</a>
            <?php endif; ?>
            <?php if (!empty($company['email'])): ?>
              <a href="mailto:<?php echo htmlspecialchars($company['email'], ENT_QUOTES); ?>" class="contact-link">📧 Email</a>
            <?php endif; ?>
            <?php if (!empty($company['website'])): ?>
              <a href="<?php echo htmlspecialchars($company['website'], ENT_QUOTES); ?>" class="contact-link" target="_blank">🌐 Website</a>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars($companyUrl, ENT_QUOTES); ?>" class="contact-link contact-link-profile">View Profile →</a>
          </div>
        </a>
      <?php endforeach; ?>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="pagination-controls">
          <?php if ($page > 1): ?>
            <a href="?page=1" class="pagination-link">← First</a>
            <a href="?page=<?php echo $page - 1; ?>" class="pagination-link">← Previous</a>
          <?php endif; ?>

          <?php 
          $start = max(1, $page - 2);
          $end = min($totalPages, $page + 2);
          
          for ($i = $start; $i <= $end; $i++):
            $isActive = ($i == $page) ? 'active' : '';
          ?>
            <a href="?page=<?php echo $i; ?>" class="pagination-link <?php echo $isActive; ?>">
              <?php echo $i; ?>
            </a>
          <?php endfor;
          
          if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>" class="pagination-link">Next →</a>
            <a href="?page=<?php echo $totalPages; ?>" class="pagination-link">Last →</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <!-- No Companies Found -->
      <div class="no-companies">
        <h3>No Companies Found</h3>
        <p>No businesses are currently offering this service.<br>
        <a href="<?php echo htmlspecialchars(url('/services'), ENT_QUOTES); ?>" class="service-browse-link">Browse other services</a></p>
      </div>
    <?php endif; ?>

  </div>

</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
