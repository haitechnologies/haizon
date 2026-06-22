<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var string $deviceName
 * @var string $ipAddress
 * @var int $port
 * @var string $serialNumber
 * @var string $devicePassword
 * @var string $deviceModel
 * @var string $location
 * @var int $isActive
 * @var array $employees
 * @var array $devices
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
                <div class="card col-lg-6">
                    <div class="card-body clearfix">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Device Name:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="device_name" value="<?php echo htmlspecialchars($deviceName); ?>" class="form-control" placeholder="e.g. Main Entrance">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">IP Address:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="ip_address" value="<?php echo htmlspecialchars($ipAddress); ?>" class="form-control" placeholder="192.168.1.100">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Port:</label>
                            <div class="col-lg-9">
                                <input type="number" name="port" value="<?php echo (int)$port; ?>" class="form-control" placeholder="4370">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Serial Number:</label>
                            <div class="col-lg-9">
                                <input type="text" name="serial_number" value="<?php echo htmlspecialchars($serialNumber); ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Device Password:</label>
                            <div class="col-lg-9">
                                <input type="text" name="device_password" value="<?php echo htmlspecialchars($devicePassword); ?>" class="form-control">
                                <small class="form-text text-muted">Default is 0</small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Device Model:</label>
                            <div class="col-lg-9">
                                <input type="text" name="device_model" value="<?php echo htmlspecialchars($deviceModel); ?>" class="form-control" placeholder="BioPro SA30">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Location:</label>
                            <div class="col-lg-9">
                                <input type="text" name="location" value="<?php echo htmlspecialchars($location); ?>" class="form-control" placeholder="e.g. Main Gate">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Is Active:</label>
                            <div class="col-lg-9">
                                <div class="form-check">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" <?php echo $isActive ? 'checked' : ''; ?> id="is_active">
                                    <label class="form-check-label" for="is_active">Active</label>
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
