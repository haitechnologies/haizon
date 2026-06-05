/**
 * Input Mask Configuration
 * Extracted from admin_header.php
 * Manages input mask patterns for formatted fields (phone, SSN, etc.)
 */

(function($) {
    $(function() {
        // Initialize all data-inputmask elements
        $(":input[data-inputmask-mask]").inputmask();
        $(":input[data-inputmask-alias]").inputmask();
        $(":input[data-inputmask-regex]").inputmask("Regex");

        // Additional patterns can be added here as needed
        // Example:
        // $("#phone").inputmask("(999) 999-9999");
        // $("#zip").inputmask("99999-9999");
    });
})(jQuery);
