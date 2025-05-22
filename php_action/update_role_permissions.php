<?php
require_once 'db_connect.php';
require_once 'core.php';

header('Content-Type: application/json');

// Check if user is logged in and has permission
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Verify admin permission
$sql = "SELECT 1 FROM users u
        JOIN user_roles r ON u.role_id = r.role_id
        JOIN role_permissions rp ON r.role_id = rp.role_id
        JOIN permissions p ON rp.permission_id = p.permission_id
        WHERE u.user_id = ? AND p.permission_name = 'admin.manage_permissions'
        LIMIT 1";

$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $_SESSION['userId']);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Update role permissions
if (isset($_POST['role_id']) && isset($_POST['permissions'])) {
    $role_id = intval($_POST['role_id']);
    $permissions = json_decode($_POST['permissions']);
    
    if (!is_array($permissions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid permissions format']);
        exit();
    }
    
    try {
        // Start transaction
        $connect->begin_transaction();
        
        // Delete existing permissions
        $stmt = $connect->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmt->bind_param('i', $role_id);
        $stmt->execute();
        
        // Insert new permissions
        if (!empty($permissions)) {
            $stmt = $connect->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($permissions as $permission_id) {
                $stmt->bind_param('ii', $role_id, $permission_id);
                $stmt->execute();
            }
        }
        
        // Commit transaction
        $connect->commit();
        
        echo json_encode(['success' => true, 'message' => 'Permissions updated successfully']);
    } catch (Exception $e) {
        // Rollback on error
        $connect->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
} 