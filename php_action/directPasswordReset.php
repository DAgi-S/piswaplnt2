<?php
require_once 'db_connect.php';
require_once 'core.php';

header('Content-Type: application/json');
$response = array('success' => false, 'messages' => '');

try {
    if (empty($_POST['email']) || empty($_POST['password'])) {
        throw new Exception("Email and password are required");
    }

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // First verify email exists and get current password
    $checkUser = $connect->prepare("SELECT user_id, password FROM users WHERE email = ?");
    $checkUser->bind_param("s", $email);
    $checkUser->execute();
    $result = $checkUser->get_result();
    
    if ($result->num_rows !== 1) {
        throw new Exception("Email not found");
    }
    
    $userData = $result->fetch_assoc();
    $userId = $userData['user_id'];
    
    // Hash password consistently with login method
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Update password
    $updatePass = $connect->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $updatePass->bind_param("si", $hashedPassword, $userId);
    
    if (!$updatePass->execute()) {
        throw new Exception("Failed to update password");
    }
    
    $response['success'] = true;
    $response['messages'] = "Password updated successfully. Please login with your new password.";

} catch (Exception $e) {
    $response['messages'] = $e->getMessage();
}

echo json_encode($response);
exit();
 