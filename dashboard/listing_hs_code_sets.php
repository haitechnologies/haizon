<?php
/**
 * HS Code Sets Listing Page
 */

include('admin_elements/admin_header.php');

$module = 'hs_code_sets';
$module_caption = 'HS Code Sets';
$tbl_name = DB::HS_CODE_SETS;
$module_id = getModuleIdBySlug($module, $mysqli);

$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if ($action == "delete_$module" && !empty($id)) {
    if (!granted('delete', $module)) {
        $error_message = "You don't have permission to delete HS code sets.";
    } else {
        if (delete($tbl_name, $id)) {
            $success_message = "HS Code Set deleted successfully.";
            header("Location: listing_$module.php?msg=deleted");
            exit;
        } else {
            $error_message = "Could not delete HS Code Set.";
        }
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
                                <th>COUNTRY CODE</th>
                                <th>VERSION</th>
                                <th>EFFECTIVE FROM</th>
                                <th>EFFECTIVE TO</th>
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
        pageLength: 10,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: 'datatables_dispatcher.php',
            data: function(d) {
                d.action = '<?php echo $action; ?>';
                d.csrf_token = $('input[name="csrf_token"]').val();
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
            { data: 'country_code' },
            { data: 'version_label' },
            { data: 'effective_from' },
            { data: 'effective_to' },
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


