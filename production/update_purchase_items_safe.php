<?php
require_once 'php_action/db_connect.php';

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

// Drop existing constraints if they exist
$constraints = ['fk_purchase_item_warehouse', 'fk_purchase_item_location'];
foreach ($constraints as $constraint) {
    if (constraintExists($connect, 'purchase_items', $constraint)) {
        $sql = "ALTER TABLE purchase_items DROP FOREIGN KEY $constraint";
        if (!$connect->query($sql)) {
            echo "Error dropping constraint $constraint: " . $connect->error . "\n";
        } else {
            echo "Successfully dropped constraint $constraint\n";
        }
    }
}

// Array of SQL statements
$sql_statements = [
    // Add columns if they don't exist
    "ALTER TABLE purchase_items 
    ADD COLUMN IF NOT EXISTS warehouse_id INT(11) NULL AFTER product_id,
    ADD COLUMN IF NOT EXISTS location_id INT(11) NULL AFTER warehouse_id",

    // Add foreign key constraints
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
        echo "Failed SQL: " . $sql . "\n\n";
    } else {
        echo "Successfully executed: " . $sql . "\n\n";
    }
}

// Verify the table structure
$query = "SHOW CREATE TABLE purchase_items";
$result = $connect->query($query);

if ($result && $row = $result->fetch_array(MYSQLI_NUM)) {
    echo "\n=== Updated Table Structure: purchase_items ===\n";
    echo $row[1] . "\n";
}

$connect->close();
?> 