<?php

declare(strict_types=1);
/**
 * @var int $employeeId
 * @var array $existing
 * @var string $dateOfJoining
 * @var array $earningComponents
 * @var array $deductionComponents
 * @var string $moduleCaption
 * @var string $module
 * @var bool $canCreate
 * @var bool $canEdit
 * @var array $employees
 */
include 'admin_elements/admin_header.php';

function renderComponentTable(array $components, array $existing, string $dateOfJoining, int $employeeId): string
{
    $html = '<div class="table-responsive"><table class="table table-striped mb-0">';
    $html .= '<thead class="table-light"><tr>';
    $html .= '<th width="40">#</th><th>Component</th>';
    $html .= '<th width="180">Amount (AED)</th>';
    $html .= '<th width="160">Effective From</th>';
    $html .= '<th width="160">Effective To</th>';
    $html .= '</tr></thead><tbody>';

    $idx = 0;
    foreach ($components as $comp) {
        $compId = (int)$comp['id'];
        $existingItem = $existing[$compId] ?? null;
        $amount = $existingItem !== null ? (string)$existingItem->amount : '0';
        $rawFrom = $existingItem?->effectiveFrom ?? '';
        $rawTo = $existingItem?->effectiveTo ?? '';
        $effFrom = $rawFrom !== '' ? date('d-m-Y', strtotime($rawFrom)) : ($employeeId > 0 ? $dateOfJoining : '');
        $effTo = $rawTo !== '' ? date('d-m-Y', strtotime($rawTo)) : '';
        $idx++;

        $isBasic = $compId === 1;
        $nameLabel = htmlspecialchars($comp['component_name']);
        if ($isBasic) {
            $nameLabel .= ' <span class="badge bg-primary bg-opacity-20 text-primary ms-1"><i class="ph-star ph-xs me-1"></i>Basic</span>';
        }

        $html .= '<tr>';
        $html .= '<td class="text-muted align-middle">' . $idx . '</td>';
        $html .= '<td class="align-middle"><strong>' . $nameLabel . '</strong></td>';
        $html .= '<td>';
        $html .= '<input type="number" step="0.01" min="0" name="components[' . $compId . '][amount]" value="' . htmlspecialchars($amount) . '" class="form-control" placeholder="0.00">';
        $html .= '</td>';
        $html .= '<td>';
        $html .= '<input type="text" name="components[' . $compId . '][effective_from]" value="' . htmlspecialchars($effFrom) . '" class="form-control datepicker-batch" readonly>';
        $html .= '</td>';
        $html .= '<td>';
        $html .= '<input type="text" name="components[' . $compId . '][effective_to]" value="' . htmlspecialchars($effTo) . '" class="form-control datepicker-batch" readonly>';
        $html .= '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';
    return $html;
}
?>
<div class="content-wrapper">
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1 d-flex align-items-center gap-2">
                <h5 class="mb-0">Salary Structure</h5>
            </div>
            <div class="my-1">
                <?php if ($canEdit) { ?>
                    <button type="submit" form="frm<?php echo $module; ?>" class="btn btn-primary btn-sm me-2">Save All</button>
                    <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="action" value="batch_save">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <label class="col-lg-2 col-form-label"><span class="text-danger">Employee:*</span></label>
                            <div class="col-lg-4">
                                <select required name="employee_id" class="form-select" onchange="if(this.value) window.location='<?php echo $module; ?>.php?employee_id='+this.value;">
                                    <option value="0">Please select</option>
                                    <?php foreach ($employees as $emp) { ?>
                                        <option value="<?php echo $emp['id']; ?>" <?php echo (int)$emp['id'] === $employeeId ? 'selected' : ''; ?>><?php echo htmlspecialchars($emp['full_name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($employeeId > 0) { ?>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="ph-currency-circle-dollar me-2 text-success"></i>Earnings</h6>
                            </div>
                            <div class="card-body p-0">
                                <?php echo renderComponentTable($earningComponents, $existing, $dateOfJoining, $employeeId); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="ph-minus-circle me-2 text-danger"></i>Deductions</h6>
                            </div>
                            <div class="card-body p-0">
                                <?php echo renderComponentTable($deductionComponents, $existing, $dateOfJoining, $employeeId); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </form>
        </div>
        <?php include 'admin_elements/copyright.php'; ?>
    </div>
</div>
<script>
$(function() {
    $('.datepicker-batch').datepicker({
        dateFormat: 'dd-mm-yy',
        changeMonth: true,
        changeYear: true,
        yearRange: '2020:2050'
    });
});
</script>
<?php include 'admin_elements/admin_footer.php';
