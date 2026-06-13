<?php

declare(strict_types=1);

use App\Controller\BanksController;

require_once __DIR__ . '/bootstrap.php';

$activeOrganizationId = dashboardRequireActiveOrganization();
$controller = $container->get(BanksController::class);
$controller->handle();
