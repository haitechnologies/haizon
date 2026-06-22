<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var string $leaveType
 * @var int $maxPerYear
 * @var int $paid
 * @var int $publish
 * @var string $moduleCaption
 * @var string $module
 * @var bool $canCreate
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
                <?php if ($canCreate) { ?>
                    <button type="submit" form="frmleave_types" class="btn btn-primary btn-sm me-2">Save</button>
                    <a href="listing_leave_types.php" class="btn btn-light btn-sm">Cancel</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" id="frmleave_types" action="leave_types.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_leave_types">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_leave_types">
                <?php } ?>
                <div class="card col-lg-6">
                    <div class="content clearfix">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Leave Type:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="leave_type" value="<?php echo $leaveType; ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Max Per Year:*</span></label>
                            <div class="col-lg-9">
                                <input required type="number" name="max_per_year" value="<?php echo $maxPerYear; ?>" class="form-control" min="0">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Paid Leave:</label>
                            <div class="col-lg-9">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="paid" id="paid" value="1" <?php echo $paid ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="paid">Paid</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Paid Days:*</span></label>
                            <div class="col-lg-9">
                                <input required type="number" name="paid_days" value="<?php echo $paidDays; ?>" class="form-control" min="0">
                                <div class="form-text text-muted">First N days are paid; days beyond this limit are unpaid. Default: 3</div>
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
