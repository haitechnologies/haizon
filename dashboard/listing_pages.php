<?php
include('admin_elements/admin_header.php');
require_once __DIR__ . '/../classes/InputValidator.php';

$module = 'pages';
$module_caption = 'Page';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::PAGES;  // Pages table
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

$compliance_only = !empty($_GET['compliance_only']);


/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in listing_pages.php', 'WARNING', __FILE__, __LINE__);
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

    // INPUT VALIDATION: Validate page ID
    $idResult = InputValidator::integer($id, 1);
    if (!$idResult['valid']) {
        $error_message = "Invalid page ID: " . $idResult['error'];
    } else {
        $pageId = $idResult['value'];
        
        // IDOR PROTECTION: Check ownership (unless system admin)
        $canDelete = has_full_access();
        if (!$canDelete) {
            $canDelete = checkOwnership($tbl_name, $pageId, 'created_by');
        }
        
        if (!$canDelete) {
            $error_message = "You do not have permission to delete this page";
            log_error("IDOR attempt: User $session_user_id tried to delete page $pageId", 'WARNING', __FILE__, __LINE__);
        } else {
            // Perform delete with prepared statement
            $stmt = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id=?");
            $stmt->bind_param("i", $pageId);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success_message = "Page deleted successfully.";
                    header("Location:listing_$module.php?success_message=$success_message");
                } else {
                    $error_message = "Could not delete record. It may have already been deleted.";
                }
            } else {
                $error_message = "Database error: " . $stmt->error;
                log_error("Delete failed for page $pageId: " . $stmt->error, 'ERROR', __FILE__, __LINE__);
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

            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-semibold">Pages</span>
                <div class="btn-group btn-group-sm" role="group" aria-label="Pages filter">
                    <a href="listing_pages.php" class="btn btn-outline-primary<?php echo $compliance_only ? '' : ' active'; ?>">All Pages</a>
                    <a href="listing_pages.php?compliance_only=1" class="btn btn-outline-primary<?php echo $compliance_only ? ' active' : ''; ?>">Compliance Only</a>
                </div>
            </div>

            <div class="card-body">
                <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover order-column" width="100%">
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th>TITLE</th>
                            <th width="120">TEMPLATE</th>
                            <th width="100">MENU</th>
                            <th width="70">VIEWS</th>
                            <th width="110">UPDATED</th>
                            <th width="140">STATUS</th>
                            <th width="90">ACTIONS</th>
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
        pageLength: 10,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: 'datatables.php',
            type: 'POST',
            data: function(d) {
                d.csrf_token = window.HAI_CSRF_TOKEN || $('input[name="csrf_token"]').first().val() || '';
                d.action = '<?php echo $action; ?>';
                d.compliance_only = <?php echo $compliance_only ? '1' : '0'; ?>;
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
            { data: 0, width: "50px" },
            { data: 1 },
            { data: 2, width: "120px" },
            { data: 3, width: "100px" },
            { data: 4, width: "70px" },
            { data: 5, width: "110px" },
            { data: 6, width: "140px" },
            { data: 7, orderable: false, searchable: false, width: "90px" }
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
        
        if (confirm('Are you sure you want to delete this page?')) {
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


