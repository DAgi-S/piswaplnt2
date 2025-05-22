<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the base path
$base_path = dirname(dirname(__FILE__));

// Include core files using absolute paths
require_once $base_path . '/php_action/core.php';
require_once $base_path . '/php_action/db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['userId'])) {
    header('location: ../index.php');
    exit();
}

// Get user role from database if not set in session
if (!isset($_SESSION['userRole'])) {
    $userId = $_SESSION['userId'];
    $sql = "SELECT role_id FROM users WHERE user_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $_SESSION['userRole'] = $row['role_id'];
    }
}
?> 