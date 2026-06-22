<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var string $statusName
 * @var string $statusType
 * @var int $publish
 * @var string $moduleCaption
 * @var string $module
 * @var bool $canCreate
 */
include 'admin_elements/admin_header.php';
?>
<div class="content-wrapper">
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1 d-flex align-items-center gap-2">
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> <?php echo $moduleCaption; ?></h5>
                <span class="text-muted small">(<?php echo $publish ? 'Active' : 'InActive'; ?>)</span>
            </div>
            <div class="my-1">
                <?php if ($canCreate) { ?>
                    <button type="submit" form="frmsetup_statuses" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_setup_statuses.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" id="frmsetup_statuses" action="setup_statuses.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_setup_statuses">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_setup_statuses">
                <?php } ?>
                <div class="card col-lg-6">
                    <div class="card-body clearfix">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Status Type:*</span></label>
                            <div class="col-lg-9">
                                <select required name="status_type" class="form-control">
                                    <option value="">Select Type</option>
                                    <option value="leads" <?php echo $statusType === 'lead_status' || $statusType === 'leads' ? 'selected' : ''; ?>>Leads</option>
                                    <option value="vendors" <?php echo $statusType === 'vendor_status' || $statusType === 'vendors' ? 'selected' : ''; ?>>Vendors</option>
                                    <option value="customers" <?php echo $statusType === 'customer_status' || $statusType === 'customers' || ($statusType === '' && $id === 0) ? 'selected' : ''; ?>>Customers</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Status Name:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="status_name" value="<?php echo $statusName; ?>" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php include 'admin_elements/copyright.php'; ?>
    </div>
</div>
<?php include 'admin_elements/admin_footer.php'; ?>
