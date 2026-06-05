<?php
include('admin_elements/admin_header.php');

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

    if (publish($module_caption, $tbl_name, $id))
        $success_message = "$module_caption Published Successfully.";
    else
        $error_message = "Sorry! $module Could Not Be Published.";

    /*
|--------------------------------------------------------------------------
| UN-PUBLISH
|--------------------------------------------------------------------------
*/
} else if (($action == "unpublish_$module" && !empty($id))) {

    if (unpublish($module_caption, $tbl_name, $id))
        $success_message = "$module_caption Un-Published Successfully.";
    else
        $error_message = "Sorry! $module Could Not Be Un-Published.";

    /*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/
} else if (($action == "delete_$module" && !empty($id))) {
    // Users use role-based permissions (has_full_access/is_hr checked at top)

    // Protect super-admin: user ID 1 must never be deleted
    if ((int)$id === 1) {
        $error_message = "The primary administrator account cannot be deleted.";
    } else {
    // Get photo before deletion
    $photo = getTableAttr('photo', $tbl_name, $id);
    
    $result = DeletionManager::delete(
        $tbl_name,
        $id,
        $session_user_id,
        ['verify_field' => 'full_name', 'item_label' => 'User', 'module_slug' => 'users']
    );
    
    if ($result['success']) {
        // Delete user photo if exists
        if (!empty($photo)) {
            delete_photo($photo, $photo_upload_path, '1');     // DELETE THUMB
            delete_photo($photo, $photo_upload_path, '0');     // DELETE PHOTO
        }
        $success_message = $result['message'];
    } else {
        $error_message = $result['message'];
    }
    } // end not-ID-1 guard
}

?>

<div class="content-wrapper">

    <!-- Page header -->
    <?php include('admin_elements/page_header.php'); ?>
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


