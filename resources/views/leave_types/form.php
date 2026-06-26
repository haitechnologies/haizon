<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var string $leaveType
 * @var int $paid
 * @var int $publish
 * @var string $moduleCaption
 * @var string $module
 * @var bool $canCreate
 */
include 'admin_elements/admin_header.php';

$leaveTypesMap = [
    'Annual Leave' => ['paid' => 1, 'desc' => 'Eligible after 12 months from date of joining. 1 month paid leave with air ticket.'],
    'Sick Leave'   => ['paid' => 1, 'desc' => 'Paid sick leave as per medical certificate.'],
    'Urgent Leave' => ['paid' => 1, 'desc' => '3 days paid leave, once per year from date of joining. Resets annually.'],
];
?>
<div class="content-wrapper">
    <?php  ?>
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1 d-flex align-items-center gap-2">
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> <?php echo $moduleCaption; ?></h5>
            </div>
            <div class="my-1">
                <?php if ($canCreate) { ?>
                    <button type="submit" form="frmleave_types" class="btn btn-primary btn-sm me-2">Save</button>
                    <a href="listing_leave_types.php" class="btn btn-light btn-sm">Cancel</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" id="frmleave_types" action="leave_types.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_leave_types">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_leave_types">
                <?php } ?>
                <div class="card col-lg-8">
                    <div class="card-body">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Leave Type:*</span></label>
                            <div class="col-lg-9">
                                <?php if ($id > 0): ?>
                                    <input type="hidden" name="leave_type" value="<?php echo htmlspecialchars($leaveType); ?>">
                                    <span class="form-control-plaintext fw-bold"><?php echo htmlspecialchars($leaveType); ?></span>
                                <?php else: ?>
                                    <select required name="leave_type" id="leave_type" class="form-select">
                                        <option value="">Select Leave Type</option>
                                        <?php foreach (array_keys($leaveTypesMap) as $key): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $leaveType === $key ? 'selected' : ''; ?> data-paid="<?php echo $leaveTypesMap[$key]['paid']; ?>"><?php echo $key; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Paid Leave:</label>
                            <div class="col-lg-9 pt-2">
                                <input type="hidden" name="paid" id="paid" value="<?php echo $paid ? 1 : 0; ?>">
                                <span class="badge bg-success bg-opacity-20 text-success fs-6" id="paid-badge"><?php echo $paid ? 'Yes — Fully Paid' : 'No'; ?></span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Rule:</label>
                            <div class="col-lg-9">
                                <div class="alert alert-info mb-0 py-2 px-3" id="rule-desc">
                                    <?php
                                    $desc = $leaveTypesMap[$leaveType]['desc'] ?? 'Select a leave type to see its rules.';
                                    echo htmlspecialchars($desc);
                                    ?>
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
<script>
document.getElementById('leave_type')?.addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    if (opt.value) {
        document.getElementById('paid').value = opt.getAttribute('data-paid');
        document.getElementById('paid-badge').textContent = opt.getAttribute('data-paid') === '1' ? 'Yes — Fully Paid' : 'No';
        var descs = <?php echo json_encode(array_combine(array_keys($leaveTypesMap), array_column($leaveTypesMap, 'desc'))); ?>;
        document.getElementById('rule-desc').textContent = descs[opt.value] || '';
    }
});
</script>
<?php
include 'admin_elements/admin_footer.php';
