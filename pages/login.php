<?php
/**
 * Page: User Login (NEW DESIGN)
 * Route: /login
 * Description: Frontend user authentication
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
require_once __DIR__ . '/../classes/SimpleCaptcha.php';
require_once __DIR__ . '/../classes/DisposableEmailValidator.php';
require_once __DIR__ . '/../includes/helpers.php';

// ============================================
// SECTION 2: CHECK IF ALREADY LOGGED IN
// ============================================
if (isset($_SESSION['frontend_user_id']) && !empty($_SESSION['frontend_user_id'])) {
    header('Location: ' . url('/account/profile'));
    exit;
}

// ============================================
// SECTION 3: FORM HANDLING
// ============================================
$errors = [];
$success = '';
$formData = [
    'login_email' => '',
    'remember_me' => false
];

$loginCaptchaThreshold = (int)getSystemSetting('login_captcha_threshold', '3');
if ($loginCaptchaThreshold < 1 || $loginCaptchaThreshold > 10) {
    $loginCaptchaThreshold = 3;
}
$ipAddress = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (strpos((string)$ipAddress, ',') !== false) {
    $ipAddress = trim(explode(',', (string)$ipAddress)[0]);
}

$rateLimitRow = null;
$rateStmt = $conn->prepare("\n    SELECT attempts, banned_until FROM `" . DB::RATE_LIMIT_ATTEMPTS . "`\n    WHERE identifier = ?\n    AND action = 'login'\n    LIMIT 1\n");
if ($rateStmt) {
    $rateStmt->bind_param('s', $ipAddress);
    $rateStmt->execute();
    $rateResult = $rateStmt->get_result();
    $rateLimitRow = $rateResult ? $rateResult->fetch_assoc() : null;
    $rateStmt->close();
}

$captchaRequired = ((int)($rateLimitRow['attempts'] ?? 0) >= $loginCaptchaThreshold);
if ($captchaRequired) {
    SimpleCaptcha::ensureChallenge('login_form');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validate_csrf_token_frontend($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security validation failed. Please try again.';
    } elseif (!empty($_POST['website'] ?? '')) {
        $errors[] = 'Spam detected. Submission rejected.';
    } elseif ($captchaRequired && !SimpleCaptcha::validate('login_form', (string)($_POST['login_captcha'] ?? ''))) {
        $errors[] = 'Security code is incorrect or expired. Please try again.';
    } else {
        // Validate inputs
        $email = InputValidator::email($_POST['login_email'] ?? '');
        $password = $_POST['login_password'] ?? '';
        $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] == '1';
        
        // Store form data for repopulation (except password)
        $formData = [
            'login_email' => $_POST['login_email'] ?? '',
            'remember_me' => $rememberMe
        ];
        
        // Validation
        if (!$email['valid']) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            try {
                $emailValidator = new DisposableEmailValidator(null, null, $conn);
                list($isValidDisposableCheck, $disposableMessage) = $emailValidator->validate($email['value']);
                if (!$isValidDisposableCheck) {
                    $errors[] = $disposableMessage;
                }
            } catch (Exception $e) {
                error_log('Disposable email validation error (login): ' . $e->getMessage());
            }
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }
        
        // If validation passes, attempt authentication
        if (empty($errors)) {
            try {
                if ($rateLimitRow && $rateLimitRow['banned_until'] && strtotime($rateLimitRow['banned_until']) > time()) {
                    $errors[] = 'Too many failed login attempts. Please try again in 15 minutes.';
                } else {
                    // Query user from database
                    $stmt = $conn->prepare("
                        SELECT id, email, password, full_name, is_active, email_verified, created_at 
                        FROM `" . DB::FRONTEND_USERS . "` 
                        WHERE email = ? 
                        LIMIT 1
                    ");
                    $stmt->bind_param('s', $email['value']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();
                    
                    if ($user && password_verify($password, $user['password'])) {
                        // Check if account is active
                        if ($user['is_active'] != 1) {
                            $errors[] = 'Your account has been deactivated. Please contact support.';
                        } 
                        // Check if email is verified
                        elseif ($user['email_verified'] != 1) {
                            $errors[] = 'Please verify your email address before logging in. Check your inbox for the verification link.';
                        } 
                        else {
                            // ✅ LOGIN SUCCESSFUL
                            
                            // Set session variables
                            $_SESSION['frontend_user_id'] = $user['id'];
                            $_SESSION['frontend_user_email'] = $user['email'];
                            $_SESSION['frontend_user_name'] = $user['full_name'];
                            $_SESSION['frontend_login_time'] = time();
                            
                            // Remember me functionality (30 days)
                            if ($rememberMe) {
                                // TODO: Implement remember me with session tokens
                                // Requires DB::FRONTEND_USER_SESSIONS table
                                // For now, just extend session lifetime
                                $_SESSION['remember_me'] = true;
                            }
                            
                            // Log successful login
                            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                            try {
                                $stmt = $conn->prepare("
                                    INSERT INTO `" . DB::AUTHENTICATION_ACTIVITY . "` 
                                    (user_id, activity_type, ip_address, user_agent, created_at) 
                                    VALUES (?, 'login_success', ?, ?, NOW())
                                ");
                                if ($stmt) {
                                    $stmt->bind_param('iss', $user['id'], $ipAddress, $userAgent);
                                    $stmt->execute();
                                    $stmt->close();
                                }
                            } catch (Throwable $logException) {
                                error_log('Login success activity log warning: ' . $logException->getMessage());
                            }
                            
                            // Update last login timestamp
                            try {
                                $stmt = $conn->prepare("
                                    UPDATE `" . DB::FRONTEND_USERS . "` 
                                    SET last_login_at = NOW(), last_login_ip = ? 
                                    WHERE id = ?
                                ");
                                if ($stmt) {
                                    $stmt->bind_param('si', $ipAddress, $user['id']);
                                    $stmt->execute();
                                    $stmt->close();
                                }
                            } catch (Throwable $profileUpdateException) {
                                error_log('Login profile update warning: ' . $profileUpdateException->getMessage());
                            }
                            
                            // Redirect to public account area or a safe public page only.
                            $defaultRedirect = '/account/profile';
                            $redirectUrl = $defaultRedirect;
                            $requestedRedirect = trim((string)($_GET['redirect'] ?? ''));

                            if ($requestedRedirect !== ''
                                && strpos($requestedRedirect, '://') === false
                                && strpos($requestedRedirect, '//') !== 0
                                && strpos($requestedRedirect, '/') === 0
                            ) {
                                $normalizedRedirect = strtolower(rtrim($requestedRedirect, '/'));
                                $isDashboardPath = ($normalizedRedirect === '/dashboard'
                                    || strpos($normalizedRedirect, '/dashboard/') === 0
                                    || strpos($normalizedRedirect, '/dashboard-') === 0);
                                if (!$isDashboardPath) {
                                    $redirectUrl = $requestedRedirect;
                                }
                            }

                            header('Location: ' . url($redirectUrl));
                            exit;
                        }
                    } else {
                        // Invalid credentials - log failed attempt
                        try {
                            $stmt = $conn->prepare("
                                INSERT INTO `" . DB::RATE_LIMIT_ATTEMPTS . "` 
                                (identifier, action, attempts, first_attempt_at, last_attempt_at) 
                                VALUES (?, 'login', 1, NOW(), NOW())
                                ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt_at = NOW()
                            ");
                            if ($stmt) {
                                $stmt->bind_param('s', $ipAddress);
                                $stmt->execute();
                                $stmt->close();
                            }
                        } catch (Throwable $rateLimitException) {
                            error_log('Login rate-limit log warning: ' . $rateLimitException->getMessage());
                        }
                        
                        // Log failed login attempt
                        if ($user) {
                            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                            try {
                                $stmt = $conn->prepare("
                                    INSERT INTO `" . DB::AUTHENTICATION_ACTIVITY . "` 
                                    (user_id, activity_type, ip_address, user_agent, created_at) 
                                    VALUES (?, 'login_failed', ?, ?, NOW())
                                ");
                                if ($stmt) {
                                    $stmt->bind_param('iss', $user['id'], $ipAddress, $userAgent);
                                    $stmt->execute();
                                    $stmt->close();
                                }
                            } catch (Throwable $failedLogException) {
                                error_log('Login failed activity log warning: ' . $failedLogException->getMessage());
                            }
                        }
                        
                        $errors[] = 'Invalid email or password.';
                    }
                }
                
            } catch (Throwable $e) {
                $errors[] = 'An unexpected error occurred. Please try again later.';
                error_log('Login error: ' . $e->getMessage());
            }
        }
    }
}

// ============================================
// SECTION 4: CSRF TOKEN
// ============================================
$pageTitle = 'UAE Business Directory | Login';
$pageDescription = 'Login to manage your UAE business listings, saved searches, and account settings.';
$bodyClass = 'page-login';
$csrfToken = csrf_field_frontend();
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main id="main-content" class="section">
        <div class="container-narrow auth-shell">
            <form class="card-ui form-box auth-form" method="post" action="">
        <?php echo $csrfToken; ?>
                <input type="text" name="website" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
                <h1 class="auth-title">Sign in</h1>
        <p class="muted">Access your listings, leads, and billing options.</p>

        <?php if (!empty($errors)): ?>
                    <div class="auth-alert auth-alert--error">
            <strong>⚠ Login failed:</strong>
                        <ul class="auth-alert-list">
              <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
                    <div class="auth-alert auth-alert--success">
            <strong>✓ Success!</strong><br>
            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <label for="login_email">Email</label>
        <input class="field" id="login_email" name="login_email" type="email" autocomplete="email" required
               value="<?php echo htmlspecialchars($formData['login_email'], ENT_QUOTES, 'UTF-8'); ?>">

                <label for="login_password" class="auth-label-spaced">Password</label>
        <input class="field" id="login_password" name="login_password" type="password" autocomplete="current-password" required>

                <?php if ($captchaRequired): ?>
                    <label for="login_captcha" class="auth-label-spaced">Security code</label>
                    <div class="mb-2">
                        <img
                            id="login-captcha-image"
                            src="<?php echo htmlspecialchars(url('/api/captcha.php?context=login_form&refresh=1'), ENT_QUOTES, 'UTF-8'); ?>"
                            alt="Captcha code"
                            width="180"
                            height="56">
                    </div>
                    <div class="mb-2">
                        <button class="btn btn-light btn-sm" type="button" id="login-refresh-captcha">Refresh code</button>
                    </div>
                    <input class="field" id="login_captcha" name="login_captcha" type="text" required
                                 placeholder="Enter security code"
                                 inputmode="text"
                                 autocapitalize="characters"
                                 autocomplete="off"
                                 maxlength="7">
                <?php endif; ?>

                <div class="auth-row">
          <label class="option-row">
            <input type="checkbox" name="remember_me" value="1" <?php echo $formData['remember_me'] ? 'checked' : ''; ?>> 
            Remember me
          </label>
          <a class="muted" href="<?php echo htmlspecialchars(url('/forgot-password'), ENT_QUOTES, 'UTF-8'); ?>">Forgot password?</a>
        </div>

                <button class="btn-ui btn-primary-ui auth-submit" type="submit">Sign in</button>
                <p class="muted auth-note">No account? <a href="<?php echo htmlspecialchars(url('/register'), ENT_QUOTES, 'UTF-8'); ?>">Create one</a></p>
      </form>
    </div>
  </main>

<?php if ($captchaRequired): ?>
<script>
function refreshLoginCaptcha() {
    const image = document.getElementById('login-captcha-image');
    if (!image) {
        return;
    }
    const baseUrl = '<?php echo htmlspecialchars(url('/api/captcha.php?context=login_form&refresh=1'), ENT_QUOTES, 'UTF-8'); ?>';
    image.src = baseUrl + '&_=' + Date.now();
}

document.addEventListener('DOMContentLoaded', function() {
    const refreshButton = document.getElementById('login-refresh-captcha');
    if (refreshButton) {
        refreshButton.addEventListener('click', refreshLoginCaptcha);
    }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
