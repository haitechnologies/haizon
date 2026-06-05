<?php
/**
 * SaaS Public Layout — Navigation Bar
 * Included by includes/saas/header.php
 */
$_currentPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
function saasNavActive(string $route, string $currentPath): string {
    return ($currentPath === $route || strpos($currentPath, rtrim($route, '/') . '/') === 0) ? ' active' : '';
}
?>
<nav class="saas-nav" id="saas-nav" aria-label="Main navigation">
  <div class="saas-nav__inner">
    <!-- Logo -->
    <a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>" class="saas-nav__logo" aria-label="HAIPULSE home">
      <div class="saas-nav__logo-mark">H</div>
      <span class="saas-nav__logo-text">HAIPULSE</span>
    </a>

    <!-- Desktop links -->
    <ul class="saas-nav__links" role="list">
      <li>
        <a href="<?php echo htmlspecialchars(url('/crm'), ENT_QUOTES, 'UTF-8'); ?>"
           class="<?php echo saasNavActive('/crm', $_currentPath); ?>">CRM</a>
      </li>
      <li>
        <a href="<?php echo htmlspecialchars(url('/hr'), ENT_QUOTES, 'UTF-8'); ?>"
           class="<?php echo saasNavActive('/hr', $_currentPath); ?>">HR</a>
      </li>
      <li>
        <a href="<?php echo htmlspecialchars(url('/accounting'), ENT_QUOTES, 'UTF-8'); ?>"
           class="<?php echo saasNavActive('/accounting', $_currentPath); ?>">Accounting</a>
      </li>
      <li>
        <a href="<?php echo htmlspecialchars(url('/shipping'), ENT_QUOTES, 'UTF-8'); ?>"
           class="<?php echo saasNavActive('/shipping', $_currentPath); ?>">Shipping</a>
      </li>
      <li>
        <a href="<?php echo htmlspecialchars(url('/all-in-one'), ENT_QUOTES, 'UTF-8'); ?>"
           class="<?php echo saasNavActive('/all-in-one', $_currentPath); ?>">All-in-One</a>
      </li>
      <li>
        <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>"
           class="<?php echo saasNavActive('/pricing', $_currentPath); ?>">Pricing</a>
      </li>
    </ul>

    <!-- Desktop CTA -->
    <div class="saas-nav__actions">
      <a href="<?php echo htmlspecialchars(url('/contact?source=nav'), ENT_QUOTES, 'UTF-8'); ?>"
         class="saas-btn saas-btn-outline saas-btn-sm d-none d-md-inline-flex">Contact sales</a>
      <a href="<?php echo htmlspecialchars(url('/dashboard/'), ENT_QUOTES, 'UTF-8'); ?>"
         class="saas-btn saas-btn-primary saas-btn-sm">Sign in</a>
    </div>

    <!-- Mobile toggle -->
    <button class="saas-nav__mobile-toggle d-md-none" id="saas-mobile-toggle"
            aria-label="Toggle navigation" aria-expanded="false" aria-controls="saas-mobile-menu">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
  </div>

  <!-- Mobile menu -->
  <div class="saas-nav__mobile-menu" id="saas-mobile-menu" role="navigation" aria-label="Mobile menu">
    <a href="<?php echo htmlspecialchars(url('/crm'), ENT_QUOTES, 'UTF-8'); ?>">CRM</a>
    <a href="<?php echo htmlspecialchars(url('/hr'), ENT_QUOTES, 'UTF-8'); ?>">HR</a>
    <a href="<?php echo htmlspecialchars(url('/accounting'), ENT_QUOTES, 'UTF-8'); ?>">Accounting</a>
    <a href="<?php echo htmlspecialchars(url('/shipping'), ENT_QUOTES, 'UTF-8'); ?>">Shipping</a>
    <a href="<?php echo htmlspecialchars(url('/all-in-one'), ENT_QUOTES, 'UTF-8'); ?>">All-in-One</a>
    <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>">Pricing</a>
    <div class="saas-nav__mobile-divider"></div>
    <a href="<?php echo htmlspecialchars(url('/contact?source=mobile-nav'), ENT_QUOTES, 'UTF-8'); ?>">Contact sales</a>
    <a href="<?php echo htmlspecialchars(url('/dashboard/'), ENT_QUOTES, 'UTF-8'); ?>"
       style="color: var(--saas-accent); font-weight: 600;">Sign in</a>
  </div>
</nav>

<script>
(function () {
  var btn  = document.getElementById('saas-mobile-toggle');
  var menu = document.getElementById('saas-mobile-menu');
  if (!btn || !menu) return;
  btn.addEventListener('click', function () {
    var open = menu.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
})();
</script>
