<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/RateLimiter.php';
require_once __DIR__ . '/../classes/frontend/EmailPreferences.php';

$model = new EmailPreferences($conn);
RateLimiter::init($conn);
$errors = [];
$success = '';

$email = trim((string)($_GET['email'] ?? $_POST['email'] ?? ''));
$reason = trim((string)($_POST['reason'] ?? 'User requested unsubscribe'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientIP = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (strpos($clientIP, ',') !== false) {
        $clientIP = trim(explode(',', $clientIP)[0]);
    }

    // Start session for CSRF token validation
    startFrontendSession();

    if (!validate_csrf_token_frontend($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security validation failed. Please refresh and try again.';
    } elseif (!empty($_POST['website'] ?? '')) {
        $errors[] = 'Spam detected. Submission rejected.';
    } else {
        $rateLimit = RateLimiter::check($clientIP, 'unsubscribe_form', 8, 3600);
        if (!$rateLimit['allowed']) {
            $errors[] = 'Too many requests. Please try again later.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
        } else {
            $ok = $model->unsubscribeEmail($email, $reason ?: 'User requested unsubscribe', 'unsubscribe_page', null);
            if ($ok) {
                $success = 'You have been unsubscribed successfully.';
                RateLimiter::recordAttempt($clientIP, 'unsubscribe_form');
            } else {
                $errors[] = 'Could not process unsubscribe request.';
            }
        }
    }
}

$pageTitle = 'Unsubscribe - UAE Business Directory';
$pageDescription = 'Manage your email unsubscribe preferences.';

include __DIR__ . '/../includes/layout/header.php';
?>
<main class="section">
    <div class="container-narrow unsub-shell">
        <div class="section-head">
            <h1 class="unsub-title">Unsubscribe</h1>
            <p class="muted unsub-subtitle">You can unsubscribe from marketing and campaign emails at any time.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
            <p><a href="<?php echo url('/'); ?>">Return to homepage</a></p>
        <?php else: ?>
            <section class="card-ui unsub-card">
                <form method="post">
                    <?php echo csrf_field_frontend(); ?>
                    <input type="text" name="website" class="reset-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason (optional)</label>
                        <input type="text" name="reason" class="form-control" value="<?php echo htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <button class="btn-ui btn-light-ui" type="submit">Unsubscribe</button>
                </form>
            </section>
        <?php endif; ?>
    </div>
</main>
<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>
