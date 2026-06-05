  </main>
  <!-- Main Content End -->
  <?php $isAmpUserLoggedIn = function_exists('isFrontendUserLoggedIn') ? isFrontendUserLoggedIn() : false; ?>
  <?php $canonicalUrl = $canonicalUrl ?? url('/'); ?>
  
  <!-- Footer -->
  <footer class="amp-footer">
    <div class="container">
      <div class="amp-footer-grid">
        
        <!-- About -->
        <div>
          <h3 class="amp-footer-title">HaiPulse - Business Directory</h3>
          <p class="amp-footer-text">
            Browse verified UAE companies, HS codes, and business insights through fast AMP pages built for mobile users.
          </p>
          <div class="amp-footer-links amp-footer-links-spaced">
            <a href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">View full website version</a>
            <a href="<?php echo htmlspecialchars(url('/sitemap-amp.xml'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">Open AMP sitemap</a>
          </div>
        </div>
        
        <!-- AMP Directory -->
        <div>
          <h3 class="amp-footer-title">AMP Directory</h3>
          <div class="amp-footer-links">
            <a href="<?php echo htmlspecialchars(url('/listings/amp'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">Browse Companies (AMP)</a>
            <a href="<?php echo htmlspecialchars(url('/trade/hs-codes/amp'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">HS Codes (AMP)</a>
            <a href="<?php echo htmlspecialchars(url('/blog/amp'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">Business Blog (AMP)</a>
            <a href="<?php echo htmlspecialchars(url('/about/amp'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">About HAIPULSE (AMP)</a>
            <a href="<?php echo htmlspecialchars(url('/contact/amp'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">Contact Support (AMP)</a>
          </div>
        </div>
        
        <!-- Popular Actions -->
        <div>
          <h3 class="amp-footer-title">Popular Actions</h3>
          <div class="amp-footer-links">
            <a href="<?php echo htmlspecialchars(url('/add-business'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">Add your business listing</a>
            <a href="<?php echo htmlspecialchars(url('/trade'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">Open full Trade Portal</a>
            <a href="<?php echo htmlspecialchars(url('/privacy-policy'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">Privacy Policy</a>
            <a href="<?php echo htmlspecialchars(url('/terms-of-use'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">Terms of Use</a>
          </div>
        </div>
        
        <!-- Account -->
        <div>
          <h3 class="amp-footer-title">Account</h3>
          <div class="amp-footer-links">
            <?php if ($isAmpUserLoggedIn): ?>
              <a href="<?php echo htmlspecialchars(url('/account/profile'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">My Account</a>
              <a href="<?php echo htmlspecialchars(url('/logout'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">Logout</a>
            <?php else: ?>
              <a href="<?php echo htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">Sign In</a>
              <a href="<?php echo htmlspecialchars(url('/register'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">Create Account</a>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars(url('/my-favorites'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-footer-link">My Favorites</a>
          </div>
        </div>
        
      </div>
      
      <!-- Copyright -->
      <div class="amp-footer-meta">
        <p class="amp-footer-meta-text">
          &copy; <?php echo date('Y'); ?> HaiPulse - Business Directory. Fast AMP experience for search and mobile users.
        </p>
      </div>
    </div>
  </footer>
  
</body>
</html>

