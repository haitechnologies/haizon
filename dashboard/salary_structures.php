<?php
$module = 'salary_structures';
$module_caption = 'Salary Structure';
$tbl_name = DB::SALARY_STRUCTURES;
$error_message = '';
$success_message = '';

include('admin_elements/admin_header.php');
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| RESTRICT ACCESS
|--------------------------------------------------------------------------
*/
if (!is_SystemAdmin() && !is_SuperAdmin() && is_role() != 'hr') {
    echo 'Permission Denied.';
    exit();
}

$employee_id = intval($_GET['employee_id'] ?? $_POST['employee_id'] ?? 0);
$effective_from = '';
$effective_to = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $effective_from = e_s__($_POST['effective_from'] ?? '');
    $effective_to = e_s__($_POST['effective_to'] ?? '');
    $components = $_POST['components'] ?? [];

    if (empty($employee_id)) {
        $error_message = 'Please select an employee.';
    } else {
        // Start transaction
        $mysqli->begin_transaction();

        try {
            // Handle NULL for empty dates
            $effective_from_sql = !empty($effective_from) ? "'$effective_from'" : "NULL";
            $effective_to_sql = !empty($effective_to) ? "'$effective_to'" : "NULL";

            // Delete all existing salary structures for this employee
            $mysqli->query("DELETE FROM `$tbl_name` WHERE employee_id=$employee_id");

            // Insert new salary components (only non-zero amounts)
            $inserted_count = 0;
            foreach ($components as $component_id => $amount) {
                $component_id = intval($component_id);
                $amount = floatval($amount);

                // Only insert if amount is greater than 0
                if ($amount > 0) {
                    $mysqli->query("
                        INSERT INTO `$tbl_name`
                        (employee_id, component_id, amount, effective_from, effective_to)
                        VALUES
                        ($employee_id, $component_id, $amount, $effective_from_sql, $effective_to_sql)
                    ");
                    $inserted_count++;
                }
            }

            // Commit transaction
            $mysqli->commit();

            $success_message = "Salary structure saved successfully! $inserted_count component(s) assigned.";
            header("Location:listing_employee_salaries.php?success_message=" . urlencode($success_message));
            exit;

        } catch (Exception $e) {
            // Rollback on error
            $mysqli->rollback();
            $error_message = "Failed to save salary structure: " . $e->getMessage();
        }
    }
}

// Get employee details if employee_id is set
$employee_name = '';
$employee_department = '';
if ($employee_id > 0) {
    $emp_result = $mysqli->query("
        SELECT u.full_name, d.department
        FROM `" . tbl_users . "` u
        LEFT JOIN `" . DB::DEPARTMENTS . "` d ON u.department_id = d.id
        WHERE u.id = $employee_id
    ");
    if ($emp_result && $emp_row = $emp_result->fetch_assoc()) {
        $employee_name = s__($emp_row['full_name']);
        $employee_department = s__($emp_row['department']) ?: 'Not assigned';
    }
}

// Get all active payroll components
$components_query = $mysqli->query("
    SELECT id, component_name, component_type
    FROM `" . DB::PAYROLL_COMPONENTS . "`
    WHERE is_active = 1
    ORDER BY component_type DESC, component_name ASC
");

$earnings = [];
$deductions = [];

while ($comp = $components_query->fetch_assoc()) {
    if ($comp['component_type'] == 'earning') {
        $earnings[] = $comp;
    } else {
        $deductions[] = $comp;
    }
}

// Get existing salary structure for the employee (if any)
$existing_amounts = [];
if ($employee_id > 0) {
    $existing_query = $mysqli->query("
        SELECT component_id, amount, effective_from, effective_to
        FROM `$tbl_name`
        WHERE employee_id = $employee_id
        AND (effective_to IS NULL OR effective_to >= CURDATE())
    ");

    while ($existing = $existing_query->fetch_assoc()) {
        $existing_amounts[$existing['component_id']] = $existing['amount'];

        // Use the first record's dates as defaults
        if (empty($effective_from) && !empty($existing['effective_from'])) {
            $effective_from = $existing['effective_from'];
        }
        if (empty($effective_to) && !empty($existing['effective_to'])) {
            $effective_to = $existing['effective_to'];
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
                    <h5 class="mb-0">
                        <i class="ph-currency-circle-dollar me-2"></i>
                        <?php echo $employee_id > 0 ? 'Edit' : 'Setup'; ?> Salary Structure
                    </h5>
                    <a href="listing_employee_salaries.php" class="btn btn-light btn-sm">
                        <i class="ph-arrow-left me-2"></i>Back to Employee Salaries
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($error_message)) { ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            <strong>Error:</strong> <?php echo $error_message; ?>
                        </div>
                    <?php } ?>

                    <?php if (!empty($success_message)) { ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            <strong>Success:</strong> <?php echo $success_message; ?>
                        </div>
                    <?php } ?>

                    <form method="post" id="salaryForm">
                        <!-- Employee Selection -->
                        <div class="row mb-4">
                            <label class="col-lg-3 col-form-label fw-bold">Select Employee <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <select name="employee_id" id="employee_id" class="form-select" required <?php echo $employee_id > 0 ? '' : ''; ?>>
                                    <option value="0">-- Select Employee --</option>
                                    <?php
                                    $res = $mysqli->query("
                                        SELECT u.id, u.full_name, d.department
                                        FROM `" . tbl_users . "` u
                                        LEFT JOIN `" . DB::DEPARTMENTS . "` d ON u.department_id = d.id
                                        WHERE u.id > 1 AND u.is_active = 1
                                        ORDER BY u.full_name ASC
                                    ");
                                    while ($u = $res->fetch_array()) {
                                    ?>
                                        <option value="<?php echo $u['id']; ?>" <?php if ($employee_id == $u['id']) echo 'selected'; ?>>
                                            <?php echo s__($u['full_name']); ?><?php echo !empty($u['department']) ? ' - ' . s__($u['department']) : ''; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <small class="form-text text-muted">Select employee to configure salary components</small>
                            </div>
                        </div>

                        <?php if ($employee_id > 0) { ?>

                            <!-- Employee Info Card -->
                            <div class="alert alert-info mb-4">
                                <div class="d-flex align-items-center">
                                    <i class="ph-user-circle ph-2x me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Employee: <strong><?php echo $employee_name; ?></strong></h6>
                                        <small>Department: <?php echo $employee_department; ?></small>
                                    </div>
                                </div>
                            </div>

                            <!-- Effective Dates -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Effective From</label>
                                    <input type="date" name="effective_from" class="form-control" value="<?php echo $effective_from; ?>">
                                    <small class="form-text text-muted">Leave blank for immediate effect</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Effective To</label>
                                    <input type="date" name="effective_to" class="form-control" value="<?php echo $effective_to; ?>">
                                    <small class="form-text text-muted">Leave blank for ongoing</small>
                                </div>
                            </div>

                            <hr class="mb-4">

                            <!-- Earnings Section -->
                            <div class="mb-4">
                                <h6 class="text-success mb-3">
                                    <i class="ph-plus-circle me-2"></i>Earnings Components
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="50%">Component Name</th>
                                                <th width="50%">Monthly Amount (AED)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (count($earnings) == 0) {
                                                echo '<tr><td colspan="2" class="text-center text-muted">No earning components available. Please add components first.</td></tr>';
                                            } else {
                                                foreach ($earnings as $earning) {
                                                    $current_amount = $existing_amounts[$earning['id']] ?? 0;
                                            ?>
                                                <tr>
                                                    <td class="align-middle">
                                                        <strong><?php echo s__($earning['component_name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                               name="components[<?php echo $earning['id']; ?>]"
                                                               class="form-control earning-input"
                                                               step="0.01"
                                                               min="0"
                                                               value="<?php echo $current_amount > 0 ? number_format($current_amount, 2, '.', '') : ''; ?>"
                                                               placeholder="0.00">
                                                    </td>
                                                </tr>
                                            <?php
                                                }
                                            }
                                            ?>
                                            <tr class="table-success">
                                                <td class="text-end"><strong>Total Earnings:</strong></td>
                                                <td><strong id="total_earnings">AED 0.00</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Deductions Section -->
                            <div class="mb-4">
                                <h6 class="text-danger mb-3">
                                    <i class="ph-minus-circle me-2"></i>Deduction Components
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="50%">Component Name</th>
                                                <th width="50%">Monthly Amount (AED)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (count($deductions) == 0) {
                                                echo '<tr><td colspan="2" class="text-center text-muted">No deduction components available.</td></tr>';
                                            } else {
                                                foreach ($deductions as $deduction) {
                                                    $current_amount = $existing_amounts[$deduction['id']] ?? 0;
                                            ?>
                                                <tr>
                                                    <td class="align-middle">
                                                        <strong><?php echo s__($deduction['component_name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                               name="components[<?php echo $deduction['id']; ?>]"
                                                               class="form-control deduction-input"
                                                               step="0.01"
                                                               min="0"
                                                               value="<?php echo $current_amount > 0 ? number_format($current_amount, 2, '.', '') : ''; ?>"
                                                               placeholder="0.00">
                                                    </td>
                                                </tr>
                                            <?php
                                                }
                                            }
                                            ?>
                                            <tr class="table-danger">
                                                <td class="text-end"><strong>Total Deductions:</strong></td>
                                                <td><strong id="total_deductions">AED 0.00</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Net Salary Summary -->
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h5 class="mb-0">Net Monthly Salary</h5>
                                            <small>Total Earnings - Total Deductions</small>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <h3 class="mb-0" id="net_salary">AED 0.00</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="ph-floppy-disk me-2"></i>Save Salary Structure
                                </button>
                                <a href="listing_employee_salaries.php" class="btn btn-light btn-lg">
                                    <i class="ph-x me-2"></i>Cancel
                                </a>
                            </div>

                        <?php } else { ?>
                            <div class="alert alert-warning">
                                <i class="ph-info me-2"></i>Please select an employee from the dropdown above to configure their salary structure.
                            </div>
                        <?php } ?>
                    </form>
                </div>
            </div>
        </div>
        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Redirect to same page with employee_id parameter when employee is selected
    const employeeSelect = document.getElementById('employee_id');
    if (employeeSelect) {
        employeeSelect.addEventListener('change', function() {
            if (this.value > 0) {
                window.location.href = 'salary_structures.php?employee_id=' + this.value;
            }
        });
    }

    // Calculate totals when amounts change
    function calculateTotals() {
        let totalEarnings = 0;
        let totalDeductions = 0;

        // Calculate earnings
        document.querySelectorAll('.earning-input').forEach(function(input) {
            const value = parseFloat(input.value) || 0;
            totalEarnings += value;
        });

        // Calculate deductions
        document.querySelectorAll('.deduction-input').forEach(function(input) {
            const value = parseFloat(input.value) || 0;
            totalDeductions += value;
        });

        // Calculate net
        const netSalary = totalEarnings - totalDeductions;

        // Update display
        document.getElementById('total_earnings').textContent = 'AED ' + totalEarnings.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        document.getElementById('total_deductions').textContent = 'AED ' + totalDeductions.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        document.getElementById('net_salary').textContent = 'AED ' + netSalary.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    // Attach event listeners to all input fields
    document.querySelectorAll('.earning-input, .deduction-input').forEach(function(input) {
        input.addEventListener('input', calculateTotals);
    });

    // Initial calculation
    calculateTotals();
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>
