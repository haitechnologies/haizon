<?php

declare(strict_types=1);

use App\Security\InputValidator;

include('admin_elements/admin_header.php');

$module = 'job_statuses';
$module_caption = 'Job Status';
$tbl_name = $tbl_prefix . $module;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();
$hide_add_button = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in listing_job_statuses.php', 'WARNING', __FILE__, __LINE__);
        $action = '';
    }
}

if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {
    $idResult = InputValidator::integer($id, 1);
    if (!$idResult['valid']) {
        $error_message = "Invalid job status ID: " . $idResult['error'];
    } else {
        $statusId = $idResult['value'];

        $canDelete = has_full_access();
        if (!$canDelete) {
            $canDelete = checkOwnership($tbl_name, $statusId, 'created_by');
        }

        if (!$canDelete) {
            $error_message = "You do not have permission to delete this job status";
            log_error("IDOR attempt: User Session::userId() tried to delete job status $statusId", 'WARNING', __FILE__, __LINE__);
        } else {
            $stmt = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id=?");
            $stmt->bind_param("i", $statusId);

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
                log_error("Delete failed for job status $statusId: " . $stmt->error, 'ERROR', __FILE__, __LINE__);
            }
            $stmt->close();
        }
    }
}

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'hide_add_button' => true,
    'thead' => '
        <th width="40">SR.</th>
        <th>JOB STATUS</th>
    ',
    'columns' => [
        ['data' => 0, 'orderable' => false, 'searchable' => false],
        ['data' => 1, 'className' => 'text-start'],
    ],
    'order' => [[0, 'asc']],
    'page_length' => 25,
    'search_placeholder' => 'Search job statuses...',
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
