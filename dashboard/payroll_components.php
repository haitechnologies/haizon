<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Http\Request;
use App\Http\Controller\PayrollComponentController;

$request = Request::fromGlobals();
$controller = $container->get(PayrollComponentController::class);
$response = $controller($request);
$response->send();
