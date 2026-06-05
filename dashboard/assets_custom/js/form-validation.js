/**
 * Bootstrap Form Validation
 * Handles client-side form validation with visual feedback
 * Works with forms marked with novalidate attribute
 */

(function() {
    'use strict';
    
    window.addEventListener('load', function() {
        // Get all forms that should have validation
        const forms = document.querySelectorAll('form[novalidate]');
        
        // Loop and prevent submission
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);

    // Validation feedback helper function
    window.validateForm = function(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.classList.add('was-validated');
            return form.checkValidity();
        }
        return false;
    };

    // Clear validation on form reset
    document.addEventListener('reset', function(e) {
        const form = e.target.closest('form');
        if (form) {
            form.classList.remove('was-validated');
        }
    }, true);
})();
