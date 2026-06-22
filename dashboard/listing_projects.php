<?php

declare(strict_types=1);

use App\Core\DB;
use App\Core\Session;

include('admin_elements/admin_header.php');

$module = 'projects';
$module_caption = 'Project';
$tbl_name = DB::PROJECTS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if (($action == "delete_$module" && !empty($id))) {
    // Delete logic placeholder (currently commented out in original)
}

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="80">SR.</th>
        <th width="100">DATE</th>
        <th width="150">PROJECT #</th>
        <th width="150">PROJECT NAME</th>
        <th width="150">JOB ID</th>
        <th width="150">CUSTOMER NAME</th>
        <th width="130" class="col-center">ACTIONS</th>
    ',
    'columns' => [
        ['data' => 0, 'orderable' => false, 'searchable' => false],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4],
        ['data' => 5],
        ['data' => 6, 'orderable' => false, 'searchable' => false, 'className' => 'col-center'],
    ],
    'order' => [[1, 'desc']],
    'page_length' => 25,
    'search_placeholder' => 'Search projects...',
    'extra_js' => "
        $(document).on('click', '[data-action=\"delete_record\"]', function(e) {
            e.preventDefault();
            var id = $(this).data('id'), module = $(this).data('module');
            if (!confirm('Are you sure you want to delete this record?')) return;
            $('<form method=\"POST\">').append(
                $('<input type=\"hidden\" name=\"action\">').val('delete_' + module),
                $('<input type=\"hidden\" name=\"id\">').val(id)
            ).appendTo('body').submit();
        });
    ",
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
