<?php
require_once '../includes/core.php';
require_once '../../includes/db_connect.php';

// Set response header
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'messages' => ''
];

if(isset($_SESSION['guest_id']) && $_POST) {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    
    // Get current user data
    $sql = "SELECT password FROM guest_users WHERE guest_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $_SESSION['guest_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 1) {
        $userData = $result->fetch_assoc();
        
        // Verify current password
        if(password_verify($currentPassword, $userData['password'])) {
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $sql = "UPDATE guest_users SET password = ? WHERE guest_id = ?";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("ss", $hashedPassword, $_SESSION['guest_id']);
            
            if($stmt->execute()) {
                $response['success'] = true;
                $response['messages'] = "Password changed successfully";
                
                // Log the action
                $sql = "INSERT INTO audit_log (user_id, action) VALUES (?, ?)";
                $stmt = $connect->prepare($sql);
                $action = "Guest user password changed for guest ID: " . $_SESSION['guest_id'];
                $stmt->bind_param("is", $_SESSION['userId'], $action);
                $stmt->execute();
            } else {
                $response['success'] = false;
                $response['messages'] = "Error while updating password";
            }
        } else {
            $response['success'] = false;
            $response['messages'] = "Current password is incorrect";
        }
    } else {
        $response['success'] = false;
        $response['messages'] = "User not found";
    }
} else {
    $response['success'] = false;
    $response['messages'] = "Invalid request";
}

echo json_encode($response); 