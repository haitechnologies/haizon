<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var string $roleId
 * @var string $firstName
 * @var string $lastName
 * @var string $email
 * @var string $password
 * @var string $contact1
 * @var string $contact2
 * @var string $address
 * @var string $dob
 * @var int $canAccessSystem
 * @var int $isActive
 * @var string $moduleCaption
 * @var string $module
 * @var bool $canCreate
 * @var bool $canEdit
 * @var string $rolesHtml
 * @var array $userDocuments
 * @var array $documentCategories
 * @var string $uploadPath
 */
include 'admin_elements/admin_header.php';
?>
<div class="content-wrapper">
    <?php include 'admin_elements/hr_navbar.php'; ?>
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> <?php echo $moduleCaption; ?></h5>
            </div>
            <div class="my-1 d-inline-flex align-items-center me-2">
                <div class="form-check form-check-inline form-switch mb-0">
                    <input type="checkbox" class="form-check-input form-check-input-info" name="can_access_system" id="can_access_system" <?php echo $canAccessSystem ? 'checked="checked"' : ''; ?> form="frmusers">
                    <label class="form-check-label" for="can_access_system">Can Access System</label>
                </div>
                <div class="form-check form-check-inline form-switch mb-0 ms-3">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php echo $isActive ? 'checked="checked"' : ''; ?> form="frmusers">
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            <div class="my-1">
                <?php if ($canCreate || $canEdit) { ?>
                    <button type="submit" form="frmusers" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_users.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" id="frmusers" name="frmusers" action="users.php" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_users">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_users">
                <?php } ?>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">System Role:*</span></label>
                                    <div class="col-lg-9">
                                        <select required class="form-select" name="role_id" id="role_id">
                                            <option value="">Please select</option>
                                            <?php echo $rolesHtml; ?>
                                        </select>
                                        <div class="form-text text-muted">System Access: Roles & Permissions</div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Name:*</span></label>
                                    <div class="col-lg-9">
                                        <div class="row">
                                            <div class="col-6 pe-1">
                                                <input required type="text" name="first_name" id="first_name" value="<?php echo $firstName; ?>" class="form-control" placeholder="First Name">
                                            </div>
                                            <div class="col-6 ps-1">
                                                <input type="text" name="last_name" id="last_name" value="<?php echo $lastName; ?>" class="form-control" placeholder="Last Name">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Email:*</span></label>
                                    <div class="col-lg-9">
                                        <input required type="email" name="email" id="email" value="<?php echo $email; ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Password:</label>
                                    <div class="col-lg-9">
                                        <input type="password" name="password" id="password" class="form-control password-input" data-strength-target="#password" autocomplete="new-password" maxlength="20">
                                        <div class="form-text text-muted">Password length must be between 6 - 20 chars</div>
                                        <div id="password-strength-status" class="mt-1"></div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Contact 1:*</span></label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="contact1" id="contact1" value="<?php echo $contact1; ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Contact 2:</label>
                                    <div class="col-lg-9">
                                        <input type="text" name="contact2" id="contact2" value="<?php echo $contact2; ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Address:</label>
                                    <div class="col-lg-9">
                                        <input type="text" name="address" id="address" value="<?php echo $address; ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Date of Birth:</label>
                                    <div class="col-lg-9">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ph-calendar"></i></span>
                                            <input type="text" class="form-control datepicker-basic datepicker-input in-edit" name="dob" id="dob" value="<?php echo ($dob == '01-01-1970' ? '' : $dob); ?>" placeholder="Date of Birth">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h2 class="mb-0">Documents</h2>
                            </div>
                            <div class="card-body">
                                <?php if ($id > 0) { ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped" id="user-documents-table">
                                            <thead>
                                                <tr>
                                                    <th>Category</th>
                                                    <th>File</th>
                                                    <th>Issue Date</th>
                                                    <th>Expiry Date</th>
                                                    <th width="50">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($userDocuments)): ?>
                                                    <?php $catMap = []; foreach ($documentCategories as $dc) { $catMap[$dc['id']] = $dc['document_category']; } ?>
                                                    <?php foreach ($userDocuments as $doc): ?>
                                                        <tr data-id="<?php echo $doc->id; ?>">
                                                            <td><?php echo htmlspecialchars((string)($catMap[$doc->documentType] ?? 'Uncategorized')); ?></td>
                                                            <td>
                                                                <?php if ($doc->filename): ?>
                                                                    <a href="<?php echo $uploadPath . $doc->filename; ?>" target="_blank" title="<?php echo htmlspecialchars((string)$doc->originalFilename); ?>">
                                                                        <?php echo htmlspecialchars((string)$doc->originalFilename); ?>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <em class="text-muted">No file</em>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars((string)$doc->issuedDate); ?></td>
                                                            <td><?php echo htmlspecialchars((string)$doc->expiryDate); ?></td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-sm btn-outline-danger delete-user-doc" data-id="<?php echo $doc->id; ?>" title="Delete document">
                                                                    <i class="ph-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr class="empty-row"><td colspan="5" class="text-center text-muted py-3">No documents uploaded yet.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <hr>
                                    <h6 class="fw-semibold mb-3">Add Document</h6>
                                    <div class="row g-2 align-items-end" id="add-doc-row">
                                        <div class="col-lg-3">
                                            <select class="form-select form-select-sm" id="doc-category">
                                                <option value="">Select category</option>
                                                <?php foreach ($documentCategories as $dc): ?>
                                                    <option value="<?php echo $dc['id']; ?>"><?php echo htmlspecialchars($dc['document_category']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-lg-3">
                                            <input type="file" class="form-control form-control-sm" id="doc-file" accept=".doc,.docx,.pdf,.txt,.rtf,.xls,.xlsx,.ppt,.pptx,.jpeg,.jpg,.png">
                                        </div>
                                        <div class="col-lg-2">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="ph-calendar"></i></span>
                                                <input type="text" class="form-control datepicker-basic datepicker-input" id="doc-issued" placeholder="Issue date" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-2">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="ph-calendar"></i></span>
                                                <input type="text" class="form-control datepicker-basic datepicker-input" id="doc-expiry" placeholder="Expiry date" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-2">
                                            <button type="button" class="btn btn-primary btn-sm w-100" id="btn-upload-doc">
                                                <i class="ph-upload ph-sm me-1"></i>Upload
                                            </button>
                                        </div>
                                    </div>
                                    <div id="doc-upload-progress" class="mt-2" style="display:none;">
                                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                        <span class="text-muted small">Uploading...</span>
                                    </div>
                                    <div id="doc-upload-error" class="mt-2 text-danger small" style="display:none;"></div>
                                <?php } else { ?>
                                    <p class="text-muted mb-0">Save the employee first to manage documents.</p>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php include 'admin_elements/copyright.php'; ?>
    </div>
</div>
<?php if ($id > 0 && ($canCreate || $canEdit)): ?>
<script>
$(function() {
    var csrfToken = $('input[name="csrf_token"]').val();
    var userId = <?php echo $id; ?>;

    $('#btn-upload-doc').on('click', function() {
        var category = $('#doc-category').val();
        var fileInput = $('#doc-file')[0];
        var issued = $('#doc-issued').val();
        var expiry = $('#doc-expiry').val();

        if (!category) { $('#doc-upload-error').text('Please select a category.').show(); return; }
        if (!fileInput || !fileInput.files[0]) { $('#doc-upload-error').text('Please select a file.').show(); return; }

        $('#doc-upload-error').hide();
        $('#doc-upload-progress').show();
        $(this).prop('disabled', true);

        var formData = new FormData();
        formData.append('action', 'upload_user_doc');
        formData.append('csrf_token', csrfToken);
        formData.append('user_id', userId);
        formData.append('document_type', category);
        formData.append('issued_date', issued);
        formData.append('expiry_date', expiry);
        formData.append('document', fileInput.files[0]);

        $.ajax({
            url: 'users.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    $('#doc-category').val('');
                    $('#doc-file').val('');
                    $('#doc-issued').val('');
                    $('#doc-expiry').val('');
                    loadUserDocuments();
                } else {
                    $('#doc-upload-error').text(resp.error || 'Upload failed.').show();
                }
            },
            error: function(xhr) {
                var msg = 'Upload failed.';
                try { var r = JSON.parse(xhr.responseText); msg = r.error || msg; } catch(e) {}
                $('#doc-upload-error').text(msg).show();
            },
            complete: function() {
                $('#doc-upload-progress').hide();
                $('#btn-upload-doc').prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.delete-user-doc', function() {
        if (!confirm('Delete this document?')) return;
        var docId = $(this).data('id');
        var btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: 'users.php',
            type: 'POST',
            data: {
                action: 'delete_user_doc',
                csrf_token: csrfToken,
                id: docId,
                user_id: userId
            },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    loadUserDocuments();
                } else {
                    alert(resp.error || 'Delete failed.');
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                alert('Delete failed.');
                btn.prop('disabled', false);
            }
        });
    });

    function loadUserDocuments() {
        $.ajax({
            url: 'users.php',
            type: 'POST',
            data: {
                action: 'list_user_docs',
                csrf_token: csrfToken,
                id: userId
            },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    var tbody = $('#user-documents-table tbody');
                    if (resp.data.length === 0) {
                        tbody.html('<tr class="empty-row"><td colspan="5" class="text-center text-muted py-3">No documents uploaded yet.</td></tr>');
                    } else {
                        var html = '';
                        $.each(resp.data, function(i, doc) {
                            var fileLink = doc.filename
                                ? '<a href="' + doc.file_url + '" target="_blank" title="' + (doc.original_filename || '') + '">' + (doc.original_filename || doc.filename) + '</a>'
                                : '<em class="text-muted">No file</em>';
                            html += '<tr data-id="' + doc.id + '">'
                                + '<td>' + (doc.category || 'Uncategorized') + '</td>'
                                + '<td>' + fileLink + '</td>'
                                + '<td>' + (doc.issued_date || '') + '</td>'
                                + '<td>' + (doc.expiry_date || '') + '</td>'
                                + '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger delete-user-doc" data-id="' + doc.id + '" title="Delete document"><i class="ph-trash"></i></button></td>'
                                + '</tr>';
                        });
                        tbody.html(html);
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>
<?php
include 'admin_elements/admin_footer.php';
