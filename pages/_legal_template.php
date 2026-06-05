<?php
/**
 * Legal Pages Template
 * 
 * Used by: terms-of-use.php, privacy-policy.php, cookies-policy.php, 
 *          gdpr.php, ccpa.php, refund-policy.php, accessibility.php,
 *          security.php, uae-pdpl.php
 * 
 * Expected variables:
 * - $pageTitle: Page title for <title> tag
 * - $pageHeading: Main heading displayed on the page
 * - $pageSummary: Subtitle/summary displayed under heading
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

// Default values if not set
$pageTitle = $pageTitle ?? 'Legal - UAE Business Directory';
$pageHeading = $pageHeading ?? 'Legal Information';
$pageSummary = $pageSummary ?? 'Terms, policies, and other legal information';
$pageDescription = $pageDescription ?? $pageSummary;

// Set correct baseHref for new design
$basePath = $GLOBALS['basePath'] ?? '';
$baseHref = $basePath === '' ? '/' : $basePath . '/';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main id="main-content" class="section">
    <div class="container-narrow">
      <article class="card-ui legal-card">
        <!-- Page Header -->
        <div class="legal-head">
          <h1 class="legal-title">
            <?php echo htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8'); ?>
          </h1>
          <p class="muted legal-summary">
            <?php echo htmlspecialchars($pageSummary, ENT_QUOTES, 'UTF-8'); ?>
          </p>
        </div>

        <!-- Legal Content Area -->
        <div class="legal-content legal-body">
          <!-- Content will be displayed here -->
          
          <?php
          // Display content based on $pageContent variable set by each legal page
          if (isset($pageContent) && !empty($pageContent)) {
              $contentFile = __DIR__ . '/_legal_content/' . $pageContent . '.php';
              if (file_exists($contentFile)) {
                  include $contentFile;
              } else {
                  // Display placeholder if no content file
                  echo '<p class="legal-placeholder">';
                  echo 'Content file not found.';
                  echo '</p>';
              }
          } else {
              // Display placeholder if pageContent not set
              echo '<p class="legal-placeholder">';
              echo htmlspecialchars($pageSummary, ENT_QUOTES, 'UTF-8');
              echo '</p>';
          }
          ?>
        </div>

        <!-- Last Updated -->
        <div class="legal-updated">
          <p class="muted">
            Last updated: <?php echo date('d M Y'); ?>
          </p>
        </div>
      </article>

      <!-- Quick Links to Other Legal Pages -->
      <div class="legal-links-wrap">
        <h3 class="legal-links-title">Other Legal Information</h3>
        <div class="legal-links-grid">
          <a href="<?php echo htmlspecialchars(url('/terms-of-use'), ENT_QUOTES, 'UTF-8'); ?>" class="legal-link">
            → Terms of Service
          </a>
          <a href="<?php echo htmlspecialchars(url('/privacy-policy'), ENT_QUOTES, 'UTF-8'); ?>" class="legal-link">
            → Privacy Policy
          </a>
          <a href="<?php echo htmlspecialchars(url('/cookies-policy'), ENT_QUOTES, 'UTF-8'); ?>" class="legal-link">
            → Cookies Policy
          </a>
          <a href="<?php echo htmlspecialchars(url('/refund-policy'), ENT_QUOTES, 'UTF-8'); ?>" class="legal-link">
            → Refund Policy
          </a>
          <a href="<?php echo htmlspecialchars(url('/accessibility'), ENT_QUOTES, 'UTF-8'); ?>" class="legal-link">
            → Accessibility
          </a>
          <a href="<?php echo htmlspecialchars(url('/security'), ENT_QUOTES, 'UTF-8'); ?>" class="legal-link">
            → Security
          </a>
          <a href="<?php echo htmlspecialchars(url('/gdpr'), ENT_QUOTES, 'UTF-8'); ?>" class="legal-link">
            → GDPR
          </a>
          <a href="<?php echo htmlspecialchars(url('/ccpa'), ENT_QUOTES, 'UTF-8'); ?>" class="legal-link">
            → CCPA
          </a>
          <a href="<?php echo htmlspecialchars(url('/uae-pdpl'), ENT_QUOTES, 'UTF-8'); ?>" class="legal-link">
            → UAE PDPL
          </a>
        </div>
      </div>
    </div>
  </main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
