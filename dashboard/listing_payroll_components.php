<?php
include('admin_elements/admin_header.php');

$module = 'payroll_components';
$module_caption = 'Payroll Components';
$tbl_name = DB::PAYROLL_COMPONENTS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| RESTRICT ACCESS: Only System Admin, Super Admin, and HR can view payroll components
|--------------------------------------------------------------------------
*/
if (!is_SystemAdmin() && !is_SuperAdmin() && is_role() != 'hr') {
    echo 'Permission Denied.';
    exit();
}

if (($action == "delete_$module" && !empty($id)) && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {

    // Check if component is being used in salary structures
    $usage_check = $mysqli->query("SELECT COUNT(*) as count FROM `" . DB::SALARY_STRUCTURES . "` WHERE component_id=$id");
    $usage = $usage_check->fetch_assoc();

    if ($usage['count'] > 0) {
        $error_message = "Cannot delete this component. It is currently assigned to {$usage['count']} employee(s) in salary structures. Please remove it from all salary structures first.";
    } else {
        // Safe to delete
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
        if ($mysqli->affected_rows > 0) {
            $success_message = "Component deleted successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "Unable to delete component. Please try again.";
        }
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
                    <h5 class="mb-0">Payroll Components</h5>
                    <a href="payroll_components.php" class="btn btn-primary">Add Component</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($error_message)) { ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            <strong>Error:</strong> <?php echo $error_message; ?>
                        </div>
                    <?php } ?>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">#</th>
                                    <th>Component Name</th>
                                    <th>Type</th>
                                    <th>Taxable</th>
                                    <th>Account ID</th>
                                    <th>In Use</th>
                                    <th>Status</th>
                                    <th width="180">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result = $mysqli->query("SELECT * FROM `$tbl_name` ORDER BY component_type DESC, component_name ASC");
                                $counter = 1; // Sequential numbering

                                while ($row = $result->fetch_array()) {
                                    // Check usage in salary structures
                                    $usage_query = $mysqli->query("SELECT COUNT(*) as count FROM `" . DB::SALARY_STRUCTURES . "` WHERE component_id=" . $row['id']);
                                    $usage_count = $usage_query->fetch_assoc()['count'];
                                    $is_in_use = $usage_count > 0;
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td class="fw-semibold"><?php echo s__($row['component_name']); ?></td>
                                        <td>
                                            <?php if ($row['component_type'] == 'earning') { ?>
                                                <span class="badge bg-success bg-opacity-20 text-success">
                                                    <i class="ph-plus-circle"></i> Earning
                                                </span>
                                            <?php } else { ?>
                                                <span class="badge bg-danger bg-opacity-20 text-danger">
                                                    <i class="ph-minus-circle"></i> Deduction
                                                </span>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo ($row['taxable'] ? '<span class="badge bg-info">Yes</span>' : '<span class="text-muted">No</span>'); ?></td>
                                        <td><?php echo s__($row['account_id']); ?></td>
                                        <td>
                                            <?php if ($is_in_use) { ?>
                                                <span class="badge bg-primary" title="Used by <?php echo $usage_count; ?> employee(s)">
                                                    <i class="ph-users"></i> <?php echo $usage_count; ?> employee<?php echo $usage_count > 1 ? 's' : ''; ?>
                                                </span>
                                            <?php } else { ?>
                                                <span class="text-muted">-</span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if ($row['is_active']) { ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php } else { ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <a href="payroll_components.php?action=edit_<?php echo $module; ?>&id=<?php echo $row['id']; ?>"
                                               class="btn btn-sm btn-primary"
                                               title="Edit component">
                                                <i class="ph-pencil"></i>
                                            </a>

                                            <?php if ($is_in_use) { ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-secondary"
                                                        disabled
                                                        title="Cannot delete: In use by <?php echo $usage_count; ?> employee(s)">
                                                    <i class="ph-lock"></i>
                                                </button>
                                            <?php } else { ?>
                                                <a href="listing_<?php echo $module; ?>.php?action=delete_<?php echo $module; ?>&id=<?php echo $row['id']; ?>"
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete \'<?php echo addslashes($row['component_name']); ?>\'?\n\nThis action cannot be undone.');"
                                                   title="Delete component">
                                                    <i class="ph-trash"></i>
                                                </a>
                                            <?php } ?>
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
