<?php

include('admin_elements/admin_header.php');

$module             = 'pages';
$module_caption     = 'Page';
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
        log_error('CSRF token validation failed in pages.php', 'WARNING', __FILE__, __LINE__);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


if (isset($_POST['status']))       $status     = 1;
else $status = 0;

if (isset($_POST['is_main_menu'])) $is_main_menu = 1;
else $is_main_menu = 0;

if (isset($_POST['is_php_page']))  $is_php_page = 1;
else $is_php_page = 0;


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $title              = e_s__($_POST['title']);
    $slug               = e_s__($_POST['slug']);
    $content            = $_POST['content'] ?? ''; // Keep HTML intact
    $menu_caption       = e_s__($_POST['menu_caption']);
    $meta_title         = e_s__($_POST['meta_title']);
    $meta_description   = e_s__($_POST['meta_description']);
    $slider_heading     = e_s__($_POST['slider_heading']);
    $slider_subheading  = e_s__($_POST['slider_subheading']);
} else {
    $title              = '';
    $slug               = '';
    $content            = '';
    $menu_caption       = '';
    $meta_title         = '';
    $meta_description   = '';
    $slider_heading     = '';
    $slider_subheading  = '';
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {

    if (empty($title)) {
        $error_message = 'Page title is mandatory.';
    } else if (empty($content)) {
        $error_message = 'Page content is mandatory.';
    } else {

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET
                                            title           = '" . $title . "',
                                            slug            = '" . $slug . "',
											content         = '" . $mysqli->real_escape_string($content) . "',

											menu_caption    = '" . $menu_caption . "',
											meta_title      = '" . $meta_title . "',
											meta_description = '" . $meta_description . "',
											slider_heading  = '" . $slider_heading . "',
											slider_subheading = '" . $slider_subheading . "',
											is_main_menu    = '" . $is_main_menu . "',
											is_php_page     = '" . $is_php_page . "',
											status          = '" . $status . "',
											updated_by      = '" . $session_user_id . "',
											updated_at      = NOW()
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

    if (empty($title)) {
        $error_message = 'Page title is mandatory.';
    } else if (empty($content)) {
        $error_message = 'Page content is mandatory.';
    } else {

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`
        (title, slug, content, menu_caption, meta_title, meta_description, slider_heading, slider_subheading, is_main_menu, is_php_page, status, created_by) 
        VALUES 
        ('" . $title . "', '" . $slug . "', '" . $mysqli->real_escape_string($content) . "', '" . $menu_caption . "', 
         '" . $meta_title . "', '" . $meta_description . "', '" . $slider_heading . "', '" . $slider_subheading . "', '" . $is_main_menu . "', '" . $is_php_page . "', '" . $status . "', '" . $session_user_id . "')");

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
    $row = $result ? $result->fetch_array() : null;

    $title              = s__($row['title'] ?? '');
    $slug               = s__($row['slug'] ?? '');
    $content            = s__($row['content'] ?? '');

    $menu_caption       = s__($row['menu_caption'] ?? '');
    $meta_title         = s__($row['meta_title'] ?? '');
    $meta_description   = s__($row['meta_description'] ?? '');
    $slider_heading     = s__($row['slider_heading'] ?? '');
    $slider_subheading  = s__($row['slider_subheading'] ?? '');
    $is_main_menu       = s__($row['is_main_menu'] ?? '');
    $is_php_page        = s__($row['is_php_page'] ?? '');
    $status             = s__($row['status'] ?? '');
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
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module" || $action == "change_password") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
            </div>

            <div class="my-1">
                <?php if (empty($id) || (isset($module_id) && granted('create', $module_id)) || (isset($module_id) && granted('edit', $module_id)) || $file === 'profile.php' || $file === 'change_password.php') { ?>
                    <button type="submit" form="frmpages" class="btn btn-primary btn-sm me-2">Save</button>
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
        <?php echo csrf_field(); ?>

        <!-- Page header -->


                <div class="card">

                    <div class="card-body">

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Page Title:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="title" id="title" placeholder="Enter page title" value="<?php echo $title; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Slug (SEO):*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="slug" id="slug" placeholder="Enter slug (e.g., home, about-us)" value="<?php echo $slug; ?>" required>
                                <small class="text-muted">⚠️ IMPORTANT: This slug is used for SEO on the frontend. Preserve it carefully if editing existing pages.</small>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Menu Caption:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="menu_caption" id="menu_caption" placeholder="Menu label (e.g., Home, About)" value="<?php echo $menu_caption; ?>" maxlength="100">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Content:*</span></label>
                            <div class="col-lg-9">
                                <textarea class="form-control editor" name="content" id="content" placeholder="Page content" required><?php echo $content; ?></textarea>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- Slider Section -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">Slider Settings</h6>
                    </div>
                    <div class="card-body">

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Slider Heading:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="slider_heading" id="slider_heading" placeholder="Main heading for slider" value="<?php echo $slider_heading; ?>" maxlength="255">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Slider Subheading:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="slider_subheading" id="slider_subheading" rows="3" placeholder="Subheading for slider"><?php echo $slider_subheading; ?></textarea>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- SEO Section -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">SEO Settings</h6>
                    </div>
                    <div class="card-body">

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Meta Title:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="meta_title" id="meta_title" placeholder="SEO meta title" value="<?php echo $meta_title; ?>" maxlength="255">
                                <small class="text-muted">Recommended: 50-60 characters</small>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Meta Description:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="meta_description" id="meta_description" rows="2" placeholder="SEO meta description"><?php echo $meta_description; ?></textarea>
                                <small class="text-muted">Recommended: 150-160 characters</small>
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
