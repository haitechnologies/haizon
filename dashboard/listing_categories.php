<?php

use App\Core\DB;
use App\Core\DeletionManager;
include('admin_elements/admin_header.php');

$module = 'categories';
$module_caption = 'Category';
$tbl_name = DB::CATEGORIES;
$module_id = getModuleIdBySlug($module, $mysqli);
$error_message = '';
$success_message = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
*/
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
} else if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {
    $result = DeletionManager::delete(
        $tbl_name,
        $id,
        $session_user_id,
        ['verify_field' => 'name', 'item_label' => 'Category', 'module_slug' => 'categories']
    );
    if ($result['success']) {
        $success_message = $result['message'];
    } else {
        $error_message = $result['message'];
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
                                <th width="40" class="col-center">ID</th>
                                <th>CATEGORY NAME</th>
                                <th class="col-center">SUBCATEGORIES</th>
                                <th class="col-center">ITEMS</th>
                                <th class="col-center">COMPANIES</th>
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

<?php include('admin_elements/admin_footer.php'); ?>

<script>
$(document).ready(function() {
    window.HAIDatatableInitializer.init('#grid-<?php echo $module; ?>', '<?php echo $module; ?>', {
        stateSave: false,
        deferRender: true,
        retrieve: false,
        ajax: {
            data: function(d) {
                d.module = 'categories';
                return d;
            },
            error: function(xhr, status, error) {
                console.error('[Categories] DataTable AJAX error:', error);
                console.error('[Categories] Response:', xhr.responseText);
                alert('Error loading data. Please check console for details.');
            }
        },
        columns: [
            { data: 0, name: 'id', title: 'ID' },
            { data: 1, name: 'name', title: 'Category Name' },
            { data: 2, title: 'Subcategories' },
            { data: 3, title: 'Items' },
            { data: 4, title: 'Companies' },
            { data: 5, name: 'is_active', title: 'Status' },
            { data: 6, title: 'Actions', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: [0, 2, 3, 4, 5, 6], className: 'col-center' },
            { targets: 6, orderable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        autoWidth: false,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: {
            search: "",
            searchPlaceholder: "Search categories...",
            lengthMenu: "_MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            loadingRecords: "Loading...",
            processing: "Processing...",
            emptyTable: "No categories available"
        }
    });
    
    // Handle delete button clicks (using event delegation)
    $(document).on('click', 'a[data-action="delete_record"]', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const module = $(this).data('module');
        
        if (confirm('Are you sure you want to delete this category?')) {
            // Use form submission method
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete_' + module + '"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    });


});
</script>
