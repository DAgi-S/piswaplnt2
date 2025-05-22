<?php
require_once 'core.php';
require_once 'db_connect.php';

// New permissions to add
$permissions = [
    ['view_accounts', 'Permission to view account list'],
    ['manage_accounts', 'Permission to manage accounts'],
    ['view_transactions', 'Permission to view account transactions'],
    ['manage_transactions', 'Permission to manage account transactions']
];

// Begin transaction
$connect->begin_transaction();

try {
    // Prepare insert statement
    $stmt = $connect->prepare("INSERT INTO permissions (permission_name, description) VALUES (?, ?)");
    
    // Add each permission
    foreach($permissions as $permission) {
        // Check if permission already exists
        $checkStmt = $connect->prepare("SELECT permission_id FROM permissions WHERE permission_name = ?");
        $checkStmt->bind_param("s", $permission[0]);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if($result->num_rows === 0) {
            // Permission doesn't exist, add it
            $stmt->bind_param("ss", $permission[0], $permission[1]);
            $stmt->execute();
            echo "Added permission: " . $permission[0] . "\n";
        } else {
            echo "Permission already exists: " . $permission[0] . "\n";
        }
    }
    
    // Grant these permissions to admin role (role_id = 2)
    $adminRoleId = 2;
    
    // Get the permission IDs for the new permissions
    $permissionIds = [];
    foreach($permissions as $permission) {
        $checkStmt = $connect->prepare("SELECT permission_id FROM permissions WHERE permission_name = ?");
        $checkStmt->bind_param("s", $permission[0]);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if($row = $result->fetch_assoc()) {
            $permissionIds[] = $row['permission_id'];
        }
    }
    
    // Add permissions to admin role
    $rolePermStmt = $connect->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
    foreach($permissionIds as $permissionId) {
        // Check if role permission already exists
        $checkStmt = $connect->prepare("SELECT 1 FROM role_permissions WHERE role_id = ? AND permission_id = ?");
        $checkStmt->bind_param("ii", $adminRoleId, $permissionId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if($result->num_rows === 0) {
            $rolePermStmt->bind_param("ii", $adminRoleId, $permissionId);
            $rolePermStmt->execute();
            echo "Added permission ID " . $permissionId . " to admin role\n";
        } else {
            echo "Permission ID " . $permissionId . " already assigned to admin role\n";
        }
    }
    
    // Commit transaction
    $connect->commit();
    echo "Successfully added all account permissions\n";
    
} catch (Exception $e) {
    // Rollback on error
    $connect->rollback();
    echo "Error: " . $e->getMessage() . "\n";
}

$connect->close(); 