<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var int $employeeId
 * @var int $leaveTypeId
 * @var string $startDate
 * @var string $endDate
 * @var float $totalDays
 * @var string $reason
 * @var string $status
 * @var string $moduleCaption
 * @var string $module
 * @var bool $canCreate
 * @var array $users
 * @var array $leaveTypes
 * @var string|null $medicalReportFile
 * @var string $uploadPath
 */
include 'admin_elements/admin_header.php';
?>
<div class="content-wrapper">
    <?php  ?>
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> <?php echo $moduleCaption; ?></h5>
            </div>
            <div class="my-1">
                <?php if ($canCreate) { ?>
                    <button type="submit" form="frmleave_requests" class="btn btn-primary btn-sm me-2">Save</button>
                    <a href="listing_leave_requests.php" class="btn btn-light btn-sm">Cancel</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" id="frmleave_requests" action="leave_requests.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_leave_requests">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_leave_requests">
                <?php } ?>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="row mb-3">
                                    <label class="col-lg-4 col-form-label"><span class="text-danger">Employee:*</span></label>
                                    <div class="col-lg-8">
                                        <select required name="employee_id" class="form-select">
                                            <option value="0">Please select</option>
                                            <?php foreach ($users as $user) { ?>
                                                <option value="<?php echo $user->id; ?>" <?php echo $user->id === $employeeId ? 'selected' : ''; ?>><?php echo htmlspecialchars($user->fullName); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-4 col-form-label"><span class="text-danger">Leave Type:*</span></label>
                                    <div class="col-lg-8">
                                        <select required name="leave_type_id" id="leave_type_id" class="form-select">
                                            <option value="0">Please select</option>
                                            <?php foreach ($leaveTypes as $lt) { ?>
                                                <option value="<?php echo $lt->id; ?>" <?php echo $lt->id === $leaveTypeId ? 'selected' : ''; ?>><?php echo htmlspecialchars($lt->leaveType); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-4 col-form-label"><span class="text-danger">Start Date:*</span></label>
                                    <div class="col-lg-8">
                                        <input required type="text" name="start_date" id="start_date" value="<?php echo $startDate; ?>" class="form-control datepicker-basic" readonly>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-4 col-form-label"><span class="text-danger">End Date:*</span></label>
                                    <div class="col-lg-8">
                                        <input required type="text" name="end_date" id="end_date" value="<?php echo $endDate; ?>" class="form-control datepicker-basic" readonly>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-4 col-form-label"><span class="text-danger">Total Days:*</span></label>
                                    <div class="col-lg-8">
                                        <input required type="number" step="0.5" name="total_days" id="total_days" value="<?php echo $totalDays; ?>" class="form-control" min="0.5" readonly>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-4 col-form-label">Reason:</label>
                                    <div class="col-lg-8">
                                        <textarea name="reason" class="form-control" rows="3"><?php echo $reason; ?></textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-4 col-form-label">Status:</label>
                                    <div class="col-lg-8">
                                        <select name="status" class="form-select">
                                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="ph-file-plus me-2"></i>Medical Certificate <span class="text-danger" id="med-required-star">*</span><span class="text-muted small fw-normal ms-1" id="med-hint">(required for Sick Leave)</span></h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Upload Medical Certificate <span class="text-danger" id="med-label-star">*</span></label>
                                    <input type="file" name="medical_report_file" id="medical_report_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                    <div class="form-text">Accepted formats: PDF, DOC, DOCX, JPG, PNG</div>
                                </div>
                                <?php if (!empty($medicalReportFile) && file_exists(dirname(__DIR__, 3) . '/uploads/leave_requests/' . $medicalReportFile)): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Existing File:</label>
                                        <div class="d-flex gap-2">
                                            <a href="<?php echo $uploadPath . htmlspecialchars($medicalReportFile); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="ph-file"></i> View Uploaded Report
                                            </a>
                                            <form method="post" action="leave_requests.php" style="display:inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <input type="hidden" name="action" value="delete_medical_report">
                                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this medical certificate?')">
                                                    <i class="ph-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php include 'admin_elements/copyright.php'; ?>
    </div>
</div>
<script>
(function() {
    var startEl = document.getElementById('start_date');
    var endEl = document.getElementById('end_date');
    var totalEl = document.getElementById('total_days');

    function parseDDMMYY(val) {
        if (!val) return null;
        var parts = val.split('-');
        if (parts.length !== 3) return null;
        return new Date(parts[2], parts[1] - 1, parts[0]);
    }

    function calcTotalDays() {
        var startDt = parseDDMMYY(startEl.value);
        var endDt = parseDDMMYY(endEl.value);
        if (startDt && endDt && startDt <= endDt) {
            var diff = (endDt - startDt) / (1000 * 60 * 60 * 24) + 1;
            totalEl.value = diff;
        }
    }

    $(startEl).datepicker({
        dateFormat: 'dd-mm-yy',
        changeMonth: true,
        changeYear: true,
        onSelect: calcTotalDays
    });

    $(endEl).datepicker({
        dateFormat: 'dd-mm-yy',
        changeMonth: true,
        changeYear: true,
        onSelect: calcTotalDays
    });

    var sickLeaveId = <?php echo $sickLeaveTypeId; ?>;
    var leaveTypeEl = document.getElementById('leave_type_id');
    var fileEl = document.getElementById('medical_report_file');
    var medStar = document.getElementById('med-required-star');
    var medLabelStar = document.getElementById('med-label-star');
    var medHint = document.getElementById('med-hint');

    function toggleMedRequired() {
        var isSick = leaveTypeEl.value == sickLeaveId;
        if (isSick) {
            fileEl.setAttribute('required', '');
            if (medStar) medStar.style.display = '';
            if (medLabelStar) medLabelStar.style.display = '';
            if (medHint) medHint.textContent = '(required for Sick Leave)';
        } else {
            fileEl.removeAttribute('required');
            if (medStar) medStar.style.display = 'none';
            if (medLabelStar) medLabelStar.style.display = 'none';
            if (medHint) medHint.textContent = '(optional)';
        }
    }

    if (leaveTypeEl) {
        leaveTypeEl.addEventListener('change', toggleMedRequired);
        toggleMedRequired();
    }
})();
</script>
<?php
include 'admin_elements/admin_footer.php';
