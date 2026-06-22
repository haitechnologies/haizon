<?php

use App\Core\DB;

include('admin_elements/admin_header.php');

$module = 'system_settings';
$module_caption = 'System Settings';
$tbl_name = DB::SYSTEM_SETTINGS;
$module_id = getModuleIdBySlug($module, $mysqli);
$error_message = '';
$success_message = '';
$hide_add_button = true;

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'hide_add_button' => true,
    'thead' => '
        <th>ID</th>
        <th>SLUG</th>
        <th>NAME</th>
        <th>VALUE</th>
        <th>HINT</th>
        <th>STATUS</th>
        <th>UPDATED</th>
        <th>ACTIONS</th>
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
    ],
    'order' => [[0, 'desc']],
    'page_length' => 10,
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
