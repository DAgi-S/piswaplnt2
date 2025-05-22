<?php
require_once 'core.php';
require_once 'db_connect.php';

// Check if user has admin role
if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'admin') {
    echo json_encode(array('success' => false, 'message' => 'Access denied'));
    exit();
}

if ($_POST) {
    $response = array();
    
    $roleId = $_POST['roleId'];

    // Get role information
    $sql = "SELECT r.role_id, r.role_name, r.description 
            FROM user_roles r 
            WHERE r.role_id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $roleData = $result->fetch_assoc();

        // Get role permissions
        $sql = "SELECT p.permission_name 
                FROM role_permissions rp 
                JOIN permissions p ON rp.permission_id = p.permission_id 
                WHERE rp.role_id = ?";
                
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $roleId);
        $stmt->execute();
        $permResult = $stmt->get_result();
        
        $permissions = array();
        while ($row = $permResult->fetch_assoc()) {
            $permissions[] = $row['permission_name'];
        }

        $response['success'] = true;
        $response['data'] = array(
            'role_id' => $roleData['role_id'],
            'role_name' => $roleData['role_name'],
            'description' => $roleData['description'],
            'permissions' => $permissions
        );
    } else {
        $response['success'] = false;
        $response['message'] = 'Role not found';
    }

    $stmt->close();
    $connect->close();

    echo json_encode($response);
} 