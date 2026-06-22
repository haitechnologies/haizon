<?php
declare(strict_types=1);
use App\Http\Controller\ShippingAdviceController;
use App\Http\Request;
require_once __DIR__ . '/bootstrap.php';
$activeOrganizationId = dashboardRequireActiveOrganization();
$controller = $container->get(ShippingAdviceController::class);
$response = $controller(Request::fromGlobals());
$response->send();
