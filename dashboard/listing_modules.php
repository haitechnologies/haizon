<?php

declare(strict_types=1);

use App\Core\DB;
use App\Core\Session;
use App\Security\Roles;

include('admin_elements/admin_header.php');
Roles::requireSystemAdmin();

$module = 'modules';
$module_caption = 'Module';
$error_message = '';
$success_message = '';

if (($action == "delete_$module" && !empty($id))) {
    if (Roles::isSystemAdmin(Session::roleId())) {
        $id = intval($id);

        $stmt = $mysqli->prepare("DELETE FROM " . DB::MODULES . " WHERE id = ?");
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare("DELETE FROM " . DB::MODULE_PERMISSIONS . " WHERE module_id = ?");
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $stmt->close();
    }

    if ($result) {
        $success_message = "$module_caption Deleted Successfully.";
    } else {
        $error_message = "Sorry! $module Could Not Be Deleted.";
    }
}

$module_id_param = '';
if (isset($_REQUEST['module_id']) && !empty($_REQUEST['module_id'])) {
    $module_id_param = intval(e_s__($_REQUEST['module_id']));
}

if ($action == "delete_module_permissions" && !empty($id) && !empty($module_id_param)) {
    if (Roles::isSystemAdmin(Session::roleId())) {
        $id = intval($id);

        $stmt = $mysqli->prepare("DELETE FROM " . DB::MODULE_PERMISSIONS . " WHERE id = ? AND module_id = ?");
        $stmt->bind_param('ii', $id, $module_id_param);
        $result = $stmt->execute();
        $stmt->close();
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
        <th width="80">ID</th>
        <th width="200">MODULE NAME</th>
        <th>PERMISSIONS</th>
        <th width="200">SYSTEMS</th>
        <th width="90">ACTION</th>
    ',
    'columns' => [
        ['data' => 0, 'name' => 'id', 'title' => 'ID'],
        ['data' => 1, 'name' => 'module_name', 'title' => 'Module Name'],
        ['data' => 2, 'title' => 'Permissions', 'orderable' => false],
        ['data' => 3, 'title' => 'Systems'],
        ['data' => 4, 'title' => 'Action', 'orderable' => false, 'searchable' => false],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 10,
    'extra_js' => "
        $(document).on('click', 'a[data-action=\"delete_record\"]', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const module = $(this).data('module');
            if (confirm('Are you sure you want to delete this module?')) {
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
