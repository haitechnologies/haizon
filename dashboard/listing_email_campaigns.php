<?php

include('admin_elements/admin_header.php');

$module = 'email_campaigns';
$module_caption = 'Email Campaign';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::EMAIL_CAMPAIGNS;  // Email campaigns table
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
		['verify_field' => 'campaign_name', 'item_label' => 'Email Campaign', 'module_slug' => 'email_campaigns']
	);
	if ($result['success']) {
		$success_message = $result['message'];
	} else {
		$error_message = $result['message'];
	}
}
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
							<th>CAMPAIGN NAME</th>
							<th>CAMPAIGN CODE</th>
							<th width="140">CREATED</th>
							<th width="100">STATUS</th>
							<th width="120">ACTIONS</th>
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
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        pageLength: 10,
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
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5, orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    // ========================================
    // Delete Record Handler
    // ========================================
    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var module = $(this).data('module');
        
        if (confirm('Are you sure you want to delete this campaign?')) {
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


