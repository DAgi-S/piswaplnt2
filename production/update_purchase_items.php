<?php
require_once 'php_action/db_connect.php';

// Array to store SQL statements
$sql_statements = [
    // Add new columns
    "ALTER TABLE purchase_items 
    ADD COLUMN warehouse_id INT(11) NULL AFTER product_id,
    ADD COLUMN location_id INT(11) NULL AFTER warehouse_id",

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

// Verify the table structure after changes
$query = "SHOW CREATE TABLE purchase_items";
$result = $connect->query($query);

if ($result && $row = $result->fetch_array(MYSQLI_NUM)) {
    echo "\n=== Updated Table Structure: purchase_items ===\n";
    echo $row[1] . "\n";
}

$connect->close();
?> 