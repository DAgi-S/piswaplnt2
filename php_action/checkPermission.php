<?php
function hasPermission($permissionName) {
    require 'db_connect.php';
    
    if(!isset($_SESSION['userId'])) return false;
    
    $userId = $_SESSION['userId'];
    
    // Get user's role
    $sql = "SELECT role_id FROM users WHERE user_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if(!$user) return false;
    
    // Check if role has permission
    $sql = "SELECT 1 FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ? AND p.permission_name = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $user['role_id'], $permissionName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

// Usage example:
// if(!hasPermission('create_letter')) {
//     header('Location: access_denied.php');
//     exit();
// } 