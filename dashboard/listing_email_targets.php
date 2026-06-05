<?php

include('admin_elements/admin_header.php');

$module = 'email_targets';
$module_caption = 'Email Segment';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::EMAIL_TARGETS;  // Email targets table
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
| DELETE
|--------------------------------------------------------------------------
*/
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {

	$result = DeletionManager::delete(
		$tbl_name,
		$id,
		$session_user_id,
		['verify_field' => 'target_name', 'item_label' => 'Email Target', 'module_slug' => 'email_targets']
	);
	if ($result['success']) {
		$success_message = $result['message'];
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
							<th>Name</th>
							<th>Type</th>
							<th>Est. Count</th>
							<th>Active</th>
							<th>Created</th>
							<th width="120">ACTION</th>
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
        responsive: true,
        pageLength: 10,
        order: [[0, 'desc']],
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            searchPlaceholder: 'Segment name, type...'
        },
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
            error: function() {
                $('.grid-error').html('');
                $(tableSelector).append('<tbody class="grid-error"><tr><th colspan="7">An error occurred while loading the data.</th></tr></tbody>');
                $(tableSelector + '_processing').css('display', 'none');
            }
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'segment_type' },
            { data: 'estimated_count' },
            { data: 'is_active' },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ]
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



