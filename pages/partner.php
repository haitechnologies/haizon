<?php
/**
 * Page: Individual Partner/Source Detail
 * Route: /partner/{slug}
 * Description: Display information about a specific data source with companies from that source
 * Updated: March 2, 2026
 */

// ============================================
// DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/CompanySources.php';
require_once __DIR__ . '/../classes/frontend/Companies.php';

// ============================================
// GET ROUTE PARAMETERS
// ============================================
$partnerSlug = $GLOBALS['route_params']['partner_slug'] ?? null;
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;

if (!$partnerSlug) {
    header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/partners'));
    exit;
}

// ============================================
// LOAD PARTNER DATA
// ============================================
$CompanySourcesModel = new CompanySources($conn);
$CompaniesModel = new Companies($conn);

// Get partner by slug
$allSources = $CompanySourcesModel->getAll(['limit' => 100]);
$partner = null;

foreach ($allSources as $source) {
  $sourceNameForSlug = trim((string)($source['source'] ?? $source['source_name'] ?? $source['name'] ?? ''));
  $sourceSlug = trim((string)($source['sitemap_slug'] ?? ''));
  if ($sourceSlug === '') {
    $sourceSlug = preg_replace('/[^a-z0-9-]+/', '-', strtolower($sourceNameForSlug));
    $sourceSlug = trim($sourceSlug, '-');
  }
    if ($sourceSlug === strtolower($partnerSlug)) {
        $partner = $source;
        break;
    }
}

if (!$partner) {
    http_response_code(404);
    $pageTitle = 'Partner Not Found';
    include __DIR__ . '/../pages/404.php';
    exit;
}

$partnerName = trim((string)($partner['source'] ?? $partner['source_name'] ?? $partner['name'] ?? $partner['sitemap_slug'] ?? 'Partner'));

// Get companies from this source
$partnerId = (int)($partner['id'] ?? 0);
$partnerSourceValue = trim((string)($partner['source'] ?? ''));
$offset = ($page - 1) * $perPage;

$sourcePredicates = [];
$bindTypes = '';
$bindValues = [];

// Support both import schemas used in different environments.
if ($partnerId > 0) {
  $sourcePredicates[] = 'data_source_id = ?';
  $bindTypes .= 'i';
  $bindValues[] = $partnerId;
}

if ($partnerSourceValue !== '') {
  $sourcePredicates[] = 'LOWER(data_source) = LOWER(?)';
  $bindTypes .= 's';
  $bindValues[] = $partnerSourceValue;
}

$companies = [];
$totalCompanies = 0;

if (!empty($sourcePredicates)) {
  $sourceWhereSql = '(' . implode(' OR ', $sourcePredicates) . ')';

  $countSql = "SELECT COUNT(*) AS total
         FROM " . DB::COMPANIES . "
         WHERE " . $sourceWhereSql . "
           AND publish = 1
           AND is_active = 1";
  $countStmt = $conn->prepare($countSql);
  if ($countStmt) {
    $countStmt->bind_param($bindTypes, ...$bindValues);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countRow = $countResult ? $countResult->fetch_assoc() : null;
    $totalCompanies = (int)($countRow['total'] ?? 0);
    $countStmt->close();
  }

  $listSql = "SELECT *
        FROM " . DB::COMPANIES . "
        WHERE " . $sourceWhereSql . "
          AND publish = 1
          AND is_active = 1
        ORDER BY `company_name` ASC
        LIMIT ? OFFSET ?";
  $listStmt = $conn->prepare($listSql);
  if ($listStmt) {
    $listTypes = $bindTypes . 'ii';
    $listValues = array_merge($bindValues, [$perPage, $offset]);
    $listStmt->bind_param($listTypes, ...$listValues);
    $listStmt->execute();
    $result = $listStmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $companies[] = $row;
    }
    $listStmt->close();
  }
}

$totalPages = max(1, ceil($totalCompanies / $perPage));

// ============================================
// PAGE METADATA
// ============================================
$pageTitle = htmlspecialchars($partnerName, ENT_QUOTES, 'UTF-8') . ' - Companies Directory';
$pageDescription = 'View ' . $totalCompanies . ' companies from ' . htmlspecialchars($partnerName, ENT_QUOTES, 'UTF-8') . ' in HAIPULSE business directory.';

include __DIR__ . '/../includes/layout/header.php';
?>

<style>
  .partner-detail-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 20px;
    margin-bottom: 40px;
  }

  .partner-detail-content {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 30px;
    margin-bottom: 40px;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    padding: 0 20px;
  }

  .partner-detail-sidebar {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 24px;
    height: fit-content;
  }

  .partner-icon-large {
    font-size: 4rem;
    text-align: center;
    margin-bottom: 16px;
  }

  .partner-info-section {
    margin-bottom: 24px;
  }

  .partner-info-label {
    font-size: 0.75rem;
    color: #999;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 4px;
  }

  .partner-info-value {
    color: #333;
    font-size: 0.95rem;
  }

  .companies-list {
    flex: 1;
  }

  .companies-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .company-item {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 16px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s;
  }

  .company-item:hover {
    border-color: #667eea;
    box-shadow: 0 4px 8px rgba(102, 126, 234, 0.1);
  }

  .company-item-title {
    font-size: 1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
  }

  .company-item-meta {
    display: flex;
    gap: 12px;
    font-size: 0.85rem;
    color: #999;
    margin-bottom: 8px;
  }

  .company-item-description {
    font-size: 0.9rem;
    color: #666;
  }

  .no-companies {
    text-align: center;
    padding: 40px;
    color: #999;
  }

  .pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin: 30px 0;
    flex-wrap: wrap;
  }

  .pagination-link {
    padding: 8px 12px;
    border: 1px solid #ddd;
    background: white;
    color: #333;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.2s;
  }

  .pagination-link:hover, .pagination-link.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
  }

  .breadcrumb {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    font-size: 0.9rem;
    margin-bottom: 20px;
  }

  .breadcrumb a {
    color: #667eea;
    text-decoration: none;
  }

  .breadcrumb a:hover {
    text-decoration: underline;
  }

  .partner-detail-hero-inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
  }

  .partner-detail-subtitle {
    opacity: 0.95;
    margin-top: 8px;
  }

  .partner-link {
    color: #667eea;
    text-decoration: none;
  }

  .partner-back-link {
    display: block;
    margin-top: 20px;
    padding: 10px 16px;
    background: #f0f0f0;
    color: #333;
    text-decoration: none;
    border-radius: 4px;
    text-align: center;
    font-weight: 600;
    transition: all 0.2s;
  }

  .partner-back-link:hover {
    background: #667eea;
    color: #fff;
  }

  .partner-companies-title {
    margin-bottom: 24px;
    font-size: 1.3rem;
  }

  @media (max-width: 1000px) {
    .partner-detail-content {
      grid-template-columns: 1fr;
    }

    .partner-detail-sidebar {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 12px;
      height: auto;
    }

    .partner-info-section {
      margin-bottom: 12px;
    }
  }

  @media (max-width: 768px) {
    .partner-detail-hero h1 {
      font-size: 1.5rem;
    }

    .companies-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<main class="site-main">
  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>">Home</a> / <a href="<?php echo htmlspecialchars(url('/partners'), ENT_QUOTES, 'UTF-8'); ?>">Partners</a> / <?php echo htmlspecialchars($partnerName, ENT_QUOTES); ?>
  </div>

  <!-- Hero Section -->
  <div class="partner-detail-hero">
    <div class="partner-detail-hero-inner">
      <h1><?php echo htmlspecialchars($partnerName, ENT_QUOTES); ?></h1>
      <p class="partner-detail-subtitle">
        <?php echo htmlspecialchars($partner['description'] ?? 'Data partner for HAIPULSE Business Directory', ENT_QUOTES); ?>
      </p>
    </div>
  </div>

  <!-- Content Section -->
  <div class="partner-detail-content">
    <!-- Sidebar -->
    <div class="partner-detail-sidebar">
      <div class="partner-icon-large">
        <?php 
        $icons = [
          'government' => 'ðŸ›ï¸',
          'chamber' => 'ðŸ¢',
          'association' => 'ðŸ¤',
          'corporate' => 'ðŸ’¼',
          'api' => 'ðŸ”Œ',
          'default' => 'ðŸŒ'
        ];
        $sourceType = strtolower($partner['source_type'] ?? 'default');
        echo $icons[$sourceType] ?? $icons['default'];
        ?>
      </div>

      <div class="partner-info-section">
        <div class="partner-info-label">Source Type</div>
        <div class="partner-info-value">
          <?php echo htmlspecialchars(ucfirst($partner['source_type'] ?? 'Unknown'), ENT_QUOTES); ?>
        </div>
      </div>

      <div class="partner-info-section">
        <div class="partner-info-label">Companies Listed</div>
        <div class="partner-info-value">
          <?php echo number_format($totalCompanies); ?>
        </div>
      </div>

      <?php if (!empty($partner['website'])): ?>
        <div class="partner-info-section">
          <div class="partner-info-label">Website</div>
          <div class="partner-info-value">
            <a href="<?php echo htmlspecialchars($partner['website'], ENT_QUOTES); ?>" target="_blank" rel="noopener" class="partner-link">
              Visit Website â†’
            </a>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($partner['contact_email'])): ?>
        <div class="partner-info-section">
          <div class="partner-info-label">Contact</div>
          <div class="partner-info-value">
            <a href="mailto:<?php echo htmlspecialchars($partner['contact_email'], ENT_QUOTES); ?>" class="partner-link">
              <?php echo htmlspecialchars($partner['contact_email'], ENT_QUOTES); ?>
            </a>
          </div>
        </div>
      <?php endif; ?>

      <a href="<?php echo htmlspecialchars(url('/partners'), ENT_QUOTES, 'UTF-8'); ?>" class="partner-back-link">
        â† Back to Partners
      </a>
    </div>

    <!-- Companies List -->
    <div class="companies-list">
      <h2 class="partner-companies-title">Companies from <?php echo htmlspecialchars($partnerName, ENT_QUOTES); ?></h2>

      <?php if (!empty($companies)): ?>
        <div class="companies-grid">
          <?php foreach ($companies as $company): ?>
            <?php $companyName = display_text($company['display_name'] ?? $company['company_name'] ?? $company['name'] ?? 'Business'); ?>
            <a href="<?php echo htmlspecialchars(url('/company/' . rawurlencode((string)($company['slug'] ?? $company['id']))), ENT_QUOTES, 'UTF-8'); ?>" class="company-item">
              <div class="company-item-title"><?php echo htmlspecialchars($companyName, ENT_QUOTES); ?></div>
              <div class="company-item-meta">
                <?php if (!empty($company['emirate'])): ?>
                  <span>ðŸ“ <?php echo htmlspecialchars($company['emirate'], ENT_QUOTES); ?></span>
                <?php endif; ?>
                <?php if ($company['verified'] ?? false): ?>
                  <span>âœ“ Verified</span>
                <?php endif; ?>
              </div>
              <?php if (!empty($company['description'])): ?>
                <div class="company-item-description">
                  <?php echo htmlspecialchars(substr($company['description'], 0, 80), ENT_QUOTES); ?>...
                </div>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <div class="pagination">
            <?php if ($page > 1): ?>
              <a href="?page=1" class="pagination-link">â† First</a>
              <a href="?page=<?php echo $page - 1; ?>" class="pagination-link">â† Previous</a>
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
              <a href="?page=<?php echo $page + 1; ?>" class="pagination-link">Next â†’</a>
              <a href="?page=<?php echo $totalPages; ?>" class="pagination-link">Last â†’</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <div class="no-companies">
          <h3>No Companies Listed</h3>
          <p>Currently, no companies from this source are listed in our directory.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

