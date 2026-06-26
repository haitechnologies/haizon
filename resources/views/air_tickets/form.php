<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var int $employeeId
 * @var float $entitlementAmount
 * @var string $status
 * @var string $eligibilityDate
 * @var string $paidDate
 * @var string $paymentReference
 * @var string $notes
 * @var string $moduleCaption
 * @var string $module
 * @var bool $canCreate
 * @var bool $canEdit
 * @var array $employees
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
                    <button type="submit" form="frmair_tickets" class="btn btn-primary btn-sm me-2">Save</button>
                    <a href="listing_air_tickets.php" class="btn btn-light btn-sm">Cancel</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" id="frmair_tickets" action="air_tickets.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_air_ticket">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_air_ticket">
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
                            <label class="col-lg-3 col-form-label">Entitlement Amount:</label>
                            <div class="col-lg-9">
                                <input readonly type="number" name="entitlement_amount" value="<?php echo number_format($entitlementAmount, 2); ?>" class="form-control" step="0.01" min="0">
                                <div class="form-text text-muted">Default entitlement amount is 1250.00</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Status:</label>
                            <div class="col-lg-9">
                                <select name="status" class="form-control">
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="payable" <?php echo $status === 'payable' ? 'selected' : ''; ?>>Payable</option>
                                    <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Eligibility Date:</label>
                            <div class="col-lg-9">
                                <input type="date" name="eligibility_date" value="<?php echo htmlspecialchars($eligibilityDate); ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Paid Date:</label>
                            <div class="col-lg-9">
                                <input type="date" name="paid_date" value="<?php echo htmlspecialchars($paidDate); ?>" class="form-control">
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
<?php
include 'admin_elements/admin_footer.php';
