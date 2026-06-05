<?php
include('admin_elements/admin_header.php');

$module = 'subcategories';
$module_caption = 'Subcategories';
$tbl_name = DB::SUBCATEGORIES;
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
| CSRF VALIDATION FOR FORM SUBMISSIONS
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in ' . __FILE__, 'WARNING', __FILE__, __LINE__);
    }
}

/*
|--------------------------------------------------------------------------
| PUBLISH
|--------------------------------------------------------------------------
*/
if (($action == "publish_$module" && !empty($id)) && $error_message === '') {
    if (publish($module_caption, $tbl_name, $id))
        $success_message = "$module_caption Published Successfully.";
    else
        $error_message = "Sorry! $module Could Not Be Published.";

/*
|--------------------------------------------------------------------------
| UN-PUBLISH
|--------------------------------------------------------------------------
*/
} else if (($action == "unpublish_$module" && !empty($id)) && $error_message === '') {
    if (unpublish($module_caption, $tbl_name, $id))
        $success_message = "$module_caption Un-Published Successfully.";
    else
        $error_message = "Sorry! $module Could Not Be Un-Published.";

/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/
} else if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id) && $error_message === '') {
    $result = DeletionManager::delete(
        $tbl_name,
        $id,
        $session_user_id,
        ['verify_field' => 'subcategory', 'item_label' => 'Subcategory', 'module_slug' => 'subcategories']
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
    <?php include('admin_elements/page_header.php'); ?>
    <!-- /page header -->

    <div class="content datatable-enhanced">

        <?php include('admin_elements/breadcrumb.php'); ?>

        <div class="card">

            <div class="card-body">
                    <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                        <thead>
                            <tr>
                                <th width="50">ID</th>
                                <th>PARENT CATEGORY</th>
                                <th>SUBCATEGORY NAME</th>
                                <th>ITEMS</th>
                                <th>COMPANIES</th>
                                <th width="80">STATUS</th>
                                <th width="90">ACTIONS</th>
                            </tr>
                        </thead>
                    </table>
                </div>
        </div>

    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>

<!-- Hidden CSRF Token for Form Submissions -->
<?php echo csrf_field(); ?>

<script>
$(document).ready(function() {
    var tableSelector = '#grid-<?php echo $module; ?>';

    window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
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
                d.action = '<?php echo $action; ?>';
                d.csrf_token = window.HAI_CSRF_TOKEN || $('input[name="csrf_token"]').first().val() || '';
                d.edit_permission = <?php echo granted('edit', $module_id) ? '1' : '0'; ?>;
                d.delete_permission = <?php echo granted('delete', $module_id) ? '1' : '0'; ?>;
                return d;
            },
            error: function(xhr, status, error) {
                console.error('[' + '<?php echo $module; ?>' + '] DataTable AJAX Error');
                console.error('Status:', xhr.status, '|', status);
                console.error('Response:', xhr.responseText);
                $('.grid-error').html('');
                $(tableSelector).append('<tbody class="grid-error"><tr><th colspan="7">Error loading data. Check browser console.</th></tr></tbody>');
                $(tableSelector + '_processing').hide();
            }
        },
        columns: [
            { data: 'id' },
            { data: 'parent_category' },
            { data: 'name' },
            { data: 'items_count' },
            { data: 'companies_count' },
            { data: 'is_active' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();
        if (confirm('Delete this item?')) {
            var token = $('input[name="csrf_token"]').val();
            var form = $('<form>', { 'method': 'POST', 'action': 'listing_<?php echo $module; ?>.php' })
                .append($('<input>', { 'type': 'hidden', 'name': 'action', 'value': 'delete_<?php echo $module; ?>' }))
                .append($('<input>', { 'type': 'hidden', 'name': 'id', 'value': $(this).data('id') }))
                .append($('<input>', { 'type': 'hidden', 'name': 'csrf_token', 'value': token }));
            $('body').append(form);
            form.submit();
        }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>


