<?php

use App\Core\DB;
use App\Security\Roles;
/* -------------------------------------------------------------------------- */

$customer_id = 0;
if (isset($_REQUEST['customer_id'])) {
    $customer_id = (int)$_REQUEST['customer_id'];
}
if (isset($_POST['customer_id'])) {
    $customer_id = (int)$_POST['customer_id'];
}

if ($customer_id <= 0) {
    header('Location:listing_customers.php');
    exit;
}


$customer_type  = getTableAttr('customer_type', DB::CUSTOMERS, $customer_id);
$display_name   = getTableAttr('display_name', DB::CUSTOMERS, $customer_id);
$approved       = getTableAttr('approved', DB::CUSTOMERS, $customer_id);
$approved_at    = getTableAttr('approved_at', DB::CUSTOMERS, $customer_id);
$publish        = getTableAttr('is_active', DB::CUSTOMERS, $customer_id);
$created_at     = getTableAttr('created_at', DB::CUSTOMERS, $customer_id);
$created_by     = getTableAttr('created_by', DB::CUSTOMERS, $customer_id);

/*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */
?>

<div class="page-header page-header-light shadow carriers-page-header">
    <div class="page-header-content d-lg-flex border-top carriers-page-header-content py-2 px-3">
        <div class="row mt-3">
            <div class="col-lg-12">
                <h1 class="ms-2"> <a href="customer_overview.php?customer_id=<?php echo $customer_id;?>" class="text-dark"><?php echo $display_name; ?></a></h1>
            </div>

            <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
            </a>
        </div>

        <div class="p-3 rounded mt-1">
            <label class="form-check-label text-muted small"><?php if ($publish == '1') { ?>Active <?php } else { ?> InActive <?php } ?></label>
        </div>

        <div class="p-3 rounded mt-1">
            <!-- approved
                    approved_by
                    approved_at -->
            <?php if ($approved != 1) { ?>
                <span class="badge bg-warning small text-white fw-normal">Request Approval</span>
            <?php } ?>
        </div>

        <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
            <div class="d-lg-flex mb-2 mb-lg-0">
                <div class="mt-2 mb-2">

                    <div class="row">
                        <div class="col-lg-12 d-flex align-items-center">

                            <?php if (granted_('edit', 'customers')) { ?>
                                <?php if (Roles::hasFullAccess($session_role_id)) { ?>



                                    <?php if ($approved == 0) { ?>
                                        <a href="customer_overview.php?action=approved&customer_id=<?php echo $customer_id; ?>&approve=1" class="btn btn-light btn-sm me-2">
                                            Approve
                                        </a>

                                    <?php } else { ?>
                                        <a href="customer_overview.php?action=disapproved&customer_id=<?php echo $customer_id; ?>&approve=0" class="btn btn-light btn-sm me-2">
                                            Dis-Approve
                                        </a>
                                    <?php } ?>


                                <?php } ?>

                            <?php } ?>



                            <?php if (isset($module_id) && granted('edit', $module_id)) { ?>
                                <button type="button" onclick="window.location.href='<?php echo $module; ?>.php?action=edit_customers&id=<?php echo $customer_id; ?>';" class="btn btn-light btn-sm me-2">Edit</button>
                            <?php } ?>

                            <!-- <button type="button" onclick="window.location.href='<?php echo $module; ?>.php?action=edit_customers&id=<?php echo $customer_id; ?>';" class="btn btn-light btn-sm me-2"><i class="ph-paperclip"></i></button> -->



                            <?php $transactions_disabled = ($approved != 1); ?>
                            <div class="dropdown d-inline-block">
                                <button type="button" class="btn btn-primary btn-sm me-2 dropdown-toggle<?php echo $transactions_disabled ? ' disabled' : ''; ?>" data-bs-toggle="dropdown" <?php echo $transactions_disabled ? 'aria-disabled="true"' : ''; ?> <?php echo $transactions_disabled ? 'tabindex="-1"' : ''; ?>>
                                    New Transaction
                                </button>

                                <div class="dropdown-menu dropdown-menu-end">
                                    <div class="dropdown-header text-uppercase fs-sm lh-sm">SALES</div>
                                    <a class="dropdown-item<?php echo $transactions_disabled ? ' disabled' : ''; ?>" href="<?php echo $transactions_disabled ? '#' : 'invoices.php?customer_id=' . $customer_id; ?>" <?php echo $transactions_disabled ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>
                                        Invoice
                                    </a>
                                    <a class="dropdown-item<?php echo $transactions_disabled ? ' disabled' : ''; ?>" href="<?php echo $transactions_disabled ? '#' : 'expenses.php?customer_id=' . $customer_id; ?>" <?php echo $transactions_disabled ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>
                                        Expense
                                    </a>
                                </div>
                            </div>

                            <div class="dropdown">
                                <button type="button" class="btn btn-light btn-sm me-2 dropdown-toggle" data-bs-toggle="dropdown">
                                    More
                                </button>

                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="customer_overview.php?action=clone_customers&customer_id=<?php echo $customer_id; ?>">
                                        Clone
                                    </a>


                                    <?php
                                    $customer_active_status = getTableAttr('is_active', DB::CUSTOMERS, $customer_id);
                                    if ($customer_active_status == 1) {
                                    ?>
                                        <a class="dropdown-item" href="customer_overview.php?action=mark_as_inactive&customer_id=<?php echo $customer_id; ?>">
                                            Mark as Inactive
                                        </a>

                                    <?php } else { ?>
                                        <a class="dropdown-item" href="customer_overview.php?action=mark_as_active&customer_id=<?php echo $customer_id; ?>">
                                            Mark as Active
                                        </a>
                                    <?php } ?>

                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="listing_customers.php?action=delete_customers&id=<?php echo $customer_id; ?>">
                                        Delete
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <div class="row">

        <ul class="nav nav-tabs nav-tabs-underline mb-0" role="tablist">
            <li class="nav-item">
                <a href="customer_overview.php?customer_id=<?php echo $customer_id; ?>" class="nav-link <?php if ($current_page == "customer_overview.php" || $current_page == "customer_billing_addresses.php" || $current_page == "customer_shipping_addresses.php") { ?> active fw-semibold<?php } ?>">Overview</a>
            </li>
            <li class="nav-item">
                <a href="customer_comments.php?customer_id=<?php echo $customer_id; ?>" class="nav-link <?php if ($current_page == "customer_comments.php") { ?> active fw-semibold<?php } ?>">Comments</a>
            </li>
            <li class="nav-item">
                <a href="<?php echo ($approved != 1) ? '#' : 'customer_transactions.php?customer_id=' . $customer_id; ?>" class="nav-link <?php if ($current_page == "customer_transactions.php") { ?> active fw-semibold<?php } ?><?php echo ($approved != 1) ? ' disabled' : ''; ?>" <?php echo ($approved != 1) ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>Transactions</a>
            </li>
            <li class="nav-item">
                <a href="customer_mails.php?customer_id=<?php echo $customer_id; ?>" class="nav-link <?php if ($current_page == "customer_mails.php") { ?> active fw-semibold<?php } ?>">Mails</a>
            </li>
            <li class="nav-item">
                <a href="customer_statement.php?customer_id=<?php echo $customer_id; ?>" class="nav-link <?php if ($current_page == "customer_statement.php") { ?> active fw-semibold<?php } ?>">Statement</a>
            </li>
        </ul>

    </div>

</div>
