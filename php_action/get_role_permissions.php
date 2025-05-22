<?php
require_once 'db_connect.php';
require_once 'core.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// Verify permissions
$sql = "SELECT 1 FROM users u
        JOIN user_roles r ON u.role_id = r.role_id
        JOIN role_permissions rp ON r.role_id = rp.role_id
        JOIN permissions p ON rp.permission_id = p.permission_id
        WHERE u.user_id = ? AND (
            p.permission_name IN ('role.view', 'view_role', 'permission.view', 'manage_roles', 'admin.manage_permissions')
        )
        LIMIT 1";

$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $_SESSION['userId']);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have permission to view role permissions']);
    exit();
}

// Get role permissions
if (isset($_GET['role_id'])) {
    $role_id = intval($_GET['role_id']);
    
    // Verify role exists
    $checkRole = "SELECT 1 FROM user_roles WHERE role_id = ? LIMIT 1";
    $stmtCheck = $connect->prepare($checkRole);
    $stmtCheck->bind_param('i', $role_id);
    $stmtCheck->execute();
    $stmtCheck->store_result();
    
    if ($stmtCheck->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Role not found']);
        exit();
    }
    
    $sql = "SELECT rp.permission_id, p.permission_name, p.permission_description 
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.permission_id
            WHERE rp.role_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = [
            'id' => $row['permission_id'],
            'name' => $row['permission_name'],
            'description' => $row['permission_description']
        ];
    }
    
    echo json_encode([
        'success' => true, 
        'permissions' => $permissions,
        'message' => 'Permissions retrieved successfully'
    ]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Role ID not provided']);
} 