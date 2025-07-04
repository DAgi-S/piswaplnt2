<?php
require_once 'db_connect.php';
require_once 'core.php';
require_once 'middleware.php';

header('Content-Type: application/json');

// Check session
if (!isset($_SESSION['userId'])) {
    echo json_encode([
        'error' => true,
        'message' => 'No active session found'
    ]);
    exit();
}

try {
    // Check permission
    if(!hasPermission('delete_user')) {
        throw new Exception('Permission denied: delete_user required');
    }

    // Validate input
    if(!isset($_POST['userId'])) {
        throw new Exception('Missing user ID');
    }

    $userId = (int)$_POST['userId'];
    
    // Don't allow deletion of own account
    if($userId === (int)$_SESSION['userId']) {
        throw new Exception('Cannot delete your own account');
    }

    // Check if user exists
    $stmt = $connect->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    if($stmt->get_result()->num_rows === 0) {
        throw new Exception('User not found');
    }
    
    // Delete user
    $stmt = $connect->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    
    if(!$stmt->execute()) {
        throw new Exception('Error removing user: ' . $stmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'messages' => 'User removed successfully'
    ]);

} catch (Exception $e) {
    error_log('Error in removeUser.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'messages' => $e->getMessage()
    ]);
}

$connect->close();