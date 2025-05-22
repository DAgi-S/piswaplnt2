<?php
require_once 'core.php';
require_once 'db_connect.php';

// Check for either new granular permission or legacy permission
if(!hasPermission('role.view') && !hasPermission('view_role') && !hasPermission('manage_roles')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access Denied: Insufficient permissions'
    ]);
    exit();
}

$response = ['success' => false, 'messages' => '', 'data' => []];

try {
    // Fetch all roles from user_roles table
    $stmt = $connect->prepare("
        SELECT role_id, role_name, description, created_at 
        FROM user_roles 
        ORDER BY role_id ASC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $roles = [];
    while($row = $result->fetch_assoc()) {
        $roles[] = [
            'role_id' => $row['role_id'],
            'role_name' => $row['role_name'],
            'description' => $row['description'],
            'created_at' => $row['created_at']
        ];
    }
    
    $response['success'] = true;
    $response['data'] = $roles;
    
} catch(Exception $e) {
    $response['messages'] = "Error: " . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
