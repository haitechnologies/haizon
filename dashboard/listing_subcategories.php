<?php

declare(strict_types=1);

use App\Core\DB;

include('admin_elements/admin_header.php');

$module = 'subcategories';
$module_caption = 'Subcategories';
$tbl_name = DB::SUBCATEGORIES;
$module_id = getModuleIdBySlug($module, $mysqli);
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in ' . __FILE__, 'WARNING', __FILE__, __LINE__);
    }
}

include('admin_elements/listing_handler.php');

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="50">ID</th>
        <th>PARENT CATEGORY</th>
        <th>SUBCATEGORY NAME</th>
        <th>ITEMS</th>
        <th>COMPANIES</th>
        <th width="80">STATUS</th>
        <th width="90">ACTIONS</th>
    ',
    'columns' => [
        ['data' => 'id'],
        ['data' => 'parent_category'],
        ['data' => 'name'],
        ['data' => 'items_count'],
        ['data' => 'companies_count'],
        ['data' => 'is_active'],
        ['data' => 'actions', 'orderable' => false, 'searchable' => false],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 10,
    'extra_js' => "
        $(document).on('click', '[data-action=\"delete_record\"]', function(e) {
            e.preventDefault();
            if (confirm('Delete this item?')) {
                var token = $('input[name=\"csrf_token\"]').val();
                var form = $('<form>', { 'method': 'POST', 'action': 'listing_{$module}.php' })
                    .append($('<input>', { 'type': 'hidden', 'name': 'action', 'value': 'delete_{$module}' }))
                    .append($('<input>', { 'type': 'hidden', 'name': 'id', 'value': $(this).data('id') }))
                    .append($('<input>', { 'type': 'hidden', 'name': 'csrf_token', 'value': token }));
                $('body').append(form);
                form.submit();
            }
        });
    ",
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
