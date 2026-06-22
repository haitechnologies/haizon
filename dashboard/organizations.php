<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module = 'organizations';
$module_caption = 'Organization';
$tbl_name = $tbl_prefix . $module;

$photo_upload_path         = '../uploads/' . $module . '/';
$allowed_file_size         = 5; // MB
$allowed_file_formats     = 'jpg, jpeg, png, gif, webp'; // Allowed formats

// Template Sizes
// 500 x 334
// 150 x 100

$image_width            = '400';
$image_height           = '68';

$thumb_width            = '200';
$thumb_height           = '68';

$display_thumb_width    = '100';
$display_thumb_height   = '34';

$error_message = '';
$success_message = '';

if (!function_exists('organizationSlugify')) {
    function organizationSlugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
}

if (!function_exists('generateUniqueOrganizationSlug')) {
    function generateUniqueOrganizationSlug(mysqli $mysqli, string $table, string $warehouseName, string $warehouseNo): string
    {
        $base = organizationSlugify($warehouseName);
        if ($base === '') {
            $base = organizationSlugify($warehouseNo);
        }
        if ($base === '') {
            $base = 'organization';
        }

        $slug = $base;
        $counter = 2;

        while (true) {
            $stmt = $mysqli->prepare("SELECT id FROM `{$table}` WHERE slug = ? LIMIT 1");
            if (!$stmt) {
                return $slug;
            }

            $stmt->bind_param('s', $slug);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$existing) {
                return $slug;
            }

            $slug = $base . '-' . $counter;
            $counter++;
        }
    }
}

if (!function_exists('countOwnedOrganizations')) {
    function countOwnedOrganizations(mysqli $mysqli, string $table, int $ownerUserId): int
    {
        $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM `{$table}` WHERE owner_user_id = ?");
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('i', $ownerUserId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($row['cnt'] ?? 0);
    }
}


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
        log_error('CSRF token validation failed in organizations.php', 'WARNING', __FILE__, __LINE__);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/



/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $warehouse_no           = e_s__($_POST['warehouse_no']);
    $warehouse_name         = e_s__($_POST['warehouse_name']);
    $street1                = e_s__($_POST['street1']);
    $street2                = e_s__($_POST['street2']);
    $country                = e_s__($_POST['country']);
    $state                  = e_s__($_POST['state']);
    $phone                  = e_s__($_POST['phone']);
    $email                  = e_s__($_POST['email']);
    $trn                    = e_s__($_POST['trn']);
} else {
    $warehouse_no           = '';
    $warehouse_name         = '';
    $street1                = '';
    $street2                = '';
    $country                = '';
    $state                  = '';
    $phone                  = '';
    $email                  = '';
    $trn                    = '';
}


/*
|--------------------------------------------------------------------------
| 	DELETE PHOTO ONLY
|--------------------------------------------------------------------------
|
*/
if (isset($_REQUEST['delete_photo']) && $_REQUEST['delete_photo'] == 1 && !empty($id)) {
    $photo = getTableAttr("photo", DB::ORGANIZATIONS, $id);
    if (!empty($photo)) {
        delete_photo($photo, $photo_upload_path, '1');     // DELETE OLD THUMB
        delete_photo($photo, $photo_upload_path, '0');    // DELETE OLD PHOTO

        $result = $mysqli->query("UPDATE `$tbl_name` SET photo='' WHERE id=$id");
        $success_message = 'Image Deleted Successfully.';
    }
}


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {

    if (empty($warehouse_no)) {
        $error_message = 'Organization number is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'warehouse_no', $warehouse_no) && $warehouse_no != getTableAttr('warehouse_no', $tbl_name, $id)) {
        $error_message = 'Duplicate Organization number. Please enter different.';
    } else if (empty($warehouse_name)) {
        $error_message = 'Organization name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'warehouse_name', $warehouse_name) && $warehouse_name != getTableAttr('warehouse_name', $tbl_name, $id)) {
        $error_message = 'Duplicate Organization name. Please enter different.';
    } else {


        /* ---------------------- UPLOAD PHOTO ---------------------- */
        $old_photo = getTableAttr('photo', $tbl_name, $id);

        if (isset($_FILES["photo"]["name"])) {
            $photo = $_FILES["photo"]["name"];
            if (!empty($photo)) {
                // $renamed 				= full_rename($logo, 'haipulse-logo');
                // $renamed_photo 			= renamePhoto($photo, $complete=0);
                $renamed_photo                 = full_rename($photo, $id . '_logo');
                $message                     =
                    uploadNewPhoto('photo', $renamed_photo, $photo_upload_path, $allowed_file_size, $old_photo, $image_width, $image_height, $thumb_width, $thumb_height);
                if ($message)        $error_message = $message;
                else                $result = $mysqli->query("UPDATE `$tbl_name` SET photo='$renamed_photo' WHERE id=$id");
            } //endif
        }

        //////////////////////////////////////////////////
        $update_row = $mysqli->query("
									UPDATE `$tbl_name` SET
										warehouse_no					= '" . $warehouse_no . "',
										warehouse_name					= '" . $warehouse_name . "',
										street1					        = '" . $street1 . "',
										street2					        = '" . $street2 . "',
										country					        = '" . $country . "',
										state					        = '" . $state . "',
										phone			            = '" . $phone . "',
										email			            = '" . $email . "',
										trn			                = '" . $trn . "'
									WHERE id=$id");
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            flash_success($success_message);
            header("Location:listing_$module.php");
            exit;
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
            flash_error($error_message);
            header("Location:$module.php?action=edit_$module&id=$id");
            exit;
        }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module" && granted('create', $module_id)) {

    if (!function_exists('dashboardCanCreateOrganizations') || !dashboardCanCreateOrganizations()) {
        $error_message = 'Your subscription does not allow creating organizations.';
    } else if (empty($warehouse_no)) {
        $error_message = 'Organization number is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'warehouse_no', $warehouse_no)) {
        $error_message = 'Duplicate Organization number. Please enter different.';
    } else if (empty($warehouse_name)) {
        $error_message = 'Organization name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'warehouse_name', $warehouse_name)) {
        $error_message = 'Duplicate Organization name. Please enter different.';
    } else {
        $maxOrganizations = function_exists('dashboardMaxOrganizations') ? dashboardMaxOrganizations() : 0;
        $ownedOrganizations = countOwnedOrganizations($mysqli, $tbl_name, (int)Session::userId());
        if ($maxOrganizations > 0 && $ownedOrganizations >= $maxOrganizations) {
            $error_message = 'Your subscription organization limit has been reached.';
        }
    }

    if (empty($error_message)) {
        $organizationSlug = generateUniqueOrganizationSlug($mysqli, $tbl_name, $warehouse_name, $warehouse_no);


        /* ---------------------- UPLOAD PHOTO ---------------------- */
        $old_photo = getTableAttr('photo', $tbl_name, $id);

        if (isset($_FILES["photo"]["name"])) {
            $photo = $_FILES["photo"]["name"];
            if (!empty($photo)) {
                // $renamed 				= full_rename($logo, 'haipulse-logo');
                // $renamed_photo 			= renamePhoto($photo, $complete=0);
                $renamed_photo                 = full_rename($photo, $id . '_logo');
                $message                     =
                    uploadNewPhoto('photo', $renamed_photo, $photo_upload_path, $allowed_file_size, $old_photo, $image_width, $image_height, $thumb_width, $thumb_height);
                if ($message)        $error_message = $message;
                else                $result = $mysqli->query("UPDATE `$tbl_name` SET photo='$renamed_photo' WHERE id=$id");
            } //endif
        }

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(owner_user_id, warehouse_no, warehouse_name, slug, status, street1, street2, country, state, phone, email, trn) VALUES ('" . (int)Session::userId() . "', '" . $warehouse_no . "', '" . $warehouse_name . "', '" . $organizationSlug . "', 'active', '" . $street1 . "', '" . $street2 . "',  '" . $country . "', '" . $state . "', '" . $phone . "', '" . $email . "', '" . $trn . "'); ");

        if ($insert_row) {
            $id = $mysqli->insert_id;
            dashboardSetActiveOrganization($id);

            $membershipStmt = $mysqli->prepare(
                "INSERT INTO `" . DB::ORGANIZATION_MEMBERSHIPS . "` (organization_id, user_id, membership_status, is_owner, invited_by, joined_at) VALUES (?, ?, 'active', 1, ?, NOW())"
            );
            if ($membershipStmt) {
                $ownerUserId = (int)Session::userId();
                $membershipStmt->bind_param('iii', $id, $ownerUserId, $ownerUserId);
                $membershipStmt->execute();
                $membershipStmt->close();
            }

            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $id);
            flash_success($success_message);
            header("Location:listing_$module.php");
            exit;
            //////////////////////////////////////////////////
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

    $warehouse_no                       = s__($row['warehouse_no']);
    $warehouse_name                     = s__($row['warehouse_name']);
    $street1                            = s__($row['street1']);
    $street2                            = s__($row['street2']);
    $country                            = s__($row['country']);
    $state                              = s__($row['state']);
    $phone                              = s__($row['phone']);
    $email                              = s__($row['email']);
    $trn                                = s__($row['trn']);

}

$photo = getTableAttr('photo', $tbl_name, $id);

/*
|--------------------------------------------------------------------------
| ORGANIZATION DOCUMENT HANDLING (UPLOAD / DELETE)
|--------------------------------------------------------------------------
*/
$documents = [];
if (!empty($id)) {
    $docStmt = $mysqli->prepare("
        SELECT a.id, a.document_category, a.display_name, a.filename, a.original_filename,
               a.file_size, a.description, a.issued_date, a.expiry_date, a.created_at,
               dc.document_category AS category_name
        FROM `" . DB::USER_DOCUMENTS . "` a
        LEFT JOIN `" . DB::DOCUMENT_CATEGORIES . "` dc ON a.document_category = dc.id
        WHERE a.attachable_type = 'OrganizationDoc' AND a.attachable_id = ?
        ORDER BY a.created_at DESC
    ");
    if ($docStmt) {
        $docStmt->bind_param('i', $id);
        $docStmt->execute();
        $docResult = $docStmt->get_result();
        while ($r = $docResult->fetch_assoc()) {
            $documents[] = $r;
        }
        $docStmt->close();
    }
}

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($id) && isset($_POST['action']) && $_POST['action'] === 'org_upload_doc') {
    $docCategory = (int)($_POST['doc_category'] ?? 0);
    $docDesc = e_s__($_POST['doc_description'] ?? '');
    $issuedDate = e_s__($_POST['doc_issued_date'] ?? '');
    $expiryDate = e_s__($_POST['doc_expiry_date'] ?? '');

    if ($docCategory <= 0) {
        flash_error('Please select a document category.');
        header("Location: organizations.php?id=$id&action=edit_organizations");
        exit;
    } elseif (!isset($_FILES['doc_file']) || $_FILES['doc_file']['error'] !== UPLOAD_ERR_OK) {
        flash_error('Please select a file to upload.');
        header("Location: organizations.php?id=$id&action=edit_organizations");
        exit;
    }

    $file = $_FILES['doc_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['doc', 'docx', 'pdf', 'txt', 'xls', 'xlsx', 'ppt', 'pptx', 'jpeg', 'jpg', 'png'];

    if (!in_array($ext, $allowedExts, true)) {
        flash_error('File type not allowed. Allowed: ' . implode(', ', $allowedExts));
        header("Location: organizations.php?id=$id&action=edit_organizations");
        exit;
    } elseif ($file['size'] > 5242880) {
        flash_error('File size must be under 5 MB.');
        header("Location: organizations.php?id=$id&action=edit_organizations");
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/organization_documents/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $filename = 'org_doc_' . $id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        flash_error('Failed to upload document. Please try again.');
        header("Location: organizations.php?id=$id&action=edit_organizations");
        exit;
    }

    $insStmt = $mysqli->prepare("
        INSERT INTO `" . DB::USER_DOCUMENTS . "`
        (organization_id, attachable_type, attachable_id, document_category, display_name,
         filename, original_filename, file_size, description, issued_date, expiry_date,
         created_at, updated_at, created_by)
        VALUES (?, 'OrganizationDoc', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
    ");
    if (!$insStmt) {
        flash_error('Database error. Please try again.');
        header("Location: organizations.php?id=$id&action=edit_organizations");
        exit;
    }

    $displayName = $file['name'];
    $fileSize = $file['size'];
    $insStmt->bind_param(
        'iiississssi',
        $activeOrganizationId, $id, $docCategory, $displayName,
        $filename, $file['name'], $fileSize,
        $docDesc, $issuedDate, $expiryDate,
        (int)Session::userId()
    );
    $insStmt->execute();
    $insStmt->close();
    flash_success('Document uploaded successfully.');
    header("Location: organizations.php?id=$id&action=edit_organizations");
    exit;
}

// Handle document delete
if (!empty($id) && isset($_GET['delete_doc']) && (int)$_GET['delete_doc'] > 0) {
    $docId = (int)$_GET['delete_doc'];
    $selStmt = $mysqli->prepare("SELECT filename FROM `" . DB::USER_DOCUMENTS . "` WHERE id = ? AND attachable_type = 'OrganizationDoc' AND attachable_id = ?");
    if ($selStmt) {
        $selStmt->bind_param('ii', $docId, $id);
        $selStmt->execute();
        $docRow = $selStmt->get_result()->fetch_assoc();
        $selStmt->close();

        if ($docRow) {
            $delStmt = $mysqli->prepare("DELETE FROM `" . DB::USER_DOCUMENTS . "` WHERE id = ? AND attachable_type = 'OrganizationDoc' AND attachable_id = ?");
            if ($delStmt) {
                $delStmt->bind_param('ii', $docId, $id);
                $delStmt->execute();
                $delStmt->close();
            }
            $filePath = __DIR__ . '/../uploads/organization_documents/' . $docRow['filename'];
            if (!empty($docRow['filename']) && file_exists($filePath)) {
                @unlink($filePath);
            }
            flash_success('Document deleted successfully.');
            header("Location: organizations.php?id=$id&action=edit_organizations");
            exit;
        }
    }
    flash_error('Document not found.');
    header("Location: organizations.php?id=$id&action=edit_organizations");
    exit;
}

// Fetch active organization document categories
$orgCategories = [];
$catResult = $mysqli->query("SELECT id, document_category FROM `" . DB::DOCUMENT_CATEGORIES . "` WHERE is_active = 1 AND document_category_type = 'organizations' ORDER BY document_category ASC");
if ($catResult) {
    while ($cr = $catResult->fetch_assoc()) {
        $orgCategories[] = $cr;
    }
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
                    <button type="submit" form="frmorganizations" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <form method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="delete_photo" id="delete_photo" value="0" />
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>
        <?php echo csrf_field(); ?>

        <!-- Page header -->


                <div class="row">
                    <div class="col-lg-6">

                        <div class="card">

                            <div class="card-body">

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Organization number:*</span></label>
                                    <div class="col-lg-9">
                                        <input name="warehouse_no" id="warehouse_no" value="<?php echo $warehouse_no; ?>" class="form-control" type="text" required aria-required="true">
                                        <div class="invalid-feedback">
                                            Organization number is required
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Organization name:*</span></label>
                                    <div class="col-lg-9">
                                        <input name="warehouse_name" id="warehouse_name" value="<?php echo $warehouse_name; ?>" class="form-control" type="text" required aria-required="true">
                                        <div class="invalid-feedback">
                                            Organization name is required
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Street 1: </label>
                                    <div class="col-lg-9">
                                        <input name="street1" id="street1" value="<?php echo $street1; ?>" class="form-control" type="text">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Street 2: </label>
                                    <div class="col-lg-9">
                                        <input name="street2" id="street2" value="<?php echo $street2; ?>" class="form-control" type="text">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Country:</label>
                                    <div class="col-lg-9">
                                        <select required class="form-select select country-selector" name="country" id="country" aria-required="true">
                                            <!-- <option value="0">Please select *</option> -->
                                            <?php echo getUAECountryDropdown($country); ?>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a country
                                        </div>
                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">State:</label>
                                    <div class="col-lg-9">

                                        <select class="form-select" name="state" id="state">
                                            <option value='0'>Please select</option>
                                            <?php echo getUAEStatesDropdown($state); ?>
                                        </select>

                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Phone:</label>
                                    <div class="col-lg-9">
                                        <input name="phone" id="phone" value="<?php echo $phone; ?>" class="form-control" type="text">
                                        <div class="form-text text-muted"><small>+971 50 1234567</small></div>
                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Email: </label>
                                    <div class="col-lg-9">
                                        <input name="email" id="email" value="<?php echo $email; ?>" class="form-control" type="email">
                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">TRN#: </label>
                                    <div class="col-lg-9">
                                        <input name="trn" id="trn" value="<?php echo $trn; ?>" class="form-control" type="text">
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="row mb-3">
                                    <label class="form-label fw-semibold">Organization Logo</label>
                                    <div class="col-lg-9">
                                        <input type="file" name="photo" id="photo" class="form-control">
                                        <div class="form-text text-muted">Size <?php echo $image_width; ?>px x <?php echo $image_height; ?>px -> <?php echo $allowed_file_formats; ?>. Max file size <?php echo $allowed_file_size; ?> Mb</div>
                                    </div>
                                </div>

                                <?php if (!empty($photo) && file_exists('../uploads/organizations/thumbs/' . $photo)) { ?>
                                <div class="row mb-3">
                                    <div class="col-lg-9 offset-lg-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <a data-lightbox="organization" href="<?php echo $photo_upload_path . $photo ?>" target="_blank">
                                                <img src="<?php echo $photo_upload_path . '/thumbs/' . $photo; ?>" alt="" width="<?php echo $display_thumb_width; ?>" height="<?php echo $display_thumb_height; ?>" />
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm delete-photo" name="delete_photo" id="delete_photo">
                                                <i class="ph-trash me-1"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>

                            </div>

                        </div>

                    </div>

                    <div class="col-lg-6">

                        <?php if (!empty($id)): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="ph-files me-2"></i>Organization Documents</h5>
                            </div>
                            <div class="card-body">

                                <?php if (!empty($documents)): ?>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Category</th>
                                                <th>Document</th>
                                                <th>Issue Date</th>
                                                <th>Expiry Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $di = 1; foreach ($documents as $doc): ?>
                                            <tr>
                                                <td><?php echo $di++; ?></td>
                                                <td><?php echo htmlspecialchars($doc['category_name'] ?? '-'); ?></td>
                                                <td>
                                                    <a href="../uploads/organization_documents/<?php echo rawurlencode($doc['filename'] ?? ''); ?>" target="_blank" class="text-info">
                                                        <i class="ph-file"></i> <?php echo htmlspecialchars($doc['original_filename'] ?? $doc['display_name'] ?? 'View'); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo !empty($doc['issued_date']) && $doc['issued_date'] !== '1970-01-01' ? htmlspecialchars($doc['issued_date']) : '-'; ?></td>
                                                <td>
                                                    <?php if (!empty($doc['expiry_date']) && $doc['expiry_date'] !== '1970-01-01'): ?>
                                                        <?php
                                                        $expiryTs = strtotime($doc['expiry_date']);
                                                        $daysLeft = $expiryTs ? ceil(($expiryTs - time()) / 86400) : null;
                                                        $badgeClass = 'bg-secondary';
                                                        if ($daysLeft !== null && $daysLeft <= 0) $badgeClass = 'bg-danger';
                                                        elseif ($daysLeft !== null && $daysLeft <= 7) $badgeClass = 'bg-warning text-dark';
                                                        elseif ($daysLeft !== null && $daysLeft <= 30) $badgeClass = 'bg-info';
                                                        ?>
                                                        <span class="badge <?php echo $badgeClass; ?>">
                                                            <?php echo htmlspecialchars($doc['expiry_date']); ?>
                                                            <?php if ($daysLeft !== null): ?>
                                                                (<?php echo $daysLeft <= 0 ? 'Expired' : "$daysLeft days"; ?>)
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="?id=<?php echo $id; ?>&action=edit_organizations&delete_doc=<?php echo $doc['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this document?');">
                                                        <i class="ph-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>

                                <form method="post" action="organizations.php?id=<?php echo $id; ?>&action=edit_organizations" enctype="multipart/form-data" class="border rounded p-3 bg-light">
                                    <input type="hidden" name="action" value="org_upload_doc">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-lg-4">
                                            <select name="doc_category" class="form-select form-select-sm" required>
                                                <option value="">Category</option>
                                                <?php foreach ($orgCategories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['document_category']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-lg-4">
                                            <input type="file" name="doc_file" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-lg-2">
                                            <input type="date" name="doc_issued_date" class="form-control form-control-sm" placeholder="Issue date">
                                        </div>
                                        <div class="col-lg-2">
                                            <input type="date" name="doc_expiry_date" class="form-control form-control-sm" placeholder="Expiry date">
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-lg-8">
                                            <input type="text" name="doc_description" class="form-control form-control-sm" placeholder="Description (optional)">
                                        </div>
                                        <div class="col-lg-4">
                                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                                <i class="ph-upload"></i> Upload Document
                                            </button>
                                        </div>
                                    </div>
                                </form>

                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>


            <?php include('admin_elements/copyright.php'); ?>
        </div>
    </form>



</div>
<?php include('admin_elements/admin_footer.php'); ?>

