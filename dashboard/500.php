<?php
http_response_code(500);

// Detect base path based on server environment
$is_local = (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0 || strpos($_SERVER['HTTP_HOST'], '127.0.0.1:') === 0);

if ($is_local) {
    $base_path = '/uaehscodes/dashboard/';
} else {
    $base_path = '/dashboard/';
}

include('admin_elements/admin_header.php');
?>

<div class="content-wrapper">

	<!-- Page header -->
	<div class="page-header page-header-light shadow carriers-page-header">
		<div class="page-header-content d-lg-flex border-top carriers-page-header-content py-2 px-3 carriers-page-header-content">
			<div class="d-flex">
				<div class="breadcrumb py-2">
					<a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
					<a href="index.php" class="breadcrumb-item">Home</a>
					<span class="breadcrumb-item active">500 - Server Error</span>
				</div>
			</div>
		</div>
	</div>
	<!-- /page header -->

	<div class="content-inner">
		<div class="content">

			<!-- Error page -->
			<div class="row">
				<div class="col-lg-12">
					<div class="card">
						<div class="card-body text-center">

							<div class="py-5">
								<!-- Error icon -->
								<div class="mb-4">
									<i class="ph-warning-circle text-danger" style="font-size: 96px;"></i>
								</div>

								<!-- Error message -->
								<h1 class="display-4 fw-semibold mb-3">500</h1>
								<h5 class="mb-3">Internal Server Error</h5>
								<p class="text-muted mb-4">
									We're experiencing technical difficulties. Our team has been notified and is working on resolving the issue. 
									Please try again in a few moments.
								</p>

								<!-- Error details (if available) -->
								<?php if (!empty($_GET['details']) && $_ENV['APP_DEBUG']): ?>
									<div class="alert alert-danger text-start mb-4" style="max-width: 600px; margin: 0 auto;">
										<strong>Error Details:</strong>
										<p class="mb-0" style="font-family: monospace; font-size: 12px;">
											<?php echo htmlspecialchars($_GET['details']); ?>
										</p>
									</div>
								<?php endif; ?>

								<!-- Navigation options -->
								<div class="d-flex justify-content-center gap-2 mb-4">
									<a href="index.php" class="btn btn-primary">
										<i class="ph-house me-2"></i>
										Go to Dashboard
									</a>
									<a href="javascript:history.back()" class="btn btn-light">
										<i class="ph-arrow-left me-2"></i>
										Go Back
									</a>
								</div>

								<!-- Additional help -->
								<div class="text-muted">
									<p class="mb-2">If the problem persists, please contact your system administrator.</p>
									<p class="mb-0">
										<i class="ph-info me-1"></i>
										Error Code: 500 - Internal Server Error
									</p>
									<?php if (!empty($_GET['request_id'])): ?>
										<p class="mb-0">
											<small>Request ID: <code><?php echo htmlspecialchars($_GET['request_id']); ?></code></small>
										</p>
									<?php endif; ?>
								</div>

							</div>

						</div>
					</div>
				</div>
			</div>
			<!-- /error page -->

		</div>

		<?php include('admin_elements/copyright.php'); ?>
	</div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
