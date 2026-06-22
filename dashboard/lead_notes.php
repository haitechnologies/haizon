<?php

declare(strict_types=1);

use App\Http\Controller\LeadNoteController;
use App\Http\Request;

require_once __DIR__ . '/bootstrap.php';

$activeOrganizationId = dashboardRequireActiveOrganization();
$controller = $container->get(LeadNoteController::class);
$response = $controller(Request::fromGlobals());
$response->send();
