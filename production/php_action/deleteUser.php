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
    
    $userId = $_POST['userId'];

    // Prevent deleting admin user (user_id = 1)
    if ($userId == 1) {
        $response['success'] = false;
        $response['message'] = 'Cannot delete admin user';
        echo json_encode($response);
        exit();
    }

    // Get user information before deletion for logging
    $sql = "SELECT username FROM users WHERE user_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();

    if (!$userData) {
        $response['success'] = false;
        $response['message'] = 'User not found';
        echo json_encode($response);
        exit();
    }

    // Start transaction
    $connect->begin_transaction();

    try {
        // Delete user
        $sql = "DELETE FROM users WHERE user_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $userId);
        
        if (!$stmt->execute()) {
            throw new Exception('Error deleting user: ' . $connect->error);
        }

        // Log the action
        $logSql = "INSERT INTO audit_log (user_id, activity_type, description, ip_address) VALUES (?, 'delete_user', ?, ?)";
        $logStmt = $connect->prepare($logSql);
        $actorId = $_SESSION['userId'];
        $description = "Deleted user: " . $userData['username'];
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $logStmt->bind_param("iss", $actorId, $description, $ipAddress);
        
        if (!$logStmt->execute()) {
            throw new Exception('Error logging action: ' . $connect->error);
        }

        // Commit transaction
        $connect->commit();

        $response['success'] = true;
        $response['message'] = 'User deleted successfully';

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