<?php
require_once 'core.php';
require_once 'db_connect.php';

// Check if user has admin role
if (!isset($_SESSION['roleId']) || $_SESSION['roleId'] != 2) {
    echo json_encode(array('data' => array()));
    exit();
}

$sql = "SELECT u.user_id, u.username, u.email, u.status, u.created_at, u.updated_at, r.role_name 
        FROM users u 
        LEFT JOIN user_roles r ON u.role_id = r.role_id 
        ORDER BY u.user_id DESC";

$result = $connect->query($sql);

$output = array('data' => array());

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $output['data'][] = array(
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'email' => $row['email'],
            'role_name' => $row['role_name'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        );
    }
}

$connect->close();

echo json_encode($output); 