<?php
require_once 'core.php';
require_once 'middleware.php';

// Check if user has permission to manage roles
if(!hasPermission('manage_roles')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied: Insufficient permissions'
    ]);
    exit();
}

// Set headers
header('Content-Type: application/json');

// Validate input
if(!isset($_POST['role_id']) || !is_numeric($_POST['role_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Role ID is required'
    ]);
    exit();
}

if(!isset($_POST['roleName']) || empty(trim($_POST['roleName']))) {
    echo json_encode([
        'success' => false,
        'message' => 'Role name is required'
    ]);
    exit();
}

$roleId = intval($_POST['role_id']);
$roleName = $connect->real_escape_string(trim($_POST['roleName']));
$roleDescription = isset($_POST['roleDescription']) ? $connect->real_escape_string(trim($_POST['roleDescription'])) : '';

// Check if role exists and is not a system role (role_id <= 2)
$stmt = $connect->prepare("SELECT role_id FROM user_roles WHERE role_id = ?");
$stmt->bind_param("i", $roleId);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Role not found'
    ]);
    exit();
}

if($roleId <= 2) {
    echo json_encode([
        'success' => false,
        'message' => 'System roles cannot be modified'
    ]);
    exit();
}

try {
    // Check if the new role name already exists for other roles
    $checkStmt = $connect->prepare("SELECT role_id FROM user_roles WHERE role_name = ? AND role_id != ?");
    $checkStmt->bind_param("si", $roleName, $roleId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if($checkResult->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Role name already exists'
        ]);
        exit();
    }
    
    // Update role
    $updateStmt = $connect->prepare("UPDATE user_roles SET role_name = ?, description = ? WHERE role_id = ?");
    $updateStmt->bind_param("ssi", $roleName, $roleDescription, $roleId);
    
    if($updateStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => [
                'role_id' => $roleId,
                'role_name' => $roleName,
                'description' => $roleDescription
            ]
        ]);
    } else {
        throw new Exception("Failed to update role");
    }
} catch(Exception $e) {
    error_log("Error updating role: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error updating role: ' . $e->getMessage()
    ]);
} 