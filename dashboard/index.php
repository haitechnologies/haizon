<?php

declare(strict_types=1);

if (!function_exists('dashboardLogSeverity')) {
    function dashboardLogSeverity($entry) {
        $entry = strtoupper((string)$entry);
        if (strpos($entry, 'CRITICAL') !== false || strpos($entry, 'FATAL') !== false) {
            return 'critical';
        }
        if (strpos($entry, 'ERROR') !== false || strpos($entry, 'EXCEPTION') !== false) {
            return 'error';
        }
        if (strpos($entry, 'WARNING') !== false || strpos($entry, 'WARN') !== false) {
            return 'warning';
        }
        if (strpos($entry, 'NOTICE') !== false) {
            return 'notice';
        }
        if (strpos($entry, 'DEBUG') !== false) {
            return 'debug';
        }
        return 'info';
    }
}

include('admin_elements/admin_header.php');

$module = 'dashboard';
$module_caption = 'Dashboard';
$error_message = '';
$success_message = '';

use App\Core\Container;
use App\Service\DashboardService;

// Resolve DashboardService from Container
$container = Container::getInstance();
$dashboardService = $container->get(DashboardService::class);

// Compute statistics and fetch dashboard metrics
$data = $dashboardService->getDashboardData(
    (int)($unread_error_logs_count ?? 0),
    (int)($frontend_error_logs_count ?? 0),
    (string)($_GET['view'] ?? 'compact')
);

// Extract computed data to scope to support template variables
extract($data);

// Render the view template
require __DIR__ . '/views/index.view.php';