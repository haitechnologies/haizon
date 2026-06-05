<?php
/**
 * 403 Access Forbidden Error Page
 * 
 * Public-facing error page when user doesn't have permission to access a resource
 */

http_response_code(403);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/frontend/ErrorLogger.php';

// Log 403 error
$errorLogger = new FrontendErrorLogger();
$reason = 'Access Denied to Protected Resource';
if (!isset($_SESSION['project_pre']['FRONTEND']['user_id'])) {
    $reason = 'Not Authenticated';
}
$errorLogger->log403($_SERVER['REQUEST_URI'] ?? 'unknown', $reason);

// Set page meta
$pageTitle = '403 - Access Forbidden | HaiPulse';
$pageDescription = 'You do not have permission to access this resource.';

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
                        <svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" fill="#DC3545" class="bi bi-shield-exclamation" viewBox="0 0 16 16">
                            <path d="M5.338 1.59a61.748 61.748 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.5.12.04.243.086.371.136.18.08.354.143.52.188.869.305 1.921.573 3.851.573s2.982-.268 3.851-.573c.166-.045.34-.108.52-.188.128-.05.251-.096.371-.136.241-.08.547-.256.893-.5a10.728 10.728 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.856C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.255-.89.255s-.61-.123-.89-.255a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.456 62.456 0 0 1 5.072.56z"/>
                            <path d="M8 4.754a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5zM7.25 8a.75.75 0 1 1 1.5 0v3a.75.75 0 1 1-1.5 0V8z"/>
                        </svg>
                    </div>

                    <!-- Error Code & Message -->
                    <h1 class="display-4 fw-bold mb-2 err-500-code">403</h1>
                    <h2 class="h4 fw-semibold mb-3">Access Forbidden</h2>
                    <p class="text-muted mb-4 lead">
                        You don't have permission to access this resource. Your access has been restricted.
                    </p>

                    <!-- Reason Alert -->
                    <div class="alert alert-warning mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Why you see this:</strong>
                        <ul class="mb-0 mt-2 text-start">
                            <li>The page may be restricted to premium members only</li>
                            <li>You may not have verified your account</li>
                            <li>Your access may have been temporarily suspended</li>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center mb-4">
                        <a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-house-fill me-2"></i>
                            Back to Home
                        </a>
                        <?php if (!isset($_SESSION['project_pre']['FRONTEND']['user_id'])): ?>
                            <a href="<?php echo htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Sign In
                            </a>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars(url('/contact'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary btn-lg">
                                <i class="bi bi-envelope me-2"></i>
                                Contact Support
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Help Text -->
                    <p class="text-muted small mb-0">
                        <i class="bi bi-question-circle me-1"></i>
                        If you believe you should have access, please <a href="<?php echo htmlspecialchars(url('/contact'), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none">contact our support team</a>.
                    </p>

                </div>
            </div>

            <!-- Account Status Info -->
            <?php 
            require_once __DIR__ . '/../classes/frontend/FrontendUsers.php';
            $frontendUsers = new FrontendUsers($conn);
            
            if (isset($_SESSION['project_pre']['FRONTEND']['user_id'])):
                $userId = $_SESSION['project_pre']['FRONTEND']['user_id'];
                $user = $frontendUsers->getById($userId);
                if ($user):
            ?>
            <div class="alert alert-info mt-4 text-start">
                <strong>Your Account Status:</strong>
                <ul class="mb-0 small mt-2">
                    <li><strong>Username:</strong> <?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></li>
                    <li>
                        <strong>Email:</strong>
                        <?php if (!empty($user['email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </li>
                    <li><strong>Verified:</strong> <span class="badge bg-<?php echo ($user['verified'] == 1) ? 'success' : 'warning'; ?>">
                        <?php echo ($user['verified'] == 1) ? 'Yes' : 'No'; ?></span></li>
                </ul>
            </div>
            <?php 
                endif;
            else:
            ?>
            <div class="alert alert-info mt-4">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Not signed in?</strong> 
                <a href="<?php echo htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none">Sign in to your account</a> to see if the page is accessible to you.
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

