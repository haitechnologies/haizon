<?php

declare(strict_types=1);

use App\Security\Roles;

if (!Roles::hasFullAccess($session_role_id)) {
    echo "<div class='alert alert-danger text-center mt-5'><h3>Access Denied</h3><p>This page is restricted to System and Super Administrators only.</p></div>";
    include(__DIR__ . '/admin_footer.php');
    exit;
}
