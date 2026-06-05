<?php

include('admin_elements/admin_header.php');

$module = 'projects';
$module_caption = 'Project';
$tbl_name = DB::PROJECTS;
$error_message = '';
$success_message = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$project_id = '';
if (isset($_REQUEST['id']))        $project_id = e_s__($_REQUEST['id']);
if (isset($_POST['id']))           $project_id = e_s__($_POST['id']);

// ------------------ CHECK IF EXISTS ----------------
$rs_valid = $mysqli->query("SELECT id FROM `" . DB::PROJECTS . "` WHERE id='" . $project_id . "'");
if ($rs_valid->num_rows == 0) {
    header("Location:listing_projects.php?error_message=Invalid Record in the database.");
}

// Load project data with job fields (projects table only stores job/customer links)
$result = $mysqli->query(
    "SELECT p.*, 
            j.job_date AS job_date_from_job,
            j.job_status AS job_status_from_job,
            j.job_seq AS job_seq_from_job,
            j.job_no AS job_no_from_job,
            j.job_ref_no AS job_ref_no_from_job
     FROM `" . DB::PROJECTS . "` p
     LEFT JOIN `" . DB::JOBS . "` j ON p.job_id = j.id
     WHERE p.id=$project_id"
);
$row = $result->fetch_array();

$project_name   = s__($row['project_name'] ?? '');
$job_id         = s__($row['job_id'] ?? '');
$customer_id    = s__($row['customer_id'] ?? '');
$job_date       = s__($row['job_date'] ?? $row['job_date_from_job'] ?? '');
$job_status     = s__($row['job_status'] ?? $row['job_status_from_job'] ?? '');
$job_seq        = s__($row['job_seq'] ?? $row['job_seq_from_job'] ?? '');
$job_no         = s__($row['job_no'] ?? $row['job_no_from_job'] ?? '');
$job_ref_no     = s__($row['job_ref_no'] ?? $row['job_ref_no_from_job'] ?? '');
$created_at     = s__($row['created_at'] ?? '');
$updated_at     = s__($row['updated_at'] ?? '');
$notes          = s__($row['notes'] ?? '');

$customer_name = (!empty($customer_id) ? getTableAttr('display_name', DB::CUSTOMERS, $customer_id) : '');
$job_status_name = (!empty($job_status) ? getTableAttr('job_status', DB::JOB_STATUSES, $job_status) : '');

$job_date = (!empty($job_date) && $job_date !== '1970-01-01') ? processDateYtoD($job_date) : '';
$created_at_display = (!empty($created_at) ? dd__($created_at) : '');
$updated_at_display = (!empty($updated_at) ? dd__($updated_at) : '');

?>

<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow">
        <div class="page-header-content d-lg-flex border-top">
            <div class="row mt-3">
                <div class="col-lg-12">
                    <h5 class="ms-2">Project #<?php echo $project_id; ?><?php echo (!empty($project_name) ? ' - ' . $project_name : ''); ?></h5>
                </div>

                <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                    <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                </a>
            </div>

            <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                <div class="d-lg-flex mb-2 mb-lg-0">
                    <div class="mt-2 mb-2">
                        <div class="row">
                            <div class="col-lg-12 d-flex align-items-center">
                                <?php if (isset($module_id) && granted('edit', $module_id)) { ?>
                                    <a href="projects.php?action=edit_projects&id=<?php echo $project_id; ?>" class="btn btn-primary btn-sm me-2">
                                        Edit
                                    </a>
                                <?php } ?>
                                <a href="listing_projects.php" class="btn btn-light btn-sm">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- /page header -->


    <div class="content-inner">
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Project Details</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-lg-3 text-muted">Project Name</div>
                        <div class="col-lg-9 fw-semibold"><?php echo $project_name; ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-lg-3 text-muted">Project ID</div>
                        <div class="col-lg-9 fw-semibold"><?php echo $project_id; ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-lg-3 text-muted">Customer</div>
                        <div class="col-lg-9 fw-semibold">
                            <?php if (!empty($customer_id)) { ?>
                                <a href="customer_overview.php?customer_id=<?php echo $customer_id; ?>" class="text-black">
                                    <?php echo $customer_name; ?>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-lg-3 text-muted">Job</div>
                        <div class="col-lg-9 fw-semibold">
                            <?php if (!empty($job_id)) { ?>
                                <a href="view_job.php?id=<?php echo $job_id; ?>" class="text-black">Job #<?php echo $job_id; ?></a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-lg-3 text-muted">Job No</div>
                        <div class="col-lg-9 fw-semibold"><?php echo $job_no; ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-lg-3 text-muted">Reference No</div>
                        <div class="col-lg-9 fw-semibold"><?php echo $job_ref_no; ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-lg-3 text-muted">Job Date</div>
                        <div class="col-lg-9 fw-semibold"><?php echo $job_date; ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-lg-3 text-muted">Status</div>
                        <div class="col-lg-9 fw-semibold"><?php echo $job_status_name; ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-lg-3 text-muted">Sequence</div>
                        <div class="col-lg-9 fw-semibold"><?php echo $job_seq; ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-lg-3 text-muted">Created At</div>
                        <div class="col-lg-9 fw-semibold"><?php echo $created_at_display; ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-lg-3 text-muted">Updated At</div>
                        <div class="col-lg-9 fw-semibold"><?php echo $updated_at_display; ?></div>
                    </div>
                    <?php if (!empty($notes)) { ?>
                        <div class="row mb-2">
                            <div class="col-lg-3 text-muted">Notes</div>
                            <div class="col-lg-9 fw-semibold"><?php echo nl2br($notes); ?></div>
                        </div>
                    <?php } ?>
                </div>
            </div>

        </div>

        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
