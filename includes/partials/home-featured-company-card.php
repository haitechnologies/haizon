<?php
/**
 * Home page featured company card.
 *
 * Expected variables:
 * @var array $company
 */

$homeCompanyName = display_text($company['company_name'] ?? '');
$homeCompanySlug = (string)($company['slug'] ?? '');
$homeCompanyLocation = display_text($company['city'] ?? ($company['emirate'] ?? 'UAE'));
$homeCompanyDescription = display_text($company['description'] ?? 'Professional services and trusted business solutions.');
if (strlen($homeCompanyDescription) > 110) {
    $homeCompanyDescription = substr($homeCompanyDescription, 0, 107) . '...';
}
?>

<article class="card-ui business-card">
  <div class="business-top">
    <?php if (!empty($company['featured'])): ?><span class="pill">Featured</span><?php endif; ?>
    <?php if (!empty($company['verified'])): ?><span class="pill">Verified</span><?php endif; ?>
  </div>
  <h3><?php echo htmlspecialchars($homeCompanyName, ENT_QUOTES, 'UTF-8'); ?></h3>
  <p class="meta-line">
    <?php echo htmlspecialchars($homeCompanyLocation, ENT_QUOTES, 'UTF-8'); ?>
  </p>
  <p class="muted">
    <?php echo htmlspecialchars($homeCompanyDescription, ENT_QUOTES, 'UTF-8'); ?>
  </p>
  <a class="btn-ui btn-light-ui" href="<?php echo htmlspecialchars(url('/company/' . $homeCompanySlug), ENT_QUOTES, 'UTF-8'); ?>">View profile</a>
</article>