<?php
require_once __DIR__ . '/../config/session.php';
startFrontendSession();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/frontend/EmailPreferences.php';

if (!isset($_SESSION['frontend_user_id'])) {
    $redirect = urlencode('/email-preferences');
    header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/login?redirect=' . $redirect));
    exit;
}

$userId = (int)$_SESSION['frontend_user_id'];
$model = new EmailPreferences($conn);
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security validation failed.';
    } else {
        $isSubscribed = isset($_POST['newsletter_opt_in']) && $_POST['newsletter_opt_in'] === '1';
        $ok = $model->updateUserPreference($userId, $isSubscribed);
        if ($ok) {
            $success = 'Email preferences updated successfully.';
        } else {
            $errors[] = 'Unable to update email preferences.';
        }
    }
}

$preference = $model->getPreferenceByUser($userId);
if (!$preference) {
    $errors[] = 'Unable to load preference settings.';
    $preference = ['email' => '', 'is_subscribed' => true];
}

$stats = $model->getEmailHistoryStats($preference['email']);

$pageTitle = 'Email Preferences - UAE Business Directory';
$pageDescription = 'Manage newsletter and campaign email preferences.';

include __DIR__ . '/../includes/layout/header.php';
?>
<main class="section">
    <div class="container-narrow emailpref-shell">
        <div class="section-head">
            <h1 class="emailpref-title">Email Preferences</h1>
            <p class="muted emailpref-subtitle">Control your newsletter and campaign communication settings.</p>
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
        <?php endif; ?>

        <section class="card-ui emailpref-card-spaced">
            <h3 class="emailpref-h3">Subscription Settings</h3>
            <form method="post">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input class="form-control" value="<?php echo htmlspecialchars($preference['email'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="newsletter_opt_in" name="newsletter_opt_in" value="1" <?php echo $preference['is_subscribed'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="newsletter_opt_in">
                        Receive newsletters and campaign emails
                    </label>
                </div>

                <button class="btn-ui btn-primary-ui" type="submit">Save Preferences</button>
                <a href="<?php echo url('/unsubscribe?email=' . urlencode($preference['email'])); ?>" class="btn-ui btn-light-ui emailpref-unsub-btn">Global Unsubscribe</a>
            </form>
        </section>

        <section class="card-ui emailpref-card">
            <h3 class="emailpref-h3">Email Activity</h3>
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="p-3 emailpref-stat">
                        <div class="small text-muted">Total Emails</div>
                        <div class="emailpref-stat-value"><?php echo (int)$stats['total']; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 emailpref-stat">
                        <div class="small text-muted">Sent</div>
                        <div class="emailpref-stat-value"><?php echo (int)$stats['sent']; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 emailpref-stat">
                        <div class="small text-muted">Opened</div>
                        <div class="emailpref-stat-value"><?php echo (int)$stats['opened']; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 emailpref-stat">
                        <div class="small text-muted">Clicked</div>
                        <div class="emailpref-stat-value"><?php echo (int)$stats['clicked']; ?></div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>
<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>
