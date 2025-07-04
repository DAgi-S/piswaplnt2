<?php
/**
 * Role-based permission check system
 */

/**
 * Check if the current user has a specific permission
 * @param string $permission_name The name of the permission to check
 * @return bool True if user has permission, false otherwise
 */
function check_access($permission_name) {
    global $connect;
    
    // If no user is logged in, return false
    if (!isset($_SESSION['userId'])) {
        return false;
    }

    try {
        // Check if user has the permission through their role
        $sql = "SELECT 1 FROM users u
                JOIN user_roles ur ON u.role_id = ur.role_id
                JOIN role_permissions rp ON ur.role_id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.permission_id
                WHERE u.user_id = ? AND p.permission_name = ?
                LIMIT 1";
                
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            error_log("Error preparing statement: " . $connect->error);
            return false;
        }
        
        $stmt->bind_param('is', $_SESSION['userId'], $permission_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return true;
        }
        
        // If user doesn't have permission through role, check for direct user permissions
        $sql = "SELECT 1 FROM user_permissions up
                JOIN permissions p ON up.permission_id = p.permission_id
                WHERE up.user_id = ? AND p.permission_name = ?
                LIMIT 1";
                
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            error_log("Error preparing statement: " . $connect->error);
            return false;
        }
        
        $stmt->bind_param('is', $_SESSION['userId'], $permission_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return ($result && $result->num_rows > 0);
        
    } catch (Exception $e) {
        error_log("Error checking permission: " . $e->getMessage());
        return false;
    }
} 