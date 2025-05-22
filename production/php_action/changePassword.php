<?php
require_once 'core.php';
require_once 'db_connect.php';

if ($_POST) {
    $response = array();
    
    $userId = $_SESSION['userId'];
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    // Validate password length
    if (strlen($newPassword) < 6) {
        $response['success'] = false;
        $response['message'] = 'Password must be at least 6 characters long';
        echo json_encode($response);
        exit();
    }

    // Validate password format (at least one letter and one number)
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).+$/', $newPassword)) {
        $response['success'] = false;
        $response['message'] = 'Password must contain at least one letter and one number';
        echo json_encode($response);
        exit();
    }

    // Validate password confirmation
    if ($newPassword !== $confirmPassword) {
        $response['success'] = false;
        $response['message'] = 'Passwords do not match';
        echo json_encode($response);
        exit();
    }

    // Get current user's password
    $sql = "SELECT password FROM users WHERE user_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();

    // Verify current password
    if (!password_verify($currentPassword, $userData['password'])) {
        $response['success'] = false;
        $response['message'] = 'Current password is incorrect';
        echo json_encode($response);
        exit();
    }

    // Start transaction
    $connect->begin_transaction();

    try {
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password
        $sql = "UPDATE users SET password = ? WHERE user_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $userId);
        
        if (!$stmt->execute()) {
            throw new Exception('Error changing password: ' . $connect->error);
        }

        // Log the action
        $logSql = "INSERT INTO audit_log (user_id, activity_type, description, ip_address) VALUES (?, 'change_password', ?, ?)";
        $logStmt = $connect->prepare($logSql);
        $description = "Changed password";
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $logStmt->bind_param("iss", $userId, $description, $ipAddress);
        
        if (!$logStmt->execute()) {
            throw new Exception('Error logging action: ' . $connect->error);
        }

        // Commit transaction
        $connect->commit();

        $response['success'] = true;
        $response['message'] = 'Password changed successfully';

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