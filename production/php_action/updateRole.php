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
    
    // Validate input
    $roleId = $_POST['roleId'];
    $roleName = trim($_POST['roleName']);
    $description = trim($_POST['description']);
    $permissions = isset($_POST['editPermissions']) ? $_POST['editPermissions'] : array();

    // Prevent editing admin role
    if ($roleId == 1) {
        $response['success'] = false;
        $response['message'] = 'Cannot modify admin role';
        echo json_encode($response);
        exit();
    }

    // Validate role name
    if (strlen($roleName) < 3) {
        $response['success'] = false;
        $response['message'] = 'Role name must be at least 3 characters long';
        echo json_encode($response);
        exit();
    }

    // Validate permissions
    if (empty($permissions)) {
        $response['success'] = false;
        $response['message'] = 'Please select at least one permission';
        echo json_encode($response);
        exit();
    }

    // Check if role name exists for other roles
    $sql = "SELECT * FROM user_roles WHERE role_name = ? AND role_id != ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("si", $roleName, $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['success'] = false;
        $response['message'] = 'Role name already exists';
        echo json_encode($response);
        exit();
    }

    // Start transaction
    $connect->begin_transaction();

    try {
        // Update role information
        $sql = "UPDATE user_roles SET role_name = ?, description = ? WHERE role_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("ssi", $roleName, $description, $roleId);
        
        if (!$stmt->execute()) {
            throw new Exception('Error updating role: ' . $connect->error);
        }

        // Delete existing permissions
        $sql = "DELETE FROM role_permissions WHERE role_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $roleId);
        
        if (!$stmt->execute()) {
            throw new Exception('Error removing old permissions: ' . $connect->error);
        }

        // Insert new permissions
        $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
        $stmt = $connect->prepare($sql);

        foreach ($permissions as $permission) {
            // Get permission ID from name
            $permSql = "SELECT permission_id FROM permissions WHERE permission_name = ?";
            $permStmt = $connect->prepare($permSql);
            $permStmt->bind_param("s", $permission);
            $permStmt->execute();
            $permResult = $permStmt->get_result();
            
            if ($permResult->num_rows > 0) {
                $permRow = $permResult->fetch_assoc();
                $permissionId = $permRow['permission_id'];
                
                $stmt->bind_param("ii", $roleId, $permissionId);
                if (!$stmt->execute()) {
                    throw new Exception('Error assigning permissions: ' . $connect->error);
                }
            }
        }

        // Log the action
        $logSql = "INSERT INTO audit_log (user_id, activity_type, description, ip_address) VALUES (?, 'update_role', ?, ?)";
        $logStmt = $connect->prepare($logSql);
        $actorId = $_SESSION['userId'];
        $description = "Updated role: " . $roleName;
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $logStmt->bind_param("iss", $actorId, $description, $ipAddress);
        
        if (!$logStmt->execute()) {
            throw new Exception('Error logging action: ' . $connect->error);
        }

        // Commit transaction
        $connect->commit();

        $response['success'] = true;
        $response['message'] = 'Role updated successfully';

    } catch (Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    $stmt->close();
    $connect->close();

    echo json_encode($response);
} 