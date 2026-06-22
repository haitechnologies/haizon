<?php

declare(strict_types=1);

use App\Core\DB;
use App\Core\Session;

include('admin_elements/admin_header.php');

$module             = 'debit_notes';
$module_caption     = 'Debit Note';
$tbl_name = DB::DEBIT_NOTES;
$error_message      = '';
$success_message    = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if (($action == "delete_$module" && !empty($id))) {

    if (Session::roleId() == '1') {

        $mysqli->query("DELETE FROM `" . DB::DEBIT_NOTE_ITEMS . "` WHERE debit_note_id=$id");
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id ");
    } else {

        $mysqli->query("DELETE FROM `" . DB::DEBIT_NOTE_ITEMS . "` WHERE debit_note_id=$id");
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by='" . Session::userId() . "'");
    }


    if ($mysqli->affected_rows > 0) {
        $success_message = "$module_caption Deleted Successfully.";
        flash_success($success_message);
        header("Location:listing_$module.php?page=$page");
    } else {
        $error_message = "Sorry! $module_caption Could Not Be Deleted. Only Super Administrator can delete this record.";
    }
}

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="100">DATE</th>
        <th width="150">DEBIT NOTE #</th>
        <th>REFERENCE #</th>
        <th>VENDOR NAME</th>
        <th width="100" class="col-center">STATUS</th>
        <th width="100" class="text-end">AMOUNT</th>
    ',
    'columns' => [
        ['data' => 0],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4, 'className' => 'col-center'],
        ['data' => 5, 'className' => 'text-end'],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 25,
    'search_placeholder' => 'Search debit notes...',
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
