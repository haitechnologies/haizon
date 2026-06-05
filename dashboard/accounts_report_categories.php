<?php

include('admin_elements/admin_header.php');
Roles::requireAdminAccess();

$module             = 'accounts_report_categories';
$module_caption     = 'Accounts Report Category';
$tbl_name = DB::ACCOUNTS_REPORT_CATEGORIES;
$error_message         = '';
$success_message     = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/


// print_r($_REQUEST);



/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $category_name      = e_s__($_POST['category_name']);
} else {
    $category_name      = '';
}

/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id)) {


    if (empty($category_name)) {
        $error_message = 'Category name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'category_name', $category_name) && $category_name != getTableAttr('category_name', $tbl_name, $id)) {
        $error_message = 'Duplicate Category name. Please enter different.';
    } else if (empty($module_name)) {
    } else {

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET
											category_name       = '" . $category_name . "'
										WHERE id=$id");

        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
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
} else if ($action == "add_$module") {

    if (empty($category_name)) {
        $error_message = 'Category name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'category_name', $category_name)) {
        $error_message = 'Duplicate Category name. Please enter different.';
    } else {

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(category_name) VALUES ('" . $category_name . "'); ");

        if ($insert_row) {
            $id = $mysqli->insert_id;
            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
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

    $category_name               = s__($row['category_name']);
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">


    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" autocomplete="off" action="<?php echo $module; ?>.php">
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

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Category name:*</span> </label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="category_name" id="category_name" value="<?php echo $category_name; ?>" class="form-control">
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