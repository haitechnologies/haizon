<?php
/**
 * Page: Data Request
 * Route: /data-request
 * Description: Request to update or remove business data (GDPR compliance)
 */

$pageTitle = 'Data Request - UAE Business Directory';
$pageDescription = 'Request to update, access, or remove your business information';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/RateLimiter.php';
require_once __DIR__ . '/../classes/SimpleCaptcha.php';

RateLimiter::init($conn);
startFrontendSession();
SimpleCaptcha::ensureChallenge('data_request_form');

$successMessage = '';
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
  $clientIP = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  if (strpos($clientIP, ',') !== false) {
    $clientIP = trim(explode(',', $clientIP)[0]);
  }

  if (!validate_csrf_token_frontend($_POST['csrf_token'] ?? '')) {
    $errorMessage = 'Security validation failed. Please refresh and try again.';
  } elseif (!empty($_POST['website'] ?? '')) {
    $errorMessage = 'Spam detected. Submission rejected.';
  } elseif (!SimpleCaptcha::validate('data_request_form', (string)($_POST['data_request_captcha'] ?? ''))) {
    $errorMessage = 'Security code is incorrect or expired. Please try again.';
  } else {
    $rateLimit = RateLimiter::check($clientIP, 'data_request_form', 5, 3600);
    if (!$rateLimit['allowed']) {
      $errorMessage = 'Too many requests. Please try again later.';
    } else {
    $requestType = $_POST['request_type'] ?? '';
    $businessName = trim($_POST['business_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($requestType) || empty($businessName) || empty($email)) {
        $errorMessage = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } else {
      // Store request in database (table may be absent in trimmed deployments)
      $dataRequestsTable = 'erp_data_requests';
      $tableExistsResult = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($dataRequestsTable) . "'");
      if (!$tableExistsResult || $tableExistsResult->num_rows === 0) {
        $errorMessage = 'Data request service is temporarily unavailable. Please contact support directly.';
        goto data_request_done;
      }

      $stmt = $conn->prepare("INSERT INTO `" . $dataRequestsTable . "` (request_type, business_name, email, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("ssss", $requestType, $businessName, $email, $message);
            if ($stmt->execute()) {
                $successMessage = 'Your request has been submitted successfully. We will respond within 5 business days.';
              RateLimiter::recordAttempt($clientIP, 'data_request_form');
                // Clear form
                $_POST = [];
            } else {
                $errorMessage = 'Failed to submit request. Please try again.';
            }
            $stmt->close();
      } else {
        $errorMessage = 'Failed to submit request. Please try again.';
        }
    }
          }
        }
}
  data_request_done:
$pageTitle = 'Data Request - UAE Business Directory';
$pageDescription = 'Submit a data request to UAE Business Directory.';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main class="main-content">
    <div class="container-narrow datareq-shell">
      <h1 class="datareq-title">Data Request</h1>
      <p class="muted datareq-subtitle">
        We respect your privacy. Use this form to request updates, access, or removal of your business data.
      </p>

      <?php if ($successMessage): ?>
      <div class="alert alert-success datareq-alert datareq-alert--ok">
        <p><?php echo htmlspecialchars($successMessage, ENT_QUOTES); ?></p>
      </div>
      <?php endif; ?>

      <?php if ($errorMessage): ?>
      <div class="alert alert-danger datareq-alert datareq-alert--err">
        <p><?php echo htmlspecialchars($errorMessage, ENT_QUOTES); ?></p>
      </div>
      <?php endif; ?>

      <form method="POST" class="card-ui datareq-form">
        <?php echo csrf_field_frontend(); ?>
        <input type="text" name="website" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
        <div class="datareq-field">
          <label for="request_type" class="datareq-label">Request Type *</label>
          <select id="request_type" name="request_type" required class="datareq-select">
            <option value="">Select request type</option>
            <option value="update" <?php echo (isset($_POST['request_type']) && $_POST['request_type'] === 'update') ? 'selected' : ''; ?>>Update my business information</option>
            <option value="access" <?php echo (isset($_POST['request_type']) && $_POST['request_type'] === 'access') ? 'selected' : ''; ?>>Access my data</option>
            <option value="remove" <?php echo (isset($_POST['request_type']) && $_POST['request_type'] === 'remove') ? 'selected' : ''; ?>>Remove my business listing</option>
            <option value="other" <?php echo (isset($_POST['request_type']) && $_POST['request_type'] === 'other') ? 'selected' : ''; ?>>Other request</option>
          </select>
        </div>

        <div class="datareq-field">
          <label for="business_name" class="datareq-label">Business Name *</label>
          <input type="text" id="business_name" name="business_name" required 
                 value="<?php echo htmlspecialchars($_POST['business_name'] ?? '', ENT_QUOTES); ?>"
                 class="datareq-input">
        </div>

        <div class="datareq-field">
          <label for="email" class="datareq-label">Email Address *</label>
          <input type="email" id="email" name="email" required 
                 value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>"
                 class="datareq-input">
        </div>

        <div class="datareq-field">
          <label for="message" class="datareq-label">Additional Details</label>
          <textarea id="message" name="message" rows="5" 
                    class="datareq-textarea"><?php echo htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES); ?></textarea>
        </div>

        <div class="datareq-field">
          <label for="data_request_captcha" class="datareq-label">Security Code *</label>
          <div class="mb-2">
            <img
              id="data-request-captcha-image"
              src="<?php echo htmlspecialchars(url('/api/captcha.php?context=data_request_form&refresh=1'), ENT_QUOTES, 'UTF-8'); ?>"
              alt="Captcha code"
              width="180"
              height="56">
          </div>
          <div class="mb-2">
            <button class="btn btn-light btn-sm" type="button" id="data-request-refresh-captcha">Refresh code</button>
          </div>
          <input type="text" id="data_request_captcha" name="data_request_captcha" required
                 class="datareq-input"
                 placeholder="Enter security code"
                 inputmode="text"
                 autocapitalize="characters"
                 autocomplete="off"
                 maxlength="7">
        </div>

        <button type="submit" name="submit_request" class="btn-ui btn-primary-ui datareq-submit">Submit Request</button>
      </form>

      <div class="datareq-info">
        <h3>Response Timeline</h3>
        <p class="muted">We aim to respond to all data requests within 5 business days. For urgent matters, please contact us directly at <a href="mailto:privacy@haipulse.com">privacy@haipulse.com</a>.</p>
      </div>
    </div>
  </main>

<script>
function refreshDataRequestCaptcha() {
  const image = document.getElementById('data-request-captcha-image');
  if (!image) {
    return;
  }
  const baseUrl = '<?php echo htmlspecialchars(url('/api/captcha.php?context=data_request_form&refresh=1'), ENT_QUOTES, 'UTF-8'); ?>';
  image.src = baseUrl + '&_=' + Date.now();
}

document.addEventListener('DOMContentLoaded', function() {
  const refreshButton = document.getElementById('data-request-refresh-captcha');
  if (refreshButton) {
    refreshButton.addEventListener('click', refreshDataRequestCaptcha);
  }
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>

