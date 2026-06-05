<?php
/**
 * Page: HR Suite
 * Route: /hr
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/globals.php';
require_once __DIR__ . '/../../includes/helpers.php';

$saasCatalog = require __DIR__ . '/../../config/saas_catalog.php';
$plans       = $saasCatalog['plans'] ?? [];

$pageTitle       = 'HR Software for UAE Teams — HAIPULSE';
$pageDescription = 'Manage attendance, leave, payroll, and employee records for your UAE team from one connected HR workspace.';
$pageKeywords    = 'HR software UAE, payroll software UAE, attendance management UAE, employee records system UAE';
$bodyClass       = 'page-saas-hr';
$canonicalUrl    = getFullUrl('/hr');

$breadcrumbs = [
    ['name' => 'Home', 'url' => getFullUrl('/')],
    ['name' => 'HR', 'url' => $canonicalUrl],
];

$jsonLdSchema  = '<script type="application/ld+json">' . json_encode([
    '@context' => 'https://schema.org', '@type' => 'WebPage',
    'name' => $pageTitle, 'description' => $pageDescription, 'url' => $canonicalUrl, 'inLanguage' => 'en-AE',
], JSON_UNESCAPED_SLASHES) . '</script>';
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);

include __DIR__ . '/../../includes/saas/header.php';

$features = [
    ['icon' => 'ph-buildings', 'title' => 'Departments & Designations',
     'desc' => 'Organise your workforce into departments and designations for clear reporting lines and access management.'],
    ['icon' => 'ph-clock', 'title' => 'Attendance Tracking',
     'desc' => 'Record daily check-in/check-out, track punctuality, and run attendance reports by employee or team.'],
    ['icon' => 'ph-calendar-check', 'title' => 'Leave Management',
     'desc' => 'Configure leave types, manage applications and approvals, and track balances across the full calendar.'],
    ['icon' => 'ph-money', 'title' => 'Payroll Runs',
     'desc' => 'Run monthly payroll, apply allowances and deductions, and generate payslips directly from the platform.'],
    ['icon' => 'ph-file-text', 'title' => 'Payslips',
     'desc' => 'Generate printable and downloadable payslips for each pay run with a full salary breakdown.'],
    ['icon' => 'ph-folder-user', 'title' => 'User Documents',
     'desc' => 'Store and manage employee documents, contracts, and IDs with expiry tracking and document categories.'],
];
?>

<main>
  <!-- HERO -->
  <section class="saas-product-hero">
    <div class="saas-container">
      <nav aria-label="Breadcrumb">
        <ol class="saas-breadcrumb" role="list">
          <li><a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>">Home</a></li>
          <li>HR</li>
        </ol>
      </nav>
      <span class="saas-eyebrow">HR Suite</span>
      <h1>Manage your workforce from attendance through payroll&nbsp;in one place.</h1>
      <p class="saas-hero__lead">
        HAIPULSE HR covers the full employee lifecycle — departments, attendance, leave, payroll, and
        documents — all within the same admin workspace as your other operations.
      </p>
      <div class="saas-hero__actions">
        <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-primary saas-btn-lg">Get started</a>
        <a href="<?php echo htmlspecialchars(url('/contact?subject=hr-demo&source=hr-hero'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-outline saas-btn-lg">Book a demo</a>
      </div>
    </div>
  </section>

  <!-- FEATURES -->
  <section class="saas-section saas-section--alt">
    <div class="saas-container">
      <div class="saas-section-head text-center">
        <span class="saas-eyebrow">What's included</span>
        <h2>HR module coverage</h2>
        <p class="saas-muted">Every HR module ships live inside HAIPULSE — no separate HR software required.</p>
      </div>
      <div class="saas-features-grid">
        <?php foreach ($features as $f): ?>
          <article class="saas-feature-card" style="--saas-accent:#16a34a; --saas-accent-light:#dcfce7;">
            <div class="saas-feature-icon">
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
        <h2>For UAE companies formalising their HR operations</h2>
      </div>
      <div class="row g-4">
        <?php
        $audiences = [
            ['title' => 'HR managers', 'desc' => 'Run the full HR cycle — onboarding, attendance, leave approvals, payroll — without switching systems.'],
            ['title' => 'Finance + HR combined', 'desc' => 'Connect payroll directly to your accounting module so salary runs update your ledger automatically.'],
            ['title' => 'Growing teams', 'desc' => 'Add users, create departments, and manage access as your headcount grows — all from admin settings.'],
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
      <h2>Ready to bring HR into your operations platform?</h2>
      <p>Contact the team to discuss your headcount, payroll structure, and rollout plan.</p>
      <div class="saas-hero__actions">
        <a href="<?php echo htmlspecialchars(url('/contact?subject=hr&source=hr-cta'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-primary saas-btn-lg">Contact sales</a>
        <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-outline saas-btn-lg">See pricing</a>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../../includes/saas/footer.php'; ?>
