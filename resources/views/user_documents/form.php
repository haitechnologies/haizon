<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var string $module
 * @var string $moduleCaption
 * @var int $moduleId
 * @var int $session_user_id
 * @var int $session_role_id
 * @var string $error_message
 * @var string $action
 * @var string $user_id
 * @var string $document_type
 * @var string $description
 * @var string $issued_date
 * @var string $expiry_date
 * @var string $document_filename
 * @var array $usersList
 * @var array $documentCategories
 * @var string $uploadPath
 * @var bool $canCreate
 * @var bool $canEdit
 */
include 'admin_elements/admin_header.php';
?>
<div class="content-wrapper">
    <?php  ?>
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1 d-flex align-items-center gap-2">
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> <?php echo $moduleCaption; ?></h5>
            </div>
            <div class="my-1">
                <?php if ($id > 0 ? $canEdit : $canCreate) { ?>
                    <button type="submit" form="frm<?php echo $module; ?>" class="btn btn-primary btn-sm me-2">Save</button>
                    <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_<?php echo $module; ?>">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_<?php echo $module; ?>">
                <?php } ?>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Employee Name: *</span></label>
                                    <div class="col-lg-9">
                                        <select class="form-select" name="user_id" id="user_id">
                                            <option value="0">Please select</option>
                                            <?php foreach ($usersList as $user) { ?>
                                                <option value="<?php echo $user['id']; ?>" <?php echo ((int)$user_id === (int)$user['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Document Category:</label>
                                    <div class="col-lg-9">
                                        <select class="form-select" name="document_type" id="document_type">
                                            <option value="0">Please select</option>
                                            <?php foreach ($documentCategories as $cat) { ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo ((int)$document_type === (int)$cat['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['document_category']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Document Name:</label>
                                    <div class="col-lg-9">
                                        <input type="text" name="document_name" id="document_name" value="<?php echo htmlspecialchars($document_name ?? ''); ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Issued Date:</label>
                                    <div class="col-lg-9">
                                        <div class="form-control-feedback form-control-feedback-start">
                                            <input type="text" class="form-control" name="issued_date" id="issued_date" value="<?php echo htmlspecialchars($issued_date); ?>">
                                            <div class="form-control-feedback-icon">
                                                <i class="ph-calendar"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Expiry Date:</label>
                                    <div class="col-lg-9">
                                        <div class="form-control-feedback form-control-feedback-start">
                                            <input type="text" class="form-control" name="expiry_date" id="expiry_date" value="<?php echo htmlspecialchars($expiry_date); ?>">
                                            <div class="form-control-feedback-icon">
                                                <i class="ph-calendar"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Description:</label>
                                    <div class="col-lg-9">
                                        <textarea class="form-control" name="description" id="description" style="field-sizing: content;"><?php echo htmlspecialchars($description); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <?php if (!empty($document_filename) && $uploadPath && file_exists(dirname(__DIR__, 2) . '/' . $uploadPath . $document_filename)) { ?>
                                        <div class="form-group">
                                            <a href="<?php echo $uploadPath . $document_filename; ?>" target="_blank">
                                                <small><?php echo htmlspecialchars($document_filename); ?></small>
                                            </a>
                                        </div>
                                    <?php } else { ?>
                                        <div class="row mb-3">
                                            <label class="form-label fw-semibold"><span class="text-danger">Document:*</span></label>
                                            <input type="file" name="document" id="document" class="form-control">
                                        </div>
                                        <div class="form-text text-muted">.doc, .docx, .pdf, .txt, .rtf, .xls, .xlsx, .ppt, .pptx, .jpeg, .jpg, .png<br>5MB</div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php include 'admin_elements/copyright.php'; ?>
    </div>
</div>

<?php if (isset($moduleId) && function_exists('granted') && granted('view', $moduleId) && !granted('create', $moduleId) && !granted('edit', $moduleId)) { ?>
    <script>
        $(function() {
            toggleFormElements('true');
        });
    </script>
<?php } ?>

<?php include 'admin_elements/admin_footer.php'; ?>
