<?php

declare(strict_types=1);

use App\Controller\CurrenciesController;

require_once __DIR__ . '/bootstrap.php';

$activeOrganizationId = dashboardRequireActiveOrganization();
$controller = $container->get(CurrenciesController::class);
$controller->handle();
