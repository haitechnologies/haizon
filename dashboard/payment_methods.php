<?php
declare(strict_types=1);
include('admin_elements/admin_header.php');
$activeOrganizationId = dashboardRequireActiveOrganization();
$controller = $container->get(\App\Controller\PaymentMethodsController::class);
$controller->handle();
