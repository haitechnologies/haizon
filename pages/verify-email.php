<?php
/**
 * Page: Email Verification (NEW DESIGN)
 * Route: /verify-email
 * Description: Verify user email address with token
 * Author: Development Team
 * Created: February 28, 2026
 */

// ============================================
// SECTION 1: DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../includes/helpers.php';

// ============================================
// SECTION 2: GET TOKEN & VALIDATE
// ============================================
$token = $_GET['token'] ?? '';
$message = '';
$messageType = ''; // success or danger
$verified = false;

if (empty($token)) {
    $message = 'Invalid verification link. Token is missing.';
    $messageType = 'danger';
} else {
    // Check if token exists and is not expired (24 hour expiry)
    $query = "
        SELECT id, email, email_verified, created_at 
        FROM `" . DB::FRONTEND_USERS . "` 
        WHERE email_verification_token = ? 
        AND email_verified = 0
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        $message = 'Invalid or expired verification link. This link may have already been used or is no longer valid.';
        $messageType = 'danger';
    } else {
        // Check token age (24 hours = 86400 seconds)
        $tokenAge = time() - strtotime($user['created_at']);
        
        if ($tokenAge > 86400) {
            $message = 'Verification link has expired. Please request a new verification email.';
            $messageType = 'danger';
        } else {
            // Token is valid - verify the user
            $updateQuery = "
                UPDATE `" . DB::FRONTEND_USERS . "` 
                SET email_verified = 1, 
                    email_verification_token = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ";
            
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('i', $user['id']);
            
            if ($updateStmt->execute()) {
                $verified = true;
                $message = 'Email verified successfully! Your account is now active.';
                $messageType = 'success';
                
                // Log activity
                $activityQuery = "
                    INSERT INTO `" . DB::AUTHENTICATION_ACTIVITY . "` 
                    (user_id, activity_type, ip_address, user_agent, created_at) 
                    VALUES (?, 'email_verified', ?, ?, NOW())
                ";
                $activityStmt = $conn->prepare($activityQuery);
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                $activityStmt->bind_param('iss', $user['id'], $ipAddress, $userAgent);
                $activityStmt->execute();
                $activityStmt->close();
            } else {
                $message = 'An error occurred while verifying your email. Please try again.';
                $messageType = 'danger';
            }
            
            $updateStmt->close();
        }
    }
}

$pageTitle = 'Email Verification - UAE Business Directory';
$pageDescription = 'Verify your email address to activate your UAE Business Directory account.';
$bodyClass = 'page-verify-email';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main id="main-content" class="section">
    <div class="container-narrow verify-shell">
      <div class="card-ui verifyresult-card">
        <?php if ($messageType === 'success'): ?>
          <div class="verifyresult-icon verifyresult-icon--ok">✓</div>
          <h1 class="verifyresult-title">Email Verified!</h1>
        <?php else: ?>
          <div class="verifyresult-icon verifyresult-icon--fail">✕</div>
          <h1 class="verifyresult-title">Verification Failed</h1>
        <?php endif; ?>
        
        <div class="alert alert-<?php echo $messageType; ?> verifyresult-alert" role="alert">
          <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <?php if ($verified): ?>
          <p class="verifyresult-copy">
            Your account is now fully activated. You can now login and access all features including adding businesses, managing listings, and more.
          </p>
          <a href="<?php echo htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui verifyresult-action">
            Continue to Login
          </a>
        <?php else: ?>
          <p class="verifyresult-copy">
            Need help? <a href="<?php echo htmlspecialchars(url('/contact'), ENT_QUOTES, 'UTF-8'); ?>">Contact our support team</a> for assistance.
          </p>
          <div class="verifyresult-actions">
            <a href="<?php echo htmlspecialchars(url('/register'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui">Register Again</a>
            <a href="<?php echo htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui">Back to Login</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
