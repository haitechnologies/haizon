<?php
/**
 * SaaS Public Layout — Footer
 * Include at the bottom of every pages/saas/*.php file.
 */
?>
<footer class="saas-footer" aria-label="Site footer">
  <div class="saas-container">
    <div class="saas-footer__grid">
      <!-- Brand -->
      <div>
        <a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-nav__logo" style="margin-bottom:12px; text-decoration:none; display:inline-flex;">
          <div class="saas-nav__logo-mark">H</div>
          <span class="saas-nav__logo-text" style="color:#fff;">HAIPULSE</span>
        </a>
        <p class="saas-footer__brand-text">
          Business operations software built for UAE teams. Run CRM, HR, Accounting,
          and Shipping from one connected admin workspace.
        </p>
      </div>

      <!-- Platform -->
      <div>
        <div class="saas-footer__col-title">Platform</div>
        <ul class="saas-footer__links">
          <li><a href="<?php echo htmlspecialchars(url('/crm'), ENT_QUOTES, 'UTF-8'); ?>">CRM</a></li>
          <li><a href="<?php echo htmlspecialchars(url('/hr'), ENT_QUOTES, 'UTF-8'); ?>">HR</a></li>
          <li><a href="<?php echo htmlspecialchars(url('/accounting'), ENT_QUOTES, 'UTF-8'); ?>">Accounting</a></li>
          <li><a href="<?php echo htmlspecialchars(url('/shipping'), ENT_QUOTES, 'UTF-8'); ?>">Shipping</a></li>
          <li><a href="<?php echo htmlspecialchars(url('/all-in-one'), ENT_QUOTES, 'UTF-8'); ?>">All-in-One</a></li>
          <li><a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>">Pricing</a></li>
        </ul>
      </div>

      <!-- Company -->
      <div>
        <div class="saas-footer__col-title">Company</div>
        <ul class="saas-footer__links">
          <li><a href="<?php echo htmlspecialchars(url('/contact'), ENT_QUOTES, 'UTF-8'); ?>">Contact</a></li>
          <li><a href="<?php echo htmlspecialchars(url('/about'), ENT_QUOTES, 'UTF-8'); ?>">About</a></li>
          <li><a href="<?php echo htmlspecialchars(url('/dashboard/'), ENT_QUOTES, 'UTF-8'); ?>">Sign in</a></li>
        </ul>
      </div>

      <!-- Legal -->
      <div>
        <div class="saas-footer__col-title">Legal</div>
        <ul class="saas-footer__links">
          <li><a href="<?php echo htmlspecialchars(url('/privacy-policy'), ENT_QUOTES, 'UTF-8'); ?>">Privacy Policy</a></li>
          <li><a href="<?php echo htmlspecialchars(url('/terms-of-use'), ENT_QUOTES, 'UTF-8'); ?>">Terms of Use</a></li>
          <li><a href="<?php echo htmlspecialchars(url('/cookies-policy'), ENT_QUOTES, 'UTF-8'); ?>">Cookies Policy</a></li>
          <li><a href="<?php echo htmlspecialchars(url('/refund-policy'), ENT_QUOTES, 'UTF-8'); ?>">Refund Policy</a></li>
        </ul>
      </div>
    </div>

    <div class="saas-footer__bottom">
      <span>&copy; <?php echo date('Y'); ?> HAIPULSE. All rights reserved.</span>
      <span>Dubai, UAE &mdash; Built for the region.</span>
    </div>
  </div>
</footer>

<!-- Bootstrap JS (bundle includes Popper) -->
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
