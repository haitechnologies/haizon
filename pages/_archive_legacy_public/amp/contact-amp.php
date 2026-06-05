<?php
/**
 * AMP Page: Contact
 * Route: /contact/amp
 * 
 * Mobile-optimized contact page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Page meta
$pageTitle = 'Contact Us - HAIPULSE';
$pageDescription = 'Get in touch with UAE Business Directory. We\'re here to help with any questions or inquiries.';
$pageKeywords = 'contact us, customer support, business inquiries, UAE directory support';
$canonicalUrl = url('/contact');
$pageUrl = url('/contact/amp');

// Open Graph
$ogTitle = $pageTitle;
$ogDescription = $pageDescription;
$ogImage = getFullUrl('/assets/images/brand/logo.png');
$ogType = 'website';

// Twitter Card
$twitterCard = 'summary';
$twitterTitle = $pageTitle;
$twitterDescription = $pageDescription;
$twitterImage = $ogImage;

// Schema.org ContactPage
$schemaData = [
  '@context' => 'https://schema.org',
  '@type' => 'ContactPage',
  'name' => 'Contact UAE Business Directory',
  'description' => $pageDescription,
  'url' => $canonicalUrl,
  'mainEntity' => [
    '@type' => 'Organization',
    'name' => 'UAE Business Directory',
    'url' => getFullUrl('/'),
    'contactPoint' => [
      '@type' => 'ContactPoint',
      'contactType' => 'Customer Service',
      'email' => 'info@haipulse.com',
      'availableLanguage' => ['English', 'Arabic'],
      'areaServed' => 'AE'
    ]
  ]
];

// Add breadcrumb schema
$breadcrumbSchema = [
  '@context' => 'https://schema.org',
  '@type' => 'BreadcrumbList',
  'itemListElement' => [
    [
      '@type' => 'ListItem',
      'position' => 1,
      'name' => 'Home',
      'item' => getFullUrl('/')
    ],
    [
      '@type' => 'ListItem',
      'position' => 2,
      'name' => 'Contact',
      'item' => $pageUrl
    ]
  ]
];

// Combine schemas
$schemaData = [
  $schemaData,
  $breadcrumbSchema
];

// AMP components
$ampComponents = [
    ['name' => 'amp-form', 'src' => 'https://cdn.ampproject.org/v0/amp-form-0.1.js']
];

$pageCustomCss = <<<'CSS'
  /* Contact Page Styles */
  .page-header {
    background: linear-gradient(135deg, #0f4ad8 0%, #1e5fd8 100%);
    color: white;
    padding: 48px 16px;
    text-align: center;
    margin-bottom: 24px;
  }
  
  .page-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 12px;
  }
  
  .page-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
  }
  
  .contact-info-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    padding: 0 16px;
    margin-bottom: 24px;
  }
  
  .contact-info-card {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 16px;
  }
  
  .contact-icon {
    font-size: 2.5rem;
    min-width: 60px;
    text-align: center;
  }
  
  .contact-details h3 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 4px;
    color: #333;
  }
  
  .contact-details p {
    font-size: 1rem;
    color: #666;
    margin: 0;
  }
  
  .contact-details a {
    color: #1e5fd8;
    text-decoration: none;
  }
  
  .form-section {
    background: white;
    padding: 24px 16px;
    margin: 16px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }
  
  .form-title {
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
  }
  
  .form-subtitle {
    font-size: 1rem;
    color: #666;
    margin-bottom: 24px;
  }
  
  .form-group {
    margin-bottom: 16px;
  }
  
  .form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #333;
  }
  
  .form-input,
  .form-textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    font-family: inherit;
  }
  
  .form-textarea {
    min-height: 120px;
    resize: vertical;
  }
  
  .form-button {
    width: 100%;
    padding: 14px;
    background: #1e5fd8;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
  }
  
  .form-note {
    font-size: 0.85rem;
    color: #888;
    margin-top: 12px;
    text-align: center;
  }
  
  .view-regular {
    text-align: center;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 6px;
    margin: 16px;
  }
  
  .view-regular a {
    color: #1e5fd8;
    text-decoration: none;
    font-weight: 500;
  }
CSS;

include __DIR__ . '/amp-header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <h1 class="page-title">Contact Us</h1>
  <div class="page-subtitle">Reach our team for support, partnerships, and business inquiries.</div>
</div>

<!-- Contact Information -->
<div class="contact-info-grid">
  <div class="contact-info-card">
    <div class="contact-icon">Email</div>
    <div class="contact-details">
      <h3>Email</h3>
      <p><a href="mailto:info@haipulse.com">info@haipulse.com</a></p>
    </div>
  </div>
  
  <div class="contact-info-card">
    <div class="contact-icon">Address</div>
    <div class="contact-details">
      <h3>Address</h3>
      <p>Dubai, United Arab Emirates</p>
    </div>
  </div>
  
  <div class="contact-info-card">
    <div class="contact-icon">Hours</div>
    <div class="contact-details">
      <h3>Business Hours</h3>
      <p>Mon-Sat: 9:00 AM - 7:00 PM (GST)<br>Sunday: Closed</p>
    </div>
  </div>
</div>

<!-- Contact Form Note -->
<div class="form-section">
  <h2 class="form-title">Send Us a Message</h2>
  <p class="form-subtitle">
    For the best experience with our contact form, please visit the full website version.
  </p>
  
  <div style="text-align: center; padding: 32px 0;">
    <a href="<?= $canonicalUrl ?>" style="display: inline-block; padding: 14px 32px; background: #1e5fd8; color: white; text-decoration: none; border-radius: 6px; font-weight: 600;">
      Go to Contact Form
    </a>
  </div>
</div>

<div class="context-nav">
  <div class="context-nav-title">Explore popular AMP pages</div>
  <div class="context-nav-links">
    <a href="<?= url('/listings/amp') ?>" class="context-nav-link">Browse Companies</a>
    <a href="<?= url('/trade/hs-codes/amp') ?>" class="context-nav-link">Find HS Codes</a>
    <a href="<?= url('/blog/amp') ?>" class="context-nav-link">Read Business Blog</a>
    <a href="<?= url('/about/amp') ?>" class="context-nav-link">About HAIPULSE</a>
  </div>
</div>

<!-- View Full Version -->
<div class="view-regular">
  <a href="<?= $canonicalUrl ?>">View Full Website Version</a>
</div>

<?php include __DIR__ . '/amp-footer.php'; ?>


