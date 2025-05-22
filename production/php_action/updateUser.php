<?php
require_once 'core.php';
require_once 'db_connect.php';

// Check if user has admin role
if (!isset($_SESSION['roleId']) || $_SESSION['roleId'] != 2) {
    echo json_encode(array('success' => false, 'messages' => 'Access denied'));
    exit();
}

if ($_POST) {
    $userId = $_POST['userId'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $roleId = $_POST['roleId'];
    $status = isset($_POST['status']) ? $_POST['status'] : 1;
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    // Basic validation
    if (empty($username) || empty($email) || empty($roleId)) {
        echo json_encode(array('success' => false, 'messages' => 'Required fields cannot be empty'));
        exit();
    }
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(array('success' => false, 'messages' => 'Invalid email format'));
        exit();
    }
    
    // Check if username exists for other users
    $stmt = $connect->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
    $stmt->bind_param("si", $username, $userId);
    $stmt->execute();
    if($stmt->get_result()->num_rows > 0) {
        echo json_encode(array('success' => false, 'messages' => 'Username already exists'));
        exit();
    }
    
    // Check if email exists for other users
    $stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    if($stmt->get_result()->num_rows > 0) {
        echo json_encode(array('success' => false, 'messages' => 'Email already exists'));
        exit();
    }
    
    // Prepare base update query
    $sql = "UPDATE users SET 
            username = ?, 
            email = ?, 
            role_id = ?, 
            status = ?, 
            full_name = ?, 
            phone = ?";
    $types = "ssiiss";
    $params = array($username, $email, $roleId, $status, $fullName, $phone);
    
    // Add password to update if provided
    if (!empty($password)) {
        $sql .= ", password = ?";
        $types .= "s";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Add WHERE clause
    $sql .= " WHERE user_id = ?";
    $types .= "i";
    $params[] = $userId;
    
    // Prepare and execute statement
    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if($stmt->execute()) {
        echo json_encode(array(
            'success' => true,
            'messages' => 'User updated successfully'
        ));
    } else {
        echo json_encode(array(
            'success' => false,
            'messages' => 'Error while updating user: ' . $connect->error
        ));
    }
    
    $stmt->close();
    $connect->close();
} 