<?php
/**
 * Page: Email Verification Pending
 * Route: /verify-email-pending
 * Description: Shows after registration that verification link was sent
 */

require_once __DIR__ . '/../config/session.php';
startFrontendSession();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
// If no registration success message, redirect to register
if (!isset($_SESSION['registration_success']) || !isset($_SESSION['registration_email'])) {
    header('Location: ' . url('/register'));
    exit;
}

$email = $_SESSION['registration_email'] ?? 'your email';
$pageTitle = 'Verify Your Email - UAE Business Directory';
$pageDescription = 'Confirm your email address to activate your account.';
$bodyClass = 'page-verify-email-pending';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

<main id="main-content" class="section">
    <div class="container-narrow verify-shell">
        <div class="card-ui register-verify-card">
            <!-- Success icon -->
            <div class="register-verify-icon">
                âœ‰ï¸
            </div>
            
            <h1 class="register-verify-title">Verification Email Sent</h1>
            
            <p class="register-verify-lead">
                We've sent a verification link to:
            </p>
            
            <div class="register-verify-email">
                <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>
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
                    <li><a href="<?php echo htmlspecialchars(url('/register'), ENT_QUOTES, 'UTF-8'); ?>" class="verify-resend-link">Register again to receive a new verification email</a></li>
                </ul>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

