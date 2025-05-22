<?php
// Database connection
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "pistocklnt";

$connect = new mysqli($localhost, $username, $password, $dbname);

if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Check if admin exists
$sql = "SELECT COUNT(*) as count FROM users WHERE role_id = 1";
$result = $connect->query($sql);
$row = $result->fetch_assoc();

echo "<h2>Current Users:</h2>";
$sql = "SELECT user_id, username, role_id FROM users";
$result = $connect->query($sql);
echo "<pre>";
while($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// If no admin exists, create one
if($row['count'] == 0) {
    $username = "admin";
    $password = password_hash("admin123", PASSWORD_DEFAULT);
    $email = "admin@example.com";
    
    $sql = "INSERT INTO users (username, password, email, role_id, status) VALUES (?, ?, ?, 1, 1)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("sss", $username, $password, $email);
    
    if($stmt->execute()) {
        echo "Created default admin user (username: admin, password: admin123)";
    } else {
        echo "Error creating admin: " . $connect->error;
    }
}

$connect->close();
?> 