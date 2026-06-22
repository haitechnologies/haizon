<?php

declare(strict_types=1);

use App\Core\DB;
use App\Security\Roles;

include('admin_elements/admin_header.php');
Roles::requireAdminAccess();

$module = 'roles';
$module_caption = 'Admin Roles & Permission';
$tbl_name = DB::ROLES;
$module_id = getModuleIdBySlug($module, $mysqli);
$error_message = '';
$success_message = '';

include('admin_elements/listing_handler.php');

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="40">SR.</th>
        <th>ROLE</th>
        <th>DESCRIPTION</th>
        <th width="90">USERS</th>
        <th width="90">CREATED AT</th>
        <th width="90">ACTION</th>
    ',
    'columns' => [
        ['data' => 'id'],
        ['data' => 'role_name'],
        ['data' => 'role_description'],
        ['data' => 'user_count'],
        ['data' => 'created_at'],
        ['data' => 'actions', 'orderable' => false, 'searchable' => false],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 10,
    'search_placeholder' => 'Search roles...',
    'extra_js' => "
        // Re-number rows
        var table = $('#grid-{$module}').DataTable();
        table.on('draw', function() {
            var info = table.page.info();
            table.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                cell.innerHTML = i + 1 + info.start;
            });
        });
    ",
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
