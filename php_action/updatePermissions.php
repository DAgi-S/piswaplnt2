<?php
require_once 'core.php';
require_once 'middleware.php';

// Check if user has permission to manage roles
if(!hasPermission('manage_roles')) {
    echo json_encode(array('success' => false, 'message' => 'Access denied'));
    exit();
}

// Validate input
if(!isset($_POST['roleId']) || empty($_POST['roleId'])) {
    echo json_encode(array('success' => false, 'message' => 'Role ID is required'));
    exit();
}

if(!isset($_POST['permissions']) || !is_array($_POST['permissions'])) {
    echo json_encode(array('success' => false, 'message' => 'Permissions array is required'));
    exit();
}

$roleId = (int)$_POST['roleId'];
$permissions = $_POST['permissions'];

try {
    // Start transaction
    $connect->begin_transaction();
    
    // Check if role exists and is not the admin role (id = 1)
    if($roleId === 1) {
        throw new Exception("Cannot modify administrator role permissions");
    }
    
    $checkSql = "SELECT role_id FROM user_roles WHERE role_id = ?";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bind_param("i", $roleId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if($result->num_rows === 0) {
        throw new Exception("Role not found");
    }
    
    // Delete existing permissions for the role
    $deleteSql = "DELETE FROM role_permissions WHERE role_id = ?";
    $deleteStmt = $connect->prepare($deleteSql);
    $deleteStmt->bind_param("i", $roleId);
    $deleteStmt->execute();
    
    if(!empty($permissions)) {
        // Get permission IDs from names
        $placeholders = str_repeat('?,', count($permissions) - 1) . '?';
        $permSql = "SELECT permission_id FROM permissions WHERE permission_name IN ($placeholders)";
        $permStmt = $connect->prepare($permSql);
        
        $types = str_repeat('s', count($permissions));
        $permStmt->bind_param($types, ...$permissions);
        $permStmt->execute();
        $permResult = $permStmt->get_result();
        
        // Insert new permissions
        $insertSql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
        $insertStmt = $connect->prepare($insertSql);
        
        while($row = $permResult->fetch_assoc()) {
            $insertStmt->bind_param("ii", $roleId, $row['permission_id']);
            $insertStmt->execute();
        }
    }
    
    // Commit transaction
    $connect->commit();
    
    echo json_encode(array(
        'success' => true, 
        'message' => 'Permissions updated successfully'
    ));
} catch(Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    echo json_encode(array('success' => false, 'message' => 'Error updating permissions: ' . $e->getMessage()));
} 