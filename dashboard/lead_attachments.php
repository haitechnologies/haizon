<?php

declare(strict_types=1);

use App\Http\Controller\LeadAttachmentController;
use App\Http\Request;

require_once __DIR__ . '/bootstrap.php';

$activeOrganizationId = dashboardRequireActiveOrganization();
$controller = $container->get(LeadAttachmentController::class);
$response = $controller(Request::fromGlobals());
$response->send();
