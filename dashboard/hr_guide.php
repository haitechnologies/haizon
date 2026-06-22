<?php

declare(strict_types=1);

use App\Http\Controller\HrGuideController;
use App\Http\Request;

require_once __DIR__ . '/bootstrap.php';

$controller = $container->get(HrGuideController::class);
$response = $controller(Request::fromGlobals());
$response->send();
