<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if current page requires specific permission
function checkPagePermission($requiredPermission) {
    if (!isset($_SESSION['userId'])) {
        header('location: login.php');
        exit();
    }

    if (!hasPermission($requiredPermission)) {
        header('location: access_denied.php');
        exit();
    }
}

// Function to get all permissions for a role
function getRolePermissions($roleId) {
    global $connect;
    
    $sql = "SELECT p.permission_name 
            FROM role_permissions rp 
            INNER JOIN permissions p ON rp.permission_id = p.permission_id 
            WHERE rp.role_id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $permissions = array();
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row['permission_name'];
    }
    
    return $permissions;
}
?> 