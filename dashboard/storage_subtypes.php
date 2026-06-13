<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'storage_subtypes';
$module_caption     = 'Storage subtype';
$tbl_name = DB::STORAGE_TYPES;
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
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

if (isset($_POST['publish']))                                 $publish     = 1;
else $publish = 0;


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $storage_subtype            = e_s__($_POST['storage_subtype']);
    $storage_type               = e_s__($_POST['storage_type']);
    $description                = e_s__($_POST['description']);
} else {
    $storage_subtype            = '';
    $storage_type               = '';
    $description                = '';
}

/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {


    if (empty($storage_subtype)) {
        $error_message = 'Storage Subtype is mandatory.';
    } else if (empty($storage_type) || $storage_type == 'Please select') {
        $error_message = 'Storage type is mandatory.';
    } else {
        $parentId = (int)$storage_type;
        $checkDup = $mysqli->prepare("SELECT id FROM `$tbl_name` WHERE name = ? AND parent_id = ? AND organization_id = ? AND id != ? LIMIT 1");
        $checkDup->bind_param('siii', $storage_subtype, $parentId, $activeOrganizationId, $id);
        $checkDup->execute();
        $checkDup->store_result();
        $isDuplicate = $checkDup->num_rows > 1;
        $checkDup->close();

        if ($isDuplicate) {
            $error_message = "Duplicate Storage Subtype in the Same Storage type. $module_caption Could Not Be Updated.";
        } else {
            $activeFlag = $publish ? 1 : 0;
            $stmt = $mysqli->prepare("UPDATE `$tbl_name` SET name = ?, parent_id = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ? AND parent_id IS NOT NULL");
            $stmt->bind_param('sisii', $storage_subtype, $parentId, $description, $activeFlag, $id);
            $update_row = $stmt->execute();
            $stmt->close();

            if ($update_row) {
                $success_message = "The $module_caption has been updated successfully.";
                fp__($tbl_name, $id);
                header("Location:listing_$module.php?success_message=$success_message");
            } else {
                $error_message = "The $module_caption could not be updated. Please try again.";
            }
        }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module" && granted('create', $module_id)) {

    if (empty($storage_subtype)) {
        $error_message = 'Storage Subtype is mandatory.';
    } else if (empty($storage_type) || $storage_type == 'Please select') {
        $error_message = 'Storage type is mandatory.';
    } else {
        $parentId = (int)$storage_type;
        $checkDup = $mysqli->prepare("SELECT id FROM `$tbl_name` WHERE name = ? AND parent_id = ? AND organization_id = ? LIMIT 1");
        $checkDup->bind_param('sii', $storage_subtype, $parentId, $activeOrganizationId);
        $checkDup->execute();
        $checkDup->store_result();
        $isDuplicate = $checkDup->num_rows > 0;
        $checkDup->close();

        if ($isDuplicate) {
            $error_message = "Duplicate Storage Subtype in the Same Storage type. $module_caption Could Not Be Updated.";
        } else {
            $activeFlag = $publish ? 1 : 0;
            $stmt = $mysqli->prepare("INSERT INTO `$tbl_name` (organization_id, parent_id, name, description, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param('iisii', $activeOrganizationId, $parentId, $storage_subtype, $description, $activeFlag);
            $insert_row = $stmt->execute();
            $stmt->close();

            if ($insert_row) {
                $id = $mysqli->insert_id;
                $success_message = "The $module_caption has been saved successfully.";
                fp__($tbl_name, $id);
                header("Location:listing_$module.php?success_message=$success_message");
            } else {
                $error_message = "The $module_caption could not be saved. Please try again.";
            }
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

    $stmt = $mysqli->prepare("SELECT * FROM `$tbl_name` WHERE id = ? AND parent_id IS NOT NULL LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    $storage_subtype            = s__($row['name'] ?? '');
    $storage_type               = (string)($row['parent_id'] ?? '');
    $description                = s__($row['description'] ?? '');
    $publish                    = (int)($row['is_active'] ?? 0);
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

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>

        <!-- Page header -->


                <div class="row">
                    <div class="col-lg-6">

                        <div class="card">

                            <div class="card-body">

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Storage Subtype:*</span></label>

                                    <div class="col-lg-9">
                                        <input required type="text" name="storage_subtype" id="storage_subtype" value="<?php echo $storage_subtype; ?>" class="form-control">
                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Storage type:*</span></label>

                                    <div class="col-lg-9">
                                        <select class="form-select" name="storage_type" id="storage_type">
                                            <!-- <option value="0" selected="">Select</option> -->
                                            <option value='0'>Please select</option>
                                            <?php
                                            $result = $mysqli->query("SELECT * FROM `" . DB::STORAGE_TYPES . "` WHERE is_active=1 AND parent_id IS NULL ORDER BY name");
                                            while ($rows = $result->fetch_array()) {
                                            ?>
                                                <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $storage_type) { ?>selected <?php } else if ($rows['id'] == $storage_type) { ?>selected <?php } ?>>
                                                    <?php echo $rows['name']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Description: </label>

                                    <div class="col-lg-9">
                                        <textarea class="form-control" name="description" id="description" style="field-sizing: content;"><?php echo $description; ?></textarea>
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



<!--
    // ---------------------------------------------------------
    // ENABLE VIEW ONLY MODE FOR FORM ELEMENTS
    // ---------------------------------------------------------
-->
<?php if (isset($module_id) && granted('view', $module_id) && !granted('create', $module_id) && !granted('edit', $module_id)) { ?>
    <script>
        $(function() {
            toggleFormElements('true');
        });
    </script>
<?php } ?>

<?php include('admin_elements/admin_footer.php'); ?>
