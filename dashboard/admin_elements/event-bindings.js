/**
 * Event Bindings Framework
 * 
 * Central event delegation system for dashboard interaction handlers.
 * Uses class-based binding with HTML5 data attributes instead of inline event handlers.
 * 
 * Pattern: <element class="handler-class" data-attribute="value">
 * The framework uses $(document).on() for event delegation, supporting both
 * existing and dynamically-added elements.
 * 
 * Benefits:
 * - Separation of concerns (HTML structure + behavior)
 * - Data-driven configuration via HTML5 attributes
 * - Automatic support for AJAX-inserted elements
 * - Easier testing and maintenance
 * - Better CSP (Content Security Policy) compliance
 */

$(function() {
  
  // ============================================================================
  // NAVIGATION & REDIRECTION HANDLERS
  // ============================================================================
  
  /**
   * Navigation link handler
   * Usage: <a class="nav-link" data-href="page.php">Click me</a>
   */
  $(document).on('click', '.nav-link', function(e) {
    e.preventDefault();
    var href = $(this).data('href');
    if (href) {
      window.location.href = href;
    }
  });
  
  /**
   * Reset form button handler
   * Usage: <button type="button" class="reset-form">Reset</button>
   * Resets all form inputs to their initial state
   */
  $(document).on('click', '.reset-form', function(e) {
    e.preventDefault();
    var $form = $(this).closest('form');
    if ($form.length > 0) {
      $form[0].reset();
    }
  });
  
  /**
   * Delete modal confirmation handler
   * Usage: <button class="confirm-delete" data-href="page.php?action=delete&id=123">Delete</button>
   * Opens a confirmation modal before navigating to deletion URL
   */
  $(document).on('click', '.confirm-delete', function(e) {
    e.preventDefault();
    var href = $(this).data('href');
    var itemId = $(this).data('id');
    
    if (href) {
      // URL-based deletion (navigate to URL)
      if (confirm('Are you sure you want to delete this item?')) {
        window.location.href = href;
      }
    } else if (typeof itemId !== 'undefined' && typeof window.deleteItem === 'function') {
      // Function-based deletion
      if (confirm('Are you sure you want to delete this item?')) {
        deleteItem(itemId);
      }
    }
  });
  
  /**
   * Form submission confirmation handler
   * Usage: <form class="confirm-form-submit" data-message="Execute now?">
   * Shows confirmation dialog before form submission
   */
  $(document).on('submit', '.confirm-form-submit', function(e) {
    var message = $(this).data('message') || 'Are you sure?';
    if (!confirm(message)) {
      e.preventDefault();
      return false;
    }
  });
  
  // ============================================================================
  // FORM INTERACTION HANDLERS
  // ============================================================================
  
  /**
   * Password strength indicator
   * Usage: <input type="password" class="password-input" data-strength-target="#strength-div">
   * Calls checkPasswordStrength() function on input to provide real-time feedback
   */
  $(document).on('keyup', '.password-input', function() {
    var strengthTarget = $(this).data('strength-target');
    
    if (strengthTarget) {
      // Call checkPasswordStrength with field ID
      if (typeof checkPasswordStrength === 'function') {
        checkPasswordStrength(strengthTarget.substring(1)); // Remove # for function call
      }
    } else {
      // Call with this element reference
      if (typeof checkPasswordStrength === 'function') {
        checkPasswordStrength(this);
      }
    }
  });
  
  /**
   * Child permission update handler
   * Updates parent checkbox based on child checkboxes
   * Usage: <input type="checkbox" class="child-permission" data-parent="#parent-checkbox">
   */
  $(document).on('change', '.child-permission', function() {
    var parentSelector = $(this).data('parent');
    if (parentSelector) {
      var $parent = $(parentSelector);
      var $siblings = $parent.closest('div').find('.child-permission');
      var allChecked = $siblings.length > 0 && $siblings.length === $siblings.filter(':checked').length;
      $parent.prop('checked', allChecked);
      
      // Call updatePermissionCount if it exists
      if (typeof updatePermissionCount === 'function') {
        updatePermissionCount();
      }
    }
  });
  
  /**
   * Parent permission update handler
   * Updates all child checkboxes based on parent
   * Usage: <input type="checkbox" class="parent-permission" data-target=".child-permission">
   */
  $(document).on('change', '.parent-permission', function() {
    var childSelector = $(this).data('target');
    var isChecked = this.checked;
    
    if (childSelector) {
      $(childSelector).prop('checked', isChecked);
      
      // Call updatePermissionCount if it exists
      if (typeof updatePermissionCount === 'function') {
        updatePermissionCount();
      }
    }
  });
  
  /**
   * Dropdown auto-submit handler
   * Usage: <select class="auto-submit">
   * Submits form when selection changes
   */
  $(document).on('change', 'select.auto-submit', function(e) {
    var $form = $(this).closest('form');
    if ($form.length > 0) {
      $form.submit();
    }
  });
  
  /**
   * Clear row item handler
   * Removes dynamic form row
   * Usage: <a class="clear-row-item" data-item-id="rowNumber">Remove</a>
   */
  $(document).on('click', '.clear-row-item', function(e) {
    e.preventDefault();
    var itemId = $(this).data('item-id');
    if (typeof itemId !== 'undefined' && typeof clear_row === 'function') {
      clear_row(itemId);
    }
  });
  
  /**
   * Delete photo handler
   * Combines form field reset with form submission for deletion
   * Usage: <button type="button" class="delete-photo" data-field-id="#photo_input">Delete</button>
   */
  $(document).on('click', '.delete-photo', function(e) {
    e.preventDefault();
    var fieldId = $(this).data('field-id');
    
    if (fieldId) {
      $(fieldId).val('');
      var $form = $(this).closest('form');
      if ($form.length > 0) {
        $form.submit();
      }
    }
  });
  
  // ============================================================================
  // AJAX & ASYNC HANDLERS
  // ============================================================================
  
  /**
   * AJAX checkbox handler
   * Usage: <input type="checkbox" class="ajax-checkbox" data-url="endpoint.php" data-item-id="123">
   * Submits checkbox state via AJAX when toggled
   */
  $(document).on('change', '.ajax-checkbox', function() {
    var url = $(this).data('url');
    var itemId = $(this).data('item-id');
    var isChecked = this.checked ? 1 : 0;
    
    if (url) {
      $.ajax({
        url: url,
        type: 'POST',
        data: {
          id: itemId,
          status: isChecked
        },
        success: function(response) {
          // Optional: Show success message
          console.log('Updated successfully');
        },
        error: function(xhr, status, error) {
          console.error('Error:', error);
        }
      });
    }
  });
  
  /**
   * AJAX dropdown handler
   * Usage: <select class="ajax-select" data-url="endpoint.php" data-item-id="123">
   * Submits selection via AJAX when changed
   */
  $(document).on('change', 'select.ajax-select', function() {
    var url = $(this).data('url');
    var itemId = $(this).data('item-id');
    var value = $(this).val();
    
    if (url) {
      $.ajax({
        url: url,
        type: 'POST',
        data: {
          id: itemId,
          value: value
        },
        success: function(response) {
          console.log('Updated successfully');
        },
        error: function(xhr, status, error) {
          console.error('Error:', error);
        }
      });
    }
  });
  
  /**
   * Delete item via AJAX
   * Usage: <button class="ajax-delete" data-url="endpoint.php" data-item-id="123">Delete</button>
   */
  $(document).on('click', '.ajax-delete', function(e) {
    e.preventDefault();
    var url = $(this).data('url');
    var itemId = $(this).data('item-id');
    var $row = $(this).closest('tr');
    
    if (url && confirm('Are you sure you want to delete this item?')) {
      $.ajax({
        url: url,
        type: 'POST',
        data: {
          id: itemId
        },
        success: function(response) {
          if ($row.length > 0) {
            $row.fadeOut(300, function() { 
              $(this).remove(); 
            });
          }
          console.log('Deleted successfully');
        },
        error: function(xhr, status, error) {
          console.error('Error:', error);
          alert('Error deleting item');
        }
      });
    }
  });
  
  // ============================================================================
  // MODAL & DIALOG HANDLERS
  // ============================================================================
  
  /**
   * Confirm delete modal handler
   * Usage: <button class="confirm-delete-modal" data-href="page.php?action=delete&id=123">Delete</button>
   * Opens Bootstrap modal with delete confirmation before redirecting
   */
  $(document).on('click', '.confirm-delete-modal', function(e) {
    e.preventDefault();
    var href = $(this).data('href');
    
    if (href) {
      // Show Bootstrap modal and set action
      var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
      deleteModal.show();
      
      // Set up delete confirmation button
      $('#confirmDeleteButton').off('click').on('click', function() {
        window.location.href = href;
      });
    }
  });
  
  /**
   * Modal trigger for confirmDeleteModal function
   * Usage: <button class="modal-delete" data-href="url.php?action=delete&id=123">Delete</button>
   * Calls confirmDeleteModal function if it exists
   */
  $(document).on('click', '.modal-delete', function(e) {
    e.preventDefault();
    var href = $(this).data('href');
    
    if (href && typeof confirmDeleteModal === 'function') {
      confirmDeleteModal(href);
    }
  });
  
});
