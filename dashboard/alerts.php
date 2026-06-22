<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Http\Request;
use App\Http\Controller\AlertController;

$request = Request::fromGlobals();
$controller = $container->get(AlertController::class);
$response = $controller($request);
$response->send();
