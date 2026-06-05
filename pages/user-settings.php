<?php
require_once __DIR__ . '/../config/session.php';
startFrontendSession();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/UserSettings.php';

if (!isset($_SESSION['frontend_user_id'])) {
    $redirect = urlencode('/user-settings');
  header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/login?redirect=' . $redirect));
    exit;
}

$userId = (int)$_SESSION['frontend_user_id'];
$model = new UserSettings($conn);
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security validation failed.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $mobile = trim((string)($_POST['mobile'] ?? ''));

            if ($fullName === '') {
                $errors[] = 'Full name is required.';
            } else {
                $ok = $model->updateProfile($userId, ['full_name' => $fullName, 'mobile' => $mobile]);
                $success = $ok ? 'Profile updated successfully.' : 'Could not update profile.';
                if ($ok) {
                    $_SESSION['frontend_user_name'] = $fullName;
                }
            }
        }

        if ($action === 'change_password') {
            $current = (string)($_POST['current_password'] ?? '');
            $new = (string)($_POST['new_password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');

            if (!$model->verifyPassword($userId, $current)) {
                $errors[] = 'Current password is incorrect.';
            } elseif (strlen($new) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            } elseif ($new !== $confirm) {
                $errors[] = 'New password and confirmation do not match.';
            } else {
                $success = $model->changePassword($userId, $new) ? 'Password changed successfully.' : 'Password change failed.';
            }
        }

        if ($action === 'delete_account') {
            $confirmDelete = (string)($_POST['confirm_delete'] ?? '');
            if ($confirmDelete !== 'DELETE') {
                $errors[] = 'Type DELETE to confirm account deletion.';
            } else {
                if ($model->deleteAccount($userId)) {
                    session_destroy();
                  header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/register?deleted=1'));
                    exit;
                }
                $errors[] = 'Could not delete account.';
            }
        }
    }
}

$user = $model->getUserInfo($userId);
if (!$user) {
    session_destroy();
  header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/login'));
    exit;
}
$pageTitle = 'User Settings - UAE Business Directory';
$pageDescription = 'Manage account profile and password.';
$bodyClass = 'page-user-settings';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>
<main class="section">
  <div class="container-narrow userset-shell">
    <div class="section-head"><h1 class="userset-title">Account Settings</h1></div>

    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e) { echo '<div>' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '</div>'; } ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

    <section class="card-ui userset-card">
      <h3 class="userset-h3">Profile</h3>
      <form method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="update_profile">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input class="form-control" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">Full Name</label>
            <input class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Mobile</label>
            <input class="form-control" name="mobile" value="<?php echo htmlspecialchars($user['mobile'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Member Since</label>
            <input class="form-control" value="<?php echo htmlspecialchars(dd_($user['created_at'] ?? 'now', 'd M Y'), ENT_QUOTES, 'UTF-8'); ?>" disabled>
          </div>
        </div>
        <button class="btn-ui btn-primary-ui userset-btn-top" type="submit">Save Profile</button>
      </form>
    </section>

    <section class="card-ui userset-card">
      <h3 class="userset-h3">Change Password</h3>
      <form method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="change_password">
        <div class="row g-3">
          <div class="col-md-4"><input class="form-control" type="password" name="current_password" placeholder="Current password" required></div>
          <div class="col-md-4"><input class="form-control" type="password" name="new_password" placeholder="New password" required></div>
          <div class="col-md-4"><input class="form-control" type="password" name="confirm_password" placeholder="Confirm password" required></div>
        </div>
        <button class="btn-ui btn-light-ui userset-btn-top" type="submit">Update Password</button>
      </form>
    </section>

    <section class="card-ui userset-card">
      <h3 class="userset-h3">Email Preferences</h3>
      <p class="muted userset-note-gap">Manage newsletter and campaign communication settings.</p>
      <a class="btn-ui btn-primary-ui" href="<?php echo url('/email-preferences'); ?>">Open Email Preferences</a>
    </section>

    <section class="card-ui userset-card userset-danger">
      <h3 class="userset-h3 userset-danger-title">Danger Zone</h3>
      <form method="post" onsubmit="return confirm('This action is permanent. Continue?');">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="delete_account">
        <p class="muted">Type <strong>DELETE</strong> to permanently remove your account.</p>
        <input class="form-control userset-confirm" name="confirm_delete" placeholder="Type DELETE">
        <button class="btn-ui btn-light-ui userset-btn-delete" type="submit">Delete Account</button>
      </form>
    </section>
  </div>
</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>
