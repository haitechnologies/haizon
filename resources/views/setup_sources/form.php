<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var string $sourceName
 * @var string $sourceType
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
                    <button type="submit" form="frmsetup_sources" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_setup_sources.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" id="frmsetup_sources" action="setup_sources.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_setup_sources">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_setup_sources">
                <?php } ?>
                <div class="card col-lg-6">
                    <div class="card-body clearfix">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Source Type:*</span></label>
                            <div class="col-lg-9">
                                <select required name="source_type" class="form-control">
                                    <option value="">Select Type</option>
                                    <option value="leads" <?php echo $sourceType === 'lead_source' || $sourceType === 'leads' ? 'selected' : ''; ?>>Leads</option>
                                    <option value="customers" <?php echo $sourceType === 'customer_source' || $sourceType === 'customers' || ($sourceType === '' && $id === 0) ? 'selected' : ''; ?>>Customers</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Source Name:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="source_name" value="<?php echo $sourceName; ?>" class="form-control">
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
