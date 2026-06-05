<?php
/**
 * 404 Not Found Error Page
 * 
 * Public-facing error page when a requested resource is not found
 */

$errorStatusCode = isset($GLOBALS['error_status_code']) ? (int)$GLOBALS['error_status_code'] : 404;
$isRetiredPage = (($GLOBALS['error_page_variant'] ?? '') === 'retired' && $errorStatusCode === 410);

http_response_code($errorStatusCode);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/frontend/ErrorLogger.php';

// Log 404 error - but ONLY for frontend pages, not dashboard assets or paths
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$shouldLog = true;

// Skip logging if request is for dashboard paths or assets
if (stripos($requestUri, '/dashboard/') !== false ||
    stripos($requestUri, '/admin_elements/') !== false ||
    stripos($requestUri, '/assets/') !== false ||
    stripos($requestUri, '/uploads/') !== false ||
    preg_match('/\.(js|css|map|woff|woff2|ttf|eot|png|jpg|jpeg|gif|svg|ico)$/i', $requestUri)) {
    $shouldLog = false;
}

// Log only frontend 404s
if ($shouldLog) {
    $errorLogger = new FrontendErrorLogger();
    $errorLogger->log404(
        $requestUri,
        $_SERVER['HTTP_REFERER'] ?? 'direct'
    );
}

// Set page meta
$pageTitle = $isRetiredPage ? '410 - Listing Retired | HaiPulse' : '404 - Page Not Found | HaiPulse';
$pageDescription = $isRetiredPage
    ? 'The business listing you requested is no longer available.'
    : 'The page you are looking for could not be found.';

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center min-vh-100 d-flex align-items-center">
        <div class="col-lg-6 col-md-8">
            <!-- Error Card -->
            <div class="card shadow-lg border-0 rounded-lg text-center">
                <div class="card-body p-5">
                    
                    <!-- Error Icon -->
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" fill="#FFC107" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
                            <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.057.107.107 0 0 1-.066.01H.146a.11.11 0 0 1-.066-.01.163.163 0 0 1-.054-.057.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.146 13.192c-.057.099-.106.177-.134.252a.96.96 0 0 0 .004.272c.05.167.585.435 1.338.435h13.394c.753 0 1.288-.268 1.338-.435a.96.96 0 0 0 .005-.272c-.058-.075-.077-.153-.134-.252L8.982 1.566z"/>
                            <path d="m14.22 12.97.007-.008a.5.5 0 1 0-.707-.707l-.708.707a.5.5 0 0 0 .708.707l.007-.007zM5.354 5.385a.5.5 0 1 0-.707.707L7.293 8 4.647 10.646a.5.5 0 1 0 .707.707L8 8.707l2.646 2.647a.5.5 0 0 0 .707-.707L8.707 8l2.646-2.646a.5.5 0 0 0-.707-.707L8 7.293 5.354 4.646z"/>
                        </svg>
                    </div>

                    <!-- Error Code & Message -->
                    <h1 class="display-4 fw-bold mb-2 err-404-code"><?php echo $isRetiredPage ? '410' : '404'; ?></h1>
                    <h2 class="h4 fw-semibold mb-3"><?php echo $isRetiredPage ? 'Listing Retired' : 'Page Not Found'; ?></h2>
                    <p class="text-muted mb-4 lead">
                        <?php if ($isRetiredPage): ?>
                            This business listing is no longer available. You can continue browsing active companies and categories below.
                        <?php else: ?>
                            We're sorry, but the page you're looking for doesn't exist or has been moved.
                        <?php endif; ?>
                    </p>

                    <!-- Search Suggestion -->
                    <div class="alert alert-info mb-4" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Looking for something?</strong> <?php echo $isRetiredPage ? 'Browse active listings or search for a replacement business.' : 'Try using the search bar above to find what you need.'; ?>
                    </div>

                    <!-- Error Details -->
                    <div class="text-start bg-light rounded p-3 mb-4 err-details-box">
                        <small class="text-muted">
                            <strong>Requested URL:</strong><br>
                            <code><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'unknown'); ?></code>
                        </small>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center mb-4">
                        <a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-house-fill me-2"></i>
                            Back to Home
                        </a>
                        <a href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-search me-2"></i>
                            Browse Companies
                        </a>
                    </div>

                    <!-- Help Text -->
                    <p class="text-muted small mb-0">
                        <i class="bi bi-question-circle me-1"></i>
                        If you believe this is an error, please <a href="<?php echo htmlspecialchars(url('/contact'), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none">contact us</a>.
                    </p>

                </div>
            </div>

            <!-- Suggested Links -->
            <div class="mt-5">
                <h5 class="text-center mb-4">Popular Pages</h5>
                <div class="row g-2">
                    <div class="col-md-6">
                        <a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none text-body btn btn-light w-100 text-start">
                            <i class="bi bi-house me-2"></i> Home
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none text-body btn btn-light w-100 text-start">
                            <i class="bi bi-building me-2"></i> Companies
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo htmlspecialchars(url('/blog'), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none text-body btn btn-light w-100 text-start">
                            <i class="bi bi-newspaper me-2"></i> Blog
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo htmlspecialchars(url('/about'), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none text-body btn btn-light w-100 text-start">
                            <i class="bi bi-info-circle me-2"></i> About Us
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

