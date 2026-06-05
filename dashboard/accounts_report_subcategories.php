<?php


use App\Core\DB;
use App\Security\Roles;
include('admin_elements/admin_header.php');
Roles::requireAdminAccess();

$module             = 'accounts_report_subcategories';
$module_caption     = 'Accounts Report - Subcategories';
$tbl_name = DB::ACCOUNTS_REPORT_SUBCATEGORIES;
$error_message         = '';
$success_message     = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/

if (isset($_POST['is_completed']))       $is_completed     = 1;
else $is_completed = 0;



/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

$category_id = '';
if (isset($_REQUEST['category_id']) && !empty($_REQUEST['category_id']))        $category_id     = e_s__($_REQUEST['category_id']);
if (isset($_POST['category_id']) && !empty($_POST['category_id']))              $category_id     = e_s__($_POST['category_id']);

/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $slug               = e_s__($_POST['slug']);
    $report_name        = e_s__($_POST['report_name']);
    $ordering           = e_s__($_POST['ordering']);
} else {
    $slug               = '';
    $report_name        = '';
    $ordering           = '';
}

/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id)) {


    if (empty($slug)) {
        $error_message = 'Slug is mandatory.';

        // } else if (checkDuplicateRow($tbl_name, 'slug', $slug) && $slug != getTableAttr('slug', $tbl_name, $id)) {
        //     $error_message = 'Duplicate Slug. Please enter different.';

    } else if (empty($report_name)) {
        $error_message = 'Report Name is mandatory.';

        // } else if (checkDuplicateRow($tbl_name, 'report_name', $module) && $module != getTableAttr('report_name', $tbl_name, $id)) {
        //     $error_message = 'Duplicate Report Name. Please enter different.';

    } else {

        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET
                                            is_completed 		    = '" . $is_completed . "',
											slug			        = '" . $slug . "',
											report_name			    = '" . $report_name . "',
											ordering			    = '" . $ordering . "'
										WHERE id=$id AND category_id = $category_id");
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_accounts_report_categories.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
            //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
        }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module" && !empty($category_id)) {

    if (empty($slug)) {
        $error_message = 'Slug is mandatory.';

        // } else if (checkDuplicateRow($tbl_name, 'slug', $slug)) {
        //     $error_message = 'Duplicate Slug. Please enter different.';

    } else if (empty($report_name)) {
        $error_message = 'Report Name is mandatory.';

        // } else if (checkDuplicateRow($tbl_name, 'report_name', $report_name)) {
        //     $error_message = 'Duplicate Report Name. Please enter different.';

    } else {

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(is_completed, category_id, slug, report_name, ordering) VALUES ('" . $is_completed . "', '" . $category_id . "', '" . $slug . "', '" . $report_name . "', '" . $ordering . "'); ");

        if ($insert_row) {
            $id = $mysqli->insert_id;
            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_accounts_report_categories.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be saved. Please try again.";
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

    $is_completed       = s__($row['is_completed']);
    $slug               = s__($row['slug']);
    $report_name        = s__($row['report_name']);
    $ordering           = s__($row['ordering']);
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">


    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" autocomplete="off" action="<?php echo $module; ?>.php">
        <input type="hidden" name="category_id" id="category_id" value="<?php echo $category_id; ?>" />
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

                <div class="p-3 rounded mt-1">
                    <div class="form-check form-check-inline form-switch">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="is_completed" id="is_completed" <?php if ($is_completed == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="sc_r_success">Is Completed?</label>
                    </div>
                </div>

                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <div class="mt-2 mb-2">
                            <button type="submit" class="btn btn-primary btn-sm me-2">Save</button>
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

                <div class="row">
                    <div class="col-lg-6">

                        <div class="card">

                            <div class="card-body">

                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold"><span class="text-danger">Slug:*</span></label>
                                            <input required type="text" name="slug" id="slug" value="<?php echo $slug; ?>" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold"><span class="text-danger">Report Name:*</span></label>
                                            <input required type="text" name="report_name" id="report_name" value="<?php echo $report_name; ?>" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Ordering: </label>
                                            <input required type="text" name="ordering" id="ordering" value="<?php echo $ordering; ?>" class="form-control">
                                        </div>
                                    </div>
                                </div>

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