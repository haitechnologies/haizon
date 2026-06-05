<?php
/**
 * Page: Logout (NEW DESIGN)
 * Route: /logout
 * Description: Logout user and destroy session
 */

require_once __DIR__ . '/../config/session.php';
startFrontendSession();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';

clearFrontendAuthSession($project_pre ?? null);
session_regenerate_id(true);

header('Location: ' . url('/login?logged_out=1'));
exit;
