<?php
require_once 'db_connect.php';
require_once 'core.php';
require_once 'middleware.php';

// Ensure no whitespace or output before headers
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['userId'])) {
    echo json_encode([
        'success' => false,
        'messages' => 'No active session found'
    ]);
    exit();
}

try {
    error_log('Session data: ' . print_r($_SESSION, true));
    error_log('POST data: ' . print_r($_POST, true));
    
    if (!isset($_SESSION['roleId'])) {
        throw new Exception('Role ID not found in session');
    }
    
    if ($_SESSION['roleId'] !== 2 && !hasPermission('create_user')) {
        throw new Exception('Access denied. Insufficient privileges.');
    }

    // Validate input
    if (!isset($_POST['username']) || !isset($_POST['email']) || 
        !isset($_POST['password']) || !isset($_POST['roleId'])) {
        throw new Exception('Missing required fields');
    }

    $username = mysqli_real_escape_string($connect, trim($_POST['username']));
    $email = mysqli_real_escape_string($connect, trim($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $roleId = (int)$_POST['roleId'];
    
    if (empty($username) || empty($email) || empty($password) || $roleId <= 0) {
        throw new Exception('Invalid input data');
    }
    
    // Check if username already exists
    $stmt = $connect->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception('Username already exists');
    }
    
    // Insert new user
    $stmt = $connect->prepare(
        "INSERT INTO users (username, email, password, role_id, status) 
         VALUES (?, ?, ?, ?, 1)"
    );
    $stmt->bind_param("sssi", $username, $email, $password, $roleId);
    
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'messages' => 'User created successfully'
    ]);

} catch (Exception $e) {
    error_log('Error in createUser.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'messages' => $e->getMessage()
    ]);
}

$connect->close();
 
