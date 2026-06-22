<?php
declare(strict_types=1);

use App\Core\DB;
use App\Core\Container;
use App\Service\LeaveTypeService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

include('admin_elements/admin_header.php');

$module = 'leave_types';
$module_caption = 'Leave Types';
$tbl_name = DB::LEAVE_TYPES;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| RESTRICT ACCESS: Only System Admin, Super Admin, and HR can view leave types
|--------------------------------------------------------------------------
*/
if (!is_SystemAdmin() && !is_SuperAdmin() && is_role() != 'hr') {
    echo 'Permission Denied.';
    exit();
}

$container = Container::getInstance();
/** @var LeaveTypeService $leaveTypeService */
$leaveTypeService = $container->get(LeaveTypeService::class);

if (($action == "delete_$module" && !empty($id)) && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    try {
        $leaveTypeService->delete((int)$id, $activeOrganizationId);
        $success_message = "Leave type deleted successfully.";
        flash_success($success_message);
        header("Location:listing_$module.php");
        exit;
    } catch (ValidationException $e) {
        $error_message = current($e->getErrors());
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
    } catch (\Throwable $e) {
        $error_message = "Leave type could not be deleted.";
    }
}
$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="50">ID</th>
        <th>Leave Type</th>
        <th>Max Days/Year</th>
        <th>Paid</th>
        <th width="100" class="col-center">Action</th>
    ',
    'columns' => [
        ['data' => 0],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4, 'orderable' => false, 'searchable' => false, 'className' => 'col-center text-center'],
    ],
    'order' => [[0, 'desc']], // order by id desc
    'page_length' => 25,
    'messages' => [
        'success' => $success_message ?? '',
        'error' => $error_message ?? ''
    ]
];

ob_start();
include('admin_elements/hr_navbar.php');
$listingConfig['extra_header'] = ob_get_clean();

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
?>
