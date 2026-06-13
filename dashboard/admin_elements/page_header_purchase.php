<?php

use App\Core\DB;

$purchase_id = "";
if (isset($_REQUEST['purchase_id'])) $purchase_id = e_s__($_REQUEST['purchase_id']);
if (isset($_POST['purchase_id']))    $purchase_id = e_s__($_POST['purchase_id']);

?>
<?php
$display_no = getTableAttr('purchase_no', DB::PURCHASES, $purchase_id);
$display_status = getTableAttr('purchase_status', DB::PURCHASES, $purchase_id);
if (empty($display_no)) $display_no = getTableAttr('id', DB::PURCHASES, $purchase_id);
?>
<div class="page-header page-header-light shadow">
    <div class="page-header-content d-lg-flex border-top">
        <div class="row mt-3"><div class="col-lg-12">
            <h1 class="ms-2"><a href="purchase_overview.php?purchase_id=<?php echo $purchase_id; ?>" class="text-black"><?php echo $display_no; ?></a></h1>
        </div></div>
        <div class="p-3 rounded mt-1"><label class="form-check-label text-muted small"><?php echo strtoupper($display_status); ?></label></div>
        <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
            <div class="d-lg-flex mb-2 mb-lg-0"><div class="mt-2 mb-2">
                <div class="row"><div class="col-lg-12 d-flex align-items-center">
                    <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
                </div></div>
            </div></div>
        </div>
    </div>
    <div class="row"><div class="row mb-1"><div class="col-lg-12 d-flex align-items-center">
        <?php if (isset($module_id) && granted('edit', $module_id)) { ?>
            <a href="<?php echo $module; ?>.php?action=edit_<?php echo $module; ?>&id=<?php echo $purchase_id; ?>" class="btn btn-light btn-sm me-2 ms-1"><i class="ph-pencil"></i> Edit</a>
        <?php } ?>
        <div class="dropdown"><button type="button" class="btn btn-light btn-sm me-2" data-bs-toggle="dropdown"><i class="ph-dots-three"></i></button>
            <div class="dropdown-menu dropdown-menu-end">
                <a href="purchase_overview.php?purchase_id=<?php echo $purchase_id; ?>&action=clone_<?php echo $module; ?>" class="dropdown-item"><i class="ph-copy me-2"></i> Clone</a>
                <a href="listing_<?php echo $module; ?>.php?action=delete_<?php echo $module; ?>&id=<?php echo $purchase_id; ?>" class="dropdown-item"><i class="ph-trash me-2"></i> Delete</a>
            </div>
        </div>
    </div></div></div>
</div>