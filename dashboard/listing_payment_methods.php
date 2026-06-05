<?php

use App\Core\DB;
use App\Security\InputValidator;
include('admin_elements/admin_header.php');
// Removed legacy require for autoloader compatibility: require_once __DIR__ . '/../classes/InputValidator.php';

$module = 'payment_methods';
$module_caption = 'Payment Method';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::PAYMENT_METHODS;
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
        log_error('CSRF token validation failed in listing_payment_methods.php', 'WARNING', __FILE__, __LINE__);
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

    // INPUT VALIDATION: Validate payment method ID
    $idResult = InputValidator::integer($id, 1);
    if (!$idResult['valid']) {
        $error_message = "Invalid payment method ID: " . $idResult['error'];
    } else {
        $methodId = $idResult['value'];
        
        // IDOR PROTECTION: Check ownership (unless system admin)
        $canDelete = has_full_access();
        if (!$canDelete) {
            $canDelete = checkOwnership($tbl_name, $methodId, 'created_by');
        }
        
        if (!$canDelete) {
            $error_message = "You do not have permission to delete this payment method";
            log_error("IDOR attempt: User $session_user_id tried to delete payment method $methodId", 'WARNING', __FILE__, __LINE__);
        } else {
            // Perform delete with prepared statement
            $stmt = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id=?");
            $stmt->bind_param("i", $methodId);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success_message = "Item deleted successfully.";
                    header("Location:listing_$module.php?success_message=$success_message");
                } else {
                    $error_message = "Could not delete record. It may have already been deleted.";
                }
            } else {
                $error_message = "Database error: " . $stmt->error;
                log_error("Delete failed for payment method $methodId: " . $stmt->error, 'ERROR', __FILE__, __LINE__);
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
                        <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover order-column" width="100%"> <!-- table table-striped -->
                            <thead>
                                <tr>
                                    <th width="40">SR.</th>
                                    <th>PAYMENT METHOD</th>
                                    <th width="90">CREATED AT</th>
                                    <th width="50">STATUS</th>
                                    <th width="90">ACTION</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
        </div>

    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>

<?php include('admin_elements/admin_footer.php'); ?>

<script>
$(document).ready(function() {
    var module = 'payment_methods';
    window.HAIDatatableInitializer.init('#grid-' + module, module, {
        stateSave: false,     // Disable state saving to prevent conflicts
        deferRender: true,    // Defer rendering for performance
        retrieve: false,      // Don't retrieve existing instance
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        pageLength: 10,
        ajax: {
            data: function(d) {
                d.edit_permission = <?php echo granted_('edit', $module) ? '1' : '0'; ?>;
                d.delete_permission = <?php echo granted_('delete', $module) ? '1' : '0'; ?>;
                return d;
            },
            error: function(xhr, status, error) {
                console.error('[<?php echo ucfirst($module); ?>] DataTable AJAX error:', error);
                console.error('[<?php echo ucfirst($module); ?>] Response:', xhr.responseText);
            }
        },
        columns: [{ data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4, orderable: false }],
        order: [[0, 'desc']]
    });
    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();
        if (confirm('Confirm delete?')) {
            var csrfToken = $('input[name="csrf_token"]').val();
            var form = $('<form method="POST" style="display:none;"></form>')
                .append($('<input type="hidden" name="action" />', { value: 'delete_' + $(this).data('module') }))
                .append($('<input type="hidden" name="id" />', { value: $(this).data('id') }))
                .append($('<input type="hidden" name="csrf_token" />', { value: csrfToken }));
            $('body').append(form); form.submit();
        }
    });
});
</script>

