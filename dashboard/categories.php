<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'categories';
$module_caption     = 'Category';
$error_message      = '';
$success_message    = '';

// Get action and ID from query string or POST request
$action             = isset($_POST['action']) ? e_s__($_POST['action']) : (isset($_GET['action']) ? e_s__($_GET['action']) : '');
$id                 = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : '');

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
        log_error('CSRF token validation failed in categories.php', 'WARNING', __FILE__, __LINE__);
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
| CHECK FOR UPDATE/CREATE SUCCESS MESSAGE  
|--------------------------------------------------------------------------
| Display success message after redirect from update/create handlers
*/
if (isset($_GET['updated'])) {
    $success_message = "The $module_caption has been updated successfully.";
} elseif (isset($_GET['created'])) {
    $success_message = "The new $module_caption has been added successfully.";
}

/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $category_id        = e_s__($_POST['category_id'] ?? '');
    $name               = e_s__($_POST['name'] ?? '');
    $slug               = e_s__($_POST['slug'] ?? '');
    $description        = e_s__($_POST['description'] ?? '');
    $icon               = e_s__($_POST['icon'] ?? '');
    $meta_title         = e_s__($_POST['meta_title'] ?? '');
    $meta_description   = e_s__($_POST['meta_description'] ?? '');
} else {
    $category_id        = '';
    $name               = '';
    $slug               = '';
    $description        = '';
    $icon               = '';
    $meta_title         = '';
    $meta_description   = '';
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

        /* ---------------------- VALIDATION PASSED - PREPARE STATEMENT ---------------------- */
        $stmt = $mysqli->prepare("
            UPDATE `" . DB::CATEGORIES . "` SET
                name = ?,
                slug = ?,
                description = ?,
                icon = ?,
                meta_title = ?,
                meta_description = ?,
                is_active = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param(
                "sssssssii",
                $name,
                $slug,
                $description,
                $icon,
                $meta_title,
                $meta_description,
                $is_active,
                $session_user_id,
                $id
            );
            
            if ($stmt->execute()) {
                fp__(DB::CATEGORIES, $id);
                header("Location:categories.php?action=edit_$module&id=$id&updated=1");
                exit;
            } else {
                $error_message = "The $module_caption could not be updated. Database error: " . $mysqli->error;
                log_error($mysqli->error, 'ERROR', __FILE__, __LINE__);
            }
            $stmt->close();
        } else {
            $error_message = "The $module_caption could not be updated. Prepare error: " . $mysqli->error;
            log_error($mysqli->error, 'ERROR', __FILE__, __LINE__);
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

        // Insert with prepared statement
        $insert_stmt = $mysqli->prepare("
            INSERT INTO `" . DB::CATEGORIES . "`
            (name, slug, description, icon, meta_title, meta_description, is_active, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($insert_stmt) {
            $insert_stmt->bind_param(
                "sssssssi",
                $name,
                $slug,
                $description,
                $icon,
                $meta_title,
                $meta_description,
                $is_active,
                $session_user_id
            );
            
            if ($insert_stmt->execute()) {
                $id = $mysqli->insert_id;
                fp__(DB::CATEGORIES, $id);
                header("Location:categories.php?action=edit_$module&id=$id&created=1");
                exit;
            } else {
                $error_message = "The $module_caption could not be saved. Database error: " . $mysqli->error;
                log_error($mysqli->error, 'ERROR', __FILE__, __LINE__);
            }
            $insert_stmt->close();
        } else {
            $error_message = "The $module_caption could not be saved. Prepare error: " . $mysqli->error;
            log_error($mysqli->error, 'ERROR', __FILE__, __LINE__);
        }
    }
}


/*
|--------------------------------------------------------------------------
| EDIT - LOAD DATA
|--------------------------------------------------------------------------
|
*/
if (!empty($id)) {
    $stmt = $mysqli->prepare("SELECT * FROM `" . DB::CATEGORIES . "` WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_array();

        if ($row) {
            $category_id        = s__($row['slug'] ?? '');
            $name               = s__($row['name'] ?? '');
            $slug               = s__($row['slug'] ?? '');
            $description        = s__($row['description'] ?? '');
            $icon               = s__($row['icon'] ?? '');
            $meta_title         = s__($row['meta_title'] ?? '');
            $meta_description   = s__($row['meta_description'] ?? '');
            $is_active          = s__($row['is_active'] ?? '');
        } else {
            $error_message = "Category not found.";
            $id = '';
            $category_id = '';
        }
        $stmt->close();
    } else {
        $error_message = "Database error: " . $mysqli->error;
        log_error($mysqli->error, 'ERROR', __FILE__, __LINE__);
    }
}



/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">
    <?php include('admin_elements/messages.php'); ?>

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
            </div>

            <div class="my-1 d-inline-flex align-items-center me-2">
                <div class="form-check form-check-inline form-switch mb-0">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php if ($is_active == '1') { ?>checked="checked" <?php } ?> form="frm<?php echo $module; ?>">
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <div class="my-1">
                <?php if (isset($module_id) && (($action == "edit_$module" && granted('edit', $module_id)) || ($action != "edit_$module" && granted('create', $module_id)))) { ?>
                    <button type="submit" form="frm<?php echo $module; ?>" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <!-- Inner content -->
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

                <div class="card">

                    <div class="card-body">

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Category ID:*</span></label>
                            <div class="col-lg-9">
                                <?php if (!empty($id)) { ?>
                                    <!-- Editing: Show as text, use hidden field for submission -->
                                    <input type="text" class="form-control" disabled placeholder="Enter unique identifier (e.g., electronics)" value="<?php echo htmlspecialchars($category_id); ?>">
                                    <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_id); ?>">
                                    <small class="text-muted">Category ID cannot be changed after creation</small>
                                <?php } else { ?>
                                    <!-- Creating new: Input field -->
                                    <input type="text" class="form-control" name="category_id" id="category_id" placeholder="Enter unique identifier (e.g., electronics)" value="<?php echo htmlspecialchars($category_id); ?>" required>
                                    <small class="text-muted">Unique identifier - cannot be changed after creation</small>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Category Name:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="name" id="name" placeholder="Enter category name" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Slug:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="slug" id="slug" placeholder="Enter slug (e.g., electronics)" value="<?php echo htmlspecialchars($slug); ?>" required>
                                <small class="text-muted">URL-friendly identifier</small>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Description:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="description" id="description" rows="3" placeholder="Enter category description"><?php echo htmlspecialchars($description); ?></textarea>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Icon:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="icon" id="icon" placeholder="Icon class or filename" value="<?php echo htmlspecialchars($icon); ?>">
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
                                <input type="text" class="form-control" name="meta_title" id="meta_title" placeholder="SEO meta title" value="<?php echo htmlspecialchars($meta_title); ?>" maxlength="60">
                                <small class="text-muted">Recommended: 50-60 characters</small>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Meta Description:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="meta_description" id="meta_description" rows="2" placeholder="SEO meta description" maxlength="160"><?php echo htmlspecialchars($meta_description); ?></textarea>
                                <small class="text-muted">Recommended: 150-160 characters</small>
                            </div>
                        </div>

                    </div>
                </div>

            </form>

        </div>

        <?php include('admin_elements/copyright.php'); ?>

    </div>
    <!-- /inner content -->

</div>

<?php include('admin_elements/admin_footer.php'); ?>
