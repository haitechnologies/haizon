<?php
require_once __DIR__ . '/../config/session.php';
startFrontendSession();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/Favorites.php';

if (!isset($_SESSION['frontend_user_id'])) {
  $redirect = urlencode('/my-favorites');
  $basePath = $GLOBALS['basePath'] ?? '';
  header('Location: ' . $basePath . '/login?redirect=' . $redirect);
    exit;
}

$userId = (int)$_SESSION['frontend_user_id'];
$favoritesModel = new Favorites($conn);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security validation failed.';
    } else {
        $action = $_POST['action'] ?? '';
        $companyId = (int)($_POST['company_id'] ?? 0);

        if ($action === 'remove' && $companyId > 0) {
            $success = $favoritesModel->removeFavorite($userId, $companyId) ? 'Favorite removed.' : 'Failed to remove favorite.';
        }

        if ($action === 'note' && $companyId > 0) {
            $note = trim((string)($_POST['user_notes'] ?? ''));
            $success = $favoritesModel->updateNote($userId, $companyId, $note) ? 'Note updated.' : 'Could not update note.';
        }
    }
}

$verifiedOnly = isset($_GET['verified_only']) ? 1 : 0;
$emirate = trim((string)($_GET['emirate'] ?? ''));
$favorites = $favoritesModel->getUserFavorites($userId, [
    'verified_only' => $verifiedOnly,
    'emirate' => $emirate
]);
$pageTitle = 'My Favorites - UAE Business Directory';
$pageDescription = 'Manage your saved favorite companies.';
$bodyClass = 'page-my-favorites';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>
<main class="section">
  <div class="container-narrow">
    <div class="section-head"><h1 class="fav-title">My Saved Companies</h1><span class="muted"><?php echo count($favorites); ?> saved</span></div>

    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e) { echo '<div>' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '</div>'; } ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

    <form method="get" class="card-ui fav-filter">
      <label><input type="checkbox" name="verified_only" value="1" <?php echo $verifiedOnly ? 'checked' : ''; ?>> Verified only</label>
      <input class="field fav-emirate" type="text" name="emirate" placeholder="Filter emirate" value="<?php echo htmlspecialchars($emirate, ENT_QUOTES, 'UTF-8'); ?>">
      <button class="btn-ui btn-primary-ui" type="submit">Apply</button>
      <a class="btn-ui btn-light-ui" href="<?php echo htmlspecialchars(url('/my-favorites'), ENT_QUOTES, 'UTF-8'); ?>">Clear</a>
    </form>

    <?php if (empty($favorites)): ?>
      <article class="card-ui"><p class="muted fav-empty">No saved companies yet. <a href="listings">Browse listings</a>.</p></article>
    <?php else: ?>
      <?php foreach ($favorites as $fav): ?>
        <article class="card-ui business-card fav-card">
          <div class="fav-row">
            <div>
              <h3 class="fav-h3"><a href="company/<?php echo urlencode($fav['slug']); ?>"><?php echo htmlspecialchars($fav['company_name'], ENT_QUOTES, 'UTF-8'); ?></a></h3>
              <p class="meta-line"><?php echo htmlspecialchars($fav['category_name'] ?: 'Business', ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars(trim(($fav['city'] ?? '') . ' ' . ($fav['state'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></p>
              <p class="fav-saved">Saved on <?php echo htmlspecialchars(dd_($fav['saved_at'], 'd M Y'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <form method="post" onsubmit="return confirm('Remove this company from favorites?');">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="company_id" value="<?php echo (int)$fav['company_id']; ?>">
              <button class="btn-ui btn-light-ui" type="submit">Remove</button>
            </form>
          </div>

          <form method="post" class="fav-note-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="note">
            <input type="hidden" name="company_id" value="<?php echo (int)$fav['company_id']; ?>">
            <textarea class="field" name="user_notes" rows="2" placeholder="Your notes"><?php echo htmlspecialchars($fav['user_notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            <button class="btn-ui btn-primary-ui fav-note-btn" type="submit">Save Note</button>
          </form>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>
