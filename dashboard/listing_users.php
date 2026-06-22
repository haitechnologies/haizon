<?php

declare(strict_types=1);

use App\Core\DB;
use App\Core\Session;
use App\Core\Container;
use App\Service\UserService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

include('admin_elements/admin_header.php');

$container = Container::getInstance();
$userService = $container->get(UserService::class);

$module = 'users';
$module_caption = 'Users';
$tbl_name = DB::USERS;
$error_message = '';
$success_message = '';

if (!has_full_access()) {
    echo 'Permission Denied.';
    exit();
}

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$handler_config = ['hard_delete' => false, 'ownership_check' => false, 'redirect_on_success' => false];
include('admin_elements/listing_handler.php');

if (($action == "delete_$module" && !empty($id))) {
    try {
        $user = $userService->getById((int)$id);
        $photo = $user->photo;
        $userService->delete((int)$id);

        if (!empty($photo)) {
            delete_photo($photo, $photo_upload_path, '1');
            delete_photo($photo, $photo_upload_path, '0');
        }
        $success_message = "Employee account deleted successfully.";
        flash_success($success_message);
    } catch (ValidationException $e) {
        $error_message = current($e->getErrors());
        flash_error($error_message);
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
        flash_error($error_message);
    } catch (\Throwable $e) {
        $error_message = "Unable to delete employee account. Please try again.";
        flash_error($error_message);
    }
}

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="60" class="col-center">ID</th>
        <th>NAME</th>
        <th>EMAIL</th>
        <th>CONTACT</th>
        <th>ROLE</th>
        <th width="140">LAST LOGIN</th>
        <th width="80" class="col-center">STATUS</th>
        <th width="90" class="col-center">ACTIONS</th>
    ',
    'columns' => [
        ['data' => 0],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4],
        ['data' => 5],
        ['data' => 6],
        ['data' => 7, 'orderable' => false, 'searchable' => false],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 10,
    'search_placeholder' => 'Search users...',
    'extra_js' => "
        $(document).on('click', '[data-action=\"delete_record\"]', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var module = $(this).data('module');
            if (confirm('Are you sure you want to delete this record?')) {
                var form = $('<form>', { 'method': 'POST', 'action': 'listing_{$module}.php' })
                    .append($('<input>', { 'type': 'hidden', 'name': 'action', 'value': 'delete_' + module }))
                    .append($('<input>', { 'type': 'hidden', 'name': 'id', 'value': id }));
                $('body').append(form);
                form.submit();
            }
        });
    ",
];

ob_start();
include('admin_elements/hr_navbar.php');
$listingConfig['extra_header'] = ob_get_clean();

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
