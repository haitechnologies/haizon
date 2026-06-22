<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var int $employeeId
 * @var array $employees
 * @var float $totalTenureYears
 * @var int $totalTenureDays
 * @var float $lastBasicSalary
 * @var float $gratuityAmount
 * @var string $status
 * @var string $settlementDate
 * @var string $paymentDate
 * @var string $paymentReference
 * @var string $notes
 * @var string $errorMessage
 * @var string $moduleCaption
 * @var string $module
 * @var bool $canCreate
 * @var bool $canEdit
 */
include 'admin_elements/admin_header.php';
?>
<div class="content-wrapper">
    <?php include 'admin_elements/hr_navbar.php'; ?>
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1 d-flex align-items-center gap-2">
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> <?php echo $moduleCaption; ?></h5>
            </div>
            <div class="my-1">
                <?php if ($id > 0 ? $canEdit : $canCreate) { ?>
                    <button type="submit" form="frm<?php echo $module; ?>" class="btn btn-primary btn-sm me-2">Save</button>
                    <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_<?php echo $module; ?>">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_<?php echo $module; ?>">
                <?php } ?>
                <div class="card col-lg-8">
                    <div class="card-body clearfix">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Employee:*</span></label>
                            <div class="col-lg-9">
                                <select required name="employee_id" id="employee_id" class="form-select" <?php echo $id > 0 ? 'disabled' : ''; ?>>
                                    <option value="">-- Select Employee --</option>
                                    <?php foreach ($employees as $emp) { ?>
                                        <option value="<?php echo $emp['id']; ?>" <?php echo $employeeId === (int)$emp['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($emp['full_name']); ?></option>
                                    <?php } ?>
                                </select>
                                <?php if ($id > 0) { ?>
                                    <input type="hidden" name="employee_id" value="<?php echo $employeeId; ?>">
                                <?php } ?>
                                <div id="calculate_status" class="form-text text-muted mt-1"></div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Tenure (Years):</label>
                            <div class="col-lg-9">
                                <input type="text" id="total_tenure_years" name="total_tenure_years" value="<?php echo $totalTenureYears; ?>" class="form-control" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Total Days:</label>
                            <div class="col-lg-9">
                                <input type="text" id="total_tenure_days" name="total_tenure_days" value="<?php echo $totalTenureDays; ?>" class="form-control" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Last Basic Salary:*</span></label>
                            <div class="col-lg-9">
                                <input type="number" step="0.01" name="last_basic_salary" id="last_basic_salary" value="<?php echo $lastBasicSalary; ?>" class="form-control" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Gratuity Amount:*</span></label>
                            <div class="col-lg-9">
                                <div class="input-group">
                                    <input type="text" id="gratuity_amount" name="gratuity_amount" value="<?php echo number_format($gratuityAmount, 2); ?>" class="form-control" readonly>
                                    <button type="button" id="btn_calculate" class="btn btn-info" <?php echo $id > 0 ? 'disabled' : ''; ?>>
                                        <i class="ph-calculator me-1"></i>Calculate
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Status:</label>
                            <div class="col-lg-9">
                                <select name="status" class="form-select">
                                    <option value="calculated" <?php echo $status === 'calculated' ? 'selected' : ''; ?>>Calculated</option>
                                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Settlement Date:</label>
                            <div class="col-lg-9">
                                <input type="date" name="settlement_date" value="<?php echo htmlspecialchars($settlementDate); ?>" class="form-control">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Payment Date:</label>
                            <div class="col-lg-9">
                                <input type="date" name="payment_date" value="<?php echo htmlspecialchars($paymentDate); ?>" class="form-control">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Payment Reference:</label>
                            <div class="col-lg-9">
                                <input type="text" name="payment_reference" value="<?php echo htmlspecialchars($paymentReference); ?>" class="form-control" maxlength="255">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Notes:</label>
                            <div class="col-lg-9">
                                <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php include 'admin_elements/copyright.php'; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#btn_calculate').on('click', function() {
        var employeeId = $('#employee_id').val();
        if (!employeeId) {
            $('#calculate_status').html('<span class="text-danger">Please select an employee first.</span>');
            return;
        }

        $('#calculate_status').html('<span class="text-info"><i class="ph-spinner ph-spin me-1"></i>Calculating...</span>');
        $('#btn_calculate').prop('disabled', true);

        $.ajax({
            url: 'gratuity_settlements.php',
            method: 'POST',
            data: {
                action: 'calculate_gratuity',
                employee_id: employeeId,
                csrf_token: $('input[name="csrf_token"]').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#total_tenure_years').val(response.data.total_tenure_years);
                    $('#total_tenure_days').val(response.data.total_tenure_days);
                    $('#last_basic_salary').val(response.data.last_basic_salary);
                    $('#gratuity_amount').val(parseFloat(response.data.gratuity_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#calculate_status').html('<span class="text-success"><i class="ph-check-circle me-1"></i>Calculation completed.</span>');
                } else {
                    $('#calculate_status').html('<span class="text-danger">' + response.error + '</span>');
                }
            },
            error: function() {
                $('#calculate_status').html('<span class="text-danger">An error occurred while calculating. Please try again.</span>');
            },
            complete: function() {
                $('#btn_calculate').prop('disabled', false);
            }
        });
    });
});
</script>

<?php
include 'admin_elements/admin_footer.php';
