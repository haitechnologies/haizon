<?php
/**
 * Deletion Manager Class
 * 
 * Centralized deletion logic with ownership verification, audit logging,
 * and consistent error handling across the entire dashboard.
 * 
 * This eliminates duplicate deletion code and ensures consistent behavior
 * across all CRUD operations in the dashboard.
 * 
 * @package Classes
 * @version 1.0.0
 * @author  HAIPULSE Development Team
 * @since   February 2026
 */

class DeletionManager
{
    /**
     * Database connection instance
     * @var mysqli
     */
    private static $mysqli = null;
    
    /**
     * Project prefix for sessions/globals
     * @var string
     */
    private static $project_pre = null;
    
    /**
     * Initialize the DeletionManager with database connection
     * 
     * Should be called once during application bootstrap
     * 
     * @param mysqli $mysqli Database connection
     * @param string $project_pre Project prefix from $GLOBALS
     * @return void
     */
    public static function init($mysqli, $project_pre = null)
    {
        self::$mysqli = $mysqli;
        self::$project_pre = $project_pre ?? $GLOBALS['project_pre'] ?? 'HAI';
    }
    
    /**
     * Safe delete a record with full permission & ownership validation
     * 
     * This method handles:
     * 1. Validates record ID (must be positive integer)
     * 2. Checks user has delete permission (via granted() function)
     * 3. Verifies user owns the record (unless they're a full-access admin)
     * 4. Retrieves record details before deletion
     * 5. Executes deletion
     * 6. Returns detailed result with success/error status
     * 
     * @param string $table_name        DB table constant (e.g., DB::BLOGS)
     * @param int $record_id            ID of record to delete
     * @param int $user_id              Current user ID
     * @param array $options            Optional configuration:
     *                                  - 'verify_field' => 'title' (field to fetch for logging)
     *                                  - 'module_slug' => 'blogs' (for permission check, auto-detected if not provided)
     *                                  - 'item_label' => 'Blog Post' (for user message)
     *                                  - 'cascade_deletes' => [DB::RELATED_TABLE] (tables to delete cascade from)
     * @return array                    Result array:
     *                                  [
     *                                    'success' => bool,
     *                                    'message' => string,
     *                                    'record_data' => array or null,
     *                                    'id' => int,
     *                                    'error_code' => string or null
     *                                  ]
     * 
     * @example
     * $result = DeletionManager::delete(
     *     DB::BLOGS,
     *     $blog_id,
     *     $session_user_id,
     *     [
     *         'verify_field' => 'title',
     *         'item_label' => 'Blog Post',
     *         'module_slug' => 'blogs'
     *     ]
     * );
     * 
     * if ($result['success']) {
     *     $success_message = $result['message'];
     *     header("Location: listing_blogs.php?msg=deleted");
     * } else {
     *     $error_message = $result['message'];
     * }
     */
    public static function delete(
        $table_name,
        $record_id,
        $user_id,
        $options = []
    ) {
        // Ensure manager is initialized
        if (self::$mysqli === null || self::$project_pre === null) {
            return self::error('Manager not initialized. Call DeletionManager::init() first.', 'NOT_INITIALIZED');
        }
        
        // Extract options with defaults
        $verify_field = $options['verify_field'] ?? 'title';
        $item_label = $options['item_label'] ?? 'Record';
        $cascade_deletes = $options['cascade_deletes'] ?? [];
        $module_slug = $options['module_slug'] ?? self::tableToModuleSlug($table_name);
        
        // 1. Validate record ID
        $record_id = (int)$record_id;
        if ($record_id <= 0) {
            return self::error('Invalid record ID.', 'INVALID_ID');
        }
        
        // 2. Check permission
        if (!function_exists('granted') || !granted('delete', $module_slug)) {
            return self::error(
                "You don't have permission to delete {$item_label}.",
                'PERMISSION_DENIED'
            );
        }
        
        try {
            // 3. Check ownership (unless full access)
            $current_role_id = $_SESSION[self::$project_pre]['DASHBOARD']['role_id'] ?? null;
            $has_full_access = Roles::hasFullAccess($current_role_id);
            
            $ownership_filter = '';
            if (!$has_full_access) {
                $ownership_filter = " AND created_by = " . (int)$user_id;
            }
            
            // 4. Verify record exists & get data for logging
            $verify_query = "SELECT * FROM `{$table_name}` WHERE id = {$record_id}{$ownership_filter} LIMIT 1";
            $verify_result = self::$mysqli->query($verify_query);
            
            if (!$verify_result) {
                return self::error("Database error: " . self::$mysqli->error, 'DB_ERROR');
            }
            
            if ($verify_result->num_rows === 0) {
                return self::error(
                    "{$item_label} not found or you don't have permission to delete it.",
                    'NOT_FOUND'
                );
            }
            
            $record_data = $verify_result->fetch_assoc();
            $record_label = $record_data[$verify_field] ?? "ID {$record_id}";
            
            // 5. Handle cascade deletes (if configured)
            if (!empty($cascade_deletes) && is_array($cascade_deletes)) {
                foreach ($cascade_deletes as $cascade_table => $cascade_foreign_key) {
                    $cascade_query = "DELETE FROM `{$cascade_table}` WHERE {$cascade_foreign_key} = {$record_id}";
                    self::$mysqli->query($cascade_query);
                    
                    // Log cascade delete error if it occurred
                    if (self::$mysqli->error) {
                        error_log("CASCADE_DELETE_ERROR: " . self::$mysqli->error . " | Table: {$cascade_table}");
                    }
                }
            }
            
            // 6. Execute deletion
            $delete_query = "DELETE FROM `{$table_name}` WHERE id = {$record_id}{$ownership_filter}";
            $delete_result = self::$mysqli->query($delete_query);
            
            if (!$delete_result) {
                return self::error("Failed to delete record: " . self::$mysqli->error, 'DELETE_FAILED');
            }
            
            // 7. Check if actually deleted
            if (self::$mysqli->affected_rows === 0) {
                return self::error(
                    "{$item_label} may have already been deleted.",
                    'NO_ROWS_DELETED'
                );
            }
            
            // 8. Log the action for audit trail
            self::logAction(
                $user_id,
                'DELETE',
                $module_slug,
                $record_id,
                "Deleted {$item_label}: {$record_label}"
            );
            
            // 9. Return success with record data
            return [
                'success' => true,
                'message' => "{$item_label} '{$record_label}' deleted successfully.",
                'record_data' => $record_data,
                'id' => $record_id,
                'error_code' => null
            ];
            
        } catch (Exception $e) {
            error_log(
                "DELETION_EXCEPTION: " . $e->getMessage() . 
                " | Table: {$table_name} | Record ID: {$record_id} | User ID: {$user_id}"
            );
            
            return self::error($e->getMessage(), 'EXCEPTION');
        }
    }
    
    /**
     * Log an action to the user_logs table
     * 
     * Note: The user_logs table has been deleted from the database.
     * This method is kept for backward compatibility but does not perform any operations.
     * 
     * @param int $user_id      User ID performing the action
     * @param string $action    Action type (DELETE, CREATE, UPDATE, etc.)
     * @param string $module    Module/controller name
     * @param int $record_id    Record ID being acted upon
     * @param string $description Detailed description of the action
     * @return bool Always returns true
     */
    private static function logAction($user_id, $action, $module, $record_id, $description)
    {
        // User logs table (hai_user_logs) has been removed from database
        // Logging functionality has been disabled
        return true;
    }
    
    /**
     * Create an error response array
     * 
     * @param string $message Error message
     * @param string $error_code Error code for debugging
     * @return array Error response
     */
    private static function error($message, $error_code)
    {
        return [
            'success' => false,
            'message' => $message,
            'record_data' => null,
            'id' => null,
            'error_code' => $error_code
        ];
    }
    
    /**
     * Convert database table name to module slug
     * 
     * Examples:
     * - hai_blogs â†’ blogs
     * - hai_customers â†’ customers
     * - hai_company_categories â†’ company_categories
     * 
     * @param string $table_name Full table name
     * @return string Module slug
     */
    private static function tableToModuleSlug($table_name)
    {
        // Remove prefix (hai_ or haipulse_)
        $slug = preg_replace('/^(hai_|haipulse_)/', '', $table_name);
        
        // Remove trailing 's' if it looks like a plural
        if (substr($slug, -1) === 's' && strlen($slug) > 3) {
            // Common exceptions that don't need 's' removed
            $exceptions = ['address', 'status', 'business', 'process'];
            if (!in_array(substr($slug, 0, -1), $exceptions)) {
                // Check if removing 's' still gives a valid word (simple heuristic)
                // For now, keep it simple and try to remove 's'
                $singular = substr($slug, 0, -1);
                
                // Don't remove 's' from short words or when it looks wrong
                if (strlen($singular) > 2) {
                    $slug = $singular;
                }
            }
        }
        
        return $slug;
    }
}


