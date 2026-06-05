<?php

include('admin_elements/admin_header.php');

$module = 'user_documents';
$module_caption = 'Employee Document';
$tbl_name = DB::USER_DOCUMENTS;
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

$document_expiry_type = 'ALL';


if (isset($_REQUEST['document_expiry_type']) && !empty($_REQUEST['document_expiry_type'])) {

    $document_expiry_type = e_s__($_REQUEST['document_expiry_type']);
    $document_expiry_type = strtoupper(str_ireplace('_', ' ', $document_expiry_type));
}




/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {

    //SUPERADMIN CAN DELETE ANY DATA
    if ($_SESSION[$project_pre]['DASHBOARD']['type'] == 'superadmin') {

        $photo = getTableAttr('photo', $tbl_name, $id);
        $result = $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");

        if (!empty($photo)) {
            delete_photo($photo, $photo_upload_path, '1');   // DELETE OLD THUMB
            delete_photo($photo, $photo_upload_path, '0');    // DELETE OLD PHOTO
        }

        //ADMIN CAN DELETE ONLY HIS/HER DATA
    } else {

        $photo = getTableAttr('photo', $tbl_name, $id);
        $result = $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by='" . $_SESSION[$project_pre]['DASHBOARD']['admin_id'] . "'");

        if (!empty($photo)) {
            delete_photo($photo, $photo_upload_path, '1');   // DELETE OLD THUMB
            delete_photo($photo, $photo_upload_path, '0');    // DELETE OLD PHOTO
        }
    }


    if ($result) {
        $success_message = "$module_caption Deleted Successfully.";
        header("Location:listing_$module.php?success_message=$success_message");
    } else {
        $error_message = "Sorry! $module Could Not Be Deleted.";
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
    <div class="page-header page-header-light shadow">
        <div class="page-header-content d-lg-flex border-top">
            <div class="row mt-2">
                <div class="col-lg-12">
                    <h5 class="ms-2 mb-0"> <a href="listing_<?php echo $module; ?>.php" class="text-dark">Employee Documents </a></h5>
                </div>

                <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                    <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                </a>
            </div>

            <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <button type="button" class="btn btn-primary btn-sm mt-1 mb-1" onclick="window.location.href='<?php echo $module; ?>.php';"><i class="ph-plus ph-sm me-2 opacity-75"></i>New</button>
                    </div>
                </div>
            <?php } ?>

        </div>
    </div>
    <!-- /page header -->


    <div class="content datatable-enhanced">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="row mt-3 mb-3 ">
                <!-- <div class="card"> -->
                <!-- <div class="card-header"> -->
                <div class="d-flex justify-content-between align-items-center">

                    <h5 class="mb-0">&nbsp;</h5>

                    <ul class="nav nav-tabs nav-tabs-solid nav-justified rounded col-lg-6">
                        <li class="nav-item">
                            <a href="listing_<?php echo $module; ?>.php?document_expiry_type=all" class="nav-link rounded-start active ">All</a>
                        </li>
                        <li class="nav-item bg-success">
                            <a href="listing_<?php echo $module; ?>.php?document_expiry_type=up_to_date" class="nav-link text-white ">UP-TO-DATE</a>
                        </li>
                        <li class="nav-item bg-warning">
                            <a href="listing_<?php echo $module; ?>.php?document_expiry_type=near_expiry" class="nav-link text-white ">NEAR EXPIRY</a>
                        </li>
                        <li class="nav-item bg-danger">
                            <a href="listing_<?php echo $module; ?>.php?document_expiry_type=expired" class="nav-link text-white ">EXPIRED</a>
                        </li>
                    </ul>
                </div>
                <!-- </div> -->
                <!-- </div> -->
            </div>

            <div class="row">

                <div class="row mb-2 mt-2">
                    <div class="col-lg-12">

                        <?php
                        // ------------------------------------------------------------------------------------------------
                        $result = $mysqli->query("SELECT * FROM `" . tbl_document_categories . "` WHERE publish=1 AND document_category_type='employees' ORDER BY document_category LIMIT 50");
                        while ($rows = $result->fetch_array()) {
                            $document_category = $rows['id'];
                            // ------------------------------------------------------------------------------------------------
                        ?>
                            <?php
                            // ======================================================
                            $rs = $mysqli->query("SELECT id FROM `" . DB::USER_DOCUMENTS . "` WHERE document_category=$document_category");
                            // echo $rs->num_rows;
                            // ======================================================
                            ?>
                            <span class="badge bg-light text-dark fw-normal"><?php echo $rows['document_category']; ?> (<?php echo $rs->num_rows; ?>)</span>

                        <?php } // while 
                        ?>

                    </div>
                </div>

            </div>

            <!-- <h2>User Documents</h2> -->

            <div class="card">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                        <thead>
                            <tr>
                                <th width="40">SR.</th>
                                <th>DOCUMENT NAME</th>
                                <th>CATEGORY</th>
                                <th>EMPLOYEE NAME</th>
                                <th>DOCUMENT</th>
                                <th>ISSUE DATE</th>
                                <th>EXPIRY DATE</th>
                                <th width="90">CREATED AT</th>
                                <th width="90">ACTIONS</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

<?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<script>
$(document).ready(function() {
    window.HAIDatatableInitializer.init('#grid-<?php echo $module; ?>', '<?php echo $module; ?>', {
        columns: [
            { data: 0, orderable: false },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 },
            { data: 7 },
            { data: 8, className: 'text-center' }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: { search: '', searchPlaceholder: 'Search documents...', lengthMenu: '_MENU_' }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>