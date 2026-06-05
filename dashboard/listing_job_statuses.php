<?php
include('admin_elements/admin_header.php');
require_once __DIR__ . '/../classes/InputValidator.php';

$module = 'job_statuses';
$module_caption = 'Job Status';
$tbl_name = $tbl_prefix . $module;
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
$hide_add_button = true;


/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in listing_job_statuses.php', 'WARNING', __FILE__, __LINE__);
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

    // INPUT VALIDATION: Validate job status ID
    $idResult = InputValidator::integer($id, 1);
    if (!$idResult['valid']) {
        $error_message = "Invalid job status ID: " . $idResult['error'];
    } else {
        $statusId = $idResult['value'];
        
        // IDOR PROTECTION: Check ownership (unless system admin)
        $canDelete = has_full_access();
        if (!$canDelete) {
            $canDelete = checkOwnership($tbl_name, $statusId, 'created_by');
        }
        
        if (!$canDelete) {
            $error_message = "You do not have permission to delete this job status";
            log_error("IDOR attempt: User $session_user_id tried to delete job status $statusId", 'WARNING', __FILE__, __LINE__);
        } else {
            // Perform delete with prepared statement
            $stmt = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id=?");
            $stmt->bind_param("i", $statusId);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success_message = "Item deleted successfully.";
                    header("Location:listing_$module.php?success_message=$success_message");
                } else {
                    $error_message = "Could not delete record. It may have already been deleted.";
                }
            } else {
                $error_message = "Database error: " . $stmt->error;
                log_error("Delete failed for job status $statusId: " . $stmt->error, 'ERROR', __FILE__, __LINE__);
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

        <h5 class="ms-2">All <?php echo ucwords($module); ?></h6>

        <div class="card">

            <div class="card-body">
                        <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover order-column" width="100%"> <!-- table table-striped -->
                            <thead>
                                <tr>
                                    <th width="40">SR.</th>
                                    <th>JOB STATUS</th>
                                    <!-- <th width="90">CREATED AT</th> -->
                                    <!-- <th width="50">STATUS</th> -->
                                    <!-- <th width="90">ACTION</th> -->
                                </tr>
                            </thead>
                        </table>
                    </div>
        </div>

    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
