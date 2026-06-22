<?php

declare(strict_types=1);

use App\Core\DB;
use App\Core\Session;
use App\Security\Roles;

include('admin_elements/admin_header.php');
Roles::requireSystemAdmin();

$module = 'authentication_activity';
$module_caption = 'Authentication Activity';
$error_message = '';
$success_message = '';
$hide_add_button = true;

if (($action == "delete_$module" && !empty($id))) {
    if (Roles::isSystemAdmin(Session::roleId())) {
        $id = intval($id);

        $stmt = $mysqli->prepare("DELETE FROM " . DB::AUTHENTICATION_ACTIVITY . " WHERE id = ?");
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $stmt->close();
    }

    if ($result) {
        $success_message = "$module_caption Record Deleted Successfully.";
    } else {
        $error_message = "Action denied. You are not authorized to delete this record.";
    }
}

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'hide_add_button' => true,
    'thead' => '
        <th>ID</th>
        <th>User</th>
        <th>Activity Type</th>
        <th>IP Address</th>
        <th>Time</th>
        <th>Actions</th>
    ',
    'columns' => [
        ['data' => 0],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4],
        ['data' => 5, 'orderable' => false, 'searchable' => false],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 10,
    'extra_js' => "
        var tableSelector = '#grid-' + '$module';
        $(tableSelector).closest('.card').find('.card-body').prepend('<input type=\"hidden\" name=\"csrf_token\" value=\"' + (window.HAI_CSRF_TOKEN || '') + '\">');
    ",
];

$listingConfig['extra_js'] = "
    // Override ajax data to pass session info
    $(document).ready(function() {
        var origInit = window.HAIDatatableInitializer;
        // Session data is passed via the dispatcher by default
    });
";

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
