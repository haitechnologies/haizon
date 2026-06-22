<?php

$module = 'salary_structures';
$module_caption = 'Salary Structures';
$error_message = '';
$success_message = '';

include('admin_elements/admin_header.php');

use App\Core\DB;
$tbl_name = DB::SALARY_STRUCTURES;

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| RESTRICT ACCESS: Only System Admin, Super Admin, and HR can view salary structures
|--------------------------------------------------------------------------
*/
if (!has_full_access() && !is_accounts() && is_role() != 'hr') {
    echo 'Permission Denied.';
    exit();
}

if (($action == "delete_$module" && !empty($id)) && (has_full_access() || is_accounts() || is_role() == 'hr')) {
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
        <th>Component</th>
        <th>Amount</th>
        <th>Effective From</th>
        <th>Effective To</th>
        <th width="100" class="col-center">Action</th>
    ',
    'columns' => [
        ['data' => 0],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4],
        ['data' => 5],
        ['data' => 6, 'orderable' => false, 'searchable' => false, 'className' => 'col-center text-center'],
    ],
    'order' => [[0, 'desc']], // order by id desc
    'page_length' => 25,
    'messages' => [
        'success' => $success_message ?? '',
        'error' => $error_message ?? ''
    ]
];

ob_start();
include('admin_elements/hr_navbar.php');
$listingConfig['extra_header'] = ob_get_clean();

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
?>
