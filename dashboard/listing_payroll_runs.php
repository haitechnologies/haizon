<?php

use App\Core\DB;
include('admin_elements/admin_header.php');

$module = 'payroll_runs';
$module_caption = 'Payroll Runs';
$tbl_name = DB::PAYROLL_RUNS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| RESTRICT ACCESS: Only System Admin, Super Admin, and HR can view payroll runs
|--------------------------------------------------------------------------
*/
if (!has_full_access() && !is_accounts() && is_role() != 'hr') {
    echo 'Permission Denied.';
    exit();
}

if (($action == "delete_$module" && !empty($id)) && (has_full_access() || is_accounts() || is_role() == 'hr')) {
    // Delete associated payslips and payroll run items first
    $mysqli->query("DELETE FROM `" . DB::PAYSLIPS . "` WHERE payroll_run_id=$id");
    $mysqli->query("DELETE FROM `" . DB::table('payroll_run_items') . "` WHERE payroll_run_id=$id");
    $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
    if ($mysqli->affected_rows > 0) {
        $success_message = "Payroll run deleted successfully.";
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
        <th>Period Start</th>
        <th>Period End</th>
        <th>Status</th>
        <th>Total Gross</th>
        <th>Total Deductions</th>
        <th>Total Net</th>
        <th>Employees</th>
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
        ['data' => 7, 'orderable' => false, 'searchable' => false],
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
