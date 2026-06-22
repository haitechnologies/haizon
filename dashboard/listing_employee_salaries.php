<?php

use App\Core\DB;
$module = 'employee_salaries';
$module_caption = 'Employee Salaries';
$error_message = '';
$success_message = '';

include('admin_elements/admin_header.php');
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'hide_add_button' => true,
    'thead' => '
        <th width="50">ID</th>
        <th>Employee</th>
        <th>Department</th>
        <th>Gross Salary</th>
        <th>Total Deductions</th>
        <th>Net Salary</th>
        <th>Components</th>
        <th width="100" class="col-center">Action</th>
    ',
    'columns' => [
        ['data' => 0],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3, 'orderable' => false],
        ['data' => 4, 'orderable' => false],
        ['data' => 5, 'orderable' => false],
        ['data' => 6, 'orderable' => false, 'searchable' => false],
        ['data' => 7, 'orderable' => false, 'searchable' => false, 'className' => 'col-center text-center'],
    ],
    'order' => [[1, 'asc']], // order by employee name asc
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
