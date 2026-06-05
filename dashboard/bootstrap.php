<?php

use App\Core\DB;
use App\Security\Roles;
use App\Core\DeletionManager;
use App\Security\SystemEntitlements;
use App\Service\SMTPMailer;
require_once __DIR__ . '/../config/session.php';
/*
|--------------------------------------------------------------------------
| Security Headers & HTTPS Enforcement
|--------------------------------------------------------------------------
| Comprehensive security headers to protect against common web vulnerabilities
*/

// HTTPS Enforcement (only in production)
$appEnv = strtolower((string)(getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '')));
$serverName = strtolower((string)($_SERVER['SERVER_NAME'] ?? 'localhost'));
$isLocalHost = in_array($serverName, ['127.0.0.1', 'localhost'], true);
$isProduction = ($appEnv === 'production') || ($appEnv === '' && !$isLocalHost);
if ($isProduction && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}

// Cache Control Headers
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", false);
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Security Headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Strict-Transport-Security (HSTS) - Only on HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

// Content-Security-Policy (CSP) - Strict but allows inline scripts/styles for compatibility
header("Content-Security-Policy: " . implode('; ', [
    "default-src 'self'",
    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com https://cdn.datatables.net",
    "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdn.datatables.net",
    "font-src 'self' https://fonts.gstatic.com data:",
    "img-src 'self' data: https:",
    "connect-src 'self'",
    "frame-ancestors 'none'",
    "base-uri 'self'",
    "form-action 'self'"
]));

// Permissions-Policy (Feature Policy) - Restrict browser features
header("Permissions-Policy: " . implode(', ', [
    "geolocation=()",
    "microphone=()",
    "camera=()",
    "payment=()",
    "usb=()",
    "magnetometer=()",
    "gyroscope=()",
    "accelerometer=()"
]));

session_cache_limiter("must-revalidate");
ob_start();

// Load Time Calculation
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start_page_time = $time;

/*
|--------------------------------------------------------------------------
| Secure Session Configuration
|--------------------------------------------------------------------------
| Configure session security settings before starting session
*/

// Secure session cookie settings
ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookie
ini_set('session.cookie_samesite', 'Strict'); // Prevent CSRF via cookies
ini_set('session.use_strict_mode', 1); // Reject uninitialized session IDs
ini_set('session.use_only_cookies', 1); // Only use cookies for session ID
ini_set('session.cookie_secure', $isProduction ? 1 : 0); // Secure flag (HTTPS only) in production

startDashboardSession();
header("Content-Type: text/html; charset=utf-8");
require_once('../config/globals.php');
require_once('../config/database.php');

// Ensure shared mail/database services can resolve the active DB handle in dashboard context.
if (!isset($GLOBALS['conn']) && isset($mysqli) && $mysqli instanceof mysqli) {
    $GLOBALS['conn'] = $mysqli;
}

// Initialize Dependency Injection Container
$container = \App\Core\Container::getInstance();

$container->register(\App\Core\Database::class, function () {
    return new \App\Core\Database();
});

$container->register(\App\Repository\UserRepository::class, function (\App\Core\Container $c) {
    return new \App\Repository\UserRepository($c->get(\App\Core\Database::class));
});

$container->register(\App\Repository\DepartmentRepository::class, function (\App\Core\Container $c) {
    return new \App\Repository\DepartmentRepository($c->get(\App\Core\Database::class));
});

$container->register(\App\Service\DepartmentService::class, function (\App\Core\Container $c) {
    return new \App\Service\DepartmentService(
        $c->get(\App\Repository\DepartmentRepository::class),
        $c->get(\App\Repository\UserRepository::class)
    );
});

$container->register(\App\Repository\DesignationRepository::class, function (\App\Core\Container $c) {
    return new \App\Repository\DesignationRepository($c->get(\App\Core\Database::class));
});

$container->register(\App\Service\DesignationService::class, function (\App\Core\Container $c) {
    return new \App\Service\DesignationService($c->get(\App\Repository\DesignationRepository::class));
});


$container->register(\App\Service\UserService::class, function (\App\Core\Container $c) {
    return new \App\Service\UserService($c->get(\App\Repository\UserRepository::class));
});

$container->register(\App\Repository\CustomerRepository::class, function (\App\Core\Container $c) {
    return new \App\Repository\CustomerRepository($c->get(\App\Core\Database::class));
});

$container->register(\App\Service\CustomerService::class, function (\App\Core\Container $c) {
    return new \App\Service\CustomerService($c->get(\App\Repository\CustomerRepository::class));
});

$container->register(\App\Service\DashboardService::class, function () {
    return new \App\Service\DashboardService();
});

$container->register(\App\Repository\InvoiceRepository::class, function (\App\Core\Container $c) {
    return new \App\Repository\InvoiceRepository($c->get(\App\Core\Database::class));
});

$container->register(\App\Service\InvoiceService::class, function (\App\Core\Container $c) {
    return new \App\Service\InvoiceService(
        $c->get(\App\Repository\InvoiceRepository::class),
        $c->get(\App\Repository\CustomerRepository::class),
        $c->get(\App\Core\Database::class)
    );
});

// Initialize dashboard error logging (only for dashboard pages)
require_once(__DIR__ . '/admin_elements/error_logger.php');

// Mark this request in backend coverage manifest even if no error occurs.
if (function_exists('backend_log_coverage_heartbeat')) {
    backend_log_coverage_heartbeat(['entrypoint' => 'page']);
}

// Register custom error handlers for dashboard
if (function_exists('custom_error_handler')) {
	set_error_handler('custom_error_handler');
}
if (function_exists('custom_exception_handler')) {
	set_exception_handler('custom_exception_handler');
}
if (function_exists('handle_fatal_error')) {
	register_shutdown_function('handle_fatal_error');
}

// Initialize Deletion Manager (centralized deletion handling)
\App\Core\DeletionManager::init($mysqli, $project_pre);

include('../config/images.php');
include('admin_elements/security.php');
include('admin_elements/grab_vars.php');

/*
|--------------------------------------------------------------------------
| 	SESSION VARIABLES
|--------------------------------------------------------------------------
|
*/

$session_role_id = $_SESSION[$project_pre]['DASHBOARD']['role_id'] ?? null;
$session_user_id = $_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? null;
$session_full_name = $_SESSION[$project_pre]['DASHBOARD']['full_name'] ?? '';
$session_email = $_SESSION[$project_pre]['DASHBOARD']['email'] ?? '';

if (!function_exists('dashboardGetSystemEntitlements')) {
    function dashboardGetSystemEntitlements(): array
    {
        global $project_pre;

        $default = SystemEntitlements::defaultEntitlements();
        $fromSession = $_SESSION[$project_pre]['DASHBOARD']['system_entitlements'] ?? null;

        if (!is_array($fromSession)) {
            return $default;
        }

        return array_replace($default, $fromSession);
    }
}

if (!function_exists('dashboardGetSubscriptionFeatures')) {
    function dashboardGetSubscriptionFeatures(): array
    {
        global $project_pre;

        $default = SystemEntitlements::defaultFeatures();
        $fromSession = $_SESSION[$project_pre]['DASHBOARD']['subscription_features'] ?? null;

        if (!is_array($fromSession)) {
            return $default;
        }

        return array_replace($default, $fromSession);
    }
}

if (!function_exists('dashboardHasSystemAccess')) {
    function dashboardHasSystemAccess(string $systemKey): bool
    {
        $systemKey = strtolower(trim($systemKey));
        if ($systemKey === '') {
            return true;
        }

        $entitlements = dashboardGetSystemEntitlements();
        if (!array_key_exists($systemKey, $entitlements)) {
            return true;
        }

        return (bool)$entitlements[$systemKey];
    }
}

if (!function_exists('dashboardCanCreateOrganizations')) {
    function dashboardCanCreateOrganizations(): bool
    {
        $features = dashboardGetSubscriptionFeatures();
        return !empty($features['can_create_organizations']) && $features['can_create_organizations'] !== '0';
    }
}

if (!function_exists('dashboardCanInviteMembers')) {
    function dashboardCanInviteMembers(): bool
    {
        $features = dashboardGetSubscriptionFeatures();
        return !empty($features['can_invite_members']) && $features['can_invite_members'] !== '0';
    }
}

if (!function_exists('dashboardMaxOrganizations')) {
    function dashboardMaxOrganizations(): int
    {
        $features = dashboardGetSubscriptionFeatures();
        return (int)($features['max_organizations'] ?? 0);
    }
}

if (!function_exists('dashboardMaxTeamMembers')) {
    function dashboardMaxTeamMembers(): int
    {
        $features = dashboardGetSubscriptionFeatures();
        return (int)($features['max_team_members'] ?? 0);
    }
}

if (!function_exists('dashboardOrganizationActiveMemberCount')) {
    function dashboardOrganizationActiveMemberCount(int $organizationId): int
    {
        global $mysqli;

        if ($organizationId <= 0 || !$mysqli instanceof mysqli) {
            return 0;
        }

        return OrganizationMembershipManager::countActiveMembers($mysqli, $organizationId);
    }
}

if (!function_exists('dashboardUserBelongsToOrganization')) {
    function dashboardUserBelongsToOrganization(int $organizationId, ?int $userId = null): bool
    {
        global $mysqli, $session_user_id;

        if (!($mysqli instanceof mysqli)) {
            return false;
        }

        $resolvedUserId = $userId !== null ? (int)$userId : (int)$session_user_id;
        if ($organizationId <= 0 || $resolvedUserId <= 0) {
            return false;
        }

        return OrganizationMembershipManager::hasActiveMembership($mysqli, $organizationId, $resolvedUserId);
    }
}

if (!function_exists('dashboardGetAccessibleOrganizations')) {
    function dashboardGetAccessibleOrganizations(?int $userId = null): array
    {
        global $mysqli, $session_user_id;

        if (!($mysqli instanceof mysqli)) {
            return [];
        }

        $resolvedUserId = $userId !== null ? (int)$userId : (int)$session_user_id;
        if ($resolvedUserId <= 0 && !Roles::currentUserHasFullAccess()) {
            return [];
        }

        $organizations = [];
        if (Roles::currentUserHasFullAccess()) {
            $query = "SELECT id, warehouse_name, slug, status FROM `" . DB::ORGANIZATIONS . "` ORDER BY warehouse_name ASC";
            $result = $mysqli->query($query);
            while ($row = $result instanceof mysqli_result ? $result->fetch_assoc() : null) {
                $organizations[] = $row;
            }
            if ($result instanceof mysqli_result) {
                $result->free();
            }

            return $organizations;
        }

        $stmt = $mysqli->prepare(
            "SELECT o.id, o.warehouse_name, o.slug, o.status
             FROM `" . DB::ORGANIZATIONS . "` o
             INNER JOIN `" . DB::ORGANIZATION_MEMBERSHIPS . "` om ON om.organization_id = o.id
             WHERE om.user_id = ?
               AND om.membership_status = 'active'
             ORDER BY o.warehouse_name ASC"
        );
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $resolvedUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result ? $result->fetch_assoc() : null) {
            $organizations[] = $row;
        }
        $stmt->close();

        return $organizations;
    }
}

if (!function_exists('dashboardSetActiveOrganization')) {
    function dashboardSetActiveOrganization(int $organizationId, ?int $userId = null): bool
    {
        global $project_pre;

        if ($organizationId <= 0) {
            unset($_SESSION[$project_pre]['DASHBOARD']['organization_id']);
            unset($_SESSION[$project_pre]['DASHBOARD']['organization_name']);
            return false;
        }

        foreach (dashboardGetAccessibleOrganizations($userId) as $organization) {
            if ((int)($organization['id'] ?? 0) !== $organizationId) {
                continue;
            }

            $_SESSION[$project_pre]['DASHBOARD']['organization_id'] = $organizationId;
            $_SESSION[$project_pre]['DASHBOARD']['organization_name'] = (string)($organization['warehouse_name'] ?? '');
            return true;
        }

        return false;
    }
}

if (!function_exists('dashboardGetActiveOrganizationId')) {
    function dashboardGetActiveOrganizationId(bool $autoResolve = true): int
    {
        global $project_pre;

        $currentOrganizationId = (int)($_SESSION[$project_pre]['DASHBOARD']['organization_id'] ?? 0);
        if ($currentOrganizationId > 0 && dashboardSetActiveOrganization($currentOrganizationId)) {
            return $currentOrganizationId;
        }

        if (!$autoResolve) {
            return 0;
        }

        $organizations = dashboardGetAccessibleOrganizations();
        $fallbackOrganizationId = (int)($organizations[0]['id'] ?? 0);
        if ($fallbackOrganizationId > 0 && dashboardSetActiveOrganization($fallbackOrganizationId)) {
            return $fallbackOrganizationId;
        }

        return 0;
    }
}

if (!function_exists('dashboardGetActiveOrganizationName')) {
    function dashboardGetActiveOrganizationName(): string
    {
        global $project_pre;

        $activeOrganizationId = dashboardGetActiveOrganizationId();
        if ($activeOrganizationId <= 0) {
            return '';
        }

        $cachedName = trim((string)($_SESSION[$project_pre]['DASHBOARD']['organization_name'] ?? ''));
        if ($cachedName !== '') {
            return $cachedName;
        }

        foreach (dashboardGetAccessibleOrganizations() as $organization) {
            if ((int)($organization['id'] ?? 0) === $activeOrganizationId) {
                $resolvedName = trim((string)($organization['warehouse_name'] ?? ''));
                $_SESSION[$project_pre]['DASHBOARD']['organization_name'] = $resolvedName;
                return $resolvedName;
            }
        }

        return '';
    }
}

if (!function_exists('dashboardUserIsOrganizationOwner')) {
    function dashboardUserIsOrganizationOwner(int $organizationId, int $userId): bool
    {
        global $mysqli;

        if ($organizationId <= 0 || $userId <= 0) {
            return false;
        }

        if (!($mysqli instanceof mysqli)) {
            return false;
        }

        $stmt = $mysqli->prepare(
            "SELECT id FROM `" . DB::ORGANIZATIONS . "` WHERE id = ? AND owner_user_id = ? LIMIT 1"
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ii', $organizationId, $userId);
        $stmt->execute();
        $isOwner = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $isOwner;
    }
}

if (!function_exists('dashboardRequireActiveOrganization')) {
    function dashboardRequireActiveOrganization(
        bool $autoResolve = true,
        string $redirectTo = 'select_organization.php',
        string $message = 'Please select an organization to continue.'
    ): int {
        $activeOrganizationId = dashboardGetActiveOrganizationId($autoResolve);
        if ($activeOrganizationId > 0) {
            return $activeOrganizationId;
        }

        $separator = (strpos($redirectTo, '?') === false) ? '?' : '&';
        header('Location:' . $redirectTo . $separator . 'error_message=' . urlencode($message));
        exit;
    }
}

if (!function_exists('dashboardCreateOrganizationInvite')) {
    function dashboardCreateOrganizationInvite(int $organizationId, string $email, ?int $roleId = null): array
    {
        global $mysqli, $session_user_id;

        if (!dashboardCanInviteMembers()) {
            return ['success' => false, 'message' => 'Your subscription does not allow inviting members.'];
        }

        $maxTeamMembers = dashboardMaxTeamMembers();
        $activeMembers = dashboardOrganizationActiveMemberCount($organizationId);
        if ($maxTeamMembers > 0 && $activeMembers >= $maxTeamMembers) {
            return ['success' => false, 'message' => 'Your subscription team-member limit has been reached.'];
        }

        $result = OrganizationMembershipManager::createInvite($mysqli, $organizationId, (int)$session_user_id, $email, $roleId);
        if (!empty($result['success'])) {
            $queueResult = dashboardQueueOrganizationInviteEmail(
                $organizationId,
                (string)($result['email'] ?? $email),
                (string)($result['invite_token'] ?? ''),
                'created',
                (int)($result['invite_id'] ?? 0)
            );
            $result['email_queued'] = !empty($queueResult['success']);
            if (empty($queueResult['success']) && !empty($queueResult['message'])) {
                $result['message'] = rtrim((string)$result['message'], '.') . '. Email queue warning: ' . $queueResult['message'];
            }
        }

        return $result;
    }
}

if (!function_exists('dashboardAcceptOrganizationInvite')) {
    function dashboardAcceptOrganizationInvite(string $token): array
    {
        global $mysqli, $session_user_id;

        return OrganizationMembershipManager::acceptInviteByToken($mysqli, $token, (int)$session_user_id);
    }
}

if (!function_exists('dashboardResendOrganizationInvite')) {
    function dashboardResendOrganizationInvite(int $organizationId, int $inviteId, ?int $roleId = null): array
    {
        global $mysqli, $session_user_id;

        if (!dashboardCanInviteMembers()) {
            return ['success' => false, 'message' => 'Your subscription does not allow inviting members.'];
        }

        $result = OrganizationMembershipManager::resendInvite(
            $mysqli,
            $organizationId,
            $inviteId,
            (int)$session_user_id,
            $roleId,
            7,
            Roles::currentUserHasFullAccess()
        );

        if (!empty($result['success'])) {
            $queueResult = dashboardQueueOrganizationInviteEmail(
                $organizationId,
                (string)($result['email'] ?? ''),
                (string)($result['invite_token'] ?? ''),
                'resent',
                (int)($result['invite_id'] ?? 0)
            );
            $result['email_queued'] = !empty($queueResult['success']);
            if (empty($queueResult['success']) && !empty($queueResult['message'])) {
                $result['message'] = rtrim((string)$result['message'], '.') . '. Email queue warning: ' . $queueResult['message'];
            }
        }

        return $result;
    }
}

if (!function_exists('dashboardRevokeOrganizationInvite')) {
    function dashboardRevokeOrganizationInvite(int $organizationId, int $inviteId): array
    {
        global $mysqli, $session_user_id;

        if (!dashboardCanInviteMembers()) {
            return ['success' => false, 'message' => 'Your subscription does not allow inviting members.'];
        }

        return OrganizationMembershipManager::revokeInvite(
            $mysqli,
            $organizationId,
            $inviteId,
            (int)$session_user_id,
            Roles::currentUserHasFullAccess()
        );
    }
}

if (!function_exists('dashboardSendOrganizationInviteEmailNow')) {
    function dashboardSendOrganizationInviteEmailNow(int $organizationId, int $inviteId, int $queueId): array
    {
        global $mysqli;

        if (!dashboardCanInviteMembers()) {
            return ['success' => false, 'message' => 'Your subscription does not allow inviting members.'];
        }

        if ($organizationId <= 0 || $inviteId <= 0 || $queueId <= 0) {
            return ['success' => false, 'message' => 'Invalid organization invite email request.'];
        }

        $inviteStmt = $mysqli->prepare(
            "SELECT invite_token FROM `" . DB::ORGANIZATION_INVITES . "` WHERE id = ? AND organization_id = ? LIMIT 1"
        );
        if (!$inviteStmt) {
            return ['success' => false, 'message' => 'Unable to validate invite details.'];
        }

        $inviteStmt->bind_param('ii', $inviteId, $organizationId);
        $inviteStmt->execute();
        $inviteRow = $inviteStmt->get_result()->fetch_assoc();
        $inviteStmt->close();

        $inviteToken = trim((string)($inviteRow['invite_token'] ?? ''));
        if ($inviteToken === '') {
            return ['success' => false, 'message' => 'Invite record not found.'];
        }

        $queueStmt = $mysqli->prepare(
            "SELECT id, status, recipient_email, recipient, subject, body, headers, retries, max_retries
             FROM `" . DB::EMAIL_QUEUE . "`
             WHERE id = ?
             LIMIT 1"
        );
        if (!$queueStmt) {
            return ['success' => false, 'message' => 'Unable to validate queued invite email.'];
        }

        $queueStmt->bind_param('i', $queueId);
        $queueStmt->execute();
        $queueRow = $queueStmt->get_result()->fetch_assoc();
        $queueStmt->close();

        if (!$queueRow) {
            return ['success' => false, 'message' => 'Queued invite email not found.'];
        }

        $headers = [];
        if (!empty($queueRow['headers'])) {
            $decodedHeaders = json_decode((string)$queueRow['headers'], true);
            if (is_array($decodedHeaders)) {
                $headers = $decodedHeaders;
            }
        }

        if (
            trim((string)($headers['X-Invite-Token'] ?? '')) !== $inviteToken
            || (int)($headers['X-Organization-Id'] ?? 0) !== $organizationId
            || trim((string)($headers['X-Invite-Type'] ?? '')) !== 'organization'
        ) {
            return ['success' => false, 'message' => 'Queued email does not belong to this invite.'];
        }

        $status = strtolower(trim((string)($queueRow['status'] ?? '')));
        if (!in_array($status, ['pending', 'retry', 'queued', 'failed'], true)) {
            return ['success' => false, 'message' => 'Only pending/retry/queued/failed invite emails can be sent now.'];
        }

        $to = trim((string)($queueRow['recipient_email'] ?? ''));
        if ($to === '') {
            $to = trim((string)($queueRow['recipient'] ?? ''));
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invite email recipient is invalid.'];
        }

        $subject = (string)($queueRow['subject'] ?? 'Organization Invite');
        $body = (string)($queueRow['body'] ?? '');

        try {
            $mailer = new SMTPMailer();
            $sent = (bool)$mailer->send($to, $subject, $body, $headers);
            if ($sent) {
                $updateStmt = $mysqli->prepare(
                    "UPDATE `" . DB::EMAIL_QUEUE . "`
                     SET status = 'sent', sent_at = NOW(), updated_at = NOW(), failed_reason = NULL
                     WHERE id = ?"
                );
                if ($updateStmt) {
                    $updateStmt->bind_param('i', $queueId);
                    $updateStmt->execute();
                    $updateStmt->close();
                }

                return ['success' => true, 'message' => 'Invite email sent successfully.'];
            }

            $retries = (int)($queueRow['retries'] ?? 0) + 1;
            $maxRetries = (int)($queueRow['max_retries'] ?? 3);
            if ($maxRetries <= 0) {
                $maxRetries = 3;
            }
            $nextStatus = $retries >= $maxRetries ? 'failed' : 'retry';
            $lastError = method_exists($mailer, 'getLastError') ? (string)$mailer->getLastError() : 'Manual send failed';

            $updateStmt = $mysqli->prepare(
                "UPDATE `" . DB::EMAIL_QUEUE . "`
                 SET status = ?, retries = ?, attempts = ?, failed_reason = ?, updated_at = NOW()
                 WHERE id = ?"
            );
            if ($updateStmt) {
                $updateStmt->bind_param('siisi', $nextStatus, $retries, $retries, $lastError, $queueId);
                $updateStmt->execute();
                $updateStmt->close();
            }

            return ['success' => false, 'message' => 'Invite email send failed.'];
        } catch (Throwable $e) {
            $retries = (int)($queueRow['retries'] ?? 0) + 1;
            $maxRetries = (int)($queueRow['max_retries'] ?? 3);
            if ($maxRetries <= 0) {
                $maxRetries = 3;
            }
            $nextStatus = $retries >= $maxRetries ? 'failed' : 'retry';
            $lastError = substr((string)$e->getMessage(), 0, 1000);

            $updateStmt = $mysqli->prepare(
                "UPDATE `" . DB::EMAIL_QUEUE . "`
                 SET status = ?, retries = ?, attempts = ?, failed_reason = ?, updated_at = NOW()
                 WHERE id = ?"
            );
            if ($updateStmt) {
                $updateStmt->bind_param('siisi', $nextStatus, $retries, $retries, $lastError, $queueId);
                $updateStmt->execute();
                $updateStmt->close();
            }

            return ['success' => false, 'message' => 'Invite email send encountered an exception.'];
        }
    }
}

if (!function_exists('dashboardQueueOrganizationInviteEmail')) {
    function dashboardQueueOrganizationInviteEmail(int $organizationId, string $recipientEmail, string $inviteToken, string $mode = 'created', int $inviteId = 0): array
    {
        global $mysqli, $admin_base_url, $session_user_id;

        $recipientEmail = strtolower(trim($recipientEmail));
        $inviteToken = trim($inviteToken);
        if (!($mysqli instanceof mysqli)) {
            return ['success' => false, 'message' => 'Database connection unavailable.'];
        }
        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid invite email address.'];
        }
        if ($organizationId <= 0 || $inviteToken === '') {
            return ['success' => false, 'message' => 'Missing organization invite details.'];
        }

        $organizationName = 'your organization';
        $organizationStmt = $mysqli->prepare("SELECT warehouse_name FROM `" . DB::ORGANIZATIONS . "` WHERE id = ? LIMIT 1");
        if ($organizationStmt) {
            $organizationStmt->bind_param('i', $organizationId);
            $organizationStmt->execute();
            $organizationRow = $organizationStmt->get_result()->fetch_assoc();
            $organizationStmt->close();
            if (!empty($organizationRow['warehouse_name'])) {
                $organizationName = (string)$organizationRow['warehouse_name'];
            }
        }

        $inviterName = 'Team Admin';
        $inviterStmt = $mysqli->prepare("SELECT full_name FROM `" . DB::USERS . "` WHERE id = ? LIMIT 1");
        if ($inviterStmt) {
            $inviterStmt->bind_param('i', $session_user_id);
            $inviterStmt->execute();
            $inviterRow = $inviterStmt->get_result()->fetch_assoc();
            $inviterStmt->close();
            if (!empty($inviterRow['full_name'])) {
                $inviterName = (string)$inviterRow['full_name'];
            }
        }

        $acceptUrl = rtrim((string)$admin_base_url, '/') . '/organization_accept_invite.php?token=' . rawurlencode($inviteToken);
        $subjectPrefix = $mode === 'resent' ? 'Reminder:' : 'You are invited to join';
        $subject = $subjectPrefix . ' ' . $organizationName;

        $body = '<p>Hello,</p>'
            . '<p><strong>' . htmlspecialchars($inviterName, ENT_QUOTES, 'UTF-8') . '</strong> has invited you to join <strong>'
            . htmlspecialchars($organizationName, ENT_QUOTES, 'UTF-8') . '</strong> on HAIPULSE.</p>'
            . '<p><a href="' . htmlspecialchars($acceptUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:10px 16px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:4px;">Accept Organization Invite</a></p>'
            . '<p>If the button does not work, copy this link into your browser:<br>'
            . htmlspecialchars($acceptUrl, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>This invite link will expire in 7 days.</p>';

        $headers = [
            'X-Invite-Type' => 'organization',
            'X-Organization-Id' => (string)$organizationId,
            'X-Invite-Mode' => $mode,
            'X-Invite-Id' => (string)max(0, $inviteId),
            'X-Invite-Token' => $inviteToken,
        ];

        $queue = new EmailQueue($mysqli);
        $queueId = $queue->enqueue($recipientEmail, $subject, $body, $headers, 1);

        if (!$queueId) {
            return ['success' => false, 'message' => 'Unable to queue invite email at this time.'];
        }

        return ['success' => true, 'queue_id' => (int)$queueId];
    }
}

$entitlementCacheTtl = 300;
$entitlementsCachedAt = (int)($_SESSION[$project_pre]['DASHBOARD']['system_entitlements_cached_at'] ?? 0);
$entitlementExpired = ($entitlementsCachedAt <= 0) || ((time() - $entitlementsCachedAt) >= $entitlementCacheTtl);

if ($entitlementExpired) {
    $dashboardSession = $_SESSION[$project_pre]['DASHBOARD'] ?? [];
    $resolvedFeatures = SystemEntitlements::resolveFeatureSnapshotForDashboardUser($mysqli, is_array($dashboardSession) ? $dashboardSession : []);
    $resolvedEntitlements = SystemEntitlements::resolveForDashboardUser($mysqli, is_array($dashboardSession) ? $dashboardSession : []);
    $_SESSION[$project_pre]['DASHBOARD']['subscription_features'] = $resolvedFeatures;
    $_SESSION[$project_pre]['DASHBOARD']['system_entitlements'] = $resolvedEntitlements;
    $_SESSION[$project_pre]['DASHBOARD']['system_entitlements_cached_at'] = time();
}
