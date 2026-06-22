<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var string $module
 * @var string $moduleCaption
 * @var int $moduleId
 * @var int $session_user_id
 * @var int $session_role_id
 * @var string $error_message
 * @var string $invoice_date
 * @var string $reference_no
 * @var string $customer_id
 * @var string $invoice_status
 * @var string $warehouse_id
 * @var string $no_of_packs
 * @var string $gross_weight
 * @var string $master_awb_no
 * @var string $grand_total
 * @var int $total_rows
 * @var array $item_id_arr
 * @var array $description_arr
 * @var array $coo_arr
 * @var array $declaration_no_arr
 * @var array $hscode_arr
 * @var array $qty_arr
 * @var array $rate_arr
 * @var array $total_arr
 * @var array $customersList
 * @var array $warehousesList
 * @var array $countriesList
 * @var bool $canCreate
 * @var bool $canEdit
 */
include 'admin_elements/admin_header.php';

$customer_options = '';
foreach ($customersList as $c) {
    $sel = ((string)$c['id'] === $customer_id) ? 'selected' : '';
    $customer_options .= '<option value="' . $c['id'] . '" ' . $sel . '>' . s__($c['display_name']) . '</option>';
}

$warehouse_options = '';
foreach ($warehousesList as $w) {
    $sel = ((string)$w['id'] === $warehouse_id) ? 'selected' : '';
    $warehouse_options .= '<option value="' . $w['id'] . '" ' . $sel . '>' . s__($w['warehouse_name']) . '</option>';
}
?>
<div class="content-wrapper">
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1 d-flex align-items-center gap-3">
                <h5 class="mb-0"><?php echo ($id > 0) ? 'Update' : 'Create'; ?> <?php echo $moduleCaption; ?></h5>
                <?php if ($id > 0): ?>
                    <span class="badge bg-light text-dark border fw-semibold">Invoice #: <?php echo $reference_no; ?></span>
                <?php endif; ?>
                <strong><?php echo ((empty($invoice_status) ? 'Not Confirmed' : colorfulInvoiceStatus($invoice_status))); ?></strong>
            </div>
            <div class="my-1">
                <?php if ($canCreate || $canEdit): ?>
                    <button type="submit" form="frm<?php echo $module; ?>" class="btn btn-primary btn-sm me-2"><?php echo ($id > 0) ? 'Update' : 'Save'; ?> <?php echo $moduleCaption; ?></button>
                <?php endif; ?>
                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Exit</a>
            </div>
        </div>
    </div>

    <div class="content-inner">
        <div class="content">
            <?php include('admin_elements/breadcrumb.php'); ?>

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
                <?php if ($id > 0): ?>
                    <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
                    <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
                <?php else: ?>
                    <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
                <?php endif; ?>

                <div class="col-xl-12">
                    <div class="row p-lg-2">
                        <div class="col-lg-2">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Customer Name: <span class="text-danger">*</span></label>
                                <select name="customer_id" id="customer_id" class="form-control select" required>
                                    <option value="0">Please select</option>
                                    <?php echo $customer_options; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Invoice Date: <span class="text-danger">*</span></label>
                                <div class="form-control-feedback form-control-feedback-start">
                                    <input type="text" class="form-control" placeholder="Requested Date" name="invoice_date" id="invoice_date" value="<?php echo $invoice_date; ?>" required autocomplete="off">
                                    <div class="form-control-feedback-icon">
                                        <i class="ph-calendar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Warehouse: <span class="text-danger">*</span></label>
                                <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                                    <option value="0">Please select</option>
                                    <?php echo $warehouse_options; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Reference No:</label>
                                <input type="text" class="form-control" name="reference_no" id="reference_no" value="<?php echo $reference_no; ?>">
                            </div>
                        </div>

                        <div class="col-lg-1">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Status:</label>
                                <select name="invoice_status" id="invoice_status" class="form-select">
                                    <option value="draft" <?php if ($invoice_status === 'draft') echo 'selected'; ?>>Draft</option>
                                    <option value="sent" <?php if ($invoice_status === 'sent') echo 'selected'; ?>>Sent</option>
                                    <option value="open" <?php if ($invoice_status === 'open') echo 'selected'; ?>>Open</option>
                                    <option value="revised" <?php if ($invoice_status === 'revised') echo 'selected'; ?>>Revised</option>
                                    <option value="declined" <?php if ($invoice_status === 'declined') echo 'selected'; ?>>Declined</option>
                                    <option value="accepted" <?php if ($invoice_status === 'accepted') echo 'selected'; ?>>Accepted</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-12">
                        <div class="row mb-2">
                            <div class="col-lg-3">
                                <label class="form-label ms-3">Description <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label ms-3">Origin <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-lg-1">
                                <label class="form-label">Declaration No <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-lg-1">
                                <label class="form-label ms-3">HS Code <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-lg-1">
                                <label class="form-label ms-3">Qty</label>
                            </div>
                            <div class="col-lg-1">
                                <label class="form-label ms-4">Unit Price</label>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label ms-2">Total</label>
                            </div>
                        </div>

                        <div class="card">
                            <div class="row card-body">
                                <div class="col-lg-12">
                                    <?php for ($item = 1; $item <= $total_rows; $item++):
                                        $index = $item - 1;
                                        $item_id_val = (!empty($item_id_arr[$index]) ? $item_id_arr[$index] : '');
                                        $description_val = (!empty($description_arr[$index]) ? $description_arr[$index] : '');
                                        $coo_val = (!empty($coo_arr[$index]) ? $coo_arr[$index] : '0');
                                        $declaration_no_val = (!empty($declaration_no_arr[$index]) ? $declaration_no_arr[$index] : '');
                                        $hscode_val = (!empty($hscode_arr[$index]) ? $hscode_arr[$index] : '');
                                        $qty_val = (!empty($qty_arr[$index]) ? $qty_arr[$index] : '1');
                                        $rate_val = (!empty($rate_arr[$index]) ? $rate_arr[$index] : '0');
                                        $total_val = (!empty($total_arr[$index]) ? $total_arr[$index] : '');
                                    ?>
                                        <div class="mb-2">
                                            <div class="row mb-3 pb-3" id="row_<?php echo $item; ?>">
                                                <div class="col-lg-12">
                                                    <div class="row">
                                                        <input type="hidden" name="item_id[]" id="item_id<?php echo $item; ?>" value="<?php echo $item_id_val; ?>">

                                                        <div class="col-lg-3">
                                                            <input class="form-control" name="description[]" id="description<?php echo $item; ?>" placeholder="Add a description to your item" value="<?php echo $description_val; ?>">
                                                        </div>

                                                        <div class="col-lg-2">
                                                            <select class="form-select" name="coo[]" id="coo<?php echo $item; ?>">
                                                                <option value="0">Please select</option>
                                                                <?php foreach ($countriesList as $cnt): ?>
                                                                    <option value="<?php echo $cnt['id']; ?>" <?php echo ((!empty($coo_val) && (string)$coo_val === (string)$cnt['id']) ? 'selected="selected"' : ''); ?>>
                                                                        <?php echo s__($cnt['abbr']); ?> - <?php echo s__($cnt['country']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input class="form-control" name="declaration_no[]" id="declaration_no<?php echo $item; ?>" placeholder="Declaration no" value="<?php echo $declaration_no_val; ?>">
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input class="form-control" name="hscode[]" id="hscode<?php echo $item; ?>" placeholder="HS Code" value="<?php echo $hscode_val; ?>">
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <div class="input-group">
                                                                <button type="button" class="btn btn-light btn-icon" onclick="this.parentNode.querySelector('input[type=number]').stepDown(); calculateItemAmount('<?php echo $item; ?>');">
                                                                    <i class="ph-minus ph-sm"></i></button>
                                                                <input class="form-control form-control-number text-center" type="number" name="qty[]" id="qty<?php echo $item; ?>" value="<?php echo $qty_val; ?>" min="1" onkeyup="calculateItemAmount('<?php echo $item; ?>');" onchange="calculateItemAmount('<?php echo $item; ?>');">
                                                                <button type="button" class="btn btn-light btn-icon" onclick="this.parentNode.querySelector('input[type=number]').stepUp(); calculateItemAmount('<?php echo $item; ?>');"><i class="ph-plus ph-sm"></i></button>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input type="number" step="1" name="rate[]" id="rate<?php echo $item; ?>" min="0" class="form-control text-center" value="<?php echo $rate_val; ?>" onkeyup="calculateItemAmount('<?php echo $item; ?>');" onchange="calculateItemAmount('<?php echo $item; ?>');">
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input type="number" name="total[]" id="total<?php echo $item; ?>" min="0" class="form-control text-end" placeholder="0" value="<?php echo $total_val; ?>" onchange="calculateGrand();" onkeyup="calculateGrand();">
                                                        </div>

                                                        <div class="col-lg-2 mt-1">
                                                            <?php if ($item > 1): ?>
                                                                <a href="#" onclick="calculateItemAmount('<?php echo $item; ?>'); clear_row(<?php echo $item; ?>)"><span class="badge bg-warning"> <i class="ph-x"></i> </span></a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endfor; ?>

                                    <div id="add_row_here"></div>
                                </div>

                                <div class="">
                                    <span id="span_add_item_row"><a href="#" onclick="add_item_row();"><span class="badge bg-primary"> Add New Row </span></a></span>
                                </div>

                                <script>
                                    const countriesList = [
                                        <?php foreach ($countriesList as $cnt): ?>
                                            {id: <?php echo $cnt['id']; ?>, name: '<?php echo addslashes((string)$cnt['country']); ?>', abbr: '<?php echo addslashes((string)$cnt['abbr']); ?>'},
                                        <?php endforeach; ?>
                                    ];

                                    function ajax_populate_coo() {
                                        var total_rows = document.getElementById('total_rows').value;
                                        var selectEl = document.getElementById('coo' + total_rows);
                                        if (!selectEl) return;
                                        selectEl.innerHTML = '<option value="0">Please select</option>';
                                        countriesList.forEach(function(country) {
                                            var opt = document.createElement('option');
                                            opt.value = country.id;
                                            opt.textContent = country.abbr + ' - ' + country.name;
                                            selectEl.appendChild(opt);
                                        });
                                    }

                                    function add_item_row() {
                                        var total_rows = document.getElementById('total_rows').value;
                                        total_rows++;

                                        var new_row = "";
                                        new_row += "<div class=\"row mb-3 pb-3\" id=\"row_" + total_rows + "\">";
                                        new_row += "<input type=\"hidden\" name=\"item_id[]\" id=\"item_id" + total_rows + "\">";

                                        new_row += "<div class=\"col-lg-3\">";
                                        new_row += "<input class=\"form-control\" name=\"description[]\" id=\"description" + total_rows + "\" placeholder=\"Add a description to your item\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-2\">";
                                        new_row += "<select class=\"form-select\" name=\"coo[]\" id=\"coo" + total_rows + "\">";
                                        new_row += "<option value=\"0\">Please select</option>";
                                        new_row += "</select>";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input class=\"form-control\" name=\"declaration_no[]\" id=\"declaration_no" + total_rows + "\" placeholder=\"Declaration no\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input class=\"form-control\" name=\"hscode[]\" id=\"hscode" + total_rows + "\" placeholder=\"HS Code\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<div class=\"input-group\">";
                                        new_row += "<button type=\"button\" class=\"btn btn-light btn-icon\" onclick=\"this.parentNode.querySelector('input[type=number]').stepDown(); calculateItemAmount('" + total_rows + "'); \"><i class=\"ph-minus ph-sm\"></i></button>";
                                        new_row += "<input class=\"form-control form-control-number text-center\" type=\"number\" name=\"qty[]\" id=\"qty" + total_rows + "\" value=\"1\" min=\"1\" onkeyup=\"calculateItemAmount('" + total_rows + "');\" onchange=\"calculateItemAmount('" + total_rows + "');\">";
                                        new_row += "<button type=\"button\" class=\"btn btn-light btn-icon\" onclick=\"this.parentNode.querySelector('input[type=number]').stepUp(); calculateItemAmount('" + total_rows + "'); \"><i class=\"ph-plus ph-sm\"></i></button>";
                                        new_row += "</div>";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input type=\"number\" step=\"1\" name=\"rate[]\" id=\"rate" + total_rows + "\" min=\"0\" class=\"form-control text-center\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input type=\"number\" name=\"total[]\" id=\"total" + total_rows + "\" min=\"0\" class=\"form-control text-end\" placeholder=\"0\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-2 mt-1\"><span id=\"span_remove_item_row" + total_rows + "\"> <a href=\"#\" onclick=\"clear_row(" + total_rows + ")\"><span class=\"badge bg-warning\"> <i class=\"ph-x\"></i> </span></a></span> </div>";

                                        new_row += "</div>";

                                        document.getElementById('add_row_here').insertAdjacentHTML("beforebegin", new_row);
                                        document.getElementById('total_rows').value = total_rows;
                                        ajax_populate_coo();
                                    }

                                    function clear_row(row_no) {
                                        calculateItemAmount(row_no);
                                        document.getElementById('description' + row_no).value = '';
                                        document.getElementById('coo' + row_no).value = '0';
                                        document.getElementById('declaration_no' + row_no).value = '';
                                        document.getElementById('hscode' + row_no).value = '';
                                        document.getElementById('qty' + row_no).value = '';
                                        document.getElementById('rate' + row_no).value = '';
                                        document.getElementById('total' + row_no).value = '';
                                        document.getElementById('row_' + row_no).style.display = 'none';
                                    }

                                    function calculateItemAmount(row_no) {
                                        var qty = document.getElementById('qty' + row_no).value;
                                        qty = Number(qty);
                                        var rate = document.getElementById('rate' + row_no).value;
                                        var total = parseFloat(rate * qty).toFixed(2);
                                        document.getElementById('total' + row_no).value = parseFloat(total).toFixed(2);
                                        calculateGrand();
                                    }

                                    function calculateGrand() {
                                        var total_rows = document.getElementById('total_rows').value;
                                        var final_total = 0;
                                        for (var i = 1; i <= total_rows; i++) {
                                            var total = document.getElementById('total' + i).value;
                                            final_total += Number(total);
                                        }
                                        document.getElementById('grand_total').value = parseFloat(final_total.toFixed(2));
                                    }
                                </script>

                                <input type="hidden" name="total_rows" id="total_rows" value="<?php echo $total_rows; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="ms-sm-3 mb-3 mb-sm-0">
                                        <label class="col-lg-6 col-form-label">PLT/BOX/PKG's:</label>
                                        <input type="text" class="form-control" name="no_of_packs" id="no_of_packs" value="<?php echo $no_of_packs; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="ms-sm-3 mb-3 mb-sm-0">
                                        <label class="col-lg-6 col-form-label">WEIGHT:</label>
                                        <input type="text" class="form-control" name="gross_weight" id="gross_weight" value="<?php echo $gross_weight; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="ms-sm-3 mb-3 mb-sm-0">
                                        <label class="col-lg-6 col-form-label">Master AWB No:</label>
                                        <input type="text" class="form-control" name="master_awb_no" id="master_awb_no" value="<?php echo $master_awb_no; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3"></div>

                        <div class="col-lg-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label fw-semibold">Grand Total</label>
                                        <div class="col-lg-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo BASE_CURRENCY['code']; ?></span>
                                                <input type="number" class="form-control fw-semibold bg-light bg-opacity-50 text-end" name="grand_total" id="grand_total" value="<?php echo $grand_total; ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </form>
        </div>
    </div>
    <?php include('admin_elements/copyright.php'); ?>
</div>
</div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
