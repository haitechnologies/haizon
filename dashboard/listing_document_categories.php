<?php

use App\Core\DB;
use App\Core\Session;

include('admin_elements/admin_header.php');

$module = 'document_categories';
$module_caption = 'Document Category';
$tbl_name = DB::DOCUMENT_CATEGORIES;
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
        <th>DOCUMENT CATEGORY</th>
        <th>MODULES</th>
        <th width="90">CREATED AT</th>
        <th width="90">ACTIONS</th>
    ',
    'columns' => [
        ['data' => 0, 'orderable' => false],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4, 'className' => 'text-center'],
    ],
    'order' => [],
    'order_by' => 'document_category_type DESC, document_category ASC',
    'page_length' => 25,
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
