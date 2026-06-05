<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'incoterms';
$module_caption     = 'Incoterm';
$tbl_name = DB::INCOTERMS;
$error_message         = '';
$success_message     = '';


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


if (isset($_POST['publish']))       $publish     = 1;
else $publish = 1;


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $incoterm   = e_s__($_POST['incoterm']);
} else {
    $incoterm   = '';
}


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {


    if (empty($incoterm)) {
        $error_message = 'Incoterm is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'incoterm', $incoterm) && $incoterm != getTableAttr('incoterm', $tbl_name, $id)) {
        $error_message = 'Duplicate Incoterm. Please enter different.';
    } else {

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET
											incoterm	        = '" . $incoterm . "',
											publish 		    = '" . $publish . "'
										WHERE id=$id");
        if ($update_row) {
            $success_message = "Item has been updated successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "Could not update the item. Please try again later.";
            //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
        }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module" && granted('create', $module_id)) {

    if (empty($incoterm)) {
        $error_message = 'Incoterm is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'incoterm', $incoterm)) {
        $error_message = 'Incoterm already exists. Please enter a different one.';
    } else {

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(incoterm, publish) VALUES ('" . $incoterm . "', '" . $publish . "'); ");

        if ($insert_row) {
            $id = $mysqli->insert_id;
            fp__($tbl_name, $id);
            $success_message = "Item has been saved successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "Failed to save the item. Please try again";
            //header("Location:$module.php?error_message=$error_message");
        }
    }
}


/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
|
*/
if (!empty($id)) {

    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();

    $incoterm           = s__($row['incoterm']);
    $publish            = s__($row['publish']);
}



/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">

    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>

        <!-- Page header -->
        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="row mt-3">
                    <div class="col-lg-12">
                        <h5 class="ms-2"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>

                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <div class="mt-2 mb-2">

                            <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                                <button type="submit" class="btn btn-primary btn-sm me-2">Save</button>
                            <?php } ?>

                            <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- /page header -->

        <div class="content-inner">
            <div class="content">

                <?php include('admin_elements/breadcrumb.php'); ?>

                <div class="card col-lg-6">

                    <div class="card-body">

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Incoterm:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="incoterm" id="incoterm" value="<?php echo $incoterm; ?>" class="form-control">
                                <!-- <small class="text-muted ">DXB, SHJ </small> -->
                            </div>
                        </div>

                    </div>

                </div>
            </div>


            <?php include('admin_elements/copyright.php'); ?>
        </div>
    </form>

</div>
<?php include('admin_elements/admin_footer.php'); ?>