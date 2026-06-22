<?php

use App\Core\DB;
$module = 'report_hr';
$module_caption = 'HR Reports';
$error_message = '';
$success_message = '';

// Load bootstrap first so permissions.php can check auth before layout renders
require_once __DIR__ . '/bootstrap.php';
csrf_token();

include('admin_elements/permissions.php');

include('admin_elements/admin_header.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

// Get headcount by department
$dept_query = $mysqli->query("
    SELECT
        d.department,
        COUNT(u.id) as employee_count
    FROM `" . DB::DEPARTMENTS . "` d
    LEFT JOIN `" . DB::USERS . "` u ON d.id = u.department_id AND u.is_active = 1 AND u.id > 1
    GROUP BY d.id, d.department
    ORDER BY employee_count DESC
");

// Get total employees
$total_employees_query = $mysqli->query("SELECT COUNT(*) as total FROM `" . DB::USERS . "` WHERE id > 1 AND is_active = 1");
$total_employees = $total_employees_query->fetch_assoc()['total'];

// Get payroll summary
$payroll_summary = $mysqli->query("
    SELECT
        COUNT(DISTINCT employee_id) as employees_with_salary,
        SUM(CASE WHEN pc.component_type = 'earning' THEN ss.amount ELSE 0 END) as total_gross,
        SUM(CASE WHEN pc.component_type = 'deduction' THEN ss.amount ELSE 0 END) as total_deductions
    FROM `" . DB::SALARY_STRUCTURES . "` ss
    INNER JOIN `" . DB::PAYROLL_COMPONENTS . "` pc ON ss.component_id = pc.id
    WHERE (ss.effective_to IS NULL OR YEAR(ss.effective_to) = 0 OR ss.effective_to >= CURDATE())
    AND (ss.effective_from IS NULL OR YEAR(ss.effective_from) = 0 OR ss.effective_from <= CURDATE())
")->fetch_assoc();

$total_gross = floatval($payroll_summary['total_gross'] ?? 0);
$total_deductions = floatval($payroll_summary['total_deductions'] ?? 0);
$total_net = $total_gross - $total_deductions;
$employees_with_salary = intval($payroll_summary['employees_with_salary'] ?? 0);

// Get leave summary
$leave_summary = $mysqli->query("
    SELECT
        lt.leave_type,
        COUNT(lr.id) as request_count,
        SUM(CASE WHEN lr.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN lr.status = 'pending' THEN 1 ELSE 0 END) as pending_count
    FROM `" . DB::LEAVE_TYPES . "` lt
    LEFT JOIN `" . DB::LEAVE_REQUESTS . "` lr ON lt.id = lr.leave_type_id
    GROUP BY lt.id, lt.leave_type
    ORDER BY request_count DESC
");
?>

<div class="content-wrapper">
    <?php include('admin_elements/hr_navbar.php'); ?>

        <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <?php if (isset($module) && !empty($module)): ?>
                    <h1 class="h5 mb-0 d-inline-flex align-items-center gap-2">
                        <a href="listing_<?php echo $module; ?>.php" class="text-dark">All <?php echo ucwords(str_ireplace('_', " ", $module)); ?></a>
                        <?php if (!empty($pageHelpData)): ?>
                            <button type="button" class="page-help-trigger-btn" data-bs-toggle="offcanvas" data-bs-target="#pageHelpPanel" title="How to use this page" aria-label="Page help">
                                <i class="ph-question"></i>
                            </button>
                        <?php endif; ?>
                    </h1>
                <?php else: ?>
                    <h1 class="h5 mb-0 d-inline-flex align-items-center gap-2">
                        <?php echo !empty($module_caption) ? htmlspecialchars($module_caption) : 'Dashboard'; ?>
                        <?php if (!empty($pageHelpData)): ?>
                            <button type="button" class="page-help-trigger-btn" data-bs-toggle="offcanvas" data-bs-target="#pageHelpPanel" title="How to use this page" aria-label="Page help">
                                <i class="ph-question"></i>
                            </button>
                        <?php endif; ?>
                    </h1>
                <?php endif; ?>
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

            <!-- Summary Cards -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-fill">
                                    <h4 class="mb-0"><?php echo $total_employees; ?></h4>
                                    <span class="text-muted">Total Employees</span>
                                </div>
                                <i class="ph-users ph-3x text-primary opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-fill">
                                    <h4 class="mb-0"><?php echo $employees_with_salary; ?></h4>
                                    <span class="text-muted">On Payroll</span>
                                </div>
                                <i class="ph-money ph-3x text-success opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-fill">
                                    <h4 class="mb-0">AED <?php echo number_format($total_gross, 0); ?></h4>
                                    <span class="text-muted">Total Gross Salary</span>
                                </div>
                                <i class="ph-trending-up ph-3x text-info opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-fill">
                                    <h4 class="mb-0">AED <?php echo number_format($total_net, 0); ?></h4>
                                    <span class="text-muted">Total Net Salary</span>
                                </div>
                                <i class="ph-wallet ph-3x text-primary opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Headcount by Department -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ph-chart-bar me-2"></i>Headcount by Department</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Department</th>
                                    <th>Employee Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 1;
                                while ($dept = $dept_query->fetch_array()) {
                                    $percentage = $total_employees > 0 ? ($dept['employee_count'] / $total_employees * 100) : 0;
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td class="fw-semibold"><?php echo s__($dept['department']); ?></td>
                                        <td><?php echo $dept['employee_count']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-fill me-2" style="height: 20px;">
                                                    <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%">
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Leave Requests Summary -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ph-calendar-blank me-2"></i>Leave Requests Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Leave Type</th>
                                    <th>Total Requests</th>
                                    <th>Approved</th>
                                    <th>Pending</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 1;
                                if ($leave_summary->num_rows == 0) {
                                    echo '<tr><td colspan="5" class="text-center text-muted py-4">No leave requests found.</td></tr>';
                                } else {
                                    while ($leave = $leave_summary->fetch_array()) {
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td class="fw-semibold"><?php echo s__($leave['leave_type']); ?></td>
                                        <td><?php echo $leave['request_count']; ?></td>
                                        <td><span class="badge bg-success"><?php echo $leave['approved_count']; ?></span></td>
                                        <td><span class="badge bg-warning"><?php echo $leave['pending_count']; ?></span></td>
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

            <!-- Payroll Summary -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ph-money me-2"></i>Payroll Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="text-muted d-block mb-1">Total Gross Salary</label>
                                <h4 class="text-success mb-0">AED <?php echo number_format($total_gross, 2); ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="text-muted d-block mb-1">Total Deductions</label>
                                <h4 class="text-danger mb-0">AED <?php echo number_format($total_deductions, 2); ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="text-muted d-block mb-1">Total Net Salary</label>
                                <h4 class="text-primary mb-0">AED <?php echo number_format($total_net, 2); ?></h4>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted mb-0">
                        <i class="ph-info me-1"></i>
                        Based on current active salary structures for <?php echo $employees_with_salary; ?> employee<?php echo $employees_with_salary != 1 ? 's' : ''; ?>
                    </p>
                </div>
            </div>
        </div>

        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
