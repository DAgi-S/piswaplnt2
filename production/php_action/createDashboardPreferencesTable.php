<?php
// For setup only - direct database connection
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "pistocklnt";

// Create connection
$connect = new mysqli($localhost, $username, $password, $dbname);

// Check connection
if ($connect->connect_error) {
    die(json_encode(array('success' => false, 'message' => 'Connection failed: ' . $connect->connect_error)));
}

try {
    // Start transaction
    $connect->begin_transaction();
    
    // Drop existing tables if they exist
    $connect->query("DROP TABLE IF EXISTS user_dashboard_preferences");
    $connect->query("DROP TABLE IF EXISTS dashboard_available_components");
    
    // Create dashboard_available_components table
    $query = "CREATE TABLE IF NOT EXISTS dashboard_available_components (
        id INT(11) NOT NULL AUTO_INCREMENT,
        section_type ENUM('buttons', 'cards', 'analytics') NOT NULL,
        component_key VARCHAR(50) NOT NULL,
        component_name VARCHAR(100) NOT NULL,
        component_icon VARCHAR(50) NOT NULL,
        is_default TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_component (section_type, component_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $connect->query($query);
    
    // Create user_dashboard_preferences table
    $query = "CREATE TABLE IF NOT EXISTS user_dashboard_preferences (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        section_type ENUM('buttons', 'cards', 'analytics') NOT NULL,
        component_key VARCHAR(50) NOT NULL,
        position INT(11) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_user_component (user_id, section_type, component_key),
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $connect->query($query);
    
    // Commit transaction
    $connect->commit();
    
    echo json_encode(array('success' => true, 'message' => 'Successfully created dashboard preferences tables'));
    
} catch (Exception $e) {
    // Rollback on error
    $connect->rollback();
    echo json_encode(array('success' => false, 'message' => 'Error creating tables: ' . $e->getMessage()));
}

$connect->close();
?> 