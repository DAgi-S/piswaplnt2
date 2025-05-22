<?php
require_once 'php_action/db_connect.php';

$tables = ['warehouses', 'warehouse_stock', 'warehouse_stock_movements', 'warehouse_settings'];
$structures = [];

foreach ($tables as $table) {
    $query = "SHOW CREATE TABLE " . $table;
    $result = $connect->query($query);
    if ($result) {
        $row = $result->fetch_row();
        echo "\n\n=== Table: " . $table . " ===\n";
        echo $row[1] . "\n";
    } else {
        echo "\nError getting structure for table: " . $table . "\n";
        echo "Error: " . $connect->error . "\n";
    }
}

$sql = "CREATE TABLE warehouse_zones (
    id INT(11) NOT NULL AUTO_INCREMENT,
    warehouse_id INT(11) NOT NULL,
    zone_code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    capacity DECIMAL(10,2),
    temperature_min DECIMAL(5,2),
    temperature_max DECIMAL(5,2),
    humidity_min DECIMAL(5,2),
    humidity_max DECIMAL(5,2),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT(11),
    PRIMARY KEY (id),
    UNIQUE KEY unique_zone_code (warehouse_id, zone_code),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if (!$connect->query($sql)) {
    echo "Error creating table: " . $connect->error . "\n";
    
    // Check parent tables
    echo "\nChecking warehouses table:\n";
    $result = $connect->query("SHOW CREATE TABLE warehouses");
    if ($result) {
        $row = $result->fetch_row();
        echo $row[1] . "\n";
    }
    
    echo "\nChecking users table:\n";
    $result = $connect->query("SHOW CREATE TABLE users");
    if ($result) {
        $row = $result->fetch_row();
        echo $row[1] . "\n";
    }
}

$connect->close();
?> 