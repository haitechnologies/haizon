<?php

use App\Core\DB;

include('admin_elements/admin_header.php');

$module = 'categories';
$module_caption = 'Category';
$tbl_name = DB::CATEGORIES;
$module_id = getModuleIdBySlug($module, $mysqli);
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

include('admin_elements/listing_handler.php');

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="40" class="col-center">ID</th>
        <th>CATEGORY NAME</th>
        <th class="col-center">SUBCATEGORIES</th>
        <th class="col-center">ITEMS</th>
        <th class="col-center">COMPANIES</th>
        <th width="80" class="col-center">STATUS</th>
        <th width="90" class="col-center">ACTIONS</th>
    ',
    'columns' => [
        ['data' => 0, 'name' => 'id', 'title' => 'ID', 'className' => 'col-center'],
        ['data' => 1, 'name' => 'name', 'title' => 'Category Name'],
        ['data' => 2, 'title' => 'Subcategories', 'className' => 'col-center'],
        ['data' => 3, 'title' => 'Items', 'className' => 'col-center'],
        ['data' => 4, 'title' => 'Companies', 'className' => 'col-center'],
        ['data' => 5, 'name' => 'is_active', 'title' => 'Status', 'className' => 'col-center'],
        ['data' => 6, 'title' => 'Actions', 'orderable' => false, 'searchable' => false, 'className' => 'col-center'],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 10,
    'extra_js' => "
        $(document).on('click', 'a[data-action=\"delete_record\"]', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const module = $(this).data('module');
            if (confirm('Are you sure you want to delete this category?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type=\"hidden\" name=\"action\" value=\"delete_' + module + '\"><input type=\"hidden\" name=\"id\" value=\"' + id + '\">';
                document.body.appendChild(form);
                form.submit();
            }
        });
    ",
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
