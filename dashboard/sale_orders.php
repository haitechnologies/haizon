<?php

declare(strict_types=1);

use App\Http\Controller\SaleOrderController;
use App\Http\Request;

require_once __DIR__ . '/bootstrap.php';

$activeOrganizationId = dashboardRequireActiveOrganization();
$controller = $container->get(SaleOrderController::class);
$response = $controller(Request::fromGlobals());
$response->send();
