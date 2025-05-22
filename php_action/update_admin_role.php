<?php
require_once 'db_connect.php';

// First, ensure the admin role exists
$sql = "INSERT INTO user_roles (role_id, role_name, description) 
        VALUES (2, 'admin', 'Administrator with full system access')
        ON DUPLICATE KEY UPDATE role_name = 'admin', description = 'Administrator with full system access'";
$connect->query($sql);

// Then update user 1 to have the admin role
$sql = "UPDATE users SET role_id = 2 WHERE user_id = 1";
$connect->query($sql);

echo "Admin role updated successfully"; 