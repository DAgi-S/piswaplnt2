<?php
require_once 'core.php';
require_once 'middleware.php';

// Check if user has permission to manage roles
if(!hasPermission('manage_roles')) {
    echo json_encode(array('success' => false, 'message' => 'Access denied'));
    exit();
}

// Check if role ID is provided
if(!isset($_POST['roleId'])) {
    echo json_encode(array('success' => false, 'message' => 'Role ID is required'));
    exit();
}

$roleId = (int)$_POST['roleId'];

try {
    // Get permissions for the role
    $sql = "SELECT p.permission_name 
            FROM permissions p 
            JOIN role_permissions rp ON p.permission_id = rp.permission_id 
            WHERE rp.role_id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $permissions = array();
    while($row = $result->fetch_assoc()) {
        $permissions[] = $row['permission_name'];
    }
    
    echo json_encode(array('success' => true, 'permissions' => $permissions));
} catch(Exception $e) {
    echo json_encode(array('success' => false, 'message' => 'Error fetching permissions: ' . $e->getMessage()));
} 