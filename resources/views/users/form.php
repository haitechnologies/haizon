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
 * @var string $dateOfJoining
 * @var int $canAccessSystem
 * @var int $isActive
 * @var string $moduleCaption
 * @var string $module
 * @var bool $canCreate
 * @var bool $canEdit
 * @var bool $canManageSystemAccess
 * @var string $rolesHtml
 * @var string $currentRoleName
 * @var string $departmentId
 * @var string $designationId
 * @var string $departmentsHtml
 * @var string $designationsHtml
 * @var array $userDocuments
 * @var array $documentCategories
 * @var array $missingMandatoryDocs
 * @var string $uploadPath
 * @var int $sessionRoleId
 */
include 'admin_elements/admin_header.php';
?>
<div class="content-wrapper">
    <?php  ?>
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1 d-flex align-items-center gap-3 flex-wrap">
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> <?php echo $moduleCaption; ?></h5>
                <?php
                $canManageSystemAccess = function_exists('has_full_access') && has_full_access();
                if (!$canManageSystemAccess && class_exists('App\\Security\\Roles')) {
                    $sessionRoleId = \App\Core\Session::roleId();
                    $canManageSystemAccess = in_array($sessionRoleId, [\App\Security\Roles::ACCOUNTS], true);
                }
                ?>
                <?php if ($canManageSystemAccess): ?>
                <div class="d-inline-flex align-items-center gap-2">
                    <div class="form-check form-check-inline form-switch mb-0">
                        <input type="checkbox" class="form-check-input form-check-input-info" name="can_access_system" id="can_access_system" <?php echo $canAccessSystem ? 'checked="checked"' : ''; ?> form="frmusers">
                        <label class="form-check-label" for="can_access_system">Can Access System</label>
                    </div>
                    <div class="form-check form-check-inline form-switch mb-0">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php echo $isActive ? 'checked="checked"' : ''; ?> form="frmusers">
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <?php else: ?>
                <input type="hidden" name="can_access_system" value="<?php echo $canAccessSystem; ?>">
                <input type="hidden" name="is_active" value="<?php echo $isActive; ?>">
                <?php endif; ?>
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
                                        <?php if ($canManageSystemAccess): ?>
                                        <select required class="form-select" name="role_id" id="role_id">
                                            <option value="">Please select</option>
                                            <?php echo $rolesHtml; ?>
                                        </select>
                                        <div class="form-text text-muted">System Access: Roles & Permissions</div>
                                        <?php else: ?>
                                        <input type="hidden" name="role_id" value="<?php echo htmlspecialchars($roleId ?: (string)$sessionRoleId); ?>">
                                        <span class="form-control-plaintext fw-bold text-uppercase"><?php echo htmlspecialchars($currentRoleName ?: 'N/A'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Department:*</span></label>
                                    <div class="col-lg-9">
                                        <select required class="form-select" name="department_id" id="department_id">
                                            <option value="">Please select</option>
                                            <?php echo $departmentsHtml; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Designation:*</span></label>
                                    <div class="col-lg-9">
                                        <select required class="form-select" name="designation_id" id="designation_id">
                                            <option value="">Please select</option>
                                            <?php echo $designationsHtml; ?>
                                        </select>
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
                                <?php if (!function_exists('has_full_access') || has_full_access() || $id <= 0): ?>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Password:</label>
                                    <div class="col-lg-9">
                                        <input type="password" name="password" id="password" class="form-control password-input" data-strength-target="#password" autocomplete="new-password" maxlength="20">
                                        <div class="form-text text-muted">Password length must be between 6 - 20 chars</div>
                                        <div id="password-strength-status" class="mt-1"></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Contact (UAE):*</span></label>
                                    <div class="col-lg-9">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">+971</span>
                                            <input required type="text" name="contact1" id="contact1" value="<?php echo $contact1; ?>" class="form-control" placeholder="52 123 4567">
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Contact (PAK):*</span></label>
                                    <div class="col-lg-9">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">+92</span>
                                            <input required type="text" name="contact2" id="contact2" value="<?php echo $contact2; ?>" class="form-control" placeholder="300 1234567">
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Address:</label>
                                    <div class="col-lg-9">
                                        <input type="text" name="address" id="address" value="<?php echo $address; ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Date of Joining:*</span></label>
                                    <div class="col-lg-9">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ph-calendar"></i></span>
                                            <input required type="text" class="form-control datepicker-basic datepicker-input in-edit" name="date_of_joining" id="date_of_joining" value="<?php echo $dateOfJoining; ?>" placeholder="Date of Joining">
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Date of Birth:</label>
                                    <div class="col-lg-9">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ph-calendar"></i></span>
                                            <input type="text" class="form-control datepicker-basic datepicker-input in-edit" name="dob" id="dob" value="<?php echo (in_array($dob, ['01-01-1970', '1970-01-01']) ? '' : $dob); ?>" placeholder="Date of Birth">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h6 class="fw-semibold mb-3">Documents Listing</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($id > 0) { ?>
                                        <div id="missing-docs-warning">
                                            <?php if (!empty($missingMandatoryDocs)): ?>
                                            <div class="alert alert-warning alert-dismissible fade show py-2 px-3 small" role="alert">
                                                <i class="ph-warning-circle me-1"></i><strong>Missing Required Documents:</strong>
                                                <?php echo implode(', ', $missingMandatoryDocs); ?>
                                                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="table-responsive">
                                        <table class="table table-sm table-striped small" id="user-documents-table">
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
                                                    <?php $catMap = []; $mandatoryMap = []; foreach ($documentCategories as $dc) { $catMap[$dc['id']] = $dc['document_category']; $mandatoryMap[$dc['id']] = !empty($dc['is_mandatory']); } ?>
                                                    <?php foreach ($userDocuments as $doc): ?>
                                                        <tr data-id="<?php echo $doc->id; ?>">
                                                            <td><?php echo htmlspecialchars((string)($catMap[$doc->documentType] ?? 'Uncategorized')); ?><?php if (!empty($mandatoryMap[$doc->documentType])): ?><br><span class="badge bg-danger py-0 px-1" style="font-size: .65rem;">Required</span><?php endif; ?></td>
                                                            <td>
                                                                <?php if ($doc->filename): ?>
                                                                    <a href="<?php echo $uploadPath . $doc->filename; ?>" target="_blank" title="<?php echo htmlspecialchars((string)$doc->originalFilename); ?>">
                                                                        <?php echo htmlspecialchars((string)$doc->originalFilename); ?>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <em class="text-muted">No file</em>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo $doc->issuedDate ? htmlspecialchars(date('d-m-Y', strtotime($doc->issuedDate))) : ''; ?></td>
                                                            <td><?php echo $doc->expiryDate ? htmlspecialchars(date('d-m-Y', strtotime($doc->expiryDate))) : ''; ?></td>
                                                            <td class="text-center text-nowrap">
                                                                <button type="button" class="btn btn-sm border-0 text-primary p-0 me-1 edit-user-doc" data-id="<?php echo $doc->id; ?>" data-issued="<?php echo $doc->issuedDate ? htmlspecialchars(date('d-m-Y', strtotime($doc->issuedDate))) : ''; ?>" data-expiry="<?php echo $doc->expiryDate ? htmlspecialchars(date('d-m-Y', strtotime($doc->expiryDate))) : ''; ?>" title="Edit dates">
                                                                    <i class="ph-pencil-simple ph-sm"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm border-0 text-danger p-0 delete-user-doc" data-id="<?php echo $doc->id; ?>" title="Delete document">
                                                                    <i class="ph-trash ph-sm"></i>
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
                                                    <option value="<?php echo $dc['id']; ?>"<?php if (!empty($dc['is_mandatory'])): ?> class="fw-bold"<?php endif; ?>><?php echo htmlspecialchars($dc['document_category']); ?><?php if (!empty($dc['is_mandatory'])): ?> *<?php endif; ?></option>
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

            <?php if ($id > 0): ?>
            <!-- Air Tickets Section -->
            <div class="card mt-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="fw-semibold mb-0"><i class="ph-airplane me-2"></i>Air Tickets</h6>
                    <span id="air-ticket-eligibility-badge" class="small"></span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped small" id="air-tickets-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Eligibility Date</th>
                                    <th>Departure</th>
                                    <th>Arrival</th>
                                    <th>Ticket File</th>
                                    <th>Notes</th>
                                    <th width="60">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="empty-row"><td colspan="7" class="text-center text-muted py-3">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <hr>
                    <h6 class="fw-semibold mb-3">Add Air Ticket</h6>
                    <div class="row g-2 align-items-end" id="add-air-ticket-row">
                        <div class="col-lg-2">
                            <select class="form-select form-select-sm" id="at-status">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="ph-calendar"></i></span>
                                <input type="text" class="form-control datepicker-basic datepicker-input" id="at-eligibility" placeholder="Eligibility date" readonly>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="ph-calendar"></i></span>
                                <input type="text" class="form-control datepicker-basic datepicker-input" id="at-departure" placeholder="Departure date" readonly>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="ph-calendar"></i></span>
                                <input type="text" class="form-control datepicker-basic datepicker-input" id="at-arrival" placeholder="Arrival date" readonly>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <input type="file" class="form-control form-control-sm" id="at-file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        </div>
                        <div class="col-lg-2">
                            <button type="button" class="btn btn-primary btn-sm w-100" id="btn-add-air-ticket">
                                <i class="ph-plus ph-sm me-1"></i>Add
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-lg-12">
                            <textarea class="form-control form-control-sm" id="at-notes" placeholder="Notes..." rows="1"></textarea>
                        </div>
                    </div>
                    <div id="at-upload-progress" class="mt-2" style="display:none;">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                        <span class="text-muted small">Saving...</span>
                    </div>
                    <div id="at-upload-error" class="mt-2 text-danger small" style="display:none;"></div>
                </div>
            </div>
            <!-- /Air Tickets Section -->

            <!-- Edit Air Ticket Modal -->
            <div class="modal fade" id="editAirTicketModal" tabindex="-1">
                <div class="modal-dialog modal-md">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title">Edit Air Ticket</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="edit-at-id">
                            <div class="row g-2 mb-2">
                                <div class="col-lg-4">
                                    <label class="form-label small">Status</label>
                                    <select class="form-select form-select-sm" id="edit-at-status">
                                        <option value="pending">Pending</option>
                                        <option value="paid">Paid</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label small">Departure Date</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="ph-calendar"></i></span>
                                        <input type="text" class="form-control datepicker-basic datepicker-input" id="edit-at-departure" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label small">Arrival Date</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="ph-calendar"></i></span>
                                        <input type="text" class="form-control datepicker-basic datepicker-input" id="edit-at-arrival" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Ticket File</label>
                                <input type="file" class="form-control form-control-sm" id="edit-at-file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Notes</label>
                                <textarea class="form-control form-control-sm" id="edit-at-notes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary btn-sm" id="btn-save-air-ticket">Save</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Edit Air Ticket Modal -->
            <?php endif; ?>

        </div>
        <?php include 'admin_elements/copyright.php'; ?>
    </div>
</div>

<!-- Edit Document Dates Modal -->
<div class="modal fade" id="editDocDatesModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Edit Document Dates</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-doc-id">
                <div class="mb-3">
                    <label class="form-label">Issue Date</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="ph-calendar"></i></span>
                        <input type="text" class="form-control datepicker-basic datepicker-input" id="edit-doc-issued" readonly>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Expiry Date</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="ph-calendar"></i></span>
                        <input type="text" class="form-control datepicker-basic datepicker-input" id="edit-doc-expiry" readonly>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btn-save-doc-dates">Save</button>
            </div>
        </div>
    </div>
</div>

<style>
.ui-datepicker { z-index: 9999 !important; }
</style>
<?php if ($id > 0 && ($canCreate || $canEdit)): ?>
<script>
$(function() {
    var csrfToken = $('input[name="csrf_token"]').val();
    var userId = <?php echo $id; ?>;
    var dateOfJoining = '<?php echo $dateOfJoining; ?>';

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

    // Edit document dates
    $(document).on('click', '.edit-user-doc', function() {
        var id = $(this).data('id');
        var issued = $(this).data('issued') || '';
        var expiry = $(this).data('expiry') || '';
        $('#edit-doc-id').val(id);
        $('#edit-doc-issued').val(issued);
        $('#edit-doc-expiry').val(expiry);
        if (!$('#edit-doc-issued').hasClass('hasDatepicker')) {
            $('#edit-doc-issued').datepicker({
                dateFormat: 'dd-mm-yy',
                changeMonth: true,
                changeYear: true,
                beforeShow: function() { setTimeout(function(){ $('.ui-datepicker').css('z-index', 9999); }, 0); }
            });
            $('#edit-doc-expiry').datepicker({
                dateFormat: 'dd-mm-yy',
                changeMonth: true,
                changeYear: true,
                beforeShow: function() { setTimeout(function(){ $('.ui-datepicker').css('z-index', 9999); }, 0); }
            });
        }
        $('#editDocDatesModal').modal('show');
    });

    $('#btn-save-doc-dates').on('click', function() {
        var id = $('#edit-doc-id').val();
        if (!id) return;
        var btn = $(this).prop('disabled', true);
        $.ajax({
            url: 'users.php',
            type: 'POST',
            data: {
                action: 'update_user_doc_dates',
                csrf_token: csrfToken,
                id: id,
                issued_date: $('#edit-doc-issued').val(),
                expiry_date: $('#edit-doc-expiry').val()
            },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    $('#editDocDatesModal').modal('hide');
                    loadUserDocuments();
                } else {
                    alert(resp.error || 'Update failed.');
                }
            },
            error: function() {
                alert('Update failed.');
            },
            complete: function() {
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
                            var catBadge = doc.is_mandatory ? '<br><span class="badge bg-danger py-0 px-1" style="font-size: .65rem;">Required</span>' : '';
                            var fileLink = doc.filename
                                ? '<a href="' + doc.file_url + '" target="_blank" title="' + (doc.original_filename || '') + '">' + (doc.original_filename || doc.filename) + '</a>'
                                : '<em class="text-muted">No file</em>';
                            html += '<tr data-id="' + doc.id + '">'
                                + '<td>' + (doc.category || 'Uncategorized') + catBadge + '</td>'
                                + '<td>' + fileLink + '</td>'
                                + '<td>' + (doc.issued_date || '') + '</td>'
                                + '<td>' + (doc.expiry_date || '') + '</td>'
                                + '<td class="text-center text-nowrap"><button type="button" class="btn btn-sm border-0 text-primary p-0 me-1 edit-user-doc" data-id="' + doc.id + '" data-issued="' + (doc.issued_date || '') + '" data-expiry="' + (doc.expiry_date || '') + '" title="Edit dates"><i class="ph-pencil-simple ph-sm"></i></button><button type="button" class="btn btn-sm border-0 text-danger p-0 delete-user-doc" data-id="' + doc.id + '" title="Delete document"><i class="ph-trash ph-sm"></i></button></td>'
                                + '</tr>';
                        });
                        tbody.html(html);
                    }
                    // Refresh missing mandatory docs warning
                    var warningHtml = '';
                    if (resp.missing_mandatory && resp.missing_mandatory.length > 0) {
                        warningHtml = '<div class="alert alert-warning alert-dismissible fade show py-2 px-3 small" role="alert">'
                            + '<i class="ph-warning-circle me-1"></i><strong>Missing Required Documents:</strong> '
                            + resp.missing_mandatory.join(', ')
                            + '<button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>'
                            + '</div>';
                    }
                    $('#missing-docs-warning').html(warningHtml);
                }
            }
        });
    }

    // === Air Tickets ===
    function updateEligibilityBadge() {
        if (dateOfJoining) {
            var parts = dateOfJoining.split('-');
            var doj = new Date(parts[2], parts[1] - 1, parts[0]);
            var now = new Date();
            var months = (now.getFullYear() - doj.getFullYear()) * 12 + (now.getMonth() - doj.getMonth());
            var badge = $('#air-ticket-eligibility-badge');
            if (months >= 12) {
                badge.html('<span class="badge bg-success">Eligible (' + Math.floor(months / 12) + ' yr(s))</span>');
            } else {
                badge.html('<span class="badge bg-secondary">Not yet eligible (' + (12 - months) + ' mo(s) remaining)</span>');
            }
        }
    }
    updateEligibilityBadge();

    function loadAirTickets() {
        $.ajax({
            url: 'users.php',
            type: 'POST',
            data: {
                action: 'list_user_air_tickets',
                csrf_token: csrfToken,
                id: userId
            },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    var tbody = $('#air-tickets-table tbody');
                    if (resp.data.length === 0) {
                        tbody.html('<tr class="empty-row"><td colspan="7" class="text-center text-muted py-3">No air tickets yet.</td></tr>');
                    } else {
                        var html = '';
                        $.each(resp.data, function(i, t) {
                            var statusBadge = t.status === 'paid'
                                ? '<span class="badge bg-success">Paid</span>'
                                : t.status === 'cancelled'
                                    ? '<span class="badge bg-secondary">Cancelled</span>'
                                    : '<span class="badge bg-warning text-dark">Pending</span>';
                            var fileLink = t.ticket_file
                                ? '<a href="' + t.file_url + '" target="_blank" title="View ticket"><i class="ph-file-text ph-sm me-1"></i>View</a>'
                                : '<span class="text-muted">—</span>';
                            html += '<tr>'
                                + '<td>' + statusBadge + '</td>'
                                + '<td class="small">' + (t.eligibility_date || '—') + '</td>'
                                + '<td class="small">' + (t.departure_date || '—') + '</td>'
                                + '<td class="small">' + (t.arrival_date || '—') + '</td>'
                                + '<td class="small">' + fileLink + '</td>'
                                + '<td class="small text-muted">' + (t.notes || '—') + '</td>'
                                + '<td class="text-center text-nowrap"><button type="button" class="btn btn-sm border-0 text-primary p-0 me-1 edit-air-ticket" data-id="' + t.id + '" data-status="' + t.status + '" data-departure="' + (t.departure_date || '') + '" data-arrival="' + (t.arrival_date || '') + '" title="Edit"><i class="ph-pencil-simple ph-sm"></i></button><button type="button" class="btn btn-sm border-0 text-danger p-0 delete-air-ticket" data-id="' + t.id + '" title="Delete"><i class="ph-trash ph-sm"></i></button></td>'
                                + '</tr>';
                        });
                        tbody.html(html);
                    }
                }
            }
        });
    }

    // Add air ticket
    $('#btn-add-air-ticket').on('click', function() {
        var status = $('#at-status').val();
        var eligibility = $('#at-eligibility').val();
        var departure = $('#at-departure').val();
        var arrival = $('#at-arrival').val();
        var notes = $('#at-notes').val();
        var fileInput = $('#at-file')[0];

        $('#at-upload-error').hide();
        $('#at-upload-progress').show();
        $(this).prop('disabled', true);

        var formData = new FormData();
        formData.append('action', 'add_user_air_ticket');
        formData.append('csrf_token', csrfToken);
        formData.append('employee_id', userId);
        formData.append('status', status);
        formData.append('eligibility_date', eligibility);
        formData.append('departure_date', departure);
        formData.append('arrival_date', arrival);
        formData.append('notes', notes);
        if (fileInput && fileInput.files[0]) {
            formData.append('ticket_file', fileInput.files[0]);
        }

        $.ajax({
            url: 'users.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    $('#at-status').val('pending');
                    $('#at-eligibility').val('');
                    $('#at-departure').val('');
                    $('#at-arrival').val('');
                    $('#at-notes').val('');
                    $('#at-file').val('');
                    loadAirTickets();
                } else {
                    $('#at-upload-error').text(resp.error || 'Failed to add ticket.').show();
                }
            },
            error: function(xhr) {
                var msg = 'Failed to add ticket.';
                try { var r = JSON.parse(xhr.responseText); msg = r.error || msg; } catch(e) {}
                $('#at-upload-error').text(msg).show();
            },
            complete: function() {
                $('#at-upload-progress').hide();
                $('#btn-add-air-ticket').prop('disabled', false);
            }
        });
    });

    // Edit air ticket - open modal
    $(document).on('click', '.edit-air-ticket', function() {
        var id = $(this).data('id');
        var status = $(this).data('status');
        var departure = $(this).data('departure') || '';
        var arrival = $(this).data('arrival') || '';
        var notes = $(this).closest('tr').find('td:nth-child(6)').text();
        notes = notes === '—' ? '' : notes;
        $('#edit-at-id').val(id);
        $('#edit-at-status').val(status);
        $('#edit-at-departure').val(departure);
        $('#edit-at-arrival').val(arrival);
        $('#edit-at-notes').val(notes);
        $('#edit-at-file').val('');
        $('#editAirTicketModal').modal('show');
    });

    // Save edited air ticket
    $('#btn-save-air-ticket').on('click', function() {
        var id = $('#edit-at-id').val();
        if (!id) return;
        var btn = $(this).prop('disabled', true);

        var formData = new FormData();
        formData.append('action', 'update_user_air_ticket');
        formData.append('csrf_token', csrfToken);
        formData.append('id', id);
        formData.append('status', $('#edit-at-status').val());
        formData.append('departure_date', $('#edit-at-departure').val());
        formData.append('arrival_date', $('#edit-at-arrival').val());
        formData.append('notes', $('#edit-at-notes').val());
        var fileInput = $('#edit-at-file')[0];
        if (fileInput && fileInput.files[0]) {
            formData.append('ticket_file', fileInput.files[0]);
        }

        $.ajax({
            url: 'users.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    $('#editAirTicketModal').modal('hide');
                    loadAirTickets();
                } else {
                    alert(resp.error || 'Update failed.');
                }
            },
            error: function(xhr) {
                var msg = 'Update failed.';
                try { var r = JSON.parse(xhr.responseText); msg = r.error || msg; } catch(e) {}
                alert(msg);
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Delete air ticket
    $(document).on('click', '.delete-air-ticket', function() {
        if (!confirm('Delete this air ticket?')) return;
        var ticketId = $(this).data('id');
        var btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: 'users.php',
            type: 'POST',
            data: {
                action: 'delete_user_air_ticket',
                csrf_token: csrfToken,
                id: ticketId
            },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    loadAirTickets();
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

    // Initial load
    loadAirTickets();
});
</script>
<?php endif; ?>
<?php
include 'admin_elements/admin_footer.php';
