<?php

declare(strict_types=1);

use App\Core\DB;
use App\Core\Session;

include('admin_elements/admin_header.php');

$module = 'payments_made';
$module_caption = 'Payment Made';
$tbl_name = DB::PAYMENTS_MADE;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if (isset($_REQUEST['vendor_id']) && !empty($_REQUEST['vendor_id'])) {
    $vendor_id = e_s__($_REQUEST['vendor_id']);
} else {
    $vendor_id = '';
}

if (($action == "delete_payments_made" && !empty($id)) && granted('delete', $module_id)) {
    if (Session::roleId() == '1') {
        $result = $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");

        $journal_id = getTableAttrV('id', DB::JOURNALS, " reference_type='payment_made' AND reference_id=$id ");
        if (!empty($journal_id)) {
            $mysqli->query("DELETE FROM `" . DB::JOURNAL_ITEMS . "` WHERE journal_id=$journal_id ");
            $mysqli->query("DELETE FROM `" . DB::JOURNALS . "` WHERE reference_type='payment_made' AND reference_id=$id ");
        }
    } else {
        $result = $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by='" . Session::userId() . "'");

        $journal_id = getTableAttrV('id', DB::JOURNALS, " reference_type='payment_made' AND reference_id=$id ");
        if (!empty($journal_id)) {
            $mysqli->query("DELETE FROM `" . DB::JOURNAL_ITEMS . "` WHERE journal_id=$journal_id ");
            $mysqli->query("DELETE FROM `" . DB::JOURNALS . "` WHERE reference_type='payment_made' AND reference_id=$id AND created_by='" . Session::userId() . "' ");
        }
    }

    if ($result) {
        $success_message = "$module_caption Deleted Successfully.";
        flash_success($success_message);
        header("Location:listing_payments_made.php?page=$page");
    } else {
        $error_message = "Sorry! $module Could Not Be Deleted.";
    }
}

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'table_classes' => 'custom_datatables display responsive no-wrap table-hover',
    'thead' => '
        <th width="100">DATE</th>
        <th width="100">PAYMENT#</th>
        <th width="100">REFERENCE NUMBER</th>
        <th width="100">VENDOR NAME</th>
        <th width="100">PURCHASE#</th>
        <th width="100">MODE</th>
        <th width="100">AMOUNT</th>
        <th width="100">STATUS</th>
    ',
    'columns' => [
        ['data' => 0],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4],
        ['data' => 5],
        ['data' => 6],
        ['data' => 7],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 25,
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
