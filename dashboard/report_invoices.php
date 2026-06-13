<?php

include('admin_elements/admin_header.php');
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$module = 'invoices';
$module_caption = 'Invoice';
$tbl_name = $tbl_prefix . $module;
$error_message = '';
$success_message = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/
include('admin_elements/permissions.php');


$limit 				= 10;
$stages 			= 2;

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


if (isset($_REQUEST['export']) && !empty($_REQUEST['export']))	$export     = e_s__($_REQUEST['export']);
else $export = '';


// ---------------------------------------------------------------------------------------
// --------------------------------- GET MAXIMUM DATE -----------------------------------
// ---------------------------------------------------------------------------------------
$result_max 	= $mysqli->query("SELECT max(invoice_date) FROM `" . tbl_invoices . "`");
$row_max 		= $result_max->fetch_array();
$max_date		= $row_max[0];

if ($max_date == '0000-00-000') $max_date = date('Y-m-d', time());

// ---------------------------------------------------------------------------------------
// --------------------------------- GET MINIMUM DATE -----------------------------------
// ---------------------------------------------------------------------------------------
$result_min 	= $mysqli->query("SELECT min(invoice_date) FROM `" . tbl_invoices . "`");
$row_min 		= $result_min->fetch_array();
$min_date		= $row_min[0];

if ($min_date == '0000-00-000') $min_date = date('Y-m-d', time());
// ---------------------------------------------------------------------------------------



/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
// if ($action == "generate_report") {

$from                  		= (isset($_REQUEST['from']) ? e_s__($_REQUEST['from']) : '');
$date_from                  = (isset($_REQUEST['date_from']) ? e_s__($_REQUEST['date_from']) : '');
$date_to                    = (isset($_REQUEST['date_to']) ? e_s__($_REQUEST['date_to']) : '');
$date_arrival               = (isset($_REQUEST['date_arrival']) ? e_s__($_REQUEST['date_arrival']) : '');
$date_departure             = (isset($_REQUEST['date_departure']) ? e_s__($_REQUEST['date_departure']) : '');
$vehicle_type               = (isset($_REQUEST['vehicle_type']) ? e_s__($_REQUEST['vehicle_type']) : '');
$client_id                  = (isset($_REQUEST['client_id']) ? e_s__($_REQUEST['client_id']) : '');
$agent_id                  	= (isset($_REQUEST['agent_id']) ? e_s__($_REQUEST['agent_id']) : '');
$driver_id                  = (isset($_REQUEST['driver_id']) ? e_s__($_REQUEST['driver_id']) : '');
$invoice_status             = (isset($_REQUEST['invoice_status']) ? e_s__($_REQUEST['invoice_status']) : '');
$driver_assigned            = (isset($_REQUEST['driver_assigned']) ? e_s__($_REQUEST['driver_assigned']) : '');
$admin_id            		= (isset($_REQUEST['admin_id']) ? e_s__($_REQUEST['admin_id']) : '');
// }


/*
|--------------------------------------------------------------------------
| 	TODAY, TOMORROW, YESTERDAY, THIS MONTH, LAST MONTH ETC
|--------------------------------------------------------------------------
|
*/


if ($from == 'yesterday') {
	$date_from 	= date('d-m-Y', strtotime('-1 day'));
	$date_to 	= date('d-m-Y', strtotime('-1 day'));
} else if ($from == 'today') {
	$date_from 	= date('d-m-Y', time());
	$date_to 	= date('d-m-Y', time());
} else if ($from == 'tomorrow') {
	$date_from 	= date('d-m-Y', strtotime('+1 weeks'));
	$date_to 	= date('d-m-Y', strtotime('+1 weeks'));
} else if ($from == 'last_month') {
	$date_from 	= date('d-m-Y', strtotime('first day of last month'));
	$date_to 	= date('d-m-Y', strtotime('last day of last month'));
} else if ($from == 'this_month') {
	$date_from 	= date('d-m-Y', strtotime('first day of this month'));
	$date_to 	= date('d-m-Y', strtotime('last day of this month'));
} else if ($from == 'next_month') {
	$date_from 	= date('d-m-Y', strtotime('first day of next month'));
	$date_to 	= date('d-m-Y', strtotime('last day of next month'));
} else {

	if (preg_match('/year_/', $from)) {
		$year = explode('year_', $from);

		$date_from 	= date('01-01-' . $year[1]);
		$date_to 	= date('31-12-' . $year[1]);
	}
}



/*
|--------------------------------------------------------------------------
| 	PAGINATION
|--------------------------------------------------------------------------
|
*/

if (isset($_GET['page_no']) && !empty($_GET['page_no'])) {
	$page_no            = e_s__($_GET['page_no']);
} else {
	$page_no            = 1;
}


$targetpage			= 'report_invoices.php?action=generate_report&date_from=' . $date_from . '&date_to=' . $date_to . '&date_arrival=' . $date_arrival . '&date_departure=' . $date_departure . '&client_id=' . $client_id . '&agent_id=' . $agent_id . '&invoice_status=' . $invoice_status . '&admin_id=' . $admin_id;



if ($page_no) {
	$start   = ($page_no - 1) * $limit;
} else {
	$start   = 0;
}

// -------------------
$search_query = '';
// -------------------


$date_from 			= processDateDtoY($date_from);
$date_to 			= processDateDtoY($date_to);
$date_arrival 		= processDateDtoY($date_arrival);
$date_departure 	= processDateDtoY($date_departure);

if (!empty($date_from) && validateDate($date_from) && !empty($date_to) && validateDate($date_to)) {
	$search_query .= " AND invoice_date BETWEEN '" . $date_from . "' AND '" . $date_to . "'";
	$min_date = $date_from;
}


if (!empty($date_from) && validateDate($date_from)) {
	$search_query .= " AND invoice_date >= '" . $date_from . "'";
}

if (!empty($date_to) && validateDate($date_from)) {
	$search_query .= " AND invoice_date <= '" . $date_to . "'";
	$max_date = $date_to;
}

if (!empty($date_arrival) && validateDate($date_arrival)) {
	$search_query .= " AND arrival_date_time = '" . $date_arrival . "'";
}

if (!empty($date_departure) && validateDate($date_departure)) {
	$search_query .= " AND departure_date_time = '" . $date_departure . "'";
}

$date_from 			= processDateYtoD($date_from);
$date_to 			= processDateYtoD($date_to);
$date_arrival 		= processDateYtoD($date_arrival);
$date_departure 	= processDateYtoD($date_departure);

// ----------------------------------------------------------------------------------------------------



if (!empty($client_id)) {
	$search_query .= " AND client_id = $client_id";
}

if (!empty($agent_id)) {
	$search_query .= " AND agent_id = $agent_id";
}


if (!empty($vehicle_type)) {
	$search_query .= " AND id IN (SELECT invoice_id FROM " . tbl_invoice_items . " WHERE vehicle_type = $vehicle_type) ";
}

if (!empty($driver_assigned)) {

	if ($driver_assigned == 'yes') {
		$search_query .= " AND id IN (SELECT invoice_id FROM " . tbl_trips . " WHERE driver_id > 0) ";
	} else {
		$search_query .= " AND id IN (SELECT invoice_id FROM " . tbl_trips . " WHERE driver_id = 0) ";
	}
}


if (!empty($invoice_status)) {
	$search_query .= " AND invoice_status = '" . $invoice_status . "'";
}

if (!empty($admin_id)) {
	$search_query .= " AND created_by = '" . $admin_id . "'";
}


/*
|--------------------------------------------------------------------------
| SEARCH QUERY
|--------------------------------------------------------------------------
|
*/

//COUNT QUERY
// $result = $mysqli->query("SELECT id FROM `" . tbl_invoices . "` WHERE id=0 ");
// $total_pages  = $result->num_rows;

// //NORMAL QUERY
// $result_invoices = $mysqli->query("SELECT * FROM `" . tbl_invoices . "` WHERE id=0 ");


if ($action == "generate_report") {

	//COUNT QUERY
	// echo "SELECT id FROM `" . tbl_invoices . "` WHERE id>0 " . $search_query;
	$result 		= $mysqli->query("SELECT id FROM `" . tbl_invoices . "` WHERE id>0 " . $search_query);
	$total_pages  	= $result->num_rows;

	//NORMAL QUERY
	// echo "SELECT * FROM `" . tbl_invoices . "` WHERE id>0 " . $search_query . " ORDER BY id DESC LIMIT $start, $limit";
	$result_invoices = $mysqli->query("SELECT * FROM `" . tbl_invoices . "` WHERE id>0 " . $search_query . " ORDER BY invoice_date ASC LIMIT $start, $limit");

	//EXPORT EXCEL
	// $result_invoices_ = $mysqli->query("SELECT * FROM `" . tbl_invoices . "` WHERE id>0 " . $search_query . " ORDER BY invoice_date ASC LIMIT $start, $limit");
	$result_invoices_ = $mysqli->query("SELECT * FROM `" . tbl_invoices . "` WHERE id>0 " . $search_query . " ORDER BY invoice_date ASC"); // Remove Limit
}




/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>

<div class="content-wrapper">

	<!-- Page header -->
	<div class="page-header page-header-light shadow carriers-page-header">
		<div class="page-header-content d-lg-flex border-top carriers-page-header-content py-2 px-3 carriers-page-header-content py-2 px-3">
			<div class="d-flex">
				<div class="breadcrumb py-2">
					<a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
					<a href="index.php" class="breadcrumb-item">Home</a>
					<a href="listing_invoices.php" class="breadcrumb-item">Invoices</a>
					<span class="breadcrumb-item active">Generate Report</span>
				</div>

				<a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
					<i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
				</a>
			</div>

			<!-- <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
				<div class="d-lg-flex mb-2 mb-lg-0">
					<button type="button" onclick="window.location.href='index.php';"" class=" btn btn-outline-primary my-1 me-2">Exit</button>
				</div>
			</div> -->

		</div>
	</div>
	<!-- /page header -->


	<div class="content">

		<?php include('admin_elements/breadcrumb.php'); ?>

		<div class="row">
			<div class="col-lg-12">
				<div class="card bg-success bg-opacity-10">
					<!-- <div class="card-header">
                            <h6 class="mb-0">Generate Invoice For:</h6>
                        </div> -->

					<form class="steps-basic clearfix" method="request" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" autocomplete="off" action="report_invoices.php">
						<input type="hidden" name="action" id="action" value="generate_report" />

						<div class="card-body">

							<div class="row">

								<div class="col-lg-2">
									<div class="mb-3">
										<label class="form-label fw-semibold">Date From: </label>

										<div class="form-control-feedback form-control-feedback-start">
											<input type="text" class="form-control" name="date_from" id="date_from" value="<?php echo $date_from; ?>">
											<div class="form-control-feedback-icon">
												<i class="ph-calendar"></i>
											</div>
										</div>

									</div>
								</div>

								<div class="col-lg-2">
									<div class="mb-3">
										<label class="form-label fw-semibold">Date To: </label>

										<div class="form-control-feedback form-control-feedback-start">
											<input type="text" class="form-control" name="date_to" id="date_to" value="<?php echo $date_to; ?>">
											<div class="form-control-feedback-icon">
												<i class="ph-calendar"></i>
											</div>
										</div>

									</div>
								</div>




								<div class="col-lg-2">
									<div class="mb-3">
										<label class="form-label fw-semibold">Client: </label>

										<select name="client_id" id="client_id" class="form-control" onchange="ajax_populate_properties(this.value);">
											<option value='0'>Please select</option>
											<?php

											$client_details = '';

											$result = $mysqli->query("SELECT * FROM `" . tbl_clients  . "` WHERE is_active=1 ORDER BY id DESC");
											while ($rows = $result->fetch_array()) {

												$title                = $rows["title"];
												$first_name           = $rows["first_name"];
												$last_name            = $rows["last_name"];
												$company_name         = $rows["company_name"];
												$company_is_primary   = $rows["company_is_primary"];

												if ($company_is_primary == 1) {
													$client_details =  $company_name . ' - ' . ucwords($title) . ' ' . $first_name . ' ' . $last_name;
												} else {
													$client_details =  ucwords($title) . ' ' . $first_name . ' ' . $last_name . ' - ' . $company_name;
												}
											?>
												<option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $client_id) { ?>selected <?php } else if ($rows['id'] == $client_id) { ?>selected <?php } ?>>
													<?php echo $client_details; ?>
												</option>
											<?php } ?>
										</select>

									</div>
								</div>


								<div class="col-lg-2">
									<div class="mb-3">
										<label class="form-label fw-semibold">Property: </label>

										<select class="form-select" name="property_id" id="property_id">
											<option value='0'>Please select</option>
											<?php
											if (!empty($client_id)) {
												$result_properties = $mysqli->query("SELECT * FROM `" . tbl_properties  . "` WHERE is_active=1 AND client_id=$client_id");
											} else {
												$result_properties = $mysqli->query("SELECT * FROM `" . tbl_properties  . "` WHERE id=0");
											}


											while ($rows_properties = $result_properties->fetch_array()) {

												$street1        = s__($rows_properties['street1']);

												$city            = s__($rows_properties['city']);
												$city           =  getTableAttr('city', tbl_geo_cities, $city);

												$country        = s__($rows_properties['country']);
												$country        =  getTableAttr('country', tbl_geo_countries, $country);
											?>

												<option value="<?php echo $rows_properties['id']; ?>" <?php if ($action == "edit_$module" && $rows_properties['id'] == $property_id) { ?>selected <?php } else if ($rows_properties['id'] == $property_id) { ?>selected <?php } ?>>
													<?php echo $street1; ?> - <?php echo $city; ?> - <?php echo $country; ?>
												</option>

											<?php
											}  // while
											?>
										</select>
									</div>
								</div>

								<div class="col-lg-1">
									<div class="mb-3">
										<label class="form-label fw-semibold">Invoice No: </label>

										<div class="form-control-feedback form-control-feedback-start">
											<input type="text" class="form-control" name="date_arrival2" id="date_arrival2" value="<?php echo $date_arrival; ?>">
										</div>

									</div>
								</div>


								<div class="col-lg-3">
									<div class="mt-3">

										<button type="submit" class="btn btn-success my-1 me-2">Generate Report</button>
										<button type="button" onclick="resetForm();" class="btn btn-light my-1 me-2">Reset</button>

									</div>
								</div>


							</div>

						</div>

					</form>

				</div>
			</div>

		</div>

		<script>
			function resetForm() {
				document.getElementById('date_from').value = '';
				document.getElementById('date_to').value = '';
				document.getElementById('date_arrival').value = '';
				document.getElementById('date_departure').value = '';
				document.getElementById('client_id').value = '0';
				document.getElementById('client_id').text = 'Please select';
				document.getElementById('agent_id').value = '0';
				document.getElementById('agent_id').text = 'Please select';
				document.getElementById('driver_id').value = '0';
				document.getElementById('driver_id').text = 'Please select';
				document.getElementById('vehicle_type').value = '0';
				document.getElementById('vehicle_type').text = 'Please select';
				document.getElementById('driver_assigned').value = '0';
				document.getElementById('driver_assigned').text = 'Please select';
				document.getElementById('invoice_status').value = '0';
				document.getElementById('invoice_status').text = 'Please select';
			}
		</script>


		<?php
		if ($action == 'generate_report') {
		?>

			<div class="card pb-3">
				<div class="card-header d-flex align-items-center">
					<h5 class="mb-0"><?php echo $total_pages; ?> Invoices Found.</h5>

					<div class="ms-auto">

						<!-- <a href="export_pdf.php?<?php echo str_replace('listing_reports.php?', '', $targetpage); ?>&export=pdf" target="_blank">
							<button type="button" class="btn btn-info btn-labeled btn-labeled-start">
								<span class="btn-labeled-icon bg-black bg-opacity-20">
									<i class="ph-file-pdf"></i>
								</span>Export PDF
							</button>
						</a> -->

						<!-- <a href="<?php echo $targetpage; ?>&export=excel">
							<button type="button" class="btn btn-info btn-labeled btn-labeled-start">
								<span class="btn-labeled-icon bg-black bg-opacity-20">
									<i class="ph-file-csv"></i>
								</span>Export Excel
							</button>
						</a> -->

					</div>
				</div>

				<div class="table-responsive">
					<table class="table">

						<thead>
							<tr>
								<th width="100">&nbsp;</th>
								<th width="150">QUOTATION #</th>
								<th width="150">REQUESTED DATE</th>
								<th>COMPANY NAME</th>
								<th width="120">VAT (5%)</th>
								<th width="120">GRAND TOTAL</th>
								<th width="100">STATUS</th>
								<th width="150"></th>
							</tr>
						</thead>


						<tbody>

							<?php

							// ---------------------------------------------------------------------------------------
							while ($row_invoices = $result_invoices->fetch_array(MYSQLI_ASSOC)) {

								$id                   = $row_invoices["id"];
								$invoice_date         = $row_invoices["invoice_date"];
								$invoice_no           = $row_invoices["invoice_no"];
								$client_id            = $row_invoices["client_id"];
								$invoice_status       = $row_invoices["invoice_status"];
								$total_vat            = $row_invoices["total_vat"];
								$grand_total          = $row_invoices["grand_total"];
								$qrcode               = $row_invoices["qrcode"];
								$pdf                  = $row_invoices["pdf"];
								$publish              = $row_invoices["is_active"];
								$subject              = $row_invoices["subject"];
								$created_at           = $row_invoices["created_at"];

								// ---------------------------------------------------------------------------------------
							?>

								<tr>
									<td><a href="invoice_overview.php?action=edit_invoices&id=<?php echo $id; ?>"><a data-lightbox="<?php echo $id; ?>" href="../qrcodes_invoices/' . $qrcode . '.png" target="_blank"><img src="../qrcodes_invoices/' . $qrcode . '.png" width="80" alt="" /></a><br /><small><?php echo $id; ?></small></a></td>


									<td><a href="invoice_overview.php?action=edit_invoices&id=<?php echo $id; ?>"><a href="invoice_overview.php?id=<?php echo $id; ?>" class="text-black"> <?php echo $invoice_no; ?> </a></a></td>
									<td><a href="invoice_overview.php?action=edit_invoices&id=<?php echo $id; ?>"><a href="invoice_overview.php?id=<?php echo $id; ?>" class="text-black"> <?php echo processDateYtoD($invoice_date); ?> </a></a></td>


									<?php 

									$result_client = $mysqli->query("SELECT * FROM `" . tbl_clients . "` WHERE id=$client_id"); // AND is_primary=1
									$row_client = $result_client->fetch_array();
									$title = $row_client["title"];
									$first_name = $row_client["first_name"];
									$last_name = $row_client["last_name"];
									$company_name = $row_client["company_name"];
									$company_is_primary = $row_client["company_is_primary"];

									if ($company_is_primary == 1) {
									$client_details = '<strong>' . $company_name . '</strong><br />' . ucwords($title) . ' ' . $first_name . ' ' . $last_name;
									} else {
									$client_details = '<strong>' . ucwords($title) . ' ' . $first_name . ' ' . $last_name . '</strong><br />' . $company_name;
									}

									?>

									<td><a href="invoice_overview.php?action=edit_invoices&id=<?php echo $id; ?>"><a href="invoice_overview.php?id=<?php echo $id; ?>" class="text-black"> <?php echo $client_details; ?> </a></a></td>

									<td><a href="invoice_overview.php?action=edit_invoices&id=<?php echo $id; ?>"><a href="invoice_overview.php?id=<?php echo $id; ?>" class="text-black"> <?php echo number_format($total_vat, 2); ?> </a></a></td>

									<td><a href="invoice_overview.php?action=edit_invoices&id=<?php echo $id; ?>"><a href="invoice_overview.php?id=<?php echo $id; ?>" class="text-black"> <?php echo number_format($grand_total, 2); ?> </a></a></td>

									<td><a href="invoice_overview.php?action=edit_invoices&id=<?php echo $id; ?>"><a href="invoice_overview.php?id=<?php echo $id; ?>" class="text-black"> <?php echo colorfulInvoiceStatus($invoice_status); ?> </a></a></td>

									<?php $token = hash("sha512", 'bushogai' . $id); ?>

									<td><a href="invoice_overview.php?action=edit_invoices&id=<?php echo $id; ?>"><a href="generate_pdf.php?id=<?php echo $id; ?>&token=<?php echo $token; ?>" target="_blank"> Download </a></a></td>
									<td><a href="invoice_overview.php?action=edit_invoices&id=<?php echo $id; ?>"><a href="pdf_invoice.php?id=<?php echo $id; ?>&token=' . $token; ?>" target="_blank"> <button type="button" class="btn btn-outline-primary"><i class="ph-file-pdf me-2"></i>Download</button> </a></a></td>

								</tr>


							<?php
							} //while
							?>


						</tbody>
					</table>
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

			$lastpage 	= ceil($total_pages / $limit);
			$LastPagem1 = $lastpage - 1;

			$pagination = '';

			if ($lastpage > 1) {
				$pagination .= '<div class="center-block text-center">';
				$pagination .= '<ul class="pagination mb-5 mb-lg-0">';

				// PREVIOUS
				if ($page_no > 1) {
					$pagination	.= '<li class="page-item page-prev"><a class="page-link" href="' . $targetpage . '&page_no=' . $prev . '" tabindex="-1">Prev</a></li>';
				} else {
					$pagination	.= '<li class="page-item page-prev disabled"><a class="page-link" href="#" tabindex="-1">Prev</a></li>';
				}

				// Pages
				if ($lastpage < 7 + ($stages * 2))	// Not enough pages to breaking it up
				{
					for ($counter = 1; $counter <= $lastpage; $counter++) {
						if ($counter == $page_no) {
							$pagination .= '<li class="page-item active"><a class="page-link" href="#">' . $counter . '</a></li>';
						} else {
							$pagination	.= "<li class=\"page-item\"><a class=\"page-link\" href='" . $targetpage . "&page_no=" . $counter . "'>" . $counter . "</a></li>";
						}
					}
				} else if ($lastpage > 5 + ($stages * 2))	// Enough pages to hide a few?
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
		} // endif
?>
</div>
<?php include('admin_elements/copyright.php'); ?>
</div>

<?php include('admin_elements/admin_footer.php'); ?>