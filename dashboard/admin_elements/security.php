<?php
	
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


