<?php
/**
 * Page: User Registration (NEW DESIGN)
 * Route: /register
 * Description: Frontend user account creation
 * Author: Development Team
 * Created: February 28, 2026
 */

// ============================================
// SECTION 1: SESSION & DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/session.php';
startFrontendSession();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/InputValidator.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/RateLimiter.php';
require_once __DIR__ . '/../classes/SimpleCaptcha.php';
require_once __DIR__ . '/../classes/DisposableEmailValidator.php';
require_once __DIR__ . '/../classes/SMTPMailer.php';
require_once __DIR__ . '/../classes/EmailProviderManager.php';
require_once __DIR__ . '/../includes/helpers.php';

// SMTPMailer resolves provider config through $GLOBALS['conn'].
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof mysqli)) {
    $GLOBALS['conn'] = $conn;
}

RateLimiter::init($conn);
SimpleCaptcha::ensureChallenge('register_form');

// ============================================
// SECTION 2: CHECK IF ALREADY LOGGED IN
// ============================================
if (isset($_SESSION['frontend_user_id']) && !empty($_SESSION['frontend_user_id'])) {
  header('Location: ' . url('/account/profile'));
    exit;
}

// Check if just registered
$showVerificationMessage = false;
$verificationEmail = '';
if (isset($_SESSION['registration_success']) && isset($_SESSION['registration_email'])) {
    $showVerificationMessage = true;
    $verificationEmail = $_SESSION['registration_email'];
    // Don't unset yet - keep for page display
}

// ============================================
// SECTION 3: FORM HANDLING
// ============================================
$errors = [];
$success = '';
$formData = [
    'full_name' => '',
    'email' => '',
    'phone_number' => '',
    'emirate' => '',
    'accept_terms' => false
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract client IP (handling CloudFlare headers)
    $clientIP = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (strpos($clientIP, ',') !== false) {
        $clientIP = trim(explode(',', $clientIP)[0]);
    }

    // Validate CSRF token
    if (!validate_csrf_token_frontend($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security validation failed. Please try again.';
    } elseif (!empty($_POST['website'] ?? '')) {
        // Check for spam (honeypot field)
        $errors[] = 'Spam detected. Submission rejected.';
    } elseif (!SimpleCaptcha::validate('register_form', (string)($_POST['register_captcha'] ?? ''))) {
      $errors[] = 'Security code is incorrect or expired. Please try again.';
    } else {
        // Check rate limit
        $rateLimit = RateLimiter::check($clientIP, 'register_form', 5, 3600);
        if (!$rateLimit['allowed']) {
            $errors[] = 'Too many registration attempts. Please try again later.';
        } else {
            // Extract and validate form inputs
            $fullName = InputValidator::string($_POST['full_name'] ?? '', 100, 2);
            $email = InputValidator::email($_POST['email'] ?? '');
            $phone = InputValidator::string($_POST['phone_number'] ?? '', 20, 8);
            $emirate = InputValidator::string($_POST['emirate'] ?? '', 50, 2);
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $acceptTerms = isset($_POST['accept_terms']) && $_POST['accept_terms'] == '1';

            // Store form data for repopulation (except passwords)
            $formData = [
                'full_name' => $_POST['full_name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone_number' => $_POST['phone_number'] ?? '',
                'emirate' => $_POST['emirate'] ?? '',
                'accept_terms' => $acceptTerms
            ];

            // Validate full name
            if (!$fullName['valid']) {
                $errors[] = 'Full name must be between 2 and 100 characters.';
            }

            // Validate email
            if (!$email['valid']) {
                $errors[] = 'Please enter a valid email address.';
            } else {
                // Check for disposable email
                try {
                    $emailValidator = new DisposableEmailValidator(null, null, $conn);
                    list($isValidDisposableCheck, $disposableMessage) = $emailValidator->validate($email['value']);
                    if (!$isValidDisposableCheck) {
                        $errors[] = $disposableMessage;
                    }
                } catch (Exception $e) {
                    error_log('Disposable email validation error (register): ' . $e->getMessage());
                }
            }

            // Validate phone number
            if (!$phone['valid']) {
                $errors[] = 'Please enter a valid phone number (8-20 characters).';
            }

            // Validate emirate
            if (empty($emirate['value'])) {
                $errors[] = 'Please select your city/emirate.';
            }

            // Validate password length
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters long.';
            }

            // Validate password match
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }

            // Validate password strength
            if (!empty($password) && strlen($password) >= 8) {
                $hasLetter = preg_match('/[a-zA-Z]/', $password);
                $hasNumber = preg_match('/[0-9]/', $password);

                if (!$hasLetter || !$hasNumber) {
                    $errors[] = 'Password must contain at least one letter and one number.';
                }
            }

            // Validate terms acceptance
            if (!$acceptTerms) {
                $errors[] = 'You must accept the terms and privacy policy.';
            }

            // If all validation passed, create the account
            if (empty($errors)) {
                try {
                    // Check if email already exists
                    $stmt = $conn->prepare("
                        SELECT id FROM `" . DB::FRONTEND_USERS . "` 
                        WHERE email = ? 
                        LIMIT 1
                    ");
                    $stmt->bind_param('s', $email['value']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $existingUser = $result->fetch_assoc();
                    $stmt->close();

                    if ($existingUser) {
                        $errors[] = 'An account with this email address already exists. <a href="' . htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8') . '">Sign in instead</a>?';
                    } else {
                        // Hash password
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                        // Generate email verification token
                        $verificationToken = bin2hex(random_bytes(32));

                        // Insert new user
                        $stmt = $conn->prepare("
                            INSERT INTO `" . DB::FRONTEND_USERS . "` 
                            (full_name, email, mobile, password, 
                             email_verification_token, is_active, email_verified, 
                             created_at) 
                            VALUES (?, ?, ?, ?, ?, 1, 0, NOW())
                        ");

                        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                        $stmt->bind_param(
                            'sssss',
                            $fullName['value'],
                            $email['value'],
                            $phone['value'],
                            $passwordHash,
                            $verificationToken
                        );

                        if ($stmt->execute()) {
                            $userId = $stmt->insert_id;
                            $stmt->close();

                            // Send verification email immediately via SMTP
                            $verificationLink = getFullUrl('/verify-email') . "?token=" . $verificationToken;

                            $to = $email['value'];
                            $subject = 'Verify Your Email - UAE Business Directory';
                            $emailBody = "Hello " . htmlspecialchars($fullName['value']) . ",\n\n";
                            $emailBody .= "Thank you for registering with UAE Business Directory!\n\n";
                            $emailBody .= "Please verify your email address by clicking the link below:\n";
                            $emailBody .= $verificationLink . "\n\n";
                            $emailBody .= "This link will expire in 24 hours.\n\n";
                            $emailBody .= "If you didn't create this account, please ignore this email.\n\n";
                            $emailBody .= "Best regards,\n";
                            $emailBody .= "UAE Business Directory Team\n";

                            $headers = [
                              'Reply-To' => 'support@haipulse.com',
                                'X-Mailer' => 'PHP/' . phpversion(),
                                'Content-Type' => 'text/plain; charset=UTF-8'
                            ];


                            // Send immediately via SMTP; fall back to queue if it fails.
                            $mailer = new SMTPMailer();
                            $sent = $mailer->send($to, $subject, nl2br($emailBody), $headers);
                            if (!$sent) {
                                error_log('Registration email send failed: ' . (string)$mailer->getLastError());
                                require_once __DIR__ . '/../classes/EmailQueue.php';
                                (new EmailQueue($conn))->enqueue($to, $subject, nl2br($emailBody), $headers);
                            }

                            // Log registration activity
                            // NOTE: authentication_activity enum doesn't support 'frontend_registration'
                            // Only supports: login_success, login_failed, account_locked, ip_blocked
                            // TODO: Create separate registration_activity table or update enum
                            // $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                            // $stmt = $conn->prepare("
                            //     INSERT INTO `" . DB::AUTHENTICATION_ACTIVITY . "` 
                            //     (user_id, activity_type, ip_address, user_agent, created_at) 
                            //     VALUES (?, 'frontend_registration', ?, ?, NOW())
                            // ");
                            // $stmt->bind_param('iss', $userId, $ipAddress, $userAgent);
                            // $stmt->execute();
                            // $stmt->close();

                            // Record registration attempt - DON'T REDIRECT, DISPLAY MESSAGE HERE
                            RateLimiter::recordAttempt($clientIP, 'register_form');
                            $_SESSION['registration_success'] = 'Account created successfully! Check your email to verify.';
                            $_SESSION['registration_email'] = $email['value'];
                            
                            // Set flag to display verification message
                            $showVerificationMessage = true;
                            $verificationEmail = $email['value'];
                            // Clear form data and errors
                            $errors = [];
                            $formData = ['full_name' => '', 'email' => '', 'phone_number' => '', 'emirate' => '', 'accept_terms' => false];
                            // Don't exit - let the page continue to template display
                        } else {
                            $errors[] = 'Failed to create account. Please try again.';
                            error_log('Registration error: ' . $stmt->error);
                            $stmt->close();
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = 'An unexpected error occurred. Please try again later.';
                    error_log('Registration exception: ' . $e->getMessage());
                }
            }
        }
    }
}

// ============================================
// SECTION 4: CSRF TOKEN
// ============================================
$csrfToken = csrf_field_frontend();

// Emirate options
$emirateOptions = [
    '' => 'Select city',
    'dubai' => 'Dubai',
    'abu-dhabi' => 'Abu Dhabi',
    'sharjah' => 'Sharjah',
    'ajman' => 'Ajman',
    'ras-al-khaimah' => 'Ras Al Khaimah',
    'fujairah' => 'Fujairah',
    'umm-al-quwain' => 'Umm Al Quwain'
];
$pageTitle = 'UAE Business Directory | Register';
$pageDescription = 'Create owner account for UAE business directory.';
$bodyClass = 'page-register';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main id="main-content" class="section">
    <div class="container-narrow register-shell">
      
      <!-- VERIFICATION PENDING MESSAGE -->
      <?php if ($showVerificationMessage): 
        $regEmail = $verificationEmail;
      ?>
      
        <div class="card-ui register-verify-card">
          <!-- Success icon -->
          <div class="register-verify-icon">âœ‰ï¸</div>
          
          <h1 class="register-verify-title">Verification Email Sent</h1>
          
          <p class="register-verify-lead">
            We've sent a verification link to:
          </p>
          
          <div class="register-verify-email">
            <?php echo htmlspecialchars($regEmail, ENT_QUOTES, 'UTF-8'); ?>
          </div>
          
          <div class="register-verify-steps">
            <strong>ðŸ“‹ Next Steps:</strong>
            <ol class="register-verify-steps-list">
              <li>Check your inbox for an email from <strong>noreply@haipulse.com</strong></li>
              <li>Click the verification link in the email</li>
              <li>Your account will be activated immediately</li>
              <li>Return here to sign in</li>
            </ol>
          </div>
          
          <p class="register-verify-note">
            The verification link will expire in 24 hours.
          </p>
          
          <div class="register-verify-actions">
            <a href="<?php echo htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui register-verify-cta">
              Go to Sign In
            </a>
          </div>
          
          <hr class="register-divider">
          
          <div class="register-help-wrap">
            <p>Didn't receive the email?</p>
            <ul class="register-help-list">
              <li>âœ“ Check your spam/junk folder</li>
              <li>âœ“ Make sure you entered the correct email address</li>
            </ul>
          </div>
        </div>
      
      <?php else: ?>
      
      <!-- REGISTRATION FORM -->
      <form class="card-ui form-box" method="post" action="">
        <?php echo $csrfToken; ?>
        <input type="text" name="website" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
        <h1 class="register-title">Create account</h1>
        <p class="muted">Start listing and managing your business in UAE.</p>

        <?php if (!empty($errors)): ?>
          <div class="register-alert">
            <strong>âš  Please correct the following:</strong>
            <ul class="register-alert-list">
              <?php foreach ($errors as $error): ?>
                <li><?php echo $error; /* Allow HTML for link to login */ ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="grid-3 register-grid">
          <div>
            <label for="reg_name">Full name</label>
            <input class="field" id="reg_name" name="full_name" autocomplete="name" required
                   value="<?php echo htmlspecialchars($formData['full_name'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div>
            <label for="reg_phone">Phone</label>
            <input class="field" id="reg_phone" name="phone_number" type="tel" inputmode="tel" autocomplete="tel" placeholder="+971..." required
                   value="<?php echo htmlspecialchars($formData['phone_number'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div>
            <label for="reg_email">Email</label>
            <input class="field" id="reg_email" name="email" type="email" autocomplete="email" required
                   value="<?php echo htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div>
            <label for="reg_city">City</label>
            <select class="select" id="reg_city" name="emirate" required>
              <?php foreach ($emirateOptions as $value => $label): ?>
                <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo ($formData['emirate'] === $value) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="reg_password">Password</label>
            <input class="field" id="reg_password" name="password" type="password" autocomplete="new-password" required minlength="8">
            
            <!-- Password strength indicator -->
            <div id="pwd_strength" class="register-password-meter">
              <div class="register-meter-row">
                <span id="pwd_length" class="register-meter-dot">â­•</span>
                <span class="register-meter-text">Min 8 characters</span>
              </div>
              <div class="register-meter-row">
                <span id="pwd_letter" class="register-meter-dot">â­•</span>
                <span class="register-meter-text">At least 1 letter (A-Z)</span>
              </div>
              <div>
                <span id="pwd_number" class="register-meter-dot">â­•</span>
                <span class="register-meter-text">At least 1 number (0-9)</span>
              </div>
            </div>
          </div>
          <div>
            <label for="reg_confirm">Confirm password</label>
            <input class="field" id="reg_confirm" name="confirm_password" type="password" autocomplete="new-password" required minlength="8">
          </div>
        </div>

        <label class="option-row register-terms">
          <input type="checkbox" name="accept_terms" value="1" required <?php echo $formData['accept_terms'] ? 'checked' : ''; ?>> 
          I accept <a href="<?php echo htmlspecialchars(url('/terms-of-use'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank">terms</a> and <a href="<?php echo htmlspecialchars(url('/privacy-policy'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank">privacy policy</a>
        </label>

        <label for="register_captcha">Security code</label>
        <p class="muted">Enter the characters shown below.</p>
        <div class="mb-2">
          <img
            id="register-captcha-image"
            src="<?php echo htmlspecialchars(url('/api/captcha.php?context=register_form&refresh=1'), ENT_QUOTES, 'UTF-8'); ?>"
            alt="Captcha code"
            width="180"
            height="56">
        </div>
        <div class="mb-2">
          <button class="btn btn-light btn-sm" type="button" id="register-refresh-captcha">Refresh code</button>
        </div>
        <input class="field" id="register_captcha" name="register_captcha" type="text" required
               placeholder="Enter security code"
               inputmode="text"
               autocapitalize="characters"
               autocomplete="off"
               maxlength="7">
        
        <button class="btn-ui btn-primary-ui register-submit" type="submit">Create account</button>
        <p class="muted register-note">Already have an account? <a href="<?php echo htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>">Sign in</a></p>
      </form>
      
      <?php endif; ?>
      
    </div>
  </main>

<script>
// Real-time password strength validation
document.getElementById('reg_password').addEventListener('input', function() {
  const password = this.value;
  
  // Check requirements
  const hasMinLength = password.length >= 8;
  const hasLetter = /[a-zA-Z]/.test(password);
  const hasNumber = /[0-9]/.test(password);
  
  // Update UI indicators
  updateIndicator('pwd_length', hasMinLength);
  updateIndicator('pwd_letter', hasLetter);
  updateIndicator('pwd_number', hasNumber);
  
  // Set HTML5 validation
  if (password && (!hasMinLength || !hasLetter || !hasNumber)) {
    this.setCustomValidity('Password must be at least 8 characters with a letter and a number');
  } else {
    this.setCustomValidity('');
  }
});

// Client-side password confirmation validation
document.getElementById('reg_confirm').addEventListener('input', function() {
  const password = document.getElementById('reg_password').value;
  const confirm = this.value;
  
  if (confirm && password !== confirm) {
    this.setCustomValidity('Passwords do not match');
  } else {
    this.setCustomValidity('');
  }
});

// Helper function to update indicator
function updateIndicator(elementId, isValid) {
  const element = document.getElementById(elementId);
  element.classList.remove('pwd-indicator--ok', 'pwd-indicator--error');
  if (isValid) {
    element.textContent = 'âœ…';
    element.classList.add('pwd-indicator--ok');
  } else {
    element.textContent = 'âŒ';
    element.classList.add('pwd-indicator--error');
  }
}

function refreshRegisterCaptcha() {
  const image = document.getElementById('register-captcha-image');
  if (!image) {
    return;
  }
  const baseUrl = '<?php echo htmlspecialchars(url('/api/captcha.php?context=register_form&refresh=1'), ENT_QUOTES, 'UTF-8'); ?>';
  image.src = baseUrl + '&_=' + Date.now();
}

document.addEventListener('DOMContentLoaded', function() {
  const refreshButton = document.getElementById('register-refresh-captcha');
  if (refreshButton) {
    refreshButton.addEventListener('click', refreshRegisterCaptcha);
  }
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

