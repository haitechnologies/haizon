<?php

use App\Core\DB;
use App\Core\Session;

include('admin_elements/admin_header.php');

$module = 'alerts';
$module_caption = 'Alert';
$error_message = '';
$success_message = '';

// Mark as Read (CSRF protected and user-scoped)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }

    $user_id = Session::userId() ?? 0;

    $stmt = $mysqli->prepare("UPDATE " . DB::ALERTS . " SET is_read=1 WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'hide_add_button' => true,
    'thead' => '<th width="40">SR.</th><th>Alert</th><th>Type</th><th>Created</th><th width="100" class="col-center">Action</th>',
    'columns' => [
        ['data' => 0, 'orderable' => false, 'searchable' => false],
        ['data' => 1],
        ['data' => 2, 'orderable' => false, 'searchable' => false],
        ['data' => 3, 'orderable' => false, 'searchable' => false],
        ['data' => 4, 'orderable' => false, 'searchable' => false],
    ],
    'searching' => true,
    'page_length' => 10,
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
