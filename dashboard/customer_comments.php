<?php

declare(strict_types=1);

use App\Http\Controller\CustomerCommentController;
use App\Http\Request;

require_once __DIR__ . '/bootstrap.php';

$activeOrganizationId = dashboardRequireActiveOrganization();
$controller = $container->get(CustomerCommentController::class);
$response = $controller(Request::fromGlobals());
$response->send();
