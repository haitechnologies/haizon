<?php

$module = 'payslips';
$module_caption = 'Payslips';
$error_message = '';
$success_message = '';

include('admin_elements/admin_header.php');

use App\Core\DB;
$tbl_name = DB::PAYSLIPS;

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| RESTRICT ACCESS: Only System Admin, Super Admin, and HR can view payslips
|--------------------------------------------------------------------------
*/
if (!has_full_access() && !is_accounts() && is_role() != 'hr') {
    echo 'Permission Denied.';
    exit();
}

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'hide_add_button' => true,
    'thead' => '
        <th width="50">ID</th>
        <th>Employee</th>
        <th>Department</th>
        <th>Payroll Period</th>
        <th>Gross Salary</th>
        <th>Deductions</th>
        <th>Net Salary</th>
        <th>Status</th>
        <th width="80" class="col-center">Action</th>
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
        ['data' => 8, 'orderable' => false, 'searchable' => false, 'className' => 'col-center text-center'],
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
