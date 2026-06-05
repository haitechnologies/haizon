/**
 * Keyboard Navigation & Shortcuts
 * Extracted from admin_header.php
 * Manages keyboard shortcuts and navigation improvements
 */

(function($) {
    $(document).on('keydown', function(e) {
        // Enter key behavior: move to next focusable element
        if (e.keyCode === 13) {
            var $canfocus = $(':focusable');
            var index = $canfocus.index(this) + 1;
            if (index >= $canfocus.length) {
                index = 0;
            }
            $canfocus.eq(index).focus();
            return false;
        }
    });

    // Additional keyboard shortcuts can be added here
    // Example:
    // Ctrl+S: Submit form
    // Ctrl+Q: Quick search
})(jQuery);
