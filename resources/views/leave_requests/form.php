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
 * @var bool $medicalReportProvided
 * @var string|null $medicalReportFile
 * @var string $uploadPath
 */
include 'admin_elements/admin_header.php';
?>
<div class="content-wrapper">
    <?php include 'admin_elements/hr_navbar.php'; ?>
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
                <div class="card col-lg-8">
                    <div class="content clearfix">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Employee:*</span></label>
                            <div class="col-lg-9">
                                <select required name="employee_id" class="form-select">
                                    <option value="0">Please select</option>
                                    <?php foreach ($users as $user) { ?>
                                        <option value="<?php echo $user->id; ?>" <?php echo $user->id === $employeeId ? 'selected' : ''; ?>><?php echo htmlspecialchars($user->fullName); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Leave Type:*</span></label>
                            <div class="col-lg-9">
                                <select required name="leave_type_id" class="form-select">
                                    <option value="0">Please select</option>
                                    <?php foreach ($leaveTypes as $lt) { ?>
                                        <option value="<?php echo $lt->id; ?>" <?php echo $lt->id === $leaveTypeId ? 'selected' : ''; ?>><?php echo htmlspecialchars($lt->leaveType); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Start Date:*</span></label>
                            <div class="col-lg-9">
                                <input required type="date" name="start_date" value="<?php echo $startDate; ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">End Date:*</span></label>
                            <div class="col-lg-9">
                                <input required type="date" name="end_date" value="<?php echo $endDate; ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Total Days:*</span></label>
                            <div class="col-lg-9">
                                <input required type="number" step="0.5" name="total_days" id="total_days" value="<?php echo $totalDays; ?>" class="form-control" min="0.5" readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Reason:</label>
                            <div class="col-lg-9">
                                <textarea name="reason" class="form-control" rows="3"><?php echo $reason; ?></textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Medical Report:</label>
                            <div class="col-lg-9">
                                <div class="form-check form-switch mb-2">
                                    <input type="checkbox" class="form-check-input form-check-input-info" name="medical_report_provided" id="medical_report_provided" value="1" <?php echo $medicalReportProvided ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="medical_report_provided">Medical Report Provided</label>
                                </div>
                                <div id="medical_report_upload_section" class="<?php echo $medicalReportProvided ? '' : 'd-none'; ?>">
                                    <input type="file" name="medical_report_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                    <?php if (!empty($medicalReportFile) && file_exists(dirname(__DIR__, 3) . '/uploads/leave_requests/' . $medicalReportFile)): ?>
                                        <div class="mt-2">
                                            <a href="<?php echo $uploadPath . htmlspecialchars($medicalReportFile); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="ph-file"></i> View Uploaded Report
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Status:</label>
                            <div class="col-lg-9">
                                <select name="status" class="form-select">
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
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
<script>
(function() {
    var startEl = document.querySelector('input[name="start_date"]');
    var endEl = document.querySelector('input[name="end_date"]');
    var totalEl = document.getElementById('total_days');

    function calcTotalDays() {
        var start = startEl.value;
        var end = endEl.value;
        if (start && end) {
            var startDt = new Date(start);
            var endDt = new Date(end);
            if (startDt <= endDt) {
                var diff = (endDt - startDt) / (1000 * 60 * 60 * 24) + 1;
                totalEl.value = diff;
            }
        }
    }

    startEl.addEventListener('change', calcTotalDays);
    endEl.addEventListener('change', calcTotalDays);

    var medCheckbox = document.getElementById('medical_report_provided');
    var medUploadSection = document.getElementById('medical_report_upload_section');
    if (medCheckbox && medUploadSection) {
        medCheckbox.addEventListener('change', function() {
            medUploadSection.classList.toggle('d-none', !this.checked);
        });
    }
})();
</script>
<?php
include 'admin_elements/admin_footer.php';
