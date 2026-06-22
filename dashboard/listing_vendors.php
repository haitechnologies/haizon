<?php

declare(strict_types=1);

use App\Core\DB;

include('admin_elements/admin_header.php');

$module = 'vendors';
$module_caption = 'Vendor';
$tbl_name = DB::VENDORS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {
    if (is_SuperAdmin()) {
        $mysqli->query("DELETE FROM `" . DB::VENDOR_ADDRESSES . "` WHERE addressable_type='Vendor' AND addressable_id=$id");
        $mysqli->query("DELETE FROM `" . DB::VENDOR_CONTACTS . "` WHERE contactable_type='Vendor' AND contactable_id=$id");
        $mysqli->query("DELETE FROM `" . DB::ENTITY_NOTES . "` WHERE entity_type='vendor' AND entity_id=$id");

        $result = $mysqli->query("SELECT * FROM `" . DB::VENDOR_ATTACHMENTS . "` WHERE attachable_type = 'Vendor' AND attachable_id=$id");
        while ($rows = $result->fetch_array()) {
            @unlink('../uploads/vendor_attachments/' . $rows['filename']);
            $mysqli->query("DELETE FROM `" . DB::VENDOR_ATTACHMENTS . "` WHERE id=" . $rows['id']);
        }

        $mysqli->query("DELETE FROM `" . DB::ENTITY_LOGS . "` WHERE entity_type='vendor' AND entity_id=$id");
        $mysqli->query("DELETE FROM `" . DB::VENDORS . "` WHERE id=$id");
    } else {
        $mysqli->query("DELETE FROM `" . DB::VENDOR_ADDRESSES . "` WHERE addressable_type='Vendor' AND addressable_id=$id AND created_by ='" . Session::userId() . "'");
        $mysqli->query("DELETE FROM `" . DB::VENDOR_CONTACTS . "` WHERE contactable_type='Vendor' AND contactable_id=$id AND created_by ='" . Session::userId() . "'");
        $mysqli->query("DELETE FROM `" . DB::ENTITY_NOTES . "` WHERE entity_type='vendor' AND entity_id=$id AND created_by ='" . Session::userId() . "'");

        $result = $mysqli->query("SELECT * FROM `" . DB::VENDOR_ATTACHMENTS . "` WHERE attachable_type = 'Vendor' AND attachable_id=$id AND created_by ='" . Session::userId() . "'");
        while ($rows = $result->fetch_array()) {
            @unlink('../uploads/vendor_attachments/' . $rows['filename']);
            $mysqli->query("DELETE FROM `" . DB::VENDOR_ATTACHMENTS . "` WHERE id=" . $rows['id']);
        }

        $mysqli->query("DELETE FROM `" . DB::ENTITY_LOGS . "` WHERE entity_type='vendor' AND entity_id=$id AND created_by ='" . Session::userId() . "'");
        $mysqli->query("DELETE FROM `" . DB::VENDORS . "` WHERE id=$id AND created_by ='" . Session::userId() . "'");
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
        <th>NAME</th>
        <th>EMAIL</th>
        <th>WORK PHONE</th>
        <th>PAYABLES (BCY)</th>
        <th>UNUSED CREDIT (BCY)</th>
    ',
    'columns' => [
        ['data' => 0],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4],
    ],
    'order' => [[0, 'asc']],
    'page_length' => 25,
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
