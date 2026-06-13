<?php
require_once __DIR__ . '/../config/session.php';
startDashboardSession();
include('../config/globals.php');
include('../config/database.php');
include(__DIR__ . '/admin_elements/error_logger.php');
set_error_handler('custom_error_handler');
set_exception_handler('custom_exception_handler');
register_shutdown_function('handle_fatal_error');

	clearDashboardAuthSession($project_pre ?? null);
	session_regenerate_id(true);

	$redirectTo = isset($_GET['redirect_to']) ? '?redirect_to=' . urlencode($_GET['redirect_to']) : '';
	header("Location:login.php" . $redirectTo);