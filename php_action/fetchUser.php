<?php
require_once 'db_connect.php';
require_once 'core.php';
require_once 'middleware.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['userId'])) {
        throw new Exception('No active session found');
    }
    
    if ($_SESSION['roleId'] !== 2 && !hasPermission('view_user')) {
        throw new Exception('Access denied. Insufficient privileges.');
    }

    $sql = "SELECT u.user_id, u.username, u.email, u.status, u.role_id, 
            ur.role_name as role
            FROM users u 
            LEFT JOIN user_roles ur ON u.role_id = ur.role_id 
            ORDER BY u.username ASC";
            
    $result = $connect->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $connect->error);
    }

    $output = array();
    while($row = $result->fetch_assoc()) {
        $output[] = array(
            'user_id' => $row['user_id'],
            'username' => htmlspecialchars($row['username']),
            'email' => htmlspecialchars($row['email']),
            'role' => htmlspecialchars($row['role']),
            'status' => (int)$row['status'],
            'role_id' => (int)$row['role_id']
        );
    }

    echo json_encode($output);

} catch (Exception $e) {
    error_log('Error in fetchUser.php: ' . $e->getMessage());
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}

$connect->close();