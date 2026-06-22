<?php

declare(strict_types=1);

use App\Core\DB;

include('admin_elements/admin_header.php');

$module = 'lead_quotations';
$module_caption = 'Lead Quotation';
$tbl_name = DB::QUOTATIONS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');
$activeOrganizationId = dashboardRequireActiveOrganization();

$lead_id = isset($_REQUEST['lead_id']) && !empty($_REQUEST['lead_id']) ? e_s__($_REQUEST['lead_id']) : 0;

if (isset($_POST['publish'])) {
    $publish = 1;
} else {
    $publish = 0;
}

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="40">SR.</th>
        <th width="130">DATE</th>
        <th width="170">QUOTATION #</th>
        <th>JOB REFERENCE #</th>
        <th>LEAD NAME</th>
        <th>STATUS</th>
        <th>AMOUNT</th>
        <th width="120" class="col-center">ACTIONS</th>
    ',
    'columns' => [
        ['data' => 0, 'orderable' => false, 'searchable' => false],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4],
        ['data' => 5, 'orderable' => false, 'searchable' => false],
        ['data' => 6],
        ['data' => 7, 'orderable' => false, 'searchable' => false, 'className' => 'col-center'],
    ],
    'order' => [[1, 'desc']],
    'page_length' => 25,
    'search_placeholder' => 'Search lead quotations...',
    'extra_header' => '',
    'before_table' => '',
    'extra_js' => "
        // Override ajax data to pass lead_id
        var origDataFn = null;
        var origInit = window.HAIDatatableInitializer;
    ",
];

$leadNavbarHtml = '<div class="my-1">' . "\n";
ob_start();
include('admin_elements/lead_navbar.php');
$leadNavbarHtml .= ob_get_clean();
$leadNavbarHtml .= '</div>';

$listingConfig['extra_header'] = '';
$beforeTableExtra = '';

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
