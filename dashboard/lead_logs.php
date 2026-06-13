<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module                 = 'lead_logs';
$module_caption         = 'Lead Activity';
$tbl_name = DB::ENTITY_LOGS;

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

// print_r($_REQUEST);

if (!granted_('view', 'leads') && !granted_('create', 'leads') && !granted_('edit', 'leads') && !granted_('delete', 'leads')) {
    $error_message = 'You do not have permission to view this page.';
}

/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

if (isset($_POST['publish']))                                 $publish     = 1;
else $publish = 0;

$lead_id = '';
if (isset($_REQUEST['lead_id']))        $lead_id     = e_s__($_REQUEST['lead_id']);
if (isset($_POST['lead_id']))           $lead_id     = e_s__($_POST['lead_id']);


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>


<style>
    .timeline {
        position: relative;
        padding: 0;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 50%;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #ddd;
        transform: translateX(-50%);
    }

    .timeline-item {
        display: flex;
        align-items: flex-start;
        position: relative;
        margin-bottom: 30px;
    }

    .timeline-date {
        width: 45%;
        text-align: right;
        padding-right: 20px;
        font-weight: 600;
        color: #555;
    }

    .timeline-marker {
        position: relative;
        z-index: 1;
        background: #fff;
        border: 2px solid #0d6efd;
        border-radius: 50%;
        width: 14px;
        height: 14px;
        margin: 0 10px;
        flex-shrink: 0;
        top: 5px;
    }

    .timeline-content {
        width: 45%;
        background: #fff;
        border: 1px solid #eee;
        border-radius: 6px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
</style>

<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content d-lg-flex border-top carriers-page-header-content py-2 px-3 carriers-page-header-content">
            <div class="row mt-2">
                <div class="col-lg-12">
                    <?php include('admin_elements/lead_navbar.php'); ?>
                </div>

                <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                    <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                </a>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="row">

                <div class="col-lg-6">

                    <div class="card">
                        <div class="card-header d-flex">
                            <h5 class="mb-0">
                                <i class="ph-folder me-2"></i>
                                Lead Activity
                            </h5>

                            <div class="ms-auto">
                                <span class="text-muted">
                                    <?php
                                    // ----------------------------------------------------------------
                                    $result = $mysqli->query("SELECT id FROM `" . DB::ENTITY_LOGS . "` WHERE entity_type='lead' AND entity_id=$lead_id");
                                    echo '(' . $result->num_rows . ')';
                                    // ----------------------------------------------------------------
                                    ?>
                                </span>
                            </div>
                        </div>


                        <div class="list-feed p-3">

                            <?php
                            // ======================================================
                            $result = $mysqli->query("SELECT * FROM `" . DB::ENTITY_LOGS . "` WHERE entity_type='lead' AND entity_id=$lead_id ORDER BY id ASC");
                            while ($rows = $result->fetch_array()) {
                                $note_id = $rows['id'];
                                // ======================================================
                            ?>

                                <!-- <div class="list-feed-item border-info">
                                    <div class="text-muted fs-sm mb-1"><?php //echo date("d F Y h:i", strtotime($rows['created_at'])); 
                                                                        ?></div>
                                    <?php //echo ucwords($rows['module']); 
                                    ?> has been <?php //echo $rows['action']; 
                                                ?>.
                                </div> -->

                                <div class="timeline-item">
                                    <div class="timeline-date">
                                        <?php echo date("d F Y", strtotime($rows['created_at'])); ?><br>
                                        <small class="text-muted"><?php echo dd__($rows['created_at']); ?></small>
                                    </div>
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <!-- <h6 class="mb-1">Purchase Order updated</h6> -->
                                        <!-- <p class="mb-1 small text-muted">Items received for Purchase Order PO-00002</p> -->
                                        <h6 class="mb-1">
                                            <?php echo ucwords($rows['module']); ?> has been <?php echo $rows['action']; ?>.<br />


                                            <!-- <a href="">View Details <?php echo $rows['record_id']; ?></a> -->


                                        </h6>
                                        <!-- <p class="mb-1 small text-muted">Items received for Purchase Order PO-00002</p> -->
                                        <!-- <small class="text-muted"> -->
                                        <!-- by AlFeneeq Medical  -->
                                        <!-- - <a href="#">View Details</a> -->
                                        <!-- </small> -->
                                    </div>
                                </div>

                            <?php } // while 
                            ?>

                            <!-- <div class="list-feed-item border-warning">
                                        <div class="text-muted fs-sm mb-1">12 minutes ago</div>
                                        All sellers have received payouts for December!
                                    </div> -->
                        </div>

                    </div>

                </div>

                <!-- <div class="col-lg-6"> -->

                <!-- <div class="card"> -->
                <!-- <div class="container py-5">
                    <div class="timeline">

                        <div class="timeline-item">
                            <div class="timeline-date">
                                13 Aug 2025<br><small class="text-muted">05:40 PM</small>
                            </div>
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <p class="mb-1">asdf</p>
                                <small class="text-muted">by AlFeneeq Medical</small>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-date">
                                13 Aug 2025<br><small class="text-muted">05:40 PM</small>
                            </div>
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <p class="mb-1">ftest</p>
                                <small class="text-muted">by AlFeneeq Medical</small>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-date">
                                15 Mar 2025<br><small class="text-muted">12:49 AM</small>
                            </div>
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Purchase Order updated</h6>
                                <p class="mb-1 small text-muted">Items received for Purchase Order PO-00002</p>
                                <small class="text-muted">by AlFeneeq Medical - <a href="#">View Details</a></small>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-date">
                                15 Mar 2025<br><small class="text-muted">12:48 AM</small>
                            </div>
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Payments Made added</h6>
                                <p class="mb-1 small text-muted">Payment of amount 7,500.00 made and applied for 111</p>
                                <small class="text-muted">by AlFeneeq Medical - <a href="#">View Details</a></small>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-date">
                                15 Mar 2025<br><small class="text-muted">12:48 AM</small>
                            </div>
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Bill added</h6>
                                <p class="mb-1 small text-muted">Purchase Order of amount 7,500.00 converted as bill 111</p>
                                <small class="text-muted">by AlFeneeq Medical - <a href="#">View Details</a></small>
                            </div>
                        </div>

                    </div>
                </div> -->
                <!-- </div> -->
                <!-- </div> -->


            </div>
        </div>


    </div>


    <?php include('admin_elements/copyright.php'); ?>
</div>

</div>

<?php include('admin_elements/admin_footer.php'); ?>