<?php
require_once 'core.php';
require_once 'db_connect.php';

// Check if user has admin role
if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'admin') {
    echo json_encode(array('success' => false, 'message' => 'Access denied'));
    exit();
}

if ($_POST) {
    $response = array();
    
    $roleId = $_POST['roleId'];

    // Check if role has assigned users
    $sql = "SELECT COUNT(*) as user_count FROM users WHERE role_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userCount = $result->fetch_assoc()['user_count'];

    $response['success'] = true;
    $response['hasUsers'] = ($userCount > 0);

    $stmt->close();
    $connect->close();

    echo json_encode($response);
} 