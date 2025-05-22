<?php
require_once 'core.php';
require_once 'middleware.php';

// Set headers
header('Content-Type: application/json');

// Check if user has permission to manage roles
if(!hasPermission('manage_roles')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied: Insufficient permissions'
    ]);
    exit();
}

// Validate input
if(!isset($_POST['role_id']) || !is_numeric($_POST['role_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Role ID is required'
    ]);
    exit();
}

$roleId = intval($_POST['role_id']);

try {
    // Start transaction
    $connect->begin_transaction();
    
    // Check if role exists
    $checkStmt = $connect->prepare("SELECT role_id FROM user_roles WHERE role_id = ?");
    $checkStmt->bind_param("i", $roleId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if($result->num_rows === 0) {
        throw new Exception("Role not found");
    }

    // Check if it's a system role (role_id <= 2)
    if($roleId <= 2) {
        throw new Exception("System roles cannot be deleted");
    }
    
    // Check if any users are assigned to this role
    $userStmt = $connect->prepare("SELECT COUNT(*) as count FROM users WHERE role_id = ?");
    $userStmt->bind_param("i", $roleId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userCount = $userResult->fetch_assoc()['count'];
    
    if($userCount > 0) {
        throw new Exception("Cannot delete role: There are " . $userCount . " user(s) assigned to this role");
    }
    
    // Delete role permissions first
    $deletePermStmt = $connect->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $deletePermStmt->bind_param("i", $roleId);
    
    if(!$deletePermStmt->execute()) {
        throw new Exception("Error deleting role permissions");
    }
    
    // Delete the role
    $deleteRoleStmt = $connect->prepare("DELETE FROM user_roles WHERE role_id = ?");
    $deleteRoleStmt->bind_param("i", $roleId);
    
    if(!$deleteRoleStmt->execute()) {
        throw new Exception("Error deleting role");
    }
    
    // Commit transaction
    $connect->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Role deleted successfully',
        'data' => [
            'role_id' => $roleId
        ]
    ]);
    
} catch(Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    
    error_log("Error deleting role: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 