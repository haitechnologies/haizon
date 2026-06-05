<?php
include('admin_elements/admin_header.php');
require_once __DIR__ . '/../classes/InputValidator.php';

$module = 'invoices';
$module_caption = 'Invoice';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::INVOICES;  // Invoices table
$error_message = '';
$success_message = '';

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| GENEATE QR CODE AND PDF BOOKING
|--------------------------------------------------------------------------
|
*/
// --- Get From DB where qrcode=''
?>
<!-- <img src="generate_qrcode.php" alt=""> -->
<!-- <img src="generate.php?code=12345" alt=""> -->

<iframe src="generate_invoice_qrcode.php" width="1" height="1"></iframe>

<?php
// --- Get From DB where pdf=''
// include_once('pdf_invoice.php');

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
        log_error('CSRF token validation failed in listing_invoices.php', 'WARNING', __FILE__, __LINE__);
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

    // INPUT VALIDATION: Validate invoice ID
    $idResult = InputValidator::integer($id, 1);
    if (!$idResult['valid']) {
        $error_message = "Invalid invoice ID: " . $idResult['error'];
    } else {
        $invoiceId = $idResult['value'];
        
        // IDOR PROTECTION: Check ownership (unless system admin)
        $canDelete = has_full_access();
        if (!$canDelete) {
            $canDelete = checkOwnership($tbl_name, $invoiceId, 'created_by');
        }
        
        if (!$canDelete) {
            $error_message = "You do not have permission to delete this invoice";
            log_error("IDOR attempt: User $session_user_id tried to delete invoice $invoiceId", 'WARNING', __FILE__, __LINE__);
        } else {
            // Perform cascading delete with prepared statements
            // 1. Delete invoice items first
            $stmt1 = $mysqli->prepare("DELETE FROM `" . DB::INVOICE_ITEMS . "` WHERE invoice_id=?");
            $stmt1->bind_param("i", $invoiceId);
            $stmt1->execute();
            $stmt1->close();
            
            // 2. Then delete invoice
            $stmt2 = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id=?");
            $stmt2->bind_param("i", $invoiceId);
            
            if ($stmt2->execute()) {
                if ($stmt2->affected_rows > 0) {
                    $success_message = "$module_caption Deleted Successfully.";
                    header("Location:listing_$module.php?page=$page&success_message=$success_message");
                } else {
                    $error_message = "Could not delete record. It may have already been deleted.";
                }
            } else {
                $error_message = "Database error: " . $stmt2->error;
                log_error("Delete failed for invoice $invoiceId: " . $stmt2->error, 'ERROR', __FILE__, __LINE__);
            }
            $stmt2->close();
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

        <?php include('admin_elements/breadcrumb.php'); ?>

        <div class="card">

            <div class="card-body">
                    <!-- CSRF Protection Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                        <thead>
                            <tr>
                                <th width="100">DATE</th>
                                <th width="150">INVOICE #</th>
                                <th>ORDER NUMBER</th>
                                <th>CUSTOMER NAME</th>
                                <th class="col-center">STATUS</th>
                                <th>DUE DATE</th>
                                <th class="col-center">AMOUNT</th>
                                <th class="col-center">BALANCE DUE</th>
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
            { data: 5 },
            { data: 6 },
            { data: 7 }
        ],
        columnDefs: [
            { targets: [4, 6, 7], className: 'col-center' }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: {
            search: "",
            searchPlaceholder: "Search invoices...",
            lengthMenu: "_MENU_"
        }
    });

});
</script>

<?php include('admin_elements/admin_footer.php'); ?>

