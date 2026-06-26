<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var int $employeeId
 * @var int $entitlementYear
 * @var float $totalLeaveDays
 * @var float $leaveAvailed
 * @var float $leaveBalance
 * @var float $airTicketAmount
 * @var string $airTicketAvailed
 * @var string $status
 * @var string $notes
 * @var string $moduleCaption
 * @var string $module
 * @var bool $canCreate
 * @var bool $canEdit
 * @var array $employees
 * @var array $departments
 * @var array $designations
 */
include 'admin_elements/admin_header.php';
?>
<div class="content-wrapper">
    <?php  ?>
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1 d-flex align-items-center gap-2">
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> <?php echo $moduleCaption; ?></h5>
            </div>
            <div class="my-1">
                <?php if ($canCreate || $canEdit) { ?>
                    <button type="submit" form="frmannual_leave_entitlements" class="btn btn-primary btn-sm me-2">Save</button>
                    <a href="listing_annual_leave_entitlements.php" class="btn btn-light btn-sm">Cancel</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" id="frmannual_leave_entitlements" action="annual_leave_entitlements.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_annual_leave_entitlements">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_annual_leave_entitlements">
                <?php } ?>
                <div class="card col-lg-8">
                    <div class="content clearfix">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Employee:*</span></label>
                            <div class="col-lg-9">
                                <select required name="employee_id" class="form-control" <?php echo $id > 0 ? 'disabled' : ''; ?>>
                                    <option value="">-- Select Employee --</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?php echo (int)$emp['id']; ?>" <?php echo (int)$emp['id'] === $employeeId ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars((string)$emp['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($id > 0): ?>
                                    <input type="hidden" name="employee_id" value="<?php echo $employeeId; ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Entitlement Year:*</span></label>
                            <div class="col-lg-9">
                                <input readonly type="number" name="entitlement_year" value="<?php echo $entitlementYear; ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Total Leave Days:</label>
                            <div class="col-lg-9">
                                <input readonly type="number" name="total_leave_days" value="<?php echo $totalLeaveDays; ?>" class="form-control" step="0.5" min="0">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Leave Availed:</label>
                            <div class="col-lg-9">
                                <input type="number" name="leave_availed" id="leave_availed" value="<?php echo $leaveAvailed; ?>" class="form-control" step="0.5" min="0" oninput="calculateLeaveBalance()">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Leave Balance:</label>
                            <div class="col-lg-9">
                                <input readonly type="number" name="leave_balance" id="leave_balance" value="<?php echo $leaveBalance; ?>" class="form-control" step="0.5" min="0">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Air Ticket Amount:</label>
                            <div class="col-lg-9">
                                <input readonly type="number" name="air_ticket_amount" value="<?php echo number_format($airTicketAmount, 2); ?>" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Air Ticket Availed:</label>
                            <div class="col-lg-9">
                                <select name="air_ticket_availed" class="form-control">
                                    <option value="">No</option>
                                    <option value="yes" <?php echo $airTicketAvailed === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Status:</label>
                            <div class="col-lg-9">
                                <select name="status" class="form-control">
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="availed" <?php echo $status === 'availed' ? 'selected' : ''; ?>>Availed</option>
                                    <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Notes:</label>
                            <div class="col-lg-9">
                                <textarea name="notes" class="form-control" rows="4"><?php echo htmlspecialchars($notes); ?></textarea>
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
function calculateLeaveBalance() {
    var totalDays = parseFloat(document.querySelector('input[name="total_leave_days"]').value) || 0;
    var availed = parseFloat(document.getElementById('leave_availed').value) || 0;
    var balance = totalDays - availed;
    if (balance < 0) balance = 0;
    document.getElementById('leave_balance').value = balance;
}
</script>
<?php
include 'admin_elements/admin_footer.php';
