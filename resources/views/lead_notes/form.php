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
 * @var string $success_message
 * @var int $lead_id
 * @var string $notes
 * @var int $noteId
 * @var string $action
 * @var array $allNotes
 * @var array $userNames
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
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($lead_id)): ?>Edit<?php else: ?>New<?php endif; ?> <?php echo $moduleCaption; ?></h5>
            </div>

            <div class="my-1">
                <?php if ($canCreate || $canEdit): ?>
                    <button type="submit" form="frmlead_notes" class="btn btn-primary btn-sm me-2">Save</button>
                <?php endif; ?>
                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>

    <div class="content-inner">
        <div class="content">
            <?php include('admin_elements/breadcrumb.php'); ?>

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" name="lead_id" id="lead_id" value="<?php echo $lead_id; ?>" />

                <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($lead_id)): ?>
                    <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
                    <input type="hidden" name="note_id" id="note_id" value="<?php echo $noteId; ?>" />
                <?php else: ?>
                    <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
                <?php endif; ?>
                <?php echo csrf_field(); ?>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($lead_id)): ?>Edit<?php else: ?>New<?php endif; ?> <?php echo $moduleCaption; ?></h6>
                            </div>

                            <div class="card-body">
                                <?php
                                $field = ['name' => 'notes', 'label' => 'Notes', 'required' => true, 'value' => $notes, 'extra_attr' => 'style="field-sizing: content;"'];
                                include 'admin_elements/form_field_textarea.php';
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="">
                        <div class="card">
                            <div class="card-header d-flex">
                                <h5 class="mb-0">
                                    <i class="ph-folder me-2"></i>
                                    Notes
                                </h5>
                                <div class="ms-auto">
                                    <span class="text-muted">
                                        (<?php echo count($allNotes); ?>)
                                    </span>
                                </div>
                            </div>

                            <div class="p-3">
                                <?php foreach ($allNotes as $note): ?>
                                    <?php $noteIdVal = $note->id; ?>
                                    <?php $userName = $userNames[$note->createdBy] ?? 'Unknown'; ?>
                                    <div class="mb-3">
                                        <a href="user.php?id=<?php echo $note->createdBy; ?>"><?php echo htmlspecialchars($userName); ?></a> <br />
                                        <small>Note Added: <?php echo htmlspecialchars($note->createdAt ?? ''); ?></small> <br /><br />
                                        <?php echo htmlspecialchars($note->notes ?? ''); ?>

                                        <?php if ($canEdit): ?>
                                            <a href="lead_notes.php?action=edit_lead_notes&note_id=<?php echo $noteIdVal; ?>&lead_id=<?php echo $lead_id; ?>">
                                                <span class="text-dark opacity-50"><i class="ph-pencil"></i></span>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($canDelete): ?>
                                            <a href="lead_notes.php?action=delete_lead_notes&note_id=<?php echo $noteIdVal; ?>&lead_id=<?php echo $lead_id; ?>">
                                                <span class="text-danger opacity-50"><i class="ph-trash"></i></span>
                                            </a>
                                        <?php endif; ?>
                                        <hr />
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        <?php include('admin_elements/copyright.php'); ?>
        </div>
    </div>
</div>

<?php if ($canView && !$canCreate && !$canEdit): ?>
    <script>
        $(function() {
            toggleFormElements('true');
        });
    </script>
<?php endif; ?>

<?php include('admin_elements/admin_footer.php'); ?>
