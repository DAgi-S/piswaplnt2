<?php
require_once 'php_action/db_connect.php';

$tables = [
    'warehouses',
    'warehouse_stock',
    'warehouse_stock_movements',
    'warehouse_settings'
];

foreach ($tables as $table) {
    $query = "SHOW CREATE TABLE " . $table;
    $result = $connect->query($query);
    
    if ($result && $row = $result->fetch_array(MYSQLI_NUM)) {
        echo "\n=== Table: " . $table . " ===\n";
        echo $row[1] . "\n\n";
    } else {
        echo "\nError getting structure for table: " . $table . "\n";
        if ($connect->error) {
            echo "MySQL Error: " . $connect->error . "\n";
        }
    }
}

$connect->close();
?> 