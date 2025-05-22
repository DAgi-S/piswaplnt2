<?php
function checkApiPermission($permission) {
    // Check if user is logged in
    if(!isset($_SESSION['userId'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No active session']);
        exit();
    }

    // Check if user has admin role
    if(isAdminRole($_SESSION['roleId'])) {
        return true;
    }

    // Check specific permission
    if(!hasPermission($permission)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Permission denied: ' . $permission]);
        exit();
    }
    
    return true;
}

function hasPermission($permissionName) {
    global $connect;
    
    if (!isset($_SESSION['roleId'])) {
        error_log("No roleId in session");
        return false;
    }
    
    $roleId = (int)$_SESSION['roleId'];
    error_log("Checking permission '$permissionName' for role $roleId");
    
    // Check if user has admin role
    if (isAdminRole($roleId)) {
        error_log("User is admin, granting permission");
        return true;
    }
    
    try {
        $sql = "SELECT 1 
            FROM role_permissions rp 
            JOIN permissions p ON rp.permission_id = p.permission_id 
            WHERE rp.role_id = ? 
            AND p.permission_name = ?
            LIMIT 1";
                
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('is', $roleId, $permissionName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $has_permission = $result->num_rows > 0;
        error_log("Permission check result: " . ($has_permission ? 'true' : 'false'));
        
        return $has_permission;
    } catch (Exception $e) {
        error_log("Error checking permission: " . $e->getMessage());
        return false;
    }
}

function isAdminRole($roleId) {
    // Only grant admin privileges to specific admin role IDs
    // Assuming role_id 1 is the super admin
    return $roleId === 1;
}

function checkModulePermission($moduleName) {
    // Check if request is for API
    $is_api_request = strpos($_SERVER['REQUEST_URI'], '/api/') !== false;

    if (!isset($_SESSION['roleId'])) {
        if ($is_api_request) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No active session']);
            exit();
        } else {
            header('location: login.php');
            exit();
        }
    }

    // Check if user has admin role
    if (isAdminRole($_SESSION['roleId'])) {
        return true;
    }

    // Check specific permission
    if (!hasPermission($moduleName)) {
        if ($is_api_request) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Permission denied: ' . $moduleName]);
            exit();
        } else {
            // Check if headers have already been sent
            if (!headers_sent()) {
                header('location: access_denied.php');
                exit();
            } else {
                echo '<script>window.location.href = "access_denied.php";</script>';
                exit();
            }
        }
    }

    return true;
}

// Example usage in API endpoints:
// require_once 'middleware.php';
// checkApiPermission('create_user'); 