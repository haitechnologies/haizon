<?php

include('admin_elements/admin_header.php');

$module             = 'blog_categories';
$module_caption     = 'Blog Category';
$tbl_name           = $tbl_prefix . $module;
$error_message      = '';
$success_message    = '';


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
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in blog_categories.php', 'WARNING', __FILE__, __LINE__);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


if (isset($_POST['status']))       $status     = 1;
else $status = 0;


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $name       = e_s__($_POST['name']);
    $slug       = e_s__($_POST['slug']);
    $description = e_s__($_POST['description']);
    $meta_title = e_s__($_POST['meta_title']);
    $meta_description = e_s__($_POST['meta_description']);
    $meta_keywords = e_s__($_POST['meta_keywords']);
} else {
    $name       = '';
    $slug       = '';
    $description = '';
    $meta_title = '';
    $meta_description = '';
    $meta_keywords = '';
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {


    if (empty($name)) {
        $error_message = 'Category name is mandatory.';
    } else if (empty($slug)) {
        $error_message = 'Slug is mandatory.';
    } else {

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET
                                            name	        = '" . $name . "',
                                            slug           = '" . $slug . "',
											description	   = '" . $description . "',
											meta_title     = '" . $meta_title . "',
											meta_description = '" . $meta_description . "',
											meta_keywords  = '" . $meta_keywords . "',
											status 		   = '" . $status . "',
											updated_by     = '" . $session_user_id . "',
											updated_at     = NOW()
										WHERE id=$id");
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
        }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module" && granted('create', $module_id)) {

    if (empty($name)) {
        $error_message = 'Category name is mandatory.';
    } else if (empty($slug)) {
        $error_message = 'Slug is mandatory.';
    } else {

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(name, slug, description, meta_title, meta_description, meta_keywords, status, created_by) VALUES ('" . $name . "',  '" . $slug . "', '" . $description . "', '" . $meta_title . "', '" . $meta_description . "', '" . $meta_keywords . "', '" . $status . "', '" . $session_user_id . "'); ");

        if ($insert_row) {
            $id = $mysqli->insert_id;
            fp__($tbl_name, $id);
            $success_message = "The $module_caption has been saved successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be saved. Please try again.";
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

    $name           = s__($row['name']);
    $slug           = s__($row['slug']);
    $description    = s__($row['description']);
    $meta_title     = s__($row['meta_title']);
    $meta_description = s__($row['meta_description']);
    $meta_keywords  = s__($row['meta_keywords']);
    $status         = s__($row['status']);
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
        <?php echo csrf_field(); ?>

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
                        <input type="checkbox" class="form-check-input form-check-input-success" name="status" id="status" <?php if ($status == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="status">Publish</label>
                    </div>
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

        <!-- Inner content -->
        <div class="content-inner">

            <div class="content">

                <?php include('admin_elements/breadcrumb.php'); ?>

                <div class="card col-lg-6">

                    <div class="card-body">

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Category Name:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="name" id="name" placeholder="Enter category name" value="<?php echo $name; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Slug:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="slug" id="slug" placeholder="Enter slug (e.g., blog-category)" value="<?php echo $slug; ?>" required>
                                <small class="text-muted">URL-friendly identifier</small>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Description:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="description" id="description" rows="4" placeholder="Enter category description"><?php echo $description; ?></textarea>
                            </div>
                        </div>

                        <!-- SEO Fields Section -->
                        <div class="border-top pt-4 mt-4">
                            <h6 class="text-primary mb-3"><i class="icon icon-star me-2"></i>SEO Settings</h6>
                            
                            <div class="mb-3 row">
                                <label class="col-lg-3 col-form-label">Meta Title:</label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control" name="meta_title" id="meta_title" placeholder="SEO meta title (recommended: 50-60 chars)" value="<?php echo $meta_title; ?>" maxlength="255">
                                    <small class="text-muted">Leave blank to use category name</small>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-lg-3 col-form-label">Meta Description:</label>
                                <div class="col-lg-9">
                                    <textarea class="form-control" name="meta_description" id="meta_description" rows="2" placeholder="SEO meta description (recommended: 150-160 chars)" maxlength="500"><?php echo $meta_description; ?></textarea>
                                    <small class="text-muted">Leave blank to use category description</small>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-lg-3 col-form-label">Meta Keywords:</label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control" name="meta_keywords" id="meta_keywords" placeholder="keyword1, keyword2, keyword3" value="<?php echo $meta_keywords; ?>" maxlength="500">
                                    <small class="text-muted">Comma-separated keywords (optional)</small>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>
        <!-- /inner content -->

    </form>

    <?php include('admin_elements/copyright.php'); ?>

</div>

<?php include('admin_elements/admin_footer.php'); ?>
