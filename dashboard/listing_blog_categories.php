<?php
include('admin_elements/admin_header.php');
require_once __DIR__ . '/../classes/InputValidator.php';

$module = 'blog_categories';
$module_caption = 'Blog Category';
$tbl_name = DB::BLOG_CATEGORIES;
$module_id = getModuleIdBySlug($module, $mysqli);
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
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in listing_blog_categories.php', 'WARNING', __FILE__, __LINE__);
        // Prevent further execution
        $action = '';
    }
}


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
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {

    // INPUT VALIDATION: Validate blog category ID
    $idResult = InputValidator::integer($id, 1);
    if (!$idResult['valid']) {
        $error_message = "Invalid blog category ID: " . $idResult['error'];
    } else {
        $categoryId = $idResult['value'];
        
        // IDOR PROTECTION: Check ownership (unless system admin)
        $canDelete = has_full_access();
        if (!$canDelete) {
            $canDelete = checkOwnership($tbl_name, $categoryId, 'created_by');
        }
        
        if (!$canDelete) {
            $error_message = "You do not have permission to delete this blog category";
            log_error("IDOR attempt: User $session_user_id tried to delete blog_category $categoryId", 'WARNING', __FILE__, __LINE__);
        } else {
            // Perform delete with prepared statement
            $stmt = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id=?");
            $stmt->bind_param("i", $categoryId);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success_message = "Item deleted successfully.";
                    header("Location:listing_$module.php?success_message=$success_message");
                } else {
                    $error_message = "Could not delete record. It may have already been deleted.";
                }
            } else {
                $error_message = "Database error: " . $stmt->error;
                log_error("Delete failed for blog_category $categoryId: " . $stmt->error, 'ERROR', __FILE__, __LINE__);
            }
            $stmt->close();
        }
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

            <!-- <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><?php echo $module_caption; ?> Information</h6>
            </div> -->

            <div class="card-body">
                <!-- CSRF Protection Token -->
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                
                <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover order-column" width="100%">
                    <thead>
                        <tr>
                            <th width="40" class="col-center">SR.</th>
                            <th>CATEGORY NAME</th>
                            <th>SLUG</th>
                            <th width="90">CREATED AT</th>
                            <th width="75" class="col-center">STATUS</th>
                            <th width="90" class="col-center">ACTION</th>
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
    var tableSelector = '#grid-<?php echo $module; ?>';

    window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
        stateSave: false,
        deferRender: true,
        retrieve: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: {
            search: "",
            searchPlaceholder: "Search blog categories...",
            lengthMenu: "_MENU_"
        },
        ajax: {
            url: 'datatables.php',
            type: 'POST',
            data: function(d) {
                d.csrf_token = window.HAI_CSRF_TOKEN || $('input[name="csrf_token"]').first().val() || '';
                d.action = '<?php echo $action; ?>';
                d.edit_permission = <?php echo granted('edit', $module_id) ? '1' : '0'; ?>;
                d.delete_permission = <?php echo granted('delete', $module_id) ? '1' : '0'; ?>;
                return d;
            },
            error: function() {
                $('.grid-error').html('');
                $(tableSelector).append('<tbody class="grid-error"><tr><th colspan="6">No Results Found.</th></tr></tbody>');
                $(tableSelector + '_processing').hide();
            }
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'slug' },
            { data: 'created_at' },
            { data: 'status' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: [0, 4, 5], className: 'col-center' },
            { targets: 5, orderable: false }
        ],
        order: [[0, 'desc']]
    });

    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();
        if (confirm('Delete this item?')) {
            var form = $('<form>', { 'method': 'POST', 'action': 'listing_<?php echo $module; ?>.php' })
                .append($('<input>', { 'type': 'hidden', 'name': 'action', 'value': 'delete_<?php echo $module; ?>' }))
                .append($('<input>', { 'type': 'hidden', 'name': 'id', 'value': $(this).data('id') }))
                .append($('<input>', { 'type': 'hidden', 'name': 'csrf_token', 'value': $('input[name="csrf_token"]').val() }));
            $('body').append(form);
            form.submit();
        }
    });

});
</script>

<?php include('admin_elements/admin_footer.php'); ?>

