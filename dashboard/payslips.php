<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Security\Roles;

// Payslips are batch-generated from payroll runs, not created individually.
// Redirect to the listing page.
header('Location: listing_payslips.php');
exit;
