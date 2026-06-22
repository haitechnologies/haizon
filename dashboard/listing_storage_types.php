<?php

declare(strict_types=1);

use App\Core\DB;

include('admin_elements/admin_header.php');

$module = 'storage_types';
$module_caption = 'Storage Type';
$tbl_name = DB::STORAGE_TYPES;
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
        <th>STORAGE TYPE</th>
        <th width="90">CREATED AT</th>
        <th width="50">STATUS</th>
        <th width="90">ACTIONS</th>
    ',
    'columns' => [
        ['data' => 0, 'orderable' => false],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3, 'className' => 'text-center'],
        ['data' => 4, 'className' => 'text-center'],
    ],
    'order' => [[1, 'asc']],
    'page_length' => 25,
    'search_placeholder' => 'Search storage types...',
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
