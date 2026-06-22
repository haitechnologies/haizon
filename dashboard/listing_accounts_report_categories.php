<?php

declare(strict_types=1);

use App\Core\DB;
use App\Core\Session;

include('admin_elements/admin_header.php');
include('admin_elements/only_systemadmin.php');

$module = 'accounts_report_categories';
$module_caption = 'Module';
$tbl_name = DB::ACCOUNTS_REPORT_CATEGORIES;
$error_message = '';
$success_message = '';

$module_id_param = '';
if (isset($_REQUEST['module_id']) && !empty($_REQUEST['module_id'])) {
    $module_id_param = e_s__($_REQUEST['module_id']);
}

if ($action == "delete_module_permissions" && !empty($id) && !empty($module_id_param)) {
    if (Session::roleId() == '1') {
        $result = $mysqli->query("DELETE FROM `" . DB::MODULE_PERMISSIONS . "` WHERE id=$id AND module_id=$module_id_param");
    }

    if ($result) {
        $success_message = "$module_caption Deleted Successfully.";
    } else {
        $error_message = "Sorry! $module Could Not Be Deleted.";
    }
}

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'before_table' => '
        <div class="alert alert-danger border-0 alert-dismissible fade show">
            <strong>Warning</strong> Only Development Teams can edit this.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    ',
    'thead' => '
        <th width="80">SR.</th>
        <th width="200">CATEGORY</th>
        <th>SUBCATEGORIES</th>
    ',
    'columns' => [
        ['data' => 0, 'orderable' => false],
        ['data' => 1],
        ['data' => 2],
    ],
    'order' => [[1, 'asc']],
    'page_length' => 25,
    'search_placeholder' => 'Search categories...',
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
