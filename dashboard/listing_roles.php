<?php

use App\Core\DB;
use App\Security\Roles;
include('admin_elements/admin_header.php');
Roles::requireAdminAccess();
$module = 'roles';
$module_caption = 'Admin Roles & Permission';
$error_message = '';
$success_message = '';
/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

// // Restrict to System Admin only
// Roles::requireSystemAdmin();

// // Allow System or Super Admin
// Roles::requireAdminAccess();

// // Custom role combination
// Roles::requireRole([Roles::SALES, Roles::OPERATIONS], 'Sales or Ops only');

// // Inline permission check
// if (Roles::currentUserHasRole(Roles::SYSTEM_ADMIN)) {
//     echo '<button>Delete All</button>';
// }


/*
|--------------------------------------------------------------------------
| PUBLISH
|--------------------------------------------------------------------------
|
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
|
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
|
*/
} else if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {

		// Use prepared statements to prevent SQL injection
		$id = intval($id); // Sanitize to integer
		
		if (Roles::isSuperAdmin(Roles::getCurrentRoleId())) {
			// Delete all permissions for this role (correct: use role_id, not id)
			$stmt = $mysqli->prepare("DELETE FROM " . DB::PERMISSIONS . " WHERE role_id = ?");
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->close();
			
			// Delete the role itself
			$stmt = $mysqli->prepare("DELETE FROM " . DB::ROLES . " WHERE id = ?");
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$affected_rows = $stmt->affected_rows;
			$stmt->close();
		
		} else {
			// Non-super admin can only delete their own roles
			// First delete permissions for this role
			$stmt = $mysqli->prepare("DELETE FROM " . DB::PERMISSIONS . " WHERE role_id = ?");
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->close();
			
			// Then delete the role (only if created by current user)
			$stmt = $mysqli->prepare("DELETE FROM " . DB::ROLES . " WHERE id = ? AND created_by = ?");
			$stmt->bind_param('ii', $id, $session_user_id);
			$stmt->execute();
			$affected_rows = $stmt->affected_rows;
			$stmt->close();
		}


		if ($affected_rows > 0) {
			$success_message = "Item deleted successfully.";
			header("Location:listing_$module.php?success_message=$success_message");
		} else {
			$error_message = "Action denied. You are not authorized to delete this record.";
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

			<div class="card-body">
					<table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
						<thead>
							<tr>
								<th width="40">SR.</th>
								<th>ROLE</th>
								<th>DESCRIPTION</th>
								<th width="90">USERS</th>
								<th width="90">CREATED AT</th>
								<th width="90">ACTION</th>
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
        language: {
            searchPlaceholder: 'role',
            sLengthMenu: 'Show _MENU_'
        },
        searchHighlight: true,
        columnDefs: [{
            targets: 0,
            className: 'text-center',
            render: function(data, type, row, meta) {
                return meta.row + 1 + meta.settings._iDisplayStart;
            }
        }],
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
                return d;
            },
            error: function() {
                $('.grid-error').html('');
                $(tableSelector).append('<tbody class="grid-error"><tr><th colspan="6">No Results Found.</th></tr></tbody>');
                $(tableSelector + '_processing').css('display', 'none');
            }
        },
        columns: [
            { data: 'id' },
            { data: 'role_name' },
            { data: 'role_description' },
            { data: 'user_count' },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>

