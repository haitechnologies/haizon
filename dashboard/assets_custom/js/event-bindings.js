/**
 * Dashboard Event Bindings
 * 
 * Purpose: Centralized event handler management
 * Moved from inline HTML attributes to this file for:
 * - Better maintainability
 * - Code separation (HTML vs behavior)
 * - Easier testing and debugging
 * - Performance (external JS file can be cached)
 * - WCAG W3 HTML validation compliance
 * 
 * Categories:
 * - Navigation & Redirection (17 handlers)
 * - Form Operations (5 handlers)
 * - Confirmations & Dialogs (2 handlers)
 * - AJAX & Asynchronous (2 handlers)
 * - Calculations & Real-time (8 handlers)
 * - Data Validation (3 handlers)
 * - Utility & UI Functions (25 handlers)
 * 
 * Last Updated: February 23, 2026
 */

$(document).ready(function() {
    
    // ==========================================
    // 1. NAVIGATION & REDIRECTION
    // ==========================================
    
    /**
     * Navigate to page when clicking nav link
     * Before: onclick="window.location.href='page.php?id=123'"
     * After:  class="nav-link" data-href="page.php?id=123"
     */
    $(document).on('click', '.nav-link[data-href]', function(e) {
        var href = $(this).data('href');
        if (href) {
            e.preventDefault();
            window.location.href = href;
        }
    });
    
    /**
     * Go back to previous page
     * Before: onclick="history.back(); return false;"
     * After:  class="nav-back"
     */
    $(document).on('click', '.nav-back', function(e) {
        e.preventDefault();
        history.back();
    });
    
    
    // ==========================================
    // 2. FORM OPERATIONS
    // ==========================================
    
    /**
     * Submit form
     * Before: onclick="this.form.submit()"
     * After:  class="submit-form"
     */
    $(document).on('click', '.submit-form', function(e) {
        e.preventDefault();
        $(this).closest('form').submit();
    });
    
    /**
     * Show/hide element by ID
     * Before: onclick="document.getElementById('element').style.display='...'"
     * After:  class="toggle-element" data-target="#element"
     */
    $(document).on('click', '.toggle-element', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        if (target) {
            var element = $(target)[0];
            if (element.style.display === 'none' || !element.style.display) {
                element.style.display = element.style.display === 'none' ? 'block' : 'none';
            }
        }
    });
    
    /**
     * Auto-submit form on change
     * Before: onchange="this.form.submit()"
     * After:  class="auto-submit-form"
     */
    $(document).on('change', '.auto-submit-form', function() {
        $(this).closest('form').submit();
    });
    
    
    // ==========================================
    // 3. CONFIRM/DIALOG HANDLERS
    // ==========================================
    
    /**
     * Confirm form submission
     * Before: onsubmit="return confirm('Message')"
     * After:  class="confirm-form-submit" data-message="Are you sure?"
     */
    $(document).on('submit', '.confirm-form-submit', function(e) {
        var message = $(this).data('message') || 'Are you sure?';
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
    
    /**
     * Confirm delete action with URL navigation
     * Before: onclick="if(confirm('Are you sure?')) { deleteItem(id); }"
     * After:  class="confirm-delete" data-id="123"
     * 
     * Also supports URL navigation:
     * Before: onclick="if(confirm('Are you sure?')) window.location.href='url?id=123'"
     * After:  class="confirm-delete" data-href="url?id=123"
     */
    $(document).on('click', '.confirm-delete', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this item?')) {
            var href = $(this).data('href');
            var id = $(this).data('id');
            
            if (href) {
                // Navigate to URL for server-side deletion
                window.location.href = href;
            } else if (id && typeof deleteItem === 'function') {
                // Call deleteItem function for AJAX deletion
                deleteItem(id);
            }
        }
    });
    
    /**
     * Confirm modal action
     * Before: onclick="confirmDeleteModal(id, name)"
     * After:  class="confirm-modal-delete" data-id="123" data-name="Item"
     */
    $(document).on('click', '.confirm-modal-delete', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var name = $(this).data('name') || 'item';
        if (typeof confirmDeleteModal === 'function') {
            confirmDeleteModal(id, name);
        }
    });
    
    
    // ==========================================
    // 4. AJAX & ASYNCHRONOUS OPERATIONS
    // ==========================================
    
    /**
     * Populate states based on country selection
     * Before: onchange="ajax_populate_states(this.value)"
     * After:  class="country-selector"
     */
    $(document).on('change', '.country-selector', function() {
        var countryValue = this.value;
        if (typeof ajax_populate_states === 'function') {
            ajax_populate_states(countryValue);
        }
    });
    
    /**
     * Populate item rate based on selection
     * Before: onchange="ajax_populate_item_rate(this.value, itemId)"
     * After:  class="item-selector" data-item-id="123"
     */
    $(document).on('change', '.item-selector', function() {
        var itemId = $(this).data('item-id');
        var value = this.value;
        if (typeof ajax_populate_item_rate === 'function') {
            ajax_populate_item_rate(value, itemId);
        }
    });
    
    
    // ==========================================
    // 5. CALCULATIONS & REAL-TIME UPDATES
    // ==========================================
    
    /**
     * Calculate item amount on value change
     * Before: onchange="calculateItemAmount(itemId, value)"
     *         onkeyup="calculateItemAmount(itemId, value)"
     * After:  class="calc-item" data-item-id="123"
     */
    $(document).on('change keyup', '.calc-item', function() {
        var itemId = $(this).data('item-id');
        var value = this.value;
        if (typeof calculateItemAmount === 'function') {
            calculateItemAmount(itemId, value);
        }
    });
    
    /**
     * Calculate grand total
     * Before: onchange="calculateGrand(itemId)"
     *         onkeyup="calculateGrand(itemId)"
     * After:  class="calc-grand" data-item-id="123"
     */
    $(document).on('change keyup', '.calc-grand', function() {
        var itemId = $(this).data('item-id');
        if (typeof calculateGrand === 'function') {
            calculateGrand(itemId);
        }
    });
    
    /**
     * Clear discount and remove hidden field value
     * Before: onclick="calculateItemAmount('itemId'); clear_row(itemId);"
     * After:  class="clear-row-item" data-item-id="123"
     */
    $(document).on('click', '.clear-row-item', function(e) {
        e.preventDefault();
        var itemId = $(this).data('item-id');
        if (typeof calculateItemAmount === 'function') {
            calculateItemAmount(itemId);
        }
        if (typeof clear_row === 'function') {
            clear_row(itemId);
        }
    });
    
    /**
     * Set invoice status and submit
     * Before: onclick="document.getElementById('invoice_status').value='draft'; this.form.submit();"
     * After:  class="save-draft-invoice"
     */
    $(document).on('click', '.save-draft-invoice', function(e) {
        e.preventDefault();
        document.getElementById('invoice_status').value = 'draft';
        $(this).closest('form').submit();
    });
    
    /**
     * Set save_and_send flag and submit
     * Before: onclick="document.getElementById('save_and_send').value='1'; ... this.form.submit();"
     * After:  class="save-and-send-invoice"
     */
    $(document).on('click', '.save-and-send-invoice', function(e) {
        e.preventDefault();
        document.getElementById('save_and_send').value = '1';
        // Also set draft status for new invoices
        if (!document.getElementById('id') || !document.getElementById('id').value) {
            document.getElementById('invoice_status').value = 'draft';
        }
        $(this).closest('form').submit();
    });
    
    /**
     * Clear grand total discount and recalculate
     * Before: onchange="clearGrandDiscountTypeValue(); calculateGrand();"
     * After:  class="clear-discount"
     */
    $(document).on('change', '.clear-discount', function() {
        if (typeof clearGrandDiscountTypeValue === 'function') {
            clearGrandDiscountTypeValue();
        }
        if (typeof calculateGrand === 'function') {
            calculateGrand();
        }
    });
    
    /**
     * Update receivables chart
     * Before: onchange="updateReceivablesChart()"
     * After:  class="update-receivables"
     */
    $(document).on('change', '.update-receivables', function() {
        if (typeof updateReceivablesChart === 'function') {
            updateReceivablesChart();
        }
    });
    
    
    // ==========================================
    // 6. DATA VALIDATION & INPUT HANDLING
    // ==========================================
    
    /**
     * Check password strength in real-time
     * Before: onkeyup="checkPasswordStrength(this)" OR onkeyup="checkPasswordStrength('fieldId')"
     * After:  class="password-input" OR class="password-input" data-strength-target="#fieldId"
     */
    $(document).on('keyup', '.password-input', function() {
        if (typeof checkPasswordStrength === 'function') {
            var fieldId = $(this).data('strength-target');
            if (fieldId) {
                // Call with field ID
                checkPasswordStrength(fieldId.replace('#', ''));
            } else {
                // Call with element itself
                checkPasswordStrength(this);
            }
        }
    });
    
    /**
     * Filter list based on search input
     * Before: onkeyup="filterModules()"
     * After:  class="filter-search"
     */
    $(document).on('keyup', '.filter-search', function() {
        if (typeof filterModules === 'function') {
            filterModules();
        }
    });
    
    
    // ==========================================
    // 7. UTILITY & UI FUNCTIONS
    // ==========================================
    
    /**
     * Add new item row to form
     * Before: onclick="add_item_row()"
     * After:  class="add-item-row"
     */
    $(document).on('click', '.add-item-row', function(e) {
        e.preventDefault();
        if (typeof add_item_row === 'function') {
            add_item_row();
        }
    });
    
    /**
     * Reset form to default state
     * Before: onclick="resetForm()"
     * After:  class="reset-form"
     */
    $(document).on('click', '.reset-form', function(e) {
        e.preventDefault();
        if (typeof resetForm === 'function') {
            resetForm();
        }
    });
    
    /**
     * Select all permissions
     * Before: onclick="selectAllPermissions()"
     * After:  class="select-all-permissions"
     */
    $(document).on('click', '.select-all-permissions', function(e) {
        e.preventDefault();
        if (typeof selectAllPermissions === 'function') {
            selectAllPermissions();
        }
    });
    
    /**
     * Clear all permissions
     * Before: onclick="clearAllPermissions()"
     * After:  class="clear-all-permissions"
     */
    $(document).on('click', '.clear-all-permissions', function(e) {
        e.preventDefault();
        if (typeof clearAllPermissions === 'function') {
            clearAllPermissions();
        }
    });
    
    /**
     * Check/uncheck all checkboxes in group
     * Before: onclick="check_uncheck_all(selector, isChecked)"
     * After:  class="check-uncheck-all" data-target="input[name='permissions[]']"
     */
    $(document).on('change', '.check-uncheck-all', function() {
        var target = $(this).data('target');
        if (target) {
            $(target).prop('checked', this.checked);
        }
    });
    
    /**
     * Update parent checkbox based on child checkboxes
     * Before: onclick="update_parent_checkbox(selector, parentId); updatePermissionCount();"
     * After:  class="child-permission" data-parent="#parentPermission"
     */
    $(document).on('change', '.child-permission', function() {
        var parentId = $(this).data('parent');
        if (typeof update_parent_checkbox === 'function') {
            update_parent_checkbox(null, parentId);
        }
        if (typeof updatePermissionCount === 'function') {
            updatePermissionCount();
        }
    });
    
    /**
     * Delete photo and submit form
     * Before: onclick="document.getElementById('delete_photo').value=1; this.form.submit();"
     * After:  class="delete-photo"
     */
    $(document).on('click', '.delete-photo', function(e) {
        e.preventDefault();
        var fieldId = $(this).data('field-id') || 'delete_photo';
        document.getElementById(fieldId).value = 1;
        $(this).closest('form').submit();
    });
    
});


