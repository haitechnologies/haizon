<?php

include('admin_elements/admin_header.php');

$module             = 'category_hs_codes';
$module_caption     = 'Category HS Code';
$error_message      = '';
$success_message    = '';

$action             = isset($_GET['action']) ? e_s__($_GET['action']) : '';
$id                 = isset($_GET['id']) ? intval($_GET['id']) : '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in ' . __FILE__, 'WARNING', __FILE__, __LINE__);
    }
}

if ($action == "update_$module" || $action == "add_$module") {
    $category_id        = intval($_POST['category_id'] ?? 0);
    $hs_code_id         = intval($_POST['hs_code_id'] ?? 0);
    $relevance          = intval($_POST['relevance'] ?? 0);
    $notes              = e_s__($_POST['notes'] ?? '');
} else {
    $category_id        = '';
    $hs_code_id         = '';
    $relevance          = '';
    $notes              = '';
}

if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {
    if (empty($category_id) || empty($hs_code_id)) {
        $error_message = 'Category ID and HS Code ID are mandatory.';
    } else {
        $update_row = $mysqli->query("
            UPDATE `" . DB::CATEGORY_HS_CODES . "` SET
                category_id     = '" . $category_id . "',
                hs_code_id      = '" . $hs_code_id . "',
                relevance       = '" . $relevance . "',
                notes           = '" . $notes . "',
                updated_at      = NOW()
            WHERE id=$id");
        
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
        }
    }

} else if ($action == "add_$module" && granted('create', $module_id)) {
    if (empty($category_id) || empty($hs_code_id)) {
        $error_message = 'Category ID and HS Code ID are mandatory.';
    } else {
        $insert_row = $mysqli->query("INSERT INTO `" . DB::CATEGORY_HS_CODES . "`
            (category_id, hs_code_id, relevance, notes) 
            VALUES 
            ('" . $category_id . "', '" . $hs_code_id . "', '" . $relevance . "', '" . $notes . "')");
        
        if ($insert_row) {
            $success_message = "The $module_caption has been saved successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "Could not save. Please try again.";
        }
    }
}

if (!empty($id)) {
    $result = $mysqli->query("SELECT * FROM `" . DB::CATEGORY_HS_CODES . "` WHERE id=$id");
    $row = $result ? $result->fetch_array() : null;

    if ($row) {
        $category_id        = s__($row['category_id'] ?? '');
        $hs_code_id         = s__($row['hs_code_id'] ?? '');
        $relevance          = s__($row['relevance'] ?? '');
        $notes              = s__($row['notes'] ?? '');
    }
}

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

        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="row mt-3">
                    <div class="col-lg-12">
                        <h5 class="ms-2"><?php echo (($action == "edit_$module" || $action == "update_$module") && !empty($id)) ? 'Edit' : 'New'; ?> <?php echo $module_caption; ?></h5>
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

        <div class="content-inner">
            <div class="content">
                <?php include('admin_elements/breadcrumb.php'); ?>

                <div class="card">
                    <div class="card-body">
                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Category:*</span></label>
                            <div class="col-lg-9">
                                <select class="form-select" name="category_id" id="category_id" required>
                                    <option value="">-- Select Category --</option>
                                    <?php
                                    $cat_result = $mysqli->query("SELECT id, name FROM `" . DB::CATEGORIES . "` WHERE is_active=1 ORDER BY name");
                                    while ($cat_row = $cat_result->fetch_assoc()) {
                                        $selected = ($category_id == $cat_row['id']) ? 'selected' : '';
                                        echo '<option value="' . $cat_row['id'] . '" ' . $selected . '>' . $cat_row['name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">HS Code:*</span></label>
                            <div class="col-lg-9">
                                <select class="form-select" name="hs_code_id" id="hs_code_id" required>
                                    <option value="">-- Select HS Code --</option>
                                    <?php
                                    $hs_result = $mysqli->query("SELECT id, code FROM `" . DB::HS_CODES . "` WHERE is_active=1 ORDER BY code LIMIT 1000");
                                    while ($hs_row = $hs_result->fetch_assoc()) {
                                        $selected = ($hs_code_id == $hs_row['id']) ? 'selected' : '';
                                        echo '<option value="' . $hs_row['id'] . '" ' . $selected . '>' . $hs_row['code'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Relevance:</label>
                            <div class="col-lg-9">
                                <select class="form-select" name="relevance" id="relevance">
                                    <option value="0" <?php if ($relevance == 0) echo 'selected'; ?>>-- Select Level --</option>
                                    <option value="1" <?php if ($relevance == 1) echo 'selected'; ?>>High</option>
                                    <option value="2" <?php if ($relevance == 2) echo 'selected'; ?>>Medium</option>
                                    <option value="3" <?php if ($relevance == 3) echo 'selected'; ?>>Low</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Notes:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="notes" id="notes" placeholder="Additional notes" rows="4"><?php echo $notes; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
