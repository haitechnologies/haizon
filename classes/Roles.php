<?php
/**
 * Role Constants Class
 * Centralized role ID definitions and helper methods
 * 
 * This eliminates hardcoded role IDs throughout the codebase
 * and provides a single source of truth for role management.
 * 
 * ============================================================================
 * HOW TO ADD A NEW ROLE
 * ============================================================================
 * 
 * When you create a new role in the database (hai_roles table), follow these
 * steps to add it to this class:
 * 
 * STEP 1: Add a new constant with the role ID
 * Example: const CUSTOMER_SERVICE = 6;
 * 
 * STEP 2: Add the role name to $role_names array
 * Example: self::CUSTOMER_SERVICE => 'Customer Service',
 * 
 * STEP 3: (Optional) Add a specific checker method
 * Example:
 *   public static function isCustomerService($role_id) {
 *       return $role_id == self::CUSTOMER_SERVICE;
 *   }
 * 
 * STEP 4: (Optional) If role needs full access, add to hasFullAccess() method
 * Example: return in_array($role_id, [self::SYSTEM_ADMIN, self::SUPER_ADMIN, self::CUSTOM_ADMIN]);
 * 
 * COMPLETE EXAMPLE:
 * -----------------
 * 1. Create role in database via dashboard/roles.php
 * 2. Note the auto-generated role ID (e.g., 6)
 * 3. Add to this class:
 * 
 *    const CUSTOMER_SERVICE = 6;  // Step 1
 * 
 *    private static $role_names = [
 *        ...
 *        self::CUSTOMER_SERVICE => 'Customer Service',  // Step 2
 *    ];
 * 
 *    public static function isCustomerService($role_id) {  // Step 3
 *        return $role_id == self::CUSTOMER_SERVICE;
 *    }
 * 
 * 4. Use anywhere in code:
 *    if (Roles::isCustomerService($user_role_id)) {
 *        // Customer service specific code
 *    }
 * 
 * NOTE: For dynamic roles that don't need constants in code, you can simply
 * use the database and check with getName($role_id) or isValid($role_id).
 * Only add constants for roles that are frequently referenced in code.
 * 
 * ============================================================================
 * 
 * @package HAIPULSE Dashboard
 * @version 1.1.0
 * @updated February 18, 2026
 */

class Roles 
{
    /**
     * ========================================================================
     * ROLE ID CONSTANTS
     * ========================================================================
     * Add new role constants here when creating new roles in database
     * Format: const ROLE_NAME = role_id_from_database;
     */
    const SYSTEM_ADMIN = 1;
    const SUPER_ADMIN = 2;
    const SALES = 3;
    const OPERATIONS = 4;
    const ACCOUNTS = 5;
    
    // Add new roles below (maintain numerical order by ID for clarity)
    // Examples:
    // const CUSTOMER_SERVICE = 6;
    // const WAREHOUSE = 7;
    // const FINANCE = 8;
    
    /**
     * ========================================================================
     * ROLE NAMES MAPPING
     * ========================================================================
     * Maps role IDs to display names
     * Add new roles here when adding new role constants above
     * 
     * Format: self::CONSTANT_NAME => 'Display Name',
     * 
     * @var array
     */
    private static $role_names = [
        self::SYSTEM_ADMIN => 'System Admin',
        self::SUPER_ADMIN => 'Super Admin',
        self::SALES => 'Sales',
        self::OPERATIONS => 'Operations',
        self::ACCOUNTS => 'Accounts'
        
        // Add new role names below (maintain same order as constants)
        // Examples:
        // self::CUSTOMER_SERVICE => 'Customer Service',
        // self::WAREHOUSE => 'Warehouse Manager',
        // self::FINANCE => 'Finance',
    ];
    
    /**
     * Cache for database-loaded roles (populated on first access)
     * Stores roles that exist in database but not defined as constants
     * 
     * @var array|null
     */
    private static $database_roles = null;
    
    /**
     * Get role name by ID
     * First checks static constants, then falls back to database
     * 
     * @param int $role_id The role ID
     * @return string The role name or 'Unknown' if not found
     */
    public static function getName($role_id) 
    {
        // First check static role names
        if (isset(self::$role_names[$role_id])) {
            return self::$role_names[$role_id];
        }
        
        // Fall back to database for dynamic roles
        self::loadDatabaseRoles();
        
        return self::$database_roles[$role_id] ?? 'Unknown Role';
    }
    
    /**
     * Get all role names (both static and from database)
     * 
     * @return array Associative array of role_id => role_name
     */
    public static function getAllNames() 
    {
        self::loadDatabaseRoles();
        
        // Merge static and database roles
        return array_merge(self::$role_names, self::$database_roles ?? []);
    }
    
    /**
     * Load roles from database (for roles not defined as constants)
     * This allows supporting dynamic roles without updating this class
     * 
     * @return void
     */
    private static function loadDatabaseRoles()
    {
        // Only load once per request
        if (self::$database_roles !== null) {
            return;
        }
        
        self::$database_roles = [];
        
        // Only load if database connection exists
        if (!isset($GLOBALS['DB']['MSQLI'])) {
            return;
        }
        
        try {
            $mysqli = $GLOBALS['DB']['MSQLI'];
            
            // Get all roles from database that aren't already in static array
            $static_ids = array_keys(self::$role_names);
            $placeholders = implode(',', array_fill(0, count($static_ids), '?'));
            
            $stmt = $mysqli->prepare("
                SELECT id, role_name 
                FROM " . DB::ROLES . " 
                WHERE id NOT IN ($placeholders) 
                AND publish = 1
            ");
            
            if ($stmt) {
                // Bind all static IDs
                $types = str_repeat('i', count($static_ids));
                $stmt->bind_param($types, ...$static_ids);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    self::$database_roles[$row['id']] = $row['role_name'];
                }
                
                $stmt->close();
            }
        } catch (Exception $e) {
            // Silently fail - database roles are optional
            self::$database_roles = [];
        }
    }
    
    /**
     * Check if a role ID has full system access
     * System Admin and Super Admin have unrestricted access
     * 
     * If you create a new admin-level role, add it to the array below
     * Example: return in_array($role_id, [self::SYSTEM_ADMIN, self::SUPER_ADMIN, self::IT_ADMIN]);
     * 
     * @param int $role_id The role ID to check
     * @return bool True if role has full access, false otherwise
     */
    public static function hasFullAccess($role_id) 
    {
        return in_array($role_id, [self::SYSTEM_ADMIN, self::SUPER_ADMIN]);
    }
    
    /**
     * Validate if a role ID exists (in constants or database)
     * 
     * @param int $role_id The role ID to validate
     * @return bool True if valid role ID, false otherwise
     */
    public static function isValid($role_id) 
    {
        // Check static roles first
        if (isset(self::$role_names[$role_id])) {
            return true;
        }
        
        // Check database roles
        self::loadDatabaseRoles();
        
        return isset(self::$database_roles[$role_id]);
    }
    
    /**
     * ========================================================================
     * ROLE-SPECIFIC CHECKER METHODS
     * ========================================================================
     * Add new checker methods here when you add new role constants
     * 
     * Template for new role checker:
     * 
     * public static function isYourRoleName($role_id) {
     *     return $role_id == self::YOUR_ROLE_NAME;
     * }
     * 
     * Usage example: if (Roles::isYourRoleName($user_role_id)) { ... }
     * ========================================================================
     */
    
    /**
     * Check if role is System Admin
     * 
     * @param int $role_id The role ID to check
     * @return bool True if System Admin, false otherwise
     */
    public static function isSystemAdmin($role_id) 
    {
        return $role_id == self::SYSTEM_ADMIN;
    }
    
    /**
     * Check if role is Super Admin
     * 
     * @param int $role_id The role ID to check
     * @return bool True if Super Admin, false otherwise
     */
    public static function isSuperAdmin($role_id) 
    {
        return $role_id == self::SUPER_ADMIN;
    }
    
    /**
     * Check if role is Sales
     * 
     * @param int $role_id The role ID to check
     * @return bool True if Sales role, false otherwise
     */
    public static function isSales($role_id) 
    {
        return $role_id == self::SALES;
    }
    
    /**
     * Check if role is Operations
     * 
     * @param int $role_id The role ID to check
     * @return bool True if Operations role, false otherwise
     */
    public static function isOperations($role_id) 
    {
        return $role_id == self::OPERATIONS;
    }
    
    /**
     * Check if role is Accounts
     * 
     * @param int $role_id The role ID to check
     * @return bool True if Accounts role, false otherwise
     */
    public static function isAccounts($role_id) 
    {
        return $role_id == self::ACCOUNTS;
    }
    
    /**
     * Add new role checkers below (follow the same pattern)
     * ========================================================================
     * 
     * Example for Customer Service role:
     * 
     * public static function isCustomerService($role_id) {
     *     return $role_id == self::CUSTOMER_SERVICE;
     * }
     * 
     * Example for Warehouse role:
     * 
     * public static function isWarehouse($role_id) {
     *     return $role_id == self::WAREHOUSE;
     * }
     * 
     * ========================================================================
     */
    
    /**
     * Get current user's role ID from session
     * 
     * @param string $project_prefix Optional project prefix (defaults to global)
     * @return int|null Role ID or null if not logged in
     */
    public static function getCurrentRoleId($project_prefix = null) 
    {
        if ($project_prefix === null) {
            global $project_pre;
            $project_prefix = $project_pre;
        }
        
        return $_SESSION[$project_prefix]['DASHBOARD']['role_id'] ?? null;
    }
    
    /**
     * Check if current user has full access
     * 
     * @return bool True if current user is System/Super Admin
     */
    public static function currentUserHasFullAccess() 
    {
        $role_id = self::getCurrentRoleId();
        return $role_id ? self::hasFullAccess($role_id) : false;
    }
    
    /**
     * ========================================================================
     * ROLE RESTRICTION METHODS (PAGE ACCESS CONTROL)
     * ========================================================================
     * Use these methods to restrict page access based on roles
     * ========================================================================
     */

    /**
     * Require specific role(s) to access current page
     * If user doesn't have required role, shows 403 forbidden page and exits
     * 
     * @param int|array $required_roles Single role ID or array of allowed role IDs
     * @param string $message Optional custom error message
     * @param bool $log Whether to log unauthorized access (default: true)
     * @return void (exits script if access denied)
     * 
     * @example Single role:
     *   Roles::requireRole(Roles::SYSTEM_ADMIN);
     * 
     * @example Multiple roles:
     *   Roles::requireRole([Roles::SYSTEM_ADMIN, Roles::SUPER_ADMIN]);
     * 
     * @example Custom message:
     *   Roles::requireRole(Roles::SYSTEM_ADMIN, 'Only System Admin can manage modules');
     */
    public static function requireRole($required_roles, $message = null, $log = true) 
    {
        global $project_pre;
        
        // Get current user's role
        $current_role_id = $_SESSION[$project_pre]['DASHBOARD']['role_id'] ?? null;
        
        // If no role in session, redirect to login
        if (!$current_role_id) {
            header("Location: login.php?session_expired=1");
            exit;
        }
        
        // Normalize to array
        $required_roles = is_array($required_roles) ? $required_roles : [$required_roles];
        
        // Check if user has required role
        if (!in_array($current_role_id, $required_roles)) {
            
            // Log unauthorized access attempt
            if ($log && function_exists('log_error')) {
                $required_role_names = array_map(function($rid) {
                    return self::getName($rid);
                }, $required_roles);
                
                log_error(
                    'Unauthorized page access attempt', 
                    'WARNING', 
                    $_SERVER['PHP_SELF'] ?? 'unknown', 
                    0,
                    [
                        'user_id' => $_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? 'unknown',
                        'user_role' => self::getName($current_role_id),
                        'required_roles' => implode(', ', $required_role_names),
                        'requested_page' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]
                );
            }
            
            // Show 403 forbidden page
            self::showForbiddenPage($message, $required_roles);
            exit;
        }
    }

    /**
     * Require System Admin role
     * Shorthand for requireRole(Roles::SYSTEM_ADMIN)
     * 
     * @param string $message Optional custom error message
     * @return void
     */
    public static function requireSystemAdmin($message = null) 
    {
        self::requireRole(self::SYSTEM_ADMIN, $message ?? 'Only System Admin has access to this page');
    }

    /**
     * Require Super Admin role
     * Shorthand for requireRole(Roles::SUPER_ADMIN)
     * 
     * @param string $message Optional custom error message
     * @return void
     */
    public static function requireSuperAdmin($message = null) 
    {
        self::requireRole(self::SUPER_ADMIN, $message ?? 'Only Super Admin has access to this page');
    }

    /**
     * Require System Admin OR Super Admin role
     * Shorthand for requireRole([Roles::SYSTEM_ADMIN, Roles::SUPER_ADMIN])
     * 
     * @param string $message Optional custom error message
     * @return void
     */
    public static function requireAdminAccess($message = null) 
    {
        self::requireRole(
            [self::SYSTEM_ADMIN, self::SUPER_ADMIN], 
            $message ?? 'Only administrators have access to this page'
        );
    }

    /**
     * Require full access (System Admin or Super Admin)
     * Uses hasFullAccess() method for consistency
     * 
     * @param string $message Optional custom error message
     * @return void
     */
    public static function requireFullAccess($message = null) 
    {
        global $project_pre;
        
        $current_role_id = $_SESSION[$project_pre]['DASHBOARD']['role_id'] ?? null;
        
        if (!$current_role_id || !self::hasFullAccess($current_role_id)) {
            self::showForbiddenPage($message ?? 'Administrator access required');
            exit;
        }
    }

    /**
     * Check if current user has specific role(s)
     * Returns boolean instead of exiting (use for inline checks)
     * 
     * @param int|array $roles Single role ID or array of role IDs
     * @return bool True if user has one of the specified roles
     * 
     * @example
     *   if (Roles::currentUserHasRole(Roles::SYSTEM_ADMIN)) {
     *       // Show system admin menu
     *   }
     */
    public static function currentUserHasRole($roles) 
    {
        global $project_pre;
        
        $current_role_id = $_SESSION[$project_pre]['DASHBOARD']['role_id'] ?? null;
        
        if (!$current_role_id) {
            return false;
        }
        
        $roles = is_array($roles) ? $roles : [$roles];
        
        return in_array($current_role_id, $roles);
    }

    /**
     * Show 403 Forbidden page
     * 
     * @param string $message Error message to display
     * @param array $required_roles Optional array of required role IDs
     * @return void
     */
    private static function showForbiddenPage($message = null, $required_roles = null) 
    {
        // Include separate 403 forbidden page template
        $forbidden_page = __DIR__ . '/../dashboard/admin_elements/403_forbidden.php';
        
        if (file_exists($forbidden_page)) {
            // Pass variables to template
            $error_message = $message;
            $required_role_ids = $required_roles;
            include($forbidden_page);
        } else {
            // Fallback if template doesn't exist
            global $project_pre;
            
            http_response_code(403);
            
            $user_name = $_SESSION[$project_pre]['DASHBOARD']['name'] ?? 'User';
            $current_role = self::getName($_SESSION[$project_pre]['DASHBOARD']['role_id'] ?? 0);
            
            $required_text = '';
            if ($required_roles && is_array($required_roles)) {
                $role_names = array_map(function($rid) {
                    return self::getName($rid);
                }, $required_roles);
                $required_text = implode(' or ', $role_names);
            }
            
            if (!$message) {
                $message = $required_text 
                    ? "You need $required_text access to view this page" 
                    : 'You do not have permission to access this page';
            }
            
            echo '<!DOCTYPE html>';
            echo '<html lang="en">';
            echo '<head>';
            echo '<meta charset="UTF-8">';
            echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
            echo '<title>403 - Access Forbidden</title>';
            echo '<style>';
            echo 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }';
            echo '.error-container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; max-width: 500px; }';
            echo '.error-code { font-size: 72px; font-weight: 700; color: #dc3545; margin: 0; }';
            echo 'h1 { font-size: 24px; margin: 20px 0 10px; color: #333; }';
            echo 'p { color: #666; margin: 10px 0; }';
            echo '.btn { display: inline-block; padding: 10px 24px; margin: 20px 5px 0; background: #0d6efd; color: white; text-decoration: none; border-radius: 4px; }';
            echo '</style>';
            echo '</head>';
            echo '<body>';
            echo '<div class="error-container">';
            echo '<p class="error-code">403</p>';
            echo '<h1>Access Forbidden</h1>';
            echo '<p>' . htmlspecialchars($message) . '</p>';
            if ($required_text) {
                echo '<p style="font-size: 14px; margin-top: 20px;">';
                echo '<strong>Required:</strong> ' . htmlspecialchars($required_text) . '<br>';
                echo '<strong>Your Role:</strong> ' . htmlspecialchars($current_role);
                echo '</p>';
            }
            echo '<a href="index.php" class="btn">Go to Dashboard</a>';
            echo '<a href="javascript:history.back()" class="btn" style="background: #6c757d;">Go Back</a>';
            echo '</div>';
            echo '</body>';
            echo '</html>';
        }
        exit;
    }
}

