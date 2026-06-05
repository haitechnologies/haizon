<?php
include('admin_elements/admin_header.php');
require_once __DIR__ . '/../classes/InputValidator.php';

$module = 'items';
$module_caption = 'Item';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::ITEMS;  // Items table
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
        log_error('CSRF token validation failed in listing_items.php', 'WARNING', __FILE__, __LINE__);
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

    // INPUT VALIDATION: Validate item ID
    $idResult = InputValidator::integer($id, 1);
    if (!$idResult['valid']) {
        $error_message = "Invalid item ID: " . $idResult['error'];
    } else {
        $itemId = $idResult['value'];
        
        // IDOR PROTECTION: Check ownership (unless system admin)
        $canDelete = has_full_access() || checkOwnership($tbl_name, $itemId, 'created_by');
        
        if (!$canDelete) {
            $error_message = "You do not have permission to delete this item";
            log_error("IDOR attempt: User $session_user_id tried to delete item $itemId", 'WARNING', __FILE__, __LINE__);
        } else {
            // Perform delete with prepared statement
            $stmt = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id=?");
            $stmt->bind_param("i", $itemId);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success_message = "Item deleted successfully.";
                    header("Location:listing_$module.php?success_message=$success_message");
                } else {
                    $error_message = "Could not delete record. It may have already been deleted.";
                }
            } else {
                $error_message = "Database error: " . $stmt->error;
                log_error("Delete failed for item $itemId: " . $stmt->error, 'ERROR', __FILE__, __LINE__);
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
		<!-- CSRF Token for AJAX operations -->
			<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

			<?php include('admin_elements/breadcrumb.php'); ?>

			<div class="card">
				<div class="card-body">
					<table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
						<thead>
							<tr>
							<th width="40">SR.</th>
							<!-- <th width="100">ITEM TYPE</th> -->
							<th>NAME</th>
							<th width="150">PRICE</th>
							<!-- <th>UNIT</th> -->
							<th width="90">CREATED AT</th>
							<!-- <th width="50">STATUS</th> -->
								<th width="90">ACTION</th>
							</tr>
						</thead>
					</table>
				</div>
		</div>

	<?php include('admin_elements/copyright.php'); ?>
</div>

<script>
$(document).ready(function() {
    window.HAIDatatableInitializer.init('#grid-<?php echo $module; ?>', '<?php echo $module; ?>', {
        stateSave: false,     // Disable state saving to prevent conflicts
        deferRender: true,    // Defer rendering for performance
        retrieve: false,      // Don't retrieve existing instance
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        pageLength: 10,
        ajax: {
            data: function(d) {
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
            { data: 0 },     // SR (ID)
            { data: 1 },     // NAME (with link)
            { data: 2 },     // PRICE
            { data: 3 },     // CREATED AT
            { data: 4, orderable: false, searchable: false }  // ACTION
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
        var csrfToken = $('input[name="csrf_token"]').val();
        
        if (confirm('Are you sure you want to delete this item?')) {
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
            })).append($('<input>', {
                'type': 'hidden',
                'name': 'csrf_token',
                'value': csrfToken
            }));
            
            $('body').append(form);
            form.submit();
        }
    });

});
</script>

<?php include('admin_elements/admin_footer.php'); ?>


