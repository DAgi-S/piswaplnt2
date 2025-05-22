<?php
require_once 'core.php';
require_once 'db_connect.php';

// Enable error reporting and output buffering
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_implicit_flush(true);
ob_end_flush();

$sql = "SELECT order_number, order_date, created_at 
        FROM sales_orders 
        WHERE order_number IN ('SO-20250301-0001', 'SO-20250301-0003') 
        ORDER BY order_number";

$result = $connect->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        fwrite(STDOUT, "Order Number: " . $row['order_number'] . "\n");
        fwrite(STDOUT, "Order Date: " . $row['order_date'] . "\n");
        fwrite(STDOUT, "Created At: " . $row['created_at'] . "\n");
        fwrite(STDOUT, "-------------------\n");
        flush();
    }
} else {
    fwrite(STDERR, "Error: " . $connect->error . "\n");
}

$connect->close();
?> 