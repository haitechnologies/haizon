<?php
/**
 * Page: Contact Us (NEW DESIGN + SECURITY)
 * Route: /contact
 * Description: Contact form with comprehensive security measures
 * Author: Development Team
 * Updated: March 5, 2026
 * Security Features: Rate limiting, IP blocking, honeypot, captcha, spam filtering
 */

// Set up error handling to prevent 500 errors from breaking the page
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Contact page error [$errno]: $errstr in $errfile:$errline");
    // Don't let errors break the rendering
    return true;
});

set_exception_handler(function($exception) {
    error_log("Contact page exception: " . $exception->getMessage());
  // Form will still render with graceful error handling
});

// ============================================
// SECTION 1: DEPENDENCIES
// ============================================
try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/globals.php';
    require_once __DIR__ . '/../config/session.php';
    require_once __DIR__ . '/../includes/helpers.php';
    require_once __DIR__ . '/../classes/InputValidator.php';
    require_once __DIR__ . '/../classes/RateLimiter.php';
    require_once __DIR__ . '/../classes/SimpleCaptcha.php';
} catch (Exception $e) {
    error_log("Failed to load contact page dependencies: " . $e->getMessage());
    die('<h1>Service Temporarily Unavailable</h1><p>Please try again later.</p>');
}

$saasCatalog = require __DIR__ . '/../config/saas_catalog.php';

// Initialize rate limiter
try {
    RateLimiter::init($conn);
} catch (Exception $e) {
    error_log("Failed to initialize rate limiter: " . $e->getMessage());
}

// Start session for CSRF token validation
startFrontendSession();
SimpleCaptcha::ensureChallenge('contact_form');

// ============================================
// SECTION 2: SECURITY & CONFIGURATION
// ============================================
$errors = [];
$success = '';
$formData = [
    'contact_name' => '',
    'contact_email' => '',
    'subject_code' => 'general-inquiry',
    'contact_message' => ''
];

$claimContext = [
  'active' => false,
  'company_id' => 0,
  'company_slug' => '',
  'company_name' => '',
  'city' => '',
  'phone' => '',
  'email' => '',
  'website' => ''
];

$adInquiryContext = [
  'active' => false,
  'plan_key' => '',
  'plan_title' => '',
  'plan_price' => '',
  'plan_details' => []
];

$softwareInquiryContext = [
  'active' => false,
  'suite_key' => '',
  'suite_title' => '',
  'plan_key' => '',
  'plan_title' => '',
  'plan_price' => '',
  'plan_details' => []
];

// Claim flow context: /contact?claim=1&company_id=123&company_slug=example
$claimRequested = isset($_GET['claim']) && (string)$_GET['claim'] === '1';
if ($claimRequested) {
  $claimCompanyId = (int)($_GET['company_id'] ?? 0);
  $claimCompanySlug = trim((string)($_GET['company_slug'] ?? ''));

  $claimSql = "SELECT id, company_name, slug, city, telephone, email, website
         FROM `" . DB::COMPANIES . "`
         WHERE is_active = 1 AND publish = 1";
  if ($claimCompanyId > 0) {
    $claimSql .= " AND id = ? LIMIT 1";
    $claimStmt = $conn->prepare($claimSql);
    if ($claimStmt) {
      $claimStmt->bind_param('i', $claimCompanyId);
      $claimStmt->execute();
      $claimRow = $claimStmt->get_result()->fetch_assoc();
      $claimStmt->close();
    }
  } elseif ($claimCompanySlug !== '') {
    $claimSql .= " AND slug = ? LIMIT 1";
    $claimStmt = $conn->prepare($claimSql);
    if ($claimStmt) {
      $claimStmt->bind_param('s', $claimCompanySlug);
      $claimStmt->execute();
      $claimRow = $claimStmt->get_result()->fetch_assoc();
      $claimStmt->close();
    }
  }

  if (!empty($claimRow)) {
    $claimContext['active'] = true;
    $claimContext['company_id'] = (int)$claimRow['id'];
    $claimContext['company_slug'] = (string)($claimRow['slug'] ?? '');
    $claimContext['company_name'] = (string)($claimRow['company_name'] ?? '');
    $claimContext['city'] = (string)($claimRow['city'] ?? '');
    $claimContext['phone'] = (string)($claimRow['telephone'] ?? '');
    $claimContext['email'] = (string)($claimRow['email'] ?? '');
    $claimContext['website'] = (string)($claimRow['website'] ?? '');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $formData['subject_code'] = 'business-claim';
      $formData['contact_message'] = "I want to claim this business listing and verify ownership.\n\n"
        . "Business: " . $claimContext['company_name'] . "\n"
        . "Listing URL: " . url('/company/' . rawurlencode($claimContext['company_slug'])) . "\n"
        . "Proof of ownership: [add your evidence here]\n"
        . "Preferred callback: [phone/email + best time]";
    }
  }
}

// Advertising inquiry context: /contact?subject=advertising&ad_plan=featured-listings&source=ads-page
$adPlanTemplates = [
  'featured-listings' => [
    'title' => 'Featured Listings',
    'price' => 'AED 299/month',
    'details' => ['Priority placement', 'Enhanced visibility', 'Featured badge', '3x more views']
  ],
  'banner-ads' => [
    'title' => 'Banner Ads',
    'price' => 'From AED 499/month',
    'details' => ['Homepage placement', 'Category pages', 'Custom design support', 'Performance tracking']
  ],
  'sponsored-content' => [
    'title' => 'Sponsored Content',
    'price' => 'Custom Pricing',
    'details' => ['Professional copywriting', 'SEO optimization', 'Social media promotion', 'Long-term visibility']
  ]
];

$subjectFromQuery = trim((string)($_GET['subject'] ?? ''));
$adPlanFromQuery = trim((string)($_GET['ad_plan'] ?? ''));
$sourceFromQuery = trim((string)($_GET['source'] ?? ''));
$softwareSuiteFromQuery = trim((string)($_GET['suite'] ?? ''));
$softwarePlanFromQuery = trim((string)($_GET['plan'] ?? ''));

if (
  $_SERVER['REQUEST_METHOD'] !== 'POST'
  && !$claimContext['active']
  && $subjectFromQuery === 'advertising'
  && isset($adPlanTemplates[$adPlanFromQuery])
) {
  $selectedAdPlan = $adPlanTemplates[$adPlanFromQuery];
  $adInquiryContext['active'] = true;
  $adInquiryContext['plan_key'] = $adPlanFromQuery;
  $adInquiryContext['plan_title'] = (string)$selectedAdPlan['title'];
  $adInquiryContext['plan_price'] = (string)$selectedAdPlan['price'];
  $adInquiryContext['plan_details'] = (array)$selectedAdPlan['details'];

  $formData['subject_code'] = 'advertising';
  $formData['contact_message'] = "I want to learn more about the advertising package below.\n\n"
    . "Package: " . $adInquiryContext['plan_title'] . "\n"
    . "Price: " . $adInquiryContext['plan_price'] . "\n"
    . "Included features: " . implode(', ', $adInquiryContext['plan_details']) . "\n"
    . "Campaign goal: [add your goal]\n"
    . "Preferred start date: [add date]\n"
    . "Monthly budget: [add budget]\n"
    . "Business category/location: [add details]";

  if ($sourceFromQuery !== '') {
    $formData['contact_message'] .= "\nSource: " . preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $sourceFromQuery);
  }

  // Auto-populate logged-in user's contact info for ad inquiry flow.
  $sessionUserName = trim((string)($_SESSION['frontend_user_name'] ?? ''));
  $sessionUserEmail = trim((string)($_SESSION['frontend_user_email'] ?? ''));

  if ($sessionUserName === '' && !empty($_SESSION['project_pre']['FRONTEND']['full_name'])) {
    $sessionUserName = trim((string)$_SESSION['project_pre']['FRONTEND']['full_name']);
  }
  if ($sessionUserEmail === '' && !empty($_SESSION['project_pre']['FRONTEND']['email'])) {
    $sessionUserEmail = trim((string)$_SESSION['project_pre']['FRONTEND']['email']);
  }

  if ($sessionUserName !== '') {
    $formData['contact_name'] = $sessionUserName;
  }
  if ($sessionUserEmail !== '') {
    $formData['contact_email'] = $sessionUserEmail;
  }
}

$softwarePlanTemplates = $saasCatalog['plans'] ?? [];
$softwareSuiteTemplates = $saasCatalog['systems'] ?? [];

if (
  $_SERVER['REQUEST_METHOD'] !== 'POST'
  && !$claimContext['active']
  && !$adInquiryContext['active']
  && $subjectFromQuery === 'software-sales'
) {
  $selectedPlan = $softwarePlanTemplates[$softwarePlanFromQuery] ?? null;
  $selectedSuite = $softwareSuiteTemplates[$softwareSuiteFromQuery] ?? null;

  if (is_array($selectedPlan) || is_array($selectedSuite)) {
    $softwareInquiryContext['active'] = true;
    $softwareInquiryContext['suite_key'] = is_array($selectedSuite) ? $softwareSuiteFromQuery : '';
    $softwareInquiryContext['suite_title'] = is_array($selectedSuite) ? (string)($selectedSuite['label'] ?? '') : '';
    $softwareInquiryContext['plan_key'] = is_array($selectedPlan) ? $softwarePlanFromQuery : '';
    $softwareInquiryContext['plan_title'] = is_array($selectedPlan) ? (string)($selectedPlan['label'] ?? '') : '';
    $softwareInquiryContext['plan_price'] = is_array($selectedPlan) && !empty($selectedPlan['price_monthly_aed'])
      ? 'AED ' . number_format((float)$selectedPlan['price_monthly_aed'], 0) . '/month'
      : (is_array($selectedPlan) ? 'Custom pricing' : '');
    $softwareInquiryContext['plan_details'] = is_array($selectedPlan) ? (array)($selectedPlan['includes'] ?? []) : [];

    $formData['subject_code'] = 'software-sales';

    $messageParts = [
      'I want to learn more about HAIPULSE software for my business.',
      '',
    ];

    if ($softwareInquiryContext['suite_title'] !== '') {
      $messageParts[] = 'Requested suite: ' . $softwareInquiryContext['suite_title'];
    }
    if ($softwareInquiryContext['plan_title'] !== '') {
      $messageParts[] = 'Requested plan: ' . $softwareInquiryContext['plan_title'];
    }
    if ($softwareInquiryContext['plan_price'] !== '') {
      $messageParts[] = 'Indicative price: ' . $softwareInquiryContext['plan_price'];
    }
    if (!empty($softwareInquiryContext['plan_details'])) {
      $messageParts[] = 'Included: ' . implode(', ', $softwareInquiryContext['plan_details']);
    }

    $messageParts[] = 'Business type: [add your company type]';
    $messageParts[] = 'Team size: [add active users]';
    $messageParts[] = 'Priority workflow: [CRM / Accounting / HR / Shipping]';
    $messageParts[] = 'Preferred go-live date: [add date]';

    if ($sourceFromQuery !== '') {
      $messageParts[] = 'Source: ' . preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $sourceFromQuery);
    }

    $formData['contact_message'] = implode("\n", $messageParts);

    $sessionUserName = trim((string)($_SESSION['frontend_user_name'] ?? ''));
    $sessionUserEmail = trim((string)($_SESSION['frontend_user_email'] ?? ''));

    if ($sessionUserName === '' && !empty($_SESSION['project_pre']['FRONTEND']['full_name'])) {
      $sessionUserName = trim((string)$_SESSION['project_pre']['FRONTEND']['full_name']);
    }
    if ($sessionUserEmail === '' && !empty($_SESSION['project_pre']['FRONTEND']['email'])) {
      $sessionUserEmail = trim((string)$_SESSION['project_pre']['FRONTEND']['email']);
    }

    if ($sessionUserName !== '') {
      $formData['contact_name'] = $sessionUserName;
    }
    if ($sessionUserEmail !== '') {
      $formData['contact_email'] = $sessionUserEmail;
    }
  }
}

// Get client IP address
$clientIP = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? 
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
            $_SERVER['REMOTE_ADDR'] ?? 
            'unknown';

// Sanitize IP (handle multiple IPs from proxy)
if (strpos($clientIP, ',') !== false) {
    $ips = array_map('trim', explode(',', $clientIP));
    $clientIP = $ips[0];
}

$maxMessageLength = 2000;
$minMessageLength = 10;

// ============================================
// SECTION 3: SECURITY CHECKS
// ============================================

// Check if IP is blocked
try {
    if (RateLimiter::isBlocked($clientIP)) {
        die('<h1>Access Denied</h1><p>Your IP address has been blocked due to suspicious activity. Please contact support.</p>');
    }
} catch (Exception $e) {
    error_log("Rate limiter check failed: " . $e->getMessage());
    // Continue anyway - don't break the form
}

// ============================================
// SECTION 4: FORM HANDLING
// ============================================
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF token validation
        if (!validate_csrf_token_frontend($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Security validation failed. Please try again.';
        }
        // Honeypot check (spam prevention)
        elseif (!empty($_POST['website'] ?? '')) {
            $errors[] = 'Spam detected. Submission rejected.';
            error_log("Honeypot triggered from IP: $clientIP");
        }
        // Captcha check
        elseif (!SimpleCaptcha::validate('contact_form', (string)($_POST['contact_captcha'] ?? ''))) {
          $errors[] = 'Security code is incorrect or expired. Please try again.';
        }
        // Rate limit check
        else {
        $rateLimitCheck = RateLimiter::check($clientIP, 'contact_form', 3, 3600); // 3 attempts per hour
        
        if (!$rateLimitCheck['allowed']) {
            $errors[] = 'Too many submissions. Please try again in ' . ($rateLimitCheck['retryAfter'] ?? 60) . ' seconds.';
            error_log("Rate limit exceeded for IP: $clientIP");
        } else {
            // Validate inputs
            $name = InputValidator::string($_POST['contact_name'] ?? '', 100, 2, false);
            $email = InputValidator::email($_POST['contact_email'] ?? '');
            $subject = InputValidator::string($_POST['subject_code'] ?? '', 50, 3, false);
            $message = InputValidator::string($_POST['contact_message'] ?? '', $maxMessageLength, $minMessageLength, false);

            $postedClaimCompanyId = (int)($_POST['claim_company_id'] ?? 0);
            $postedClaimCompanySlug = trim((string)($_POST['claim_company_slug'] ?? ''));
            if ($subject['valid'] && $subject['value'] === 'business-claim' && $postedClaimCompanyId > 0) {
              $claimContext['active'] = true;
              $claimContext['company_id'] = $postedClaimCompanyId;
              $claimContext['company_slug'] = $postedClaimCompanySlug;
            }
            
            // Store form data for repopulation
            $formData = [
                'contact_name' => $_POST['contact_name'] ?? '',
                'contact_email' => $_POST['contact_email'] ?? '',
                'subject_code' => $_POST['subject_code'] ?? '',
                'contact_message' => $_POST['contact_message'] ?? ''
            ];
            
            // Validation
            if (!$name['valid']) {
                $errors[] = 'Name must be between 2 and 100 characters.';
            }
            if (!$email['valid']) {
                $errors[] = 'Please enter a valid email address.';
            }
            if (!$subject['valid']) {
                $errors[] = 'Please select a valid subject.';
            }
            if (!$message['valid']) {
                $errors[] = "Message must be between $minMessageLength and $maxMessageLength characters.";
            }
            
            // Additional spam checks
            $suspiciousPatterns = [
                '/viagra/i',
                '/casino/i',
                '/lottery/i',
                '/bitcoin/i',
                '/porn/i',
                '/adult/i',
                '/<script/i',
                '/onclick/i',
                '/onerror/i'
            ];
            
            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $message['value'] ?? '')) {
                    $errors[] = 'Message contains prohibited content.';
                    error_log("Suspicious content detected from IP: $clientIP");
                    break;
                }
            }
            
            // If validation passes, process form
                  foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $message['value'] ?? '')) {
                      $errors[] = 'Message contains prohibited content.';
                      error_log("Suspicious content detected from IP: $clientIP");
                      break;
                    }
                  }

                  // Block HTML anchor/link tags in message
                  if (empty($errors) && preg_match('/<a[\s>]/i', $message['value'] ?? '')) {
                    $errors[] = 'HTML links are not allowed in messages.';
                    error_log("HTML link detected in contact form from IP: $clientIP");
                  }

                  // Block href= attributes (catches href in any tag)
                  if (empty($errors) && preg_match('/href\s*=/i', $message['value'] ?? '')) {
                    $errors[] = 'Links are not allowed in messages.';
                    error_log("href attribute detected in contact form from IP: $clientIP");
                  }

                  // Block known URL shortener domains
                  if (empty($errors)) {
                    $urlShorteners = [
                      'is.gd', 'did.li', 'bit.ly', 'tinyurl.com', 't.co',
                      'goo.gl', 'ow.ly', 'tiny.cc', 'buff.ly', 'rb.gy',
                      'cutt.ly', 'shorturl.at', 'su.pr', 'adf.ly', 'bl.ink'
                    ];
                    $msgLower = strtolower(($message['value'] ?? '') . ' ' . ($subject['value'] ?? ''));
                    foreach ($urlShorteners as $shortener) {
                      if (strpos($msgLower, $shortener) !== false) {
                        $errors[] = 'Messages containing shortened URLs are not allowed.';
                        error_log("URL shortener ($shortener) detected in contact form from IP: $clientIP");
                        break;
                      }
                    }
                  }

                  // Check message + name against banned words database
                  if (empty($errors) && $message['valid']) {
                    $checkText = strtolower(($name['value'] ?? '') . ' ' . ($message['value'] ?? ''));
                    $bwStmt = $conn->prepare(
                      "SELECT 1 FROM `" . DB::BANNED_WORDS . "` WHERE is_active = 1 AND INSTR(?, LOWER(banned_word)) > 0 LIMIT 1"
                    );
                    if ($bwStmt) {
                      $bwStmt->bind_param('s', $checkText);
                      $bwStmt->execute();
                      $bwResult = $bwStmt->get_result();
                      if ($bwResult->num_rows > 0) {
                        $errors[] = 'Message contains inappropriate content.';
                        error_log("Banned word triggered in contact form from IP: $clientIP");
                      }
                      $bwStmt->close();
                    }
                  }

                  // If validation passes, process form
            if (empty($errors)) {
              $messageToStore = (string)$message['value'];
              if ($subject['value'] === 'business-claim' && $claimContext['active']) {
                $messageToStore .= "\n\n--- Claim Request Metadata ---"
                  . "\nClaim Company ID: " . (int)$claimContext['company_id']
                  . "\nClaim Company Slug: " . (string)$claimContext['company_slug'];
              }

                // Log attempt for rate limiting
                RateLimiter::recordAttempt($clientIP, 'contact_form');
                
                // Insert into database
                $stmt = $conn->prepare("
                    INSERT INTO `" . DB::INQUIRIES . "` 
                    (full_name, email, subject, message, ip_address, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                if ($stmt) {
                    $stmt->bind_param(
                        "sssss",
                        $name['value'],
                        $email['value'],
                        $subject['value'],
                      $messageToStore,
                        $clientIP
                    );
                    
                    if ($stmt->execute()) {
                        $inquiryId = $conn->insert_id;
                        $success = 'Thank you for contacting us! We will respond to your inquiry within 24-48 hours.';

                      // Contact submissions are stored only; no emails are sent from this form.
                        
                        // Clear form data after successful submission
                        $formData = [
                            'contact_name' => '',
                            'contact_email' => '',
                            'subject_code' => 'general-inquiry',
                            'contact_message' => ''
                        ];
                        SimpleCaptcha::refreshChallenge('contact_form');
                        
                    } else {
                        $errors[] = 'Failed to submit your message. Please try again or contact us directly.';
                        error_log("Contact form submission failed: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    $errors[] = 'System error. Please try again later.';
                    error_log("Failed to prepare contact form statement: " . $conn->error);
                }
            }
        }
    }
    }
} catch (Exception $e) {
    $errors[] = 'An error occurred while processing your submission. Please try again.';
    error_log("Contact form exception: " . $e->getMessage());
}

// ============================================
// SECTION 5: PAGE VARIABLES
// ============================================
$csrfToken = csrf_field_frontend();

$subjectOptions = [
    'general-inquiry' => 'General inquiry',
  'business-claim' => 'Claim this business listing',
    'business-verification' => 'Business verification',
    'listing-issue' => 'Listing issue',
    'technical-support' => 'Technical support',
    'partnership' => 'Partnership opportunity',
    'software-sales' => 'Software demo / sales inquiry',
    'advertising' => 'Advertising inquiry',
    'account-issue' => 'Account issue',
    'other' => 'Other'
];

$pageTitle = 'Contact HAIPULSE - Support & Inquiries';
$pageDescription = 'Contact HAIPULSE support for assistance with listings, verification, partnerships, and general inquiries. Response within 24-48 hours.';
$ampHtmlUrl = url('/contact/amp');

// Generate JSON-LD structured data for rich results
$contactSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'ContactPage',
    'name' => 'Contact HAIPULSE',
    'description' => $pageDescription,
    'url' => getFullUrl('/contact')
];
$jsonLdSchema = '<script type="application/ld+json">' . json_encode($contactSchema, JSON_UNESCAPED_SLASHES) . '</script>';

// Add breadcrumb schema
$breadcrumbs = [
    ['name' => 'Home', 'url' => getFullUrl('/')],
    ['name' => 'Contact', 'url' => getFullUrl('/contact')]
];
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main id="main-content" class="section">
    <div class="container-narrow list-layout">
      <!-- Left Sidebar: Support Hours & Email -->
      <aside>
        <article class="card-ui detail-box contact-card-spaced">
          <h3 class="contact-card-title"><i class="fa fa-clock-o me-2"></i>Support hours</h3>
          <ul class="contact-hours-list">
            <li class="contact-hours-item">
              <strong>Monday - Saturday:</strong><br>
              <span class="contact-hours-time">9:00 AM to 7:00 PM (GST)</span>
            </li>
            <li class="contact-hours-item">
              <strong>Sunday:</strong><br>
              <span class="contact-hours-time contact-hours-time--closed">Closed</span>
            </li>
          </ul>
          <p class="contact-response-note">
            <i class="fa fa-info-circle me-1"></i>Average response: 24-48 hours
          </p>
        </article>

        <article class="card-ui detail-box">
          <h3 class="contact-card-title"><i class="fa fa-envelope me-2"></i>Email us</h3>
          <p class="contact-email-paragraph">
            <!-- Email hidden from bots, revealed only on click -->
            <a href="#" id="email-link" onclick="revealEmail(event)" class="contact-email-link" title="Click to reveal email">
              <span class="contact-email-chip">Click to reveal</span>
            </a>
          </p>
          <p class="contact-note">
            Or use the form above for faster support ticket creation.
          </p>
        </article>
      </aside>

      <!-- Right Main: Contact Form -->
      <section>
        <form class="card-ui form-box" method="post" action="" onsubmit="return validateContactForm(event)">
          <?php echo $csrfToken; ?>
          <?php if ($claimContext['active']): ?>
            <input type="hidden" name="claim_company_id" value="<?php echo (int)$claimContext['company_id']; ?>">
            <input type="hidden" name="claim_company_slug" value="<?php echo htmlspecialchars($claimContext['company_slug'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php endif; ?>
          
          <!-- Honeypot field (hidden from real users) -->
          <input type="text" name="website" class="honeypot-field" autocomplete="off" tabindex="-1" aria-hidden="true">
          
          <h1 class="contact-form-title">Contact HAIPULSE</h1>
          <p class="muted">Get in touch with our support team. Typically respond within 24-48 hours.</p>

          <?php if ($claimContext['active']): ?>
            <div class="contact-alert contact-alert--success">
              <strong class="contact-alert-heading">Business claim request</strong>
              Claiming: <?php echo htmlspecialchars($claimContext['company_name'], ENT_QUOTES, 'UTF-8'); ?>
              <?php if ($claimContext['city'] !== ''): ?>
                (<?php echo htmlspecialchars($claimContext['city'], ENT_QUOTES, 'UTF-8'); ?>)
              <?php endif; ?>
              <br>
              Submit this form with your ownership proof and our team will verify the listing.
            </div>
          <?php endif; ?>

          <?php if ($adInquiryContext['active']): ?>
            <div class="contact-alert contact-alert--success">
              <strong class="contact-alert-heading">Advertising package selected</strong>
              Package: <?php echo htmlspecialchars($adInquiryContext['plan_title'], ENT_QUOTES, 'UTF-8'); ?>
              (<?php echo htmlspecialchars($adInquiryContext['plan_price'], ENT_QUOTES, 'UTF-8'); ?>)
              <br>
              Submit this form and our ads team will contact you with package details and next steps.
            </div>
          <?php endif; ?>

          <?php if ($softwareInquiryContext['active']): ?>
            <div class="contact-alert contact-alert--success">
              <strong class="contact-alert-heading">Software inquiry selected</strong>
              <?php if ($softwareInquiryContext['suite_title'] !== ''): ?>
                Suite: <?php echo htmlspecialchars($softwareInquiryContext['suite_title'], ENT_QUOTES, 'UTF-8'); ?><br>
              <?php endif; ?>
              <?php if ($softwareInquiryContext['plan_title'] !== ''): ?>
                Plan: <?php echo htmlspecialchars($softwareInquiryContext['plan_title'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if ($softwareInquiryContext['plan_price'] !== ''): ?>
                  (<?php echo htmlspecialchars($softwareInquiryContext['plan_price'], ENT_QUOTES, 'UTF-8'); ?>)
                <?php endif; ?>
                <br>
              <?php endif; ?>
              Submit this form and our software team will contact you with setup details and next steps.
            </div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
            <div class="contact-alert contact-alert--error">
              <strong class="contact-alert-heading">âš  Please correct the following:</strong>
              <ul class="contact-alert-list">
                <?php foreach ($errors as $error): ?>
                  <li class="contact-alert-item"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          
          <?php if ($success): ?>
            <div class="contact-alert contact-alert--success">
              <strong class="contact-alert-heading">âœ“ Success!</strong>
              <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <label for="contact_name" class="contact-label contact-label-first">Full Name <span class="contact-required">*</span></label>
          <input class="field" id="contact_name" name="contact_name" type="text" autocomplete="name" required 
                 placeholder="Your full name"
                 maxlength="100"
                 value="<?php echo htmlspecialchars($formData['contact_name'], ENT_QUOTES, 'UTF-8'); ?>">

          <label for="contact_email" class="contact-label">Email Address <span class="contact-required">*</span></label>
          <input class="field" id="contact_email" name="contact_email" type="email" autocomplete="email" required
                 placeholder="your.email@example.com"
                 maxlength="100"
                 value="<?php echo htmlspecialchars($formData['contact_email'], ENT_QUOTES, 'UTF-8'); ?>">
          <small class="contact-help-text">We'll send our response to this address</small>

          <label for="contact_subject" class="contact-label">Subject <span class="contact-required">*</span></label>
          <select class="select" id="contact_subject" name="subject_code" required>
            <option value="">Select a subject...</option>
            <?php foreach ($subjectOptions as $value => $label): ?>
              <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"
                      <?php echo ($formData['subject_code'] === $value) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label for="contact_message" class="contact-label">Message <span class="contact-required">*</span></label>
          <textarea class="textarea" id="contact_message" name="contact_message" rows="6" required
                    placeholder="Please describe your inquiry in detail... (minimum 10 characters)"
                    maxlength="2000"
                    onkeyup="updateCharCount()"><?php echo htmlspecialchars($formData['contact_message'], ENT_QUOTES, 'UTF-8'); ?></textarea>
          <small class="contact-help-text">
            <span class="contact-char-counter">
              <span id="charCount"><?php echo strlen($formData['contact_message']); ?></span>/2000
            </span>
          </small>

          <label for="contact_captcha" class="contact-label">Security Code <span class="contact-required">*</span></label>
          <p class="contact-help-text">Enter the characters shown in the image below to prove this request is human.</p>
          <div class="mb-2">
            <img
              id="contact-captcha-image"
              src="<?php echo htmlspecialchars(url('/captcha-image?context=contact_form&refresh=1'), ENT_QUOTES, 'UTF-8'); ?>"
              alt="Captcha code"
              width="180"
              height="56">
          </div>
          <div class="mb-2">
            <button class="btn btn-light btn-sm" type="button" id="refresh-captcha-button">Refresh code</button>
          </div>
          <input class="field" id="contact_captcha" name="contact_captcha" type="text" required
                 placeholder="Enter the security code"
                 inputmode="text"
                 autocapitalize="characters"
                 autocomplete="off"
                 maxlength="7">

          <div class="contact-submit-row">
            <button class="btn-ui btn-primary-ui contact-submit-btn" type="submit">
              <i class="fa fa-paper-plane-o me-2"></i>Send message
            </button>
            <p class="contact-security-text">
              <i class="fa fa-lock me-1"></i>Your information is secure and will not be shared.
            </p>
          </div>
        </form>
      </section>
    </div>
  </main>

  <script>
    // Email protection - hide from bots, reveal on click
    function revealEmail(event) {
      event.preventDefault();
      const emailLink = document.getElementById('email-link');
      const email = 'hello@haipulse.com';
      emailLink.innerHTML = '<a href="mailto:' + email + '" class="contact-email-link">' + email + '</a>';
      emailLink.onclick = null;
      emailLink.classList.add('contact-email-link--revealed');
    }
    
    function updateCharCount() {
      const textarea = document.getElementById('contact_message');
      document.getElementById('charCount').textContent = textarea.value.length;
    }
    
    function validateContactForm(event) {
      // Client-side validation before submission
      const name = document.getElementById('contact_name').value.trim();
      const email = document.getElementById('contact_email').value.trim();
      const subject = document.getElementById('contact_subject').value;
      const message = document.getElementById('contact_message').value.trim();
      const captcha = document.getElementById('contact_captcha').value.trim();
      
      // Check honeypot
      const honeypot = document.querySelector('input[name="website"]').value;
      if (honeypot) {
        event.preventDefault();
        alert('Invalid submission detected.');
        return false;
      }
      
      // Basic validation
      if (name.length < 2) {
        alert('Please enter your name (at least 2 characters).');
        document.getElementById('contact_name').focus();
        return false;
      }
      
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        document.getElementById('contact_email').focus();
        return false;
      }
      
      if (!subject) {
        alert('Please select a subject.');
        document.getElementById('contact_subject').focus();
        return false;
      }
      
      if (message.length < 10) {
        alert('Message must be at least 10 characters long.');
        document.getElementById('contact_message').focus();
        return false;
      }
      
      if (message.length > 2000) {
        alert('Message is too long (maximum 2000 characters).');
        return false;
      }

      if (captcha.length < 4) {
        alert('Please enter the security code shown in the image.');
        document.getElementById('contact_captcha').focus();
        return false;
      }
      
      return true;
    }

    function refreshContactCaptcha() {
      const image = document.getElementById('contact-captcha-image');
      if (!image) {
        return;
      }

      const baseUrl = '<?php echo htmlspecialchars(url('/captcha-image?context=contact_form&refresh=1'), ENT_QUOTES, 'UTF-8'); ?>';
      image.src = baseUrl + '&_=' + Date.now();
    }
    
    // Initialize character count on page load
    document.addEventListener('DOMContentLoaded', function() {
      updateCharCount();

      const refreshButton = document.getElementById('refresh-captcha-button');
      if (refreshButton) {
        refreshButton.addEventListener('click', refreshContactCaptcha);
      }
    });
  </script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

