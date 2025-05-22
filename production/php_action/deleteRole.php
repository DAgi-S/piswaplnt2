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

    // Prevent deleting admin role
    if ($roleId == 1) {
        $response['success'] = false;
        $response['message'] = 'Cannot delete admin role';
        echo json_encode($response);
        exit();
    }

    // Get role information before deletion for logging
    $sql = "SELECT role_name FROM user_roles WHERE role_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $roleData = $result->fetch_assoc();

    if (!$roleData) {
        $response['success'] = false;
        $response['message'] = 'Role not found';
        echo json_encode($response);
        exit();
    }

    // Check if role has assigned users
    $sql = "SELECT COUNT(*) as user_count FROM users WHERE role_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userCount = $result->fetch_assoc()['user_count'];

    if ($userCount > 0) {
        $response['success'] = false;
        $response['message'] = 'Cannot delete role: There are users assigned to this role';
        echo json_encode($response);
        exit();
    }

    // Start transaction
    $connect->begin_transaction();

    try {
        // Delete role permissions
        $sql = "DELETE FROM role_permissions WHERE role_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $roleId);
        
        if (!$stmt->execute()) {
            throw new Exception('Error deleting role permissions: ' . $connect->error);
        }

        // Delete role
        $sql = "DELETE FROM user_roles WHERE role_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $roleId);
        
        if (!$stmt->execute()) {
            throw new Exception('Error deleting role: ' . $connect->error);
        }

        // Log the action
        $logSql = "INSERT INTO audit_log (user_id, activity_type, description, ip_address) VALUES (?, 'delete_role', ?, ?)";
        $logStmt = $connect->prepare($logSql);
        $actorId = $_SESSION['userId'];
        $description = "Deleted role: " . $roleData['role_name'];
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $logStmt->bind_param("iss", $actorId, $description, $ipAddress);
        
        if (!$logStmt->execute()) {
            throw new Exception('Error logging action: ' . $connect->error);
        }

        // Commit transaction
        $connect->commit();

        $response['success'] = true;
        $response['message'] = 'Role deleted successfully';

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