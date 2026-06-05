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
        header("Location:listing_$module.php?success_message=$success_message");
        exit;
    } catch (ValidationException $e) {
        $error_message = current($e->getErrors());
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
    } catch (\Throwable $e) {
        $error_message = "Leave type could not be deleted.";
    }
}
?>

<div class="content-wrapper">
    <?php include('admin_elements/page_header.php'); ?>
    <div class="content-inner">
        <div class="content">
            <?php include('admin_elements/breadcrumb.php'); ?>

            <?php if (!empty($success_message)) { ?>
                <div class="alert alert-success"> <?php echo $success_message; ?> </div>
            <?php } ?>
            <?php if (!empty($error_message)) { ?>
                <div class="alert alert-danger"> <?php echo $error_message; ?> </div>
            <?php } ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Leave Types</h5>
                    <a href="leave_types.php" class="btn btn-primary">Add Leave Type</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Leave Type</th>
                                    <th>Max Days/Year</th>
                                    <th>Paid</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $types = $leaveTypeService->list($activeOrganizationId);
                                foreach ($types as $typeDto) {
                                    $row = $typeDto->toArray();
                                ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo s__($row['leave_type']); ?></td>
                                        <td><?php echo $row['max_per_year'] == 0 ? 'Unlimited' : $row['max_per_year']; ?></td>
                                        <td>
                                            <?php if ($row['paid'] == 1) { ?>
                                                <span class="badge bg-success">Yes</span>
                                            <?php } else { ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <a href="leave_types.php?action=edit_<?php echo $module; ?>&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="listing_<?php echo $module; ?>.php?action=delete_<?php echo $module; ?>&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this leave type?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
