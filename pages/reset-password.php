<?php
/**
 * Page: Reset Password (NEW DESIGN)
 * Route: /reset-password
 * Description: Reset user password with valid token
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
require_once __DIR__ . '/../includes/helpers.php';

RateLimiter::init($conn);

// ============================================
// SECTION 2: VERIFY TOKEN
// ============================================
$token = $_GET['token'] ?? '';
$tokenValid = false;
$tokenError = '';
$userId = null;

if (empty($token)) {
    $tokenError = 'Invalid reset link. Token is missing.';
} else {
    // Check if token exists and is not expired
    $query = "
        SELECT id, email, password_reset_token, password_reset_expiry 
        FROM `" . DB::FRONTEND_USERS . "` 
        WHERE password_reset_token = ?
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        $tokenError = 'Invalid reset link. This link may have already been used.';
    } elseif (strtotime($user['password_reset_expiry']) < time()) {
        $tokenError = 'Reset link has expired. Please request a new password reset.';
    } else {
        $tokenValid = true;
        $userId = $user['id'];
    }
}

// ============================================
// SECTION 3: HANDLE PASSWORD RESET
// ============================================
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    // Start session for CSRF token validation
  startFrontendSession();

    $clientIP = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (strpos($clientIP, ',') !== false) {
        $clientIP = trim(explode(',', $clientIP)[0]);
    }

    // CSRF validation
    if (!validate_csrf_token_frontend($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } elseif (!empty($_POST['website'] ?? '')) {
        $errors[] = 'Spam detected. Submission rejected.';
    } else {
        $rateLimit = RateLimiter::check($clientIP, 'reset_password_form', 6, 3600);
        if (!$rateLimit['allowed']) {
            $errors[] = 'Too many requests. Please try again later.';
        } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate password
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $errors[] = 'Password must be at least 8 characters and contain letters and numbers.';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        
        // If validation passes, update password
        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $updateQuery = "
                UPDATE `" . DB::FRONTEND_USERS . "` 
                SET password = ?,
                    password_reset_token = NULL,
                    password_reset_expiry = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ";
            
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('si', $hashedPassword, $userId);
            
            if ($updateStmt->execute()) {
                // Log activity
                $activityQuery = "
                    INSERT INTO `" . DB::AUTHENTICATION_ACTIVITY . "` 
                    (user_id, activity_type, ip_address, user_agent, created_at) 
                    VALUES (?, 'password_reset_completed', ?, ?, NOW())
                ";
                $activityStmt = $conn->prepare($activityQuery);
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                $activityStmt->bind_param('iss', $userId, $clientIP, $userAgent);
                $activityStmt->execute();
                $activityStmt->close();

                RateLimiter::recordAttempt($clientIP, 'reset_password_form');
                $success = 'Password reset successful! You can now login with your new password.';
                $tokenValid = false; // Prevent further submissions
            } else {
                $errors[] = 'An error occurred while resetting your password. Please try again.';
            }
            
            $updateStmt->close();
        }
    }
    }
}

$pageTitle = 'Reset Password - UAE Business Directory';
$pageDescription = 'Reset your password for UAE Business Directory.';
$bodyClass = 'page-reset-password';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main id="main-content" class="section">
    <div class="container-narrow reset-shell">
      <div class="card-ui reset-card">
        <h1 class="reset-title">Reset Password</h1>
        
        <?php if (!empty($tokenError)): ?>
          <div class="alert alert-danger reset-alert" role="alert">
            <?php echo htmlspecialchars($tokenError, ENT_QUOTES, 'UTF-8'); ?>
          </div>
          <p class="reset-center reset-gap-top">
            <a href="<?php echo htmlspecialchars(url('/forgot-password'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui">Request New Reset Link</a>
          </p>
        <?php elseif (!empty($success)): ?>
          <div class="alert alert-success reset-alert" role="alert">
            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
          </div>
          <p class="reset-center reset-gap-top">
            <a href="<?php echo htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui">Continue to Login</a>
          </p>
        <?php else: ?>
          <p class="muted reset-intro">
            Enter your new password below. Make sure it's strong and secure.
          </p>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
              <ul class="reset-error-list">
                <?php foreach ($errors as $error): ?>
                  <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post" action="" novalidate>
            <?php echo csrf_field_frontend(); ?>
            <input type="text" name="website" class="reset-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">
            
            <div class="form-group reset-form-group">
              <label for="password" class="form-label">New Password *</label>
              <input 
                type="password" 
                class="form-control" 
                id="password" 
                name="password" 
                placeholder="Enter new password"
                required
                autocomplete="new-password"
                minlength="8">
              <small class="form-text text-muted">At least 8 characters with letters and numbers</small>
            </div>

            <div class="form-group reset-form-group">
              <label for="confirm_password" class="form-label">Confirm New Password *</label>
              <input 
                type="password" 
                class="form-control" 
                id="confirm_password" 
                name="confirm_password" 
                placeholder="Confirm new password"
                required
                autocomplete="new-password"
                minlength="8">
            </div>

            <button type="submit" class="btn-ui btn-primary-ui reset-submit">
              Reset Password
            </button>
          </form>

          <div class="reset-footer">
            <p class="muted reset-footer-text">
              Remember your password? <a href="<?php echo htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>">Login here</a>
            </p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>
