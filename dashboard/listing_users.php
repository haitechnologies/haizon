<?php

use App\Core\DB;
include('admin_elements/admin_header.php');

use App\Core\Container;
use App\Service\UserService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

$container = Container::getInstance();
$userService = $container->get(UserService::class);

$module = 'users';
$module_caption = 'Users';
$tbl_name = DB::USERS;  // Admin users table
$error_message = '';
$success_message = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
*/
if (!has_full_access()) {
    echo 'Permission Denied.';
    exit();
}

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| PUBLISH
|--------------------------------------------------------------------------
*/
if (($action == "publish_$module" && !empty($id))) {

    if (publish($module_caption, $tbl_name, $id)) {
        $success_message = "Employee account activated successfully.";
        flash_success($success_message);
    } else {
        $error_message = "Unable to activate employee account. Please try again.";
        flash_error($error_message);
    }

    /*
|--------------------------------------------------------------------------
| UN-PUBLISH
|--------------------------------------------------------------------------
*/
} else if (($action == "unpublish_$module" && !empty($id))) {

    if (unpublish($module_caption, $tbl_name, $id)) {
        $success_message = "Employee account deactivated successfully.";
        flash_success($success_message);
    } else {
        $error_message = "Unable to deactivate employee account. Please try again.";
        flash_error($error_message);
    }

    /*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/
} else if (($action == "delete_$module" && !empty($id))) {
    try {
        $user = $userService->getById((int)$id);
        $photo = $user->photo;
        $userService->delete((int)$id);
        
        if (!empty($photo)) {
            delete_photo($photo, $photo_upload_path, '1');     // DELETE THUMB
            delete_photo($photo, $photo_upload_path, '0');     // DELETE PHOTO
        }
        $success_message = "Employee account deleted successfully.";
        flash_success($success_message);
    } catch (ValidationException $e) {
        $error_message = current($e->getErrors());
        flash_error($error_message);
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
        flash_error($error_message);
    } catch (\Throwable $e) {
        $error_message = "Unable to delete employee account. Please try again.";
        flash_error($error_message);
    }
}

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

        <div class="card">

            <div class="card-body">
                    <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                        <thead>
                            <tr>
                                <th width="60" class="col-center">ID</th>
                                <th>FULL NAME</th>
                                <th>EMAIL</th>
                                <th>CONTACT</th>
                                <th>ROLE</th>
                                <th width="140">LAST LOGIN</th>
                                <th width="80" class="col-center">STATUS</th>
                                <th width="90" class="col-center">ACTIONS</th>
                            </tr>
                        </thead>
                    </table>
                </div>
        </div>

    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>

<script>
$(document).ready(function() {
    window.HAIDatatableInitializer.init('#grid-<?php echo $module; ?>', '<?php echo $module; ?>', {
        stateSave: false,
        deferRender: true,
        retrieve: false,
        ajax: {
            url: 'datatables.php',
            type: 'POST',
            data: function(d) {
                d.csrf_token = window.HAI_CSRF_TOKEN || $('input[name="csrf_token"]').first().val() || '';
                d.action = '<?php echo $action; ?>';
                d.edit_permission = <?php echo has_full_access() ? '1' : '0'; ?>;
                d.delete_permission = <?php echo has_full_access() ? '1' : '0'; ?>;
                d.session_user_id = '<?php echo $_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? ''; ?>';
                d.dt_session_role_id = '<?php echo $_SESSION[$project_pre]['DASHBOARD']['role_id'] ?? ''; ?>';
                return d;
            },
            error: function(xhr, status, error) {
                console.error('[<?php echo ucfirst($module); ?>] DataTable AJAX error:', error);
                console.error('[<?php echo ucfirst($module); ?>] Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 },
            { data: 7, orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: [0, 6, 7], className: 'col-center' },
            { targets: 7, orderable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: {
            search: "",
            searchPlaceholder: "Search users...",
            lengthMenu: "_MENU_"
        }
    });

    // ========================================
    // Delete Record Handler
    // ========================================
    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var module = $(this).data('module');
        
        if (confirm('Are you sure you want to delete this record?')) {
            // Create form and submit
            var form = $('<form>', {
                'method': 'POST',
                'action': 'listing_<?php echo $module; ?>.php'
            }).append($('<input>', {
                'type': 'hidden',
                'name': 'action',
                'value': 'delete_' + module
            })).append($('<input>', {
                'type': 'hidden',
                'name': 'id',
                'value': id
            }));
            
            $('body').append(form);
            form.submit();
        }
    });

});
</script>

<?php include('admin_elements/admin_footer.php'); ?>


