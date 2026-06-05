<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim((string)($GLOBALS['basePath'] ?? ''), '/');
if ($basePath !== '' && stripos($currentPath, $basePath) === 0) {
    $currentPath = substr($currentPath, strlen($basePath));
    if ($currentPath === '' || $currentPath === false) {
        $currentPath = '/';
    }
}
$normalizedPath = rtrim($currentPath, '/');
if ($normalizedPath === '') {
    $normalizedPath = '/';
}

$isLoggedIn = function_exists('isFrontendUserLoggedIn')
    ? isFrontendUserLoggedIn()
    : (!empty($_SESSION['frontend_user_id']) || !empty($_SESSION['project_pre']['FRONTEND']['user_id']));

$logoFile = getSystemSetting('logo', '');
$logoUrl = url('assets/images/brand/logo.png');
if (!empty($logoFile) && file_exists(__DIR__ . '/../../uploads/system_settings/' . $logoFile)) {
    $logoUrl = url('uploads/system_settings/' . $logoFile);
}

$menuItems = [
    ['label' => 'Home', 'url' => '/', 'match' => ['/']],
    ['label' => 'Software', 'url' => '/software', 'match' => ['/software', '/software-pricing']],
    ['label' => 'Categories', 'url' => '/categories', 'match' => ['/categories', '/category', '/subcategory', '/listings', '/company']],
    ['label' => 'Services', 'url' => '/services', 'match' => ['/services', '/service']],
    ['label' => 'Trade Portal', 'url' => '/trade', 'match' => ['/trade', '/hs-code']],
    ['label' => 'Blog', 'url' => '/blog', 'match' => ['/blog']],
    ['label' => 'Contact', 'url' => '/contact', 'match' => ['/contact']],
];

$menuIsActive = static function (array $prefixes, string $path): bool {
    foreach ($prefixes as $prefix) {
        if ($prefix === '/') {
            if ($path === '/') {
                return true;
            }
            continue;
        }

        // Match exact path or subpath boundary only, avoiding false positives.
        if ($path === $prefix || stripos($path, $prefix . '/') === 0) {
            return true;
        }
    }

    return false;
};

?>

<header class="header-main public-shell-header">
    <div class="site-header-top py-2 public-shell-topbar">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="public-shell-badge"><i class="fa fa-map-marker me-1"></i>United Arab Emirates</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <a href="<?php echo url('/about'); ?>" class="text-muted">About Us</a>
                    <a href="<?php echo url('/ads'); ?>" class="text-muted">Advertise with Us</a>
                    <a href="<?php echo url('/contact'); ?>" class="text-muted">Contact Us</a>
                </div>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg site-main-nav public-shell-nav">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center public-shell-brand" href="<?php echo url('/'); ?>">
                <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="HAIPULSE logo">
                <span class="public-shell-brand-copy d-none d-md-flex">
                    <span class="public-shell-brand-title">HAIPULSE</span>
                    <span class="public-shell-brand-tagline">Business directory and trade platform</span>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#publicMainMenu" aria-controls="publicMainMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="offcanvas offcanvas-end offcanvas-lg navbar-collapse" tabindex="-1" id="publicMainMenu" aria-labelledby="publicMainMenuLabel">
                <div class="offcanvas-header d-lg-none">
                    <div class="d-flex align-items-center gap-2">
                        <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="HAIPULSE logo" class="offcanvas-brand-logo">
                        <div>
                            <div class="offcanvas-brand-label" id="publicMainMenuLabel">HAIPULSE</div>
                            <div class="offcanvas-brand-subtitle">Business directory and trade platform</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#publicMainMenu" aria-label="Close navigation"></button>
                </div>

                <div class="offcanvas-body align-items-lg-center">
                    <ul class="navbar-nav mx-auto mb-3 mb-lg-0">
                        <?php foreach ($menuItems as $item): ?>
                            <?php $active = $menuIsActive($item['match'], $normalizedPath); ?>
                            <li class="nav-item">
                                <a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo url($item['url']); ?>" data-public-nav-link="1"><?php echo htmlspecialchars($item['label'], ENT_QUOTES); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-3 public-nav-actions">
                        <?php if ($isLoggedIn): ?>
                            <a href="<?php echo url('/account/profile'); ?>" class="btn btn-outline-secondary btn-post" data-public-nav-link="1">
                                <i class="fa fa-user-circle me-1"></i>My Account
                            </a>
                            <a href="<?php echo url('/logout'); ?>" class="btn btn-dark btn-post" data-public-nav-link="1">
                                <i class="fa fa-sign-out me-1"></i>Logout
                            </a>
                        <?php else: ?>
                            <a href="<?php echo url('/login'); ?>" class="btn btn-outline-secondary btn-post" data-public-nav-link="1">Login</a>
                        <?php endif; ?>
                        <a href="<?php echo url('/add-business'); ?>" class="btn btn-success btn-post" data-public-nav-link="1">
                            <i class="fa fa-plus-circle me-1"></i>List My Business
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>

