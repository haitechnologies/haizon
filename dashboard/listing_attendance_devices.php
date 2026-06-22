<?php

declare(strict_types=1);

use App\Core\DB;

include('admin_elements/admin_header.php');

$module = 'attendance_devices';
$module_caption = 'Attendance Device';
$tbl_name = DB::ATTENDANCE_DEVICES;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$handler_config = ['hard_delete' => true, 'ownership_check' => true, 'redirect_on_success' => true];
include('admin_elements/listing_handler.php');

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="40">SR.</th>
        <th>Device Name</th>
        <th>IP:Port</th>
        <th>Serial #</th>
        <th>Model</th>
        <th>Location</th>
        <th>Last Sync</th>
        <th width="80" class="col-center">Status</th>
        <th width="90" class="col-center">Actions</th>
    ',
    'columns' => [
        ['data' => 0, 'orderable' => false, 'searchable' => false],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4],
        ['data' => 5],
        ['data' => 6],
        ['data' => 7, 'className' => 'col-center'],
        ['data' => 8, 'orderable' => false, 'searchable' => false, 'className' => 'col-center'],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 25,
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
