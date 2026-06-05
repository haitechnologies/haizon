<?php

use App\Core\DB;
include('admin_elements/admin_header.php');

$module = 'leave_requests';
$module_caption = 'Leave Requests';
$tbl_name = DB::LEAVE_REQUESTS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| RESTRICT ACCESS: Only System Admin, Super Admin, and HR can view leave requests
|--------------------------------------------------------------------------
*/
if (!is_SystemAdmin() && !is_SuperAdmin() && is_role() != 'hr') {
    echo 'Permission Denied.';
    exit();
}

if (($action == "delete_$module" && !empty($id)) && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
    if ($mysqli->affected_rows > 0) {
        $success_message = "Item deleted successfully.";
        header("Location:listing_$module.php?success_message=$success_message");
        exit;
    }
}
?>

<div class="content-wrapper">
    <?php include('admin_elements/page_header.php'); ?>
    <div class="content-inner">
        <div class="content">
            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Leave Requests</h5>
                    <a href="leave_requests.php" class="btn btn-primary">Add Leave</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result = $mysqli->query("SELECT * FROM `$tbl_name` ORDER BY id DESC");
                                while ($row = $result->fetch_array()) {
                                ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo getTableAttr('full_name', DB::USERS, $row['employee_id']); ?></td>
                                        <td><?php echo getTableAttr('leave_type', DB::LEAVE_TYPES, $row['leave_type_id']); ?></td>
                                        <td><?php echo s__($row['start_date']); ?></td>
                                        <td><?php echo s__($row['end_date']); ?></td>
                                        <td><?php echo s__($row['total_days']); ?></td>
                                        <td><?php echo s__($row['status']); ?></td>
                                        <td>
                                            <a href="leave_requests.php?action=edit_<?php echo $module; ?>&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="listing_<?php echo $module; ?>.php?action=delete_<?php echo $module; ?>&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?');">Delete</a>
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
