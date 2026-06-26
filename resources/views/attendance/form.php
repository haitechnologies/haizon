<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var int $employeeId
 * @var array $employees
 * @var string $workDate
 * @var string $checkIn
 * @var string $checkOut
 * @var float $totalHours
 * @var string $status
 * @var string $moduleCaption
 * @var string $module
 * @var bool $canCreate
 * @var bool $canEdit
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
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Employee:*</span></label>
                            <div class="col-lg-9">
                                <select required name="employee_id" class="form-select">
                                    <option value="">-- Select Employee --</option>
                                    <?php foreach ($employees as $emp) { ?>
                                        <option value="<?php echo $emp['id']; ?>" <?php echo $employeeId === (int)$emp['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($emp['full_name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Work Date:*</span></label>
                            <div class="col-lg-9">
                                <input required type="date" name="work_date" value="<?php echo htmlspecialchars($workDate); ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Check In:</label>
                            <div class="col-lg-9">
                                <input type="time" name="check_in" value="<?php echo htmlspecialchars($checkIn); ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Check Out:</label>
                            <div class="col-lg-9">
                                <input type="time" name="check_out" value="<?php echo htmlspecialchars($checkOut); ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Total Hours:</label>
                            <div class="col-lg-9">
                                <input type="number" step="0.01" name="total_hours" value="<?php echo $totalHours; ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Status:</label>
                            <div class="col-lg-9">
                                <select name="status" class="form-select">
                                    <option value="present" <?php echo $status === 'present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="absent" <?php echo $status === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="late" <?php echo $status === 'late' ? 'selected' : ''; ?>>Late</option>
                                    <option value="half_day" <?php echo $status === 'half_day' ? 'selected' : ''; ?>>Half Day</option>
                                    <option value="on_leave" <?php echo $status === 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                                </select>
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
