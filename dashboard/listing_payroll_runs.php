<?php

use App\Core\DB;
$module = 'payroll_runs';
$module_caption = 'Payroll Runs';
$tbl_name = DB::PAYROLL_RUNS;
$error_message = '';
$success_message = '';

include('admin_elements/admin_header.php');
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| RESTRICT ACCESS: Only System Admin, Super Admin, and HR can view payroll runs
|--------------------------------------------------------------------------
*/
if (!is_SystemAdmin() && !is_SuperAdmin() && is_role() != 'hr') {
    echo 'Permission Denied.';
    exit();
}

if (($action == "delete_$module" && !empty($id)) && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    // Delete associated payslips and payroll run items first
    $mysqli->query("DELETE FROM `" . DB::PAYSLIPS . "` WHERE payroll_run_id=$id");
    $mysqli->query("DELETE FROM `" . DB::table('payroll_run_items') . "` WHERE payroll_run_id=$id");
    $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
    if ($mysqli->affected_rows > 0) {
        $success_message = "Payroll run deleted successfully.";
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
                    <h5 class="mb-0">Payroll Runs</h5>
                    <a href="payroll_runs.php" class="btn btn-primary">
                        <i class="ph-plus me-2"></i>Create Payroll Run
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)) { ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            <strong>Success:</strong> <?php echo $success_message; ?>
                        </div>
                    <?php } ?>

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
                                    <th>Period Start</th>
                                    <th>Period End</th>
                                    <th>Status</th>
                                    <th>Total Gross</th>
                                    <th>Total Deductions</th>
                                    <th>Total Net</th>
                                    <th>Employees</th>
                                    <th width="220">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result = $mysqli->query("SELECT * FROM `$tbl_name` ORDER BY id DESC");
                                $counter = 1;

                                if ($result->num_rows == 0) {
                                    echo '<tr><td colspan="9" class="text-center text-muted py-4">No payroll runs found. Click "Create Payroll Run" to get started.</td></tr>';
                                } else {
                                    while ($row = $result->fetch_array()) {
                                        // Get employee count for this payroll run
                                        $employee_count_query = $mysqli->query("SELECT COUNT(*) as count FROM `" . DB::PAYSLIPS . "` WHERE payroll_run_id=" . $row['id']);
                                        $employee_count = $employee_count_query->fetch_assoc()['count'];
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><?php echo processDateYtoD($row['period_start']); ?></td>
                                        <td><?php echo processDateYtoD($row['period_end']); ?></td>
                                        <td>
                                            <?php if ($row['status'] == 'draft') { ?>
                                                <span class="badge bg-secondary">Draft</span>
                                            <?php } elseif ($row['status'] == 'approved') { ?>
                                                <span class="badge bg-success">Approved</span>
                                            <?php } elseif ($row['status'] == 'posted') { ?>
                                                <span class="badge bg-primary">Posted</span>
                                            <?php } else { ?>
                                                <span class="badge bg-warning"><?php echo ucfirst($row['status']); ?></span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-success fw-semibold">AED <?php echo number_format($row['total_gross'], 2); ?></td>
                                        <td class="text-danger">AED <?php echo number_format($row['total_deductions'], 2); ?></td>
                                        <td class="text-primary fw-bold">AED <?php echo number_format($row['total_net'], 2); ?></td>
                                        <td>
                                            <?php if ($employee_count > 0) { ?>
                                                <span class="badge bg-info"><?php echo $employee_count; ?> employee<?php echo $employee_count > 1 ? 's' : ''; ?></span>
                                            <?php } else { ?>
                                                <span class="text-muted">-</span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] == 'draft' && $row['total_gross'] == 0) { ?>
                                                <a href="process_payroll_run.php?id=<?php echo $row['id']; ?>"
                                                   class="btn btn-sm btn-success"
                                                   title="Generate payslips"
                                                   onclick="return confirm('Generate payslips for this payroll run?');">
                                                    <i class="ph-play"></i>
                                                </a>
                                            <?php } ?>
                                            <a href="view_payroll_run.php?id=<?php echo $row['id']; ?>"
                                               class="btn btn-sm btn-info"
                                               title="View details">
                                                <i class="ph-eye"></i>
                                            </a>
                                            <a href="payroll_runs.php?action=edit_<?php echo $module; ?>&id=<?php echo $row['id']; ?>"
                                               class="btn btn-sm btn-primary"
                                               title="Edit payroll run">
                                                <i class="ph-pencil"></i>
                                            </a>
                                            <?php if ($row['status'] == 'draft') { ?>
                                                <a href="listing_<?php echo $module; ?>.php?action=delete_<?php echo $module; ?>&id=<?php echo $row['id']; ?>"
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Delete this payroll run?\n\nThis will also delete all associated payslips and payroll items.');"
                                                   title="Delete">
                                                    <i class="ph-trash"></i>
                                                </a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php
                                    }
                                }
                                ?>
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
