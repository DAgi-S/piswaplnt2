<?php
require_once 'core.php';
require_once 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Update SO-20250301-0003
$sql = "UPDATE sales_orders 
        SET order_date = '2025-03-01' 
        WHERE order_number = 'SO-20250301-0003'";

if ($connect->query($sql)) {
    echo "Updated SO-20250301-0003 successfully\n";
} else {
    echo "Error updating SO-20250301-0003: " . $connect->error . "\n";
}

// Update SO-20250301-0001
$sql = "UPDATE sales_orders 
        SET order_date = '2025-03-01' 
        WHERE order_number = 'SO-20250301-0001'";

if ($connect->query($sql)) {
    echo "Updated SO-20250301-0001 successfully\n";
} else {
    echo "Error updating SO-20250301-0001: " . $connect->error . "\n";
}

// Verify the updates
$sql = "SELECT order_number, order_date, created_at 
        FROM sales_orders 
        WHERE order_number IN ('SO-20250301-0001', 'SO-20250301-0003') 
        ORDER BY order_number";

$result = $connect->query($sql);

if ($result) {
    echo "\nVerification Results:\n";
    echo "====================\n";
    while ($row = $result->fetch_assoc()) {
        echo "Order: " . $row['order_number'] . "\n";
        echo "Order Date: " . $row['order_date'] . "\n";
        echo "Created At: " . $row['created_at'] . "\n\n";
    }
}

$connect->close();
?> 