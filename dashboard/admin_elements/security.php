<?php
	
use App\Core\DB;
// ============================================================================
	// SESSION SECURITY VALIDATION
	// ============================================================================
	
	// Check if user is logged in
	if ( !isset($_SESSION[$project_pre]['DASHBOARD']['user_id']) || $_SESSION[$project_pre]['DASHBOARD']['user_id']=='' || $_SESSION[$project_pre]['DASHBOARD']['user_id']=='0') 
	{ 
		$redirectParam = isset($_SERVER['REQUEST_URI']) ? '?redirect_to=' . urlencode($_SERVER['REQUEST_URI']) : '';
		header("Location:logout.php" . $redirectParam); 
		exit;
	}

	// Get current client IP
	function get_client_ip_address() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			return $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
		} else {
			return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		}
	}

	// Session timeout - 2 hours (7200 seconds)
	$session_timeout = 7200;
	
	if (isset($_SESSION[$project_pre]['DASHBOARD']['last_activity'])) {
		$inactive_time = time() - $_SESSION[$project_pre]['DASHBOARD']['last_activity'];
		
		if ($inactive_time > $session_timeout) {
			// Session expired due to inactivity
			$redirectParam = isset($_SERVER['REQUEST_URI']) ? '&redirect_to=' . urlencode($_SERVER['REQUEST_URI']) : '';
			session_unset();
			session_destroy();
			header("Location:login.php?session_expired=1" . $redirectParam);
			exit;
		}
	}
	
	// Update last activity time
	$_SESSION[$project_pre]['DASHBOARD']['last_activity'] = time();
	$currentDashboardPage = basename($_SERVER['PHP_SELF'] ?? '');
	$isLiveServer = function_exists('isRemote') ? isRemote() : false;

	// Role-based MFA policy (defaults to System Admin + Super Admin).
	$mfaRequiredRoleIds = [];
	$mfaRequiredRolesEnv = (string)($_ENV['MFA_REQUIRED_ROLE_IDS'] ?? getenv('MFA_REQUIRED_ROLE_IDS') ?: '1,2');
	foreach (explode(',', $mfaRequiredRolesEnv) as $roleChunk) {
		$roleId = (int)trim($roleChunk);
		if ($roleId > 0) {
			$mfaRequiredRoleIds[] = $roleId;
		}
	}
	$mfaRequiredRoleIds = array_values(array_unique($mfaRequiredRoleIds));
	$currentRoleId = (int)($_SESSION[$project_pre]['DASHBOARD']['role_id'] ?? 0);
	$requiresMfaByPolicy = $isLiveServer && in_array($currentRoleId, $mfaRequiredRoleIds, true);

	// Enforce MFA only on live server; local keeps MFA UI visible but login remains OTP-optional.
	if ($isLiveServer && isset($mysqli)) {
		$userIdForMfa = (int)($_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? 0);
		if ($userIdForMfa > 0) {
			$mfaStmt = $mysqli->prepare("SELECT mfa_totp_enabled, mfa_totp_secret FROM `" . DB::USERS . "` WHERE id = ? LIMIT 1");
			if ($mfaStmt) {
				$mfaStmt->bind_param('i', $userIdForMfa);
				$mfaStmt->execute();
				$mfaResult = $mfaStmt->get_result();
				$mfaRow = $mfaResult ? $mfaResult->fetch_assoc() : null;
				$mfaStmt->close();

				$mfaEnabled = !empty($mfaRow['mfa_totp_enabled']) && !empty($mfaRow['mfa_totp_secret']);
				$mfaVerifiedInSession = !empty($_SESSION[$project_pre]['DASHBOARD']['mfa_verified']);
				$mfaSetupWhitelist = ['mfa_settings.php', 'logout.php'];

				if ($requiresMfaByPolicy && !$mfaEnabled && !in_array($currentDashboardPage, $mfaSetupWhitelist, true)) {
					log_error('MFA policy enforcement redirect - setup required', 'WARNING', __FILE__, __LINE__, [
						'user_id' => $userIdForMfa,
						'role_id' => $currentRoleId,
						'page' => $currentDashboardPage,
						'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
					]);
					header('Location: mfa_settings.php?enforce=1');
					exit;
				}

				if ($mfaEnabled && !$mfaVerifiedInSession) {
					log_error('MFA session enforcement logout triggered', 'WARNING', __FILE__, __LINE__, [
						'user_id' => $userIdForMfa,
						'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
					]);
					session_unset();
					session_destroy();
					header('Location: login.php?message=' . urlencode('Please login again and complete two-factor authentication.'));
					exit;
				}
			}
		}
	}

	/**
	 * SESSION HIJACKING PROTECTION - User-Agent Validation
	 * 
	 * Validates that the session request is using the same User-Agent string
	 * where the session was originally created. This prevents session theft 
	 * if an attacker obtains a session cookie.
	 * 
	 * Advantage over IP validation: Mobile users can switch between WiFi/cellular
	 * networks without being logged out. User-Agent binding is sufficient for
	 * hijacking prevention without the false-positive rate of IP checking.
	 */
	if (isset($_SESSION[$project_pre]['DASHBOARD']['user_agent'])) {
		$current_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
		if ($_SESSION[$project_pre]['DASHBOARD']['user_agent'] !== $current_user_agent) {
			log_error('Session hijacking attempt detected - User Agent mismatch', 'CRITICAL', __FILE__, __LINE__, [
				'session_ua' => substr($_SESSION[$project_pre]['DASHBOARD']['user_agent'], 0, 100),
				'current_ua' => substr($current_user_agent, 0, 100),
				'user_id' => $_SESSION[$project_pre]['DASHBOARD']['user_id']
			]);
			$redirectParam = isset($_SERVER['REQUEST_URI']) ? '&redirect_to=' . urlencode($_SERVER['REQUEST_URI']) : '';
			session_unset();
			session_destroy();
			header("Location:login.php?session_expired=1" . $redirectParam);
			exit;
		}
	}
	
	// ============================================================================

// Legacy GRANTED cache removed. Use granted()/granted_() helpers for permission checks.


