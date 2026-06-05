<?php

include('admin_elements/admin_header.php');

$module = 'email_history';
$module_caption = 'Email History';
$tbl_name = $tbl_prefix . $module;
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

	<div class="content">

		<?php include('admin_elements/breadcrumb.php'); ?>

		<div class="card">
			<div class="card-header">
				<h5 class="mb-0">
					<i class="ph-envelope me-2"></i>
					Email History
				</h5>
			</div>

			<div class="card-body">
				<table id="grid-<?php echo $module; ?>" class="custom_datatables display responsive no-wrap table-hover" width="100%">
					<thead>
						<tr>
							<th width="60">ID</th>
							<th>Campaign</th>
							<th>Recipient</th>
							<th>Status</th>
							<th>Sent At</th>
							<th>Opened</th>
							<th>Clicked</th>
							<th>Created</th>
						</tr>
					</thead>
				</table>
			</div>
		</div>

		<?php include('admin_elements/copyright.php'); ?>
	</div>
</div>

<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#grid-<?php echo $module; ?>')) {
        $('#grid-<?php echo $module; ?>').DataTable().destroy();
    }

    $('#grid-<?php echo $module; ?>').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        iDisplayLength: 25,
        language: {
            searchPlaceholder: 'Recipient email, status...'
        },
        order: [[0, 'desc']],
        ajax: {
            url: 'datatables.php',
            type: 'POST',
            data: function(d) {
                d.ajax_action = 'listing_<?php echo $module; ?>';
                d.module = '<?php echo $module; ?>';
                d.action = '<?php echo $action; ?>';
                d.edit_permission = <?php echo granted('edit', $module_id) ? '1' : '0'; ?>;
                d.delete_permission = <?php echo granted('delete', $module_id) ? '1' : '0'; ?>;
            },
            error: function() {
                $('.grid-error').html('');
                $('#grid-<?php echo $module; ?>').append('<tbody class="grid-error"><tr><th colspan="8">An error occurred while loading the data.</th></tr></tbody>');
                $('#grid-<?php echo $module; ?>_processing').css('display', 'none');
            }
        }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>

