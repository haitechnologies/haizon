<!--Footer Section-->
	<?php
	$popularHsCodes = [];
	$socialLinks = [
		['label' => 'Facebook', 'url' => 'https://www.facebook.com/HaiPulsecom/', 'icon' => 'facebook'],
		['label' => 'X (Twitter)', 'url' => 'https://x.com/haipulse', 'icon' => 'twitter'],
		['label' => 'Instagram', 'url' => 'https://www.instagram.com/haipulse', 'icon' => 'instagram'],
		['label' => 'Pinterest', 'url' => 'https://www.pinterest.com/haipulsedotcom/', 'icon' => 'pinterest'],
	];
	?>
	<section>
		<footer class="bg-white text-dark site-footer site-footer-shell">
			<div class="footer-main border-bottom">
				<div class="container">
					<div class="footer-panel">
					<div class="row mb-4 g-2 footer-cta-section">
						<div class="col-md-3 col-6">
							<a href="<?php echo url('/listings'); ?>" class="btn footer-cta-btn-companies w-100">
								<i class="fa fa-building"></i><span>Find Companies</span>
							</a>
						</div>
						<div class="col-md-3 col-6">
							<a href="<?php echo url('/ads'); ?>" class="btn footer-cta-btn-classifieds w-100">
								<i class="fa fa-bullhorn"></i><span>Advertising Options</span>
							</a>
						</div>
						<div class="col-md-3 col-6">
							<a href="<?php echo url('/trade/hs-codes'); ?>" class="btn footer-cta-btn-hscode w-100">
								<i class="fa fa-search"></i><span>HS Code Finder</span>
							</a>
						</div>
						<div class="col-md-3 col-6">
							<a href="<?php echo url('/contact'); ?>" class="btn footer-cta-btn-support w-100">
								<i class="fa fa-life-ring"></i><span>Get Support</span>
							</a>
						</div>
					</div>

					<div class="row">
						<div class="col-xl-3 col-lg-6 col-md-12">
							<h6>HAIPULSE</h6>
							<p class="text-dark mb-3 footer-brand-copy">
								UAE business directory and trade platform connecting buyers, suppliers, and service providers across all major industries.
							</p>
						</div>

						<div class="col-xl-2 col-lg-6 col-md-6 col-12">
							<h6 class="mt-6 mt-xl-0 d-none d-md-block">Company</h6>
							<button class="footer-toggle d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#footerCompany" aria-expanded="false" aria-controls="footerCompany">
								Company <i class="fa fa-chevron-down"></i>
							</button>
							<div id="footerCompany" class="collapse d-md-block mobile-collapse">
							<ul class="list-unstyled mb-0">
								<li><a href="<?php echo url('/about'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>About Us</a></li>
								<li><a href="<?php echo url('/blog'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Blog</a></li>
								<li><a href="<?php echo url('/blog/submit'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Write for Us</a></li>
								<li><a href="<?php echo url('/tips'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Tips & Guides</a></li>
								<li><a href="<?php echo url('/ads'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Advertise With Us</a></li>
							</ul>
							</div>
						</div>

						<div class="col-xl-2 col-lg-6 col-md-6 col-12">
							<h6 class="mt-6 mt-xl-0 d-none d-md-block">Explore</h6>
							<button class="footer-toggle d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#footerExplore" aria-expanded="false" aria-controls="footerExplore">
								Explore <i class="fa fa-chevron-down"></i>
							</button>
							<div id="footerExplore" class="collapse d-md-block mobile-collapse">
							<ul class="list-unstyled mb-0">
								<li><a href="<?php echo url('/software'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Software Platform</a></li>
								<li><a href="<?php echo url('/software-pricing'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Software Pricing</a></li>
								<li><a href="<?php echo url('/categories'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>All Categories</a></li>
								<li><a href="<?php echo url('/pricing'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Pricing Plans</a></li>
								<li><a href="<?php echo url('/trade/hs-codes'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>HS Codes Directory</a></li>
								<li><a href="<?php echo url('/listings'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Browse Companies</a></li>
								<li><a href="<?php echo url('/tips'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Import/Export Tips</a></li>
								<li><a href="<?php echo url('/blog'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Market Insights</a></li>
							</ul>
							</div>
						</div>

						<div class="col-xl-2 col-lg-6 col-md-6 col-12">
							<h6 class="mt-6 mt-xl-0 d-none d-md-block">Support & Policies</h6>
							<button class="footer-toggle d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#footerSupport" aria-expanded="false" aria-controls="footerSupport">
								Support & Policies <i class="fa fa-chevron-down"></i>
							</button>
							<div id="footerSupport" class="collapse d-md-block mobile-collapse">
							<ul class="list-unstyled mb-0">
								<li><a href="<?php echo url('/contact'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Contact Support</a></li>
								<li><a href="<?php echo url('/privacy-policy'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Privacy Policy</a></li>
								<li><a href="<?php echo url('/terms-of-use'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Terms of Use</a></li>
								<li><a href="<?php echo url('/cookies-policy'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Cookie Policy</a></li>
								<li><a href="<?php echo url('/accessibility'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Accessibility</a></li>
								<li><a href="<?php echo url('/security'); ?>"><i class="fa fa-angle-double-right me-2 text-muted"></i>Security</a></li>
							</ul>
							</div>
						</div>

						<!-- Payments Accepted -->
						<div class="col-xl-3 col-lg-6 col-md-6 col-12">
							<h6 class="mt-6 mt-xl-0 d-none d-md-block">Payments Accepted</h6>
							<button class="footer-toggle d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#footerPayments" aria-expanded="false" aria-controls="footerPayments">
								Payments Accepted <i class="fa fa-chevron-down"></i>
							</button>
							<div id="footerPayments" class="collapse d-md-block mobile-collapse">
								<div class="footer-pay-grid mt-1">
									<span class="fpb fpb-visa" title="Visa">VISA</span>
									<span class="fpb fpb-mc" title="Mastercard"><span class="fpb-mc-l"></span><span class="fpb-mc-r"></span></span>
									<span class="fpb fpb-amex" title="American Express">AMEX</span>
									<span class="fpb fpb-pp" title="PayPal"><span class="fpb-pp-pay">Pay</span><span class="fpb-pp-pal">Pal</span></span>
									<span class="fpb fpb-apple" title="Apple Pay">&#63743;&nbsp;Pay</span>
									<span class="fpb fpb-gpay" title="Google Pay"><span class="fpb-gpay-b fpb-gpay-blue">G</span><span class="fpb-gpay-b fpb-gpay-green">o</span><span class="fpb-gpay-b fpb-gpay-yellow">o</span><span class="fpb-gpay-b fpb-gpay-red">g</span><span class="fpb-gpay-b fpb-gpay-blue">le</span>&nbsp;Pay</span>
									<span class="fpb fpb-stripe" title="Stripe">stripe</span>
									<span class="fpb fpb-union" title="UnionPay">UnionPay</span>
								</div>
								<p class="text-muted mt-2 mb-0 footer-pay-note">
									<i class="fa fa-lock text-success me-1"></i>SSL-encrypted &amp; PCI-DSS compliant checkout. Card data is never stored on our servers.
								</p>
							</div>
						</div>
					</div>
					</div>
				</div>
			</div>

			<div class="bg-white text-muted p-3 border-top footer-bottom-bar">
				<div class="container">
					<div class="row align-items-center footer-bottom-inner">
						<div class="col-lg-8 col-sm-12 mt-2 mb-2 text-center text-lg-start">
							Â© <?php echo date('Y'); ?> <a href="<?php echo url('/'); ?>" class="fs-14 text-dark mx-1">HAIPULSE</a>. All rights reserved.
							<span class="ms-2 footer-load-time">
								<?php 
								if (isset($GLOBALS['pageStartTime'])) {
								    $loadTime = (microtime(true) - $GLOBALS['pageStartTime']) * 1000;
								    echo '| Page loaded in ' . round($loadTime, 2) . ' ms';
								}
								?>
							</span>
						</div>
						<div class="col-lg-4 col-sm-12 text-center text-lg-end mb-2 mt-2">
							<ul class="social-icons mb-0">
								<?php foreach ($socialLinks as $socialLink): ?>
									<li><a class="social-icon" href="<?php echo htmlspecialchars($socialLink['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" aria-label="<?php echo htmlspecialchars($socialLink['label'], ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-<?php echo htmlspecialchars($socialLink['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</footer>
	</section>
	<!--Footer Section-->

	<!-- JQuery js-->
	<script src="assets/js/vendors/jquery.js"></script>

	<!-- Bootstrap js -->
	<script src="assets/plugins/bootstrap/js/popper.min.js"></script>
	<script src="assets/plugins/bootstrap/js/bootstrap.min.js"></script>

	<!--JQuery Sparkline Js-->
	<script src="assets/js/vendors/jquery.sparkline.min.js"></script>

	<!-- Circle Progress Js-->
	<script src="assets/js/vendors/circle-progress.min.js"></script>

	<!-- Star Rating-2 Js-->
	<script src="assets/plugins/ratings-2/jquery.star-rating.js"></script>
	<script src="assets/plugins/ratings-2/star-rating.js"></script>

	<!--Counters -->
	<script src="assets/plugins/counters/counterup.min.js"></script>
	<script src="assets/plugins/counters/waypoints.min.js"></script>
	<script src="assets/plugins/counters/numeric-counter.js"></script>

	<!--Owl Carousel js -->
	<script src="assets/plugins/owl-carousel/owl.carousel.js"></script>
	<script src="assets/js/owl-carousel.js"></script>

	<!--Horizontal Menu-->
	<script src="assets/plugins/horizontal/horizontal-menu/horizontal.js"></script>

	<!--JQuery TouchSwipe js-->
	<script src="assets/js/jquery.touchSwipe.min.js"></script>

	<!--Select2 js -->
	<script src="assets/plugins/select2/select2.full.min.js"></script>
	<script src="assets/js/select2.js"></script>

	<!-- sticky Js-->
	<script src="assets/js/sticky.js?v=20260407"></script>

	<!-- Cookie js -->
	<script src="assets/plugins/cookie/jquery.ihavecookies.js"></script>
	<script src="assets/plugins/cookie/cookie.js"></script>

	<!--Auto Complete js -->
	<script src="assets/plugins/autocomplete/jquery.autocomplete.js"></script>
	<script src="assets/plugins/autocomplete/autocomplete.js"></script>

	<!-- p-scroll bar Js-->
	<script src="assets/plugins/perfect-scrollbar/perfect-scrollbar.js"></script>

	<!-- Swipe Js-->
	<script src="assets/js/swipe.js"></script>

	<!-- Scripts Js-->
	<script src="assets/js/scripts2.js"></script>

	<!-- Custom Js-->
	<script src="assets/js/themeColors.js"></script>
	<script src="assets/js/custom.js"></script>

	<script>
		(function () {
			function initPublicMenuFallback() {
				var toggler = document.querySelector('.site-main-nav .navbar-toggler');
				var menu = document.getElementById('publicMainMenu');
				if (!toggler || !menu) {
					return;
				}

				// Let Bootstrap handle it when available.
				if (window.bootstrap && typeof window.bootstrap.Offcanvas === 'function') {
					return;
				}

				toggler.addEventListener('click', function (e) {
					e.preventDefault();
					var isShown = menu.classList.contains('show');
					if (isShown) {
						menu.classList.remove('show');
						menu.style.transform = 'translateX(100%)';
						menu.style.visibility = 'hidden';
						menu.removeAttribute('aria-modal');
						menu.setAttribute('aria-hidden', 'true');
						toggler.setAttribute('aria-expanded', 'false');
					} else {
						menu.classList.add('show');
						menu.style.transform = 'none';
						menu.style.visibility = 'visible';
						menu.setAttribute('aria-modal', 'true');
						menu.setAttribute('aria-hidden', 'false');
						toggler.setAttribute('aria-expanded', 'true');
					}
				});
			}

			function initPublicMenuAutoClose() {
				var menu = document.getElementById('publicMainMenu');
				if (!menu) {
					return;
				}

				var links = menu.querySelectorAll('[data-public-nav-link="1"]');
				for (var i = 0; i < links.length; i++) {
					links[i].addEventListener('click', function () {
						if (!window.bootstrap || typeof window.bootstrap.Offcanvas !== 'function') {
							return;
						}

						if (window.innerWidth >= 992) {
							return;
						}

						var offcanvas = window.bootstrap.Offcanvas.getInstance(menu);
						if (offcanvas) {
							offcanvas.hide();
						}
					});
				}
			}

			function markLoadedLazyImages(root) {
				var context = root && root.querySelectorAll ? root : document;
				var lazyImages = context.querySelectorAll('img[loading="lazy"]');
				for (var i = 0; i < lazyImages.length; i++) {
					var img = lazyImages[i];
					if (img.complete) {
						img.classList.add('loaded');
						continue;
					}
					if (img.dataset.lazyBound === '1') {
						continue;
					}
					img.dataset.lazyBound = '1';
					img.addEventListener('load', function () {
						this.classList.add('loaded');
					}, { once: true });
					img.addEventListener('error', function () {
						this.classList.add('loaded');
					}, { once: true });
				}
			}

			function maskContactLinks() {
				var links = document.querySelectorAll('a[href^="tel:"], a[href^="mailto:"]');
				for (var i = 0; i < links.length; i++) {
					var link = links[i];
					if (link.dataset.revealInit === '1') {
						continue;
					}

					var href = link.getAttribute('href') || '';
					var isPhone = href.indexOf('tel:') === 0;
					var rawValue = href.replace(/^tel:|^mailto:/i, '').trim();
					if (!rawValue) {
						continue;
					}

					var icon = link.querySelector('i');
					var iconHtml = icon ? icon.outerHTML + ' ' : '';

					link.dataset.revealInit = '1';
					link.dataset.realHref = href;
					link.dataset.realValue = rawValue;
					link.dataset.iconHtml = iconHtml;
					link.dataset.revealed = '0';
					link.setAttribute('href', '#');
					link.innerHTML = iconHtml + (isPhone ? 'Click to show phone' : 'Click to show email');

					link.addEventListener('click', function (e) {
						if (this.dataset.revealed === '1') {
							return;
						}
						e.preventDefault();
						this.dataset.revealed = '1';
						this.setAttribute('href', this.dataset.realHref);
						this.innerHTML = (this.dataset.iconHtml || '') + this.dataset.realValue;
					});
				}
			}

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', function () {
					initPublicMenuFallback();
					initPublicMenuAutoClose();
					maskContactLinks();
					markLoadedLazyImages(document);
				});
			} else {
				initPublicMenuFallback();
				initPublicMenuAutoClose();
				maskContactLinks();
				markLoadedLazyImages(document);
			}

			if (window.MutationObserver) {
				var observer = new MutationObserver(function (mutations) {
					maskContactLinks();
					for (var idx = 0; idx < mutations.length; idx++) {
						var mutation = mutations[idx];
						for (var j = 0; j < mutation.addedNodes.length; j++) {
							var node = mutation.addedNodes[j];
							if (node && node.nodeType === 1) {
								markLoadedLazyImages(node);
							}
						}
					}
				});
				observer.observe(document.body, { childList: true, subtree: true });
			}
		})();
	</script>
<?php
if (!empty($pageScripts) && is_array($pageScripts)) {
    foreach ($pageScripts as $src) {
        $safeSrc = htmlspecialchars($src, ENT_QUOTES);
        echo '    <script src="' . $safeSrc . '"></script>' . "
";
    }
}
?>
</body>

</html>

