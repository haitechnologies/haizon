<?php
/**
 * Page: Business Detail (NEW DESIGN)
 * Route: /company-detail OR /business-detail
 * Description: Company profile page with full details
 * Author: Development Team
 * Created: February 28, 2026
 */

// ============================================
// SECTION 1: DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';

require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/IpRateLimiter.php';
require_once __DIR__ . '/../classes/frontend/Favorites.php';
require_once __DIR__ . '/../classes/BusinessListingPlan.php';
require_once __DIR__ . '/../classes/frontend/Companies.php';
require_once __DIR__ . '/../classes/frontend/PublicAds.php';
require_once __DIR__ . '/../includes/helpers.php';

// Router pre-checks may include config/database before this file. Rehydrate $conn from globals when needed.
if (!isset($conn) || !($conn instanceof mysqli)) {
  $conn = $GLOBALS['DB']['MSQLI'] ?? null;
}
if (!($conn instanceof mysqli)) {
  http_response_code(500);
  exit('Database connection unavailable.');
}

function companyDetailCachePath($suffix) {
  return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'haipulse_company_detail_' . $suffix . '.json';
}

function readCompanyDetailCache($cachePath, $ttlSeconds) {
  if (!is_file($cachePath)) {
    return null;
  }
  if ((time() - (int)@filemtime($cachePath)) > $ttlSeconds) {
    return null;
  }
  $raw = @file_get_contents($cachePath);
  if ($raw === false || $raw === '') {
    return null;
  }
  $decoded = json_decode($raw, true);
  return is_array($decoded) ? $decoded : null;
}

function writeCompanyDetailCache($cachePath, $payload) {
  @file_put_contents($cachePath, json_encode($payload, JSON_UNESCAPED_SLASHES));
}

// Anti-scraping throttle for company detail pages.
IpRateLimiter::init($conn);
$rateLimit = IpRateLimiter::check('company_detail_page', 180, 60);
if (empty($rateLimit['allowed'])) {
  http_response_code(429);
  header('Retry-After: 60');
  exit('Too many requests. Please try again in a minute.');
}

// ============================================
// SECTION 2: GET COMPANY BY SLUG OR ID
// ============================================

$slug = $GLOBALS['route_params']['company_slug'] ?? ($_GET['slug'] ?? '');
$id = intval($_GET['id'] ?? 0);

if (empty($slug) && empty($id)) {
    header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/listings'));
    exit;
}


// Query company details using Companies model (for consistency and future-proofing)
$CompaniesModel = new Companies($conn);
if (!empty($slug)) {
    $company = $CompaniesModel->getBySlug($slug, false); // Don't auto-increment views here
} else {
    $company = $CompaniesModel->getById($id);
}

if (!$company) {
  http_response_code(404);
  $pageTitle = 'Business Not Found';
  include __DIR__ . '/404.php';
  exit;
}

// Only record a view if not a bot/crawler

// Local bot/crawler detection (simple UA check)
function _isBotOrCrawler() {
  $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
  return (
    strpos($ua, 'bot') !== false ||
    strpos($ua, 'crawl') !== false ||
    strpos($ua, 'spider') !== false ||
    strpos($ua, 'slurp') !== false ||
    strpos($ua, 'bingpreview') !== false ||
    strpos($ua, 'mediapartners-google') !== false
  );
}
if (!_isBotOrCrawler()) {
  $CompaniesModel->recordView((int)$company['id']);
}

// Fetch current view count from hai_companies table
$viewCount = (int)($company['views'] ?? 0);

// ============================================
// SECTION 2B: CHECK IF FAVORITED
// ============================================
$isFavorited = false;
if (isset($_SESSION['frontend_user_id']) && !empty($_SESSION['frontend_user_id'])) {
    $userId = (int)$_SESSION['frontend_user_id'];
    $Favorites = new Favorites($conn);
    $isFavorited = $Favorites->isFavorite($userId, $company['id']);
}

// ============================================
// SECTION 2C: FAVORITE TOGGLE (AJAX)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'toggle_favorite')) {
  header('Content-Type: application/json; charset=utf-8');

  if (!validate_csrf_token_frontend($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security validation failed.']);
    exit;
  }

  if (empty($_SESSION['frontend_user_id'])) {
    http_response_code(401);
    $redirect = urlencode($_SERVER['REQUEST_URI'] ?? ('/company/' . ($company['slug'] ?? '')));
    echo json_encode([
      'success' => false,
      'message' => 'Please sign in to save companies.',
      'login_url' => url('/login?redirect=' . $redirect)
    ]);
    exit;
  }

  $requestedCompanyId = (int)($_POST['company_id'] ?? 0);
  $currentCompanyId = (int)$company['id'];
  if ($requestedCompanyId <= 0 || $requestedCompanyId !== $currentCompanyId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid company selection.']);
    exit;
  }

  $toggleUserId = (int)$_SESSION['frontend_user_id'];
  $favoritesModel = new Favorites($conn);
  $alreadySaved = $favoritesModel->isFavorite($toggleUserId, $requestedCompanyId);

  $ok = $alreadySaved
    ? $favoritesModel->removeFavorite($toggleUserId, $requestedCompanyId)
    : $favoritesModel->addFavorite($toggleUserId, $requestedCompanyId);

  if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to update saved status right now.']);
    exit;
  }

  $savedNow = !$alreadySaved;
  echo json_encode([
    'success' => true,
    'saved' => $savedNow,
    'message' => $savedNow ? 'Company saved to favorites.' : 'Company removed from favorites.'
  ]);
  exit;
}

$favoriteCsrfToken = csrf_token_frontend();

// ============================================
// SECTION 2C: LISTING PLAN FEATURES
// ============================================
$companyId  = (int)$company['id'];
$listingSub = null;
$planSlug   = 'free';
$planCacheKey = md5('plan_' . $companyId);
$planCachePath = companyDetailCachePath($planCacheKey);
$cachedPlan = readCompanyDetailCache($planCachePath, 300);
if (is_array($cachedPlan)) {
  $listingSub = $cachedPlan['listing_sub'] ?? null;
  $planSlug = $cachedPlan['plan_slug'] ?? 'free';
} else {
  try {
      $listingSub = BusinessListingPlan::getCompanySubscription($conn, $companyId);
      $planSlug   = $listingSub['plan_slug'] ?? 'free';
      writeCompanyDetailCache($planCachePath, [
        'listing_sub' => $listingSub,
        'plan_slug' => $planSlug,
      ]);
  } catch (Throwable $e) {
      $listingSub = null;
      $planSlug = 'free';
  }
}

// Banners (plan-limited images) — feature decommissioned
$listingBanners = [];

// Brands (plan-limited) — feature decommissioned
$listingBrands = [];

// Plan-based keywords (separate from legacy meta_keywords) — feature decommissioned
$listingKeywords = [];
// Fall back to legacy meta_keywords column if no dedicated keywords
if (empty($listingKeywords) && !empty($company['keywords'])) {
    $listingKeywords = array_filter(array_map('trim', explode(',', $company['keywords'])));
}

// ============================================
// SECTION 2G: SIMILAR BUSINESSES
// ============================================
$similarBusinesses = [];
if (!empty($company['city'])) {
  $similarCacheKey = md5('similar_' . $companyId . '_' . (int)($company['primary_category_id'] ?? 0) . '_' . strtolower((string)$company['city']));
  $similarCachePath = companyDetailCachePath($similarCacheKey);
  $cachedSimilar = readCompanyDetailCache($similarCachePath, 300);

  if (is_array($cachedSimilar)) {
    $similarBusinesses = $cachedSimilar;
  } else {
    // Smart ranking: prioritize same-category companies, then verified/contact-rich records.
    $categoryId = (int)($company['primary_category_id'] ?? 0);
    $similarQuery = "
      SELECT
        id,
        company_name,
        slug,
        city,
        telephone AS phone,
        email,
        website,
        LEFT(COALESCE(company_profile, services, ''), 200) AS description,
        verified,
        (
          CASE WHEN primary_category_id = ? THEN 3 ELSE 0 END
          + CASE WHEN verified = 1 THEN 1 ELSE 0 END
          + CASE
            WHEN COALESCE(telephone, '') <> ''
              OR COALESCE(email, '') <> ''
              OR COALESCE(website, '') <> ''
            THEN 1 ELSE 0
            END
        ) AS relevance_score
      FROM `" . DB::COMPANIES . "`
      WHERE is_active = 1
        AND publish = 1
        AND city = ?
        AND id != ?
      ORDER BY relevance_score DESC, company_name ASC
      LIMIT 6
    ";
    $simStmt = $conn->prepare($similarQuery);
    if ($simStmt) {
      $simStmt->bind_param('isi', $categoryId, $company['city'], $companyId);
      $simStmt->execute();
      $simResult = $simStmt->get_result();
      while ($simRow = $simResult->fetch_assoc()) {
        $similarBusinesses[] = $simRow;
      }
      $simStmt->close();
    }

    writeCompanyDetailCache($similarCachePath, $similarBusinesses);
  }
}

// ============================================
// SECTION 2H: BROWSE BY STATES (UAE EMIRATES ONLY)
// ============================================
$uaeEmirates = ['Abu Dhabi', 'Dubai', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah'];
$allStates = $uaeEmirates;
if (!empty($company['city']) && in_array($company['city'], $uaeEmirates, true)) {
  $allStates = array_values(array_unique(array_merge([$company['city']], $uaeEmirates)));
}

// ============================================
// SECTION 2I: BROWSE BY ALPHABETS
// ============================================
$alphabets = array_merge(range('A', 'Z'), ['0-9']);

function normalizeExternalWebsite($rawUrl) {
  $value = trim((string)$rawUrl);
  if ($value === '') {
    return '';
  }

  if (!preg_match('#^https?://#i', $value)) {
    $value = 'https://' . ltrim($value, '/');
  }

  return $value;
}

// ============================================
// SECTION 3: FORMAT DATA
// ============================================

$companyNamePlain = display_text($company['company_name'] ?? '');
$companyName = htmlspecialchars($companyNamePlain, ENT_QUOTES, 'UTF-8');
$location = trim((($company['city'] ?? '') ? $company['city'] : '') . ((($company['city'] ?? '') && ($company['emirate'] ?? '')) ? ', ' : '') . (($company['emirate'] ?? '') ? ucwords(str_replace('-', ' ', $company['emirate'])) : ''));
$companyWebsiteUrl = normalizeExternalWebsite($company['website'] ?? '');
$companyWebsiteLabel = parse_url($companyWebsiteUrl, PHP_URL_HOST) ?: ($company['website'] ?? '');
$hasContact = !empty($company['phone']) || !empty($company['mobile']) || !empty($company['email']) || $companyWebsiteUrl !== '';
$hasSocial = !empty($company['facebook']) || !empty($company['twitter']) || !empty($company['instagram']) || !empty($company['linkedin']);

$companySidebarAds = [];
try {
  $publicAdsModel = new PublicAds($conn);
  $companySidebarAds = $publicAdsModel->getAdsForSlot('company_sidebar', [
    'page_type' => 'company_detail',
    'category' => (string)($company['category_name'] ?? ''),
    'city' => (string)($company['city'] ?? ''),
    'keyword' => (string)($company['company_name'] ?? ''),
    'tags' => array_values(array_filter(array_map('strval', (array)$listingKeywords))),
  ], 1);

  if (empty($companySidebarAds)) {
    $companySidebarAds = $publicAdsModel->getAdsForSlot('listings_inline', [
      'page_type' => 'company_detail',
      'category' => (string)($company['category_name'] ?? ''),
      'city' => (string)($company['city'] ?? ''),
      'keyword' => (string)($company['company_name'] ?? ''),
    ], 1);
  }

  if (empty($companySidebarAds)) {
    $companySidebarAds = $publicAdsModel->getAdsForSlot('global_footer', [
      'page_type' => 'company_detail',
    ], 1);
  }
} catch (Throwable $e) {
  $companySidebarAds = [];
}

// Page metadata
$pageTitle = $companyNamePlain . ' - UAE Business Directory';
$pageDescription = substr(display_text($company['description'] ?? ('View business details for ' . $companyNamePlain)), 0, 160);
$ampHtmlUrl = url('/company/' . ($company['slug'] ?? '') . '/amp');
$bodyClass = 'page-company-detail';
?>

<?php include __DIR__ . '/../includes/layout/header.php'; ?>

<!-- Structured Data: Only output inside HTML, after header -->
<?php if (!empty($company['rating']) && !empty($company['review_count'])): ?>
<script type="application/ld+json">
<?php echo json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'LocalBusiness',
  'name' => $companyNamePlain,
  'telephone' => $company['phone'] ?? null,
  'address' => $location ? [
    '@type' => 'PostalAddress',
    'addressLocality' => $location,
    'addressCountry' => 'AE',
  ] : null,
  'aggregateRating' => [
    '@type' => 'AggregateRating',
    'ratingValue' => number_format($company['rating'], 1),
    'reviewCount' => $company['review_count'],
  ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
</script>
<?php endif; ?>

  <main id="main-content" class="section">
    <div class="container-narrow">
      <article class="card-ui detail-box company-card-spaced">
        <div class="business-top">
          <?php if (!empty($company['featured'])): ?>
            <span class="pill">Featured</span>
          <?php endif; ?>
          <?php if ($company['verified'] || (!empty($listingSub['has_verified_mark']))): ?>
            <span class="pill company-pill-verified">âœ“ Verified</span>
          <?php endif; ?>
          <?php if (!empty($listingSub['has_green_badge'])): ?>
            <span class="pill company-pill-green">ðŸŒ¿ Green Business</span>
          <?php endif; ?>
          <?php if (!empty($listingSub['has_iso_accolades'])): ?>
            <span class="pill company-pill-iso">ðŸ… ISO Certified</span>
          <?php endif; ?>
          <?php if (!empty($company['rating']) && !empty($company['review_count'])): ?>
            <span class="rating">
              â˜… <?php echo number_format($company['rating'], 1); ?> 
              (<?php echo number_format($company['review_count']); ?> reviews)
            </span>
          <?php endif; ?>
        </div>
        <h1 class="company-title"><?php echo $companyName; ?></h1>
        <p class="meta-line">
          <?php echo htmlspecialchars($company['category_name'] ?? 'Business', ENT_QUOTES, 'UTF-8'); ?>
          <?php if ($location): ?>
            Â· <?php echo htmlspecialchars($location, ENT_QUOTES, 'UTF-8'); ?>
          <?php endif; ?>
          <?php if (!empty($company['license_number'])): ?>
            Â· License: <?php echo htmlspecialchars($company['license_number'], ENT_QUOTES, 'UTF-8'); ?>
          <?php endif; ?>
          <?php if ($viewCount > 0): ?>
            Â· <span title="Profile Views"><i class="fa fa-eye"></i> <?php echo number_format($viewCount); ?> views</span>
          <?php endif; ?>
        </p>
        
        <?php if (!empty($company['description'])): ?>
          <p class="company-desc">
            <?php echo nl2br(htmlspecialchars($company['description'], ENT_QUOTES, 'UTF-8')); ?>
          </p>
        <?php endif; ?>
        
        <?php if (!empty($company['established_year']) || !empty($company['employee_count'])): ?>
          <div class="quick-grid company-quick-grid">
            <?php if (!empty($company['established_year'])): ?>
              <span class="quick-item">ðŸ“… Est. <?php echo htmlspecialchars($company['established_year'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
            <?php if (!empty($company['employee_count'])): ?>
              <span class="quick-item">ðŸ‘¥ <?php echo htmlspecialchars($company['employee_count'], ENT_QUOTES, 'UTF-8'); ?> employees</span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </article>

      <?php if (!empty($listingBanners)): ?>
      <!-- â”€â”€ PLAN BANNERS (OPTIMIZED) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
      <div class="card-ui company-banner-card">
        <?php if (count($listingBanners) === 1): ?>
          <?php
            // Single banner - render as eager (above fold)
            $banner = $listingBanners[0];
            $bannerHtml = '
              <img src="' . htmlspecialchars($banner['image_path'], ENT_QUOTES, 'UTF-8') . '"
                   alt="' . htmlspecialchars($banner['alt_text'] ?: $companyName, ENT_QUOTES, 'UTF-8') . '"
                   class="company-banner-img"
                   loading="eager"
                   decoding="async">
            ';
            echo $bannerHtml;
          ?>
        <?php else: ?>
          <!-- Multiple banners in carousel -->
          <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
              <?php foreach ($listingBanners as $bi => $banner): ?>
              <div class="carousel-item<?php echo $bi === 0 ? ' active' : ''; ?>">
                <?php
                  // Eager load first banner (active), lazy load others
                  $loadAttr = $bi === 0 ? 'eager' : 'lazy';
                  $bannerImg = '
                    <img src="' . htmlspecialchars($banner['image_path'], ENT_QUOTES, 'UTF-8') . '"
                         class="d-block w-100"
                         alt="' . htmlspecialchars($banner['alt_text'] ?: $companyName, ENT_QUOTES, 'UTF-8') . '"
                        class="d-block w-100 company-carousel-img"
                         loading="' . $loadAttr . '"
                         decoding="async">
                  ';
                  echo $bannerImg;
                ?>
                <?php if ($banner['title']): ?>
                <div class="carousel-caption d-none d-md-block">
                  <p class="mb-0 bg-dark bg-opacity-50 rounded px-2 py-1 d-inline-block">
                    <?php echo htmlspecialchars($banner['title'], ENT_QUOTES, 'UTF-8'); ?>
                  </p>
                </div>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
              <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
              <span class="carousel-control-next-icon"></span>
            </button>
          </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="list-layout">
        <aside>
          <?php if (!empty($companySidebarAds)): ?>
            <?php
              $publicAds = $companySidebarAds;
              $publicAdSlot = 'sidebar';
              $publicAdHeading = 'Sponsored business tools';
              include __DIR__ . '/../includes/partials/public-ad-slot.php';
            ?>
          <?php endif; ?>

          <?php if ($hasSocial): ?>
          <div class="card-ui detail-box company-card-spaced">
            <h3 class="company-card-title">Social Media</h3>
            <div class="company-social-row">
              <?php if ($company['facebook']): ?>
                <a href="<?php echo htmlspecialchars($company['facebook'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn-ui btn-light-ui">Facebook</a>
              <?php endif; ?>
              <?php if ($company['twitter']): ?>
                <a href="<?php echo htmlspecialchars($company['twitter'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn-ui btn-light-ui">Twitter</a>
              <?php endif; ?>
              <?php if ($company['instagram']): ?>
                <a href="<?php echo htmlspecialchars($company['instagram'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn-ui btn-light-ui">Instagram</a>
              <?php endif; ?>
              <?php if ($company['linkedin']): ?>
                <a href="<?php echo htmlspecialchars($company['linkedin'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn-ui btn-light-ui">LinkedIn</a>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

        </aside>

        <section>
          <?php if (!empty($company['operating_hours'])): ?>
          <article class="card-ui detail-box company-card-spaced">
            <h3 class="company-card-title">Operating Hours</h3>
            <?php echo nl2br(htmlspecialchars($company['operating_hours'], ENT_QUOTES, 'UTF-8')); ?>
          </article>
          <?php endif; ?>

          <?php if (!empty($listingKeywords)): ?>
          <article class="card-ui detail-box company-card-spaced">
            <h3 class="company-card-title">Services &amp; Keywords</h3>
            <div class="company-keywords-row">
              <?php foreach ($listingKeywords as $kw): ?>
                <span class="pill company-keyword-pill">
                  <?php echo htmlspecialchars($kw, ENT_QUOTES, 'UTF-8'); ?>
                </span>
              <?php endforeach; ?>
            </div>
          </article>
          <?php endif; ?>

          <?php if (!empty($listingBrands)): ?>
          <article class="card-ui detail-box company-card-spaced">
            <h3 class="company-card-title">Brands &amp; Products</h3>
            <div class="company-brands-row">
              <?php foreach ($listingBrands as $brand): ?>
                <?php $brandWebsiteUrl = normalizeExternalWebsite($brand['website'] ?? ''); ?>
                <?php if ($brandWebsiteUrl !== ''): ?>
                  <a href="<?php echo htmlspecialchars($brandWebsiteUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" title="<?php echo htmlspecialchars($brand['brand_name'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                <?php if (!empty($brand['logo_path'])): ?>
                  <img src="<?php echo htmlspecialchars($brand['logo_path'], ENT_QUOTES, 'UTF-8'); ?>"
                       alt="<?php echo htmlspecialchars($brand['brand_name'], ENT_QUOTES, 'UTF-8'); ?>"
                       class="company-brand-logo"
                       loading="lazy"
                       decoding="async">
                <?php else: ?>
                  <span class="pill company-brand-pill">
                    <?php echo htmlspecialchars($brand['brand_name'], ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                <?php endif; ?>
                <?php if ($brandWebsiteUrl !== ''): ?></a><?php endif; ?>
              <?php endforeach; ?>
            </div>
          </article>
          <?php endif; ?>

          <?php if ($hasContact): ?>
          <article class="card-ui detail-box">
            <h3 class="company-card-title">Contact Information</h3>
            <?php if (!empty($company['phone'])): ?>
              <p class="meta-line">ðŸ“ž Phone: <a href="tel:<?php echo htmlspecialchars($company['phone'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($company['phone'], ENT_QUOTES, 'UTF-8'); ?></a></p>
            <?php endif; ?>
            <?php if (!empty($company['mobile'])): ?>
              <p class="meta-line">ðŸ“± Mobile: <a href="tel:<?php echo htmlspecialchars($company['mobile'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($company['mobile'], ENT_QUOTES, 'UTF-8'); ?></a></p>
            <?php endif; ?>
            <?php if ($company['email']): ?>
              <p class="meta-line">âœ‰ Email: <a href="mailto:<?php echo htmlspecialchars($company['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($company['email'], ENT_QUOTES, 'UTF-8'); ?></a></p>
            <?php endif; ?>
            <?php if ($companyWebsiteUrl !== ''): ?>
              <p class="meta-line">ðŸŒ Website: <a href="<?php echo htmlspecialchars($companyWebsiteUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($companyWebsiteLabel, ENT_QUOTES, 'UTF-8'); ?></a></p>
            <?php endif; ?>
            <?php if ($company['address']): ?>
              <p class="meta-line">ðŸ“ Address: <?php echo htmlspecialchars($company['address'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <div class="quick-grid company-quick-grid">
              <?php if (!empty($company['phone'])): ?>
                <a class="btn-ui btn-primary-ui" href="tel:<?php echo htmlspecialchars($company['phone'], ENT_QUOTES, 'UTF-8'); ?>">Call now</a>
              <?php endif; ?>
              <?php if (!empty($company['mobile'])): ?>
                <a class="btn-ui btn-light-ui" href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $company['mobile']); ?>" target="_blank" rel="noopener">WhatsApp</a>
              <?php endif; ?>
              <?php if ($company['lat'] && $company['lng']): ?>
                <a class="btn-ui btn-light-ui" href="https://maps.google.com/?q=<?php echo $company['lat']; ?>,<?php echo $company['lng']; ?>" target="_blank" rel="noopener">Directions</a>
              <?php endif; ?>
              <?php if ($companyWebsiteUrl !== ''): ?>
                <a class="btn-ui btn-light-ui" href="<?php echo htmlspecialchars($companyWebsiteUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Visit Website</a>
              <?php endif; ?>
              <button type="button" class="btn-ui company-favorite-btn <?php echo $isFavorited ? 'btn-primary-ui' : 'btn-light-ui'; ?>" id="favorite-btn" data-company-id="<?php echo (int)$company['id']; ?>" data-csrf-token="<?php echo htmlspecialchars($favoriteCsrfToken, ENT_QUOTES, 'UTF-8'); ?>" onclick="toggleFavorite(<?php echo $company['id']; ?>)">
                <span id="favorite-icon"><?php echo $isFavorited ? 'â™¥' : 'â™¡'; ?></span>
                <span id="favorite-text" class="ms-1"><?php echo $isFavorited ? 'Saved' : 'Save'; ?></span>
              </button>
            </div>
          </article>
          <?php endif; ?>

          <?php if (!empty($company['lat']) && !empty($company['lng'])): ?>
          <article class="card-ui detail-box company-map-wrap company-card-spaced">
            <h3 class="company-card-title">Location &amp; Map</h3>
            <iframe 
              width="100%" 
              height="250" 
              frameborder="0" 
              class="company-map-frame"
              src="https://maps.google.com/maps?q=<?php echo $company['lat']; ?>,<?php echo $company['lng']; ?>&output=embed"
              allowfullscreen>
            </iframe>
          </article>
          <?php endif; ?>
        </section>
      </div>

      <div class="card-ui company-claim-card">
        <p class="muted company-claim-text">
          <strong>Is this your business?</strong><br>
          Claim this listing to update information, add photos, respond to reviews, and more.
        </p>
        <a href="<?php echo url('/contact?claim=1&company_id=' . (int)$company['id'] . '&company_slug=' . rawurlencode((string)$company['slug'])); ?>" class="btn-ui btn-primary-ui company-claim-btn">Claim This Business</a>
      </div>

      <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
      <!-- SECTION: SIMILAR BUSINESSES IN SAME CITY                -->
      <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
      <?php if (!empty($similarBusinesses)): ?>
      <div class="card-ui company-section-card">
        <h2 class="company-section-heading">
          <i class="fa fa-star text-warning"></i> Similar Businesses
        </h2>
        <div class="similar-grid">
          <?php foreach ($similarBusinesses as $biz): ?>
            <div class="similar-item">
              <!-- Business Name with Badge -->
              <div class="similar-head">
                <h4 class="similar-title">
                  <a href="<?php echo url('/company/' . htmlspecialchars($biz['slug'], ENT_QUOTES, 'UTF-8')); ?>" class="similar-title-link">
                    <?php echo htmlspecialchars(substr($biz['company_name'], 0, 40), ENT_QUOTES, 'UTF-8'); ?>
                  </a>
                </h4>
                <?php if ($biz['verified']): ?>
                  <span class="similar-verified">âœ“ Verified</span>
                <?php endif; ?>
              </div>
              
              <!-- Description -->
              <p class="muted similar-desc">
                <?php echo htmlspecialchars(substr($biz['description'], 0, 120), ENT_QUOTES, 'UTF-8'); ?><?php echo strlen($biz['description'] ?? '') > 120 ? '...' : ''; ?>
              </p>
              
              <!-- Contact Information -->
              <div class="similar-contact">
                <?php if (!empty($biz['phone'])): ?>
                  <p>
                    <i class="fa fa-phone similar-contact-icon similar-phone-icon"></i>
                    <a href="tel:<?php echo htmlspecialchars($biz['phone'], ENT_QUOTES, 'UTF-8'); ?>" class="similar-link-reset similar-phone-link">
                      <?php echo htmlspecialchars($biz['phone'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                  </p>
                <?php endif; ?>
                <?php if (!empty($biz['email'])): ?>
                  <p>
                    <i class="fa fa-envelope similar-contact-icon similar-email-icon"></i>
                    <a href="mailto:<?php echo htmlspecialchars($biz['email'], ENT_QUOTES, 'UTF-8'); ?>" class="similar-link-reset similar-email-link">
                      <?php echo htmlspecialchars(substr($biz['email'], 0, 25), ENT_QUOTES, 'UTF-8'); ?><?php echo strlen($biz['email'] ?? '') > 25 ? '...' : ''; ?>
                    </a>
                  </p>
                <?php endif; ?>
                <?php if (!empty($biz['website'])): ?>
                  <?php $similarWebsiteUrl = normalizeExternalWebsite($biz['website']); ?>
                  <?php if ($similarWebsiteUrl !== ''): ?>
                  <p>
                    <i class="fa fa-globe similar-contact-icon similar-web-icon"></i>
                    <a href="<?php echo htmlspecialchars($similarWebsiteUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="similar-link-reset similar-web-link">
                      <?php echo htmlspecialchars(parse_url($similarWebsiteUrl, PHP_URL_HOST) ?: substr((string)$biz['website'], 0, 20), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                  </p>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
              
              <!-- Action Button -->
              <a href="<?php echo url('/company/' . htmlspecialchars($biz['slug'], ENT_QUOTES, 'UTF-8')); ?>" class="btn btn-sm btn-primary similar-action-btn">
                View Full Profile â†’
              </a>
            </div>
          <?php endforeach; ?>
        </div>
        
        <div class="similar-viewall-wrap">
          <a href="<?php echo url('/listings?city=' . urlencode($company['city'])); ?>" class="similar-viewall-btn">
            Browse more businesses in <?php echo htmlspecialchars($company['city'], ENT_QUOTES, 'UTF-8'); ?> â†’
          </a>
        </div>
      </div>
      <?php endif; ?>

      <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
      <!-- SECTION: BROWSE BY STATES (EMIRATES)                    -->
      <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
      <?php if (!empty($allStates)): ?>
      <div class="card-ui company-section-card">
        <h2 class="company-section-heading">
          <i class="fa fa-map text-success"></i> Browse by State
        </h2>
        <div class="company-states-row">
          <?php foreach ($allStates as $state): ?>
            <a href="<?php echo url('/listings?city=' . urlencode($state)); ?>" 
               class="btn btn-sm company-state-btn <?php echo ($company['city'] === $state) ? 'btn-success' : 'btn-outline-secondary'; ?>">
              <?php echo htmlspecialchars($state, ENT_QUOTES, 'UTF-8'); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
      <!-- SECTION: BROWSE BY ALPHABETS                            -->
      <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
      <div class="card-ui company-section-card">
        <h2 class="company-section-heading">
          <i class="fa fa-font text-info"></i> Browse by Alphabets
        </h2>
        <div class="company-alpha-row">
          <?php foreach ($alphabets as $letter): ?>
            <a href="<?php echo url('/listings?company_name_starts_with=' . urlencode($letter)); ?>" 
               class="btn btn-sm btn-outline-info company-alpha-btn">
              <?php echo htmlspecialchars($letter, ENT_QUOTES, 'UTF-8'); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </main>

  <!-- Bootstrap 5.3.8 - Local version -->
  <script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/final.js"></script>
  <script>
    async function toggleFavorite(companyId) {
      const favoriteBtn = document.getElementById('favorite-btn');
      const iconEl = document.getElementById('favorite-icon');
      const textEl = document.getElementById('favorite-text');

      if (!favoriteBtn || !iconEl || !textEl) {
        return;
      }

      const csrfToken = favoriteBtn.getAttribute('data-csrf-token') || '';
      const body = new URLSearchParams({
        action: 'toggle_favorite',
        company_id: String(companyId),
        csrf_token: csrfToken
      });

      favoriteBtn.disabled = true;

      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: body.toString()
        });

        const payload = await response.json();

        if (!response.ok || !payload.success) {
          if (response.status === 401 && payload.login_url) {
            window.location.href = payload.login_url;
            return;
          }
          alert(payload.message || 'Could not update saved status. Please try again.');
          return;
        }

        const saved = !!payload.saved;
        iconEl.textContent = saved ? 'â™¥' : 'â™¡';
        textEl.textContent = saved ? 'Saved' : 'Save';
        favoriteBtn.classList.toggle('btn-primary-ui', saved);
        favoriteBtn.classList.toggle('btn-light-ui', !saved);
      } catch (error) {
        alert('Could not update saved status. Please check your connection and try again.');
      } finally {
        favoriteBtn.disabled = false;
      }
    }
  </script>
  


<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

