<?php

include('admin_elements/admin_header.php');

$module             = 'hs_code_texts';
$module_caption     = 'HS Code Text';
$error_message      = '';
$success_message    = '';

$action             = isset($_GET['action']) ? e_s__($_GET['action']) : '';
$id                 = isset($_GET['id']) ? intval($_GET['id']) : '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
    }
}

if ($action == "update_$module" || $action == "add_$module") {
    $hs_code_id         = intval($_POST['hs_code_id'] ?? 0);
    $lang               = e_s__($_POST['lang'] ?? '');
    $short_desc         = e_s__($_POST['short_desc'] ?? '');
    $long_desc          = e_s__($_POST['long_desc'] ?? '');
} else {
    $hs_code_id         = '';
    $lang               = '';
    $short_desc         = '';
    $long_desc          = '';
}

if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {
    if (empty($hs_code_id) || empty($lang) || empty($short_desc)) {
        $error_message = 'HS Code, language and short description are mandatory.';
    } else {
        $update_row = $mysqli->query("
            UPDATE `" . DB::HS_CODE_TEXTS . "` SET
                hs_code_id      = '" . $hs_code_id . "',
                lang            = '" . $lang . "',
                short_desc      = '" . $short_desc . "',
                long_desc       = '" . $long_desc . "',
                updated_at      = NOW()
            WHERE id=$id");
        
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "Could not update. Please try again.";
        }
    }

} else if ($action == "add_$module" && granted('create', $module_id)) {
    if (empty($hs_code_id) || empty($lang) || empty($short_desc)) {
        $error_message = 'HS Code, language and short description are mandatory.';
    } else {
        $insert_row = $mysqli->query("INSERT INTO `" . DB::HS_CODE_TEXTS . "`
            (hs_code_id, lang, short_desc, long_desc) 
            VALUES 
            ('" . $hs_code_id . "', '" . $lang . "', '" . $short_desc . "', '" . $long_desc . "')");
        
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
    $result = $mysqli->query("SELECT * FROM `" . DB::HS_CODE_TEXTS . "` WHERE id=$id");
    $row = $result ? $result->fetch_array() : null;

    if ($row) {
        $hs_code_id         = s__($row['hs_code_id'] ?? '');
        $lang               = s__($row['lang'] ?? '');
        $short_desc         = s__($row['short_desc'] ?? '');
        $long_desc          = s__($row['long_desc'] ?? '');
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

                <div class="collapse d-lg-block ms-lg-auto">
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
                            <label class="col-lg-3 col-form-label"><span class="text-danger">HS Code:*</span></label>
                            <div class="col-lg-9">
                                <select class="form-select" name="hs_code_id" id="hs_code_id" required>
                                    <option value="">-- Select HS Code --</option>
                                    <?php
                                    $hs_result = $mysqli->query("SELECT id, code FROM `" . DB::HS_CODES . "` ORDER BY code LIMIT 5000");
                                    while ($hs_row = $hs_result->fetch_assoc()) {
                                        $selected = ($hs_code_id == $hs_row['id']) ? 'selected' : '';
                                        echo '<option value="' . $hs_row['id'] . '" ' . $selected . '>' . $hs_row['code'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Language:*</span></label>
                            <div class="col-lg-9">
                                <select class="form-select" name="lang" id="lang" required>
                                    <option value="">-- Select Language --</option>
                                    <option value="en" <?php if ($lang == 'en') echo 'selected'; ?>>English</option>
                                    <option value="ar" <?php if ($lang == 'ar') echo 'selected'; ?>>العربية (Arabic)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Short Description:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="short_desc" id="short_desc" placeholder="Brief description" value="<?php echo $short_desc; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Long Description:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="long_desc" id="long_desc" placeholder="Detailed description" rows="6"><?php echo $long_desc; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
