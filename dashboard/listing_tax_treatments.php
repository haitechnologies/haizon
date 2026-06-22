<?php

use App\Core\DB;

include('admin_elements/admin_header.php');

$module = 'tax_treatments';
$module_caption = 'Tax Treatment';
$tbl_name = DB::TAX_TREATMENTS;
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
        <th>TAX TREATMENT</th>
        <th width="90">ACTIONS</th>
    ',
    'columns' => [
        ['data' => 0, 'orderable' => false],
        ['data' => 1],
        ['data' => 2, 'className' => 'text-center'],
    ],
    'order' => [[1, 'asc']],
    'page_length' => 25,
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
