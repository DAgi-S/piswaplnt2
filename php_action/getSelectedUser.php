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

    if(!isset($_POST['userId'])) {
        throw new Exception('Missing user ID');
    }

    $userId = (int)$_POST['userId'];
    
    $sql = "SELECT u.*, ur.role_name 
            FROM users u 
            LEFT JOIN user_roles ur ON u.role_id = ur.role_id 
            WHERE u.user_id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'username' => $row['username'],
            'email' => $row['email'],
            'role_id' => $row['role_id'],
            'role_name' => $row['role_name'],
            'status' => $row['status']
        ]);
    } else {
        throw new Exception('User not found');
    }

} catch (Exception $e) {
    error_log('Error in getSelectedUser.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'messages' => $e->getMessage()
    ]);
}

$connect->close(); 