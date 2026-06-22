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
 * @var int $lead_id
 * @var string $attachment_name
 * @var string $attachment_filename
 * @var array $existingAttachments
 * @var int $existingCount
 * @var string $uploadPath
 * @var bool $canCreate
 * @var bool $canEdit
 * @var bool $canDelete
 */
include 'admin_elements/admin_header.php';
?>
<div class="content-wrapper">
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> <?php echo $moduleCaption; ?></h5>
            </div>
            <div class="my-1">
                <?php if ($id > 0 ? $canEdit : $canCreate) { ?>
                    <button type="submit" form="frm<?php echo $module; ?>" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="lead_id" id="lead_id" value="<?php echo $lead_id; ?>">

                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_<?php echo $module; ?>">
                    <input type="hidden" name="attachment_id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_<?php echo $module; ?>">
                <?php } ?>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> <?php echo $moduleCaption; ?></h6>
                            </div>
                            <div class="content clearfix">
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Attachment Name:</label>
                                    <div class="col-lg-9">
                                        <input type="text" name="attachment_name" id="attachment_name" value="<?php echo htmlspecialchars($attachment_name); ?>" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold"><span class="text-danger">Document:*</span></label>

                                    <?php if (!empty($attachment_filename) && $uploadPath && file_exists(dirname(__DIR__, 2) . '/' . $uploadPath . $attachment_filename)) { ?>
                                        <div class="form-group">
                                            <h5>
                                                <a href="<?php echo $uploadPath . $attachment_filename; ?>" target="_blank">
                                                    <small><?php echo htmlspecialchars($attachment_filename); ?></small>
                                                </a>
                                            </h5>
                                        </div>
                                    <?php } else { ?>
                                        <div class="row mb-3">
                                            <input type="file" name="document" id="document" class="form-control">
                                        </div>
                                        <div class="form-text text-muted">.doc, .docx, .pdf, .txt, .rtf, .xls, .xlsx, .ppt, .pptx, .jpeg, .jpg, .png<br>5MB</div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($lead_id > 0 && !empty($existingAttachments)) { ?>
                    <div class="">
                        <div class="card">
                            <div class="card-header d-flex">
                                <h5 class="mb-0">
                                    <i class="ph-folder me-2"></i>
                                    Attachments
                                </h5>
                                <div class="ms-auto">
                                    <span class="text-muted">(<?php echo $existingCount; ?>)</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php foreach ($existingAttachments as $att) { ?>
                                    <div class="d-flex mb-3">
                                        <div class="me-3">
                                            <div class="bg-success bg-opacity-10 text-success lh-1 rounded-pill p-2">
                                                <i class="ph-file"></i>
                                            </div>
                                        </div>
                                        <div class="flex-fill">
                                            <a href="<?php echo $uploadPath . $att->filename; ?>">
                                                <?php echo htmlspecialchars($att->description ?? $att->originalFilename ?? ''); ?>
                                            </a>

                                            <?php if (isset($moduleId) && function_exists('granted') && granted('edit', $moduleId)) { ?>
                                                <a href="lead_attachments.php?action=edit_lead_attachments&attachment_id=<?php echo $att->id; ?>&lead_id=<?php echo $lead_id; ?>">
                                                    <span class="text-dark opacity-50"><i class="ph-pencil"></i></span>
                                                </a>
                                            <?php } ?>

                                            <?php if (isset($moduleId) && function_exists('granted') && granted('delete', $moduleId)) { ?>
                                                <a href="listing_lead_attachments.php?action=delete_lead_attachments&attachment_id=<?php echo $att->id; ?>&lead_id=<?php echo $lead_id; ?>">
                                                    <span class="text-danger opacity-50"><i class="ph-trash"></i></span>
                                                </a>
                                            <?php } ?>

                                            <a href="<?php echo $uploadPath . $att->filename; ?>">
                                                <small><?php echo htmlspecialchars($att->filename ?? ''); ?></small>
                                            </a>

                                            <div class="text-muted fs-sm"><?php echo htmlspecialchars($att->createdAt ?? ''); ?></div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
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
