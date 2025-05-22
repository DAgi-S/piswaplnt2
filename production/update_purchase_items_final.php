<?php
require_once 'php_action/db_connect.php';

// Function to check if a column exists
function columnExists($connect, $tableName, $columnName) {
    $query = "SHOW COLUMNS FROM $tableName LIKE '$columnName'";
    $result = $connect->query($query);
    return $result && $result->num_rows > 0;
}

// Function to check if a constraint exists
function constraintExists($connect, $tableName, $constraintName) {
    $query = "SELECT CONSTRAINT_NAME 
              FROM information_schema.TABLE_CONSTRAINTS 
              WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = '$tableName' 
              AND CONSTRAINT_NAME = '$constraintName'";
    $result = $connect->query($query);
    return $result && $result->num_rows > 0;
}

try {
    // Start transaction
    $connect->begin_transaction();

    // Drop existing constraints if they exist
    $constraints = [
        'fk_purchase_item_warehouse',
        'fk_purchase_item_location',
        'purchase_id',
        'product_id'
    ];

    foreach ($constraints as $constraint) {
        if (constraintExists($connect, 'purchase_items', $constraint)) {
            $sql = "ALTER TABLE purchase_items DROP FOREIGN KEY $constraint";
            $connect->query($sql);
        }
    }

    // Array of columns to add/modify
    $columns = [
        "raw_material_id" => "ADD COLUMN raw_material_id INT(11) NULL AFTER product_id",
        "warehouse_id" => "ADD COLUMN warehouse_id INT(11) NULL AFTER raw_material_id",
        "location_id" => "ADD COLUMN location_id INT(11) NULL AFTER warehouse_id"
    ];

    // Add columns if they don't exist
    foreach ($columns as $column => $sql) {
        if (!columnExists($connect, 'purchase_items', $column)) {
            $alterSql = "ALTER TABLE purchase_items $sql";
            if (!$connect->query($alterSql)) {
                throw new Exception("Error adding column $column: " . $connect->error);
            }
            echo "Added column $column successfully\n";
        }
    }

    // Add foreign key constraints
    $constraints = [
        "ALTER TABLE purchase_items
        ADD CONSTRAINT fk_purchase_item_raw_material 
        FOREIGN KEY (raw_material_id) REFERENCES raw_materials(id) 
        ON DELETE RESTRICT ON UPDATE CASCADE",

        "ALTER TABLE purchase_items
        ADD CONSTRAINT fk_purchase_item_warehouse 
        FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) 
        ON DELETE RESTRICT ON UPDATE CASCADE",

        "ALTER TABLE purchase_items
        ADD CONSTRAINT fk_purchase_item_location 
        FOREIGN KEY (location_id) REFERENCES storage_locations(id) 
        ON DELETE RESTRICT ON UPDATE CASCADE"
    ];

    foreach ($constraints as $sql) {
        if (!$connect->query($sql)) {
            throw new Exception("Error adding constraint: " . $connect->error);
        }
        echo "Added constraint successfully\n";
    }

    // Commit transaction
    $connect->commit();

    // Verify the table structure
    $query = "SHOW CREATE TABLE purchase_items";
    $result = $connect->query($query);

    if ($result && $row = $result->fetch_array(MYSQLI_NUM)) {
        echo "\n=== Updated Table Structure: purchase_items ===\n";
        echo $row[1] . "\n";
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    echo "Error: " . $e->getMessage() . "\n";
}

$connect->close();
?> 