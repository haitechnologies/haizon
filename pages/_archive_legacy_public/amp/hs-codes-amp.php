<?php
/**
 * AMP Page: HS Codes Listing
 * Route: /trade/hs-codes/amp
 * 
 * Mobile-optimized AMP version of HS codes directory
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/frontend/HSCodes.php';

$hsCodesModel = new HSCodes($conn);

// Get parameters
$search = trim((string)($_GET['search'] ?? ''));
$page = 1;

// Support exact lookup for one or more codes, same behavior as non-AMP page.
$rawParts = preg_split('/[\s,;|]+/', $search);
$parsedCodes = [];
if (is_array($rawParts)) {
  foreach ($rawParts as $part) {
    $candidate = trim((string)$part);
    if ($candidate !== '' && preg_match('/^[0-9]{2,14}(?:\.[0-9]{2})*$/', $candidate)) {
      $parsedCodes[] = $candidate;
    }
  }
}
$parsedCodes = array_values(array_unique($parsedCodes));

$hasLetters = preg_match('/[A-Za-z\x{0600}-\x{06FF}]/u', $search) === 1;
$isCodeSearch = !empty($parsedCodes) && !$hasLetters;

$perPage = 50;
$offset = ($page - 1) * $perPage;
$maxVisibleCodes = 50;

// Get HS codes
$options = [
    'lang' => 'en',
  'search' => $isCodeSearch ? '' : $search,
  'codes' => $isCodeSearch ? $parsedCodes : [],
    'limit' => $perPage,
    'offset' => $offset
];

$hsCodes = $hsCodesModel->getAll($options);
$totalCodes = min((int)$hsCodesModel->getCount($options), $maxVisibleCodes);

// Load Arabic descriptions for the current result set so AMP cards can show EN + AR together.
$hsArabicTextById = [];
if (!empty($hsCodes) && isset($conn) && $conn instanceof mysqli) {
  $ids = array_values(array_unique(array_filter(array_map(function ($row) {
    return (int)($row['id'] ?? 0);
  }, $hsCodes))));

  if (!empty($ids)) {
    $idsSql = implode(',', $ids);
    $arQuery = "
      SELECT hs_code_id, short_desc, long_desc
      FROM " . DB::HS_CODE_TEXTS . "
      WHERE lang = 'ar' AND hs_code_id IN ($idsSql)
    ";
    $arResult = $conn->query($arQuery);
    if ($arResult) {
      while ($arRow = $arResult->fetch_assoc()) {
        $hsArabicTextById[(int)$arRow['hs_code_id']] = [
          'short_desc' => (string)($arRow['short_desc'] ?? ''),
          'long_desc' => (string)($arRow['long_desc'] ?? ''),
        ];
      }
    }
  }
}

$buildAmpHsCodesUrl = function (array $overrides = []) use ($search) {
  $params = [];
  if ($search !== '') {
    $params['search'] = $search;
  }

  foreach ($overrides as $key => $value) {
    if ($value === null || $value === '') {
      unset($params[$key]);
    } else {
      $params[$key] = $value;
    }
  }

  return url('/trade/hs-codes/amp') . (empty($params) ? '' : ('?' . http_build_query($params)));
};

// Page meta
$pageTitle = "HS Codes Directory - UAE Trade Portal (AMP)";
$pageDescription = "Browse 13,000+ Harmonized System (HS) codes used in international trade. Fast mobile experience.";
$canonicalUrl = url('/trade/hs-codes');

// Schema.org structured data
$schemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'HS Codes Directory',
    'description' => 'Complete directory of Harmonized System codes for international trade',
    'numberOfItems' => $totalCodes,
    'url' => $canonicalUrl
];

// AMP components needed
$ampComponents = [
    ['name' => 'amp-form', 'src' => 'https://cdn.ampproject.org/v0/amp-form-0.1.js']
];

$pageCustomCss = <<<'CSS'
/* Additional styles for HS Codes listing */
.breadcrumb {
  background: white;
  padding: 12px 0;
  border-bottom: 1px solid #e0e0e0;
  margin-bottom: 24px;
}

.breadcrumb-list {
  list-style: none;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  font-size: 0.9rem;
}

.breadcrumb-item::after {
  content: '>';
  margin-left: 8px;
  color: #999;
}

.breadcrumb-item:last-child::after {
  content: '';
}

.breadcrumb-link {
  color: #0f4ad8;
  text-decoration: none;
}

.search-section {
  background: white;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 24px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.filter-tabs {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding: 8px 0;
  margin-bottom: 16px;
  -webkit-overflow-scrolling: touch;
}

.filter-tab {
  padding: 8px 16px;
  border: 1px solid #ddd;
  border-radius: 20px;
  white-space: nowrap;
  text-decoration: none;
  color: #666;
  font-size: 0.9rem;
  background: white;
  transition: all 0.2s;
}

.filter-tab.active {
  background: #0f4ad8;
  color: white;
  border-color: #0f4ad8;
}

.hs-code-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  text-decoration: none;
  display: block;
  color: inherit;
  transition: all 0.2s;
}

.hs-code-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  border-color: #0f4ad8;
}

.code-number {
  font-size: 1.2rem;
  font-weight: 600;
  color: #0f4ad8;
  margin-bottom: 8px;
}

.code-description {
  color: #666;
  font-size: 0.95rem;
  line-height: 1.5;
  margin-bottom: 8px;
}

.code-description-ar {
  color: #54667d;
  font-size: 0.9rem;
  line-height: 1.5;
  margin-bottom: 8px;
  direction: rtl;
  text-align: right;
}

.code-old {
  display: inline-block;
  font-size: 0.78rem;
  color: #8b5e00;
  background: #fff6df;
  border: 1px solid #f0dfae;
  border-radius: 999px;
  padding: 4px 9px;
  margin-bottom: 8px;
}

.code-meta {
  display: flex;
  gap: 12px;
  font-size: 0.85rem;
  color: #999;
}

.pagination {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-top: 24px;
  flex-wrap: wrap;
}

.page-link {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  text-decoration: none;
  color: #666;
  min-width: 40px;
  text-align: center;
}

.page-link.active {
  background: #0f4ad8;
  color: white;
  border-color: #0f4ad8;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  padding: 16px;
  border-radius: 8px;
  text-align: center;
  border: 1px solid #e0e0e0;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #0f4ad8;
}

.stat-label {
  font-size: 0.85rem;
  color: #999;
  margin-top: 4px;
}

CSS;

include __DIR__ . '/amp-header.php';
?>

<div class="container">
  
  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <ul class="breadcrumb-list">
      <li class="breadcrumb-item"><a href="<?php echo url('/'); ?>" class="breadcrumb-link">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo url('/trade'); ?>" class="breadcrumb-link">Trade Portal</a></li>
      <li class="breadcrumb-item">HS Codes</li>
    </ul>
  </div>
  
  <!-- Page Header -->
  <div class="text-center mb-3">
    <h1 style="font-size: 1.8rem; margin-bottom: 8px; color: #0f4ad8;">
      HS Code Directory
    </h1>
    <p class="text-muted">
      Browse 13,000+ harmonized trade codes
    </p>
  </div>
  
  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-value"><?php echo number_format($totalCodes); ?></div>
      <div class="stat-label">Total Codes</div>
    </div>
    <div class="stat-card">
      <div class="stat-value">50</div>
      <div class="stat-label">Codes per page</div>
    </div>
  </div>
  
  <!-- Search & Filters -->
  <div class="search-section">
    
    <!-- Search Form -->
    <form method="GET" action="<?php echo url('/trade/hs-codes/amp'); ?>" target="_top">
      <div class="form-group">
        <textarea
          name="search" 
          placeholder="Example: 730890900010, 850440110000 or steel bolts"
          class="form-control"
          rows="3"><?php echo htmlspecialchars($search, ENT_QUOTES); ?></textarea>
      </div>
      
      <button type="submit" class="btn btn-primary" style="width: 100%;">
        Search
      </button>
    </form>
  </div>
  
  <!-- Results Info -->
  <?php if ($search !== ''): ?>
  <div class="mb-2">
    <p class="text-muted" style="font-size: 0.9rem;">
      <?php if ($isCodeSearch): ?>
        Requested code results:
      <?php else: ?>
        Keyword search results for:
      <?php endif; ?>
      <strong>"<?php echo htmlspecialchars($search, ENT_QUOTES); ?>"</strong>
      (<?php echo number_format($totalCodes); ?> results)
    </p>
  </div>
  <?php endif; ?>
  
  <!-- HS Codes List -->
  <div class="codes-list">
    <?php if (empty($hsCodes)): ?>
      <div class="card text-center" style="padding: 40px;">
        <p class="text-muted">No codes found</p>
        <a href="<?php echo htmlspecialchars($buildAmpHsCodesUrl(['search' => null, 'page' => null]), ENT_QUOTES); ?>" class="btn btn-secondary mt-2">
          Reset Filters
        </a>
      </div>
    <?php else: ?>
      <?php foreach ($hsCodes as $code): ?>
        <?php
          $codeValue = (string)($code['code'] ?? '');
          $oldCodeValue = trim((string)($code['old_code'] ?? ''));
          $descriptionEn = (string)($code['short_desc'] ?? $code['long_desc'] ?? $codeValue);
          $codeId = (int)($code['id'] ?? 0);
          $arText = $hsArabicTextById[$codeId] ?? [];
          $descriptionAr = trim((string)($arText['short_desc'] ?? $arText['long_desc'] ?? ''));
        ?>
        <a href="<?php echo url('/trade/hs-code/' . urlencode($codeValue) . '/amp'); ?>" class="hs-code-card">
          <div class="code-number"><?php echo htmlspecialchars($codeValue, ENT_QUOTES); ?></div>
          <?php if ($oldCodeValue !== ''): ?>
          <div class="code-old">Old HS: <?php echo htmlspecialchars($oldCodeValue, ENT_QUOTES); ?></div>
          <?php endif; ?>
          <div class="code-description"><?php echo htmlspecialchars($descriptionEn, ENT_QUOTES); ?></div>
          <?php if ($descriptionAr !== ''): ?>
          <div class="code-description-ar"><?php echo htmlspecialchars($descriptionAr, ENT_QUOTES); ?></div>
          <?php endif; ?>
          <div class="code-meta">
            <?php if (isset($code['children_count']) && $code['children_count'] > 0): ?>
              <span>â€¢ <?php echo $code['children_count']; ?> sub-codes</span>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  
  <!-- Non-AMP Link -->
  <div class="context-nav context-nav-tight">
    <div class="context-nav-title">Explore more AMP pages</div>
    <div class="context-nav-links">
      <a href="<?php echo url('/listings/amp'); ?>" class="context-nav-link">Browse Companies</a>
      <a href="<?php echo url('/blog/amp'); ?>" class="context-nav-link">Business Blog</a>
      <a href="<?php echo url('/about/amp'); ?>" class="context-nav-link">About HAIPULSE</a>
      <a href="<?php echo url('/contact/amp'); ?>" class="context-nav-link">Contact Team</a>
    </div>
  </div>

  <!-- Non-AMP Link -->
  <div class="text-center mt-3">
    <p class="text-muted" style="font-size: 0.85rem;">
      <?php $fullUrl = url('/trade/hs-codes') . ($search !== '' ? ('?search=' . urlencode($search)) : ''); ?>
      <a href="<?php echo htmlspecialchars($fullUrl, ENT_QUOTES); ?>" style="color: #0f4ad8;">
        View full version
      </a>
    </p>
  </div>
  
</div>

<?php include __DIR__ . '/amp-footer.php'; ?>

