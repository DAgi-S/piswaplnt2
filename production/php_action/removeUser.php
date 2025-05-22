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
    
    // Don't allow deletion of own account
    if ($userId == $_SESSION['userId']) {
        echo json_encode(array('success' => false, 'messages' => 'You cannot delete your own account'));
        exit();
    }
    
    // Check if user exists
    $stmt = $connect->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    if($stmt->get_result()->num_rows == 0) {
        echo json_encode(array('success' => false, 'messages' => 'User not found'));
        exit();
    }
    
    // Delete user
    $sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $userId);
    
    if($stmt->execute()) {
        echo json_encode(array(
            'success' => true,
            'messages' => 'User deleted successfully'
        ));
    } else {
        echo json_encode(array(
            'success' => false,
            'messages' => 'Error while deleting user: ' . $connect->error
        ));
    }
    
    $stmt->close();
    $connect->close();
} 