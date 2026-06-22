<?php

declare(strict_types=1);

require_once __DIR__ . '/admin_elements/error_handler_init.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/admin_elements/error_logger.php';

use App\Core\Session;

if (function_exists('custom_error_handler')) {
    set_error_handler('custom_error_handler');
}
if (function_exists('custom_exception_handler')) {
    set_exception_handler('custom_exception_handler');
}
if (function_exists('handle_fatal_error')) {
    register_shutdown_function('handle_fatal_error');
}

startDashboardSession();

if (!empty(Session::userId())) {
    header('Location: index.php');
} else {
    header('Location: login.php');
}
exit;
