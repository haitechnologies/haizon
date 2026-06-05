<?php

include('admin_elements/admin_header.php');

$module = 'email_automation_queue';
$module_id = getModuleIdBySlug($module, $mysqli);
$module_caption = 'Email Automation Queue';
$tbl_name = DB::EMAIL_AUTOMATION_QUEUE;
$error_message = '';
$success_message = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
*/
include('admin_elements/permissions.php');
$activeOrganizationId = dashboardRequireActiveOrganization();
$hide_add_button = true;

?>

<div class="content-wrapper">
	<?php if (!empty($visibleEmailLinks) && $isEmailRelatedPage && function_exists('renderEmailQuickbar')): ?>
		<?php renderEmailQuickbar($visibleEmailLinks, $current_page); ?>
	<?php endif; ?>

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
                            <th width="60">ID</th>
                            <th width="90">Rule</th>
                            <th width="100">Company</th>
                            <th>Email To</th>
                            <th width="110">Status</th>
                            <th width="150">Scheduled For</th>
                            <th width="150">Sent At</th>
                            <th>Error</th>
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
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            data: function(d) {
                d.edit_permission = <?php echo granted('edit', $module_id) ? '1' : '0'; ?>;
                d.delete_permission = <?php echo granted('delete', $module_id) ? '1' : '0'; ?>;
                return d;
            },
            error: function() {
                $('.grid-error').html('');
                $(tableSelector).append('<tbody class="grid-error"><tr><th colspan="8">No Results Found.</th></tr></tbody>');
                $(tableSelector + '_processing').hide();
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
            { data: 7 }
        ],
        order: [[0, 'desc']]
    });

    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();
        if (confirm('Delete this item?')) {
            var form = $('<form>', { 'method': 'POST', 'action': 'listing_<?php echo $module; ?>.php' })
                .append($('<input>', { 'type': 'hidden', 'name': 'action', 'value': 'delete_<?php echo $module; ?>' }))
                .append($('<input>', { 'type': 'hidden', 'name': 'id', 'value': $(this).data('id') }));
            $('body').append(form);
            form.submit();
        }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>



