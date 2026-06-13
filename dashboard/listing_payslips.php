<?php

$module = 'payslips';
$module_caption = 'Payslips';
$error_message = '';
$success_message = '';

include('admin_elements/admin_header.php');

use App\Core\DB;
$tbl_name = DB::PAYSLIPS;

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| RESTRICT ACCESS: Only System Admin, Super Admin, and HR can view payslips
|--------------------------------------------------------------------------
*/
if (!is_SystemAdmin() && !is_SuperAdmin() && is_role() != 'hr') {
    echo 'Permission Denied.';
    exit();
}
?>

<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h1 class="h5 mb-0 d-inline-flex align-items-center gap-2">
                    <a href="listing_<?php echo $module; ?>.php" class="text-dark">All <?php echo ucwords(str_ireplace('_', " ", $module)); ?></a>
                    <?php if (!empty($pageHelpData)): ?>
                        <button type="button" class="page-help-trigger-btn" data-bs-toggle="offcanvas" data-bs-target="#pageHelpPanel" title="How to use this page" aria-label="Page help">
                            <i class="ph-question"></i>
                        </button>
                    <?php endif; ?>
                </h1>
            </div>

            <div class="my-1">
                <?php if (empty($hide_add_button) && isset($module_id) && isset($module) && granted('create', $module_id)) { ?>
                    <a href="<?php echo $module; ?>.php" class="btn btn-primary btn-sm d-inline-flex align-items-center">
                        <i class="ph-plus ph-sm me-2 opacity-75"></i>New
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>
    <!-- /page header -->
    <div class="content-inner">
        <div class="content">
            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Payslips</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">#</th>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Payroll Period</th>
                                    <th>Gross Salary</th>
                                    <th>Deductions</th>
                                    <th>Net Salary</th>
                                    <th>Status</th>
                                    <th width="100">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result = $mysqli->query("
                                    SELECT
                                        ps.*,
                                        u.full_name,
                                        d.department,
                                        pr.period_start,
                                        pr.period_end
                                    FROM `$tbl_name` ps
                                    INNER JOIN `" . DB::USERS . "` u ON ps.employee_id = u.id
                                    LEFT JOIN `" . DB::DEPARTMENTS . "` d ON u.department_id = d.id
                                    INNER JOIN `" . DB::PAYROLL_RUNS . "` pr ON ps.payroll_run_id = pr.id
                                    ORDER BY ps.id DESC
                                ");

                                $counter = 1;

                                if ($result->num_rows == 0) {
                                    echo '<tr><td colspan="9" class="text-center text-muted py-4">No payslips found. Generate payslips from Payroll Runs.</td></tr>';
                                } else {
                                    while ($row = $result->fetch_array()) {
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td class="fw-semibold"><?php echo s__($row['full_name']); ?></td>
                                        <td><?php echo s__($row['department']) ?: '-'; ?></td>
                                        <td><?php echo processDateYtoD($row['period_start']) . ' - ' . processDateYtoD($row['period_end']); ?></td>
                                        <td class="text-success fw-semibold">AED <?php echo number_format($row['gross'], 2); ?></td>
                                        <td class="text-danger">AED <?php echo number_format($row['deductions'], 2); ?></td>
                                        <td class="text-primary fw-bold">AED <?php echo number_format($row['net'], 2); ?></td>
                                        <td>
                                            <?php if ($row['status'] == 'generated') { ?>
                                                <span class="badge bg-info">Generated</span>
                                            <?php } elseif ($row['status'] == 'submitted') { ?>
                                                <span class="badge bg-success">Submitted</span>
                                            <?php } elseif ($row['status'] == 'paid') { ?>
                                                <span class="badge bg-primary">Paid</span>
                                            <?php } else { ?>
                                                <span class="badge bg-secondary"><?php echo ucfirst($row['status']); ?></span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <a href="view_payslip.php?id=<?php echo $row['id']; ?>"
                                               class="btn btn-sm btn-info"
                                               title="View payslip">
                                                <i class="ph-eye"></i>
                                            </a>
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
