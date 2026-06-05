<?php
/**
 * Page: Add Business Listing (NEW DESIGN)
 * Route: /add-business
 * Description: Submit new business listing to directory
 * Author: Development Team
 * Created: February 28, 2026
 */

// ============================================
// SECTION 1: DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/InputValidator.php';
require_once __DIR__ . '/../classes/RateLimiter.php';
require_once __DIR__ . '/../classes/SimpleCaptcha.php';
require_once __DIR__ . '/../classes/frontend/UserSettings.php';
require_once __DIR__ . '/../classes/EmailProviderManager.php';
require_once __DIR__ . '/../classes/SMTPMailer.php';
require_once __DIR__ . '/../includes/helpers.php';

// SMTPMailer resolves provider config through $GLOBALS['conn'].
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof mysqli)) {
    $GLOBALS['conn'] = $conn;
}

RateLimiter::init($conn);

// ============================================
// SECTION 2: CHECK IF USER IS LOGGED IN
// ============================================
startFrontendSession();
SimpleCaptcha::ensureChallenge('add_business_form');
$isLoggedIn = isFrontendUserLoggedIn();
$userId = getFrontendUserId();

// ============================================
// SECTION 3: GET CATEGORIES FOR DROPDOWN
// ============================================
$categoriesQuery = "SELECT id, name, slug FROM `" . DB::CATEGORIES . "` WHERE is_active = 1 ORDER BY name ASC";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
if ($categoriesResult) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// ============================================
// SECTION 4: HANDLE FORM SUBMISSION
// ============================================
$errors = [];
$success = '';
$formData = [];

$requestedCategoryId = intval($_GET['category_id'] ?? 0);
$requestedCategorySlug = trim((string)($_GET['category'] ?? ($_GET['category_slug'] ?? '')));
$preselectedCategoryId = 0;

if ($requestedCategoryId > 0) {
  foreach ($categories as $cat) {
    if ((int)($cat['id'] ?? 0) === $requestedCategoryId) {
      $preselectedCategoryId = $requestedCategoryId;
      break;
    }
  }
} elseif ($requestedCategorySlug !== '') {
  foreach ($categories as $cat) {
    if (strcasecmp((string)($cat['slug'] ?? ''), $requestedCategorySlug) === 0) {
      $preselectedCategoryId = (int)($cat['id'] ?? 0);
      break;
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $preselectedCategoryId > 0) {
  $formData['category_id'] = $preselectedCategoryId;
}

// Auto-prefill add-business form for logged-in frontend users.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $isLoggedIn && $userId > 0) {
  try {
    $userSettings = new UserSettings($conn);
    $currentUser = $userSettings->getUserInfo($userId);

    if (is_array($currentUser)) {
      $prefillData = [
        'contact_person' => trim((string)($currentUser['full_name'] ?? '')),
        'email' => trim((string)($currentUser['email'] ?? '')),
        'mobile' => trim((string)($currentUser['mobile'] ?? '')),
      ];

      // Use mobile as a sensible phone fallback when no dedicated phone exists.
      if ($prefillData['mobile'] !== '') {
        $prefillData['phone'] = $prefillData['mobile'];
      }

      foreach ($prefillData as $key => $value) {
        if ($value !== '' && (!isset($formData[$key]) || trim((string)$formData[$key]) === '')) {
          $formData[$key] = $value;
        }
      }
    }
  } catch (Throwable $e) {
    error_log('add-business prefill warning: ' . $e->getMessage());
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $clientIP = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (strpos($clientIP, ',') !== false) {
      $clientIP = trim(explode(',', $clientIP)[0]);
    }

    // Start session for CSRF token validation
    startFrontendSession();

    // CSRF validation
    if (!validate_csrf_token_frontend($_POST['csrf_token'] ?? '')) {
      $errors[] = 'Invalid security token. Please try again.';
    } elseif (!empty($_POST['fax_number'] ?? '')) {
      $errors[] = 'Spam detected. Submission rejected.';
    } elseif (!SimpleCaptcha::validate('add_business_form', (string)($_POST['add_business_captcha'] ?? ''))) {
      $errors[] = 'Security code is incorrect or expired. Please try again.';
    } else {
      $rateLimit = RateLimiter::check($clientIP, 'add_business_form', 5, 3600);
      if (!$rateLimit['allowed']) {
        $errors[] = 'Too many submissions. Please try again later.';
      } else {
        // Get form data
        $formData = [
            'company_name' => trim($_POST['company_name'] ?? ''),
            'category_id' => intval($_POST['category_id'] ?? 0),
            'license_number' => trim($_POST['license_number'] ?? ''),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'mobile' => trim($_POST['mobile'] ?? ''),
            'emirate' => trim($_POST['emirate'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'operating_hours' => trim($_POST['operating_hours'] ?? ''),
            'keywords' => trim($_POST['keywords'] ?? ''),
            'lat' => floatval($_POST['lat'] ?? 0),
            'lng' => floatval($_POST['lng'] ?? 0),
        ];
        
        // Validation
        $emailValidation = InputValidator::email($formData['email']);

        if (empty($formData['company_name'])) {
            $errors[] = 'Company name is required.';
        }

        if (empty($formData['category_id'])) {
            $errors[] = 'Please select a category.';
        }

        if (empty($formData['email'])) {
            $errors[] = 'Email address is required.';
        } elseif (!$emailValidation['valid']) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (empty($formData['phone'])) {
            $errors[] = 'Phone number is required.';
        }
        
        if (empty($formData['emirate'])) {
            $errors[] = 'Please select an emirate.';
        }
        
        // Duplicate detection: flag as duplicate only when strong signals match.
        // Criteria (branch-aware):
        //   HARD BLOCK  â€” same website domain (non-trivial) in same emirate
        //   HARD BLOCK  â€” same business email address
        //   SOFT BLOCK  â€” same normalised company name AND same emirate AND same contact email
        // A branch has the same name but different city/contact/email, so it passes through.
        if (empty($errors)) {
            $submittedEmail   = strtolower(trim($formData['email']));
            $submittedEmirate = strtolower(trim($formData['emirate']));
            $submittedName    = strtolower(trim(preg_replace('/\s+/', ' ', $formData['company_name'])));

            // Normalise website: strip scheme + www + trailing slash so "www.acme.ae" == "acme.ae"
            $websiteRaw = trim($formData['website'] ?? '');
            $websiteDomain = '';
            if ($websiteRaw !== '' && $websiteRaw !== 'http://' && $websiteRaw !== 'https://') {
                $parsedHost = strtolower(parse_url($websiteRaw, PHP_URL_HOST) ?? '');
                if ($parsedHost === '') {
                    $parsedHost = strtolower(preg_replace('#^(https?://)?(www\.)?#i', '', $websiteRaw));
                    $parsedHost = explode('/', rtrim($parsedHost, '/'))[0];
                }
                $websiteDomain = preg_replace('/^www\./i', '', $parsedHost);
            }
            // Skip generic/placeholder domains that many businesses could share
            $ignoredDomains = ['gmail.com','yahoo.com','hotmail.com','outlook.com','icloud.com','mail.com',''];
            $websiteIsSignificant = $websiteDomain !== '' && !in_array($websiteDomain, $ignoredDomains, true);

            $dupQuery = "SELECT id, company_name, email, website, state FROM `" . DB::COMPANIES . "`
                         WHERE (
                             email = ?
                         ) OR (
                             LOWER(TRIM(REGEXP_REPLACE(company_name, '\\\\s+', ' '))) = ?
                             AND LOWER(state) = ?
                             AND email = ?
                         )
                         LIMIT 5";
            $dupStmt = $conn->prepare($dupQuery);
            if ($dupStmt) {
                $dupStmt->bind_param('ssss', $submittedEmail, $submittedName, $submittedEmirate, $submittedEmail);
                $dupStmt->execute();
                $dupResult = $dupStmt->get_result();
                while ($dupRow = $dupResult->fetch_assoc()) {
                    $existingEmail   = strtolower(trim($dupRow['email'] ?? ''));
                    $existingName    = strtolower(trim(preg_replace('/\s+/', ' ', $dupRow['company_name'] ?? '')));
                    $existingEmirate = strtolower(trim($dupRow['state'] ?? ''));

                    // Signal 1: same business email â†’ always a duplicate
                    if ($submittedEmail !== '' && $existingEmail === $submittedEmail) {
                        $errors[] = 'A business listing with this email address already exists (ID #' . (int)$dupRow['id'] . '). If this is a branch, please use a branch-specific email.';
                        break;
                    }

                    // Signal 2: same normalised name + same emirate + same email
                    if ($existingName === $submittedName && $existingEmirate === $submittedEmirate && $existingEmail === $submittedEmail) {
                        $errors[] = 'A listing for "' . htmlspecialchars($dupRow['company_name']) . '" in the same emirate with the same contact email already exists (ID #' . (int)$dupRow['id'] . ').';
                        break;
                    }
                }
                $dupStmt->close();

                // Signal 3: same significant website domain + same emirate (separate query for clarity)
                if (empty($errors) && $websiteIsSignificant) {
                    $websiteLike1 = '%://' . $websiteDomain . '%';
                    $websiteLike2 = '%://www.' . $websiteDomain . '%';
                    $websiteStmt = $conn->prepare(
                        "SELECT id, company_name FROM `" . DB::COMPANIES . "`
                         WHERE (website LIKE ? OR website LIKE ?)
                           AND LOWER(state) = ?
                         LIMIT 1"
                    );
                    if ($websiteStmt) {
                        $websiteStmt->bind_param('sss', $websiteLike1, $websiteLike2, $submittedEmirate);
                        $websiteStmt->execute();
                        $websiteResult = $websiteStmt->get_result();
                        if ($row = $websiteResult->fetch_assoc()) {
                            $errors[] = 'A listing with this website (' . htmlspecialchars($websiteDomain) . ') in the same emirate already exists: "' . htmlspecialchars($row['company_name']) . '" (ID #' . (int)$row['id'] . '). If this is a branch, please contact support.';
                        }
                        $websiteStmt->close();
                    }
                }
            } else {
                error_log('add-business duplicate check prepare failed: ' . $conn->error);
            }
        }
        
        // If validation passes, insert company
        if (empty($errors)) {
            // Generate slug
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $formData['company_name'])));

            $availableColumns = [];
            $columnMetadata = [];
            $columnsStmt = $conn->prepare(
              "SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, DATA_TYPE, COLUMN_TYPE, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
            );

            if ($columnsStmt) {
                $companiesTable = DB::COMPANIES;
                $columnsStmt->bind_param('s', $companiesTable);
                $columnsStmt->execute();
                $columnsResult = $columnsStmt->get_result();
                while ($columnRow = $columnsResult->fetch_assoc()) {
                    $columnName = (string)($columnRow['COLUMN_NAME'] ?? '');
                    if ($columnName !== '') {
                        $availableColumns[$columnName] = true;
                    $columnMetadata[$columnName] = [
                      'is_nullable' => (string)($columnRow['IS_NULLABLE'] ?? 'YES'),
                      'column_default' => $columnRow['COLUMN_DEFAULT'] ?? null,
                      'data_type' => strtolower((string)($columnRow['DATA_TYPE'] ?? '')),
                      'column_type' => strtolower((string)($columnRow['COLUMN_TYPE'] ?? '')),
                      'extra' => strtolower((string)($columnRow['EXTRA'] ?? '')),
                    ];
                    }
                }
                $columnsStmt->close();
            }

            $firstExistingColumn = function(array $candidates) use ($availableColumns) {
                foreach ($candidates as $candidate) {
                    if (isset($availableColumns[$candidate])) {
                        return $candidate;
                    }
                }
                return null;
            };

            $companyNameColumn = $firstExistingColumn(['company_name', 'name']);
            if ($companyNameColumn === null) {
                $errors[] = 'Company table schema is missing required name column.';
            }

            if (empty($errors)) {
              $columns = [];
              $placeholders = [];
              $types = '';
              $values = [];
              $rawColumns = [];
              $rawValues = [];

              $addParam = function($column, $type, $value) use (&$columns, &$placeholders, &$types, &$values, $availableColumns) {
                  if (!isset($availableColumns[$column])) {
                      return;
                  }
                  $columns[] = "`{$column}`";
                  $placeholders[] = '?';
                  $types .= $type;
                  $values[] = $value;
              };

              $addRaw = function($column, $rawValue) use (&$rawColumns, &$rawValues, $availableColumns) {
                  if (!isset($availableColumns[$column])) {
                      return;
                  }
                  $rawColumns[] = "`{$column}`";
                  $rawValues[] = $rawValue;
              };

              $createdBy = $userId > 0 ? $userId : 0;

              $categoryColumn = $firstExistingColumn(['primary_category_id', 'category_id', 'category']);
              $contactColumn = $firstExistingColumn(['contact_person', 'contact_name', 'owner_name']);

              $addParam($companyNameColumn, 's', $formData['company_name']);
              $addParam('slug', 's', $slug);
              if ($categoryColumn !== null) {
                  $addParam($categoryColumn, 'i', (int)$formData['category_id']);
              }
              $addParam('license_number', 's', $formData['license_number']);
              if ($contactColumn !== null) {
                  $addParam($contactColumn, 's', $formData['contact_person']);
              }
              $addParam('email', 's', $formData['email']);
              $addParam('phone', 's', $formData['phone']);
              $addParam('mobile', 's', $formData['mobile']);
              // DB column is 'state'; form field is 'emirate' â€” map to correct column
              $addParam('state', 's', $formData['emirate']);
              // Fallback: if city left blank, use the emirate label as city
              $cityValue = $formData['city'] !== '' ? $formData['city'] : $formData['emirate'];
              $addParam('city', 's', $cityValue);
              $addParam('address', 's', $formData['address']);
              $addParam('description', 's', $formData['description']);
              $addParam('website', 's', $formData['website']);
              $addParam('operating_hours', 's', $formData['operating_hours']);
              $addParam('keywords', 's', $formData['keywords']);
              $addParam('lat', 'd', (float)$formData['lat']);
              $addParam('lng', 'd', (float)$formData['lng']);
              $addParam('created_by', 'i', $createdBy);

              $addRaw('is_active', '0');
              $addRaw('publish', '0');
              $addRaw('featured', '0');
              $addRaw('verified', '0');
              $addRaw('created_at', 'NOW()');
              $addRaw('updated_at', 'NOW()');

              $insertedColumnNames = [];
              foreach ($columns as $quotedColumn) {
                $insertedColumnNames[trim($quotedColumn, '`')] = true;
              }
              foreach ($rawColumns as $quotedColumn) {
                $insertedColumnNames[trim($quotedColumn, '`')] = true;
              }

              foreach ($columnMetadata as $columnName => $meta) {
                if (isset($insertedColumnNames[$columnName])) {
                  continue;
                }

                $hasDefault = $meta['column_default'] !== null;
                $isNullable = strtoupper((string)$meta['is_nullable']) === 'YES';
                $isAutoIncrement = strpos((string)$meta['extra'], 'auto_increment') !== false;

                if ($hasDefault || $isNullable || $isAutoIncrement) {
                  continue;
                }

                $dataType = (string)$meta['data_type'];
                $columnType = (string)$meta['column_type'];

                if (preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|float|double|real|bit)$/', $dataType)) {
                  $addRaw($columnName, '0');
                  continue;
                }

                if ($dataType === 'date' || $dataType === 'datetime' || $dataType === 'timestamp' || $dataType === 'time' || $dataType === 'year') {
                  $addRaw($columnName, 'NOW()');
                  continue;
                }

                if (strpos($columnType, 'enum(') === 0 && preg_match("/enum\\('([^']*)'/", $columnType, $enumMatch)) {
                  $addParam($columnName, 's', (string)($enumMatch[1] ?? ''));
                  continue;
                }

                $addParam($columnName, 's', '');
              }

              $insertColumns = array_merge($columns, $rawColumns);
              $insertValues = array_merge($placeholders, $rawValues);

              if (empty($insertColumns) || empty($insertValues)) {
                  $errors[] = 'Submission is temporarily unavailable. Please try again in a moment.';
              } else {
                  $insertQuery = "INSERT INTO `" . DB::COMPANIES . "` (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
                  $stmt = $conn->prepare($insertQuery);
                  if (!$stmt) {
                      $prepareError = 'add-business prepare failed: ' . $conn->error . ' | query=' . $insertQuery;
                      error_log($prepareError);
                      if (isset($GLOBALS['frontendLogger'])) {
                          $GLOBALS['frontendLogger']->error($prepareError, ['module' => 'add-business'], __FILE__, __LINE__);
                      }
                  }
              }
            } else {
              $stmt = null;
            }

            if (isset($stmt) && $stmt) {
              if ($types !== '') {
                $bindParams = array_merge([$types], $values);
                $bindRefs = [];
                foreach ($bindParams as $idx => $paramValue) {
                  $bindRefs[$idx] = &$bindParams[$idx];
                }
                call_user_func_array([$stmt, 'bind_param'], $bindRefs);
              }

              if ($stmt->execute()) {
                $companyId = $stmt->insert_id;
                $success = 'Business listing submitted successfully! It will be reviewed by our team before publishing.';
                RateLimiter::recordAttempt($clientIP, 'add_business_form');

                // Send confirmation email to the submitting visitor only.
                $mailer = new SMTPMailer();
                $listingUrl = '';
                if (!empty($slug)) {
                    $listingUrl = url('/company/' . rawurlencode((string)$slug));
                }

                $supportEmail = trim((string)($_ENV['MAIL_FROM_ADDRESS'] ?? getenv('MAIL_FROM_ADDRESS') ?? 'support@haipulse.com'));

                if (filter_var((string)$formData['email'], FILTER_VALIDATE_EMAIL)) {
                    $userSubject = 'Your business listing submission received - HAIPULSE';
                    $userBody = 'Hello ' . (string)$formData['contact_person'] . ",\n\n";
                    $userBody .= 'Thank you for submitting your business listing to HAIPULSE.' . "\n";
                    $userBody .= 'Our team will review it and publish once approved.' . "\n\n";
                    $userBody .= 'Company Name: ' . (string)$formData['company_name'] . "\n";
                    $userBody .= 'Submission ID: #' . (int)$companyId . "\n";
                    if ($listingUrl !== '') {
                        $userBody .= 'Listing Link: ' . $listingUrl . "\n";
                    }
                    $userBody .= "\nRegards,\nHAIPULSE Team";

                    $userHeaders = [
                        'Reply-To' => $supportEmail,
                        'from_name' => 'HAIPULSE Team'
                    ];

                    $userSent = $mailer->send((string)$formData['email'], $userSubject, nl2br($userBody), $userHeaders);
                    if (!$userSent) {
                        error_log('Add business confirmation email send failed for company #' . (int)$companyId . ': ' . (string)$mailer->getLastError());
                        require_once __DIR__ . '/../classes/EmailQueue.php';
                        (new EmailQueue($conn))->enqueue((string)$formData['email'], $userSubject, nl2br($userBody), $userHeaders);
                    }
                }

                // Clear form data
                $formData = [];
              } else {
                $executeError = 'add-business execute failed: ' . $stmt->error . ' | query=' . $insertQuery;
                error_log($executeError);
                if (isset($GLOBALS['frontendLogger'])) {
                    $GLOBALS['frontendLogger']->error($executeError, ['module' => 'add-business'], __FILE__, __LINE__);
                }
                $errors[] = 'An error occurred while submitting your listing. Please try again. Error: ' . $conn->error;
              }

              $stmt->close();
            } elseif (empty($errors)) {
              $errors[] = 'Submission is temporarily unavailable. Please try again in a moment.';
            }
        }
        }
            }
          } catch (Throwable $e) {
            $fatalMessage = 'add-business submission fatal: ' . $e->getMessage();
            error_log($fatalMessage);
            if (isset($GLOBALS['frontendLogger'])) {
              $GLOBALS['frontendLogger']->error($fatalMessage, ['module' => 'add-business'], __FILE__, __LINE__);
            }
            $errors[] = 'Submission is temporarily unavailable. Please try again in a moment.';
    }
}

$pageTitle = 'Add Business Listing - UAE Business Directory';
$pageDescription = 'Submit your business to UAE Business Directory and reach thousands of potential customers.';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main id="main-content" class="section">
    <div class="container-narrow">
      <div class="section-head">
        <h1 class="addbiz-title">Add Your Business Listing</h1>
        <span class="muted">Fill out the form below to submit your business for review</span>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
          <strong>Please correct the following errors:</strong>
          <ul class="addbiz-error-list">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="alert alert-success" role="alert">
          <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <p class="addbiz-success-actions">
          <a href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui">Browse Listings</a>
          <?php if ($isLoggedIn): ?>
            <a href="<?php echo htmlspecialchars(url('/account/profile'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui">Go to My Account</a>
          <?php endif; ?>
        </p>
      <?php else: ?>
        <form class="card-ui form-box" method="post" action="" novalidate>
          <?php echo csrf_field_frontend(); ?>
          <input type="text" name="fax_number" class="reset-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">
          
          <div class="grid-3">
            <div>
              <label for="company_name">Business Name *</label>
              <input 
                class="field" 
                id="company_name" 
                name="company_name" 
                value="<?php echo htmlspecialchars($formData['company_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                autocomplete="organization" 
                required>
            </div>
            
            <div>
              <label for="category_id">Primary Category *</label>
              <select class="select" id="category_id" name="category_id" required>
                <option value="">Select category</option>
                <?php foreach ($categories as $cat): ?>
                  <option 
                    value="<?php echo $cat['id']; ?>"
                    <?php echo (isset($formData['category_id']) && $formData['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div>
              <label for="license_number">Trade License No.</label>
              <input 
                class="field" 
                id="license_number" 
                name="license_number"
                value="<?php echo htmlspecialchars($formData['license_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            
            <div>
              <label for="contact_person">Owner/Manager Name</label>
              <input 
                class="field" 
                id="contact_person" 
                name="contact_person"
                value="<?php echo htmlspecialchars($formData['contact_person'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                autocomplete="name">
            </div>
            
            <div>
              <label for="email">Business Email *</label>
              <input 
                class="field" 
                id="email" 
                name="email" 
                type="email"
                value="<?php echo htmlspecialchars($formData['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                autocomplete="email" 
                required>
            </div>
            
            <div>
              <label for="phone">Phone Number *</label>
              <input 
                class="field" 
                id="phone" 
                name="phone" 
                type="tel"
                value="<?php echo htmlspecialchars($formData['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                inputmode="tel" 
                autocomplete="tel" 
                placeholder="+971..." 
                required>
            </div>
            
            <div>
              <label for="mobile">Mobile Number</label>
              <input 
                class="field" 
                id="mobile" 
                name="mobile" 
                type="tel"
                value="<?php echo htmlspecialchars($formData['mobile'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                inputmode="tel" 
                placeholder="+971...">
            </div>
            
            <div>
              <label for="emirate">Emirate *</label>
              <select class="select" id="emirate" name="emirate" required>
                <option value="">Select emirate</option>
                <option value="dubai" <?php echo (isset($formData['emirate']) && $formData['emirate'] === 'dubai') ? 'selected' : ''; ?>>Dubai</option>
                <option value="abu-dhabi" <?php echo (isset($formData['emirate']) && $formData['emirate'] === 'abu-dhabi') ? 'selected' : ''; ?>>Abu Dhabi</option>
                <option value="sharjah" <?php echo (isset($formData['emirate']) && $formData['emirate'] === 'sharjah') ? 'selected' : ''; ?>>Sharjah</option>
                <option value="ajman" <?php echo (isset($formData['emirate']) && $formData['emirate'] === 'ajman') ? 'selected' : ''; ?>>Ajman</option>
                <option value="ras-al-khaimah" <?php echo (isset($formData['emirate']) && $formData['emirate'] === 'ras-al-khaimah') ? 'selected' : ''; ?>>Ras Al Khaimah</option>
                <option value="fujairah" <?php echo (isset($formData['emirate']) && $formData['emirate'] === 'fujairah') ? 'selected' : ''; ?>>Fujairah</option>
                <option value="umm-al-quwain" <?php echo (isset($formData['emirate']) && $formData['emirate'] === 'umm-al-quwain') ? 'selected' : ''; ?>>Umm Al Quwain</option>
              </select>
            </div>
            
            <div>
              <label for="city">City/Area</label>
              <input 
                class="field" 
                id="city" 
                name="city"
                value="<?php echo htmlspecialchars($formData['city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="Business district or locality">
            </div>
          </div>

          <div class="addbiz-gap-14">
            <label for="address">Full Address</label>
            <input 
              class="field" 
              id="address" 
              name="address"
              value="<?php echo htmlspecialchars($formData['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              placeholder="Street address, building, unit number">
          </div>

          <div class="addbiz-gap-14">
            <label for="description">Business Description</label>
            <textarea 
              class="textarea" 
              id="description" 
              name="description" 
              rows="5" 
              placeholder="Tell customers about your services, strengths, and specializations..."><?php echo htmlspecialchars($formData['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>

          <div class="grid-3 addbiz-gap-14">
            <div>
              <label for="keywords">Service Keywords</label>
              <input 
                class="field" 
                id="keywords" 
                name="keywords"
                value="<?php echo htmlspecialchars($formData['keywords'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="e.g. delivery, emergency, 24/7">
            </div>
            
            <div>
              <label for="operating_hours">Operating Hours</label>
              <input 
                class="field" 
                id="operating_hours" 
                name="operating_hours"
                value="<?php echo htmlspecialchars($formData['operating_hours'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="Mon-Fri 9:00 AM - 9:00 PM">
            </div>
            
            <div>
              <label for="website">Website URL</label>
              <input 
                class="field" 
                id="website" 
                name="website" 
                type="url"
                value="<?php echo htmlspecialchars($formData['website'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                inputmode="url" 
                autocomplete="url" 
                placeholder="https://example.ae">
            </div>
            
            <div>
              <label for="lat">Latitude (for map)</label>
              <input 
                class="field" 
                id="lat" 
                name="lat" 
                type="number" 
                step="any"
                value="<?php echo htmlspecialchars($formData['lat'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="25.2048">
            </div>
            
            <div>
              <label for="lng">Longitude (for map)</label>
              <input 
                class="field" 
                id="lng" 
                name="lng" 
                type="number" 
                step="any"
                value="<?php echo htmlspecialchars($formData['lng'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="55.2708">
            </div>
          </div>

          <div class="addbiz-location-help" aria-label="Latitude and longitude help">
            <p class="addbiz-location-help-title">How to get latitude and longitude:</p>
            <ol class="addbiz-location-help-list">
              <li>Open <a href="https://www.google.com/maps" target="_blank" rel="noopener noreferrer">Google Maps</a> and search for your exact business location.</li>
              <li>Right-click the exact spot on the map. Google Maps will show coordinates like <strong>25.2048, 55.2708</strong>.</li>
              <li>Copy the first number into Latitude and the second number into Longitude.</li>
            </ol>
            <p class="addbiz-location-help-links">
              You can also use <a href="https://www.latlong.net/" target="_blank" rel="noopener noreferrer">LatLong.net</a> if you prefer a dedicated coordinate finder.
            </p>
          </div>

          <div class="addbiz-guidelines">
            <p class="addbiz-guidelines-title">Submission Guidelines:</p>
            <ul class="addbiz-guidelines-list">
              <li>All listings are reviewed before publishing (typically within 24-48 hours)</li>
              <li>Ensure all information is accurate and up-to-date</li>
              <li>Trade license verification may be required for verification badge</li>
              <li>Listings must comply with our <a href="<?php echo htmlspecialchars(url('/terms-of-use'), ENT_QUOTES, 'UTF-8'); ?>">Terms of Service</a></li>
            </ul>
          </div>

          <label for="add_business_captcha">Security code</label>
          <p class="muted">Enter the characters shown below.</p>
          <div class="mb-2">
            <img
              id="add-business-captcha-image"
              src="<?php echo htmlspecialchars(url('/api/captcha.php?context=add_business_form&refresh=1'), ENT_QUOTES, 'UTF-8'); ?>"
              alt="Captcha code"
              width="180"
              height="56">
          </div>
          <div class="mb-2">
            <button class="btn btn-light btn-sm" type="button" id="add-business-refresh-captcha">Refresh code</button>
          </div>
          <input class="field" id="add_business_captcha" name="add_business_captcha" type="text" required
                 placeholder="Enter security code"
                 inputmode="text"
                 autocapitalize="characters"
                 autocomplete="off"
                 maxlength="7">

          <div class="addbiz-actions">
            <button class="btn-ui btn-primary-ui" type="submit">Submit Listing</button>
            <a class="btn-ui btn-light-ui" href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>">Cancel</a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </main>

<script>
function refreshAddBusinessCaptcha() {
  const image = document.getElementById('add-business-captcha-image');
  if (!image) {
    return;
  }
  const baseUrl = '<?php echo htmlspecialchars(url('/api/captcha.php?context=add_business_form&refresh=1'), ENT_QUOTES, 'UTF-8'); ?>';
  image.src = baseUrl + '&_=' + Date.now();
}

document.addEventListener('DOMContentLoaded', function() {
  const refreshButton = document.getElementById('add-business-refresh-captcha');
  if (refreshButton) {
    refreshButton.addEventListener('click', refreshAddBusinessCaptcha);
  }
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>


