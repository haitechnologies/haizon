<?php
declare(strict_types=1);

use App\Core\DB;
include('admin_elements/admin_header.php');
$module = 'lead_quotations';
$module_caption = 'Lead Quotation';
$tbl_name = DB::QUOTATIONS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');
$activeOrganizationId = dashboardRequireActiveOrganization();

$lead_id = isset($_REQUEST['lead_id']) && !empty($_REQUEST['lead_id']) ? e_s__($_REQUEST['lead_id']) : 0;

if (isset($_POST['publish'])) $publish = 1;
else $publish = 0;
?>

<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <?php include('admin_elements/lead_navbar.php'); ?>
            </div>

            <div class="my-1">
                <?php if (empty($hide_add_button) && isset($module_id) && granted('create', $module_id)) { ?>
                    <a href="quotations.php?lead_id=<?php echo $lead_id; ?>" class="btn btn-primary btn-sm d-inline-flex align-items-center">
                        <i class="ph-plus ph-sm me-2 opacity-75"></i>New
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                                <thead>
                                    <tr>
                                        <th width="40">SR.</th>
                                        <th width="130">DATE</th>
                                        <th width="170">QUOTATION #</th>
                                        <th>JOB REFERENCE #</th>
                                        <th>LEAD NAME</th>
                                        <th>STATUS</th>
                                        <th>AMOUNT</th>
                                        <th width="120" class="col-center">ACTIONS</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<script>
$(document).ready(function() {
    window.HAIDatatableInitializer.init('#grid-<?php echo $module; ?>', '<?php echo $module; ?>', {
        columns: [
            { data: 0, orderable: false, searchable: false },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5, orderable: false, searchable: false },
            { data: 6 },
            { data: 7, orderable: false, searchable: false, className: 'col-center' }
        ],
        ajax: {
            data: function(d) {
                d.lead_id = <?php echo (int)$lead_id; ?>;
                return d;
            }
        }
    });
});
</script>

<?php
include('admin_elements/admin_footer.php');
