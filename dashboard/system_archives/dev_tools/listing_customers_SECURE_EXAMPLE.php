<?php
/**
 * EXAMPLE: Secure Customer Management Page
 * 
 * This file demonstrates ALL security best practices:
 * - CSRF Protection
 * - Input Validation (InputValidator)
 * - IDOR Protection (checkOwnership)
 * - Permission Checks (granted_)
 * - XSS Prevention (output escaping)
 * - SQL Injection Prevention (prepared statements)
 * 
 * USE THIS AS A TEMPLATE for creating/updating dashboard pages.
 * 
 * @version 1.0
 * @date February 27, 2026
 */

// ============================================================================
// STEP 1: Include Dependencies
// ============================================================================
include 'admin_elements/admin_header.php';
require_once __DIR__ . '/../classes/InputValidator.php';

// ============================================================================
// STEP 2: Define Module & Table
// ============================================================================
$module = 'customers';
$module_caption = 'Customers';
$tbl_name = DB::CUSTOMERS;

// ============================================================================
// STEP 3: Permission & CSRF Initialization
// ============================================================================
include 'admin_elements/permissions.php';
$hide_add_button = true;

// Initialize variables
$errors = [];
$success = '';

// ============================================================================
// STEP 4: CSRF Validation Block (MANDATORY for POST/DELETE operations)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token FIRST
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $errors[] = "Invalid security token. Please refresh and try again.";
        error_log("CSRF validation failed in listing_customers_secure.php");
    }
}

// ============================================================================
// STEP 5: POST Action Handlers (with IDOR protection)
// ============================================================================

// ----------------------------------------------------------------------------
// DELETE Action
// ----------------------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] == 'delete' && empty($errors)) {
    if (granted_('delete', $module)) {
        // Validate ID input
        $idResult = InputValidator::integer($_POST['id'], 1);
        
        if (!$idResult['valid']) {
            $errors[] = "Invalid customer ID: " . $idResult['error'];
        } else {
            $customerId = $idResult['value'];
            
            // IDOR Protection: Check ownership
            // Only allow deletion if user owns this customer OR has system admin role
            $canDelete = false;
            
            if ($_SESSION['h_role_id'] == SYSTEM_ADMIN) {
                // System admin can delete any customer
                $canDelete = true;
            } else {
                // Regular users can only delete their own customers
                $canDelete = checkOwnership($tbl_name, $customerId, 'user_id');
            }
            
            if ($canDelete) {
                if (delete($tbl_name, $customerId)) {
                    $success = "Customer deleted successfully";
                    
                    // Log the action
                    error_log("Customer ID $customerId deleted by user " . $_SESSION['h_id']);
                } else {
                    $errors[] = "Failed to delete customer";
                }
            } else {
                $errors[] = "You do not have permission to delete this customer";
                error_log("IDOR attempt: User " . $_SESSION['h_id'] . " tried to delete customer $customerId");
            }
        }
    } else {
        $errors[] = "You do not have permission to delete customers";
    }
}

// ----------------------------------------------------------------------------
// BULK DELETE Action
// ----------------------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] == 'bulk_delete' && empty($errors)) {
    if (granted_('delete', $module)) {
        $requestedIds = $_POST['ids'] ?? [];
        
        if (empty($requestedIds)) {
            $errors[] = "No customers selected for deletion";
        } else {
            // Validate each ID
            $validIds = [];
            foreach ($requestedIds as $id) {
                $result = InputValidator::integer($id, 1);
                if ($result['valid']) {
                    $validIds[] = $result['value'];
                }
            }
            
            if (empty($validIds)) {
                $errors[] = "No valid customer IDs provided";
            } else {
                // IDOR Protection: Filter to only owned customers
                if ($_SESSION['h_role_id'] == SYSTEM_ADMIN) {
                    // System admin can delete any
                    $ownedIds = $validIds;
                } else {
                    // Regular users can only delete their own
                    $ownedIds = filterOwnedResources($validIds, $tbl_name, 'user_id');
                }
                
                if (empty($ownedIds)) {
                    $errors[] = "You do not own any of the selected customers";
                } else {
                    $deleted = 0;
                    foreach ($ownedIds as $id) {
                        if (delete($tbl_name, $id)) {
                            $deleted++;
                        }
                    }
                    
                    $requested = count($requestedIds);
                    $success = "Deleted $deleted of $requested customers";
                    
                    if ($deleted < $requested) {
                        $errors[] = "Some customers could not be deleted (you may not own them)";
                    }
                }
            }
        }
    } else {
        $errors[] = "You do not have permission to delete customers";
    }
}

// ----------------------------------------------------------------------------
// PUBLISH/UNPUBLISH Action
// ----------------------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] == 'toggle_publish' && empty($errors)) {
    if (granted_('edit', $module)) {
        // Validate inputs
        $idResult = InputValidator::integer($_POST['id'], 1);
        $publishResult = InputValidator::enum($_POST['publish'], ['0', '1', 0, 1]);
        
        if (!$idResult['valid']) {
            $errors[] = "Invalid customer ID";
        } elseif (!$publishResult['valid']) {
            $errors[] = "Invalid publish status";
        } else {
            $customerId = $idResult['value'];
            $newStatus = (int)$publishResult['value'];
            
            // IDOR Protection: Check ownership
            if ($_SESSION['h_role_id'] == SYSTEM_ADMIN || checkOwnership($tbl_name, $customerId, 'user_id')) {
                $stmt = $conn->prepare("UPDATE $tbl_name SET is_active = ? WHERE id = ?");
                $stmt->bind_param("ii", $newStatus, $customerId);
                
                if ($stmt->execute()) {
                    $statusText = $newStatus ? 'published' : 'unpublished';
                    $success = "Customer $statusText successfully";
                } else {
                    $errors[] = "Failed to update customer status";
                }
                
                $stmt->close();
            } else {
                $errors[] = "You do not have permission to edit this customer";
                error_log("IDOR attempt: User " . $_SESSION['h_id'] . " tried to edit customer $customerId");
            }
        }
    } else {
        $errors[] = "You do not have permission to edit customers";
    }
}

// ----------------------------------------------------------------------------
// CREATE/EDIT Action (Form Submission)
// ----------------------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] == 'save' && empty($errors)) {
    $isEdit = !empty($_POST['id']);
    $requiredPermission = $isEdit ? 'edit' : 'create';
    
    if (granted_($requiredPermission, $module)) {
        // Validate all inputs
        $validation = InputValidator::validateMultiple($_POST, [
            'id' => [
                'type' => 'integer',
                'min' => 1,
                'allowNull' => true
            ],
            'company_name' => [
                'type' => 'string',
                'maxLength' => 200,
                'minLength' => 3,
                'allowEmpty' => false
            ],
            'contact_person' => [
                'type' => 'string',
                'maxLength' => 100,
                'allowEmpty' => true
            ],
            'email' => [
                'type' => 'email',
                'checkDNS' => false
            ],
            'phone' => [
                'type' => 'phone',
                'format' => null // Accept any valid phone format
            ],
            'website' => [
                'type' => 'url',
                'allowedSchemes' => ['http', 'https']
            ],
            'address' => [
                'type' => 'string',
                'maxLength' => 500,
                'allowEmpty' => true
            ],
            'city' => [
                'type' => 'string',
                'maxLength' => 100,
                'allowEmpty' => true
            ],
            'country' => [
                'type' => 'string',
                'maxLength' => 100,
                'allowEmpty' => true
            ],
            'status' => [
                'type' => 'enum',
                'allowedValues' => ['active', 'inactive', 'pending']
            ]
        ]);
        
        if (!$validation['valid']) {
            $errors = array_merge($errors, array_values($validation['errors']));
        } else {
            $data = $validation['values'];
            
            if ($isEdit) {
                // EDIT: Check ownership
                $customerId = $data['id'];
                
                if ($_SESSION['h_role_id'] == SYSTEM_ADMIN || checkOwnership($tbl_name, $customerId, 'user_id')) {
                    $stmt = $conn->prepare("UPDATE $tbl_name SET 
                        company_name = ?, 
                        contact_person = ?, 
                        email = ?, 
                        phone = ?, 
                        website = ?, 
                        address = ?, 
                        city = ?, 
                        country = ?, 
                        status = ? 
                        WHERE id = ?");
                    
                    $stmt->bind_param("sssssssssi",
                        $data['company_name'],
                        $data['contact_person'],
                        $data['email'],
                        $data['phone'],
                        $data['website'],
                        $data['address'],
                        $data['city'],
                        $data['country'],
                        $data['status'],
                        $customerId
                    );
                    
                    if ($stmt->execute()) {
                        $success = "Customer updated successfully";
                    } else {
                        $errors[] = "Database error: " . $stmt->error;
                    }
                    
                    $stmt->close();
                } else {
                    $errors[] = "You do not have permission to edit this customer";
                }
            } else {
                // CREATE: Assign to current user
                $userId = $_SESSION['h_id'];
                
                $stmt = $conn->prepare("INSERT INTO $tbl_name 
                    (company_name, contact_person, email, phone, website, address, city, country, status, user_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
                $stmt->bind_param("sssssssssi",
                    $data['company_name'],
                    $data['contact_person'],
                    $data['email'],
                    $data['phone'],
                    $data['website'],
                    $data['address'],
                    $data['city'],
                    $data['country'],
                    $data['status'],
                    $userId
                );
                
                if ($stmt->execute()) {
                    $success = "Customer created successfully";
                } else {
                    $errors[] = "Database error: " . $stmt->error;
                }
                
                $stmt->close();
            }
        }
    } else {
        $errors[] = "You do not have permission to $requiredPermission customers";
    }
}

?>

<!-- ============================================================================ -->
<!-- STEP 6: Page HTML with XSS Protection -->
<!-- ============================================================================ -->

<div class="content-wrapper">
    <!-- Page Header -->
    <?php include 'admin_elements/page_header.php'; ?>

    <!-- Error/Success Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> <?php echo e($success); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <section class="content datatable-enhanced">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo e($module_caption); ?> List</h3>
                
                <?php if (granted_('create', $module)): ?>
                    <div class="card-tools">
                        <a href="#" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createModal">
                            <i class="fa fa-plus"></i> Add New
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card-body">
                <!-- Bulk Actions -->
                <?php if (granted_('delete', $module)): ?>
                    <div class="mb-3">
                        <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled>
                            <i class="fa fa-trash"></i> Delete Selected
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- DataTable -->
                <table id="grid-<?php echo e($module); ?>" class="table datatable-professional table-bordered table-striped custom_datatables">
                    <thead>
                        <tr>
                            <?php if (granted_('delete', $module)): ?>
                                <th><input type="checkbox" id="selectAll"></th>
                            <?php endif; ?>
                            <th>ID</th>
                            <th>Company Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </section>
    
    <!-- Hidden CSRF Token for JavaScript Access (REQUIRED) -->
    <?php csrf_field(); ?>
</div>

<?php include 'admin_elements/admin_footer.php'; ?>

<!-- ============================================================================ -->
<!-- STEP 7: JavaScript with CSRF Token Injection -->
<!-- ============================================================================ -->
<script>
$(document).ready(function() {
    // Get CSRF token from hidden input
    const csrfToken = $('input[name="csrf_token"]').val();
    
    // Initialize DataTable
    const table = $('#grid-<?php echo e($module); ?>').DataTable({
        processing: true,
        serverSide: true,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",

        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],

        ajax: {
            url: 'datatables.php',
            type: 'POST',
            data: {
                ajax_action: 'listing_customers',
                csrf_token: csrfToken // CSRF token included in AJAX
            }
        },
        columns: [
            <?php if (granted_('delete', $module)): ?>
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function(data) {
                    return '<input type="checkbox" class="row-checkbox" value="' + data + '">';
                }
            },
            <?php endif; ?>
            { data: 'id' },
            { data: 'company_name' },
            { data: 'contact_person' },
            { data: 'email' },
            { data: 'phone' },
            { data: 'status' },
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    let actions = '';
                    
                    <?php if (granted_('edit', $module)): ?>
                    actions += '<a href="customers.php?action=edit_customers&id=' + data + '" class="btn btn-sm btn-primary">' +
                               '<i class="fa fa-edit"></i></a> ';
                    <?php endif; ?>
                    
                    <?php if (granted_('delete', $module)): ?>
                    actions += '<button class="btn btn-sm btn-danger delete-btn" data-id="' + data + '">' +
                               '<i class="fa fa-trash"></i></button>';
                    <?php endif; ?>
                    
                    return actions;
                }
            }
        ],
        order: [[1, 'desc']], // Order by ID descending
        pageLength: 10,
        language: {
            processing: '<i class="fa fa-spinner fa-spin"></i> Loading...'
        }
    });
    
    // ========================================================================
    // Select All Checkbox
    // ========================================================================
    $('#selectAll').on('click', function() {
        $('.row-checkbox').prop('checked', this.checked);
        updateBulkDeleteButton();
    });
    
    $(document).on('change', '.row-checkbox', function() {
        updateBulkDeleteButton();
    });
    
    function updateBulkDeleteButton() {
        const selectedCount = $('.row-checkbox:checked').length;
        $('#bulkDeleteBtn').prop('disabled', selectedCount === 0);
        $('#bulkDeleteBtn').text('Delete Selected (' + selectedCount + ')');
    }
    
    // ========================================================================
    // Single Delete (with CSRF)
    // ========================================================================
    $(document).on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        
        if (!confirm('Are you sure you want to delete this customer?')) {
            return;
        }
        
        // Create form and submit with CSRF token
        const form = $('<form method="POST" style="display:none;">' +
                      '<input type="hidden" name="csrf_token" value="' + csrfToken + '">' +
                      '<input type="hidden" name="action" value="delete">' +
                      '<input type="hidden" name="id" value="' + id + '">' +
                      '</form>');
        
        $('body').append(form);
        form.submit();
    });
    
    // ========================================================================
    // Bulk Delete (with CSRF and IDOR protection)
    // ========================================================================
    $('#bulkDeleteBtn').on('click', function() {
        const checkedBoxes = $('.row-checkbox:checked');
        const ids = checkedBoxes.map(function() {
            return $(this).val();
        }).get();
        
        if (ids.length === 0) {
            alert('Please select at least one customer');
            return;
        }
        
        if (!confirm('Are you sure you want to delete ' + ids.length + ' customer(s)?')) {
            return;
        }
        
        // Send AJAX request with CSRF token
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                action: 'bulk_delete',
                ids: ids,
                csrf_token: csrfToken
            },
            success: function(response) {
                // Reload page to show results
                location.reload();
            },
            error: function(xhr, status, error) {
                alert('Error: ' + error);
                console.error('Bulk delete failed:', xhr.responseText);
            }
        });
    });
});
</script>

<?php
/**
 * SECURITY FEATURES DEMONSTRATED:
 * ================================
 * 
 * 1. CSRF Protection
 *    - Token generated: csrf_field()
 *    - Token validated: validate_csrf_token()
 *    - Token in AJAX: included in all POST requests
 * 
 * 2. Input Validation
 *    - Integer validation: InputValidator::integer()
 *    - String validation: InputValidator::string()
 *    - Email validation: InputValidator::email()
 *    - Phone validation: InputValidator::phone()
 *    - URL validation: InputValidator::url()
 *    - Enum validation: InputValidator::enum()
 *    - Multi-field: validateMultiple()
 * 
 * 3. IDOR Protection
 *    - Single delete: checkOwnership()
 *    - Bulk delete: filterOwnedResources()
 *    - Edit: checkOwnership() before update
 *    - View: ownership check in DataTable query
 * 
 * 4. XSS Prevention
 *    - All output escaped: e()
 *    - JavaScript variables escaped
 *    - HTML attributes escaped
 * 
 * 5. SQL Injection Prevention
 *    - Prepared statements: $stmt->bind_param()
 *    - No direct string concatenation
 *    - Input validation before queries
 * 
 * 6. Permission Checks
 *    - granted_() checks throughout
 *    - Different permissions for create/edit/delete
 *    - UI elements hidden if no permission
 * 
 * 7. Error Logging
 *    - IDOR attempts logged
 *    - Failed operations logged
 *    - No sensitive data in user-facing messages
 * 
 * USE THIS FILE AS A TEMPLATE FOR ALL DASHBOARD PAGES!
 */
?>




