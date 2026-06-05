<?php

include('admin_elements/admin_header.php');

if (!function_exists('colorfulLeadQuotationStatus')) {
    function colorfulLeadQuotationStatus($status)
    {
        $rawStatus = trim((string)$status);
        $normalized = strtolower($rawStatus);

        $cssClass = 'bg-secondary bg-opacity-10 text-secondary';
        if ($normalized === 'approved' || $normalized === 'confirmed' || $normalized === 'booked' || $normalized === '2') {
            $cssClass = 'bg-success bg-opacity-10 text-success';
        } elseif ($normalized === 'pending' || $normalized === 'draft' || $normalized === '1') {
            $cssClass = 'bg-warning bg-opacity-10 text-warning';
        } elseif ($normalized === 'rejected' || $normalized === 'cancelled' || $normalized === 'void' || $normalized === '0') {
            $cssClass = 'bg-danger bg-opacity-10 text-danger';
        }

        $label = $rawStatus === '' ? 'N/A' : $rawStatus;
        return '<span class="badge ' . $cssClass . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    }
}

$module             = 'quotations';
$module_caption     = 'Quotation';
$tbl_name = DB::QUOTATIONS;
$error_message         = '';
$success_message     = '';


/*
|--------------------------------------------------------------------------
| GENEATE QR CODE AND PDF BOOKING
|--------------------------------------------------------------------------
|
*/
// --- Get From DB where qrcode=''
?>
<!-- <img src="generate_qrcode.php" alt=""> -->
<!-- <img src="generate.php?code=12345" alt=""> -->

<iframe src="generate_quotation_qrcode.php" width="1" height="1"></iframe>

<?php
// --- Get From DB where pdf=''
// include_once('pdf_quotation.php');

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();



// print_r($_REQUEST);

if (isset($_REQUEST['lead_id']) && !empty($_REQUEST['lead_id'])) {
    $lead_id     = e_s__($_REQUEST['lead_id']);
} else {
    $lead_id = 0;
}


/*
|--------------------------------------------------------------------------
| 	PAGINATION
|--------------------------------------------------------------------------
|
*/

$limit              = 25;
$stages             = 2;


if (isset($_GET['page_no']) && !empty($_GET['page_no'])) {
    $page_no            = e_s__($_GET['page_no']);
} else {
    $page_no            = 1;
}

$targetpage = 'listing_lead_quotations.php?lead_id=' . $lead_id;

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





/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($id))) {

    //SUPERADMIN CAN DELETE ANY DATA
    if ($_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1') {

        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND quotation_status!='confirmed'");
        $mysqli->query("DELETE FROM `" . DB::QUOTATION_ITEMS . "` WHERE quotation_id=$id");


        //ADMIN CAN DELETE ONLY HIS/HER DATA
    } else {

        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND quotation_status!='confirmed' AND created_by='" . $_SESSION[$project_pre]['admin_id'] . "'");
        $mysqli->query("DELETE FROM `" . DB::QUOTATION_ITEMS . "` WHERE quotation_id=$id");
    }


    if ($mysqli->affected_rows) {
        $success_message = "$module_caption Deleted Successfully.";
        header("Location:listing_$module.php?id=$lead_id&success_message=$success_message");
    } else {
        $error_message = "Sorry! $module Could Not Be Deleted. Only Super Administrator can delete this record.";
    }
}



// $q_s = getTableAttr('quotation_status', DB::LEAD_QUOTATIONS, $id);
// if ($q_s == 'booked') header("Location:listing_$module.php?error_message=quotation is already booked.");;


if (isset($_POST['publish']))                                 $publish     = 1;
else $publish = 0;


/*
|--------------------------------------------------------------------------
| SEARCH QUERY
|--------------------------------------------------------------------------
|
*/

//COUNT QUERY
$result         = $mysqli->query("SELECT id FROM `" . DB::QUOTATIONS . "` WHERE id>0 AND lead_id=$lead_id ");
$total_pages      = $result->num_rows;

//NORMAL QUERY
$result_lead_quotations = $mysqli->query("SELECT * FROM `" . DB::QUOTATIONS . "` WHERE id>0 AND lead_id=$lead_id ORDER BY id DESC LIMIT $start, $limit");



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
                <div class="row mt-2">
                    <div class="col-lg-12">
                        <?php include('admin_elements/lead_navbar.php'); ?>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>

                <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                    <div class="collapse d-lg-block ms-lg-auto mt-1" id="breadcrumb_elements">
                        <div class="d-lg-flex mb-2 mb-lg-0 mt-1">

                            <button type="button" onclick="window.location.href='<?php echo $module; ?>.php?lead_id=<?php echo $lead_id; ?>';" class=" btn btn-primary btn-sm my-1 me-2"><i class="ph-plus ph-sm me-2 opacity-75"></i>New</button>
                        </div>
                    </div>
                <?php } ?>

            </div>
        </div>
        <!-- /page header -->


        <div class="content-inner">
            <div class="content">

                <?php include('admin_elements/breadcrumb.php'); ?>

                <div class="row">

                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <span class="mb-0">Total Count. <?php echo $total_pages; ?></span>
                            </div>


                            <div class="card-body">

                                <div class="table-responsive">
                                    <table class="table">

                                        <thead>
                                            <tr>
                                                <th width="80">SR.</th>
                                                <th width="130">DATE</th>
                                                <th width="170">QUOTATION #</th>
                                                <th>JOB REFERENCE #</th>
                                                <th>LEAD NAME</th>
                                                <th>STATUS</th>
                                                <th>AMOUNT</th>
                                                <th width="120">ACTION</th>
                                            </tr>
                                        </thead>


                                        <tbody>

                                            <?php

                                            // Calculate serial number start based on page number and limit
                                            $serial_no = ($page_no - 1) * $limit + 1;

                                            // ---------------------------------------------------------------------------------------
                                            while ($row_lead_quotations = $result_lead_quotations->fetch_array(MYSQLI_ASSOC)) {

                                                $id                     = $row_lead_quotations["id"];
                                                $qrcode                 = $row_lead_quotations["qrcode"];

                                                $lead_id                = s__($row_lead_quotations['lead_id']);
                                                $quotation_no           = s__($row_lead_quotations['quotation_no']);
                                                $quotation_date         = s__($row_lead_quotations['quotation_date']);
                                                $expiry_date            = s__($row_lead_quotations['expiry_date']);
                                                $quotation_status       = s__($row_lead_quotations['quotation_status']);
                                                $job_reference_no       = s__($row_lead_quotations['job_reference_no']);

                                                $grand_subtotal         = s__($row_lead_quotations['grand_subtotal']);
                                                $grand_tax              = s__($row_lead_quotations['grand_tax']);
                                                $grand_total            = s__($row_lead_quotations['grand_total']);
                                                $created_at             = s__($row_lead_quotations['created_at']);
                                                $publish                = s__($row_lead_quotations['publish']);

                                                $quotation_date     = processDateYtoD($quotation_date);
                                                $expiry_date        = ($expiry_date == '1970-01-01' ? '' : processDateDtoY($expiry_date));

                                                $created_at     = dd__($created_at);

                                                // ---------------------------------------------------------------------------------------
                                            ?>

                                                <tr>
                                                    <td><a href="quotations.php?action=edit_quotations&id=<?php echo $id; ?>&lead_id=<?php echo $lead_id; ?>"><?php echo $serial_no++; ?></a></td>
                                                    <!-- <td>
                                                        <a data-lightbox="<?php echo $id; ?>" href="../qrcodes_quotations/<?php echo $qrcode; ?>.png" target="_blank">
                                                            <img src="../qrcodes_quotations/<?php echo $qrcode; ?>.png" width="80" alt="" />
                                                        </a>
                                                    </td> -->
                                                    <td><a href="quotations.php?action=edit_quotations&id=<?php echo $id; ?>&lead_id=<?php echo $lead_id; ?>"><?php echo $quotation_date; ?></a></td>
                                                    <td><a href="quotations.php?action=edit_quotations&id=<?php echo $id; ?>&lead_id=<?php echo $lead_id; ?>"><?php echo $quotation_no; ?></a></td>
                                                    <td><a href="quotations.php?action=edit_quotations&id=<?php echo $id; ?>&lead_id=<?php echo $lead_id; ?>"><?php echo $job_reference_no; ?></a></td>
                                                    <td><a href="quotations.php?action=edit_quotations&id=<?php echo $id; ?>&lead_id=<?php echo $lead_id; ?>"><?php echo getTableAttr('display_name', DB::LEADS, $lead_id); ?></a></td>
                                                    <td><a href="quotations.php?action=edit_quotations&id=<?php echo $id; ?>&lead_id=<?php echo $lead_id; ?>"><?php echo colorfulLeadQuotationStatus($quotation_status); ?></a></td>
                                                    <td><a href="quotations.php?action=edit_quotations&id=<?php echo $id; ?>&lead_id=<?php echo $lead_id; ?>">AED<?php echo $grand_total; ?></a></td>

                                                    <td>
                                                        <?php if (isset($module_id) && granted('edit', $module_id)) { ?>
                                                            <a href="quotations.php?action=edit_quotations&id=<?php echo $id; ?>&lead_id=<?php echo $lead_id; ?>">
                                                                <span class="text-dark opacity-50"><i class="ph-pencil"></i></span>
                                                            </a>
                                                        <?php } ?>

                                                        <?php if (isset($module_id) && granted('delete', $module_id)) { ?>
                                                            <a href="listing_quotations.php?action=delete_quotations&id=<?php echo $id; ?>&lead_id=<?php echo $lead_id; ?>">
                                                                <span class="text-danger opacity-50"><i class="ph-trash"></i></span>
                                                            </a>
                                                        <?php } ?>

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


            <?php include('admin_elements/copyright.php'); ?>
        </div>
    </form>

</div>
<?php include('admin_elements/admin_footer.php'); ?>