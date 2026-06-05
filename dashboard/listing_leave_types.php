<?php

use App\Core\DB;
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

if (($action == "delete_$module" && !empty($id)) && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
    if ($mysqli->affected_rows > 0) {
        $success_message = "Leave type deleted successfully.";
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

            <?php if (!empty($success_message)) { ?>
                <div class="alert alert-success"> <?php echo $success_message; ?> </div>
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
                                $result = $mysqli->query("SELECT * FROM `$tbl_name` ORDER BY leave_type ASC");
                                while ($row = $result->fetch_array()) {
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
