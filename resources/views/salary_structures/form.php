<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var string $effectiveFrom
 * @var string $description
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
            </div>
            <div class="my-1">
                <?php if ($id > 0 ? $canEdit : $canCreate) { ?>
                    <button type="submit" form="frm<?php echo $module; ?>" class="btn btn-primary btn-sm me-2">Save</button>
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
                <div class="card col-lg-6">
                    <div class="card-body clearfix">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Salary Structure:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="effective_from" value="<?php echo htmlspecialchars($effectiveFrom); ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Description:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="description" style="field-sizing: content;"><?php echo htmlspecialchars($description); ?></textarea>
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
