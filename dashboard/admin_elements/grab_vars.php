<?php
/**
 * ============================================================================
 * REQUEST VARIABLE EXTRACTOR (LEGACY PATTERN)
 * ============================================================================
 * 
 * This file extracts commonly-used request parameters into global variables.
 * 
 * INCLUDED BY: admin_header.php (used on all dashboard pages)
 * 
 * SECURITY NOTE:
 * - Uses $_REQUEST which can be from GET, POST, or COOKIE
 * - Basic sanitization applied via trim()
 * - Individual files should validate/sanitize these values before use
 * - For new code, consider using filter_input() or direct $_GET/$_POST access
 * 
 * MODERNIZATION RECOMMENDATION:
 * - Gradually migrate to dependency injection or direct parameter access
 * - This pattern exists for backward compatibility with legacy code
 * 
 * ============================================================================
 */

// Uncomment for debugging request data
// print_r($_REQUEST);

/**
 * Extract common request parameters
 * Uses null coalescing operator for cleaner code
 */

// Action parameter - commonly used for CRUD operations (add_module, update_module, delete_module, etc.)
$action = isset($_REQUEST['action']) && !empty($_REQUEST['action']) 
    ? trim($_REQUEST['action']) 
    : '';

// ID parameter - commonly used for identifying specific records
$id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) 
    ? trim($_REQUEST['id']) 
    : '';

// Parent parameter - used for hierarchical data (parent categories, etc.)
$parent = isset($_REQUEST['parent']) && !empty($_REQUEST['parent']) 
    ? trim($_REQUEST['parent']) 
    : '';

/**
 * MULTI-SITE SUPPORT
 * Site parameter for multi-site/multi-tenant architecture
 * Currently defaults to primary site - can be extended for multiple sites
 */
$site = isset($_REQUEST['site']) && !empty($_REQUEST['site']) 
    ? trim($_REQUEST['site']) 
    : 'default';

/**
 * DEPRECATED/COMMENTED OUT PARAMETERS
 * These were previously extracted but are now handled individually by files that need them
 * Kept here for reference and future cleanup
 */

// Category ID - Most files that need this define it locally
// if (isset($_REQUEST['category_id']) && !empty($_REQUEST['category_id'])) { 
//     $category_id = trim($_REQUEST['category_id']); 
// } else { 
//     $category_id = ''; 
// }

// Pagination - Most files use DataTables server-side pagination now
// if (isset($_REQUEST['page']) && !empty($_REQUEST['page'])) { 
//     $page = (int)$_REQUEST['page']; 
// } else { 
//     $page = 1; 
// }

/**
 * USAGE WARNING:
 * Many modern files re-define these variables locally for better security.
 * Example: $action = !empty($_POST['action']) ? $_POST['action'] : '';
 * 
 * This is GOOD PRACTICE and should be encouraged for new development.
 * This file provides default values but doesn't prevent local overrides.
 */