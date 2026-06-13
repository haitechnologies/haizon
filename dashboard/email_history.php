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
	    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <?php if (isset($module) && !empty($module)): ?>
                    <h1 class="h5 mb-0 d-inline-flex align-items-center gap-2">
                        <a href="listing_<?php echo $module; ?>.php" class="text-dark">All <?php echo ucwords(str_ireplace('_', " ", $module)); ?></a>
                        <?php if (!empty($pageHelpData)): ?>
                            <button type="button" class="page-help-trigger-btn" data-bs-toggle="offcanvas" data-bs-target="#pageHelpPanel" title="How to use this page" aria-label="Page help">
                                <i class="ph-question"></i>
                            </button>
                        <?php endif; ?>
                    </h1>
                <?php else: ?>
                    <h1 class="h5 mb-0 d-inline-flex align-items-center gap-2">
                        <?php echo !empty($module_caption) ? htmlspecialchars($module_caption) : 'Dashboard'; ?>
                        <?php if (!empty($pageHelpData)): ?>
                            <button type="button" class="page-help-trigger-btn" data-bs-toggle="offcanvas" data-bs-target="#pageHelpPanel" title="How to use this page" aria-label="Page help">
                                <i class="ph-question"></i>
                            </button>
                        <?php endif; ?>
                    </h1>
                <?php endif; ?>
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

	</div>
	<?php include('admin_elements/copyright.php'); ?>
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

