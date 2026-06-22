<?php

declare(strict_types=1);

use App\Core\DB;

include('admin_elements/admin_header.php');

$module = 'payments_received';
$module_caption = 'Payment Received';
$tbl_name = DB::PAYMENTS_RECEIVED;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if (($action == "delete_payments_received" && !empty($id)) && granted('delete', $module_id)) {
    if (is_SystemAdmin() || is_SuperAdmin()) {
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id ");

        $journal_id = getTableAttrV('id', DB::JOURNALS, " reference_type='payment_received' AND reference_id=$id ");
        if (!empty($journal_id)) {
            $mysqli->query("DELETE FROM `" . DB::JOURNAL_ITEMS . "` WHERE journal_id=$journal_id ");
            $mysqli->query("DELETE FROM `" . DB::JOURNALS . "` WHERE reference_type='payment_received' AND reference_id=$id ");
        }
    } else {
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by='" . Session::userId() . "'");

        $journal_id = getTableAttrV('id', DB::JOURNALS, " reference_type='payment_received' AND reference_id=$id ");
        if (!empty($journal_id)) {
            $mysqli->query("DELETE FROM `" . DB::JOURNAL_ITEMS . "` WHERE journal_id=$journal_id ");
            $mysqli->query("DELETE FROM `" . DB::JOURNALS . "` WHERE reference_type='payment_received' AND reference_id=$id AND created_by='" . Session::userId() . "' ");
        }
    }

    if ($mysqli->affected_rows > 0) {
        $success_message = "$module_caption Deleted Successfully.";
        flash_success($success_message);
        header("Location:listing_$module.php?page=$page");
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
        <th width="100">CUSTOMER NAME</th>
        <th width="100">INVOICE#</th>
        <th width="100">MODE</th>
        <th width="100">AMOUNT</th>
        <th width="100">UNUSED AMOUNT</th>
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
        ['data' => 8],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 25,
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
