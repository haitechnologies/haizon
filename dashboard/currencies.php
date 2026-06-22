<?php

declare(strict_types=1);

use App\Http\Controller\CurrencyController;
use App\Http\Request;

require_once __DIR__ . '/bootstrap.php';

$activeOrganizationId = dashboardRequireActiveOrganization();
$controller = $container->get(CurrencyController::class);
$response = $controller(Request::fromGlobals());
$response->send();
