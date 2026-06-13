<?php

use App\Core\DB;
use App\Security\InputValidator;
include('admin_elements/admin_header.php');
// Removed legacy require for autoloader compatibility: require_once __DIR__ . '/../classes/InputValidator.php';

$module = 'disposable_email_domains';
$module_caption = 'Disposable Email Domain';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::DISPOSABLE_EMAIL_DOMAINS;
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
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in listing_disposable_email_domains.php', 'WARNING', __FILE__, __LINE__);
        $action = '';
    }
}

/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {

    $idResult = InputValidator::integer($id, 1);
    if (!$idResult['valid']) {
        $error_message = 'Invalid domain ID: ' . $idResult['error'];
    } else {
        $domainId = $idResult['value'];

        $stmt = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id = ?");
        $stmt->bind_param('i', $domainId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $success_message = 'Domain deleted successfully.';
                header("Location: listing_{$module}.php?success_message=" . urlencode($success_message));
                exit;
            } else {
                $error_message = 'Could not delete record. It may have already been deleted.';
            }
        } else {
            $error_message = 'Database error: ' . $stmt->error;
            log_error("Delete failed for domain $domainId: " . $stmt->error, 'ERROR', __FILE__, __LINE__);
        }
        $stmt->close();
    }
}
?>

<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h1 class="h5 mb-0 d-inline-flex align-items-center gap-2">
                    <a href="listing_<?php echo $module; ?>.php" class="text-dark">All <?php echo ucwords(str_ireplace('_', " ", $module)); ?></a>
                    <?php if (!empty($pageHelpData)): ?>
                        <button type="button" class="page-help-trigger-btn" data-bs-toggle="offcanvas" data-bs-target="#pageHelpPanel" title="How to use this page" aria-label="Page help">
                            <i class="ph-question"></i>
                        </button>
                    <?php endif; ?>
                </h1>
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

    <div class="content datatable-enhanced">
        <!-- CSRF Token for AJAX operations -->
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <?php include('admin_elements/breadcrumb.php'); ?>

        <!-- Stats bar -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info d-flex align-items-center gap-3 py-2" role="alert">
                    <i class="ph-shield-check fs-5"></i>
                    <div>
                        <strong>Disposable Email Protection</strong> — domains listed here are blocked during
                        registration. Use the
                        <a href="../scripts/update_disposable_email_list.php" class="alert-link" target="_blank">
                            update script
                        </a>
                        to pull the latest domain lists from GitHub sources.
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover order-column" width="100%">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>DOMAIN</th>
                            <th width="180">SOURCE</th>
                            <th width="110">TYPE</th>
                            <th width="120">ALLOWLISTED</th>
                            <th width="140">UPDATED</th>
                            <th width="80">ACTION</th>
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
        lengthMenu: [[25, 50, 100, 250], [25, 50, 100, 250]],
        pageLength: 25,
        ajax: {
            data: function(d) {
                d.delete_permission = <?php echo granted('delete', $module_id) ? '1' : '0'; ?>;
                d.csrf_token = $('input[name="csrf_token"]').val();
                return d;
            },
            error: function(xhr, status, error) {
                console.error('[DisposableEmailDomains] DataTable AJAX error:', error);
                console.error('[DisposableEmailDomains] Response:', xhr.responseText);
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

    // ========================================
    // Delete Record Handler
    // ========================================
    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();

        var id     = $(this).data('id');
        var module = $(this).data('module');
        var csrfToken = $('input[name="csrf_token"]').val();

        if (confirm('Are you sure you want to delete this domain from the blocklist?')) {
            $('<form>', { method: 'POST', action: 'listing_<?php echo $module; ?>.php' })
                .append($('<input>', { type: 'hidden', name: 'action',     value: 'delete_' + module }))
                .append($('<input>', { type: 'hidden', name: 'id',         value: id }))
                .append($('<input>', { type: 'hidden', name: 'csrf_token', value: csrfToken }))
                .appendTo('body').submit();
        }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>
