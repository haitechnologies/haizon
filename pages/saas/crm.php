<?php
/**
 * Page: CRM Suite
 * Route: /crm
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/globals.php';
require_once __DIR__ . '/../../includes/helpers.php';

$saasCatalog = require __DIR__ . '/../../config/saas_catalog.php';
$plans       = $saasCatalog['plans'] ?? [];

$pageTitle       = 'CRM Software for UAE Teams — HAIPULSE';
$pageDescription = 'Manage leads, customers, projects, and jobs from one CRM workspace. Built for UAE sales and service teams.';
$pageKeywords    = 'CRM UAE, customer management software UAE, leads pipeline UAE, project tracking software UAE';
$bodyClass       = 'page-saas-crm';
$canonicalUrl    = getFullUrl('/crm');

$breadcrumbs = [
    ['name' => 'Home', 'url' => getFullUrl('/')],
    ['name' => 'CRM', 'url' => $canonicalUrl],
];

$jsonLdSchema  = '<script type="application/ld+json">' . json_encode([
    '@context' => 'https://schema.org', '@type' => 'WebPage',
    'name' => $pageTitle, 'description' => $pageDescription, 'url' => $canonicalUrl, 'inLanguage' => 'en-AE',
], JSON_UNESCAPED_SLASHES) . '</script>';
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);

include __DIR__ . '/../../includes/saas/header.php';

$features = [
    ['icon' => 'ph-funnel', 'title' => 'Leads Pipeline',
     'desc' => 'Track every lead from first contact through qualification, proposal, and close. Filter by status, source, and assigned rep.'],
    ['icon' => 'ph-users', 'title' => 'Customer Records',
     'desc' => 'Centralised customer profiles with contact details, linked jobs, transaction history, and activity notes.'],
    ['icon' => 'ph-kanban', 'title' => 'Projects',
     'desc' => 'Organise client projects with task boards, due dates, team assignments, and progress tracking in one view.'],
    ['icon' => 'ph-wrench', 'title' => 'Jobs & Execution',
     'desc' => 'Create and track service jobs end-to-end — from scheduling and assignment through completion and sign-off.'],
    ['icon' => 'ph-envelope-simple', 'title' => 'Communication Logs',
     'desc' => 'Record calls, emails, and meetings against each lead or customer so every team member has full context.'],
    ['icon' => 'ph-chart-bar', 'title' => 'Sales Reports',
     'desc' => 'Pipeline value, conversion rates, revenue by period, and performance by rep — all in built-in reporting views.'],
];
?>

<main>
  <!-- HERO -->
  <section class="saas-product-hero">
    <div class="saas-container">
      <nav aria-label="Breadcrumb">
        <ol class="saas-breadcrumb" role="list">
          <li><a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>">Home</a></li>
          <li>CRM</li>
        </ol>
      </nav>
      <span class="saas-eyebrow">CRM Suite</span>
      <h1>Manage customers, leads, and delivery in one CRM workspace.</h1>
      <p class="saas-hero__lead">
        From first lead to completed job, HAIPULSE CRM keeps your sales pipeline, customer records,
        and operational execution connected in one admin environment.
      </p>
      <div class="saas-hero__actions">
        <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-primary saas-btn-lg">Get started</a>
        <a href="<?php echo htmlspecialchars(url('/contact?subject=crm-demo&source=crm-hero'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-outline saas-btn-lg">Book a demo</a>
      </div>
    </div>
  </section>

  <!-- FEATURES -->
  <section class="saas-section saas-section--alt">
    <div class="saas-container">
      <div class="saas-section-head text-center">
        <span class="saas-eyebrow">What's included</span>
        <h2>CRM module coverage</h2>
        <p class="saas-muted">All features are live in the HAIPULSE backend and immediately accessible on activation.</p>
      </div>
      <div class="saas-features-grid">
        <?php foreach ($features as $f): ?>
          <article class="saas-feature-card">
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
        <h2>Built for sales and service businesses in the UAE</h2>
      </div>
      <div class="row g-4">
        <?php
        $audiences = [
            ['title' => 'Sales teams', 'desc' => 'Close deals faster with a structured pipeline, clear ownership, and follow-up reminders.'],
            ['title' => 'Service contractors', 'desc' => 'Track jobs, assign technicians, and record delivery from quote through completion.'],
            ['title' => 'Account managers', 'desc' => 'Maintain full customer context — history, projects, communications — in one place.'],
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

  <!-- PRICING SNIPPET -->
  <section class="saas-section saas-section--alt">
    <div class="saas-container-narrow text-center">
      <span class="saas-eyebrow">Pricing</span>
      <h2>CRM is included in every plan</h2>
      <p class="saas-muted" style="max-width:520px; margin-inline:auto; margin-bottom:32px;">
        CRM comes as part of the Starter package, so you can go live immediately without purchasing separate module licences.
      </p>
      <div class="d-flex gap-3 flex-wrap justify-content-center">
        <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-primary">View all plans</a>
        <a href="<?php echo htmlspecialchars(url('/all-in-one'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-ghost">Explore the full platform</a>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="saas-cta-section">
    <div class="saas-container">
      <h2>Ready to activate CRM for your team?</h2>
      <p>Talk to the team about your sales workflow, team size, and rollout timeline.</p>
      <div class="saas-hero__actions">
        <a href="<?php echo htmlspecialchars(url('/contact?subject=crm&source=crm-cta'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-primary saas-btn-lg">Contact sales</a>
        <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-outline saas-btn-lg">See pricing</a>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../../includes/saas/footer.php'; ?>
