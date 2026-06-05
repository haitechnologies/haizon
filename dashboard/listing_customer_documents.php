<?php

include('admin_elements/admin_header.php');
require_once __DIR__ . '/../classes/InputValidator.php';

$module             = 'customer_documents';
$module_caption     = 'Document';
$tbl_name             = $tbl_prefix . $module;
$error_message         = '';
$success_message     = '';


/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests involving actions
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in listing_customer_documents.php', 'WARNING', __FILE__, __LINE__);
        $_POST['action'] = '';
    }
}


/*
|--------------------------------------------------------------------------
| 	PAGINATION
|--------------------------------------------------------------------------
|
*/

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

if (isset($_POST['is_active']))       $is_active = 1;
else $is_active = 0;


$customer_id = '';
if (isset($_REQUEST['customer_id']))        $customer_id     = e_s__($_REQUEST['customer_id']);
if (isset($_POST['customer_id']))           $customer_id     = e_s__($_POST['customer_id']);



$document_id = 0;
if (isset($_REQUEST['document_id']))        $document_id     = e_s__($_REQUEST['document_id']);
if (isset($_POST['document_id']))           $document_id     = e_s__($_POST['document_id']);





$limit              = 10;
$stages             = 2;


if (isset($_GET['page_no']) && !empty($_GET['page_no'])) {
    $page_no            = e_s__($_GET['page_no']);
} else {
    $page_no            = 1;
}

$targetpage = 'listing_customer_documents.php?customer_id=' . $id;

// $targetpage            = 'report_bookings.php?action=generate_report&date_from=' . $date_from . '&date_to=' . $date_to . '&ticket_enumber=' . $ticket_enumber . '&booking_full_name=' . $booking_full_name . '&booking_mobile=' . $booking_mobile . '&is_free=' . $is_free . '&ticket_type=' . $ticket_type . '&check=' . $check . '&pax=' . $pax . '&batch_id=' . $batch_id . '&total_cost=' . $total_cost . '&grand_total=' . $grand_total . '&booking_status=' . $booking_status;



if ($page_no) {
    $start   = ($page_no - 1) * $limit;
} else {
    $start   = 0;
}



/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


// print_r($_REQUEST);

if (isset($_REQUEST['customer_id']) && !empty($_REQUEST['customer_id'])) {
    $customer_id     = e_s__($_REQUEST['customer_id']);
} else {
    $customer_id = 0;
}



/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($customer_id)) && granted('delete', $module_id)) {

    // INPUT VALIDATION: Validate document ID
    $idResult = InputValidator::integer($document_id, 1);
    if (!$idResult['valid']) {
        $error_message = "Invalid document ID: " . $idResult['error'];
    } else {
        $validDocumentId = $idResult['value'];
        
        //SUPERADMIN CAN DELETE ANY DATA
        if (Roles::hasFullAccess($session_role_id)) {

            // Perform delete with prepared statement
            $stmt = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id=?");
            $stmt->bind_param("i", $validDocumentId);
            
            if ($stmt->execute()) {
                // Customer Logs
                updateCustomerLogs($customer_id, 'document', 'deleted');
                $success_message = "$module_caption Deleted Successfully.";
            } else {
                $error_message = "Database error: " . $stmt->error;
                log_error("Delete failed for document $validDocumentId: " . $stmt->error, 'ERROR', __FILE__, __LINE__);
            }
            $stmt->close();

            //ADMIN CAN DELETE ONLY HIS/HER DATA
        } else {
            // Verify ownership before deleting
            $ownershipCheck = $mysqli->prepare("SELECT id, created_by FROM `" . $tbl_name . "` WHERE id=? AND created_by=?");
            $ownershipCheck->bind_param("is", $validDocumentId, $_SESSION[$project_pre]['DASHBOARD']['user_id']);
            $ownershipCheck->execute();
            $ownershipResult = $ownershipCheck->get_result();
            $ownershipCheck->close();
            
            if ($ownershipResult->num_rows === 0) {
                $error_message = "You do not have permission to delete this document";
                log_error("IDOR attempt: User " . $_SESSION[$project_pre]['DASHBOARD']['user_id'] . " tried to delete document $validDocumentId", 'WARNING', __FILE__, __LINE__);
            } else {
                // Perform delete with prepared statement
                $stmt = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id=? AND created_by=?");
                $stmt->bind_param("is", $validDocumentId, $_SESSION[$project_pre]['DASHBOARD']['user_id']);
                
                if ($stmt->execute()) {
                    // Customer Logs
                    updateCustomerLogs($customer_id, 'document', 'deleted');
                    $success_message = "$module_caption Deleted Successfully.";
                } else {
                    $error_message = "Database error: " . $stmt->error;
                    log_error("Delete failed for document $validDocumentId: " . $stmt->error, 'ERROR', __FILE__, __LINE__);
                }
                $stmt->close();
            }
        }
    }


    if ($result) {
        $success_message = "$module_caption Deleted Successfully.";
        // header("Location:listing_$module.php?page=$page&success_message=$success_message");
    } else {
        $error_message = "Sorry! $module Could Not Be Deleted.";
    }
}



if (isset($_POST['is_active']))                                 $is_active = 1;
else $is_active = 0;


/*
|--------------------------------------------------------------------------
| SEARCH QUERY
|--------------------------------------------------------------------------
|
*/

// erp_customer_documents table decommissioned
$total_pages = 0;
$result_customer_documents = null;

// EXPIRED / NEAR EXPIRY / UP-TO-DATE counts (table dropped)
$total_expired = 0;
$total_near_expiry = 0;
$total_up_to_date = 0;



/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">


    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>

        <!-- Page header -->
        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="d-flex">
                    <div class="breadcrumb py-2">
                        <a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
                        <a href="index.php" class="breadcrumb-item">Home</a>
                        <a href="listing_customers.php" class="breadcrumb-item">Customers</a>
                        <span class="breadcrumb-item active">Documents</span>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>

                <div class="collapse d-lg-block ms-lg-auto mt-1" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">

                        <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                            <button type="button" class="btn btn-info my-1 me-2 nav-link" data-href="<?php echo $admin_base_url; ?>/customer_documents.php?customer_id=<?php echo $customer_id; ?>">Create Document</button>
                        <?php } ?>

                    </div>
                </div>

            </div>
        </div>
        <!-- /page header -->


        <div class="content-inner">
            <div class="content datatable-enhanced">

                <?php include('admin_elements/breadcrumb.php'); ?>


                <div class="row">

                    <!-- <div class="col-lg-6 col-xl-2"> -->
                    <?php include(__DIR__ . '/admin_elements/customer_navbar.php'); ?>
                    <!-- </div> -->

                    <div class="col-lg-6 col-xl-10">

                        <div class="row">
                            <div class="col-lg-12">


                                <?php
                                // erp_customer_documents decommissioned — all counts already set to 0 above
                                ?>

                                <div class="row">
                                    <div class="col-lg-6 col-xl-4">
                                        <div class="row mb-3">

                                            <div class="col-lg-3">
                                                <a href="#"><span class="badge bg-danger"> <?php echo $total_expired; ?> EXPIRED</span> </a>
                                            </div>
                                            <div class="col-lg-3">
                                                <a href="#"><span class="badge bg-warning"> <?php echo $total_near_expiry; ?> NEAR EXPIRY</span> </a>
                                            </div>
                                            <div class="col-lg-3">
                                                <a href="#"><span class="badge bg-success"> <?php echo $total_up_to_date; ?> UPDATE-TO-DATE</span> </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><?php echo $total_pages; ?> Documents Found.</h5>
                                    </div>


                                    <div class="card-body">

                                        <div class="table datatable-professional-responsive">
                                            <table class="table datatable-professional">

                                                <thead>
                                                    <tr>
                                                        <th width="80">SR.</th>
                                                        <th>DOCUMENT NAME</th>
                                                        <th>CATEGORY</th>
                                                        <th>DOCUMENT</th>
                                                        <th>ISSUE DATE</th>
                                                        <th>EXPIRY DATE</th>
                                                        <th></th>
                                                        <th>DATE CREATED</th>
                                                        <!-- <th>STATUS</th> -->
                                                        <th width="120">ACTION</th>
                                                    </tr>
                                                </thead>


                                                <tbody>

                                                    <?php

                                                    // ---------------------------------------------------------------------------------------
                                                    while ($row_customer_documents = $result_customer_documents->fetch_array(MYSQLI_ASSOC)) {

                                                        $document_id             = $row_customer_documents["id"];

                                                        $document_category  = s__($row_customer_documents['document_category']);
                                                        if (defined('tbl_document_categories')) {
                                                            $document_category_table = constant('tbl_document_categories');
                                                            $document_category  = getTableAttr('document_category', $document_category_table, $document_category);
                                                        }

                                                        $document_name      = s__($row_customer_documents['document_name']);
                                                        $document_filename  = s__($row_customer_documents['document_filename']);
                                                        $issued_date        = s__($row_customer_documents['issued_date']);
                                                        $expiry_date        = s__($row_customer_documents['expiry_date']);


                                                        // DOCUMENT EXPIRY STATUS
                                                        if ($expiry_date <= date('Y-m-d', time())) {
                                                            $document_expiry_status = '<span class="badge bg-danger">Expired</span>';
                                                        } else if ($expiry_date > date('Y-m-d', time()) && $expiry_date <= date('Y-m-d', strtotime('+30 days'))) {
                                                            $document_expiry_status = '<span class="badge bg-warning">Near Expiry</span>';
                                                        } else {
                                                            $document_expiry_status = '<span class="badge bg-success">Up-to-Date</span>';
                                                        }


                                                        $description        = s__($row_customer_documents['description']);

                                                        $issued_date        = ($issued_date == '1970-01-01' ? '' : processDateDtoY($issued_date));
                                                        $expiry_date        = ($expiry_date == '1970-01-01' ? '' : processDateDtoY($expiry_date));

                                                        $created_at             = s__($row_customer_documents['created_at']);
                                                        $is_active = s__($row_customer_documents['publish']);

                                                        if ($is_active == 0)
                                                            $status = '<span class="badge bg-warning">InActive</span>';
                                                        else
                                                            $status = '<span class="badge bg-success">Active</span>';


                                                        $created_at     = date('m-d-Y', strtotime($created_at));

                                                        // ---------------------------------------------------------------------------------------
                                                    ?>

                                                        <tr>
                                                            <td><a href="customer_documents.php?action=edit_customer_documents&document_id=<?php echo $document_id; ?>&customer_id=<?php echo $customer_id; ?>"><?php echo $document_id; ?></a></td>
                                                            <td><a href="customer_documents.php?action=edit_customer_documents&document_id=<?php echo $document_id; ?>&customer_id=<?php echo $customer_id; ?>"><?php echo $document_name; ?></a></td>
                                                            <td><a href="customer_documents.php?action=edit_customer_documents&document_id=<?php echo $document_id; ?>&customer_id=<?php echo $customer_id; ?>"><?php echo $document_category; ?></a></td>
                                                            <td><a href="customer_documents.php?action=edit_customer_documents&document_id=<?php echo $document_id; ?>&customer_id=<?php echo $customer_id; ?>"><?php echo $document_filename; ?></a></td>
                                                            <td><a href="customer_documents.php?action=edit_customer_documents&document_id=<?php echo $document_id; ?>&customer_id=<?php echo $customer_id; ?>"><?php echo $issued_date; ?></a></td>
                                                            <td><a href="customer_documents.php?action=edit_customer_documents&document_id=<?php echo $document_id; ?>&customer_id=<?php echo $customer_id; ?>"><?php echo $expiry_date; ?></a></td>
                                                            <td><a href="customer_documents.php?action=edit_customer_documents&document_id=<?php echo $document_id; ?>&customer_id=<?php echo $customer_id; ?>"><?php echo $document_expiry_status; ?></a></td>
                                                            <td><a href="customer_documents.php?action=edit_customer_documents&document_id=<?php echo $document_id; ?>&customer_id=<?php echo $customer_id; ?>"><?php echo $created_at; ?></a></td>

                                                            <td>
                                                                <a href="customer_documents.php?action=edit_customer_documents&document_id=<?php echo $document_id; ?>&customer_id=<?php echo $customer_id; ?>"><i class="ph-pencil"></i></a>
                                                                <a href="listing_customer_documents.php?action=delete_customer_documents&document_id=<?php echo $document_id; ?>&customer_id=<?php echo $customer_id; ?>"><i class="ph-trash"></i></a>
                                                            </td>

                                                        </tr>

                                                    <?php
                                                    } //while
                                                    ?>


                                                </tbody>
                                            </table>
                                        </div>

                                    </div>


                                </div>


                            </div>

                            <!-- <div class="col-lg-4">
                                <div class="card">
                                </div>
                            </div> -->

                        </div>




                        <div class="row">

                            <!--Pagination -->
                            <?php
                            // @@@ PAGINATION ALGO @@@ //

                            if ($page_no == 0) {
                                $page_no = 1;
                            }

                            // echo $page_no;

                            $prev = $page_no - 1;
                            $next = $page_no + 1;

                            $lastpage     = ceil($total_pages / $limit);
                            $LastPagem1 = $lastpage - 1;

                            $pagination = '';

                            if ($lastpage > 1) {
                                $pagination .= '<div class="center-block text-center">';
                                $pagination .= '<ul class="pagination mb-5 mb-lg-0">';

                                // PREVIOUS
                                if ($page_no > 1) {
                                    $pagination    .= '<li class="page-item page-prev"><a class="page-link" href="' . $targetpage . '&page_no=' . $prev . '" tabindex="-1">Prev</a></li>';
                                } else {
                                    $pagination    .= '<li class="page-item page-prev disabled"><a class="page-link" href="#" tabindex="-1">Prev</a></li>';
                                }

                                // Pages
                                if ($lastpage < 7 + ($stages * 2))    // Not enough pages to breaking it up
                                {
                                    for ($counter = 1; $counter <= $lastpage; $counter++) {
                                        if ($counter == $page_no) {
                                            $pagination .= '<li class="page-item active"><a class="page-link" href="#">' . $counter . '</a></li>';
                                        } else {
                                            $pagination    .= "<li class=\"page-item\"><a class=\"page-link\" href='" . $targetpage . "&page_no=" . $counter . "'>" . $counter . "</a></li>";
                                        }
                                    }
                                } else if ($lastpage > 5 + ($stages * 2))    // Enough pages to hide a few?
                                {
                                    // Beginning only hide later pages
                                    if ($page_no < 1 + ($stages * 2)) {

                                        for ($counter = 1; $counter < 4 + ($stages * 2); $counter++) {
                                            if ($counter == $page_no) {
                                                $pagination .= '<li class="page-item active"><a class="page-link" href="#">' . $counter . '</a></li>';
                                            } else {
                                                $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href='" . $targetpage . "'>" . $counter . "</a></li>";
                                            }
                                        }

                                        $pagination .= '<li class="page-item"><a class="page-link" href="#">...</a></li>';
                                        $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href='" . $targetpage . "&page_no=" . $LastPagem1 . "'>$LastPagem1</a></li>";
                                        $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href='" . $targetpage . "&page_no=" . $lastpage . "'>$lastpage</a></li>";
                                    }
                                    // Middle hide some front and some back
                                    elseif ($lastpage - ($stages * 2) > $page_no && $page_no > ($stages * 2)) {
                                        $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href='" . $targetpage . "&page_no=1'>1</a></li>";
                                        $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href='" . $targetpage . "&page_no=2'>2</a></li>";
                                        $pagination .= '<li class="page-item"><a class="page-link" href="#">...</a></li>';

                                        for ($counter = $page_no - $stages; $counter <= $page_no + $stages; $counter++) {
                                            if ($counter == $page_no) {
                                                $pagination .= '<li class="page-item active"><a class="page-link" href="#">' . $counter . '</a></li>';
                                            } else {
                                                $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href='" . $targetpage . "&page_no=" . $counter . "'>" . $counter . "</a></li>";
                                            }
                                        }

                                        $pagination .= '<li class="page-item"><a class="page-link" href="#">...</a></li>';
                                        $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href='" . $targetpage . "&page_no=" . $LastPagem1 . "'>$LastPagem1</a></li>";
                                        $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href='" . $targetpage . "&page_no=" . $lastpage . "'>$lastpage</a></li>";
                                    }
                                    // End only hide early pages
                                    else {
                                        $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href='." . $targetpage . "&page_no=1'>1</a></li>";
                                        $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href='" . $targetpage . "&page_no=2'>2</a></li>";
                                        $pagination .= '<li class="page-item"><a class="page-link" href="#">...</a></li>';

                                        for ($counter = $lastpage - (2 + ($stages * 2)); $counter <= $lastpage; $counter++) {
                                            if ($counter == $page_no) {
                                                $pagination .= '<li class="page-item active"><a class="page-link" href="#">1' . $counter . '</a></li>';
                                            } else {
                                                $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href='" . $targetpage . "&page_no=" . $counter . "'>" . $counter . "</a></li>";
                                            }
                                        }
                                    }
                                }
                                // Next
                                if ($page_no < $counter - 1) {
                                    $pagination .= '<li class="page-item page-next"><a class="page-link" href="' . $targetpage . '&page_no=' . $next . '">Next</a></li>';
                                } else {
                                    $pagination .= '<li class="page-item page-next"><a class="page-link" href="#">Next</a></li>';
                                }

                                $pagination .= "</ul>";
                                $pagination .= "</div>";
                            } //endif

                            echo $pagination;
                            ?>
                            <!--/Pagination -->

                            <?php
                            // } // endif
                            ?>


                        </div>


                    </div>
                </div>



            </div>


        </div>

        <?php include('admin_elements/copyright.php'); ?>
    </div>
    </form>

</div>
<?php include('admin_elements/admin_footer.php'); ?>


