<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var string $accountName
 * @var string $accountCode
 * @var string $currency
 * @var string $bankName
 * @var string $routingNumber
 * @var string $description
 * @var int $isPrimary
 * @var int $publish
 * @var string $moduleCaption
 * @var string $module
 * @var array $allCurrencies
 * @var bool $canCreate
 * @var bool $canEdit
 */
include 'admin_elements/admin_header.php';
?>
<div class="content-wrapper">
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> Bank Account</h5>
            </div>
            <div class="my-1 d-flex align-items-center gap-3">
                <div class="form-check form-switch mb-0">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="is_primary" id="is_primary" form="frmbanks" <?php echo $isPrimary ? 'checked="checked"' : ''; ?>>
                    <label class="form-check-label fw-semibold" for="is_primary">Is Primary?</label>
                </div>
                <div class="form-check form-switch mb-0 me-2">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="publish" id="publish" form="frmbanks" <?php echo $publish ? 'checked="checked"' : ''; ?>>
                    <label class="form-check-label fw-semibold" for="publish">Publish</label>
                </div>
                <?php if ($canCreate) { ?>
                    <button type="submit" form="frmbanks" class="btn btn-primary btn-sm">Save</button>
                <?php } ?>
                <a href="listing_banks.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" id="frmbanks" name="frmbanks" action="banks.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_banks">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_banks">
                <?php } ?>
                <div class="card col-lg-6">
                    <div class="card-body">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Account Name:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="account_name" value="<?php echo e($accountName); ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Account Code:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="account_code" value="<?php echo e($accountCode); ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Currency:*</span></label>
                            <div class="col-lg-9">
                                <select class="form-select" name="currency">
                                    <option value="0">Please select</option>
                                    <?php foreach ($allCurrencies as $cur) { ?>
                                        <option value="<?php echo $cur['id']; ?>" <?php echo ((string)$cur['id'] === (string)$currency) ? 'selected' : ''; ?>>
                                            <?php echo e($cur['currency']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Bank Name:</label>
                            <div class="col-lg-9">
                                <input type="text" name="bank_name" value="<?php echo e($bankName); ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Rounting number (SWIFT):</label>
                            <div class="col-lg-9">
                                <input type="text" name="routing_number" value="<?php echo e($routingNumber); ?>" class="form-control">
                                <div class="form-text text-muted">e.g 026009593</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Description:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="description" style="field-sizing: content;"><?php echo e($description); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php include 'admin_elements/copyright.php'; ?>
    </div>
</div>
<?php if (!$canCreate && !$canEdit) { ?>
    <script>
        $(function() {
            toggleFormElements('true');
        });
    </script>
<?php } ?>
<?php
include 'admin_elements/admin_footer.php';
