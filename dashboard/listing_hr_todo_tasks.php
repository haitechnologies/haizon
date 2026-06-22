<?php

declare(strict_types=1);

use App\Core\DB;
use App\Core\Container;
use App\Service\HrTodoTaskService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

include('admin_elements/admin_header.php');

$module = 'hr_todo_tasks';
$module_caption = 'HR To-Do Tasks';
$tbl_name = DB::HR_TODO_TASKS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$container = Container::getInstance();
/** @var HrTodoTaskService $hrTodoTaskService */
$hrTodoTaskService = $container->get(HrTodoTaskService::class);

if (($action == "delete_$module" && !empty($id))) {
    try {
        $hrTodoTaskService->delete((int)$id, $activeOrganizationId);
        $success_message = "HR To-Do Task deleted successfully.";
        flash_success($success_message);
        header("Location:listing_$module.php");
        exit;
    } catch (ValidationException $e) {
        $error_message = current($e->getErrors());
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
    } catch (\Throwable $e) {
        $error_message = "HR To-Do Task could not be deleted.";
    }
}

$canCreate = granted_('create', $module);

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'hide_add_button' => false,
    'thead' => '
        <th width="50">SR.</th>
        <th>Employee</th>
        <th>Task Type</th>
        <th>Description</th>
        <th>Due Date</th>
        <th>Status</th>
        <th width="100" class="col-center">Action</th>
    ',
    'columns' => [
        ['data' => 0, 'orderable' => false, 'searchable' => false],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4],
        ['data' => 5],
        ['data' => 6, 'orderable' => false, 'searchable' => false, 'className' => 'col-center text-center'],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 25,
    'messages' => [
        'success' => $success_message ?? '',
        'error' => $error_message ?? ''
    ],
];

ob_start();
include('admin_elements/hr_navbar.php');
$listingConfig['extra_header'] = ob_get_clean();

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');
