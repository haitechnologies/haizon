<?php

declare(strict_types=1);

use App\Core\DB;
use App\Security\InputValidator;

include('admin_elements/admin_header.php');

$module = 'disposable_email_domains';
$module_caption = 'Disposable Email Domain';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::DISPOSABLE_EMAIL_DOMAINS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in listing_disposable_email_domains.php', 'WARNING', __FILE__, __LINE__);
        $action = '';
    }
}

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
                flash_success($success_message);
                header("Location: listing_{$module}.php");
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

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="60">ID</th>
        <th>DOMAIN</th>
        <th width="180">SOURCE</th>
        <th width="110">TYPE</th>
        <th width="120">ALLOWLISTED</th>
        <th width="140">UPDATED</th>
        <th width="80">ACTION</th>
    ',
    'columns' => [
        ['data' => 0],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4],
        ['data' => 5],
        ['data' => 6, 'orderable' => false, 'searchable' => false],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 25,
    'before_table' => '
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
    ',
    'extra_js' => "
        $(document).on('click', '[data-action=\"delete_record\"]', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var module = $(this).data('module');
            var csrfToken = $('input[name=\"csrf_token\"]').val();
            if (confirm('Are you sure you want to delete this domain from the blocklist?')) {
                $('<form>', { method: 'POST', action: 'listing_{$module}.php' })
                    .append($('<input>', { type: 'hidden', name: 'action', value: 'delete_' + module }))
                    .append($('<input>', { type: 'hidden', name: 'id', value: id }))
                    .append($('<input>', { type: 'hidden', name: 'csrf_token', value: csrfToken }))
                    .appendTo('body').submit();
            }
        });
    ",
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
