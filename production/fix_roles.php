<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "pistocklnt";

$connect = new mysqli($localhost, $username, $password, $dbname);

if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

echo "<h2>Fixing Roles Setup</h2>";

// 1. Create roles table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if($connect->query($sql)) {
    echo "Roles table created/verified<br>";
} else {
    echo "Error creating roles table: " . $connect->error . "<br>";
}

// 2. Insert default roles if they don't exist
$roles = array(
    array(1, 'admin', 'System Administrator'),
    array(2, 'user', 'Regular User')
);

foreach($roles as $role) {
    $sql = "INSERT IGNORE INTO roles (role_id, role_name, description) VALUES (?, ?, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("iss", $role[0], $role[1], $role[2]);
    if($stmt->execute()) {
        echo "Role '{$role[1]}' verified/created<br>";
    } else {
        echo "Error with role '{$role[1]}': " . $connect->error . "<br>";
    }
}

// 3. Create default admin user if doesn't exist
$sql = "SELECT user_id FROM users WHERE username = 'admin'";
$result = $connect->query($sql);

if($result->num_rows == 0) {
    $username = "admin";
    $password = password_hash("admin123", PASSWORD_DEFAULT);
    $email = "admin@example.com";
    
    $sql = "INSERT INTO users (username, password, email, role_id, status) VALUES (?, ?, ?, 1, 1)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("sss", $username, $password, $email);
    
    if($stmt->execute()) {
        echo "Created default admin user (username: admin, password: admin123)<br>";
    } else {
        echo "Error creating admin: " . $connect->error . "<br>";
    }
} else {
    echo "Admin user already exists<br>";
}

// 4. Show current users and their roles
echo "<h2>Current Users and Roles:</h2>";
$sql = "SELECT u.user_id, u.username, u.role_id, u.status, r.role_name 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.role_id";
$result = $connect->query($sql);

if($result) {
    while($row = $result->fetch_assoc()) {
        echo "User ID: " . $row['user_id'] . 
             ", Username: " . $row['username'] . 
             ", Role ID: " . $row['role_id'] . 
             ", Role Name: " . $row['role_name'] . 
             ", Status: " . $row['status'] . "<br>";
    }
} else {
    echo "Error querying users: " . $connect->error;
}

$connect->close();
?> 