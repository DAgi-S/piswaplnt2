<?php
/**
 * Cursor Rules for Permission Management
 * Based on the dashboard implementation pattern
 */

// Database Tables Structure
/*
Required Tables:
- permissions (permission_id, permission_name, description, module)
- roles (role_id, role_name, description)
- role_permissions (role_id, permission_id)
- user_roles (user_id, role_id)
- user_permissions (user_id, permission_id) - For direct user permissions
*/

/**
 * Permission Naming Convention Rules
 * Format: {module}.{action}[.{sub_action}]
 * Examples:
 * - dashboard.view
 * - dashboard.customize
 * - dashboard.analytics.sales
 * - dashboard.analytics.inventory
 * - dashboard.financial.overview
 */

/**
 * Core Permission Functions
 */

function hasPermission($permission_name) {
    global $connect;
    
    if (!isset($_SESSION['userId'])) {
        return false;
    }

    // Check both role-based and direct user permissions
    $sql = "SELECT 1
            FROM (
                SELECT permission_id 
                FROM role_permissions rp 
                JOIN user_roles ur ON rp.role_id = ur.role_id 
                WHERE ur.user_id = ?
                UNION
                SELECT permission_id 
                FROM user_permissions 
                WHERE user_id = ?
            ) user_perms
            JOIN permissions p ON user_perms.permission_id = p.permission_id
            WHERE p.permission_name = ?
            LIMIT 1";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param('iis', $_SESSION['userId'], $_SESSION['userId'], $permission_name);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

/**
 * Helper function for combined permission checks (new and legacy)
 */
function check_permission($new_perm, $legacy_perm = null) {
    if ($legacy_perm) {
        return hasPermission($new_perm) || hasPermission($legacy_perm);
    }
    return hasPermission($new_perm);
}

/**
 * Multiple permission checks
 */
function has_any_permission($permissions) {
    foreach ($permissions as $permission) {
        if (hasPermission($permission)) {
            return true;
        }
    }
    return false;
}

function has_all_permissions($permissions) {
    foreach ($permissions as $permission) {
        if (!hasPermission($permission)) {
            return false;
        }
    }
    return true;
}

/**
 * Module-specific permission initialization
 * Example based on dashboard implementation
 */
function initialize_module_permissions($module) {
    $permissions = array();
    
    switch ($module) {
        case 'dashboard':
            $permissions = array(
                'customize' => hasPermission('dashboard.customize'),
                'export' => hasPermission('dashboard.export'),
                
                'analytics' => array(
                    'sales' => check_permission('dashboard.analytics.sales', 'view_analytics'),
                    'inventory' => check_permission('dashboard.analytics.inventory', 'view_analytics'),
                    'revenue' => check_permission('dashboard.analytics.revenue', 'view_revenue'),
                    'export' => check_permission('dashboard.analytics.export', 'view_analytics')
                ),
                
                'inventory' => array(
                    'stock_level' => hasPermission('dashboard.inventory.stock_level'),
                    'movements' => hasPermission('dashboard.inventory.movements'),
                    'alerts' => hasPermission('dashboard.inventory.alerts'),
                    'low_stock' => check_permission('dashboard.inventory.alerts', 'view_low_stock')
                ),
                
                'financial' => array(
                    'overview' => hasPermission('dashboard.financial.overview'),
                    'profit_loss' => hasPermission('dashboard.financial.profit_loss'),
                    'expenses' => hasPermission('dashboard.financial.expenses'),
                    'revenue' => check_permission('dashboard.financial.overview', 'view_revenue')
                ),
                
                'reports' => array(
                    'view' => hasPermission('dashboard.reports.view'),
                    'generate' => hasPermission('dashboard.reports.generate'),
                    'schedule' => hasPermission('dashboard.reports.schedule')
                )
            );
            break;
            
        // Add more modules as needed
        
    }
    
    return $permissions;
}

/**
 * Page Access Control
 */
function check_page_access($page_name) {
    // Super Admin (role_id = 2) bypass
    if (isset($_SESSION['roleId']) && $_SESSION['roleId'] === 2) {
        return true;
    }
    
    // Define page permissions mapping
    $page_permissions = array(
        'dashboard.php' => 'dashboard.view',
        'user.php' => 'users.view',
        'role_management.php' => 'roles.manage',
        'product.php' => 'products.view',
        'invoice.php' => 'invoices.view',
        'reports.php' => 'reports.view'
        // Add more pages as needed
    );
    
    if (isset($page_permissions[$page_name])) {
        if (!hasPermission($page_permissions[$page_name])) {
            $_SESSION['error'] = "You don't have permission to access this page";
            header('Location: access_denied.php');
            exit();
        }
    }
    
    return true;
}

/**
 * API Permission Check
 */
function check_api_permission($permission_name) {
    if (!hasPermission($permission_name)) {
        http_response_code(403);
        echo json_encode(array(
            'success' => false,
            'message' => 'Permission denied: ' . $permission_name
        ));
        exit();
    }
    return true;
}

/**
 * Cache Management for Permissions
 */
function cache_user_permissions($user_id) {
    global $connect;
    
    // Get all user permissions (both role-based and direct)
    $sql = "SELECT DISTINCT p.permission_name
            FROM permissions p
            LEFT JOIN (
                SELECT permission_id 
                FROM role_permissions rp 
                JOIN user_roles ur ON rp.role_id = ur.role_id 
                WHERE ur.user_id = ?
                UNION
                SELECT permission_id 
                FROM user_permissions 
                WHERE user_id = ?
            ) user_perms ON p.permission_id = user_perms.permission_id
            WHERE user_perms.permission_id IS NOT NULL";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('ii', $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $permissions = array();
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row['permission_name'];
    }
    
    // Cache in session
    $_SESSION['user_permissions'] = $permissions;
    
    return $permissions;
}

/**
 * Clear Permission Cache
 */
function clear_permission_cache() {
    if (isset($_SESSION['user_permissions'])) {
        unset($_SESSION['user_permissions']);
    }
}

/**
 * Usage Examples:
 * 
 * 1. Basic Page Access:
 * if (!hasPermission('dashboard.view')) {
 *     header('Location: access_denied.php');
 *     exit();
 * }
 * 
 * 2. Feature-level Access:
 * $permissions = initialize_module_permissions('dashboard');
 * if ($permissions['analytics']['inventory']) {
 *     // Show inventory analytics
 * }
 * 
 * 3. API Endpoint:
 * check_api_permission('api.products.create');
 * 
 * 4. Multiple Permission Check:
 * if (has_any_permission(['reports.view', 'reports.create'])) {
 *     // Allow access
 * }
 * 
 * 5. Combined New/Legacy Check:
 * if (check_permission('dashboard.analytics.sales', 'view_analytics')) {
 *     // Show sales analytics
 * }
 */ 