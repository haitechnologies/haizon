<?php

declare(strict_types=1);

use App\Http\Request;
use App\Http\Response;

include('admin_elements/admin_header.php');

$response = $container->get(\App\Http\Controller\SetupGroupController::class)(Request::fromGlobals());

if ($response->getStatusCode() >= 300 && $response->getStatusCode() < 400) {
    foreach ($response->getHeaders() as $name => $value) {
        header("$name: $value");
    }
    exit;
}

echo $response->getBody();
include('admin_elements/admin_footer.php');
