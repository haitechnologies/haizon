<?php

use App\Core\DB;
use App\Security\Roles;
include('admin_elements/admin_header.php');
Roles::requireSystemAdmin();
$module = 'modules';
$module_caption = 'Module';
$error_message = '';
$success_message = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/

// // Only me as Super Admin
// if ($_SESSION[$project_pre]['DASHBOARD']['role_id'] != 1) {
//     header("Location:index.php?error_message=Only Super Admin has the rights to access Admnistration Module.");
// }


/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($id))) {

    //SUPERADMIN CAN DELETE ANY DATA
    if (Roles::isSystemAdmin($_SESSION[$project_pre]['DASHBOARD']['role_id'])) {
        $id = intval($id); // Sanitize to integer
        
        // Use prepared statements to prevent SQL injection
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
        // header("Location:listing_$module.php?page=$page&success_message=$success_message");
    } else {
        $error_message = "Sorry! $module Could Not Be Deleted.";
    }
}


/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/

$module_id = '';
if (isset($_REQUEST['module_id']) && !empty($_REQUEST['module_id'])) {
    $module_id     = intval(e_s__($_REQUEST['module_id'])); // Sanitize to integer
}


if ($action == "delete_module_permissions" && !empty($id) && !empty($module_id)) {

    //SUPERADMIN CAN DELETE ANY DATA
    if (Roles::isSystemAdmin($_SESSION[$project_pre]['DASHBOARD']['role_id'])) {
        $id = intval($id); // Sanitize to integer
        
        // Use prepared statement to prevent SQL injection
        $stmt = $mysqli->prepare("DELETE FROM " . DB::MODULE_PERMISSIONS . " WHERE id = ? AND module_id = ?");
        $stmt->bind_param('ii', $id, $module_id);
        $result = $stmt->execute();
        $stmt->close();
    }

    if ($result) {
        $success_message = "$module_caption Deleted Successfully.";
        // header("Location:listing_$module.php?page=$page&success_message=$success_message");
    } else {
        $error_message = "Sorry! $module Could Not Be Deleted.";
    }
}

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
?>

<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h1 class="h5 mb-0 d-inline-flex align-items-center gap-2">
                    <a href="listing_<?php echo $module; ?>.php" class="text-dark">All <?php echo ucwords(str_ireplace('_', " ", $module)); ?></a>
                    <?php if (!empty($pageHelpData)): ?>
                        <button type="button" class="page-help-trigger-btn" data-bs-toggle="offcanvas" data-bs-target="#pageHelpPanel" title="How to use this page" aria-label="Page help">
                            <i class="ph-question"></i>
                        </button>
                    <?php endif; ?>
                </h1>
            </div>

            <div class="my-1">
                <?php if (empty($hide_add_button) && isset($module_id) && isset($module) && granted('create', $module_id)) { ?>
                    <a href="<?php echo $module; ?>.php" class="btn btn-primary btn-sm d-inline-flex align-items-center">
                        <i class="ph-plus ph-sm me-2 opacity-75"></i>New
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>
    <!-- /page header -->


    <div class="content datatable-enhanced">

        <?php include('admin_elements/breadcrumb.php'); ?>

        <div class="alert alert-danger border-0 alert-dismissible fade show">
            <strong>Warning</strong> Only Development Teams can edit this.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <div class="card">

            <div class="card-body">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <table id="grid-modules" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%" data-ajax-source="datatables.php" data-ajax-action="listing_modules" data-page-length="25">
                        <thead>
                            <tr>
                                <th width="80">ID</th>
                                <th width="200">MODULE NAME</th>
                                <th>PERMISSIONS</th>
                                <th width="200">SYSTEMS</th>
                                <th width="90">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
        </div>

    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>

<?php include('admin_elements/admin_footer.php'); ?>

<script>
$(document).ready(function() {
    try {
        window.HAIDatatableInitializer.init('#grid-modules', 'modules', {
            stateSave: false,
            deferRender: true,
            retrieve: false,
            dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            pageLength: 10,
            ajax: {
                url: 'datatables.php',
                type: 'POST',
                data: function(d) {
                    d.module = 'modules';
                    d.edit_permission = <?php echo granted_('edit', 'modules') ? 1 : 0; ?>;
                    d.delete_permission = <?php echo granted_('delete', 'modules') ? 1 : 0; ?>;
                    d.session_user_id = '<?php echo $_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? ''; ?>';
                    d.dt_session_role_id = '<?php echo $_SESSION[$project_pre]['DASHBOARD']['role_id'] ?? ''; ?>';
                    return d;
                },
                error: function(xhr, status, error) {
                    console.error('[Modules] DataTable AJAX Error:', error);
                    console.error('Response:', xhr.responseText);
                }
            },
            columns: [
                { data: 0, name: 'id', title: 'ID' },
                { data: 1, name: 'module_name', title: 'Module Name' },
                { data: 2, title: 'Permissions', orderable: false },
                { data: 3, title: 'Systems' },
                { data: 4, title: 'Action', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']],
            responsive: true,
            autoWidth: false,
            language: {
                search: "Filter:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                loadingRecords: "Loading...",
                processing: "Processing...",
                emptyTable: "No modules available"
            },
            initComplete: function() {
                console.log('[Modules] DataTable initialized');
            }
        });
    } catch (e) {
        console.error('[Modules] Exception during DataTable initialization:', e);
    }
    
    // Handle delete button clicks (using event delegation)
    $(document).on('click', 'a[data-action="delete_record"]', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const module = $(this).data('module');
        
        if (confirm('Are you sure you want to delete this module?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete_' + module + '"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    });
});
</script>

