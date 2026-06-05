<?php
/**
 * 500 Internal Server Error Page
 * 
 * Public-facing error page when an internal server error occurs
 */

http_response_code(500);

require_once __DIR__ . '/../includes/helpers.php';

// Try to load error logger (won't fail if database unavailable)
try {
    require_once __DIR__ . '/../classes/frontend/ErrorLogger.php';
    
    // Log 500 error
    $errorLogger = new FrontendErrorLogger();
    $errorMessage = $_GET['message'] ?? 'Internal Server Error - Check logs for details';
    $errorId = $_GET['error_id'] ?? uniqid('ERR_' . date('YmdHis') . '_');
    $errorLogger->log500($errorMessage, $errorId);
} catch (Exception $e) {
    // Silently fail - don't break the error page if logging fails
}

// Set page meta
$pageTitle = '500 - Server Error | HaiPulse';
$pageDescription = 'An internal server error has occurred. Our team is working on it.';

// Ensure asset and link URLs resolve correctly when this page is accessed directly.
if (!isset($GLOBALS['basePath'])) {
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
    if ($scriptName !== '' && basename($scriptName) === 'index.php') {
        $basePath = dirname($scriptName);
    } else {
        $basePath = dirname(dirname($scriptName));
    }
    $basePath = $basePath === '/' ? '' : rtrim($basePath, '/');
    $GLOBALS['basePath'] = $basePath;
}

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
                        <svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" fill="#DC3545" class="bi bi-exclamation-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                        </svg>
                    </div>

                    <!-- Error Code & Message -->
                    <h1 class="display-4 fw-bold mb-2 err-500-code">500</h1>
                    <h2 class="h4 fw-semibold mb-3">Internal Server Error</h2>
                    <p class="text-muted mb-4 lead">
                        We're experiencing technical difficulties. Our team has been notified and is working to resolve the issue.
                    </p>

                    <!-- Status Alert -->
                    <div class="alert alert-danger mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>What Happened:</strong>
                        <p class="mb-0 mt-2 small text-start">
                            An unexpected error occurred while processing your request. We're investigating and will have this fixed shortly. 
                            Please try again in a few moments.
                        </p>
                    </div>

                    <!-- Diagnostic Info (if available) -->
                    <?php if (!empty($_GET['error_id'])): ?>
                    <div class="text-start bg-light rounded p-3 mb-4 err-details-box">
                        <small class="text-muted">
                            <strong>Error Reference ID:</strong><br>
                            <code><?php echo htmlspecialchars($_GET['error_id']); ?></code>
                            <p class="mb-0 mt-2">Please provide this ID when contacting support.</p>
                        </small>
                    </div>
                    <?php endif; ?>

                    <!-- What You Can Do -->
                    <div class="text-start bg-light rounded p-3 mb-4">
                        <strong class="d-block mb-2">What you can do:</strong>
                        <ul class="mb-0 ps-3 small">
                            <li>Refresh the page and try again</li>
                            <li>Check back in a few minutes</li>
                            <li>Clear your browser cache and cookies</li>
                            <li>Contact us if the problem persists</li>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center mb-4">
                        <a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-house-fill me-2"></i>
                            Back to Home
                        </a>
                        <button onclick="location.reload()" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-arrow-clockwise me-2"></i>
                            Refresh Page
                        </button>
                    </div>

                    <!-- Help Text -->
                    <p class="text-muted small mb-0">
                        <i class="bi bi-question-circle me-1"></i>
                        If this problem continues, please <a href="<?php echo htmlspecialchars(url('/contact'), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none">contact us</a> for assistance.
                    </p>

                </div>
            </div>

            <!-- Suggested Links -->
            <div class="mt-5">
                <h5 class="text-center mb-4">What's Working</h5>
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
                        <a href="<?php echo htmlspecialchars(url('/contact'), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none text-body btn btn-light w-100 text-start">
                            <i class="bi bi-envelope me-2"></i> Contact
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

