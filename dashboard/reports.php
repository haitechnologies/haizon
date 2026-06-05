<?php
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
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();


?>
<?php //include('admin_elements/breadcrumb.php'); 
?>

<!-- Main content -->
<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow">
        <div class="page-header-content d-lg-flex border-top">
            <div class="d-flex">
                <div class="breadcrumb py-2">
                    <a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
                    <a href="index.php" class="breadcrumb-item">Home</a>
                    <a href="#" class="breadcrumb-item"><i class="ph-chart-bar"></i> Reports</a>
                </div>

                <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                    <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                </a>
            </div>

            <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                <div class="d-lg-flex mb-2 mb-lg-0">
                    <a href="#" class="d-flex align-items-center text-body py-2">
                        <i class="ph-clock"></i> &nbsp;
                        <?php echo date('j F Y'); // | H:i a 
                        ?> &nbsp;

                        <div class="digital-clock">00:00:00</div>
                        <style>
                            .digital-clock {
                                margin: auto;
                                padding: 0 10px;
                                color: #ffffff;
                                background: linear-gradient(90deg, #000, #555);
                            }
                        </style>

                        <script>
                            $(document).ready(function() {
                                clockUpdate();
                                setInterval(clockUpdate, 1000);
                            })

                            function clockUpdate() {
                                var date = new Date();
                                $('.digital-clock').css({
                                    'color': '#fff',
                                    'text-shadow': '0 0 6px #ff0'
                                });

                                function addZero(x) {
                                    if (x < 10) {
                                        return x = '0' + x;
                                    } else {
                                        return x;
                                    }
                                }

                                function twelveHour(x) {
                                    if (x > 12) {
                                        return x = x - 12;
                                    } else if (x == 0) {
                                        return x = 12;
                                    } else {
                                        return x;
                                    }
                                }

                                var h = addZero(twelveHour(date.getHours()));
                                var m = addZero(date.getMinutes());
                                var s = addZero(date.getSeconds());

                                $('.digital-clock').text(h + ':' + m + ':' + s)
                            }
                        </script>
                    </a>
                </div>
            </div>

        </div>
    </div>
    <!-- /page header -->

    <!-- Inner content -->
    <div class="content-inner">

        <!-- Content area -->
        <div class="content">

            <!-- Dashboard content -->
            <div class="row">
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header d-flex">
                            <h5 class="mb-0">
                                Business Overview
                            </h5>
                            <div class="ms-auto">
                                <span class="text-muted">(0)</span>
                            </div>
                        </div>
                        <div class="list-group list-group-borderless py-2">



                        </div>
                    </div>

                </div>
                <div class="col-xl-4">

                </div>
                <div class="col-xl-4">

                </div>
            </div>
            <!-- /dashboard content -->

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