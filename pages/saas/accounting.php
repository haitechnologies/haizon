<?php
/**
 * Page: Accounting Suite
 * Route: /accounting
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/globals.php';
require_once __DIR__ . '/../../includes/helpers.php';

$saasCatalog = require __DIR__ . '/../../config/saas_catalog.php';
$plans       = $saasCatalog['plans'] ?? [];

$pageTitle       = 'Accounting Software for UAE Teams — HAIPULSE';
$pageDescription = 'Run quotations, invoices, expenses, payments, journals, and financial reports for your UAE business from one accounting workspace.';
$pageKeywords    = 'accounting software UAE, invoice software UAE, expense tracking UAE, financial reporting UAE, bookkeeping software UAE';
$bodyClass       = 'page-saas-accounting';
$canonicalUrl    = getFullUrl('/accounting');

$breadcrumbs = [
    ['name' => 'Home', 'url' => getFullUrl('/')],
    ['name' => 'Accounting', 'url' => $canonicalUrl],
];

$jsonLdSchema  = '<script type="application/ld+json">' . json_encode([
    '@context' => 'https://schema.org', '@type' => 'WebPage',
    'name' => $pageTitle, 'description' => $pageDescription, 'url' => $canonicalUrl, 'inLanguage' => 'en-AE',
], JSON_UNESCAPED_SLASHES) . '</script>';
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);

include __DIR__ . '/../../includes/saas/header.php';

$features = [
    ['icon' => 'ph-file-doc', 'title' => 'Quotations',
     'desc' => 'Build and send professional quotations with line items, taxes, and terms. Convert directly to invoices on approval.'],
    ['icon' => 'ph-receipt', 'title' => 'Invoices',
     'desc' => 'Issue sales invoices with due dates, payment terms, and PDF downloads. Track paid, unpaid, and overdue status.'],
    ['icon' => 'ph-shopping-cart', 'title' => 'Purchase & Expenses',
     'desc' => 'Record supplier purchases, capture expenses by category, and maintain a full expenditure trail.'],
    ['icon' => 'ph-bank', 'title' => 'Payments',
     'desc' => 'Log payments received and made. Match transactions to invoices and track outstanding balances.'],
    ['icon' => 'ph-book-open', 'title' => 'Journals & Ledger',
     'desc' => 'Double-entry journal entries, account groupings, and a full general ledger for audit-ready records.'],
    ['icon' => 'ph-chart-line-up', 'title' => 'Financial Reports',
     'desc' => 'P&L, balance sheet, cash flow, and custom reporting views with date filtering and period comparisons.'],
];
?>

<main>
  <!-- HERO -->
  <section class="saas-product-hero" style="background: linear-gradient(135deg, #1a2e4a 0%, #2d4a1e 100%);">
    <div class="saas-container">
      <nav aria-label="Breadcrumb">
        <ol class="saas-breadcrumb" role="list">
          <li><a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>">Home</a></li>
          <li>Accounting</li>
        </ol>
      </nav>
      <span class="saas-eyebrow">Accounting Suite</span>
      <h1>Full-cycle accounting for UAE businesses — quotations through reports.</h1>
      <p class="saas-hero__lead">
        HAIPULSE Accounting covers everything from quotation to journal entry. Run invoices, expenses,
        payments, and financial reporting without leaving your operations platform.
      </p>
      <div class="saas-hero__actions">
        <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-primary saas-btn-lg">Get started</a>
        <a href="<?php echo htmlspecialchars(url('/contact?subject=accounting-demo&source=accounting-hero'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-outline saas-btn-lg">Book a demo</a>
      </div>
    </div>
  </section>

  <!-- FEATURES -->
  <section class="saas-section saas-section--alt">
    <div class="saas-container">
      <div class="saas-section-head text-center">
        <span class="saas-eyebrow">What's included</span>
        <h2>Accounting module coverage</h2>
        <p class="saas-muted">All accounting features run live inside HAIPULSE — no separate software or integrations required.</p>
      </div>
      <div class="saas-features-grid">
        <?php foreach ($features as $f): ?>
          <article class="saas-feature-card" style="--saas-accent:#d97706; --saas-accent-light:#fef3c7;">
            <div class="saas-feature-icon" style="background:#fef3c7; color:#d97706;">
              <i class="ph <?php echo htmlspecialchars($f['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
            </div>
            <h3><?php echo htmlspecialchars($f['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p><?php echo htmlspecialchars($f['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- WHO IT IS FOR -->
  <section class="saas-section">
    <div class="saas-container-narrow">
      <div class="saas-section-head text-center">
        <span class="saas-eyebrow">Who it's for</span>
        <h2>For finance teams and business owners managing UAE operations</h2>
      </div>
      <div class="row g-4">
        <?php
        $audiences = [
            ['title' => 'Finance teams', 'desc' => 'Manage the full AP/AR cycle, journal entries, and monthly close from one system.'],
            ['title' => 'Business owners', 'desc' => 'See P&L, outstanding receivables, and expense trends without waiting for end-of-month reports.'],
            ['title' => 'Ops + Finance combined', 'desc' => 'Tie sales orders and job completions directly to invoicing so revenue recognition is automatic.'],
        ];
        foreach ($audiences as $a): ?>
          <div class="col-md-4">
            <div class="saas-card h-100">
              <h3 style="font-size:1rem; margin-bottom:8px;"><?php echo htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p class="saas-muted mb-0" style="font-size:0.9rem;"><?php echo htmlspecialchars($a['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="saas-cta-section">
    <div class="saas-container">
      <h2>Ready to run your finance in HAIPULSE?</h2>
      <p>Contact the team to discuss your invoice volume, reporting needs, and rollout plan.</p>
      <div class="saas-hero__actions">
        <a href="<?php echo htmlspecialchars(url('/contact?subject=accounting&source=accounting-cta'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-primary saas-btn-lg">Contact sales</a>
        <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-outline saas-btn-lg">See pricing</a>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../../includes/saas/footer.php'; ?>
