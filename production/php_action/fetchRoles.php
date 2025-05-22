<?php
require_once 'core.php';
require_once 'db_connect.php';

// Check if user has admin role
if (!isset($_SESSION['roleId']) || $_SESSION['roleId'] != 2) {
    echo json_encode(array());
    exit();
}

$sql = "SELECT role_id, role_name, description FROM user_roles ORDER BY role_name ASC";
$result = $connect->query($sql);

$roles = array();
if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $roles[] = array(
            'role_id' => $row['role_id'],
            'role_name' => $row['role_name'],
            'description' => $row['description']
        );
    }
}

echo json_encode($roles);
$connect->close(); 