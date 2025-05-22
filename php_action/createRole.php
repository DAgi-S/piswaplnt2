<?php
require_once 'core.php';
require_once 'middleware.php';

// Check if user has permission to manage roles
if(!hasPermission('manage_roles')) {
    echo json_encode(array('success' => false, 'message' => 'Access denied'));
    exit();
}

// Validate input
if(!isset($_POST['roleName']) || empty($_POST['roleName'])) {
    echo json_encode(array('success' => false, 'message' => 'Role name is required'));
    exit();
}

$roleName = $connect->real_escape_string(trim($_POST['roleName']));
$roleDescription = isset($_POST['roleDescription']) ? $connect->real_escape_string(trim($_POST['roleDescription'])) : '';

try {
    // Check if role name already exists
    $checkSql = "SELECT role_id FROM user_roles WHERE role_name = ?";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bind_param("s", $roleName);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if($result->num_rows > 0) {
        echo json_encode(array('success' => false, 'message' => 'Role name already exists'));
        exit();
    }
    
    // Create new role
    $sql = "INSERT INTO user_roles (role_name, description) VALUES (?, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ss", $roleName, $roleDescription);
    
    if($stmt->execute()) {
        echo json_encode(array(
            'success' => true, 
            'message' => 'Role created successfully',
            'roleId' => $connect->insert_id
        ));
    } else {
        throw new Exception("Error creating role");
    }
} catch(Exception $e) {
    echo json_encode(array('success' => false, 'message' => 'Error creating role: ' . $e->getMessage()));
} 