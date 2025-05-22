<?php
require_once 'db_connect.php';
require_once 'core.php';
require_once 'middleware.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['userId'])) {
        throw new Exception('No active session found');
    }
    
    if ($_SESSION['roleId'] !== 2 && !hasPermission('edit_user')) {
        throw new Exception('Access denied. Insufficient privileges.');
    }

    // Validate input
    if(!isset($_POST['userid']) || !isset($_POST['edituserName']) || !isset($_POST['editRoleId'])) {
        throw new Exception('Missing required fields');
    }

    $userId = (int)$_POST['userid'];
    $userName = mysqli_real_escape_string($connect, trim($_POST['edituserName']));
    $email = mysqli_real_escape_string($connect, trim($_POST['editEmail']));
    $roleId = (int)$_POST['editRoleId'];

    if (empty($userName) || empty($email) || $roleId <= 0) {
        throw new Exception('Invalid input data');
    }

    // Check if username exists for other users
    $stmt = $connect->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
    $stmt->bind_param("si", $userName, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        throw new Exception('Username already exists');
    }

    // Build update query based on provided data
    $updateFields = ["username = ?", "email = ?", "role_id = ?", "updated_at = NOW()"];
    $params = [$userName, $email, $roleId];
    $types = "ssi";

    // Add password if provided
    if(!empty($_POST['editPassword'])) {
        $password = password_hash($_POST['editPassword'], PASSWORD_DEFAULT);
        $updateFields[] = "password = ?";
        $params[] = $password;
        $types .= "s";
    }

    // Add user ID to parameters
    $params[] = $userId;
    $types .= "i";

    // Prepare and execute update query
    $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE user_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if(!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'messages' => 'User updated successfully'
    ]);

} catch (Exception $e) {
    error_log('Error in editUser.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'messages' => $e->getMessage()
    ]);
}

$connect->close();
 
