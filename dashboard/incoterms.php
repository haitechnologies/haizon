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
											is_active 		    = '" . $publish . "'
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

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(incoterm, is_active) VALUES ('" . $incoterm . "', '" . $publish . "'); ");

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
    $publish            = s__($row['is_active']);
}



/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
            </div>

            <div class="my-1 d-inline-flex align-items-center me-2">
                <div class="form-check form-check-inline form-switch mb-0">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="publish" id="publish" <?php if ($publish == '1') { ?>checked="checked" <?php } ?> form="frm<?php echo $module; ?>">
                    <label class="form-check-label" for="publish">Publish</label>
                </div>
            </div>

            <div class="my-1">
                <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                    <button type="submit" form="frm<?php echo $module; ?>" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
                <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
                    <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
                    <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
                <?php } else { ?>
                    <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
                <?php } ?>

                <div class="card col-lg-6">

                    <div class="card-body">

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Incoterm:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="incoterm" id="incoterm" value="<?php echo $incoterm; ?>" class="form-control">
                            </div>
                        </div>

                    </div>

                </div>
            </form>
        </div>


        <?php include('admin_elements/copyright.php'); ?>
    </div>

</div>
<?php include('admin_elements/admin_footer.php'); ?>