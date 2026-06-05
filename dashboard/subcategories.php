<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'subcategories';
$module_caption     = 'Subcategory';
$error_message      = '';
$success_message    = '';

// Get action and ID from query string
$action             = isset($_GET['action']) ? e_s__($_GET['action']) : '';
$id                 = isset($_GET['id']) ? intval($_GET['id']) : '';

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
        log_error('CSRF token validation failed in subcategories.php', 'WARNING', __FILE__, __LINE__);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


if (isset($_POST['is_active']))       $is_active = 1;
else $is_active = 0;


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $category_id        = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $name               = e_s__($_POST['name'] ?? '');
    $slug               = e_s__($_POST['slug'] ?? '');
    $description        = e_s__($_POST['description'] ?? '');
    $is_active          = isset($_POST['is_active']) ? 1 : 0;
} else {
    $category_id        = 0;
    $name               = '';
    $slug               = '';
    $description        = '';
    $is_active          = 0;
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {

    if (empty($category_id)) {
        $error_message = 'Parent category is mandatory.';
    } else if (empty($name)) {
        $error_message = 'Subcategory name is mandatory.';
    } else if (empty($slug)) {
        $error_message = 'Slug is mandatory.';
    } else {

        /* ---------------------- PREPARED STATEMENT UPDATE ---------------------- */
        $stmt = $mysqli->prepare("
            UPDATE `" . DB::SUBCATEGORIES . "` SET
                category_id = ?,
                name = ?,
                slug = ?,
                description = ?,
                is_active = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param(
                "isssiii",
                $category_id,
                $name,
                $slug,
                $description,
                $is_active,
                $session_user_id,
                $id
            );
            
            if ($stmt->execute()) {
                $success_message = "The $module_caption has been updated successfully.";
                fp__(DB::SUBCATEGORIES, $id);
                header("Location:listing_$module.php?success_message=$success_message");
            } else {
                $error_message = "The $module_caption could not be updated. Please try again.";
            }
            $stmt->close();
        } else {
            $error_message = "The $module_caption could not be updated. Database error: " . $mysqli->error;
        }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module" && granted('create', $module_id)) {

    if (empty($category_id)) {
        $error_message = 'Parent category is mandatory.';
    } else if (empty($name)) {
        $error_message = 'Subcategory name is mandatory.';
    } else if (empty($slug)) {
        $error_message = 'Slug is mandatory.';
    } else {

        $insert_stmt = $mysqli->prepare("
            INSERT INTO `" . DB::SUBCATEGORIES . "`
            (category_id, name, slug, description, is_active, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        if ($insert_stmt) {
            $insert_stmt->bind_param(
                "isssii",
                $category_id,
                $name,
                $slug,
                $description,
                $is_active,
                $session_user_id
            );
            
            if ($insert_stmt->execute()) {
                $id = $mysqli->insert_id;
                fp__(DB::SUBCATEGORIES, $id);
                $success_message = "The $module_caption has been saved successfully.";
                header("Location:listing_$module.php?success_message=$success_message");
            } else {
                $error_message = "The $module_caption could not be saved. Please try again.";
            }
            $insert_stmt->close();
        } else {
            $error_message = "The $module_caption could not be saved. Database error: " . $mysqli->error;
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

    $result = $mysqli->query("SELECT * FROM `" . DB::SUBCATEGORIES . "` WHERE id=$id");
    $row = $result->fetch_array();

    if ($row) {
        $category_id        = s__($row['category_id'] ?? '');
        $name               = s__($row['name'] ?? '');
        $slug               = s__($row['slug'] ?? '');
        $description        = s__($row['description'] ?? '');
        $is_active          = s__($row['is_active'] ?? '');
        $is_active            = s__($row['publish'] ?? '');
    } else {
        $error_message = "Subcategory not found.";
        $id = '';
    }
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
                    <div class="form-check form-check-inline form-switch me-3">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php if ($is_active == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="is_active">Active</label>
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

                <div class="card">

                    <div class="card-body">

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Parent Category:*</span></label>
                            <div class="col-lg-9">
                                <select class="form-select" name="category_id" id="category_id" required>
                                    <option value="">-- Select Parent Category --</option>
                                    <?php
                                    $category_result = $mysqli->query("SELECT id, name FROM `" . DB::CATEGORIES . "` WHERE is_active=1 ORDER BY name");
                                    while ($cat_row = $category_result->fetch_assoc()) {
                                        $selected = ($category_id == $cat_row['id']) ? 'selected' : '';
                                        echo '<option value="' . $cat_row['id'] . '" ' . $selected . '>' . $cat_row['name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Subcategory ID:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="subcategory_id" id="subcategory_id" placeholder="Enter unique identifier (e.g., laptops)" value="<?php echo $subcategory_id; ?>" required <?php if (!empty($id)) { ?>readonly<?php } ?>>
                                <small class="text-muted">Unique identifier - cannot be changed after creation</small>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Subcategory Name:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="subcategory" id="subcategory" placeholder="Enter subcategory name" value="<?php echo $subcategory; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Slug:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="slug" id="slug" placeholder="Enter slug (e.g., laptops)" value="<?php echo $slug; ?>" required>
                                <small class="text-muted">URL-friendly identifier</small>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Description:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="description" id="description" rows="3" placeholder="Enter subcategory description"><?php echo $description; ?></textarea>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Icon:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="icon" id="icon" placeholder="Icon class or filename" value="<?php echo $icon; ?>">
                                <small class="text-muted">Font Awesome class (e.g., fa-laptop) or image filename</small>
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
                                <input type="text" class="form-control" name="meta_title" id="meta_title" placeholder="SEO meta title" value="<?php echo $meta_title; ?>" maxlength="60">
                                <small class="text-muted">Recommended: 50-60 characters</small>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Meta Description:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="meta_description" id="meta_description" rows="2" placeholder="SEO meta description" maxlength="160"><?php echo $meta_description; ?></textarea>
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
