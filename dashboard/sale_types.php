<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'sale_types';
$module_caption     = 'Sale Type';
$tbl_name = DB::DOCUMENT_TYPES;
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

if (isset($_POST['publish']))       $publish     = 1;
else $publish = 0;


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $sale_type              = e_s__($_POST['sale_type']);
    $description            = e_s__($_POST['description']);
} else {
    $sale_type              = '';
    $description            = '';
}


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {

    if (empty($sale_type)) {
        $error_message = 'Sale type is mandatory.';
    } else {
        $checkDup = $mysqli->prepare("SELECT id FROM `$tbl_name` WHERE name = ? AND context = 'sale' AND organization_id = ? AND id != ? LIMIT 1");
        $checkDup->bind_param('sii', $sale_type, $activeOrganizationId, $id);
        $checkDup->execute();
        $checkDup->store_result();
        $isDuplicate = $checkDup->num_rows > 0;
        $checkDup->close();

        if ($isDuplicate) {
            $error_message = 'Duplicate Sale type. Please enter different.';
        } else {
            $activeFlag = $publish ? 1 : 0;
            $stmt = $mysqli->prepare("UPDATE `$tbl_name` SET name = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ? AND context = 'sale'");
            $stmt->bind_param('ssii', $sale_type, $description, $activeFlag, $id);
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

    if (empty($sale_type)) {
        $error_message = 'Sale type is mandatory.';
    } else {
        $checkDup = $mysqli->prepare("SELECT id FROM `$tbl_name` WHERE name = ? AND context = 'sale' AND organization_id = ? LIMIT 1");
        $checkDup->bind_param('si', $sale_type, $activeOrganizationId);
        $checkDup->execute();
        $checkDup->store_result();
        $isDuplicate = $checkDup->num_rows > 0;
        $checkDup->close();

        if ($isDuplicate) {
            $error_message = 'Sale type already exists. Please enter a different one.';
        } else {
            $activeFlag = $publish ? 1 : 0;
            $stmt = $mysqli->prepare("INSERT INTO `$tbl_name` (organization_id, context, name, description, is_active, created_at, updated_at) VALUES (?, 'sale', ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param('issi', $activeOrganizationId, $sale_type, $description, $activeFlag);
            $insert_row = $stmt->execute();
            $stmt->close();

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
}


/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
|
*/
if (!empty($id)) {

    $stmt = $mysqli->prepare("SELECT * FROM `$tbl_name` WHERE id = ? AND context = 'sale' LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    $sale_type              = s__($row['name'] ?? '');
    $description            = s__($row['description'] ?? '');
    $publish                = (int)($row['is_active'] ?? 0);
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


                <div class="card col-lg-6">

                    <div class="card-body">

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Sale Type:*</span></label>

                            <div class="col-lg-9">
                                <input required type="text" name="sale_type" id="sale_type" value="<?php echo $sale_type; ?>" class="form-control">
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


            <?php include('admin_elements/copyright.php'); ?>
        </div>
    </form>

</div>


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
