<?php
include('admin_elements/admin_header.php');
$module = 'blogs';
$module_caption = 'Blog Post';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::BLOGS;  // Blogs table
$error_message = '';
$success_message = '';


/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if ($action == "delete_$module" && !empty($id)) {
    
    // Use centralized deletion manager for consistent error handling & audit logging
    $result = DeletionManager::delete(
        DB::BLOGS,
        $id,
        $session_user_id,
        [
            'verify_field' => 'title',
            'item_label' => 'Blog Post',
            'module_slug' => 'blogs'
        ]
    );
    
    if ($result['success']) {
        $success_message = $result['message'];
        header("Location: listing_$module.php?msg=deleted");
        exit;
    } else {
        $error_message = $result['message'];
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
    <?php include('admin_elements/page_header.php'); ?>
    <!-- /page header -->


    <div class="content datatable-enhanced">

        <?php include('admin_elements/breadcrumb.php'); ?>

        <div class="card">

            <div class="card-body">
                <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover order-column" width="100%">
                    <thead>
                        <tr>
                            <th width="50" class="col-center">ID</th>
                            <th>TITLE</th>
                            <th width="150">CATEGORY</th>
                            <th width="120">AUTHOR</th>
                            <th width="70" class="col-center">VIEWS</th>
                            <th width="110">PUBLISHED</th>
                            <th width="110">UPDATED</th>
                            <th width="140" class="col-center">STATUS</th>
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
                d.edit_permission = <?php echo granted('edit', $module_id) ? '1' : '0'; ?>;
                d.delete_permission = <?php echo granted('delete', $module_id) ? '1' : '0'; ?>;
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
            { data: 0, width: "50px" },
            { data: 1 },
            { data: 2, width: "150px" },
            { data: 3, width: "120px" },
            { data: 4, width: "70px" },
            { data: 5, width: "110px" },
            { data: 6, width: "110px" },
            { data: 7, width: "140px" },
            { data: 8, orderable: false, searchable: false, width: "90px" }
        ],
        columnDefs: [
            { targets: [0, 4, 7, 8], className: 'col-center' },
            { targets: 8, orderable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: {
            search: "",
            searchPlaceholder: "Search blogs...",
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
        
        if (confirm('Are you sure you want to delete this blog post?')) {
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

