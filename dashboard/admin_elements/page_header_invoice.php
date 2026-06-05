<?php

use App\Core\DB;
/* -------------------------------------------------------------------------- */

$invoice_id = '';
if (isset($_REQUEST['invoice_id']))        $invoice_id     = e_s__($_REQUEST['invoice_id']);
if (isset($_POST['invoice_id']))           $invoice_id     = e_s__($_POST['invoice_id']);


$invoice_no     = getTableAttr('invoice_no', DB::INVOICES, $invoice_id);
$invoice_status = getTableAttr('invoice_status', DB::INVOICES, $invoice_id);

// Journal/credit note features are disabled (tables removed)
$has_journal_entries = false;
$has_void_entry = false;
$has_writeoff_entry = false;
$outstanding_balance = 0;
$grand_total = 0;

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
?>

<div class="page-header page-header-light shadow">
    <div class="page-header-content d-lg-flex border-top">
        <div class="row mt-3">
            <div class="col-lg-12">
                <h1 class="ms-2"> <a href="invoice_overview.php?invoice_id=<?php echo $invoice_id; ?>" class="text-black"><?php echo $invoice_no; ?></a> </h1>
            </div>

            <a href=" #breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
            </div>

            <div class="p-3 rounded mt-1">
                <label class="form-check-label text-muted small"><?php echo (!empty($invoice_status) ? strtoupper($invoice_status) : ''); ?></label>
            </div>

            <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                <div class="d-lg-flex mb-2 mb-lg-0">
                    <div class="mt-2 mb-2">

                        <div class="row">
                            <div class="col-lg-12 d-flex align-items-center">

                                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">
                                    Cancel
                                </a>

                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <div class="row">


            <div class="row mb-1">
                <div class="col-lg-12 d-flex align-items-center">

                    <?php if (isset($module_id) && granted('edit', $module_id) && !$has_void_entry && !$has_writeoff_entry) { ?>
                        <a href="<?php echo $module; ?>.php?action=edit_<?php echo $module; ?>&id=<?php echo $invoice_id; ?>" class="btn btn-light btn-sm me-2 ms-1">
                            <i class="ph-pencil"></i> Edit
                        </a>
                    <?php } ?>


                    <a class="btn btn-light btn-sm me-2" href="send_email.php?current_module=<?php echo $module; ?>&id=<?php echo $invoice_id; ?>">
                        <i class="ph-envelope-simple pe-1"></i> Send Email
                    </a>


                    <?php $token = hash("sha512", 'bushogai' . $invoice_id); ?>
                    <a class="btn btn-light btn-sm me-2" href="pdf_invoice.php?id=<?php echo $invoice_id; ?>&token=<?php echo $token; ?>" target="_blank">
                        <i class="ph-file-pdf pe-1"></i> PDF
                    </a>

                    <?php $invoice_status = getTableAttr('invoice_status', DB::INVOICES, $invoice_id); ?>

                    <?php if ($invoice_status == 'draft') { ?>
                        <a class="btn btn-light btn-sm me-2" href="invoice_overview.php?invoice_id=<?php echo $invoice_id; ?>&action=update_<?php echo $module; ?>&invoice_status=sent">
                            <i class="ph-check pe-1"></i> Mark As Sent
                        </a>

                    <?php } ?>



                    <div class="dropdown">
                        <button type="button" class="btn btn-light btn-sm me-2" data-bs-toggle="dropdown">
                            <i class="ph-dots-three"></i>
                        </button>

                        <div class="dropdown-menu dropdown-menu-end">

                            <a href="recurring_invoices.php?action=add_invoices&source_invoice_id=<?php echo $invoice_id; ?>" class="dropdown-item">
                                <i class="ph-arrows-clockwise me-2"></i>
                                Make Recurring
                            </a>

                            <a href="invoice_overview.php?invoice_id=<?php echo $invoice_id; ?>&action=clone_<?php echo $module; ?>" class="dropdown-item">
                                <i class="ph-copy me-2"></i>
                                Clone
                            </a>
                            <?php if (!$has_void_entry && !$has_writeoff_entry) { ?>
                            <a href="listing_<?php echo $module; ?>.php?action=delete_<?php echo $module; ?>&id=<?php echo $invoice_id; ?>" class="dropdown-item">
                                <i class="ph-trash me-2"></i>
                                Delete
                            </a>
                            <?php } ?>

                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>