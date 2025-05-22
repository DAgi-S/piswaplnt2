<?php
// Direct database connection for setup
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "pistocklnt";

// Create connection
$connect = new mysqli($localhost, $username, $password, $dbname);

// Check connection
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Create users table
$createTableQuery = "CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `language` varchar(10) DEFAULT 'en',
  `timezone` varchar(100) DEFAULT 'UTC',
  `notify_updates` tinyint(1) DEFAULT 0,
  `notify_alerts` tinyint(1) DEFAULT 0,
  `notify_reports` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dashboard_preferences` text DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

try {
    if($connect->query($createTableQuery)) {
        echo "Successfully created users table<br>";
        
        // Check if there are any users, if not create a default admin user
        $checkUsers = $connect->query("SELECT COUNT(*) as count FROM users");
        $userCount = $checkUsers->fetch_assoc()['count'];
        
        if($userCount == 0) {
            // Create default admin user
            $username = "admin";
            $password = password_hash("admin123", PASSWORD_DEFAULT);
            $email = "admin@example.com";
            
            $insertAdmin = "INSERT INTO users (username, password, email, full_name, role_id, status) 
                           VALUES (?, ?, ?, 'System Admin', 1, 1)";
            
            $stmt = $connect->prepare($insertAdmin);
            $stmt->bind_param("sss", $username, $password, $email);
            
            if($stmt->execute()) {
                echo "Created default admin user (username: admin, password: admin123)";
            } else {
                echo "Error creating default admin user: " . $connect->error;
            }
        }
    } else {
        echo "Error creating table: " . $connect->error;
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}

$connect->close();
?> 