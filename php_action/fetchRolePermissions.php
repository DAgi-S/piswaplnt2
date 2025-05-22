<?php
require_once 'core.php';
require_once 'db_connect.php';

// Check for either new granular permission or legacy permission
if(!hasPermission('role.view') && !hasPermission('view_role') && !hasPermission('permission.view') && !hasPermission('manage_roles')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access Denied: Insufficient permissions'
    ]);
    exit();
}

// Validate role_id parameter
if(!isset($_GET['role_id']) || !is_numeric($_GET['role_id'])) {
    echo json_encode([
        'success' => false,
        'messages' => 'Invalid role ID'
    ]);
    exit();
}

$roleId = intval($_GET['role_id']);
$response = ['success' => false, 'messages' => '', 'data' => []];

try {
    // First check if the role exists
    $roleStmt = $connect->prepare("SELECT role_name FROM user_roles WHERE role_id = ?");
    $roleStmt->bind_param("i", $roleId);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result();
    
    if($roleResult->num_rows === 0) {
        throw new Exception("Role not found");
    }

    // Fetch all unique permissions and check if they are assigned to the role
    $stmt = $connect->prepare("
        SELECT DISTINCT
            p.permission_id,
            p.permission_name,
            p.description,
            p.module,
            CASE WHEN rp.role_id IS NOT NULL THEN 1 ELSE 0 END as assigned
        FROM permissions p
        LEFT JOIN role_permissions rp ON p.permission_id = rp.permission_id AND rp.role_id = ?
        GROUP BY p.permission_id
        ORDER BY p.module, p.permission_name
    ");
    
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $permissions = [];
    $seenPermissions = []; // Track seen permissions to prevent duplicates
    
    while($row = $result->fetch_assoc()) {
        // Only add permission if we haven't seen it before
        if (!isset($seenPermissions[$row['permission_name']])) {
            $permissions[] = [
                'permission_id' => $row['permission_id'],
                'permission_name' => $row['permission_name'],
                'description' => $row['description'],
                'module' => $row['module'],
                'assigned' => (bool)$row['assigned']
            ];
            $seenPermissions[$row['permission_name']] = true;
        }
    }
    
    $response['success'] = true;
    $response['data'] = $permissions;
    
} catch(Exception $e) {
    $response['messages'] = "Error: " . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 