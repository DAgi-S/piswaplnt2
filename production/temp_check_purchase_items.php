<?php
require_once 'php_action/db_connect.php';

// Check purchase_items table structure
$query = "SHOW CREATE TABLE purchase_items";
$result = $connect->query($query);

if ($result && $row = $result->fetch_array(MYSQLI_NUM)) {
    echo "\n=== Table: purchase_items ===\n";
    echo $row[1] . "\n\n";
} else {
    echo "\nError getting structure for table: purchase_items\n";
    if ($connect->error) {
        echo "MySQL Error: " . $connect->error . "\n";
    }
}

$connect->close();
?> 