<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module = 'leads';
$module_caption = 'Lead';
$tbl_name = DB::LEADS;
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
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {

    if (is_SuperAdmin()) {

        // // --- Delete Quotations
        // $mysqli->query("DELETE FROM `" . DB::QUOTATION_ITEMS . "` WHERE quotation_id=$id");
        // $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id ");


        // --- Delete Lead Notes (entity_notes)
        $mysqli->query("DELETE FROM `" . DB::ENTITY_NOTES . "` WHERE entity_type='lead' AND entity_id=$id");


        // --- Delete Lead Attachments
        $result = $mysqli->query("SELECT * FROM `" . DB::LEAD_ATTACHMENTS . "` WHERE lead_id=$id");

        while ($rows = $result->fetch_array()) {

            $attachment_id          = $rows['id'];
            $attachment_filename    = $rows['attachment_filename'];

            unlink('../uploads/lead_attachments/' . $attachment_filename);
            $mysqli->query("DELETE FROM `" . DB::LEAD_ATTACHMENTS . "` WHERE id=$attachment_id");
        } // while


        // --- Delete Lead Activity Log (entity_logs)
        $mysqli->query("DELETE FROM `" . DB::ENTITY_LOGS . "` WHERE entity_type='lead' AND entity_id=$id");


        // --- Delete Lead
        $mysqli->query("DELETE FROM `" . DB::LEADS . "` WHERE id=$id");
    } else {


        // --- Delete Lead Notes (entity_notes)
        $mysqli->query("DELETE FROM `" . DB::ENTITY_NOTES . "` WHERE entity_type='lead' AND entity_id=$id AND created_by ='" . $session_user_id . "'");


        // --- Delete Lead Attachments
        $result = $mysqli->query("SELECT * FROM `" . DB::LEAD_ATTACHMENTS . "` WHERE lead_id=$id AND created_by ='" . $session_user_id . "'");

        while ($rows = $result->fetch_array()) {

            $attachment_id          = $rows['id'];
            $attachment_filename    = $rows['attachment_filename'];

            unlink('../uploads/lead_attachments/' . $attachment_filename);
            $mysqli->query("DELETE FROM `" . DB::LEAD_ATTACHMENTS . "` WHERE id=$attachment_id");
        } // while


        // --- Delete Lead Activity Log (entity_logs)
        $mysqli->query("DELETE FROM `" . DB::ENTITY_LOGS . "` WHERE entity_type='lead' AND entity_id=$id AND created_by ='" . $session_user_id . "'");


        // --- Delete Lead
        $mysqli->query("DELETE FROM `" . DB::LEADS . "` WHERE id=$id AND created_by ='" . $session_user_id . "'");
    }


    if ($mysqli->affected_rows > 0) {
        $success_message = "Item deleted successfully.";
        header("Location:listing_$module.php?success_message=$success_message");
    } else {
        $error_message = "Action denied. You are not authorized to delete this record.";
    }
}

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
?>

<div class="content-wrapper">

    <!-- Page header -->
    <?php include('admin_elements/page_header.php'); ?>
    <!-- /page header -->


    <div class="content datatable-enhanced">

        <?php include('admin_elements/breadcrumb.php'); ?>

        <div class="row">

            <div class="row mb-2 mt-2">
                <div class="col-lg-12">

                    <?php
                    // ------------------------------------------------------------------------------------------------
                    $result = $mysqli->query("SELECT * FROM `" . DB::SETUP_STATUSES . "` WHERE publish=1 AND status_type='leads' ORDER BY status LIMIT 50");
                    while ($rows = $result->fetch_array()) {
                        $status = $rows['id'];
                        // ------------------------------------------------------------------------------------------------
                    ?>
                        <?php
                        // ======================================================
                        $rs = $mysqli->query("SELECT id FROM `" . DB::LEADS . "` WHERE lead_status=$status");
                        // echo $rs->num_rows;
                        // ======================================================
                        ?>
                        <span class="badge bg-light text-dark">
                            <a href="listing_leads.php?lead_status=<?php echo $status; ?>" class="text-black fw-normal"><?php echo $rows['status']; ?> (<?php echo $rs->num_rows; ?>)</a>
                        </span>

                    <?php } // while 
                    ?>

                </div>
            </div>

        </div>


        <div class="card">
            <div class="card-body">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                    <thead>
                        <tr>
                            <th width="40">SR.</th>
                            <th>COMPANY NAME</th>
                            <th>ADDRESS</th>
                            <th>EMAIL</th>
                            <th>PHONE</th>
                            <th>TAGS</th>
                            <th>ASSIGNED</th>
                            <th width="100" class="col-center">STATUS</th>
                            <th width="100">LAST CONTACT</th>
                            <th width="90">CREATED</th>
                            <th width="90" class="col-center">ACTIONS</th>
                        </tr>
                    </thead>
                </table>
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
            { data: 5 },
            { data: 6 },
            { data: 7, className: 'col-center' },
            { data: 8 },
            { data: 9 },
            { data: 10, orderable: false, searchable: false, className: 'col-center' }
        ],
        order: [[9, 'desc']],
        pageLength: 25,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: { search: '', searchPlaceholder: 'Search leads...', lengthMenu: '_MENU_' }
    });
    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();
        var id = $(this).data('id'), module = $(this).data('module');
        if (!confirm('Are you sure you want to delete this record?')) return;
        $('<form method="POST">').append(
            $('<input type="hidden" name="action">').val('delete_' + module),
            $('<input type="hidden" name="id">').val(id)
        ).appendTo('body').submit();
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>