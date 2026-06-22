<?php

use App\Core\DB;
include('admin_elements/admin_header.php');

$module = 'attendance';
$module_caption = 'Attendance';
$tbl_name = DB::ATTENDANCE;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if (($action == "delete_$module" && !empty($id)) && is_SystemAdmin() || is_SuperAdmin() || $module_id && granted('delete', $module_id)) {
    $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
    if ($mysqli->affected_rows > 0) {
        $success_message = "Item deleted successfully.";
        flash_success($success_message);
        header("Location:listing_$module.php");
        exit;
    }
}
$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="50">ID</th>
        <th>Employee</th>
        <th>Date</th>
        <th>Check In</th>
        <th>Check Out</th>
        <th>Total Hours</th>
        <th>Status</th>
        <th width="100" class="col-center">Action</th>
    ',
    'columns' => [
        ['data' => 0],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4],
        ['data' => 5],
        ['data' => 6],
        ['data' => 7, 'orderable' => false, 'searchable' => false, 'className' => 'col-center text-center'],
    ],
    'order' => [[2, 'desc'], [0, 'desc']], // order by date desc, id desc
    'page_length' => 25,
];

ob_start();
include('admin_elements/hr_navbar.php');
$listingConfig['extra_header'] = ob_get_clean();

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
?>
