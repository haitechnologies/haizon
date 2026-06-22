<?php

use App\Core\DB;
include('admin_elements/admin_header.php');

$module = 'payroll_components';
$module_caption = 'Payroll Components';
$tbl_name = DB::PAYROLL_COMPONENTS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($action == "delete_$module" && !empty($id) && (has_full_access() || (isset($module_id) && granted('delete', $module_id)))) {

    // Check if component is being used in salary structures
    $usage_check = $mysqli->query("SELECT COUNT(*) as count FROM `" . DB::SALARY_STRUCTURES . "` WHERE component_id=$id");
    $usage = $usage_check->fetch_assoc();

    if ($usage['count'] > 0) {
        $error_message = "Cannot delete this component. It is currently assigned to {$usage['count']} employee(s) in salary structures. Please remove it from all salary structures first.";
    } else {
        // Safe to delete
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
        if ($mysqli->affected_rows > 0) {
            $success_message = "Component deleted successfully.";
            flash_success($success_message);
            header("Location:listing_$module.php");
        } else {
            $error_message = "Unable to delete component. Please try again.";
        }
    }
}

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="50">ID</th>
        <th>Component Name</th>
        <th>Type</th>
        <th>Taxable</th>
        <th>Account ID</th>
        <th>In Use</th>
        <th width="120" class="col-center">Action</th>
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
    'order' => [[2, 'desc'], [1, 'asc']], // order by component_type desc, component_name asc
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
