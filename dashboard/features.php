<?php

use App\Core\DB;
include('admin_elements/admin_header.php');

$module = 'statistics';
$module_caption = 'Statistics';
$tbl_name = $tbl_prefix . $module;
$error_message = '';
$success_message = '';
/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/


///////////////////////////////////////////////////////////////////////////////
?>

<!-- <link rel="stylesheet" href="https://acme.invoicehome.com/assets/www-f1dd69f10e1cb8f798cebf0ba38f721a7e21fe33e56eb8c73cf270707c7295f8.css" /> -->

<!-- Main content -->
<div class="content-wrapper">

	<!-- Page header -->
	<div class="page-header page-header-light shadow carriers-page-header">
		<div class="page-header-content d-lg-flex border-top carriers-page-header-content py-2 px-3 carriers-page-header-content">
			<div class="d-flex">
				<div class="breadcrumb py-2">
					<a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
					<a href="index.php" class="breadcrumb-item">All-in-One Solution</a>
				</div>
			</div>

		</div>
	</div>
	<!-- /page header -->



	<?php if (granted_('view', '___')) { ?>
		<!-- Inner content -->
		<div class="content-inner">

			<!-- Content area -->
			<div class="content">

				<?php include('admin_elements/breadcrumb.php'); ?>


				<!-- <div class="navbar navbar-expand-lg shadow rounded py-1 mb-3">
					<div class="container-fluid">
						<div class="text-center d-lg-none">
							<button type="button" class="navbar-toggler dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#navbar-filter">
								<i class="ph-funnel me-2"></i>
								Filters
							</button>
						</div>

						<div class="navbar-collapse collapse order-2 order-lg-1" id="navbar-filter">
							<span class="navbar-text d-none d-lg-inline-flex align-items-lg=center me-3">
								<i class="ph-funnel me-2"></i>
								Filter:
							</span>

							<ul class="navbar-nav flex-wrap mt-2 mt-lg-0">
								<li class="nav-item dropdown">
									<a href="#" class="navbar-nav-link dropdown-toggle rounded" data-bs-toggle="dropdown" aria-expanded="false">
										By date
									</a>

									<div class="dropdown-menu">
										<a href="#" class="dropdown-item">Show all</a>
										<div class="dropdown-divider"></div>
										<a href="#" class="dropdown-item">Today</a>
										<a href="#" class="dropdown-item">Yesterday</a>
										<a href="#" class="dropdown-item">This week</a>
										<a href="#" class="dropdown-item">This month</a>
										<a href="#" class="dropdown-item">This year</a>
									</div>
								</li>

								<li class="nav-item dropdown ms-lg-1">
									<a href="#" class="navbar-nav-link dropdown-toggle rounded" data-bs-toggle="dropdown" aria-expanded="false">
										By status
									</a>

									<div class="dropdown-menu">
										<a href="#" class="dropdown-item">Show all</a>
										<div class="dropdown-divider"></div>
										<a href="#" class="dropdown-item">Open</a>
										<a href="#" class="dropdown-item">On hold</a>
										<a href="#" class="dropdown-item">Resolved</a>
										<a href="#" class="dropdown-item">Closed</a>
										<a href="#" class="dropdown-item">Duplicate</a>
										<a href="#" class="dropdown-item">Invalid</a>
										<a href="#" class="dropdown-item">Wontfix</a>
									</div>
								</li>

								<li class="nav-item dropdown ms-lg-1">
									<a href="#" class="navbar-nav-link dropdown-toggle rounded" data-bs-toggle="dropdown" aria-expanded="false">
										By priority
									</a>

									<div class="dropdown-menu">
										<a href="#" class="dropdown-item">Show all</a>
										<div class="dropdown-divider"></div>
										<a href="#" class="dropdown-item">Highest</a>
										<a href="#" class="dropdown-item">High</a>
										<a href="#" class="dropdown-item">Normal</a>
										<a href="#" class="dropdown-item">Low</a>
									</div>
								</li>
							</ul>
						</div>

						<div class="d-flex order-1 order-lg-2 ms-auto">
							<span class="navbar-text d-none d-lg-inline-flex align-items-lg-center me-3 ms-lg-auto">
								<i class="ph-eye me-2"></i>
								View mode:
							</span>

							<ul class="navbar-nav flex-row">
								<li class="nav-item">
									<a href="#" class="navbar-nav-link navbar-nav-link-icon active rounded">
										<i class="ph-squares-four"></i>
									</a>
								</li>

								<li class="nav-item ms-1">
									<a href="#" class="navbar-nav-link navbar-nav-link-icon rounded">
										<i class="ph-list"></i>
									</a>
								</li>
							</ul>
						</div>
					</div>
				</div> -->



				<div class="content">

					<div class="text-center ">
						<!-- <h1>Orange Ticks Sofware (Ver 2.0.7) - Complete Solution for Maintenance / Technical Companies</h1> -->
						<h1>
							<?php
							//echo getTableAttr('company_name', tbl_global_settings, 1); 
							$software_name		= getTableAttrv('setting_value', DB::SYSTEM_SETTINGS, 'setting_slug ="software_name"');
							echo s__($software_name);
							?>
						</h1>
						<!-- <h3 class="fw-normal h5 mb-5">Manage your Home Maintenance Company in one-window operations</h3> -->
					</div>

					<div class="row">
						<div class="col-lg-4">
							<div class="card">
								<div class="card-body text-center">
									<div class="d-inline-flex bg-success bg-opacity-10 text-success rounded-pill p-2 mb-3 mt-1">
										<i class="ph-lifebuoy ph-2x m-1"></i>
									</div>
									<h5 class="card-title">Shipping & Logistics System</h5>
									<p class="mb-3">Smart system for managing shipping, logistics, and deliveries.</p>
									<!-- <a href="#" class="btn btn-success mb-1">View</a> -->
								</div>
							</div>
						</div>

						<div class="col-lg-4">
							<div class="card">
								<div class="card-body text-center">
									<div class="d-inline-flex bg-warning bg-opacity-10 text-warning rounded-pill p-2 mb-3 mt-1">
										<i class="ph-money ph-2x m-1"></i>
									</div>
									<h5 class="card-title">Accounting System</h5>
									<p class="mb-3">Efficient system for managing business finance and accounting.</p>
									<!-- <a href="#" class="btn btn-warning mb-1">Open a ticket</a> -->
								</div>
							</div>
						</div>

						<div class="col-lg-4">
							<div class="card">
								<div class="card-body text-center">
									<div class="d-inline-flex bg-primary bg-opacity-10 text-primary rounded-pill p-2 mb-3 mt-1">
										<i class="ph-newspaper ph-2x m-1"></i>
									</div>
									<h5 class="card-title">CRM</h5>
									<p class="mb-3">Powerful CRM to manage leads, customers, and sales pipeline.</p>
									<!-- <a href="#" class="btn btn-primary mb-1">Browse news</a> -->
								</div>
							</div>
						</div>
					</div>


					<!-- <div class="row">
						<div class="col-lg-4">
							<div class="card">
								<div class="card-body d-flex align-items-start">
									<div class="bg-success bg-opacity-10 lh-1 rounded-pill p-2 me-3">
										<i class="ph-check"></i>
									</div>

									<div class="flex-fill">
										<h6 class="fw-semibold mb-1"><a href="#" class="text-body">Shipping & Logistics System</a></h6>
										Smart system for managing shipping, logistics, and deliveries.
									</div>
								</div>
							</div>
						</div>

						<div class="col-lg-4">
							<div class="card">
								<div class="card-body d-flex align-items-start">
									<div class="bg-success bg-opacity-10 lh-1 rounded-pill p-2 me-3">
										<i class="ph-check"></i>
									</div>

									<div class="flex-fill">
										<h6 class="fw-semibold mb-1"><a href="#" class="text-body">Accounting System</a></h6>
										Efficient system for managing business finance and accounting.
									</div>
								</div>
							</div>
						</div>

						<div class="col-lg-4">
							<div class="card">
								<div class="card-body d-flex align-items-start">
									<div class="bg-success bg-opacity-10 lh-1 rounded-pill p-2 me-3">
										<i class="ph-check"></i>
									</div>

									<div class="flex-fill">
										<h6 class="fw-semibold mb-1"><a href="#" class="text-body">CRM</a></h6>
										Powerful CRM to manage leads, customers, and sales pipeline.
									</div>
								</div>
							</div>
						</div>
					</div> -->

					<div class="row">
						<div class="col-lg-2">
						</div>

						<div class="col-lg-4">
							<div class="card">
								<div class="card-body text-center">
									<div class="d-inline-flex bg-primary bg-opacity-10 text-primary rounded-pill p-2 mb-3 mt-1">
										<i class="ph-newspaper ph-2x m-1"></i>
									</div>
									<h5 class="card-title">HR</h5>
									<p class="mb-3">Streamlined HR system for employee records and payroll.</p>
									<!-- <a href="#" class="btn btn-primary mb-1">Browse news</a> -->
								</div>
							</div>
						</div>

						<!-- <div class="col-lg-4">
							<div class="card">
								<div class="card-body d-flex align-items-start">
									<div class="bg-success bg-opacity-10 lh-1 rounded-pill p-2 me-3">
										<i class="ph-check"></i>
									</div>

									<div class="flex-fill">
										<h6 class="fw-semibold mb-1"><a href="#" class="text-body">HR</a></h6>
										Streamlined HR system for employee records and payroll.
									</div>
								</div>
							</div>
						</div> -->

						<!-- <div class="col-lg-4">
							<div class="card">
								<div class="card-body d-flex align-items-start">
									<div class="bg-success bg-opacity-10 lh-1 rounded-pill p-2 me-3">
										<i class="ph-check"></i>
									</div>

									<div class="flex-fill">
										<h6 class="fw-semibold mb-1"><a href="#" class="text-body">Network Payments International</a></h6>
										Global payment gateway for secure online transactions.
									</div>
								</div>
							</div>
						</div> -->

						<div class="col-lg-4">
							<div class="card">
								<div class="card-body text-center">
									<div class="d-inline-flex bg-primary bg-opacity-10 text-primary rounded-pill p-2 mb-3 mt-1">
										<i class="ph-newspaper ph-2x m-1"></i>
									</div>
									<h5 class="card-title">Online Paymnents International</h5>
									<p class="mb-3">Global payment gateway for secure online transactions.</p>
									<!-- <a href="#" class="btn btn-primary mb-1">Browse news</a> -->
								</div>
							</div>
						</div>

					</div>

					<div class="container px-sm-5">
						<div class="px-lg-5 col-md-7 col-xl-5 mx-auto">
							<div class="row pb-4 mx-4">
								<a class="btn btn-lg btn-primary shadow" rel="nofollow" href="index.php">Go to Dashboard!</a>
							</div>
						</div>
					</div>

					<div class="row">

						<div class="col-12 col-md-12">

							<!-- <div class="text-center "> -->
							<!-- <h1>Some of the Features</h1> -->
							<!-- <h3 class="fw-normal h5 mb-5">Some of the Features</h3> -->
							<!-- </div> -->

							<!-- <div class="features text-start">

								<div class="row">

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-simplicity"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Invoicing Simplicity</h3>
													<p>Straightforward work-flow and intuitive design. 1-click to create, 1-click to send.</p>
												</div>
											</li>
										</ul>
									</div>

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-logos"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Use Logos</h3>
													<p>Customize invoices with your logo and promote your business.</p>
												</div>
											</li>
										</ul>
									</div>

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-copy"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Copy Invoices</h3>
													<p>Quickly and easily create a new invoice by copying an existing one.</p>
												</div>
											</li>
										</ul>
									</div>

								</div>

								<div class="row">

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-numbering"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Auto Numbering</h3>
													<p>Based on the initial invoice number, the next invoice is numbered automatically.</p>
												</div>
											</li>
										</ul>
									</div>

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-taxes"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Multiple Taxes</h3>
													<p>Use as many different taxes as you need within a single invoice.</p>
												</div>
											</li>
										</ul>
									</div>

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-forms"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Multiple Forms</h3>
													<p>Basic and advanced invoice forms, seamless "on the fly" switching between forms.</p>
												</div>
											</li>
										</ul>
									</div>

								</div>

								<div class="row">

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-email"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Send via E-mail</h3>
													<p>Just two clicks to send an invoice with an online payment link via e-mail.</p>
												</div>
											</li>
										</ul>

									</div>

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-printer"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Printer Friendly</h3>
													<p>Standard invoice design, PDF format compatible with browser or direct printing.</p>
												</div>
											</li>
										</ul>
									</div>

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-online"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Online Payment</h3>
													<p>Customers can pay your invoice online via PayPal or Credit Card.</p>
												</div>
											</li>
										</ul>
									</div>

								</div>

								<div class="row">

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-records"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Payment Records</h3>
													<p>When using online payments, invoices are automatically marked as paid.</p>
												</div>
											</li>
										</ul>
									</div>

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-archive"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Downloadable Archive</h3>
													<p>Download all your invoices in one click, easily forward the archive to your accountant.</p>
												</div>
											</li>
										</ul>
									</div>

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-backup"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Automatic Backup</h3>
													<p>We do care about your data, regular backup of all data is provided automatically.</p>
												</div>
											</li>
										</ul>
									</div>

								</div>

								<div class="row">

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-storage"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Unlimited Storage</h3>
													<p>You can have as many invoices and clients as you want, no limit.</p>
												</div>
											</li>
										</ul>
									</div>

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-100"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">100+ Symbols</h3>
													<p>Worldwide customers? Don't worry. We support graphic symbols for most currencies, even rare ones.</p>
												</div>
											</li>
										</ul>
									</div>

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-formatting"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Currency Formatting</h3>
													<p>In addition to the amount of symbols, easily switch between symbol and ISO; $100 or 100 USD.</p>
												</div>
											</li>
										</ul>
									</div>

								</div>

								<div class="row">

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-secure"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Secure Data</h3>
													<p>All data communication on our servers is encrypted. We pass the latest PCI compliance data security standards.</p>
												</div>
											</li>
										</ul>
									</div>

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-gateways"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Payment Gateways</h3>
													<p>We support <a href="https://www.paypal.com">PayPal</a>, <a href="http://www.authorize.net/">Authorize.Net</a> and <a href="https://stripe.com/">Stripe</a> payment gateways. More on the way.</p>
												</div>
											</li>
										</ul>
									</div>

									<div class="col-lg-4">
										<ul class="list-unstyled mb-0">
											<li>
												<div class="icons icon-support"></div>
												<div class="d-table-cell p-2">
													<h3 class="h5 mb-1">Personal Support</h3>
													<p>We are here for you. If you have any questions, don't hesitate to contact us. We will be happy to help you.</p>
												</div>
											</li>
										</ul>
									</div>

								</div>

							</div> -->




						</div>
					</div>





					<!-- Submit a ticket -->
					<!-- <div class="card card-body">
						<div class="d-flex align-items-center align-items-lg-start flex-column flex-lg-row">
							<div class="bg-success bg-opacity-10 text-success lh-1 rounded-pill p-2 me-lg-3 mb-3 mb-lg-0">
								<i class="ph-file-search"></i>
							</div>

							<div class="flex-fill text-center text-lg-start">
								<h6 class="mb-0">Can't find what you're looking for?</h6>
								<span class="text-muted">Maladroit forgetfully under until the fraternally on one much whispered waked much cumulatively some rabidly after thanks hey</span>
							</div>

							<a href="#" class="btn btn-success align-self-lg-center ms-lg-3 mt-3 mt-lg-0">
								<i class="ph-chat me-2"></i>
								Submit a ticket
							</a>
						</div>
					</div> -->
					<!-- /submit a ticket -->

				</div>



			</div>
			<!-- /content area -->


			<?php include('admin_elements/copyright.php'); ?>


		</div>
	<?php } // permissions 
	?>
	<!-- /inner content -->

</div>
<!-- /main content -->

</div>
<!-- /page content -->
<?php include('admin_elements/admin_footer.php'); ?>