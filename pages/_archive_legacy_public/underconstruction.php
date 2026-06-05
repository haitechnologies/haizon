<?php
/**
 * Page: Under Construction
 * Route: /underconstruction
 * Description: Placeholder for features under development
 */

$pageTitle = 'Page Under Construction - UAE Business Directory';
$pageDescription = 'This feature is currently under development. Check back soon!';

require_once __DIR__ . '/../includes/helpers.php';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main class="main-content">
    <div class="container-narrow underc-shell">
      <div class="underc-inner">
        <div class="underc-icon">
          🚧
        </div>
        <h1 class="underc-title">Under Construction</h1>
        <p class="muted underc-copy">
          <?php echo htmlspecialchars($pageDescription, ENT_QUOTES); ?>
        </p>
        <div class="underc-actions">
          <a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES); ?>" class="btn-ui btn-primary-ui">Back to Home</a>
          <a href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES); ?>" class="btn-ui btn-light-ui">Browse Businesses</a>
        </div>
      </div>
    </div>
  </main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>
