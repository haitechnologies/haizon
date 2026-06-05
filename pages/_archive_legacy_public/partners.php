<?php
/**
 * Page: Company Sources/Partners Directory
 * Route: /partners
 * Description: Display all data sources and partners (15 sources)
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

// ============================================
// LOAD DATA
// ============================================
$CompanySourcesModel = new CompanySources($conn);

// Get all company sources
$sources = $CompanySourcesModel->getAll([
    'limit' => 100,
    'published_only' => true
]);

$sourceCounts = [];
if (!empty($sources)) {
  // `hai_companies` may not include `source_id` in every environment.
  // Use source-level totals to keep the page schema-safe.
  foreach ($sources as $source) {
    $sourceCounts[(int)($source['id'] ?? 0)] = (int)($source['total_companies'] ?? 0);
  }
}

// ============================================
// PAGE METADATA
// ============================================
$pageTitle = 'Company Sources & Partners - HAIPULSE Business Directory';
$pageDescription = 'Explore the diverse sources of company data in HAIPULSE Business Directory. View information from government organizations, chambers of commerce, industry associations, and other verified partners.';
$pageKeywords = 'UAE business sources, data partners, chambers of commerce, business associations, government databases';

include __DIR__ . '/../includes/layout/header.php';
?>

<style>
  /* Partners Page Styles */
  .partners-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 20px;
    text-align: center;
    margin-bottom: 40px;
  }

  .partners-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 12px;
    font-weight: 700;
  }

  .partners-hero p {
    font-size: 1.1rem;
    opacity: 0.95;
    max-width: 700px;
    margin: 0 auto 24px;
  }

  .partners-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
  }

  .partners-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
  }

  .partner-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 24px;
    text-align: center;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
  }

  .partner-card:hover {
    border-color: #667eea;
    box-shadow: 0 12px 24px rgba(102, 126, 234, 0.15);
    transform: translateY(-4px);
  }

  .partner-icon {
    font-size: 3rem;
    margin-bottom: 16px;
  }

  .partner-name {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
  }

  .partner-description {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 16px;
    line-height: 1.5;
    flex: 1;
  }

  .partner-count {
    background: #f0f0f0;
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 12px;
    font-weight: 600;
    color: #667eea;
  }

  .partner-link {
    display: inline-block;
    padding: 8px 16px;
    background: #667eea;
    color: white;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.2s;
  }

  .partner-link:hover {
    background: #5568d3;
    text-decoration: none;
  }

  .partner-badge {
    display: inline-block;
    background: #e8f5e9;
    color: #2e7d32;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 8px;
  }

  .partners-info {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 40px 20px;
    margin: 60px 0;
    text-align: center;
  }

  .partners-info h3 {
    font-size: 1.5rem;
    margin-bottom: 16px;
    color: #333;
  }

  .partners-info p {
    color: #666;
    max-width: 600px;
    margin: 0 auto 20px;
    line-height: 1.6;
  }

  .trust-indicators {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 40px;
  }

  .trust-indicator {
    padding: 20px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
  }

  .trust-indicator-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 8px;
  }

  .trust-indicator-label {
    font-size: 0.9rem;
    color: #666;
  }

  .partners-empty {
    text-align: center;
    padding: 60px 20px;
    color: #999;
  }

  .partners-cta {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    margin-top: 40px;
    margin-bottom: 40px;
  }

  .partners-cta h3 {
    font-size: 1.5rem;
    margin-bottom: 12px;
  }

  .partners-cta p {
    opacity: 0.95;
    margin-bottom: 20px;
  }

  .partners-cta-link {
    display: inline-block;
    background: white;
    color: #667eea;
    padding: 10px 24px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
  }

  @media (max-width: 768px) {
    .partners-hero h1 {
      font-size: 1.8rem;
    }

    .partners-grid {
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 16px;
    }

    .trust-indicators {
      grid-template-columns: 1fr;
    }
  }
</style>

<main class="site-main">
  <!-- Hero Section -->
  <div class="partners-hero">
    <h1>Our Partners & Sources</h1>
    <p>HAIPULSE is built on verified data from trusted government organizations, chambers of commerce, industry associations, and business partners across the UAE.</p>
  </div>

  <!-- Partners Grid -->
  <div class="partners-container">
    <?php if (!empty($sources)): ?>
      <div class="partners-grid">
        <?php foreach ($sources as $source): ?>
          <?php
          $sourceDisplayName = trim((string)($source['source'] ?? $source['source_name'] ?? $source['name'] ?? 'Partner'));
          $sourceSlug = trim((string)($source['sitemap_slug'] ?? ''));
          if ($sourceSlug === '') {
            $sourceSlug = preg_replace('/[^a-z0-9-]+/', '-', strtolower($sourceDisplayName));
            $sourceSlug = trim($sourceSlug, '-');
          }
          ?>
          <a href="<?php echo htmlspecialchars(url('/partner/' . rawurlencode($sourceSlug)), ENT_QUOTES, 'UTF-8'); ?>" class="partner-card">
            <div class="partner-icon">
              <?php 
              // Map source types to icons
              $icons = [
                'government' => 'ðŸ›ï¸',
                'chamber' => 'ðŸ¢',
                'association' => 'ðŸ¤',
                'corporate' => 'ðŸ’¼',
                'api' => 'ðŸ”Œ',
                'default' => 'ðŸŒ'
              ];
              $sourceType = strtolower($source['source_type'] ?? 'default');
              echo $icons[$sourceType] ?? $icons['default'];
              ?>
            </div>
            <div class="partner-name"><?php echo htmlspecialchars($sourceDisplayName, ENT_QUOTES); ?></div>
            <?php if (!empty($source['description'])): ?>
              <div class="partner-description">
                <?php echo htmlspecialchars(substr($source['description'], 0, 100), ENT_QUOTES); ?>
              </div>
            <?php endif; ?>
            <div class="partner-count">
              <?php 
              $count = (int)($sourceCounts[(int)$source['id']] ?? 0);
              echo $count . ' ' . ($count == 1 ? 'Company' : 'Companies');
              ?>
            </div>
            <span class="partner-link">View Details â†’</span>
          </a>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <div class="partners-empty">
        <h3>No Partners Found</h3>
        <p>Please try again later.</p>
      </div>
    <?php endif; ?>

    <!-- Trust & Verification Info -->
    <div class="partners-info">
      <h3>Verified Data from Trusted Sources</h3>
      <p>Our company directory is maintained with data from multiple verified sources, ensuring accuracy and comprehensive coverage of businesses across the UAE.</p>
      
      <div class="trust-indicators">
        <div class="trust-indicator">
          <div class="trust-indicator-value"><?php echo number_format(746832); ?></div>
          <div class="trust-indicator-label">Active Companies</div>
        </div>
        <div class="trust-indicator">
          <div class="trust-indicator-value"><?php echo count($sources); ?></div>
          <div class="trust-indicator-label">Data Partners</div>
        </div>
        <div class="trust-indicator">
          <div class="trust-indicator-value">âœ“</div>
          <div class="trust-indicator-label">Verified & Updated</div>
        </div>
      </div>
    </div>

    <!-- Become a Partner Section -->
    <div class="partners-cta">
      <h3>Interested in Becoming a Data Partner?</h3>
      <p>If you represent a business organization or possess valuable business data, we'd like to partner with you.</p>
      <a href="<?php echo htmlspecialchars(url('/contact') . '?ref=partner-inquiry', ENT_QUOTES, 'UTF-8'); ?>" class="partners-cta-link">Contact Us</a>
    </div>

  </div>

</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

