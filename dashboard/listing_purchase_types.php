<?php

use App\Core\DB;

include('admin_elements/admin_header.php');

$module = 'purchase_types';
$module_caption = 'Purchase Type';
$tbl_name = DB::DOCUMENT_TYPES;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

// DELETE
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {
    if (is_SuperAdmin()) {
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND context='purchase'");
    } else {
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND context='purchase' AND created_by ='" . Session::userId() . "'");
    }

    if ($mysqli->affected_rows > 0) {
        $success_message = "Item deleted successfully.";
        flash_success($success_message);
        header("Location:listing_$module.php");
    } else {
        $error_message = "Action denied. You are not authorized to delete this record.";
    }
}

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="40">SR.</th>
        <th>PURCHASE TYPE</th>
        <th>DESCRIPTION</th>
        <th width="90">CREATED AT</th>
        <th width="50">STATUS</th>
        <th width="90">ACTIONS</th>
    ',
    'columns' => [
        ['data' => 0, 'orderable' => false],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4, 'className' => 'text-center'],
        ['data' => 5, 'className' => 'text-center'],
    ],
    'order' => [[1, 'asc']],
    'page_length' => 25,
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
