<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var string $paymentMethodName
 * @var int $isActive
 * @var string $moduleCaption
 * @var string $module
 * @var bool $canCreate
 */
include 'admin_elements/admin_header.php';
?>
<div class="content-wrapper">
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> Payment Method</h5>
            </div>
            <div class="my-1 d-inline-flex align-items-center me-2">
                <div class="form-check form-check-inline form-switch mb-0">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" <?php echo $isActive ? 'checked="checked"' : ''; ?> form="frmpayment_methods">
                    <label class="form-check-label">Publish</label>
                </div>
            </div>
            <div class="my-1">
                <?php if ($canCreate) { ?>
                    <button type="submit" form="frmpayment_methods" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_payment_methods.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" action="payment_methods.php" id="frmpayment_methods">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_payment_methods">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_payment_methods">
                <?php } ?>
                <div class="card col-lg-6">
                    <div class="card-body">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Payment method:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="payment_method" value="<?php echo $paymentMethodName; ?>" class="form-control">
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
