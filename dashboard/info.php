<?php
include('admin_elements/admin_header.php');

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
| Only System Admins can view system info
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

// Check if user is System Admin
if (!Roles::hasFullAccess($session_role_id)) {
	header("HTTP/1.0 403 Forbidden");
	echo "<div class='alert alert-danger'>Access Denied. Only System Administrators can view this information.</div>";
	include('admin_elements/admin_footer.php');
	exit;
}

///////////////////////////////////////////////////////////////////////////////
?>

<!-- Main content -->
<div class="content-wrapper">

		<!-- Inner content -->
		<div class="content-inner">

			<!-- Content area -->
			<div class="content">
                <?php include('admin_elements/breadcrumb.php'); ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Server Information</h5>
                    </div>
                    <div class="card-body">
                        <?php phpinfo(); ?>
                    </div>
                </div>
			</div>
			<!-- /content area -->

			<?php include('admin_elements/copyright.php'); ?>

		</div>
	 
	<!-- /inner content -->

</div>
<!-- /main content -->

</div>
<!-- /page content -->
<?php include('admin_elements/admin_footer.php'); ?>