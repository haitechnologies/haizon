<?php

use App\Core\DB;
use App\Security\InputValidator;
include('admin_elements/admin_header.php');
// Removed legacy require for autoloader compatibility: require_once __DIR__ . '/../classes/InputValidator.php';

$module = 'organizations';
$module_caption = 'Organization';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::ORGANIZATIONS;
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
        log_error('CSRF token validation failed in listing_organizations.php', 'WARNING', __FILE__, __LINE__);
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
$hasDeletePermission = granted_('delete', $module) || granted('delete', $module_id);
if (($action == "delete_$module" && !empty($id)) && $hasDeletePermission) {

    // INPUT VALIDATION: Validate organization ID
    $idResult = InputValidator::integer($id, 1);
    if (!$idResult['valid']) {
        $error_message = "Invalid organization ID: " . $idResult['error'];
    } else {
        $orgId = $idResult['value'];
        
        // IDOR PROTECTION: Check ownership (unless system admin).
        // Legacy compatibility: if created_by is missing or unassigned, fall back to module delete permission.
        $canDelete = has_full_access();
        if (!$canDelete) {
            $hasCreatedByColumn = false;
            $columnStmt = $mysqli->prepare(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'created_by' LIMIT 1"
            );

            if ($columnStmt) {
                $columnStmt->bind_param('s', $tbl_name);
                if ($columnStmt->execute()) {
                    $columnResult = $columnStmt->get_result();
                    $hasCreatedByColumn = $columnResult && $columnResult->num_rows > 0;
                }
                $columnStmt->close();
            }

            if ($hasCreatedByColumn) {
                $ownerId = 0;
                $ownerStmt = $mysqli->prepare("SELECT created_by FROM `" . $tbl_name . "` WHERE id = ? LIMIT 1");
                if ($ownerStmt) {
                    $ownerStmt->bind_param('i', $orgId);
                    if ($ownerStmt->execute()) {
                        $ownerRow = $ownerStmt->get_result()->fetch_assoc();
                        $ownerId = (int)($ownerRow['created_by'] ?? 0);
                    }
                    $ownerStmt->close();
                }

                if ($ownerId > 0) {
                    $canDelete = ($ownerId === (int)$session_user_id);
                } else {
                    $canDelete = granted_('delete', $module);
                }
            } else {
                $canDelete = granted_('delete', $module);
            }
        }
        
        if (!$canDelete) {
            $error_message = "You do not have permission to delete this organization";
            log_error("IDOR attempt: User $session_user_id tried to delete organization $orgId", 'WARNING', __FILE__, __LINE__);
        } else {
            // Perform delete with prepared statement
            $stmt = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id=?");
            $stmt->bind_param("i", $orgId);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success_message = "Item deleted successfully.";
                    header("Location:listing_$module.php?success_message=" . urlencode($success_message));
                    exit;
                } else {
                    $error_message = "Could not delete record. It may have already been deleted.";
                    header("Location:listing_$module.php?error_message=" . urlencode($error_message));
                    exit;
                }
            } else {
                $error_message = "Database error: " . $stmt->error;
                log_error("Delete failed for organization $orgId: " . $stmt->error, 'ERROR', __FILE__, __LINE__);
                header("Location:listing_$module.php?error_message=" . urlencode('Could not delete record due to a database constraint.'));
                exit;
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
                                    <th width="50">ID</th>
                                    <th>ORGANIZATION NAME</th>
                                    <th>PHONE</th>
                                    <th>EMAIL</th>
                                    <th width="90">CREATED AT</th>
                                    <th width="60">STATUS</th>
                                    <th width="90">ACTION</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
        </div>

        <div class="alert alert-info border-0 alert-dismissible fade show">
            <span class="fw-semibold">Logo:</span> is to display on PDFs (Quotatations, Sale Orders, Invoices etc)
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>
<?php include('admin_elements/admin_footer.php'); ?>

<script>
$(document).ready(function() {
    var module = 'organizations';

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
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6, orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });
    
    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this record?')) {
            var csrfToken = $('input[name="csrf_token"]').val();
            var form = $('<form method="POST" style="display:none;"></form>')
                .append($('<input type="hidden" name="action" />', { value: 'delete_' + $(this).data('module') }))
                .append($('<input type="hidden" name="id" />', { value: $(this).data('id') }))
                .append($('<input type="hidden" name="csrf_token" />', { value: csrfToken }));
            $('body').append(form);
            form.submit();
        }
    });
});
</script>


