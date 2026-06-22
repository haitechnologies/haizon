<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var string $itemType
 * @var string $itemName
 * @var string $unitPrice
 * @var bool $isExcise
 * @var int $publish
 * @var string $moduleCaption
 * @var string $module
 * @var bool $canCreate
 * @var bool $canEdit
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
            <div class="my-1 d-inline-flex align-items-center me-2">
                <div class="form-check form-check-inline form-switch mb-0">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php echo $publish ? 'checked="checked"' : ''; ?> form="frm<?php echo $module; ?>">
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            <div class="my-1">
                <?php if ($id > 0 ? $canEdit : $canCreate) { ?>
                    <button type="submit" form="frm<?php echo $module; ?>" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
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
                <div class="card col-lg-6">
                    <div class="card-body clearfix">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Item Type:</label>
                            <div class="col-lg-9">
                                <select name="item_type" class="form-select">
                                    <option value="services" <?php echo $itemType === 'services' ? 'selected' : ''; ?>>Service</option>
                                    <option value="goods" <?php echo $itemType === 'goods' ? 'selected' : ''; ?>>Goods</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Item Name:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="item_name" value="<?php echo htmlspecialchars($itemName); ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Unit Price:</label>
                            <div class="col-lg-9">
                                <input type="text" name="unit_price" value="<?php echo htmlspecialchars($unitPrice); ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Excise Item:</label>
                            <div class="col-lg-9">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="is_excise" value="1" <?php echo $isExcise ? 'checked' : ''; ?>>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php include 'admin_elements/copyright.php'; ?>
    </div>
</div>
<?php include 'admin_elements/admin_footer.php';
