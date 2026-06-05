<?php

include('admin_elements/admin_header.php');
require_once __DIR__ . '/../classes/InputValidator.php';

$module = 'customers';
$module_caption = 'Customer';
$tbl_name = DB::CUSTOMERS;
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
        log_error('CSRF token validation failed in listing_customers.php', 'WARNING', __FILE__, __LINE__);
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

	// INPUT VALIDATION: Validate customer ID
	$idResult = InputValidator::integer($id, 1);
	if (!$idResult['valid']) {
		$error_message = "Invalid customer ID: " . $idResult['error'];
	} else {
		$customerId = $idResult['value'];
		
		// IDOR PROTECTION: Check ownership (unless system admin)
		$canDelete = has_full_access();
		if (!$canDelete) {
			$canDelete = checkOwnership($tbl_name, $customerId, 'user_id');
		}
		
		if (!$canDelete) {
			$error_message = "You do not have permission to delete this customer";
			log_error("IDOR attempt: User $session_user_id tried to delete customer $customerId", 'WARNING', __FILE__, __LINE__);
		} else {
			// Perform cascading delete with prepared statements
			if (has_full_access()) {

				// --- Delete Customer Addresses
				$stmt = $mysqli->prepare("DELETE FROM `" . DB::CUSTOMER_ADDRESSES . "` WHERE customer_id=?");
				$stmt->bind_param("i", $customerId);
				$stmt->execute();
				$stmt->close();

				// --- Delete Customer Contacts
				$stmt = $mysqli->prepare("DELETE FROM `" . DB::CUSTOMER_CONTACTS . "` WHERE customer_id=?");
				$stmt->bind_param("i", $customerId);
				$stmt->execute();
				$stmt->close();

				// --- Delete Customer Notes (entity_notes)
				$stmt = $mysqli->prepare("DELETE FROM `" . DB::ENTITY_NOTES . "` WHERE entity_type='customer' AND entity_id=?");
				$stmt->bind_param("i", $customerId);
				$stmt->execute();
				$stmt->close();

				// erp_customer_attachments dropped — no attachment cleanup needed

				// --- Delete Customer Activity Logs (entity_logs)
				$stmt = $mysqli->prepare("DELETE FROM `" . DB::ENTITY_LOGS . "` WHERE entity_type='customer' AND entity_id=?");
				$stmt->bind_param("i", $customerId);
				$stmt->execute();
				$stmt->close();

				// --- Delete Customer
				$stmt = $mysqli->prepare("DELETE FROM `" . DB::CUSTOMERS . "` WHERE id=?");
				$stmt->bind_param("i", $customerId);
				$stmt->execute();
				$stmt->close();
				
			} else {
				// Non-admin users: only delete their own records

				// --- Delete Customer Addresses
				$stmt = $mysqli->prepare("DELETE FROM `" . DB::CUSTOMER_ADDRESSES . "` WHERE customer_id=? AND created_by=?");
				$stmt->bind_param("ii", $customerId, $session_user_id);
				$stmt->execute();
				$stmt->close();

				// --- Delete Customer Contacts
				$stmt = $mysqli->prepare("DELETE FROM `" . DB::CUSTOMER_CONTACTS . "` WHERE customer_id=? AND created_by=?");
				$stmt->bind_param("ii", $customerId, $session_user_id);
				$stmt->execute();
				$stmt->close();

				// --- Delete Customer Notes (entity_notes)
				$stmt = $mysqli->prepare("DELETE FROM `" . DB::ENTITY_NOTES . "` WHERE entity_type='customer' AND entity_id=? AND created_by=?");
				$stmt->bind_param("ii", $customerId, $session_user_id);
				$stmt->execute();
				$stmt->close();

				// erp_customer_attachments dropped — no attachment cleanup needed

				// --- Delete Customer Activity Logs (entity_logs)
				$stmt = $mysqli->prepare("DELETE FROM `" . DB::ENTITY_LOGS . "` WHERE entity_type='customer' AND entity_id=? AND created_by=?");
				$stmt->bind_param("ii", $customerId, $session_user_id);
				$stmt->execute();
				$stmt->close();

				// --- Delete Customer
				$stmt = $mysqli->prepare("DELETE FROM `" . DB::CUSTOMERS . "` WHERE id=? AND created_by=?");
				$stmt->bind_param("ii", $customerId, $session_user_id);
				$stmt->execute();
				$stmt->close();
			}
			
			$success_message = "Customer deleted successfully";
		}
	}


	if ($mysqli->affected_rows > 0) {
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
		<div class="row mb-2 mt-2">
			<div class="col-lg-12">

					<?php
					// ------------------------------------------------------------------------------------------------
					$result = $mysqli->query("SELECT * FROM `" . DB::SETUP_STATUSES . "` WHERE is_active=1 AND status_type='customers' ORDER BY status LIMIT 50");
					while ($rows = $result->fetch_array()) {
						$status = $rows['id'];
						// ------------------------------------------------------------------------------------------------
					?>
						<?php
						// ======================================================
						$rs = $mysqli->query("SELECT id FROM `" . DB::CUSTOMERS . "` WHERE customer_status=$status");
						// echo $rs->num_rows;
						// ======================================================
						?>
						<span class="badge bg-light text-dark">
							<a href="listing_customers.php?customer_status=<?php echo $status; ?>" class="text-black fw-normal"><?php echo $rows['status']; ?> (<?php echo $rs->num_rows; ?>)</a>
						</span>

					<?php } // while 
					?>

			</div>
		</div>

		<div class="card">
			<div class="card-body">
				<table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
					<thead>
						<tr>
							<th>NAME</th>
							<th>EMAIL</th>
							<th>WORK PHONE</th>
							<th class="col-center">RECEIVABLES (BCY)</th>
							<th class="col-center">STATUS</th>
							<th class="col-center">APPROVAL</th>
							<th>ACTIONS</th>
						</tr>
					</thead>
				</table>
		</div>
	</div>

</div><!-- /content datatable-enhanced -->

<?php include('admin_elements/copyright.php'); ?>

</div><!-- /content-wrapper -->

<script>
$(document).ready(function() {
	var tableSelector = '#grid-<?php echo $module; ?>';
    
    // Get customer_status filter from URL
    var customerStatus = new URLSearchParams(window.location.search).get('customer_status') || '';
    
    // Initialize DataTable
	window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
        stateSave: false,     // Disable state saving to prevent conflicts
        deferRender: true,    // Defer rendering for performance
        retrieve: false,      // Don't retrieve existing instance
        ajax: {
            data: function(d) {
                // Pass module information to dispatcher
                d.module = 'customers';
                // Pass customer status filter
                if (customerStatus) {
                    d.customer_status = customerStatus;
                }
                return d;
            },
            error: function(xhr, status, error) {
                console.error('[Customers] DataTable AJAX error:', error);
                console.error('[Customers] Response:', xhr.responseText);
                alert('Error loading data. Please check console for details.');
            }
        },
        columns: [
            { data: 0, name: 'display_name', title: 'NAME' },
            { data: 1, name: 'email', title: 'EMAIL' },
            { data: 2, name: 'phone', title: 'WORK PHONE' },
            { data: 3, name: 'receivables', title: 'RECEIVABLES (BCY)' },
            { data: 4, name: 'publish', title: 'STATUS' },
            { data: 5, name: 'approved', title: 'APPROVAL' },
            { data: 6, title: 'ACTIONS', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        autoWidth: false,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>" +
            "rt" +
            "<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        columnDefs: [
            { targets: [3, 4, 5], className: 'col-center' }, // Center: RECEIVABLES, STATUS, APPROVAL
            { targets: [6], orderable: false } // Actions not sortable
		]
    });
    
    // Handle delete button clicks (using event delegation)
    $(document).on('click', 'a[data-action="delete_record"]', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const module = $(this).data('module');
        
        if (confirm('Are you sure you want to delete this customer?')) {
            // Use form submission method
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete_' + module + '"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>


