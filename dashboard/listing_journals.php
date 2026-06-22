<?php

declare(strict_types=1);

use App\Core\DB;
use App\Core\Session;

include('admin_elements/admin_header.php');

$module = 'journals';
$module_caption = 'Journal';
$tbl_name = DB::JOURNALS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$listingConfig = [
    'module' => $module,
    'module_caption' => 'Journals',
    'thead' => '
        <th width="100">DATE</th>
        <th width="100">JOURNAL#</th>
        <th width="100">REFERENCE</th>
        <th width="100" class="col-center">STATUS</th>
        <th>NOTES</th>
        <th width="150" class="text-end">AMOUNT</th>
        <th width="100">CREATED BY</th>
        <th width="100">REPORTING METHOD</th>
        <th width="130" class="col-center">ACTIONS</th>
    ',
    'columns' => [
        ['data' => 0],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3, 'className' => 'col-center'],
        ['data' => 4],
        ['data' => 5, 'className' => 'text-end'],
        ['data' => 6],
        ['data' => 7],
        ['data' => 8, 'orderable' => false, 'searchable' => false, 'className' => 'col-center'],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 25,
    'search_placeholder' => 'Search journals...',
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
