<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "pistocklnt";

try {
    $connect = new mysqli($localhost, $username, $password, $dbname);

    if($connect->connect_error) {
        throw new Exception("Connection Failed: " . $connect->connect_error);
    }

    echo "<h2>Setting up database tables...</h2>";

    // Create user_roles table
    $sql = "CREATE TABLE IF NOT EXISTS user_roles (
        role_id INT PRIMARY KEY AUTO_INCREMENT,
        role_name VARCHAR(50) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if($connect->query($sql)) {
        echo "User roles table created successfully<br>";
    } else {
        throw new Exception("Error creating user_roles table: " . $connect->error);
    }

    // Insert default roles if they don't exist
    $roles = [
        [1, 'Super Admin', 'Full system access'],
        [2, 'Admin', 'System administrator'],
        [3, 'User', 'Regular user']
    ];

    foreach($roles as $role) {
        $sql = "INSERT IGNORE INTO user_roles (role_id, role_name, description) VALUES (?, ?, ?)";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("iss", $role[0], $role[1], $role[2]);
        $stmt->execute();
    }
    echo "Default roles created<br>";

    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        user_id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        role_id INT,
        status TINYINT(1) DEFAULT 1,
        full_name VARCHAR(255),
        phone VARCHAR(50),
        language VARCHAR(10) DEFAULT 'en',
        timezone VARCHAR(50) DEFAULT 'UTC',
        notify_updates TINYINT(1) DEFAULT 0,
        notify_alerts TINYINT(1) DEFAULT 0,
        notify_reports TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES user_roles(role_id)
    )";

    if($connect->query($sql)) {
        echo "Users table created successfully<br>";
    } else {
        throw new Exception("Error creating users table: " . $connect->error);
    }

    // Create default admin user if it doesn't exist
    $adminUsername = "admin";
    $adminPassword = password_hash("admin123", PASSWORD_DEFAULT);
    $adminEmail = "admin@example.com";
    $adminRole = 1; // Super Admin

    $sql = "INSERT IGNORE INTO users (username, password, email, role_id, status) VALUES (?, ?, ?, ?, 1)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("sssi", $adminUsername, $adminPassword, $adminEmail, $adminRole);
    
    if($stmt->execute()) {
        echo "Default admin user created (username: admin, password: admin123)<br>";
    } else {
        throw new Exception("Error creating admin user: " . $connect->error);
    }

    echo "<h3>Setup completed successfully!</h3>";
    echo "<p>You can now log in with:</p>";
    echo "<ul>";
    echo "<li>Username: admin</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";
    echo "<p><a href='login.php'>Go to login page</a></p>";

} catch(Exception $e) {
    die("<div style='color: red; margin: 20px;'><h3>Error:</h3>" . $e->getMessage() . "</div>");
}
?> 