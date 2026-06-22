<?php

declare(strict_types=1);

use App\Core\DB;
use App\Core\Session;
use App\Security\InputValidator;

include('admin_elements/admin_header.php');

$module = 'banned_words';
$module_caption = 'Banned Word';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::BANNED_WORDS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in listing_banned_words.php', 'WARNING', __FILE__, __LINE__);
        $action = '';
    }
}

if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {
    $idResult = InputValidator::integer($id, 1);
    if (!$idResult['valid']) {
        $error_message = "Invalid banned word ID: " . $idResult['error'];
    } else {
        $wordId = $idResult['value'];

        $canDelete = has_full_access();
        if (!$canDelete) {
            $canDelete = checkOwnership($tbl_name, $wordId, 'created_by');
        }

        if (!$canDelete) {
            $error_message = "You do not have permission to delete this banned word";
            log_error("IDOR attempt: User Session::userId() tried to delete banned word $wordId", 'WARNING', __FILE__, __LINE__);
        } else {
            $stmt = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id=?");
            $stmt->bind_param("i", $wordId);

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
                log_error("Delete failed for banned word $wordId: " . $stmt->error, 'ERROR', __FILE__, __LINE__);
            }
            $stmt->close();
        }
    }
}

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="40">SR.</th>
        <th>BANNED WORD</th>
        <th width="140">CREATED AT</th>
        <th width="70">STATUS</th>
        <th width="90">ACTION</th>
    ',
    'columns' => [
        ['data' => 0],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4, 'orderable' => false, 'searchable' => false],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 10,
    'extra_js' => "
        // CSRF Token for AJAX operations
        $('input[name=\"csrf_token\"]').first().after('<input type=\"hidden\" name=\"csrf_token\" value=\"' + (window.HAI_CSRF_TOKEN || '') + '\">');

        $(document).on('click', '[data-action=\"delete_record\"]', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var module = $(this).data('module');
            var csrfToken = $('input[name=\"csrf_token\"]').val();
            if (confirm('Are you sure you want to delete this banned word?')) {
                var form = $('<form>', {
                    'method': 'POST',
                    'action': 'listing_<?php echo $module; ?>.php'
                }).append($('<input>', { 'type': 'hidden', 'name': 'action', 'value': 'delete_' + module }))
                  .append($('<input>', { 'type': 'hidden', 'name': 'id', 'value': id }))
                  .append($('<input>', { 'type': 'hidden', 'name': 'csrf_token', 'value': csrfToken }));
                $('body').append(form);
                form.submit();
            }
        });
    ",
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
