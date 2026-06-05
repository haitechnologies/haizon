<?php
/**
 * Forgot Password Page
 * 
 * Allows users to request a password reset link via email.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/RateLimiter.php';
require_once __DIR__ . '/../classes/SimpleCaptcha.php';
require_once __DIR__ . '/../classes/frontend/FrontendUsers.php';

RateLimiter::init($conn);

$pageTitle = 'Forgot Password - HaiPulse';
$bodyClass = 'page-forgot-password';
$error = '';
$success = '';
$email = '';

// Start session for CSRF token validation
startFrontendSession();
SimpleCaptcha::ensureChallenge('forgot_password_form');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$clientIP = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
	if (strpos($clientIP, ',') !== false) {
		$clientIP = trim(explode(',', $clientIP)[0]);
	}

	if (!validate_csrf_token_frontend($_POST['csrf_token'] ?? '')) {
		$error = 'Security validation failed. Please refresh and try again.';
	} elseif (!empty($_POST['website'] ?? '')) {
		$error = 'Spam detected. Submission rejected.';
  } elseif (!SimpleCaptcha::validate('forgot_password_form', (string)($_POST['forgot_captcha'] ?? ''))) {
    $error = 'Security code is incorrect or expired. Please try again.';
	} else {
		$rateLimit = RateLimiter::check($clientIP, 'forgot_password_form', 5, 3600);
		if (!$rateLimit['allowed']) {
			$error = 'Too many requests. Please try again later.';
		} else {
			$email = trim($_POST['email'] ?? '');
    
    // Validate email
			if (empty($email)) {
				$error = 'Please enter your email address.';
			} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$error = 'Please enter a valid email address.';
			} else {
				$usersModel = new FrontendUsers($conn);
        
        // Check if user exists
        $user = $usersModel->getByEmail($email);
        
				if ($user) {
            // Generate reset token and send email
            $token = $usersModel->generatePasswordResetToken($email);
            
					if ($token) {
                $success = 'Password reset instructions have been sent to your email address. Please check your inbox.';
                $email = ''; // Clear email field on success
            } else {
                $error = 'An error occurred. Please try again later.';
            }
				} else {
            // Don't reveal if email exists or not (security best practice)
            $success = 'If an account exists with this email, you will receive password reset instructions shortly.';
            $email = '';
				}

				RateLimiter::recordAttempt($clientIP, 'forgot_password_form');
			}
        }
    }
}

include __DIR__ . '/../includes/layout/header.php';
?>
<main id="main-content" class="section">
  <div class="container-narrow forgot-shell">
    <form id="forgotpsd" class="card-ui form-box" method="post" action="<?php echo htmlspecialchars(url('/forgot-password'), ENT_QUOTES, 'UTF-8'); ?>">
      <?php echo csrf_field(); ?>
      <input type="text" name="website" class="reset-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">

      <h1 class="forgot-title">Forgot password</h1>
      <p class="muted">Enter your email and we will send a secure reset link.</p>

      <?php if ($error): ?>
        <div class="forgot-alert forgot-alert--error">
          <strong>Could not submit request:</strong>
          <div class="forgot-alert-detail"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="forgot-alert forgot-alert--ok">
          <strong>Request received</strong>
          <div class="forgot-alert-detail"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endif; ?>

      <label for="email">Email address</label>
      <input class="field"
             type="email"
             name="email"
             id="email"
             autocomplete="email"
             value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
             required>

      <label for="forgot_captcha">Security code</label>
      <div class="mb-2">
        <img
          id="forgot-captcha-image"
          src="<?php echo htmlspecialchars(url('/api/captcha.php?context=forgot_password_form&refresh=1'), ENT_QUOTES, 'UTF-8'); ?>"
          alt="Captcha code"
          width="180"
          height="56">
      </div>
      <div class="mb-2">
        <button class="btn btn-light btn-sm" type="button" id="forgot-refresh-captcha">Refresh code</button>
      </div>
      <input class="field" id="forgot_captcha" name="forgot_captcha" type="text" required
             placeholder="Enter security code"
             inputmode="text"
             autocapitalize="characters"
             autocomplete="off"
             maxlength="7">

      <button class="btn-ui btn-primary-ui forgot-submit" type="submit">Send reset link</button>

      <p class="muted forgot-footnote">
        Remembered it? <a href="<?php echo htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>">Back to sign in</a>
      </p>
    </form>
  </div>
</main>
<script>
function refreshForgotCaptcha() {
  const image = document.getElementById('forgot-captcha-image');
  if (!image) {
    return;
  }
  const baseUrl = '<?php echo htmlspecialchars(url('/api/captcha.php?context=forgot_password_form&refresh=1'), ENT_QUOTES, 'UTF-8'); ?>';
  image.src = baseUrl + '&_=' + Date.now();
}

document.addEventListener('DOMContentLoaded', function() {
  const refreshButton = document.getElementById('forgot-refresh-captcha');
  if (refreshButton) {
    refreshButton.addEventListener('click', refreshForgotCaptcha);
  }
});
</script>
<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

