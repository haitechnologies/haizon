<?php

use App\Core\DB;
$module = 'employee_salaries';
$module_caption = 'Employee Salaries';
$error_message = '';
$success_message = '';

include('admin_elements/admin_header.php');
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| RESTRICT ACCESS: Only System Admin, Super Admin, and HR
|--------------------------------------------------------------------------
*/
if (!is_SystemAdmin() && !is_SuperAdmin() && is_role() != 'hr') {
    echo 'Permission Denied.';
    exit();
}
?>

<div class="content-wrapper">
    <?php include('admin_elements/page_header.php'); ?>

    <div class="content-inner">
        <div class="content">
            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Employee Salaries Overview</h5>
                    <a href="salary_structures.php" class="btn btn-primary">
                        <i class="ph-plus me-2"></i>Add Salary Component
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">#</th>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Gross Salary</th>
                                    <th>Total Deductions</th>
                                    <th>Net Salary</th>
                                    <th>Components</th>
                                    <th width="120">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get all active employees
                                $employees_query = $mysqli->query("
                                    SELECT u.id, u.full_name, d.department
                                    FROM `" . DB::USERS . "` u
                                    LEFT JOIN `" . DB::DEPARTMENTS . "` d ON u.department_id = d.id
                                    WHERE u.id > 1 AND u.is_active = 1
                                    ORDER BY u.full_name ASC
                                ");

                                $counter = 1;

                                while ($emp = $employees_query->fetch_array()) {
                                    $employee_id = $emp['id'];
                                    $employee_name = s__($emp['full_name']);
                                    $department = s__($emp['department']) ?: '-';

                                    // Calculate salary breakdown for this employee
                                    $salary_query = $mysqli->query("
                                        SELECT
                                            ss.component_id,
                                            pc.component_name,
                                            pc.component_type,
                                            ss.amount,
                                            ss.effective_from,
                                            ss.effective_to
                                        FROM `" . DB::SALARY_STRUCTURES . "` ss
                                        INNER JOIN `" . DB::PAYROLL_COMPONENTS . "` pc ON ss.component_id = pc.id
                                        WHERE ss.employee_id = $employee_id
                                        AND pc.is_active = 1
                                        AND (ss.effective_to IS NULL OR ss.effective_to >= CURDATE())
                                        AND (ss.effective_from IS NULL OR ss.effective_from <= CURDATE())
                                    ");

                                    $gross_salary = 0;
                                    $total_deductions = 0;
                                    $component_count = 0;
                                    $components_list = [];

                                    while ($sal = $salary_query->fetch_array()) {
                                        $component_count++;
                                        $amount = floatval($sal['amount']);

                                        if ($sal['component_type'] == 'earning') {
                                            $gross_salary += $amount;
                                            $components_list[] = [
                                                'name' => s__($sal['component_name']),
                                                'type' => 'earning',
                                                'amount' => $amount
                                            ];
                                        } else {
                                            $total_deductions += $amount;
                                            $components_list[] = [
                                                'name' => s__($sal['component_name']),
                                                'type' => 'deduction',
                                                'amount' => $amount
                                            ];
                                        }
                                    }

                                    $net_salary = $gross_salary - $total_deductions;

                                    // Only show employees with salary components
                                    if ($component_count > 0) {
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td class="fw-semibold"><?php echo $employee_name; ?></td>
                                        <td><?php echo $department; ?></td>
                                        <td><span class="text-success fw-semibold">AED <?php echo number_format($gross_salary, 2); ?></span></td>
                                        <td><span class="text-danger">AED <?php echo number_format($total_deductions, 2); ?></span></td>
                                        <td><span class="text-primary fw-bold">AED <?php echo number_format($net_salary, 2); ?></span></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#componentsModal<?php echo $employee_id; ?>">
                                                <i class="ph-list"></i> View <?php echo $component_count; ?> Component<?php echo $component_count > 1 ? 's' : ''; ?>
                                            </button>

                                            <!-- Components Modal -->
                                            <div class="modal fade" id="componentsModal<?php echo $employee_id; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Salary Components - <?php echo $employee_name; ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <h6 class="text-success"><i class="ph-plus-circle me-1"></i>Earnings</h6>
                                                                <ul class="list-unstyled">
                                                                    <?php
                                                                    $earnings_total = 0;
                                                                    foreach ($components_list as $comp) {
                                                                        if ($comp['type'] == 'earning') {
                                                                            $earnings_total += $comp['amount'];
                                                                            echo '<li class="d-flex justify-content-between mb-1">';
                                                                            echo '<span>' . $comp['name'] . '</span>';
                                                                            echo '<span class="fw-semibold">AED ' . number_format($comp['amount'], 2) . '</span>';
                                                                            echo '</li>';
                                                                        }
                                                                    }
                                                                    if ($earnings_total == 0) echo '<li class="text-muted">No earnings</li>';
                                                                    ?>
                                                                </ul>
                                                                <hr>
                                                                <div class="d-flex justify-content-between fw-bold text-success">
                                                                    <span>Total Earnings:</span>
                                                                    <span>AED <?php echo number_format($earnings_total, 2); ?></span>
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <h6 class="text-danger"><i class="ph-minus-circle me-1"></i>Deductions</h6>
                                                                <ul class="list-unstyled">
                                                                    <?php
                                                                    $deductions_total = 0;
                                                                    foreach ($components_list as $comp) {
                                                                        if ($comp['type'] == 'deduction') {
                                                                            $deductions_total += $comp['amount'];
                                                                            echo '<li class="d-flex justify-content-between mb-1">';
                                                                            echo '<span>' . $comp['name'] . '</span>';
                                                                            echo '<span class="fw-semibold">AED ' . number_format($comp['amount'], 2) . '</span>';
                                                                            echo '</li>';
                                                                        }
                                                                    }
                                                                    if ($deductions_total == 0) echo '<li class="text-muted">No deductions</li>';
                                                                    ?>
                                                                </ul>
                                                                <hr>
                                                                <div class="d-flex justify-content-between fw-bold text-danger">
                                                                    <span>Total Deductions:</span>
                                                                    <span>AED <?php echo number_format($deductions_total, 2); ?></span>
                                                                </div>
                                                            </div>

                                                            <div class="alert alert-primary mb-0">
                                                                <div class="d-flex justify-content-between">
                                                                    <strong>Net Salary:</strong>
                                                                    <strong>AED <?php echo number_format($net_salary, 2); ?></strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="salary_structures.php?employee_id=<?php echo $employee_id; ?>"
                                               class="btn btn-sm btn-primary"
                                               title="Manage salary">
                                                <i class="ph-pencil"></i> Edit
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
