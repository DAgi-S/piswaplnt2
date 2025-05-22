<?php
require_once 'php_action/db_connect.php';

// Array of SQL statements in the correct order
$sql_statements = [
    // 1. Create warehouse_zones table
    "CREATE TABLE IF NOT EXISTS warehouse_zones (
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
        KEY idx_warehouse_id (warehouse_id),
        KEY idx_created_by (created_by),
        CONSTRAINT fk_zone_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT fk_zone_user FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    // 2. Create storage_locations table
    "CREATE TABLE IF NOT EXISTS storage_locations (
        id INT(11) NOT NULL AUTO_INCREMENT,
        zone_id INT(11) NOT NULL,
        location_code VARCHAR(20) NOT NULL,
        rack_number VARCHAR(20),
        shelf_number VARCHAR(20),
        bin_number VARCHAR(20),
        capacity DECIMAL(10,2),
        current_utilization DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('empty','partial','full','maintenance','blocked') DEFAULT 'empty',
        item_type ENUM('raw_material','finished_good','mixed') DEFAULT 'mixed',
        qr_code VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT(11),
        PRIMARY KEY (id),
        UNIQUE KEY unique_location_code (zone_id, location_code),
        KEY idx_zone_id (zone_id),
        KEY idx_created_by (created_by),
        CONSTRAINT fk_location_zone FOREIGN KEY (zone_id) REFERENCES warehouse_zones(id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT fk_location_user FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    // 3. Update purchase_items table
    "ALTER TABLE purchase_items 
    ADD COLUMN IF NOT EXISTS warehouse_id INT(11) NULL AFTER product_id,
    ADD COLUMN IF NOT EXISTS location_id INT(11) NULL AFTER warehouse_id",

    // 4. Add foreign key constraints to purchase_items
    "ALTER TABLE purchase_items
    ADD CONSTRAINT fk_purchase_item_warehouse 
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) 
    ON DELETE RESTRICT ON UPDATE CASCADE",

    "ALTER TABLE purchase_items
    ADD CONSTRAINT fk_purchase_item_location 
    FOREIGN KEY (location_id) REFERENCES storage_locations(id) 
    ON DELETE RESTRICT ON UPDATE CASCADE"
];

// Execute each SQL statement
foreach ($sql_statements as $sql) {
    if (!$connect->query($sql)) {
        echo "Error executing SQL: " . $connect->error . "\n";
        echo "Failed SQL: " . substr($sql, 0, 150) . "...\n\n";
    } else {
        echo "Successfully executed SQL statement.\n";
    }
}

// Verify the tables were created
$tables = ['warehouse_zones', 'storage_locations', 'purchase_items'];
foreach ($tables as $table) {
    $query = "SHOW CREATE TABLE " . $table;
    $result = $connect->query($query);
    if ($result && $row = $result->fetch_array(MYSQLI_NUM)) {
        echo "\n=== Table: " . $table . " ===\n";
        echo $row[1] . "\n\n";
    }
}

$connect->close();
?> 