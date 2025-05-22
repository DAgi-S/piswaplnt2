<?php
require_once 'core.php';
require_once 'db_connect.php';

// Check if user has admin role
if (!isset($_SESSION['roleId']) || $_SESSION['roleId'] != 2) {
    echo json_encode(array('success' => false, 'messages' => 'Access denied'));
    exit();
}

if ($_POST) {
    // Required fields
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $roleId = $_POST['roleId'];
    
    // Optional fields with defaults
    $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : null;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    $language = isset($_POST['language']) ? trim($_POST['language']) : 'en';
    $timezone = isset($_POST['timezone']) ? trim($_POST['timezone']) : 'UTC';
    $notifyUpdates = isset($_POST['notify_updates']) ? 1 : 0;
    $notifyAlerts = isset($_POST['notify_alerts']) ? 1 : 0;
    $notifyReports = isset($_POST['notify_reports']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 1;
    
    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($roleId)) {
        echo json_encode(array('success' => false, 'messages' => 'Required fields cannot be empty'));
        exit();
    }
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(array('success' => false, 'messages' => 'Invalid email format'));
        exit();
    }
    
    // Check if username exists
    $stmt = $connect->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if($stmt->get_result()->num_rows > 0) {
        echo json_encode(array('success' => false, 'messages' => 'Username already exists'));
        exit();
    }
    
    // Check if email exists
    $stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if($stmt->get_result()->num_rows > 0) {
        echo json_encode(array('success' => false, 'messages' => 'Email already exists'));
        exit();
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $sql = "INSERT INTO users (
                username, 
                email, 
                password, 
                role_id, 
                full_name, 
                phone, 
                language, 
                timezone, 
                notify_updates, 
                notify_alerts, 
                notify_reports, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param(
        "sssissssiiii", 
        $username, 
        $email, 
        $hashedPassword, 
        $roleId, 
        $fullName, 
        $phone, 
        $language, 
        $timezone, 
        $notifyUpdates, 
        $notifyAlerts, 
        $notifyReports, 
        $status
    );
    
    if($stmt->execute()) {
        echo json_encode(array(
            'success' => true, 
            'messages' => 'User created successfully'
        ));
    } else {
        echo json_encode(array(
            'success' => false, 
            'messages' => 'Error while creating user: ' . $connect->error
        ));
    }
    
    $stmt->close();
    $connect->close();
} 