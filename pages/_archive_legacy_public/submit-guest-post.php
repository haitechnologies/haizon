<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/InputValidator.php';
require_once __DIR__ . '/../classes/RateLimiter.php';
require_once __DIR__ . '/../classes/SimpleCaptcha.php';
require_once __DIR__ . '/../classes/DisposableEmailValidator.php';
require_once __DIR__ . '/../classes/EmailQueue.php';
require_once __DIR__ . '/../includes/helpers.php';

RateLimiter::init($conn);
startFrontendSession();
SimpleCaptcha::ensureChallenge('guest_post_form');

$isLoggedIn = isFrontendUserLoggedIn();
$userId = getFrontendUserId();
$errors = [];
$success = '';
$minWords = 300;
$maxWords = 3000;
$formData = [
    'guest_author_name' => '',
    'guest_author_email' => '',
    'guest_author_bio' => '',
    'title' => '',
    'excerpt' => '',
    'content' => '',
    'category_id' => ''
];

$categories = [];
$categoriesResult = $conn->query("SELECT id, name FROM `" . DB::BLOG_CATEGORIES . "` ORDER BY name ASC");
if ($categoriesResult) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

if ($isLoggedIn && $userId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $prefillStmt = $conn->prepare("SELECT full_name, email FROM `" . DB::FRONTEND_USERS . "` WHERE id = ? LIMIT 1");
    if ($prefillStmt) {
        $prefillStmt->bind_param('i', $userId);
        $prefillStmt->execute();
        $prefillResult = $prefillStmt->get_result();
        $prefillUser = $prefillResult ? $prefillResult->fetch_assoc() : null;
        $prefillStmt->close();
        if (is_array($prefillUser)) {
            $formData['guest_author_name'] = trim((string)($prefillUser['full_name'] ?? ''));
            $formData['guest_author_email'] = trim((string)($prefillUser['email'] ?? ''));
        }
    }
}

$containsSuspiciousPayload = static function (string $value): bool {
    $patterns = [
        '/<\s*script\b/i',
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
        '/data\s*:\s*text\/html/i',
        '/onerror\s*=/i',
        '/onload\s*=/i',
        '/<\s*iframe\b/i',
        '/<\s*object\b/i',
        '/<\s*embed\b/i',
        '/eval\s*\(/i',
        '/base64_decode\s*\(/i'
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $value)) {
            return true;
        }
    }

    return false;
};

  $resolveAdminNotificationRecipient = static function () use ($conn): string {
    $settingCandidates = ['contact_email', 'support_email', 'admin_email', 'company_email'];
    foreach ($settingCandidates as $settingKey) {
      $candidate = trim((string)getSystemSetting($settingKey, ''));
      if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
        return $candidate;
      }
    }

    $adminStmt = $conn->prepare("SELECT email FROM `" . DB::USERS . "` WHERE email <> '' ORDER BY id ASC LIMIT 1");
    if ($adminStmt) {
      $adminStmt->execute();
      $adminResult = $adminStmt->get_result();
      $adminRow = $adminResult ? $adminResult->fetch_assoc() : null;
      $adminStmt->close();
      $candidate = trim((string)($adminRow['email'] ?? ''));
      if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
        return $candidate;
      }
    }

    return '';
  };

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientIP = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (strpos($clientIP, ',') !== false) {
        $clientIP = trim(explode(',', $clientIP)[0]);
    }

    $formData = [
        'guest_author_name' => trim((string)($_POST['guest_author_name'] ?? '')),
        'guest_author_email' => trim((string)($_POST['guest_author_email'] ?? '')),
        'guest_author_bio' => trim((string)($_POST['guest_author_bio'] ?? '')),
        'title' => trim((string)($_POST['title'] ?? '')),
        'excerpt' => trim((string)($_POST['excerpt'] ?? '')),
        'content' => trim((string)($_POST['content'] ?? '')),
        'category_id' => (int)($_POST['category_id'] ?? 0)
    ];

    if (!validate_csrf_token_frontend($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    } elseif (!empty($_POST['website_url'] ?? '')) {
        $errors[] = 'Spam detected. Submission rejected.';
    } elseif (!SimpleCaptcha::validate('guest_post_form', (string)($_POST['guest_post_captcha'] ?? ''))) {
        $errors[] = 'Security code is incorrect or expired. Please try again.';
    } else {
        $rateLimit = RateLimiter::check($clientIP, 'guest_post_form', 3, 3600);
        if (!$rateLimit['allowed']) {
            $errors[] = 'Too many submissions. Please try again later.';
        }
    }

    $titleValidation = InputValidator::string($formData['title'], 200, 10, false);
    $nameValidation = InputValidator::string($formData['guest_author_name'], 100, 2, false);
    $bioValidation = InputValidator::string($formData['guest_author_bio'], 500, 0, true);
    $excerptValidation = InputValidator::string($formData['excerpt'], 300, 0, true);
    $emailValidation = InputValidator::email($formData['guest_author_email']);

    if (!$nameValidation['valid']) {
        $errors[] = 'Author name must be between 2 and 100 characters.';
    }

    if (!$titleValidation['valid']) {
        $errors[] = 'Title must be between 10 and 200 characters.';
    }

    if (!$emailValidation['valid']) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        try {
            $emailValidator = new DisposableEmailValidator(null, null, $conn);
            [$isAllowedEmail, $emailMessage] = $emailValidator->validate($emailValidation['value']);
            if (!$isAllowedEmail) {
                $errors[] = $emailMessage;
            }
        } catch (Exception $e) {
            error_log('Guest post disposable email validation error: ' . $e->getMessage());
        }
    }

    if ($formData['category_id'] <= 0) {
        $errors[] = 'Please select a blog category.';
    }

    if ($containsSuspiciousPayload($formData['content'])) {
        $errors[] = 'Your content contains disallowed code or embedded payloads.';
    }

    $sanitizedContent = InputValidator::html($formData['content'], []);
    if (!$sanitizedContent['valid']) {
        $errors[] = 'Unable to process content.';
    }

    $cleanContent = trim((string)($sanitizedContent['value'] ?? ''));
    $wordCount = str_word_count($cleanContent);
    if ($wordCount < $minWords) {
      $errors[] = 'Guest posts must contain at least ' . $minWords . ' words.';
    }

    if ($wordCount > $maxWords) {
      $errors[] = 'Guest posts cannot exceed ' . $maxWords . ' words.';
    }

    if (!$bioValidation['valid']) {
        $errors[] = 'Author bio cannot exceed 500 characters.';
    }

    if (!$excerptValidation['valid']) {
        $errors[] = 'Excerpt cannot exceed 300 characters.';
    }

    if (empty($errors)) {
        $baseSlug = strtolower(trim((string)preg_replace('/[^A-Za-z0-9-]+/', '-', $formData['title']), '-'));
        $slug = $baseSlug !== '' ? $baseSlug : 'guest-post';
        $counter = 2;

        $slugCheckStmt = $conn->prepare("SELECT id FROM `" . DB::BLOGS . "` WHERE slug = ? LIMIT 1");
        if ($slugCheckStmt) {
            while (true) {
                $slugCheckStmt->bind_param('s', $slug);
                $slugCheckStmt->execute();
                $slugResult = $slugCheckStmt->get_result();
                if (!$slugResult || $slugResult->num_rows === 0) {
                    break;
                }
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $slugCheckStmt->close();
        }

        $insertStmt = $conn->prepare(
            "INSERT INTO `" . DB::BLOGS . "`
          (title, slug, content, excerpt, category_id, permalink, source, submission_status, submitted_by, guest_author_name, guest_author_bio, guest_author_email, word_count, is_homepage, is_active, publish, created_at, updated_at)
          VALUES (?, ?, ?, ?, ?, ?, 'guest', 'pending', NULLIF(?, 0), ?, ?, ?, ?, 0, 0, 0, NOW(), NOW())"
        );

        if ($insertStmt) {
            $permalink = '/blog/' . $slug;
          $submittedBy = $userId > 0 ? $userId : 0;
            $insertStmt->bind_param(
              'ssssisisssi',
                $formData['title'],
                $slug,
                $cleanContent,
                $formData['excerpt'],
                $formData['category_id'],
                $permalink,
                $submittedBy,
                $formData['guest_author_name'],
                $formData['guest_author_bio'],
            $formData['guest_author_email'],
            $wordCount
            );

            if ($insertStmt->execute()) {
                try {
                    $queue = new EmailQueue($conn);
                    $subject = 'Guest post received - HAIPULSE';
                    $body = "Hello " . $formData['guest_author_name'] . ",\n\nThank you for submitting your guest post titled \"" . $formData['title'] . "\". Our editorial team will review it before publishing.\n\nRegards,\nHAIPULSE";
                    $queue->enqueue($formData['guest_author_email'], $subject, $body, [
                        'From' => 'noreply@haipulse.com',
                        'Content-Type' => 'text/plain; charset=UTF-8',
                        'guest_post_submission' => true
                    ], 2);

                    $adminNotificationEmail = $resolveAdminNotificationRecipient();
                    if ($adminNotificationEmail !== '') {
                      $adminBody = "New guest post submission received.\n\nTitle: " . $formData['title']
                        . "\nAuthor: " . $formData['guest_author_name']
                        . "\nEmail: " . $formData['guest_author_email']
                        . "\nWords: " . $wordCount
                        . "\n\nReview it in Admin > Guest Posts.";
                      $queue->enqueue($adminNotificationEmail, 'New guest post submission - HAIPULSE', $adminBody, [
                        'From' => 'noreply@haipulse.com',
                        'Content-Type' => 'text/plain; charset=UTF-8',
                        'guest_post_admin_notification' => true
                      ], 1);
                    }
                } catch (Throwable $e) {
                    error_log('Guest post confirmation queue error: ' . $e->getMessage());
                }

                RateLimiter::recordAttempt($clientIP, 'guest_post_form');
                $success = 'Your guest post has been submitted successfully and is now pending admin review.';
                $formData['title'] = '';
                $formData['excerpt'] = '';
                $formData['content'] = '';
                $formData['guest_author_bio'] = '';
                if (!$isLoggedIn) {
                    $formData['guest_author_name'] = '';
                    $formData['guest_author_email'] = '';
                }
            } else {
                $errors[] = 'Unable to save your submission right now. Please try again.';
            }

            $insertStmt->close();
        } else {
            $errors[] = 'Unable to prepare your submission right now. Please try again later.';
        }
    }
}

$pageTitle = 'Submit a Guest Post - HAIPULSE';
$pageDescription = 'Submit a guest blog post for editorial review on HAIPULSE.';
$bodyClass = 'page-guest-post-submit';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

<main id="main-content" class="section">
  <div class="container-narrow">
    <section class="guest-post-hero card-ui mb-4">
      <div class="guest-post-hero__eyebrow">Guest Blogging</div>
      <div class="guest-post-hero__grid">
        <div>
          <h1 class="guest-post-hero__title">Write for HAIPULSE</h1>
          <p class="guest-post-hero__copy muted">Submit original, useful UAE business or trade content for editorial review. We accept content only, block disposable emails, and screen submissions before anything goes live.</p>
        </div>
        <div class="guest-post-hero__stats">
          <div class="guest-post-stat"><strong><?php echo (int)$minWords; ?></strong><span>Minimum words</span></div>
          <div class="guest-post-stat"><strong><?php echo (int)$maxWords; ?></strong><span>Maximum words</span></div>
          <div class="guest-post-stat"><strong>0</strong><span>File uploads allowed</span></div>
        </div>
      </div>
    </section>

    <?php if (!empty($success)): ?>
      <div class="card-ui mb-4">
        <p class="mb-0"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="card-ui mb-4">
        <h3 class="h6">Please fix the following:</h3>
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="guest-post-layout">
      <aside class="guest-post-sidebar card-ui">
        <h3 class="guest-post-sidebar__title">Submission rules</h3>
        <ul class="guest-post-rules mb-0">
          <li>Use a real, monitored email address.</li>
          <li>Content must be between <?php echo (int)$minWords; ?> and <?php echo (int)$maxWords; ?> words.</li>
          <li>No HTML, scripts, embeds, attachments, or file uploads.</li>
          <li>All guest posts are manually reviewed by the admin team.</li>
          <li>Rejected submissions include editorial feedback when available.</li>
        </ul>
      </aside>

      <div class="guest-post-form card-ui">
      <form method="post" action="<?php echo htmlspecialchars(url('/blog/submit'), ENT_QUOTES, 'UTF-8'); ?>" class="guest-post-form__inner">
        <?php echo csrf_field_frontend(); ?>
        <input type="text" name="website_url" value="" class="d-none" tabindex="-1" autocomplete="off">

        <div class="row g-4 guest-post-form__grid">
          <div class="col-md-6">
            <label class="form-label" for="guest_author_name">Author Name</label>
            <input class="form-control" id="guest_author_name" name="guest_author_name" type="text" maxlength="100" required value="<?php echo htmlspecialchars($formData['guest_author_name'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="guest_author_email">Author Email</label>
            <input class="form-control" id="guest_author_email" name="guest_author_email" type="email" maxlength="255" required value="<?php echo htmlspecialchars($formData['guest_author_email'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div class="col-12">
            <label class="form-label" for="title">Post Title</label>
            <input class="form-control" id="title" name="title" type="text" maxlength="200" required value="<?php echo htmlspecialchars($formData['title'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="category_id">Category</label>
            <select class="form-select" id="category_id" name="category_id" required>
              <option value="">Select category</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?php echo (int)$category['id']; ?>" <?php echo (int)$formData['category_id'] === (int)$category['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$category['name'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="excerpt">Short Excerpt</label>
            <textarea class="form-control" id="excerpt" name="excerpt" rows="3" maxlength="300"><?php echo htmlspecialchars($formData['excerpt'], ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label" for="guest_author_bio">Author Bio</label>
            <textarea class="form-control" id="guest_author_bio" name="guest_author_bio" rows="3" maxlength="500"><?php echo htmlspecialchars($formData['guest_author_bio'], ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label" for="content">Post Content</label>
            <textarea class="form-control" id="content" name="content" rows="16" required><?php echo htmlspecialchars($formData['content'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            <div class="guest-post-form__meta">
              <div class="form-text">Paste plain content only. HTML, scripts, embeds, attachments, and uploads are not accepted.</div>
              <div class="guest-post-wordcount" id="guest-post-wordcount">0 / <?php echo (int)$maxWords; ?> words</div>
            </div>
          </div>
          <div class="col-12 guest-post-captcha-block">
            <label class="form-label" for="guest_post_captcha">Security code</label>
            <div class="mb-2">
              <img id="guest-post-captcha-image" src="<?php echo htmlspecialchars(url('/api/captcha.php?context=guest_post_form&refresh=1'), ENT_QUOTES, 'UTF-8'); ?>" alt="Captcha code" width="180" height="56">
            </div>
            <div class="mb-2">
              <button class="btn btn-light btn-sm" type="button" id="guest-post-refresh-captcha">Refresh code</button>
            </div>
            <input class="form-control" id="guest_post_captcha" name="guest_post_captcha" type="text" required maxlength="7" autocomplete="off" autocapitalize="characters">
          </div>
          <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn-ui btn-primary-ui" type="submit">Submit for Review</button>
            <a class="btn-ui btn-light-ui" href="<?php echo htmlspecialchars(url('/blog'), ENT_QUOTES, 'UTF-8'); ?>">Cancel</a>
          </div>
        </div>
      </form>
      </div>
    </div>
  </div>
</main>

<script>
function refreshGuestPostCaptcha() {
  const image = document.getElementById('guest-post-captcha-image');
  if (!image) {
    return;
  }

  const baseUrl = '<?php echo htmlspecialchars(url('/api/captcha.php?context=guest_post_form&refresh=1'), ENT_QUOTES, 'UTF-8'); ?>';
  image.src = baseUrl + '&_=' + Date.now();
}

document.addEventListener('DOMContentLoaded', function() {
  const refreshButton = document.getElementById('guest-post-refresh-captcha');
  const contentField = document.getElementById('content');
  const wordCountEl = document.getElementById('guest-post-wordcount');
  if (refreshButton) {
    refreshButton.addEventListener('click', refreshGuestPostCaptcha);
  }

  function updateGuestPostWordCount() {
    if (!contentField || !wordCountEl) {
      return;
    }

    const words = contentField.value.trim().split(/\s+/).filter(Boolean);
    const count = words.length;
    wordCountEl.textContent = count + ' / <?php echo (int)$maxWords; ?> words';
    wordCountEl.classList.toggle('is-over', count > <?php echo (int)$maxWords; ?>);
  }

  if (contentField) {
    contentField.addEventListener('input', updateGuestPostWordCount);
    updateGuestPostWordCount();
  }
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>


