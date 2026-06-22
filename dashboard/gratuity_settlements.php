<?php

declare(strict_types=1);

use App\Http\Controller\GratuitySettlementController;
use App\Http\Request;

require_once __DIR__ . '/bootstrap.php';

$activeOrganizationId = dashboardRequireActiveOrganization();
$controller = $container->get(GratuitySettlementController::class);
$response = $controller(Request::fromGlobals());
$response->send();
