<?php

use App\Core\DB;
use App\Security\InputValidator;

include('admin_elements/admin_header.php');

$module = 'payment_methods';
$module_caption = 'Payment Method';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::PAYMENT_METHODS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

// CSRF TOKEN VALIDATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in listing_payment_methods.php', 'WARNING', __FILE__, __LINE__);
        $action = '';
    }
}

// DELETE
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {
    $idResult = InputValidator::integer($id, 1);
    if (!$idResult['valid']) {
        $error_message = "Invalid payment method ID: " . $idResult['error'];
    } else {
        $methodId = $idResult['value'];

        $canDelete = has_full_access();
        if (!$canDelete) {
            $canDelete = checkOwnership($tbl_name, $methodId, 'created_by');
        }

        if (!$canDelete) {
            $error_message = "You do not have permission to delete this payment method";
            log_error("IDOR attempt: User Session::userId() tried to delete payment method $methodId", 'WARNING', __FILE__, __LINE__);
        } else {
            $stmt = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id=?");
            $stmt->bind_param("i", $methodId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success_message = "Item deleted successfully.";
                    flash_success($success_message);
                    header("Location:listing_$module.php");
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

$editPerm = granted_('edit', $module) ? '1' : '0';
$deletePerm = granted_('delete', $module) ? '1' : '0';

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="40">SR.</th>
        <th>PAYMENT METHOD</th>
        <th width="90">CREATED AT</th>
        <th width="50">STATUS</th>
        <th width="90">ACTION</th>
    ',
    'columns' => [
        ['data' => 0],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4, 'orderable' => false],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 10,
    'extra_js' => "
        $(document).on('click', '[data-action=\"delete_record\"]', function(e) {
            e.preventDefault();
            if (confirm('Confirm delete?')) {
                var csrfToken = $('input[name=\"csrf_token\"]').val();
                var form = $('<form method=\"POST\" style=\"display:none;\"></form>')
                    .append($('<input type=\"hidden\" name=\"action\" />', { value: 'delete_' + $(this).data('module') }))
                    .append($('<input type=\"hidden\" name=\"id\" />', { value: $(this).data('id') }))
                    .append($('<input type=\"hidden\" name=\"csrf_token\" />', { value: csrfToken }));
                $('body').append(form);
                form.submit();
            }
        });
    ",
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
