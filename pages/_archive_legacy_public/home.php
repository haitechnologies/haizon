<?php
/**
 * Homepage (New Design)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/Companies.php';
require_once __DIR__ . '/../classes/frontend/CompanyCategories.php';
require_once __DIR__ . '/../classes/frontend/Blogs.php';
require_once __DIR__ . '/../classes/frontend/PublicAds.php';
require_once __DIR__ . '/../includes/helpers.php';

$basePath = $GLOBALS['basePath'] ?? '';

$companiesModel = new Companies($conn);
$categoriesModel = new CompanyCategories($conn);
$blogsModel = new Blogs($conn);
$publicAdsModel = new PublicAds($conn);

$homeHeroAds = $publicAdsModel->getAdsForSlot('home_hero', [
  'page_type' => 'home',
  'tags' => ['software', 'crm', 'growth', 'automation']
], 1);

$homeFooterAds = $publicAdsModel->getAdsForSlot('global_footer', [
  'page_type' => 'home',
  'tags' => ['software', 'finance', 'accounting']
], 1);

// Feature toggle: keep partners section ready but hidden for now.
$showHomePartnersSection = false;

$cacheTtl = 90; // seconds
$cacheKey = 'homepage_v2';
$cacheFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'haipulse_' . $cacheKey . '.json';

$homeData = null;
if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
  $cachedJson = @file_get_contents($cacheFile);
  if ($cachedJson !== false) {
    $decoded = json_decode($cachedJson, true);
    if (is_array($decoded)) {
      $homeData = $decoded;
    }
  }
}

if (!is_array($homeData)) {
  $homeData = [
    'popularCategories' => [],
    'topCategories' => [],
    'featuredCompanies' => [],
    'popularBlogs' => [],
    'totalCompanies' => 0,
    'totalHsCodes' => 0,
    'totalCategories' => 0,
    'totalVerifiedBusinesses' => 0,
  ];

  $homeData['popularCategories'] = $categoriesModel->getPopular(15);
  $homeData['topCategories'] = array_slice($homeData['popularCategories'], 0, 8);
  $homeData['featuredCompanies'] = $companiesModel->getFeaturedSummary(6);
  $homeData['popularBlogs'] = $blogsModel->getLatestRandomSummary(3, 18);

  try {
    // Use one aggregated query to reduce DB round-trips for counters.
    $metricsSql = "SELECT
      (SELECT COUNT(*) FROM `" . DB::COMPANIES . "` WHERE publish = 1) AS total_companies,
      (SELECT COUNT(*) FROM `" . DB::HS_CODES . "`) AS total_hs_codes,
      (SELECT COUNT(*) FROM `" . DB::CATEGORIES . "` WHERE publish = 1) AS total_categories,
      (SELECT COUNT(*) FROM `" . DB::COMPANIES . "` WHERE publish = 1 AND verified = 1) AS total_verified_businesses";
    $metricsResult = $conn->query($metricsSql);
    if ($metricsResult) {
      $row = $metricsResult->fetch_assoc();
      $homeData['totalCompanies'] = (int)($row['total_companies'] ?? 0);
      $homeData['totalHsCodes'] = (int)($row['total_hs_codes'] ?? 0);
      $homeData['totalCategories'] = (int)($row['total_categories'] ?? 0);
      $homeData['totalVerifiedBusinesses'] = (int)($row['total_verified_businesses'] ?? 0);
    }
  } catch (Throwable $e) {
    error_log("Home: Error loading aggregated metrics - " . $e->getMessage());
  }

  @file_put_contents($cacheFile, json_encode($homeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

$popularCategories = $homeData['popularCategories'];
$topCategories = $homeData['topCategories'];
$featuredCompanies = $homeData['featuredCompanies'];
$popularBlogs = $homeData['popularBlogs'];
$totalCompanies = (int)($homeData['totalCompanies'] ?? 0);
$totalHsCodes = (int)($homeData['totalHsCodes'] ?? ($homeData['totalActiveListings'] ?? 0));
$totalCategories = (int)($homeData['totalCategories'] ?? 0);
$totalVerifiedBusinesses = (int)($homeData['totalVerifiedBusinesses'] ?? 0);

$pageTitle = 'UAE Business Directory | Find Trusted Businesses in UAE';
$pageDescription = 'Discover and connect with verified UAE businesses.';
$bodyClass = 'page-home';

$homeHeroPopularTags = [];
if (!empty($popularCategories)) {
  foreach ($popularCategories as $cat) {
    $catName = (string)($cat['name'] ?? '');
    $catSlug = (string)($cat['slug'] ?? '');
    if ($catName === '' || $catSlug === '') {
      continue;
    }
    $homeHeroPopularTags[] = [
      'label' => $catName,
      'url' => url('/category/' . $catSlug),
    ];
  }
}

if (empty($homeHeroPopularTags)) {
  $fallbackTags = [
    'Construction & Contracting' => 'construction-contracting',
    'Healthcare & Medical' => 'healthcare-medical',
    'Retail & Shopping' => 'retail-shopping',
    'Automotive & Transportation' => 'automotive-transportation',
    'Information Technology' => 'information-technology',
    'Security & Safety' => 'security-safety',
    'Wholesale & Distribution' => 'wholesale-distribution',
    'Real Estate & Property' => 'real-estate-property',
    'Food & Beverage' => 'food-beverage',
    'Manufacturing & Industrial' => 'manufacturing-industrial',
  ];
  foreach ($fallbackTags as $label => $slug) {
    $homeHeroPopularTags[] = [
      'label' => $label,
      'url' => url('/category/' . $slug),
    ];
  }
}

// Generate JSON-LD structured data for rich results
$jsonLdSchema = generateWebSiteSchema(
    'UAE Business Directory',
    getFullUrl('/')
);

// Add Organization schema
$organizationSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'UAE Business Directory',
    'url' => getFullUrl('/'),
    'description' => 'UAE\'s comprehensive business directory connecting customers with verified local businesses and services',
    'address' => [
        '@type' => 'PostalAddress',
        'addressCountry' => 'AE',
        'addressRegion' => 'UAE'
    ]
];
$jsonLdSchema .= "\n<script type=\"application/ld+json\">" . json_encode($organizationSchema, JSON_UNESCAPED_SLASHES) . "</script>";
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main id="main-content">
    <?php
      $searchHeroTitle = 'Find trusted UAE businesses in seconds';
      $searchHeroDescription = 'Search from ' . number_format($totalCompanies) . '+ verified businesses by service and location.';
      $searchHeroFormAction = url('/listings');
      $searchHeroFormId = 'hero-search-form';
      $searchHeroSubmitLabel = 'Search';
      $searchHeroVariant = 'home';
      $searchHeroPopularTags = $homeHeroPopularTags;
      include __DIR__ . '/../includes/partials/public-search-hero.php';
    ?>

    <section class="section home-mobile-quicklinks" aria-label="Quick mobile shortcuts">
      <div class="container-narrow">
        <div class="home-mobile-quicklinks__grid">
          <a class="home-mobile-quicklinks__item" href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>">Browse listings</a>
          <a class="home-mobile-quicklinks__item" href="<?php echo htmlspecialchars(url('/categories'), ENT_QUOTES, 'UTF-8'); ?>">Top categories</a>
          <a class="home-mobile-quicklinks__item" href="#home-featured-businesses">Featured picks</a>
          <a class="home-mobile-quicklinks__item home-mobile-quicklinks__item--accent" href="<?php echo htmlspecialchars(url('/add-business'), ENT_QUOTES, 'UTF-8'); ?>">Add business</a>
        </div>
      </div>
    </section>

    <section class="section home-ad-section">
      <div class="container-narrow">
        <?php
          $publicAds = $homeHeroAds;
          $publicAdSlot = 'wide';
          $publicAdHeading = 'Software products for growing UAE businesses';
          include __DIR__ . '/../includes/partials/public-ad-slot.php';
        ?>
      </div>
    </section>

    <!-- Counter Cards / KPI Section -->
    <section class="section home-kpi-section">
      <div class="container-narrow">
        <div class="kpi-grid kpi-grid--4">
          <a class="kpi" href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="kpi-value"><?php echo number_format($totalCompanies); ?></div>
            <div class="kpi-label">Total Companies</div>
          </a>
          <a class="kpi" href="<?php echo htmlspecialchars(url('/trade/hs-codes'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="kpi-value"><?php echo number_format($totalHsCodes); ?></div>
            <div class="kpi-label">Total UAE HS Codes</div>
          </a>
          <a class="kpi" href="<?php echo htmlspecialchars(url('/categories'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="kpi-value"><?php echo number_format($totalCategories); ?></div>
            <div class="kpi-label">Categories</div>
          </a>
          <a class="kpi" href="<?php echo htmlspecialchars(url('/listings') . '?verified=1', ENT_QUOTES, 'UTF-8'); ?>">
            <div class="kpi-value"><?php echo number_format($totalVerifiedBusinesses); ?></div>
            <div class="kpi-label">Verified Businesses</div>
          </a>
        </div>
      </div>
    </section>

    <!-- Browse Alphabetically Section -->
    <section class="section home-alpha-section">
      <div class="container-narrow">
        <div class="section-head">
          <h2>Browse alphabetically</h2>
        </div>
        <div class="alphabet-cards-grid">
          <!-- Browse Companies by Letter Card -->
          <div class="alphabet-card">
            <h3 class="alphabet-card-title">
              <a href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>">Companies</a>
            </h3>
            <div class="alphabet-grid">
              <?php
              $alphabet = range('A', 'Z');
              foreach ($alphabet as $letter):
                $searchUrl = htmlspecialchars(url('/listings') . '?company_name_starts_with=' . $letter, ENT_QUOTES, 'UTF-8');
              ?>
                <a href="<?php echo $searchUrl; ?>" class="alphabet-link"><?php echo $letter; ?></a>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Browse Categories by Letter Card -->
          <div class="alphabet-card">
            <h3 class="alphabet-card-title">
              <a href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>">Categories</a>
            </h3>
            <div class="alphabet-grid">
              <?php
              foreach ($alphabet as $letter):
                $searchUrl = htmlspecialchars(url('/listings') . '?category_starts_with=' . $letter, ENT_QUOTES, 'UTF-8');
              ?>
                <a href="<?php echo $searchUrl; ?>" class="alphabet-link"><?php echo $letter; ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section" id="home-top-categories">
      <div class="container-narrow">
        <div class="section-head">
          <h2>Top categories</h2>
          <a class="muted" href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>">View all</a>
        </div>
        <div class="grid-3">
          <?php foreach ($topCategories as $category): ?>
            <?php
              $categoryName = $category['name'] ?? ($category['category'] ?? 'Category');
              $categoryCount = (int)($category['total_companies'] ?? ($category['company_count'] ?? 0));
              $categoryDescription = $category['description'] ?? 'Explore businesses in this category.';
              $categorySlug = $category['slug'] ?? '';
            ?>
            <article class="card-ui category-card">
              <span class="pill"><?php echo number_format($categoryCount); ?> listings</span>
              <h3><?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?></h3>
              <p class="muted"><?php echo htmlspecialchars($categoryDescription, ENT_QUOTES, 'UTF-8'); ?></p>
              <a href="<?php echo htmlspecialchars(url('/category/' . $categorySlug), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui home-btn-top">Browse</a>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Browse by States Section -->
    <section class="section">
      <div class="container-narrow">
        <div class="section-head">
          <h2>Browse by states</h2>
          <a class="muted" href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>">View all</a>
        </div>
        <div class="states-grid">
          <?php
          $uaeStates = [
            'Abu Dhabi' => 'abu-dhabi',
            'Ajman' => 'ajman',
            'Dubai' => 'dubai',
            'Fujairah' => 'fujairah',
            'Ras Al Khaimah' => 'ras-al-khaimah',
            'Sharjah' => 'sharjah',
            'Umm Al Quwain' => 'umm-al-quwain'
          ];
          foreach ($uaeStates as $stateName => $stateSlug):
            $searchUrl = htmlspecialchars(url('/listings') . '?city=' . urlencode($stateName), ENT_QUOTES, 'UTF-8');
          ?>
            <a href="<?php echo $searchUrl; ?>" class="state-card">
              <div class="state-card-content">
                <h3><?php echo $stateName; ?></h3>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section" id="home-featured-businesses">
      <div class="container-narrow">
        <div class="section-head">
          <h2>Featured businesses</h2>
          <a class="muted" href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>">Browse all</a>
        </div>
        <div class="grid-3">
          <?php foreach ($featuredCompanies as $company): ?>
            <?php include __DIR__ . '/../includes/partials/home-featured-company-card.php'; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Claim Business Page CTA Section -->
    <section class="section cta-business-section">
      <div class="container-narrow">
        <div class="cta-business-card">
          <div class="cta-business-content">
            <div class="cta-business-text">
              <h2>Claim your free HaiPulse Business Page</h2>
              <p class="home-cta-copy">Create your professional business profile in minutes â€” no credit card required. âœ“ Free profile â€¢ âœ“ Reach customers â€¢ âœ“ Analytics</p>
            </div>
            <a href="<?php echo htmlspecialchars(url('/add-business'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui">Get Started â†’</a>
          </div>
        </div>
      </div>
    </section>

    <!-- Popular Blog Posts -->
    <section class="section home-blog-section">
      <div class="container-narrow">
        <div class="section-head">
          <h2>Latest blog posts</h2>
          <a class="muted" href="<?php echo htmlspecialchars(url('/blog'), ENT_QUOTES, 'UTF-8'); ?>">Read more</a>
        </div>
        <div class="grid-3">
          <?php if (!empty($popularBlogs)): ?>
            <?php foreach ($popularBlogs as $blog): ?>
              <?php include __DIR__ . '/../includes/partials/home-blog-card.php'; ?>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="muted home-blog-empty">No blog posts available yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="section home-footer-ad-section">
      <div class="container-narrow">
        <?php
          $publicAds = $homeFooterAds;
          $publicAdSlot = 'wide';
          $publicAdHeading = 'Operations and finance software';
          include __DIR__ . '/../includes/partials/public-ad-slot.php';
        ?>
      </div>
    </section>

    <?php if ($showHomePartnersSection): ?>
    <!-- Partners Logo Carousel Section -->
    <section class="section home-partners-section">
      <div class="container-narrow">
        <div class="section-head home-partners-head">
          <h2>Our Partners</h2>
        </div>
        
        <div class="partners-carousel">
          <button class="partners-nav partners-nav--prev" aria-label="Previous partners" title="Previous">
            <span>â€¹</span>
          </button>
          
          <div class="partners-track">
            <div class="partners-slide">
              <img src="https://placehold.co/180x80/0066cc/ffffff?text=Partner+1" alt="Partner 1" class="partner-logo">
            </div>
            <div class="partners-slide">
              <img src="https://placehold.co/180x80/ff6b6b/ffffff?text=Partner+2" alt="Partner 2" class="partner-logo">
            </div>
            <div class="partners-slide">
              <img src="https://placehold.co/180x80/51cf66/ffffff?text=Partner+3" alt="Partner 3" class="partner-logo">
            </div>
            <div class="partners-slide">
              <img src="https://placehold.co/180x80/ffa500/ffffff?text=Partner+4" alt="Partner 4" class="partner-logo">
            </div>
            <div class="partners-slide">
              <img src="https://placehold.co/180x80/6c5ce7/ffffff?text=Partner+5" alt="Partner 5" class="partner-logo">
            </div>
            <div class="partners-slide">
              <img src="https://placehold.co/180x80/00b4d8/ffffff?text=Partner+6" alt="Partner 6" class="partner-logo">
            </div>
            <div class="partners-slide">
              <img src="https://placehold.co/180x80/ee5a6f/ffffff?text=Partner+7" alt="Partner 7" class="partner-logo">
            </div>
            <div class="partners-slide">
              <img src="https://placehold.co/180x80/2a9d8f/ffffff?text=Partner+8" alt="Partner 8" class="partner-logo">
            </div>
          </div>
          
          <button class="partners-nav partners-nav--next" aria-label="Next partners" title="Next">
            <span>â€º</span>
          </button>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- Trending Searches Widget - HIDDEN -->
    <!-- 
    <section class="section home-trending-hidden">
      <div class="container-narrow">
        <?php 
          $limit = 10;
          $show_stats = true;
          $title = 'Trending Now';
          include __DIR__ . '/../includes/partials/trending-searches.php'; 
        ?>
      </div>
    </section>
    -->
  </main>

  <?php if ($showHomePartnersSection): ?>
  <script>
    // Partners Carousel - Seamless professional auto slider
    jQuery(document).ready(function($) {
      const $track = $('.partners-track');
      const $carousel = $('.partners-carousel');
      const $prev = $('.partners-nav--prev');
      const $next = $('.partners-nav--next');

      if (!$track.length) {
        return;
      }

      const $slides = $track.children('.partners-slide');
      const slideCount = $slides.length;
      const visibleCount = Math.min(3, slideCount);
      let autoTimer = null;
      let animating = false;

      if (slideCount <= 1) {
        $prev.hide();
        $next.hide();
        return;
      }

      // Duplicate first few slides at the end for seamless looping.
      $slides.slice(0, visibleCount).clone(true).appendTo($track);

      function getStepWidth() {
        const $first = $track.children('.partners-slide').first();
        if (!$first.length) {
          return 200;
        }
        const gap = parseInt($track.css('gap'), 10) || 20;
        return $first.outerWidth(true) + gap;
      }

      function goNext() {
        if (animating) {
          return;
        }
        animating = true;

        const step = getStepWidth();
        const originalWidth = step * slideCount;
        const current = $track.scrollLeft();

        $track.stop(true).animate({ scrollLeft: current + step }, 450, 'swing', function() {
          // If we reached cloned area, jump to start without flicker.
          if ($track.scrollLeft() >= originalWidth) {
            $track.scrollLeft(0);
          }
          animating = false;
        });
      }

      function goPrev() {
        if (animating) {
          return;
        }
        animating = true;

        const step = getStepWidth();
        const originalWidth = step * slideCount;
        let current = $track.scrollLeft();

        if (current <= 0) {
          // Jump to equivalent end position for smooth backward loop.
          $track.scrollLeft(originalWidth);
          current = originalWidth;
        }

        $track.stop(true).animate({ scrollLeft: current - step }, 450, 'swing', function() {
          animating = false;
        });
      }

      function startAuto() {
        stopAuto();
        autoTimer = setInterval(goNext, 2800);
      }

      function stopAuto() {
        if (autoTimer) {
          clearInterval(autoTimer);
          autoTimer = null;
        }
      }

      $next.on('click', function() {
        goNext();
        startAuto();
      });

      $prev.on('click', function() {
        goPrev();
        startAuto();
      });

      // Pause autoplay when user hovers or focuses controls.
      $carousel.on('mouseenter focusin', stopAuto);
      $carousel.on('mouseleave focusout', startAuto);

      // Keep keyboard accessibility for controls.
      $carousel.on('keydown', function(e) {
        if (e.key === 'ArrowRight') {
          e.preventDefault();
          goNext();
          startAuto();
        }
        if (e.key === 'ArrowLeft') {
          e.preventDefault();
          goPrev();
          startAuto();
        }
      });

      startAuto();
    });
  </script>
  <?php endif; ?>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

