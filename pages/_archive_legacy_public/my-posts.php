<?php
require_once __DIR__ . '/../config/session.php';
startFrontendSession();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';

if (!isFrontendUserLoggedIn()) {
    header('Location: ' . url('/login') . '?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? url('/my-posts')));
    exit;
}

$userId = getFrontendUserId();
$posts = [];
$stmt = $conn->prepare(
    "SELECT id, title, slug, submission_status, rejection_reason, created_at, updated_at
     FROM `" . DB::BLOGS . "`
     WHERE source = 'guest' AND submitted_by = ?
     ORDER BY created_at DESC"
);

if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $posts = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
}

$pageTitle = 'My Submitted Posts - UAE Business Directory';
$pageDescription = 'Track the review status of your guest blog submissions.';
$bodyClass = 'page-my-posts';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

<main id="main-content" class="section">
  <div class="container-narrow">
    <div class="mb-4">
      <h1>My Submitted Posts</h1>
      <p class="muted">Track pending, approved, and rejected guest blog submissions.</p>
    </div>

    <?php if (empty($posts)): ?>
      <div class="card-ui">
        <h3>No submissions yet</h3>
        <p class="muted">You have not submitted any guest posts yet.</p>
        <a href="<?php echo htmlspecialchars(url('/blog/submit'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui">Submit a Guest Post</a>
      </div>
    <?php else: ?>
      <div class="d-grid gap-3">
        <?php foreach ($posts as $post): ?>
          <?php $status = strtolower((string)($post['submission_status'] ?? 'pending')); ?>
          <article class="card-ui">
            <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
              <div>
                <h3 class="mb-1"><?php echo htmlspecialchars((string)($post['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p class="muted mb-2">Submitted <?php echo htmlspecialchars(dd_($post['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></p>
              </div>
              <div>
                <?php if ($status === 'approved' && !empty($post['slug'])): ?>
                  <a href="<?php echo htmlspecialchars(url('/blog/' . $post['slug']), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui">View Post</a>
                <?php endif; ?>
              </div>
            </div>

            <p class="mb-2">
              <strong>Status:</strong>
              <?php if ($status === 'approved'): ?>
                <span class="badge bg-success">Approved</span>
              <?php elseif ($status === 'rejected'): ?>
                <span class="badge bg-danger">Rejected</span>
              <?php else: ?>
                <span class="badge bg-warning text-dark">Pending Review</span>
              <?php endif; ?>
            </p>

            <?php if ($status === 'rejected' && !empty($post['rejection_reason'])): ?>
              <div class="alert alert-warning mb-0">
                <strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars((string)$post['rejection_reason'], ENT_QUOTES, 'UTF-8')); ?>
              </div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
